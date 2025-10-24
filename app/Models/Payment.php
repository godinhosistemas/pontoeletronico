<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'payment_gateway_id',
        'payment_number',
        'transaction_id',
        'authorization_code',
        'payment_method',
        'amount',
        'fee',
        'net_amount',
        'status',
        'authorized_at',
        'completed_at',
        'failed_at',
        'refunded_at',
        'boleto_url',
        'boleto_barcode',
        'boleto_digitable_line',
        'pix_qrcode',
        'pix_qrcode_text',
        'pix_txid',
        'card_brand',
        'card_last4',
        'installments',
        'gateway_response',
        'error_message',
        'error_code',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'authorized_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
        'gateway_response' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->payment_number) {
                $payment->payment_number = $payment->generatePaymentNumber();
            }
        });

        static::updated(function ($payment) {
            // Se pagamento aprovado/completado, marca fatura como paga
            if (in_array($payment->status, ['approved', 'completed']) && $payment->invoice) {
                $payment->invoice->markAsPaid($payment);
            }
        });
    }

    /**
     * Gera número do pagamento
     */
    public function generatePaymentNumber(): string
    {
        $year = now()->year;
        $lastPayment = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastPayment ? ((int) substr($lastPayment->payment_number, -5)) + 1 : 1;

        return 'PAY-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Relacionamentos
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    /**
     * Marca como aprovado
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'authorized_at' => now(),
        ]);
    }

    /**
     * Marca como completado
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Marca como falhou
     */
    public function fail(string $errorMessage = null, string $errorCode = null): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_code' => $errorCode,
        ]);
    }

    /**
     * Verifica se é boleto
     */
    public function isBoleto(): bool
    {
        return $this->payment_method === 'boleto';
    }

    /**
     * Verifica se é PIX
     */
    public function isPix(): bool
    {
        return $this->payment_method === 'pix';
    }

    /**
     * Verifica se foi aprovado
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'completed']);
    }

    /**
     * Scope para pagamentos aprovados
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'completed']);
    }

    /**
     * Badge de status
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'approved' => 'Aprovado',
            'completed' => 'Concluído',
            'failed' => 'Falhou',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            'chargeback' => 'Estornado',
            default => $this->status,
        };
    }

    /**
     * Cor do badge
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approved', 'completed' => 'green',
            'pending' => 'yellow',
            'processing' => 'blue',
            'failed', 'cancelled' => 'red',
            'refunded', 'chargeback' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Nome do método de pagamento
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match($this->payment_method) {
            'boleto' => 'Boleto',
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'bank_transfer' => 'Transferência Bancária',
            default => 'Outro',
        };
    }
}
