<?php

namespace App\Services\Payment\Gateways;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    protected PaymentGateway $gateway;
    protected string $baseUrl = 'https://api.mercadopago.com/v1';

    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Cria um pagamento no Mercado Pago
     */
    public function createPayment(Invoice $invoice, string $method, array $options = []): Payment
    {
        $tenant = $invoice->tenant;

        // Preparar dados do pagamento
        $data = [
            'transaction_amount' => (float) $invoice->total,
            'description' => "Fatura {$invoice->invoice_number}",
            'payment_method_id' => $this->mapPaymentMethod($method),
            'payer' => [
                'email' => $tenant->email,
            ],
            'external_reference' => $invoice->invoice_number,
            'notification_url' => route('webhook.mercadopago'),
        ];

        // Adicionar informações do pagador
        $cpfCnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '');
        if (strlen($cpfCnpj) === 11) {
            $data['payer']['identification'] = [
                'type' => 'CPF',
                'number' => $cpfCnpj,
            ];
        } elseif (strlen($cpfCnpj) === 14) {
            $data['payer']['identification'] = [
                'type' => 'CNPJ',
                'number' => $cpfCnpj,
            ];
        }

        // Adicionar data de vencimento para boleto
        if ($method === 'boleto') {
            // Formato ISO 8601 com timezone: 2025-10-25T23:59:59.000-03:00
            // Usar timezone de São Paulo (padrão Brasil)
            $dueDate = $invoice->due_date->clone()->setTimezone('America/Sao_Paulo');
            $data['date_of_expiration'] = $dueDate->format('Y-m-d\TH:i:s.000P');
        }

        // Criar pagamento no Mercado Pago
        // X-Idempotency-Key previne criação de pagamentos duplicados em caso de retry
        $idempotencyKey = $this->generateIdempotencyKey($invoice, $method);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->gateway->getApiKey()}",
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => $idempotencyKey,
        ])->post("{$this->baseUrl}/payments", $data);

        if (!$response->successful()) {
            Log::error('Erro ao criar pagamento no Mercado Pago', [
                'invoice_id' => $invoice->id,
                'response' => $response->json(),
            ]);

            $error = $response->json()['message'] ?? 'Erro desconhecido';
            throw new \Exception('Erro ao criar pagamento: ' . $error);
        }

        $mpPayment = $response->json();

        // Calcular taxa
        $fee = $this->gateway->calculateFee($invoice->total);
        $netAmount = $invoice->total - $fee;

        // Criar registro de pagamento
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $invoice->tenant_id,
            'payment_gateway_id' => $this->gateway->id,
            'payment_number' => Payment::query()->max('id') + 1,
            'transaction_id' => $mpPayment['id'],
            'payment_method' => $method,
            'amount' => $invoice->total,
            'fee' => $fee,
            'net_amount' => $netAmount,
            'status' => $this->mapMercadoPagoStatus($mpPayment['status']),
            'gateway_response' => $mpPayment,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Adicionar dados específicos do método
        if ($method === 'boleto') {
            $payment->update([
                'boleto_url' => $mpPayment['transaction_details']['external_resource_url'] ?? null,
                'boleto_barcode' => $mpPayment['barcode']['content'] ?? null,
            ]);
        } elseif ($method === 'pix') {
            $payment->update([
                'pix_qrcode' => $mpPayment['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null,
                'pix_qrcode_text' => $mpPayment['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'pix_txid' => $mpPayment['id'],
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
            'Authorization' => "Bearer {$this->gateway->getApiKey()}",
        ])->get("{$this->baseUrl}/payments/{$payment->transaction_id}");

        if (!$response->successful()) {
            Log::error('Erro ao consultar status no Mercado Pago', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);
            return $payment->status;
        }

        $mpPayment = $response->json();
        $status = $this->mapMercadoPagoStatus($mpPayment['status']);

        // Atualizar pagamento
        $payment->update([
            'status' => $status,
            'gateway_response' => $mpPayment,
        ]);

        if ($status === 'approved' || $status === 'completed') {
            $payment->update([
                'authorized_at' => $mpPayment['date_approved'] ?? now(),
                'completed_at' => $mpPayment['date_approved'] ?? now(),
            ]);
        }

        return $status;
    }

    /**
     * Processa webhook do Mercado Pago
     */
    public function processWebhook(array $payload): void
    {
        $type = $payload['type'] ?? null;
        $dataId = $payload['data']['id'] ?? null;

        if ($type !== 'payment' || !$dataId) {
            Log::warning('Webhook Mercado Pago inválido', ['payload' => $payload]);
            return;
        }

        // Buscar detalhes do pagamento
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->gateway->getApiKey()}",
        ])->get("{$this->baseUrl}/payments/{$dataId}");

        if (!$response->successful()) {
            Log::error('Erro ao buscar pagamento no webhook Mercado Pago', [
                'payment_id' => $dataId,
            ]);
            return;
        }

        $mpPayment = $response->json();

        // Buscar pagamento pelo transaction_id
        $payment = Payment::where('transaction_id', $dataId)->first();

        if (!$payment) {
            Log::warning('Pagamento não encontrado para webhook Mercado Pago', [
                'transaction_id' => $dataId,
            ]);
            return;
        }

        // Atualizar status
        $status = $this->mapMercadoPagoStatus($mpPayment['status']);

        $payment->update([
            'status' => $status,
            'gateway_response' => $mpPayment,
        ]);

        if ($status === 'approved' || $status === 'completed') {
            $payment->update([
                'authorized_at' => $mpPayment['date_approved'] ?? now(),
                'completed_at' => $mpPayment['date_approved'] ?? now(),
            ]);
        } elseif ($status === 'failed') {
            $payment->update([
                'failed_at' => now(),
                'error_message' => $mpPayment['status_detail'] ?? null,
            ]);
        }
    }

    /**
     * Cancela um pagamento
     * Para pagamentos aprovados, faz reembolso
     * Para pagamentos pendentes, cancela diretamente
     */
    public function cancelPayment(Payment $payment): bool
    {
        // Verificar status atual do pagamento
        $currentStatus = $payment->status;

        // Se pagamento foi aprovado/completado, fazer reembolso
        if (in_array($currentStatus, ['approved', 'completed'])) {
            return $this->refundPayment($payment);
        }

        // Para pagamentos pendentes, fazer cancelamento
        // Endpoint correto: PUT /v1/payments/{id} com status=cancelled
        // IMPORTANTE: Só funciona para pagamentos pendentes
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->gateway->getApiKey()}",
            'Content-Type' => 'application/json',
        ])->put("{$this->baseUrl}/payments/{$payment->transaction_id}", [
            'status' => 'cancelled',
        ]);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('Erro ao cancelar pagamento no Mercado Pago', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'status' => $currentStatus,
                'response' => $error,
            ]);
            return false;
        }

        $payment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return true;
    }

    /**
     * Reembolsa um pagamento aprovado
     * Endpoint: POST /v1/payments/{id}/refunds
     */
    protected function refundPayment(Payment $payment, ?float $amount = null): bool
    {
        $data = [];

        // Reembolso parcial ou total
        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->gateway->getApiKey()}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments/{$payment->transaction_id}/refunds", $data);

        if (!$response->successful()) {
            $error = $response->json();
            Log::error('Erro ao reembolsar pagamento no Mercado Pago', [
                'payment_id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $amount,
                'response' => $error,
            ]);
            return false;
        }

        $refundData = $response->json();

        $payment->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_data' => $refundData,
        ]);

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
     * Mapeia método de pagamento para Mercado Pago
     */
    protected function mapPaymentMethod(string $method): string
    {
        return match($method) {
            'boleto' => 'bolbradesco',
            'pix' => 'pix',
            'credit_card' => 'credit_card',
            default => 'pix',
        };
    }

    /**
     * Mapeia status do Mercado Pago para status interno
     */
    protected function mapMercadoPagoStatus(string $mpStatus): string
    {
        return match($mpStatus) {
            'pending' => 'pending',
            'approved' => 'completed',
            'authorized' => 'approved',
            'in_process' => 'processing',
            'in_mediation' => 'processing',
            'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'charged_back' => 'chargeback',
            default => 'pending',
        };
    }

    /**
     * Gera chave de idempotência única para prevenir pagamentos duplicados
     * Baseado em: invoice_id + method + tenant_id + timestamp do dia
     */
    protected function generateIdempotencyKey(Invoice $invoice, string $method): string
    {
        // Usar data do dia para permitir retry em dias diferentes
        $dateKey = now()->format('Y-m-d');

        // Combinar dados únicos da transação
        $data = implode('|', [
            $invoice->id,
            $invoice->tenant_id,
            $method,
            $invoice->invoice_number,
            $dateKey,
        ]);

        // Gerar hash SHA-256
        return hash('sha256', $data);
    }
}
