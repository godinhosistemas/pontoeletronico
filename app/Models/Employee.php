<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'cpf',
        'registration_number',
        'unique_code',
        'phone',
        'birth_date',
        'position',
        'department',
        'admission_date',
        'termination_date',
        'salary',
        'photo',
        'face_photo',
        'face_descriptor',
        'address',
        'city',
        'state',
        'zip_code',
        'status',
        'work_schedule',
        'work_schedule_id',
        'is_active',
        'allowed_latitude',
        'allowed_longitude',
        'geofence_radius',
        'require_geolocation',
        // Dados Pessoais Estendidos
        'rg', 'rg_issuer', 'rg_issue_date',
        'ctps', 'ctps_series', 'ctps_uf',
        'pis_pasep', 'voter_registration', 'voter_zone', 'voter_section',
        'military_certificate', 'cnh', 'cnh_category', 'cnh_expiry',
        'gender', 'marital_status', 'nationality', 'birth_place',
        'mothers_name', 'fathers_name', 'education_level',
        // Endereço Estendido
        'address_number', 'address_complement', 'neighborhood',
        // Dados Contratuais
        'contract_type', 'contract_number', 'contract_start_date', 'contract_end_date',
        'workload_hours', 'cost_center', 'immediate_supervisor', 'has_benefits',
        // Dados Bancários
        'bank_name', 'bank_code', 'bank_agency', 'bank_account', 'bank_account_type', 'pix_key',
        // Saúde
        'health_insurance', 'health_insurance_number', 'blood_type',
        'allergies', 'medications', 'health_conditions',
        // Exames Médicos
        'admission_exam_date', 'next_periodic_exam_date', 'aso_number',
        // Outros
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'admission_date' => 'date',
        'termination_date' => 'date',
        'work_schedule' => 'array',
        'is_active' => 'boolean',
        'salary' => 'decimal:2',
        'rg_issue_date' => 'date',
        'cnh_expiry' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'workload_hours' => 'decimal:2',
        'has_benefits' => 'boolean',
        'admission_exam_date' => 'date',
        'next_periodic_exam_date' => 'date',
    ];

    /**
     * Relacionamento com Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relacionamento com WorkSchedule (Jornada de Trabalho)
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id');
    }

    /**
     * Relacionamento com Dependentes
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }

    /**
     * Relacionamento com Contatos de Emergência
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    /**
     * Relacionamento com Documentos
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Relacionamento com Histórico
     */
    public function history(): HasMany
    {
        return $this->hasMany(EmployeeHistory::class)->orderBy('event_date', 'desc');
    }

    /**
     * Relacionamento com Compliance
     */
    public function compliance(): HasMany
    {
        return $this->hasMany(EmployeeCompliance::class);
    }

    /**
     * Compliance items pendentes
     */
    public function pendingCompliance(): HasMany
    {
        return $this->hasMany(EmployeeCompliance::class)
            ->where('status', 'Pendente')
            ->orWhere('status', 'Vencido');
    }

    /**
     * Scope para filtrar apenas funcionários ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Verifica se o funcionário está ativo
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === 'active';
    }

    /**
     * Formata o CPF
     */
    public function getFormattedCpfAttribute(): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }

    /**
     * Formata o salário
     */
    public function getFormattedSalaryAttribute(): string
    {
        return 'R$ ' . number_format($this->salary, 2, ',', '.');
    }

    /**
     * Obtém as iniciais do nome para avatar
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', $this->name);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Obtém a cor do status
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'red',
            'vacation' => 'blue',
            'leave' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Obtém o texto traduzido do status
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'vacation' => 'Férias',
            'leave' => 'Afastado',
            default => 'Indefinido',
        };
    }
}
