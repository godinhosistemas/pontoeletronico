<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'cnpj',
        'email',
        'phone',
        'address',
        'logo',
        'is_active',
        // Certificado Digital ICP-Brasil
        'certificate_path',
        'certificate_password_encrypted',
        'certificate_type',
        'certificate_issuer',
        'certificate_subject',
        'certificate_serial_number',
        'certificate_valid_from',
        'certificate_valid_until',
        'certificate_metadata',
        'certificate_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'certificate_active' => 'boolean',
        'certificate_valid_from' => 'datetime',
        'certificate_valid_until' => 'datetime',
        'certificate_metadata' => 'array',
    ];

    /**
     * Relacionamento com usuários
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Relacionamento com assinaturas
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Relacionamento com funcionários
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Assinatura ativa atual (inclui trialing e active)
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trialing'])
            ->latest();
    }

    /**
     * Verifica se o tenant tem assinatura ativa
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Retorna o plano atual do tenant
     */
    public function currentPlan()
    {
        return $this->activeSubscription?->plan;
    }

    /**
     * Verifica se o tenant tem certificado digital válido
     */
    public function hasCertificate(): bool
    {
        return $this->certificate_active &&
               $this->certificate_path &&
               $this->certificate_valid_until &&
               now()->lt($this->certificate_valid_until);
    }

    /**
     * Retorna dias até expiração do certificado
     */
    public function certificateDaysRemaining(): ?int
    {
        if (!$this->certificate_valid_until) {
            return null;
        }

        return now()->diffInDays($this->certificate_valid_until, false);
    }

    /**
     * Verifica se certificado precisa renovação (< 30 dias)
     */
    public function certificateNeedsRenewal(): bool
    {
        $days = $this->certificateDaysRemaining();
        return $days !== null && $days <= 30;
    }

    /**
     * Status do certificado em formato legível
     */
    public function getCertificateStatusAttribute(): string
    {
        if (!$this->certificate_active || !$this->certificate_path) {
            return 'Não cadastrado';
        }

        if (!$this->certificate_valid_until) {
            return 'Sem validade definida';
        }

        $days = $this->certificateDaysRemaining();

        if ($days === null || $days < 0) {
            return 'Expirado';
        }

        if ($days <= 7) {
            return "Expirando em {$days} dias";
        }

        if ($days <= 30) {
            return "Válido (renovar em breve)";
        }

        return "Válido até " . $this->certificate_valid_until->format('d/m/Y');
    }
}
