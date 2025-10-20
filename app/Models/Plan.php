<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'max_users',
        'max_employees',
        'billing_cycle_days',
        'features',
        'is_active',
        'trial_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'max_users' => 'integer',
        'max_employees' => 'integer',
        'billing_cycle_days' => 'integer',
        'trial_days' => 'integer',
    ];

    /**
     * Relacionamento com assinaturas
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Assinaturas ativas
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)
            ->where('status', 'active');
    }

    /**
     * Verifica se tem uma feature específica
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Retorna o preço formatado
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}
