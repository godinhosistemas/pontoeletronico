<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateway;
use App\Models\PaymentWebhook;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Processa webhook do Asaas
     */
    public function asaas(Request $request)
    {
        return $this->processWebhook($request, 'asaas');
    }

    /**
     * Processa webhook do Mercado Pago
     */
    public function mercadopago(Request $request)
    {
        return $this->processWebhook($request, 'mercadopago');
    }

    /**
     * Processa webhook genérico
     */
    protected function processWebhook(Request $request, string $provider)
    {
        $payload = $request->all();

        // Buscar gateway
        $gateway = PaymentGateway::where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            Log::warning("Gateway {$provider} não encontrado ou inativo", [
                'payload' => $payload,
            ]);
            return response()->json(['error' => 'Gateway not found'], 404);
        }

        // Criar registro de webhook
        $webhook = PaymentWebhook::create([
            'payment_gateway_id' => $gateway->id,
            'event_id' => $this->extractEventId($payload, $provider),
            'event_type' => $this->extractEventType($payload, $provider),
            'payload' => $payload,
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Processar webhook
            $webhook->incrementAttempts();
            $this->paymentService->processWebhook($gateway, $payload);
            $webhook->markAsProcessed();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error("Erro ao processar webhook {$provider}", [
                'webhook_id' => $webhook->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $webhook->markAsFailed($e->getMessage());

            return response()->json([
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extrai ID do evento do payload
     */
    protected function extractEventId(array $payload, string $provider): ?string
    {
        return match($provider) {
            'asaas' => $payload['id'] ?? null,
            'mercadopago' => $payload['id'] ?? null,
            default => null,
        };
    }

    /**
     * Extrai tipo do evento do payload
     */
    protected function extractEventType(array $payload, string $provider): ?string
    {
        return match($provider) {
            'asaas' => $payload['event'] ?? null,
            'mercadopago' => $payload['type'] ?? null,
            default => null,
        };
    }
}
