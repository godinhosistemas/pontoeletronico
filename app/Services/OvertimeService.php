<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\Employee;
use App\Models\OvertimeBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OvertimeService
{
    // Constantes da CLT
    const CLT_MAX_DAILY_OVERTIME = 2; // 2 horas por dia
    const NIGHT_SHIFT_START = '22:00:00'; // 22h
    const NIGHT_SHIFT_END = '05:00:00'; // 5h
    const OVERTIME_PERCENTAGE_NORMAL = 50; // 50%
    const OVERTIME_PERCENTAGE_NIGHT = 20; // 20% adicional noturno
    const OVERTIME_PERCENTAGE_HOLIDAY = 100; // 100%
    const BANK_HOURS_EXPIRATION_MONTHS = 12; // 1 ano

    /**
     * Processa um registro de ponto e calcula horas extras automaticamente
     */
    public function processTimeEntry(TimeEntry $timeEntry): void
    {
        if (!$timeEntry->employee) {
            return;
        }

        $employee = $timeEntry->employee;
        $workSchedule = $employee->workSchedule;

        // Se não tem jornada, usa padrão CLT (8.8h/dia)
        $expectedDailyHours = $workSchedule ? $workSchedule->daily_hours : 8.8;

        // Calcula horas trabalhadas
        if ($timeEntry->total_hours > $expectedDailyHours) {
            $overtimeHours = $timeEntry->total_hours - $expectedDailyHours;

            // Determina o tipo de hora extra
            $overtimeType = $this->determineOvertimeType($timeEntry);

            // Calcula porcentagem
            $overtimePercentage = $this->getOvertimePercentage($overtimeType);

            // Valida limite CLT
            $this->validateCltLimit($timeEntry, $overtimeHours);

            // Atualiza o registro
            $timeEntry->update([
                'overtime_hours' => $overtimeHours,
                'overtime_type' => $overtimeType,
                'overtime_percentage' => $overtimePercentage,
                'type' => 'overtime', // Marca como overtime
            ]);

            // Adiciona ao banco de horas se aprovado
            if ($timeEntry->status === 'approved') {
                $this->addToBankHours($timeEntry);
            }
        } else {
            // Não tem hora extra
            $timeEntry->update([
                'overtime_hours' => 0,
                'overtime_type' => 'none',
                'overtime_percentage' => null,
                'clt_limit_validated' => true,
                'clt_limit_exceeded' => false,
            ]);
        }
    }

    /**
     * Determina o tipo de hora extra baseado no horário e dia
     */
    protected function determineOvertimeType(TimeEntry $timeEntry): string
    {
        $date = Carbon::parse($timeEntry->date);

        // Verifica se é domingo ou feriado
        if ($date->isSunday() || $this->isHoliday($date, $timeEntry->tenant_id)) {
            return 'holiday';
        }

        // Verifica se é período noturno
        if ($this->isNightShift($timeEntry)) {
            $timeEntry->is_night_shift = true;
            return 'night';
        }

        return 'normal';
    }

    /**
     * Verifica se o trabalho foi em período noturno (22h às 5h)
     */
    protected function isNightShift(TimeEntry $timeEntry): bool
    {
        if (!$timeEntry->clock_in || !$timeEntry->clock_out) {
            return false;
        }

        $clockIn = Carbon::parse($timeEntry->clock_in);
        $clockOut = Carbon::parse($timeEntry->clock_out);
        $nightStart = Carbon::parse(self::NIGHT_SHIFT_START);
        $nightEnd = Carbon::parse(self::NIGHT_SHIFT_END)->addDay();

        // Verifica se algum período do trabalho está entre 22h e 5h
        return $clockIn->between($nightStart, $nightEnd) ||
               $clockOut->between($nightStart, $nightEnd) ||
               ($clockIn->hour >= 22 || $clockIn->hour < 5);
    }

    /**
     * Verifica se a data é feriado
     */
    protected function isHoliday(Carbon $date, int $tenantId): bool
    {
        return \App\Models\Holiday::isHoliday($date, $tenantId);
    }

    /**
     * Retorna a porcentagem de adicional baseada no tipo
     */
    protected function getOvertimePercentage(string $overtimeType): float
    {
        return match($overtimeType) {
            'normal' => self::OVERTIME_PERCENTAGE_NORMAL,
            'night' => self::OVERTIME_PERCENTAGE_NIGHT,
            'holiday' => self::OVERTIME_PERCENTAGE_HOLIDAY,
            default => 0,
        };
    }

    /**
     * Valida se as horas extras estão dentro do limite da CLT
     */
    protected function validateCltLimit(TimeEntry $timeEntry, float $overtimeHours): void
    {
        $timeEntry->clt_limit_validated = true;

        if ($overtimeHours > self::CLT_MAX_DAILY_OVERTIME) {
            $timeEntry->clt_limit_exceeded = true;
            $exceeded = $overtimeHours - self::CLT_MAX_DAILY_OVERTIME;
            $timeEntry->clt_violation_notes = sprintf(
                'Excedeu o limite CLT de %d horas extras/dia em %.2f horas',
                self::CLT_MAX_DAILY_OVERTIME,
                $exceeded
            );
        } else {
            $timeEntry->clt_limit_exceeded = false;
            $timeEntry->clt_violation_notes = null;
        }
    }

    /**
     * Adiciona horas extras ao banco de horas do funcionário
     */
    public function addToBankHours(TimeEntry $timeEntry): void
    {
        if ($timeEntry->overtime_hours <= 0) {
            return;
        }

        $period = Carbon::parse($timeEntry->date)->format('Y-m');
        $expirationDate = Carbon::parse($timeEntry->date)
            ->addMonths(self::BANK_HOURS_EXPIRATION_MONTHS);

        $balance = OvertimeBalance::firstOrCreate(
            [
                'employee_id' => $timeEntry->employee_id,
                'tenant_id' => $timeEntry->tenant_id,
                'period' => $period,
            ],
            [
                'earned_hours' => 0,
                'compensated_hours' => 0,
                'balance_hours' => 0,
                'status' => 'active',
                'expiration_date' => $expirationDate,
            ]
        );

        $balance->addEarnedHours($timeEntry->overtime_hours);
    }

    /**
     * Compensa horas do banco de horas
     */
    public function compensateHours(Employee $employee, float $hours, string $period = null): bool
    {
        if (!$period) {
            $period = Carbon::now()->format('Y-m');
        }

        $balance = OvertimeBalance::forEmployee($employee->id)
            ->forPeriod($period)
            ->active()
            ->first();

        if (!$balance || $balance->balance_hours < $hours) {
            return false;
        }

        $balance->compensateHours($hours);
        return true;
    }

    /**
     * Obtém o saldo total de banco de horas do funcionário
     */
    public function getTotalBankHours(Employee $employee): float
    {
        return OvertimeBalance::forEmployee($employee->id)
            ->active()
            ->sum('balance_hours');
    }

    /**
     * Obtém o saldo do banco de horas por período
     */
    public function getBankHoursByPeriod(Employee $employee, string $period): ?OvertimeBalance
    {
        return OvertimeBalance::forEmployee($employee->id)
            ->forPeriod($period)
            ->first();
    }

    /**
     * Expira bancos de horas vencidos
     */
    public function expireBankHours(): int
    {
        $expired = OvertimeBalance::where('status', 'active')
            ->where('expiration_date', '<', Carbon::now())
            ->get();

        foreach ($expired as $balance) {
            $balance->markAsExpired();
        }

        return $expired->count();
    }

    /**
     * Reprocessa horas extras de um período
     */
    public function reprocessPeriod(int $employeeId, string $startDate, string $endDate): int
    {
        $timeEntries = TimeEntry::forEmployee($employeeId)
            ->forPeriod($startDate, $endDate)
            ->get();

        $count = 0;
        foreach ($timeEntries as $entry) {
            $this->processTimeEntry($entry);
            $count++;
        }

        return $count;
    }

    /**
     * Obtém relatório de horas extras por funcionário
     */
    public function getOvertimeReport(int $employeeId, string $startDate, string $endDate): array
    {
        $timeEntries = TimeEntry::forEmployee($employeeId)
            ->forPeriod($startDate, $endDate)
            ->withOvertime()
            ->get();

        $totalOvertime = $timeEntries->sum('overtime_hours');
        $totalNormal = $timeEntries->where('overtime_type', 'normal')->sum('overtime_hours');
        $totalNight = $timeEntries->where('overtime_type', 'night')->sum('overtime_hours');
        $totalHoliday = $timeEntries->where('overtime_type', 'holiday')->sum('overtime_hours');
        $cltViolations = $timeEntries->where('clt_limit_exceeded', true)->count();

        return [
            'total_overtime_hours' => $totalOvertime,
            'normal_overtime' => $totalNormal,
            'night_overtime' => $totalNight,
            'holiday_overtime' => $totalHoliday,
            'clt_violations' => $cltViolations,
            'entries' => $timeEntries,
        ];
    }

    /**
     * Calcula valor monetário das horas extras (opcional)
     */
    public function calculateOvertimeValue(float $baseHourlyRate, float $overtimeHours, string $overtimeType): float
    {
        $percentage = $this->getOvertimePercentage($overtimeType);
        $multiplier = 1 + ($percentage / 100);

        return $baseHourlyRate * $overtimeHours * $multiplier;
    }
}
