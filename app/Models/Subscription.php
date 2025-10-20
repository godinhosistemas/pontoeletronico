<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'start_date',
        'end_date',
        'trial_ends_at',
        'status',
        'canceled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'trial_ends_at' => 'date',
        'canceled_at' => 'date',
    ];

    /**
     * Relacionamento com tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com plano
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Verifica se está em período de trial
     */
    public function onTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * Verifica se está ativa
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    /**
     * Verifica se está expirada
     */
    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Cancela a assinatura
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Suspende a assinatura
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Reativa a assinatura
     */
    public function reactivate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Renova a assinatura
     */
    public function renew(): void
    {
        $billingCycleDays = $this->plan->billing_cycle_days;

        $this->update([
            'start_date' => $this->end_date,
            'end_date' => $this->end_date->addDays($billingCycleDays),
            'status' => 'active',
        ]);
    }
}
