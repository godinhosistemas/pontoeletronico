<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Lista todos os pagamentos (Super Admin)
     */
    public function index()
    {
        return view('admin.payments.index');
    }

    /**
     * Exibe detalhes de um pagamento
     */
    public function show(Payment $payment)
    {
        $payment->load(['invoice.tenant', 'gateway']);
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Atualiza status do pagamento consultando o gateway
     */
    public function refreshStatus(Payment $payment)
    {
        try {
            $status = $this->paymentService->checkPaymentStatus($payment);

            return back()->with('success', "Status atualizado: {$payment->fresh()->status_badge}");

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao consultar status: ' . $e->getMessage());
        }
    }

    /**
     * Cancela um pagamento
     */
    public function cancel(Payment $payment)
    {
        if ($payment->isApproved()) {
            return back()->with('error', 'Não é possível cancelar um pagamento aprovado.');
        }

        try {
            $this->paymentService->cancelPayment($payment);

            return back()->with('success', 'Pagamento cancelado com sucesso!');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Download de boleto
     */
    public function downloadBoleto(Payment $payment)
    {
        if (!$payment->isBoleto() || !$payment->boleto_url) {
            return back()->with('error', 'Boleto não disponível.');
        }

        return redirect($payment->boleto_url);
    }

    /**
     * Exibe QR Code PIX
     */
    public function showPixQrCode(Payment $payment)
    {
        if (!$payment->isPix() || !$payment->pix_qrcode) {
            return back()->with('error', 'QR Code PIX não disponível.');
        }

        return view('admin.payments.pix-qrcode', compact('payment'));
    }
}
