<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'weekly_hours',
        'daily_hours',
        'break_minutes',
        'default_start_time',
        'default_end_time',
        'default_break_start',
        'default_break_end',
        'days_config',
        'tolerance_minutes_entry',
        'tolerance_minutes_exit',
        'consider_holidays',
        'allow_overtime',
        'is_active',
    ];

    protected $casts = [
        'days_config' => 'array',
        'consider_holidays' => 'boolean',
        'allow_overtime' => 'boolean',
        'is_active' => 'boolean',
        'weekly_hours' => 'integer',
        'daily_hours' => 'integer',
        'break_minutes' => 'integer',
        'tolerance_minutes_entry' => 'integer',
        'tolerance_minutes_exit' => 'integer',
    ];

    /**
     * Relacionamento com Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com Employees
     */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'work_schedule_id');
    }

    /**
     * Retorna a configuração de um dia específico da semana
     */
    public function getDayConfig(string $day): ?array
    {
        if (!$this->days_config) {
            return null;
        }

        return $this->days_config[$day] ?? null;
    }

    /**
     * Verifica se trabalha em determinado dia da semana
     */
    public function worksOnDay(string $day): bool
    {
        $config = $this->getDayConfig($day);
        return $config['active'] ?? false;
    }

    /**
     * Retorna os dias da semana que trabalha
     */
    public function getWorkingDays(): array
    {
        if (!$this->days_config) {
            return [];
        }

        return collect($this->days_config)
            ->filter(fn($config) => $config['active'] ?? false)
            ->keys()
            ->toArray();
    }
}
