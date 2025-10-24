<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Iniciando geração de faturas mensais');

        $now = now();
        $periodStart = $now->copy()->startOfMonth();
        $periodEnd = $now->copy()->endOfMonth();

        // Buscar assinaturas ativas
        $subscriptions = Subscription::where('status', 'active')
            ->where('start_date', '<=', $now)
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->with(['tenant', 'plan'])
            ->get();

        $invoicesCreated = 0;
        $errors = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Verificar se já existe fatura para este período
                $existingInvoice = Invoice::where('tenant_id', $subscription->tenant_id)
                    ->where('subscription_id', $subscription->id)
                    ->where('period_start', $periodStart)
                    ->where('period_end', $periodEnd)
                    ->first();

                if ($existingInvoice) {
                    Log::info("Fatura já existe para tenant {$subscription->tenant_id}");
                    continue;
                }

                // Calcular valor baseado no plano
                $plan = $subscription->plan;
                $baseAmount = $subscription->custom_price ?? $plan->price;

                // Adicionar cobranças extras (se houver)
                $extraCharges = $this->calculateExtraCharges($subscription);

                $subtotal = $baseAmount + $extraCharges;
                $discount = $this->calculateDiscount($subscription, $subtotal);
                $tax = $this->calculateTax($subtotal - $discount);
                $total = $subtotal - $discount + $tax;

                // Criar itens da fatura
                $items = [
                    [
                        'description' => "Plano {$plan->name}",
                        'quantity' => 1,
                        'unit_price' => $baseAmount,
                        'total' => $baseAmount,
                    ],
                ];

                // Adicionar itens extras se houver
                if ($extraCharges > 0) {
                    $items[] = [
                        'description' => 'Cobranças adicionais',
                        'quantity' => 1,
                        'unit_price' => $extraCharges,
                        'total' => $extraCharges,
                    ];
                }

                // Determinar data de vencimento
                $dueDate = $this->calculateDueDate($subscription);

                // Criar fatura
                $invoice = Invoice::create([
                    'tenant_id' => $subscription->tenant_id,
                    'subscription_id' => $subscription->id,
                    'reference' => $now->format('Y-m'),
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $total,
                    'issue_date' => $now->toDateString(),
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'items' => $items,
                    'payment_instructions' => 'Pagamento pode ser realizado via Boleto ou PIX através do portal.',
                ]);

                $invoicesCreated++;

                Log::info("Fatura criada: {$invoice->invoice_number} para tenant {$subscription->tenant->name}");

            } catch (\Exception $e) {
                $errors++;
                Log::error("Erro ao gerar fatura para subscription {$subscription->id}: {$e->getMessage()}");
            }
        }

        Log::info("Geração de faturas concluída. Criadas: {$invoicesCreated}, Erros: {$errors}");
    }

    /**
     * Calcula cobranças extras
     */
    protected function calculateExtraCharges(Subscription $subscription): float
    {
        // Implementar lógica de cobranças extras aqui
        // Por exemplo: usuários extras, módulos adicionais, etc.

        $extraCharges = 0;

        // Exemplo: Cobrar por usuários extras
        $metadata = $subscription->metadata ?? [];
        if (isset($metadata['extra_users'])) {
            $extraUserPrice = $metadata['extra_user_price'] ?? 10;
            $extraCharges += $metadata['extra_users'] * $extraUserPrice;
        }

        return $extraCharges;
    }

    /**
     * Calcula desconto
     */
    protected function calculateDiscount(Subscription $subscription, float $amount): float
    {
        $discount = 0;

        // Desconto por pagamento anual
        if ($subscription->billing_cycle === 'yearly') {
            $discount = $amount * 0.10; // 10% de desconto
        }

        // Desconto promocional
        $metadata = $subscription->metadata ?? [];
        if (isset($metadata['promotional_discount_percent'])) {
            $discount += $amount * ($metadata['promotional_discount_percent'] / 100);
        }

        return $discount;
    }

    /**
     * Calcula impostos
     */
    protected function calculateTax(float $amount): float
    {
        // Implementar cálculo de impostos se necessário
        // Por exemplo: ISS, PIS, COFINS, etc.

        return 0; // Sem impostos por padrão
    }

    /**
     * Calcula data de vencimento
     */
    protected function calculateDueDate(Subscription $subscription): Carbon
    {
        $metadata = $subscription->metadata ?? [];

        // Usar dia de vencimento customizado se definido
        $dueDay = $metadata['due_day'] ?? 10;

        $dueDate = now()->copy()->day($dueDay);

        // Se já passou o dia, vencer no próximo mês
        if ($dueDate->isPast()) {
            $dueDate->addMonth();
        }

        return $dueDate;
    }
}
