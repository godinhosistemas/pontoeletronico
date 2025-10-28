<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDependent extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'cpf',
        'birth_date',
        'gender',
        'is_dependent_ir',
        'has_health_insurance',
        'health_insurance_number',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_dependent_ir' => 'boolean',
        'has_health_insurance' => 'boolean',
    ];

    /**
     * Relacionamento com Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Calcula a idade do dependente
     */
    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    /**
     * Formata o CPF
     */
    public function getFormattedCpfAttribute(): string
    {
        if (!$this->cpf) {
            return '-';
        }
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }
}
