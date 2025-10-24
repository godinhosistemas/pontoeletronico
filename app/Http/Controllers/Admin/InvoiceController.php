<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Lista todas as faturas (Super Admin)
     */
    public function index()
    {
        return view('admin.invoices.index');
    }

    /**
     * Exibe detalhes de uma fatura
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'payments']);
        return view('admin.invoices.show', compact('invoice'));
    }

    /**
     * Marca fatura como paga manualmente
     */
    public function markAsPaid(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return back()->with('error', 'Esta fatura já está paga.');
        }

        $invoice->markAsPaid();

        return back()->with('success', 'Fatura marcada como paga com sucesso!');
    }

    /**
     * Cancela uma fatura
     */
    public function cancel(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return back()->with('error', 'Não é possível cancelar uma fatura paga.');
        }

        $invoice->cancel();

        return back()->with('success', 'Fatura cancelada com sucesso!');
    }

    /**
     * Envia 2ª via da fatura por email
     */
    public function sendSecondCopy(Invoice $invoice)
    {
        try {
            $tenant = $invoice->tenant;
            $appName = config('app.name', 'Sistema de Ponto Eletrônico');

            $subject = "2ª Via - Fatura {$invoice->invoice_number}";

            $message = "Olá {$tenant->name},\n\n" .
                       "Segue 2ª via da fatura {$invoice->invoice_number}.\n\n" .
                       "Valor: {$invoice->formatted_total}\n" .
                       "Vencimento: {$invoice->due_date->format('d/m/Y')}\n" .
                       "Status: {$invoice->status_badge}\n\n";

            if ($invoice->isPending() || $invoice->isOverdue()) {
                $message .= "Para realizar o pagamento, acesse:\n";
                $message .= route('tenant.billing.payment', $invoice) . "\n\n";
            }

            $message .= "Atenciosamente,\n{$appName}";

            if ($tenant->email) {
                \Mail::raw($message, function ($mail) use ($tenant, $subject) {
                    $mail->to($tenant->email)
                        ->subject($subject);
                });

                return back()->with('success', '2ª via enviada para ' . $tenant->email);
            }

            return back()->with('error', 'Cliente não possui email cadastrado.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao enviar 2ª via: ' . $e->getMessage());
        }
    }

    /**
     * Gera boleto/PIX para uma fatura sem pagamento
     */
    public function generatePayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:boleto,pix',
            'payment_gateway_id' => 'nullable|exists:payment_gateways,id',
        ]);

        try {
            $paymentService = app(\App\Services\Payment\PaymentService::class);

            $payment = $paymentService->createPayment(
                $invoice,
                $validated['payment_method'],
                $validated['payment_gateway_id'] ?? null
            );

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', 'Pagamento gerado com sucesso!');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao gerar pagamento: ' . $e->getMessage());
        }
    }

    /**
     * Download da fatura em PDF (preparado para implementação futura)
     */
    public function downloadPdf(Invoice $invoice)
    {
        // TODO: Implementar geração de PDF da fatura
        return back()->with('info', 'Funcionalidade de PDF em desenvolvimento.');
    }
}
