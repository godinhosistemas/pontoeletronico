<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_gateway_id',
        'payment_id',
        'event_id',
        'event_type',
        'payload',
        'status',
        'processed_at',
        'attempts',
        'error_message',
        'ip_address',
        'headers',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Relacionamento com gateway
     */
    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    /**
     * Relacionamento com pagamento
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Marca como processado
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Marca como falhou
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Incrementa tentativas
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope para webhooks pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para webhooks falhados
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para webhooks processados
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }
}
