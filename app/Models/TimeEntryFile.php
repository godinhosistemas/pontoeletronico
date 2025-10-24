<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TimeEntryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'generated_by',
        'file_type',
        'period_start',
        'period_end',
        'employee_id',
        'file_path',
        'signature_path',
        'total_records',
        'file_size',
        'file_hash',
        'is_signed',
        'signed_at',
        'certificate_serial',
        'certificate_issuer',
        'statistics',
        'download_count',
        'last_downloaded_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'is_signed' => 'boolean',
        'signed_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'statistics' => 'array',
    ];

    /**
     * Relação com a empresa (tenant)
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relação com o usuário que gerou o arquivo
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Relação com o funcionário (apenas para AEJ)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Obtém o conteúdo do arquivo principal
     */
    public function getFileContent(): ?string
    {
        if (!$this->file_path || !Storage::disk('local')->exists($this->file_path)) {
            return null;
        }

        return Storage::disk('local')->get($this->file_path);
    }

    /**
     * Obtém o conteúdo do arquivo de assinatura
     */
    public function getSignatureContent(): ?string
    {
        if (!$this->signature_path || !Storage::disk('local')->exists($this->signature_path)) {
            return null;
        }

        return Storage::disk('local')->get($this->signature_path);
    }

    /**
     * Incrementa o contador de downloads
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Verifica se o arquivo está assinado digitalmente
     */
    public function isSigned(): bool
    {
        return $this->is_signed && $this->signature_path && Storage::disk('local')->exists($this->signature_path);
    }

    /**
     * Obtém o nome do arquivo para download
     */
    public function getDownloadFileName(): string
    {
        $tenant = $this->tenant;
        $cnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '00000000000000');
        $periodStart = $this->period_start->format('Ymd');
        $periodEnd = $this->period_end->format('Ymd');

        if ($this->file_type === 'AFD') {
            // Formato: AFD_CNPJ_YYYYMMDD_YYYYMMDD.txt
            return "AFD_{$cnpj}_{$periodStart}_{$periodEnd}.txt";
        } else {
            // AEJ - incluir matrícula do funcionário se houver
            $employeeCode = $this->employee ? "_{$this->employee->registration_number}" : '';
            return "AEJ_{$cnpj}{$employeeCode}_{$periodStart}_{$periodEnd}.txt";
        }
    }

    /**
     * Obtém o nome do arquivo de assinatura para download
     */
    public function getSignatureFileName(): string
    {
        return str_replace('.txt', '.p7s', $this->getDownloadFileName());
    }

    /**
     * Deleta os arquivos físicos associados
     */
    public function deleteFiles(): bool
    {
        $success = true;

        if ($this->file_path && Storage::disk('local')->exists($this->file_path)) {
            $success = Storage::disk('local')->delete($this->file_path) && $success;
        }

        if ($this->signature_path && Storage::disk('local')->exists($this->signature_path)) {
            $success = Storage::disk('local')->delete($this->signature_path) && $success;
        }

        return $success;
    }

    /**
     * Scope para filtrar por tipo de arquivo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
                     ->where('period_end', '<=', $end);
    }

    /**
     * Scope para filtrar arquivos assinados
     */
    public function scopeSigned($query)
    {
        return $query->where('is_signed', true);
    }

    /**
     * Boot method para deletar arquivos ao deletar o registro
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            $file->deleteFiles();
        });
    }
}
