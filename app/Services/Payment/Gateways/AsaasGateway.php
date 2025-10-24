<?php

namespace App\Services\Payment\Gateways;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AsaasGateway implements PaymentGatewayInterface
{
    protected PaymentGateway $gateway;
    protected string $baseUrl;

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
        $this->baseUrl = $gateway->environment === 'production'
            ? 'https://api.asaas.com/v3'
            : 'https://sandbox.asaas.com/api/v3';
    }

    /**
     * Cria um pagamento no Asaas
     */
    public function createPayment(Invoice $invoice, string $method, array $options = []): Payment
    {
        $tenant = $invoice->tenant;

        // Buscar ou criar cliente no Asaas
        $customerId = $this->getOrCreateCustomer($tenant);

        // Preparar dados do pagamento
        $billingType = $this->mapPaymentMethod($method);

        $data = [
            'customer' => $customerId,
            'billingType' => $billingType,
            'value' => (float) $invoice->total,
            'dueDate' => $invoice->due_date->format('Y-m-d'),
            'description' => "Fatura {$invoice->invoice_number}",
            'externalReference' => $invoice->invoice_number,
        ];

        // Adicionar callback
        if ($tenant->webhook_url ?? null) {
            $data['callback'] = [
                'successUrl' => $tenant->webhook_url . '/payment/success',
                'autoRedirect' => true,
            ];
        }

        // Criar cobrança no Asaas
        $response = Http::withHeaders([
            'access_token' => $this->gateway->getApiKey(),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments", $data);

        if (!$response->successful()) {
            Log::error('Erro ao criar pagamento no Asaas', [
                'invoice_id' => $invoice->id,
                'response' => $response->json(),
            ]);

            throw new \Exception('Erro ao criar pagamento: ' . ($response->json()['errors'][0]['description'] ?? 'Erro desconhecido'));
        }

        $asaasPayment = $response->json();

        // Calcular taxa
        $fee = $this->gateway->calculateFee($invoice->total);
        $netAmount = $invoice->total - $fee;

        // Criar registro de pagamento
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'payment_gateway_id' => $this->gateway->id,
            'payment_number' => Payment::query()->max('id') + 1,
            'transaction_id' => $asaasPayment['id'],
            'payment_method' => $method,
            'amount' => $invoice->total,
            'fee' => $fee,
            'net_amount' => $netAmount,
            'status' => 'pending',
            'gateway_response' => $asaasPayment,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Adicionar dados específicos do método
        if ($method === 'boleto') {
            $payment->update([
                'boleto_url' => $asaasPayment['bankSlipUrl'] ?? null,
                'boleto_barcode' => $asaasPayment['barCode'] ?? null,
                'boleto_digitable_line' => $asaasPayment['identificationField'] ?? null,
            ]);
        } elseif ($method === 'pix') {
            $payment->update([
                'pix_qrcode' => $asaasPayment['encodedImage'] ?? null,
                'pix_qrcode_text' => $asaasPayment['payload'] ?? null,
                'pix_txid' => $asaasPayment['id'],
            ]);
        }

        return $payment->fresh();
    }

    /**
     * Consulta status de um pagamento
     */
    public function checkPaymentStatus(Payment $payment): string
    {
        $response = Http::withHeaders([
            'access_token' => $this->gateway->getApiKey(),
        ])->get("{$this->baseUrl}/payments/{$payment->transaction_id}");

        if (!$response->successful()) {
            Log::error('Erro ao consultar status no Asaas', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);
            return $payment->status;
        }

        $asaasPayment = $response->json();
        $status = $this->mapAsaasStatus($asaasPayment['status']);

        // Atualizar pagamento
        $payment->update([
            'status' => $status,
            'gateway_response' => $asaasPayment,
        ]);

        if ($status === 'approved' || $status === 'completed') {
            $payment->update([
                'authorized_at' => $asaasPayment['confirmedDate'] ?? now(),
                'completed_at' => $asaasPayment['confirmedDate'] ?? now(),
            ]);
        }

        return $status;
    }

    /**
     * Processa webhook do Asaas
     */
    public function processWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $paymentData = $payload['payment'] ?? null;

        if (!$paymentData) {
            Log::warning('Webhook Asaas sem dados de pagamento', ['payload' => $payload]);
            return;
        }

        // Buscar pagamento pelo transaction_id
        $payment = Payment::where('transaction_id', $paymentData['id'])->first();

        if (!$payment) {
            Log::warning('Pagamento não encontrado para webhook Asaas', [
                'transaction_id' => $paymentData['id'],
            ]);
            return;
        }

        // Processar evento
        switch ($event) {
            case 'PAYMENT_RECEIVED':
            case 'PAYMENT_CONFIRMED':
                $payment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'gateway_response' => $paymentData,
                ]);
                break;

            case 'PAYMENT_OVERDUE':
                $payment->invoice->markAsOverdue();
                break;

            case 'PAYMENT_DELETED':
            case 'PAYMENT_REFUNDED':
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'gateway_response' => $paymentData,
                ]);
                break;

            default:
                Log::info('Evento Asaas não tratado', [
                    'event' => $event,
                    'payment_id' => $payment->id,
                ]);
        }
    }

    /**
     * Cancela um pagamento
     */
    public function cancelPayment(Payment $payment): bool
    {
        $response = Http::withHeaders([
            'access_token' => $this->gateway->getApiKey(),
        ])->delete("{$this->baseUrl}/payments/{$payment->transaction_id}");

        if (!$response->successful()) {
            Log::error('Erro ao cancelar pagamento no Asaas', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);
            return false;
        }

        $payment->update(['status' => 'cancelled']);

        return true;
    }

    /**
     * Verifica se o gateway suporta um método de pagamento
     */
    public function supportsMethod(string $method): bool
    {
        $supportedMethods = $this->gateway->supported_methods ?? [];
        return in_array($method, $supportedMethods);
    }

    /**
     * Busca ou cria cliente no Asaas
     */
    protected function getOrCreateCustomer($tenant): string
    {
        // Verificar se já existe customer_id no metadata
        $metadata = $tenant->metadata ?? [];
        if (isset($metadata['asaas_customer_id'])) {
            return $metadata['asaas_customer_id'];
        }

        // Criar cliente no Asaas
        $cpfCnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '');

        $data = [
            'name' => $tenant->name,
            'cpfCnpj' => $cpfCnpj,
            'email' => $tenant->email,
            'phone' => preg_replace('/[^0-9]/', '', $tenant->phone ?? ''),
            'externalReference' => "tenant_{$tenant->id}",
        ];

        $response = Http::withHeaders([
            'access_token' => $this->gateway->getApiKey(),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/customers", $data);

        if (!$response->successful()) {
            throw new \Exception('Erro ao criar cliente no Asaas');
        }

        $customer = $response->json();

        // Salvar customer_id no metadata do tenant
        $metadata['asaas_customer_id'] = $customer['id'];
        $tenant->update(['metadata' => $metadata]);

        return $customer['id'];
    }

    /**
     * Mapeia método de pagamento para Asaas
     */
    protected function mapPaymentMethod(string $method): string
    {
        return match($method) {
            'boleto' => 'BOLETO',
            'pix' => 'PIX',
            'credit_card' => 'CREDIT_CARD',
            default => 'UNDEFINED',
        };
    }

    /**
     * Mapeia status do Asaas para status interno
     */
    protected function mapAsaasStatus(string $asaasStatus): string
    {
        return match($asaasStatus) {
            'PENDING' => 'pending',
            'RECEIVED', 'CONFIRMED' => 'completed',
            'OVERDUE' => 'failed',
            'REFUNDED' => 'refunded',
            'RECEIVED_IN_CASH' => 'completed',
            default => 'pending',
        };
    }
}
