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
        // Buscar gateway para validação HMAC
        $gateway = PaymentGateway::where('provider', 'mercadopago')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            Log::warning('Gateway Mercado Pago não encontrado ou inativo');
            return response()->json(['error' => 'Gateway not found'], 404);
        }

        // Validar assinatura HMAC (OBRIGATÓRIO para segurança)
        if (!$this->validateMercadoPagoSignature($request, $gateway)) {
            Log::error('Assinatura HMAC inválida no webhook Mercado Pago', [
                'ip' => $request->ip(),
                'signature' => $request->header('x-signature'),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

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

    /**
     * Valida assinatura HMAC do Mercado Pago
     * Documentação: https://www.mercadopago.com.br/developers/en/docs/your-integrations/notifications/webhooks
     */
    protected function validateMercadoPagoSignature(Request $request, PaymentGateway $gateway): bool
    {
        $signature = $request->header('x-signature');
        $requestId = $request->header('x-request-id');

        if (!$signature || !$requestId) {
            Log::warning('Headers x-signature ou x-request-id ausentes');
            return false;
        }

        // Extrair ts e v1 da assinatura
        // Formato: ts=1234567890,v1=abc123def456...
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        $timestamp = $parts['ts'] ?? null;
        $hash = $parts['v1'] ?? null;

        if (!$timestamp || !$hash) {
            Log::warning('Formato de assinatura inválido', ['signature' => $signature]);
            return false;
        }

        // Validar timestamp (não aceitar webhooks com mais de 5 minutos)
        $currentTime = time();
        if (abs($currentTime - $timestamp) > 300) {
            Log::warning('Webhook timestamp muito antigo ou futuro', [
                'timestamp' => $timestamp,
                'current' => $currentTime,
                'diff' => abs($currentTime - $timestamp),
            ]);
            return false;
        }

        // Obter dados do webhook
        $dataId = $request->input('data.id');

        // Construir template conforme documentação
        // Template: id:{data.id};request-id:{x-request-id};ts:{ts};
        $template = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";

        // Obter secret do gateway (deve estar armazenado nas configurações)
        $secret = $gateway->webhook_secret ?? $gateway->api_secret ?? '';

        if (empty($secret)) {
            Log::error('Webhook secret não configurado para Mercado Pago');
            return false;
        }

        // Calcular HMAC-SHA256
        $calculatedHash = hash_hmac('sha256', $template, $secret);

        // Comparar hashes (time-safe comparison)
        return hash_equals($calculatedHash, $hash);
    }
}
