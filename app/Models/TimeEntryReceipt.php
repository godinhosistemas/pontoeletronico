<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TimeEntryReceipt extends Model
{
    protected $fillable = [
        'time_entry_id',
        'employee_id',
        'tenant_id',
        'uuid',
        'action',
        'marked_at',
        'pdf_path',
        'authenticator_code',
        'ip_address',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy',
        'photo_path',
        'available_until',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
        'available_until' => 'datetime',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
    ];

    /**
     * Boot do model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (!$receipt->uuid) {
                $receipt->uuid = (string) Str::uuid();
            }

            if (!$receipt->authenticator_code) {
                $receipt->authenticator_code = static::generateAuthenticatorCode();
            }

            if (!$receipt->available_until) {
                // Disponível por 48 horas
                $receipt->available_until = now()->addHours(48);
            }
        });
    }

    /**
     * Relacionamento com TimeEntry
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

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
     * Gera código autenticador único
     */
    public static function generateAuthenticatorCode(): string
    {
        do {
            $code = strtoupper(substr(hash('sha256', uniqid(rand(), true)), 0, 16));
        } while (static::where('authenticator_code', $code)->exists());

        return $code;
    }

    /**
     * Verifica se o comprovante ainda está disponível
     */
    public function isAvailable(): bool
    {
        return $this->available_until > now();
    }

    /**
     * Scope para comprovantes disponíveis
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_until', '>', now());
    }

    /**
     * Scope para buscar por código autenticador
     */
    public function scopeByAuthenticator($query, $code)
    {
        return $query->where('authenticator_code', $code);
    }

    /**
     * Scope para buscar por funcionário
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope para buscar do mês atual
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereYear('marked_at', now()->year)
                     ->whereMonth('marked_at', now()->month);
    }

    /**
     * Retorna o nome legível da ação
     */
    public function getActionNameAttribute(): string
    {
        return match($this->action) {
            'clock_in' => 'ENTRADA',
            'clock_out' => 'SAÍDA',
            'lunch_start' => 'INÍCIO DO ALMOÇO',
            'lunch_end' => 'FIM DO ALMOÇO',
            default => 'REGISTRO',
        };
    }

    /**
     * Retorna a cor da ação
     */
    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'clock_in' => '#16a34a',
            'clock_out' => '#dc2626',
            'lunch_start' => '#eab308',
            'lunch_end' => '#3b82f6',
            default => '#6b7280',
        };
    }

    /**
     * Formata a localização
     */
    public function getFormattedLocationAttribute(): ?string
    {
        if (!$this->gps_latitude || !$this->gps_longitude) {
            return null;
        }

        return sprintf(
            'Lat: %.6f, Lon: %.6f (±%dm)',
            $this->gps_latitude,
            $this->gps_longitude,
            $this->gps_accuracy ?? 0
        );
    }

    /**
     * Retorna URL do comprovante
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('employee.receipt.download', $this->uuid);
    }

    /**
     * Retorna URL de visualização do comprovante
     */
    public function getViewUrlAttribute(): string
    {
        return route('employee.receipt.view', $this->uuid);
    }
}
