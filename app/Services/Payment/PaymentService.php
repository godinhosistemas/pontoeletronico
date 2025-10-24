<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\Gateways\AsaasGateway;
use App\Services\Payment\Gateways\MercadoPagoGateway;

class PaymentService
{
    /**
     * Cria um pagamento usando o gateway especificado ou padrão
     */
    public function createPayment(
        Invoice $invoice,
        string $method,
        ?int $gatewayId = null,
        array $options = []
    ): Payment {
        // Buscar gateway
        if ($gatewayId) {
            $gateway = PaymentGateway::findOrFail($gatewayId);
        } else {
            $gateway = PaymentGateway::where('is_default', true)
                ->where('is_active', true)
                ->firstOrFail();
        }

        // Verificar se gateway suporta o método
        $gatewayService = $this->getGatewayService($gateway);

        if (!$gatewayService->supportsMethod($method)) {
            throw new \Exception("Gateway {$gateway->name} não suporta o método de pagamento {$method}");
        }

        // Incrementar tentativas de pagamento da fatura
        $invoice->incrementPaymentAttempt();

        // Criar pagamento
        return $gatewayService->createPayment($invoice, $method, $options);
    }

    /**
     * Consulta status de um pagamento
     */
    public function checkPaymentStatus(Payment $payment): string
    {
        $gatewayService = $this->getGatewayService($payment->gateway);
        return $gatewayService->checkPaymentStatus($payment);
    }

    /**
     * Processa webhook de um gateway
     */
    public function processWebhook(PaymentGateway $gateway, array $payload): void
    {
        $gatewayService = $this->getGatewayService($gateway);
        $gatewayService->processWebhook($payload);
    }

    /**
     * Cancela um pagamento
     */
    public function cancelPayment(Payment $payment): bool
    {
        $gatewayService = $this->getGatewayService($payment->gateway);
        return $gatewayService->cancelPayment($payment);
    }

    /**
     * Retorna instância do serviço do gateway
     */
    protected function getGatewayService(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return match($gateway->provider) {
            'asaas' => new AsaasGateway($gateway),
            'mercadopago' => new MercadoPagoGateway($gateway),
            default => throw new \Exception("Gateway {$gateway->provider} não implementado"),
        };
    }

    /**
     * Lista gateways disponíveis
     */
    public function getAvailableGateways(): array
    {
        return PaymentGateway::where('is_active', true)
            ->get()
            ->toArray();
    }

    /**
     * Lista métodos de pagamento disponíveis para um gateway
     */
    public function getAvailablePaymentMethods(?int $gatewayId = null): array
    {
        if ($gatewayId) {
            $gateway = PaymentGateway::find($gatewayId);
        } else {
            $gateway = PaymentGateway::where('is_default', true)
                ->where('is_active', true)
                ->first();
        }

        if (!$gateway) {
            return [];
        }

        return $gateway->supported_methods ?? [];
    }
}
