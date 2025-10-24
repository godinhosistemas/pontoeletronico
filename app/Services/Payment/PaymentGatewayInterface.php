<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Cria um pagamento no gateway
     */
    public function createPayment(Invoice $invoice, string $method, array $options = []): Payment;

    /**
     * Consulta status de um pagamento
     */
    public function checkPaymentStatus(Payment $payment): string;

    /**
     * Processa webhook do gateway
     */
    public function processWebhook(array $payload): void;

    /**
     * Cancela um pagamento
     */
    public function cancelPayment(Payment $payment): bool;

    /**
     * Verifica se o gateway suporta um método de pagamento
     */
    public function supportsMethod(string $method): bool;
}
