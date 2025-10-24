<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Lista faturas do tenant
     */
    public function index()
    {
        return view('tenant.billing.index');
    }

    /**
     * Exibe detalhes da fatura
     */
    public function show(Invoice $invoice)
    {
        // Verificar se a fatura pertence ao tenant autenticado
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('tenant.billing.show', compact('invoice'));
    }

    /**
     * Exibe tela de pagamento
     */
    public function payment(Invoice $invoice)
    {
        // Verificar se a fatura pertence ao tenant autenticado
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Verificar se já está paga
        if ($invoice->isPaid()) {
            return redirect()->route('tenant.billing.show', $invoice)
                ->with('info', 'Esta fatura já foi paga.');
        }

        // Buscar gateways disponíveis e métodos de pagamento
        $gateways = $this->paymentService->getAvailableGateways();
        $paymentMethods = $this->paymentService->getAvailablePaymentMethods();

        return view('tenant.billing.payment', compact('invoice', 'gateways', 'paymentMethods'));
    }

    /**
     * Processa criação de pagamento
     */
    public function processPayment(Request $request, Invoice $invoice)
    {
        // Verificar se a fatura pertence ao tenant autenticado
        if ($invoice->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Verificar se já está paga
        if ($invoice->isPaid()) {
            return redirect()->route('tenant.billing.show', $invoice)
                ->with('info', 'Esta fatura já foi paga.');
        }

        $validated = $request->validate([
            'payment_method' => 'required|in:boleto,pix,credit_card',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
        ]);

        try {
            $payment = $this->paymentService->createPayment(
                $invoice,
                $validated['payment_method'],
                $validated['payment_gateway_id'] ?? null
            );

            return redirect()->route('tenant.billing.payment-details', $payment)
                ->with('success', 'Pagamento criado com sucesso!');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Exibe detalhes do pagamento (boleto, PIX, etc)
     */
    public function paymentDetails(Payment $payment)
    {
        // Verificar se o pagamento pertence ao tenant autenticado
        if ($payment->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return view('tenant.billing.payment-details', compact('payment'));
    }

    /**
     * Atualiza status do pagamento
     */
    public function checkPaymentStatus(Payment $payment)
    {
        // Verificar se o pagamento pertence ao tenant autenticado
        if ($payment->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        try {
            $status = $this->paymentService->checkPaymentStatus($payment);

            return response()->json([
                'success' => true,
                'status' => $status,
                'status_badge' => $payment->fresh()->status_badge,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download de segunda via de boleto
     */
    public function downloadBoleto(Payment $payment)
    {
        // Verificar se o pagamento pertence ao tenant autenticado
        if ($payment->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!$payment->isBoleto() || !$payment->boleto_url) {
            return back()->with('error', 'Boleto não disponível.');
        }

        return redirect($payment->boleto_url);
    }
}
