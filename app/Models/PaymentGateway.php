<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class PaymentGateway extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'provider',
        'is_active',
        'is_default',
        'api_key',
        'api_secret',
        'public_key',
        'settings',
        'supported_methods',
        'environment',
        'webhook_url',
        'webhook_secret',
        'fee_percentage',
        'fee_fixed',
        'min_amount',
        'max_amount',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
        'supported_methods' => 'array',
        'metadata' => 'array',
        'fee_percentage' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'webhook_secret',
    ];

    /**
     * Boot method para garantir que apenas um gateway seja padrão
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($gateway) {
            if ($gateway->is_default) {
                // Remove is_default de outros gateways
                static::where('id', '!=', $gateway->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Criptografa API Key antes de salvar
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Descriptografa API Key ao ler
     */
    public function getApiKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Criptografa API Secret antes de salvar
     */
    public function setApiSecretAttribute($value)
    {
        if ($value) {
            $this->attributes['api_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Descriptografa API Secret ao ler
     */
    public function getApiSecretAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Verifica se o gateway suporta um método de pagamento
     */
    public function supportsMethod(string $method): bool
    {
        return in_array($method, $this->supported_methods ?? []);
    }

    /**
     * Verifica se o gateway está em produção
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Calcula a taxa para um valor
     */
    public function calculateFee(float $amount): float
    {
        $fee = 0;

        if ($this->fee_percentage) {
            $fee += ($amount * $this->fee_percentage) / 100;
        }

        if ($this->fee_fixed) {
            $fee += $this->fee_fixed;
        }

        return round($fee, 2);
    }

    /**
     * Obtém o valor líquido após descontar a taxa
     */
    public function getNetAmount(float $amount): float
    {
        return round($amount - $this->calculateFee($amount), 2);
    }

    /**
     * Scope para gateways ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para gateway padrão
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para gateways de produção
     */
    public function scopeProduction($query)
    {
        return $query->where('environment', 'production');
    }

    /**
     * Relacionamento com pagamentos
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relacionamento com webhooks
     */
    public function webhooks()
    {
        return $this->hasMany(PaymentWebhook::class);
    }

    /**
     * Usuário que criou
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuário que atualizou
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtém o nome formatado com ambiente
     */
    public function getFormattedNameAttribute(): string
    {
        $name = $this->name;
        if ($this->environment === 'sandbox') {
            $name .= ' (Sandbox)';
        }
        return $name;
    }

    /**
     * Badge de status
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'Ativo' : 'Inativo';
    }

    /**
     * Cor do badge de status
     */
    public function getStatusColorAttribute(): string
    {
        return $this->is_active ? 'green' : 'gray';
    }
}
