<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    protected $fillable = [
        'employee_id',
        'tenant_id',
        'date',
        'clock_in',
        'clock_out',
        'lunch_start',
        'lunch_end',
        'total_minutes',
        'total_hours',
        'type',
        'status',
        'notes',
        'ip_address',
        'location',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy',
        'distance_meters',
        'gps_validated',
        'approved_by',
        'approved_at',
        'has_adjustment',
        'original_clock_in',
        'original_clock_out',
        'original_lunch_start',
        'original_lunch_end',
        'adjusted_clock_in',
        'adjusted_clock_out',
        'adjusted_lunch_start',
        'adjusted_lunch_end',
        'adjustment_reason',
        'adjusted_by',
        'adjusted_at',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'adjusted_at' => 'datetime',
        'has_adjustment' => 'boolean',
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
     * Relacionamento com o usuário que aprovou
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relacionamento com o usuário que ajustou
     */
    public function adjuster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    /**
     * Calcula o total de horas trabalhadas
     */
    public function calculateTotalHours(): void
    {
        if (!$this->clock_in || !$this->clock_out) {
            return;
        }

        // Usa a data do registro para criar timestamps completos
        $date = $this->date instanceof Carbon ? $this->date : Carbon::parse($this->date);

        $clockIn = Carbon::parse($date->format('Y-m-d') . ' ' . $this->clock_in);
        $clockOut = Carbon::parse($date->format('Y-m-d') . ' ' . $this->clock_out);

        // Se clock_out for menor que clock_in, assume que passou da meia-noite
        if ($clockOut->lessThan($clockIn)) {
            $clockOut->addDay();
        }

        // Calcula o total de minutos (com sinal positivo garantido)
        $totalMinutes = $clockIn->diffInMinutes($clockOut, false);

        // Subtrai o tempo de almoço se houver
        if ($this->lunch_start && $this->lunch_end) {
            $lunchStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->lunch_start);
            $lunchEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $this->lunch_end);

            // Se lunch_end for menor que lunch_start, assume que passou da meia-noite
            if ($lunchEnd->lessThan($lunchStart)) {
                $lunchEnd->addDay();
            }

            $lunchMinutes = $lunchStart->diffInMinutes($lunchEnd, false);
            $totalMinutes -= $lunchMinutes;
        }

        $this->total_minutes = max(0, $totalMinutes); // Garante que não seja negativo
        $this->total_hours = round($totalMinutes / 60, 2); // Arredonda para 2 decimais
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
    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar pendentes de aprovação
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para filtrar aprovados
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Aprova o registro de ponto
     */
    public function approve($userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Rejeita o registro de ponto
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Formata hora para exibição (HH:MM)
     */
    public function formatTime($field): ?string
    {
        $time = $this->attributes[$field] ?? null;

        if (!$time) {
            return null;
        }

        // Se já é uma string no formato HH:MM ou HH:MM:SS
        if (is_string($time)) {
            return substr($time, 0, 5);
        }

        // Se é um objeto Carbon/DateTime
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return $time;
    }

    /**
     * Retorna clock_in formatado
     */
    public function getFormattedClockInAttribute(): ?string
    {
        return $this->formatTime('clock_in');
    }

    /**
     * Retorna clock_out formatado
     */
    public function getFormattedClockOutAttribute(): ?string
    {
        return $this->formatTime('clock_out');
    }

    /**
     * Retorna lunch_start formatado
     */
    public function getFormattedLunchStartAttribute(): ?string
    {
        return $this->formatTime('lunch_start');
    }

    /**
     * Retorna lunch_end formatado
     */
    public function getFormattedLunchEndAttribute(): ?string
    {
        return $this->formatTime('lunch_end');
    }

    /**
     * Formata o total de horas em HH:MM
     */
    public function getFormattedTotalHoursAttribute(): string
    {
        if (!$this->total_hours || $this->total_hours <= 0) {
            return '00:00';
        }

        $hours = floor($this->total_hours);
        $minutes = round(($this->total_hours - $hours) * 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Formata as horas trabalhadas (versão legível)
     */
    public function getFormattedHoursAttribute(): string
    {
        if (!$this->total_minutes) {
            return '0h 0min';
        }

        $hours = floor($this->total_minutes / 60);
        $minutes = $this->total_minutes % 60;

        return "{$hours}h {$minutes}min";
    }

    /**
     * Obtém a cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Obtém o texto traduzido do status
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Indefinido',
        };
    }

    /**
     * Obtém a cor do tipo
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'normal' => 'blue',
            'overtime' => 'purple',
            'absence' => 'red',
            'holiday' => 'green',
            'vacation' => 'indigo',
            default => 'gray',
        };
    }

    /**
     * Obtém o texto traduzido do tipo
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'normal' => 'Normal',
            'overtime' => 'Hora Extra',
            'absence' => 'Falta',
            'holiday' => 'Feriado',
            'vacation' => 'Férias',
            default => 'Indefinido',
        };
    }
}
