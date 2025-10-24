<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\TimeEntryFile;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Serviço para geração de Arquivo Eletrônico de Jornada (AEJ)
 * Conforme Portaria MTP nº 671/2021 - Art. 83
 *
 * O AEJ contém os dados processados da jornada (com totalizações e tratamentos)
 */
class AEJService
{
    private CertificateService $certificateService;
    private int $nsr = 0;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Gera arquivo AEJ para um funcionário em um período
     *
     * @param Employee $employee
     * @param string $startDate
     * @param string $endDate
     * @param int|null $generatedBy
     * @return TimeEntryFile|null
     */
    public function generateAEJ(Employee $employee, string $startDate, string $endDate, ?int $generatedBy = null): ?TimeEntryFile
    {
        try {
            DB::beginTransaction();

            $this->nsr = 0;

            // Gera o conteúdo do AEJ
            $content = $this->buildAEJContent($employee, $startDate, $endDate);

            if (empty($content)) {
                return null;
            }

            // Salva o arquivo
            $fileName = $this->generateFileName($employee, $startDate, $endDate);
            $filePath = "aej/{$employee->tenant_id}/{$fileName}";

            Storage::disk('local')->put($filePath, $content);

            // Calcula estatísticas
            $statistics = $this->calculateStatistics($employee, $startDate, $endDate);

            // Cria registro no banco
            $fileRecord = TimeEntryFile::create([
                'tenant_id' => $employee->tenant_id,
                'generated_by' => $generatedBy,
                'file_type' => 'AEJ',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'employee_id' => $employee->id,
                'file_path' => $filePath,
                'total_records' => $this->nsr,
                'file_size' => strlen($content),
                'file_hash' => hash('sha256', $content),
                'statistics' => $statistics,
            ]);

            // Tenta assinar digitalmente
            if ($employee->tenant->certificate_active) {
                $this->signAEJ($fileRecord);
            }

            DB::commit();

            return $fileRecord;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao gerar AEJ: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Gera AEJ para todos os funcionários de uma empresa no período
     */
    public function generateBulkAEJ(Tenant $tenant, string $startDate, string $endDate, ?int $generatedBy = null): array
    {
        $files = [];

        $employees = Employee::where('tenant_id', $tenant->id)
            ->whereHas('timeEntries', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->get();

        foreach ($employees as $employee) {
            try {
                $file = $this->generateAEJ($employee, $startDate, $endDate, $generatedBy);
                if ($file) {
                    $files[] = $file;
                }
            } catch (\Exception $e) {
                \Log::error("Erro ao gerar AEJ para funcionário {$employee->id}: " . $e->getMessage());
            }
        }

        return $files;
    }

    /**
     * Constrói o conteúdo do arquivo AEJ
     */
    private function buildAEJContent(Employee $employee, string $startDate, string $endDate): string
    {
        $lines = [];
        $tenant = $employee->tenant;

        // Tipo 1: Cabeçalho
        $lines[] = $this->buildHeaderRecord($tenant, $employee);

        // Tipo 2: Dados do empregador
        $lines[] = $this->buildEmployerRecord($tenant);

        // Tipo 3: Dados do empregado
        $lines[] = $this->buildEmployeeRecord($employee);

        // Tipo 4: Configuração de jornada
        if ($employee->workSchedule) {
            $lines[] = $this->buildWorkScheduleRecord($employee->workSchedule);
        }

        // Busca marcações do período
        $timeEntries = TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        foreach ($timeEntries as $entry) {
            // Tipo 5: Jornada do dia (processada)
            $lines[] = $this->buildDailyJourneyRecord($entry, $employee);

            // Tipo 6: Marcações do dia
            $lines = array_merge($lines, $this->buildClockingRecords($entry));

            // Tipo 7: Totalizadores do dia
            $lines[] = $this->buildDailyTotalsRecord($entry, $employee);
        }

        // Tipo 8: Totalizadores do período
        $lines[] = $this->buildPeriodTotalsRecord($employee, $startDate, $endDate);

        // Tipo 9: Trailer
        $lines[] = $this->buildTrailerRecord();

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Tipo 1: Cabeçalho do AEJ
     */
    private function buildHeaderRecord(Tenant $tenant, Employee $employee): string
    {
        $this->nsr++;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '1',                                            // Tipo: Header
            'AEJ',                                          // Identificador
            '01',                                           // Versão do layout
            now()->format('dmYHis'),                        // Data/hora de geração
            str_pad('NEXT PONTO', 30),                      // Razão social do fabricante
            str_pad(config('app.inpi_registration', ''), 20),
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 2: Identificação do Empregador
     */
    private function buildEmployerRecord(Tenant $tenant): string
    {
        $this->nsr++;

        $cnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '00000000000000');
        $razaoSocial = mb_convert_encoding($tenant->name ?? '', 'ISO-8859-1', 'UTF-8');

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '2',
            str_pad($cnpj, 14, '0', STR_PAD_LEFT),
            str_pad($razaoSocial, 150),
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 3: Identificação do Empregado
     */
    private function buildEmployeeRecord(Employee $employee): string
    {
        $this->nsr++;

        $pis = preg_replace('/[^0-9]/', '', $employee->pis ?? '00000000000');
        $cpf = preg_replace('/[^0-9]/', '', $employee->cpf ?? '00000000000');
        $nome = mb_convert_encoding($employee->name ?? '', 'ISO-8859-1', 'UTF-8');
        $matricula = $employee->registration_number ?? '';
        $admissao = $employee->hired_at ? Carbon::parse($employee->hired_at)->format('dmY') : '';

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '3',
            str_pad($pis, 11, '0', STR_PAD_LEFT),
            str_pad($cpf, 11, '0', STR_PAD_LEFT),
            str_pad($nome, 52),
            str_pad($matricula, 20),
            $admissao,
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 4: Configuração de Jornada
     */
    private function buildWorkScheduleRecord(WorkSchedule $schedule): string
    {
        $this->nsr++;

        $nome = mb_convert_encoding($schedule->name ?? '', 'ISO-8859-1', 'UTF-8');
        $horasSemanais = str_pad(($schedule->weekly_hours ?? 0) * 60, 4, '0', STR_PAD_LEFT); // em minutos

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '4',
            str_pad($nome, 50),
            $horasSemanais,
            str_pad($schedule->break_minutes ?? 0, 3, '0', STR_PAD_LEFT),
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 5: Jornada do Dia (processada)
     */
    private function buildDailyJourneyRecord(TimeEntry $entry, Employee $employee): string
    {
        $this->nsr++;

        $date = $entry->date->format('dmY');
        $jornadaEsperada = $this->getExpectedWorkMinutes($entry->date, $employee);
        $jornadaTrabalhada = $entry->total_minutes ?? 0;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '5',
            $date,
            str_pad($jornadaEsperada, 4, '0', STR_PAD_LEFT),
            str_pad($jornadaTrabalhada, 4, '0', STR_PAD_LEFT),
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 6: Marcações do Dia
     */
    private function buildClockingRecords(TimeEntry $entry): array
    {
        $records = [];

        $markings = [
            ['time' => $entry->clock_in, 'type' => 'E'],
            ['time' => $entry->lunch_start, 'type' => 'S'],
            ['time' => $entry->lunch_end, 'type' => 'E'],
            ['time' => $entry->clock_out, 'type' => 'S'],
        ];

        foreach ($markings as $marking) {
            if ($marking['time']) {
                $this->nsr++;

                $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $marking['time']);

                $records[] = implode("\t", [
                    str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                    '6',
                    $datetime->format('dmYHis'),
                    $marking['type'],
                ]);
            }
        }

        return $records;
    }

    /**
     * Tipo 7: Totalizadores do Dia
     */
    private function buildDailyTotalsRecord(TimeEntry $entry, Employee $employee): string
    {
        $this->nsr++;

        $jornadaEsperada = $this->getExpectedWorkMinutes($entry->date, $employee);
        $jornadaTrabalhada = $entry->total_minutes ?? 0;

        // Calcula horas extras e faltosas
        $horasExtras = max(0, $jornadaTrabalhada - $jornadaEsperada);
        $horasFaltosas = max(0, $jornadaEsperada - $jornadaTrabalhada);

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '7',
            $entry->date->format('dmY'),
            str_pad($jornadaTrabalhada, 4, '0', STR_PAD_LEFT),      // Total trabalhado
            str_pad($horasExtras, 4, '0', STR_PAD_LEFT),             // Horas extras
            str_pad($horasFaltosas, 4, '0', STR_PAD_LEFT),           // Horas faltosas
            str_pad($entry->has_adjustment ? '1' : '0', 1),          // Teve ajuste?
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 8: Totalizadores do Período
     */
    private function buildPeriodTotalsRecord(Employee $employee, string $startDate, string $endDate): string
    {
        $this->nsr++;

        $stats = $this->calculateStatistics($employee, $startDate, $endDate);

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '8',
            str_pad($stats['total_minutes_worked'], 6, '0', STR_PAD_LEFT),
            str_pad($stats['total_overtime'], 6, '0', STR_PAD_LEFT),
            str_pad($stats['total_absent'], 6, '0', STR_PAD_LEFT),
            str_pad($stats['total_days'], 3, '0', STR_PAD_LEFT),
            str_pad($stats['total_adjustments'], 3, '0', STR_PAD_LEFT),
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 9: Trailer
     */
    private function buildTrailerRecord(): string
    {
        $this->nsr++;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
            '9',
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
        ];

        return implode("\t", $fields);
    }

    /**
     * Obtém minutos esperados de trabalho para um dia específico
     */
    private function getExpectedWorkMinutes(Carbon $date, Employee $employee): int
    {
        $schedule = $employee->workSchedule;

        if (!$schedule || !$schedule->days_config) {
            return 0;
        }

        $dayOfWeek = strtolower($date->locale('en')->dayName);
        $config = $schedule->days_config[$dayOfWeek] ?? null;

        if (!$config || !($config['enabled'] ?? false)) {
            return 0;
        }

        // Calcula minutos de trabalho esperados
        $start = Carbon::parse($config['start'] ?? '08:00');
        $end = Carbon::parse($config['end'] ?? '18:00');
        $breakMinutes = $schedule->break_minutes ?? 60;

        $totalMinutes = $start->diffInMinutes($end);
        return max(0, $totalMinutes - $breakMinutes);
    }

    /**
     * Calcula estatísticas do período
     */
    private function calculateStatistics(Employee $employee, string $startDate, string $endDate): array
    {
        $entries = TimeEntry::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalMinutes = 0;
        $totalOvertime = 0;
        $totalAbsent = 0;
        $totalAdjustments = 0;

        foreach ($entries as $entry) {
            $expected = $this->getExpectedWorkMinutes($entry->date, $employee);
            $worked = $entry->total_minutes ?? 0;

            $totalMinutes += $worked;
            $totalOvertime += max(0, $worked - $expected);
            $totalAbsent += max(0, $expected - $worked);

            if ($entry->has_adjustment) {
                $totalAdjustments++;
            }
        }

        return [
            'total_days' => $entries->count(),
            'total_minutes_worked' => $totalMinutes,
            'total_hours_worked' => round($totalMinutes / 60, 2),
            'total_overtime' => $totalOvertime,
            'total_overtime_hours' => round($totalOvertime / 60, 2),
            'total_absent' => $totalAbsent,
            'total_absent_hours' => round($totalAbsent / 60, 2),
            'total_adjustments' => $totalAdjustments,
        ];
    }

    /**
     * Assina digitalmente o arquivo AEJ
     */
    private function signAEJ(TimeEntryFile $fileRecord): bool
    {
        try {
            $tenant = $fileRecord->tenant;
            $certificates = $this->certificateService->getCertificateForSigning($tenant);

            if (!$certificates) {
                return false;
            }

            $content = $fileRecord->getFileContent();
            if (!$content) {
                return false;
            }

            $signaturePath = str_replace('.txt', '.p7s', $fileRecord->file_path);

            $success = openssl_pkcs7_sign(
                storage_path('app/' . $fileRecord->file_path),
                storage_path('app/' . $signaturePath . '.tmp'),
                $certificates['cert'],
                $certificates['pkey'],
                [],
                PKCS7_DETACHED | PKCS7_BINARY
            );

            if ($success) {
                $signed = file_get_contents(storage_path('app/' . $signaturePath . '.tmp'));
                $signatureOnly = $this->extractSignatureFromPKCS7($signed);

                Storage::disk('local')->put($signaturePath, $signatureOnly);
                @unlink(storage_path('app/' . $signaturePath . '.tmp'));

                $fileRecord->update([
                    'signature_path' => $signaturePath,
                    'is_signed' => true,
                    'signed_at' => now(),
                    'certificate_serial' => $tenant->certificate_serial_number,
                    'certificate_issuer' => $tenant->certificate_issuer,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Erro ao assinar AEJ: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extrai assinatura do PKCS7
     */
    private function extractSignatureFromPKCS7(string $pkcs7Content): string
    {
        $lines = explode("\n", $pkcs7Content);
        $signature = '';
        $capturing = false;

        foreach ($lines as $line) {
            if (strpos($line, '-----BEGIN PKCS7-----') !== false) {
                $capturing = true;
                continue;
            }
            if (strpos($line, '-----END PKCS7-----') !== false) {
                break;
            }
            if ($capturing) {
                $signature .= trim($line);
            }
        }

        return base64_decode($signature);
    }

    /**
     * Gera nome do arquivo AEJ
     */
    private function generateFileName(Employee $employee, string $startDate, string $endDate): string
    {
        $tenant = $employee->tenant;
        $cnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '00000000000000');
        $matricula = $employee->registration_number ?? $employee->id;
        $start = Carbon::parse($startDate)->format('Ymd');
        $end = Carbon::parse($endDate)->format('Ymd');

        return "AEJ_{$cnpj}_{$matricula}_{$start}_{$end}.txt";
    }
}
