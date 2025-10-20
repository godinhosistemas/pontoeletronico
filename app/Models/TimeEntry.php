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
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
        'lunch_start' => 'datetime:H:i',
        'lunch_end' => 'datetime:H:i',
        'approved_at' => 'datetime',
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
     * Calcula o total de horas trabalhadas
     */
    public function calculateTotalHours(): void
    {
        if (!$this->clock_in || !$this->clock_out) {
            return;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        // Calcula o total de minutos
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // Subtrai o tempo de almoço se houver
        if ($this->lunch_start && $this->lunch_end) {
            $lunchStart = Carbon::parse($this->lunch_start);
            $lunchEnd = Carbon::parse($this->lunch_end);
            $lunchMinutes = $lunchEnd->diffInMinutes($lunchStart);
            $totalMinutes -= $lunchMinutes;
        }

        $this->total_minutes = $totalMinutes;
        $this->total_hours = floor($totalMinutes / 60);
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
     * Formata as horas trabalhadas
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
