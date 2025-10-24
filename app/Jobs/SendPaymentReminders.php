<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendPaymentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando envio de lembretes de pagamento');

        // Buscar faturas que precisam de lembrete
        $invoices = Invoice::with(['tenant'])
            ->whereIn('status', ['pending', 'overdue'])
            ->get()
            ->filter(function ($invoice) {
                return $invoice->needsReminder();
            });

        $remindersSent = 0;
        $errors = 0;

        foreach ($invoices as $invoice) {
            try {
                $this->sendReminder($invoice);
                $invoice->incrementReminder();
                $remindersSent++;

                Log::info("Lembrete enviado para fatura {$invoice->invoice_number}");

            } catch (\Exception $e) {
                $errors++;
                Log::error("Erro ao enviar lembrete para fatura {$invoice->id}: {$e->getMessage()}");
            }
        }

        Log::info("Envio de lembretes concluído. Enviados: {$remindersSent}, Erros: {$errors}");
    }

    /**
     * Envia lembrete de pagamento
     */
    protected function sendReminder(Invoice $invoice): void
    {
        $tenant = $invoice->tenant;
        $daysUntilDue = $invoice->daysUntilDue();

        // Determinar tipo de mensagem
        $subject = $this->getReminderSubject($invoice, $daysUntilDue);
        $message = $this->getReminderMessage($invoice, $daysUntilDue);

        // Enviar notificação por email
        if ($tenant->email) {
            $this->sendEmailReminder($tenant->email, $subject, $message, $invoice);
        }

        // Enviar notificação por WhatsApp/SMS se configurado
        if ($tenant->phone && config('services.whatsapp.enabled', false)) {
            $this->sendWhatsAppReminder($tenant->phone, $message, $invoice);
        }

        // Log da notificação
        Log::info("Lembrete enviado", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'tenant_id' => $tenant->id,
            'days_until_due' => $daysUntilDue,
        ]);
    }

    /**
     * Retorna assunto do lembrete
     */
    protected function getReminderSubject(Invoice $invoice, int $daysUntilDue): string
    {
        if ($daysUntilDue < 0) {
            $daysOverdue = abs($daysUntilDue);
            return "Fatura {$invoice->invoice_number} vencida há {$daysOverdue} dia(s)";
        } elseif ($daysUntilDue === 0) {
            return "Fatura {$invoice->invoice_number} vence hoje!";
        } elseif ($daysUntilDue === 1) {
            return "Fatura {$invoice->invoice_number} vence amanhã!";
        } else {
            return "Fatura {$invoice->invoice_number} vence em {$daysUntilDue} dias";
        }
    }

    /**
     * Retorna mensagem do lembrete
     */
    protected function getReminderMessage(Invoice $invoice, int $daysUntilDue): string
    {
        $tenant = $invoice->tenant;
        $appName = config('app.name', 'Sistema de Ponto Eletrônico');

        if ($daysUntilDue < 0) {
            $daysOverdue = abs($daysUntilDue);
            return "Olá {$tenant->name},\n\n" .
                   "Identificamos que a fatura {$invoice->invoice_number} no valor de {$invoice->formatted_total} " .
                   "está vencida há {$daysOverdue} dia(s).\n\n" .
                   "Para evitar a suspensão do acesso ao sistema, regularize sua situação o quanto antes.\n\n" .
                   "Você pode pagar via Boleto ou PIX através do nosso portal.\n\n" .
                   "Atenciosamente,\n{$appName}";
        } elseif ($daysUntilDue === 0) {
            return "Olá {$tenant->name},\n\n" .
                   "Lembramos que a fatura {$invoice->invoice_number} no valor de {$invoice->formatted_total} " .
                   "vence HOJE ({$invoice->due_date->format('d/m/Y')}).\n\n" .
                   "Realize o pagamento para manter seu acesso ao sistema sem interrupções.\n\n" .
                   "Você pode pagar via Boleto ou PIX através do nosso portal.\n\n" .
                   "Atenciosamente,\n{$appName}";
        } elseif ($daysUntilDue <= 3) {
            return "Olá {$tenant->name},\n\n" .
                   "A fatura {$invoice->invoice_number} no valor de {$invoice->formatted_total} " .
                   "vence em {$daysUntilDue} dia(s) ({$invoice->due_date->format('d/m/Y')}).\n\n" .
                   "Não deixe para a última hora! Realize o pagamento com antecedência.\n\n" .
                   "Você pode pagar via Boleto ou PIX através do nosso portal.\n\n" .
                   "Atenciosamente,\n{$appName}";
        } else {
            return "Olá {$tenant->name},\n\n" .
                   "A fatura {$invoice->invoice_number} no valor de {$invoice->formatted_total} " .
                   "vence em {$daysUntilDue} dias ({$invoice->due_date->format('d/m/Y')}).\n\n" .
                   "Você pode pagar via Boleto ou PIX através do nosso portal.\n\n" .
                   "Atenciosamente,\n{$appName}";
        }
    }

    /**
     * Envia lembrete por email
     */
    protected function sendEmailReminder(string $email, string $subject, string $message, Invoice $invoice): void
    {
        // Implementar envio de email
        // Pode usar Mailable ou Notification do Laravel

        try {
            \Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)
                    ->subject($subject);
            });

            Log::info("Email enviado para {$email}");
        } catch (\Exception $e) {
            Log::error("Erro ao enviar email: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Envia lembrete por WhatsApp
     */
    protected function sendWhatsAppReminder(string $phone, string $message, Invoice $invoice): void
    {
        // Implementar integração com API de WhatsApp
        // Por exemplo: Twilio, Evolution API, etc.

        try {
            // Exemplo de integração (ajustar conforme sua API)
            /*
            $whatsappApi = config('services.whatsapp.api_url');
            $whatsappToken = config('services.whatsapp.token');

            Http::withHeaders([
                'Authorization' => "Bearer {$whatsappToken}",
            ])->post("{$whatsappApi}/send-message", [
                'phone' => $phone,
                'message' => $message,
            ]);
            */

            Log::info("WhatsApp enviado para {$phone}");
        } catch (\Exception $e) {
            Log::error("Erro ao enviar WhatsApp: {$e->getMessage()}");
            // Não propagar erro de WhatsApp para não interromper outros lembretes
        }
    }
}
