<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'tenant_id',
        'document_type',
        'document_name',
        'file_path',
        'file_name',
        'file_extension',
        'file_size',
        'issue_date',
        'expiry_date',
        'description',
        'is_verified',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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
     * Relacionamento com User (quem verificou)
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verifica se o documento está vencido
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isPast();
    }

    /**
     * Verifica se o documento está próximo do vencimento (30 dias)
     */
    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->isFuture() && $this->expiry_date->diffInDays(now()) <= 30;
    }

    /**
     * Formata o tamanho do arquivo
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
