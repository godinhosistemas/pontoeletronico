<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'date',
        'type',
        'city',
        'state',
        'is_recurring',
        'description',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Verifica se a data fornecida é um feriado para o tenant
     */
    public static function isHoliday(Carbon $date, int $tenantId): bool
    {
        return self::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                // Busca pela data exata
                $query->whereDate('date', $date->format('Y-m-d'))
                    // OU se é recorrente, busca pelo dia e mês (ignora ano)
                    ->orWhere(function ($q) use ($date) {
                        $q->where('is_recurring', true)
                          ->whereMonth('date', $date->month)
                          ->whereDay('date', $date->day);
                    });
            })
            ->exists();
    }

    /**
     * Retorna feriados de um período
     */
    public static function getHolidaysInPeriod(Carbon $startDate, Carbon $endDate, int $tenantId)
    {
        return self::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        // Feriados recorrentes
                        $q->where('is_recurring', true)
                          ->whereMonth('date', '>=', $startDate->month)
                          ->whereMonth('date', '<=', $endDate->month);
                    });
            })
            ->orderBy('date')
            ->get();
    }

    /**
     * Retorna o tipo formatado
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'national' => 'Nacional',
            'state' => 'Estadual',
            'municipal' => 'Municipal',
            'custom' => 'Personalizado',
            default => 'Indefinido',
        };
    }

    /**
     * Retorna a cor do tipo
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'national' => 'blue',
            'state' => 'green',
            'municipal' => 'purple',
            'custom' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Retorna a data formatada
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('d/m/Y');
    }

    /**
     * Retorna a data formatada por extenso
     */
    public function getFormattedDateLongAttribute(): string
    {
        return $this->date->locale('pt_BR')->isoFormat('DD [de] MMMM [de] YYYY');
    }

    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope para filtrar ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para filtrar recorrentes
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope para buscar por ano
     */
    public function scopeForYear($query, $year)
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Cria feriados nacionais padrão do Brasil
     */
    public static function createDefaultNationalHolidays(int $tenantId, int $year = null): int
    {
        $year = $year ?? Carbon::now()->year;

        $nationalHolidays = [
            ['name' => 'Ano Novo', 'date' => "{$year}-01-01", 'is_recurring' => true],
            ['name' => 'Tiradentes', 'date' => "{$year}-04-21", 'is_recurring' => true],
            ['name' => 'Dia do Trabalho', 'date' => "{$year}-05-01", 'is_recurring' => true],
            ['name' => 'Independência do Brasil', 'date' => "{$year}-09-07", 'is_recurring' => true],
            ['name' => 'Nossa Senhora Aparecida', 'date' => "{$year}-10-12", 'is_recurring' => true],
            ['name' => 'Finados', 'date' => "{$year}-11-02", 'is_recurring' => true],
            ['name' => 'Proclamação da República', 'date' => "{$year}-11-15", 'is_recurring' => true],
            ['name' => 'Consciência Negra', 'date' => "{$year}-11-20", 'is_recurring' => true],
            ['name' => 'Natal', 'date' => "{$year}-12-25", 'is_recurring' => true],
        ];

        $count = 0;
        foreach ($nationalHolidays as $holiday) {
            self::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $holiday['name'],
                    'date' => $holiday['date'],
                ],
                [
                    'type' => 'national',
                    'is_recurring' => $holiday['is_recurring'],
                    'is_active' => true,
                ]
            );
            $count++;
        }

        return $count;
    }
}
