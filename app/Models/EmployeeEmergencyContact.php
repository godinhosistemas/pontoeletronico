<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeEmergencyContact extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'relationship',
        'phone',
        'phone_secondary',
        'email',
        'address',
        'is_primary',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * Relacionamento com Employee
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Formata o telefone
     */
    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/\D/', '', $this->phone);
        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        } elseif (strlen($phone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
        }
        return $this->phone;
    }
}
