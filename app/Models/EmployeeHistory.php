<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHistory extends Model
{
    protected $table = 'employee_history';

    protected $fillable = [
        'employee_id',
        'event_type',
        'title',
        'description',
        'event_date',
        'previous_position',
        'previous_department',
        'previous_salary',
        'new_position',
        'new_department',
        'new_salary',
        'document_reference',
        'justification',
        'registered_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
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
     * Calcula a diferença salarial
     */
    public function getSalaryDifferenceAttribute(): ?float
    {
        if ($this->previous_salary && $this->new_salary) {
            return $this->new_salary - $this->previous_salary;
        }
        return null;
    }

    /**
     * Calcula o percentual de aumento salarial
     */
    public function getSalaryIncreasePercentageAttribute(): ?float
    {
        if ($this->previous_salary && $this->new_salary && $this->previous_salary > 0) {
            return (($this->new_salary - $this->previous_salary) / $this->previous_salary) * 100;
        }
        return null;
    }

    /**
     * Cor do badge do tipo de evento
     */
    public function getEventTypeColorAttribute(): string
    {
        return match($this->event_type) {
            'Admissão' => 'green',
            'Promoção', 'Aumento Salarial' => 'blue',
            'Transferência', 'Mudança de Cargo' => 'yellow',
            'Advertência', 'Suspensão' => 'red',
            'Férias', 'Licença' => 'purple',
            'Demissão' => 'gray',
            default => 'gray',
        };
    }
}
