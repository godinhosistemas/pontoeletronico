<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOverdueInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando processamento de faturas vencidas');

        // Marcar faturas pendentes como vencidas
        $invoices = Invoice::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        $invoicesUpdated = 0;
        $subscriptionsSuspended = 0;

        foreach ($invoices as $invoice) {
            try {
                // Marcar como vencida
                $invoice->markAsOverdue();
                $invoicesUpdated++;

                Log::info("Fatura {$invoice->invoice_number} marcada como vencida");

                // Verificar se deve suspender assinatura
                $daysOverdue = $invoice->daysOverdue();
                $gracePeriod = config('billing.grace_period_days', 7);

                if ($daysOverdue > $gracePeriod) {
                    $this->suspendSubscription($invoice);
                    $subscriptionsSuspended++;
                }

            } catch (\Exception $e) {
                Log::error("Erro ao processar fatura vencida {$invoice->id}: {$e->getMessage()}");
            }
        }

        Log::info("Processamento concluído. Faturas atualizadas: {$invoicesUpdated}, Assinaturas suspensas: {$subscriptionsSuspended}");
    }

    /**
     * Suspende assinatura por falta de pagamento
     */
    protected function suspendSubscription(Invoice $invoice): void
    {
        $subscription = $invoice->subscription;

        if (!$subscription || $subscription->status === 'suspended') {
            return;
        }

        // Suspender assinatura
        $subscription->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => "Fatura {$invoice->invoice_number} vencida há {$invoice->daysOverdue()} dias",
        ]);

        // Desativar tenant
        $tenant = $invoice->tenant;
        if ($tenant && $tenant->active) {
            $tenant->update([
                'active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'Assinatura suspensa por falta de pagamento',
            ]);

            Log::warning("Tenant {$tenant->name} desativado por falta de pagamento");
        }

        // Enviar notificação de suspensão
        $this->sendSuspensionNotification($invoice);

        Log::warning("Assinatura {$subscription->id} suspensa por falta de pagamento");
    }

    /**
     * Envia notificação de suspensão
     */
    protected function sendSuspensionNotification(Invoice $invoice): void
    {
        $tenant = $invoice->tenant;
        $appName = config('app.name', 'Sistema de Ponto Eletrônico');

        $subject = "IMPORTANTE: Acesso suspenso por falta de pagamento";

        $message = "Olá {$tenant->name},\n\n" .
                   "Seu acesso ao {$appName} foi SUSPENSO devido à falta de pagamento da fatura " .
                   "{$invoice->invoice_number} no valor de {$invoice->formatted_total}.\n\n" .
                   "A fatura está vencida há {$invoice->daysOverdue()} dias.\n\n" .
                   "Para reativar seu acesso imediatamente, realize o pagamento através do nosso portal.\n\n" .
                   "Após a confirmação do pagamento, seu acesso será restaurado automaticamente.\n\n" .
                   "Se você acredita que este é um erro ou já realizou o pagamento, entre em contato conosco.\n\n" .
                   "Atenciosamente,\n{$appName}";

        try {
            if ($tenant->email) {
                \Mail::raw($message, function ($mail) use ($tenant, $subject) {
                    $mail->to($tenant->email)
                        ->subject($subject)
                        ->priority(1); // Alta prioridade
                });

                Log::info("Notificação de suspensão enviada para {$tenant->email}");
            }
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação de suspensão: {$e->getMessage()}");
        }
    }
}
