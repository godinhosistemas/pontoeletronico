<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OvertimeBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'tenant_id',
        'period',
        'earned_hours',
        'compensated_hours',
        'balance_hours',
        'status',
        'expiration_date',
        'notes',
    ];

    protected $casts = [
        'earned_hours' => 'decimal:2',
        'compensated_hours' => 'decimal:2',
        'balance_hours' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    /**
     * Relacionamento com Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relacionamento com Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Adiciona horas extras ao saldo
     */
    public function addEarnedHours(float $hours): void
    {
        $this->earned_hours += $hours;
        $this->balance_hours += $hours;
        $this->save();
    }

    /**
     * Compensa horas do banco
     */
    public function compensateHours(float $hours): void
    {
        $hoursToCompensate = min($hours, $this->balance_hours);
        $this->compensated_hours += $hoursToCompensate;
        $this->balance_hours -= $hoursToCompensate;

        if ($this->balance_hours <= 0) {
            $this->status = 'compensated';
        }

        $this->save();
    }

    /**
     * Verifica se o banco está expirado
     */
    public function isExpired(): bool
    {
        if (!$this->expiration_date) {
            return false;
        }

        return Carbon::now()->isAfter($this->expiration_date);
    }

    /**
     * Marca como expirado
     */
    public function markAsExpired(): void
    {
        $this->status = 'expired';
        $this->save();
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar por funcionário
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopeForPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    /**
     * Scope para filtrar ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Formata o período (YYYY-MM) para exibição
     */
    public function getFormattedPeriodAttribute(): string
    {
        $date = Carbon::createFromFormat('Y-m', $this->period);
        return $date->locale('pt_BR')->isoFormat('MMMM [de] YYYY');
    }

    /**
     * Retorna texto do status
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'active' => 'Ativo',
            'expired' => 'Expirado',
            'compensated' => 'Compensado',
            default => 'Indefinido',
        };
    }

    /**
     * Retorna cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'expired' => 'red',
            'compensated' => 'blue',
            default => 'gray',
        };
    }
}
