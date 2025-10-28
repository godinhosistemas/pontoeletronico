<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class EmployeeCompliance extends Model
{
    protected $table = 'employee_compliance';

    protected $fillable = [
        'employee_id',
        'compliance_type',
        'title',
        'description',
        'due_date',
        'completion_date',
        'status',
        'priority',
        'send_notification',
        'notification_days_before',
        'notes',
        'document_reference',
        'registered_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completion_date' => 'date',
        'send_notification' => 'boolean',
    ];

    /**
     * Relacionamento com Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relacionamento com User (quem registrou)
     */
    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * Verifica se está vencido
     */
    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && !$this->completion_date;
    }

    /**
     * Verifica se está próximo do vencimento
     */
    public function isDueSoon(): bool
    {
        if ($this->completion_date) {
            return false;
        }

        $daysUntilDue = $this->due_date->diffInDays(now(), false);
        return $daysUntilDue >= 0 && $daysUntilDue <= $this->notification_days_before;
    }

    /**
     * Atualiza o status automaticamente baseado nas datas
     */
    public function updateStatus(): void
    {
        if ($this->completion_date) {
            $this->status = 'Em Dia';
        } elseif ($this->isOverdue()) {
            $this->status = 'Vencido';
        } else {
            $this->status = 'Pendente';
        }
        $this->save();
    }

    /**
     * Cor do badge de status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Em Dia' => 'green',
            'Pendente' => 'yellow',
            'Vencido' => 'red',
            'Dispensado' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Cor do badge de prioridade
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'Urgente' => 'red',
            'Alta' => 'orange',
            'Normal' => 'blue',
            'Baixa' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Dias até o vencimento
     */
    public function getDaysUntilDueAttribute(): int
    {
        return (int) $this->due_date->diffInDays(now(), false);
    }
}
