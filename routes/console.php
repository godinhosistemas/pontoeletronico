<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================================
// AGENDAMENTO DE JOBS DO SISTEMA DE BILLING
// ============================================================

// Gerar faturas mensais - Todo dia 1 às 00:00
Schedule::job(new \App\Jobs\GenerateMonthlyInvoices())
    ->monthlyOn(1, '00:00')
    ->name('billing:generate-invoices')
    ->withoutOverlapping()
    ->onOneServer();

// Enviar lembretes de pagamento - Todos os dias às 09:00
Schedule::job(new \App\Jobs\SendPaymentReminders())
    ->dailyAt('09:00')
    ->name('billing:send-reminders')
    ->withoutOverlapping()
    ->onOneServer();

// Processar faturas vencidas - Todos os dias às 10:00
Schedule::job(new \App\Jobs\ProcessOverdueInvoices())
    ->dailyAt('10:00')
    ->name('billing:process-overdue')
    ->withoutOverlapping()
    ->onOneServer();
