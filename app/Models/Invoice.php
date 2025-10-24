<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'invoice_number',
        'reference',
        'period_start',
        'period_end',
        'subtotal',
        'discount',
        'tax',
        'total',
        'issue_date',
        'due_date',
        'paid_at',
        'status',
        'items',
        'notes',
        'payment_instructions',
        'metadata',
        'payment_attempts',
        'last_payment_attempt',
        'reminders_sent',
        'last_reminder_sent',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'items' => 'array',
        'metadata' => 'array',
        'last_payment_attempt' => 'datetime',
        'last_reminder_sent' => 'datetime',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (!$invoice->invoice_number) {
                $invoice->invoice_number = $invoice->generateInvoiceNumber();
            }

            // Atualizar status se vencida
            if ($invoice->due_date < now() && $invoice->status === 'pending') {
                $invoice->status = 'overdue';
            }
        });
    }

    /**
     * Gera número da fatura
     */
    public function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $lastInvoice = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -5)) + 1 : 1;

        return 'INV-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relacionamento com tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com subscription
     */
    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Relacionamento com pagamentos
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Pagamento aprovado (se existir)
     */
    public function approvedPayment()
    {
        return $this->hasOne(Payment::class)->whereIn('status', ['approved', 'completed']);
    }

    /**
     * Marca fatura como paga
     */
    public function markAsPaid(Payment $payment = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Atualiza subscription se houver
        if ($this->subscription) {
            $this->subscription->update([
                'last_payment_date' => now(),
            ]);
        }
    }

    /**
     * Marca como vencida
     */
    public function markAsOverdue(): void
    {
        if ($this->status === 'pending') {
            $this->update(['status' => 'overdue']);
        }
    }

    /**
     * Cancela fatura
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);

        // Cancela pagamentos pendentes
        $this->payments()->whereIn('status', ['pending', 'processing'])->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Verifica se está paga
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Verifica se está vencida
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' ||
               ($this->status === 'pending' && $this->due_date < now());
    }

    /**
     * Verifica se está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Dias até o vencimento (negativo se vencida)
     */
    public function daysUntilDue(): int
    {
        return now()->startOfDay()->diffInDays($this->due_date, false);
    }

    /**
     * Dias desde o vencimento
     */
    public function daysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    /**
     * Verifica se precisa enviar lembrete
     */
    public function needsReminder(): bool
    {
        if ($this->isPaid() || $this->status === 'cancelled') {
            return false;
        }

        // Enviar lembrete 7 dias antes, 3 dias antes, no dia e depois do vencimento
        $daysUntilDue = $this->daysUntilDue();

        if (in_array($daysUntilDue, [7, 3, 0, -1, -3, -7])) {
            // Verifica se já enviou lembrete hoje
            if ($this->last_reminder_sent && $this->last_reminder_sent->isToday()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Incrementa tentativas de pagamento
     */
    public function incrementPaymentAttempt(): void
    {
        $this->increment('payment_attempts');
        $this->update(['last_payment_attempt' => now()]);
    }

    /**
     * Incrementa lembretes enviados
     */
    public function incrementReminder(): void
    {
        $this->increment('reminders_sent');
        $this->update(['last_reminder_sent' => now()]);
    }

    /**
     * Scope para faturas pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para faturas vencidas
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
                     ->orWhere(function($q) {
                         $q->where('status', 'pending')
                           ->where('due_date', '<', now());
                     });
    }

    /**
     * Scope para faturas pagas
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope para faturas do tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Badge de status
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'paid' => 'Paga',
            'overdue' => 'Vencida',
            'cancelled' => 'Cancelada',
            'refunded' => 'Reembolsada',
            default => $this->status,
        };
    }

    /**
     * Cor do badge
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'paid' => 'green',
            'pending' => 'yellow',
            'overdue' => 'red',
            'cancelled' => 'gray',
            'refunded' => 'purple',
            'draft' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Formata total
     */
    public function getFormattedTotalAttribute(): string
    {
        return 'R$ ' . number_format($this->total, 2, ',', '.');
    }

    /**
     * Descrição do período
     */
    public function getPeriodDescriptionAttribute(): string
    {
        if (!$this->period_start || !$this->period_end) {
            return '-';
        }

        return $this->period_start->format('d/m/Y') . ' a ' . $this->period_end->format('d/m/Y');
    }
}
