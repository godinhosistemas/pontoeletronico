<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\TimeEntryFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Serviço para geração de Arquivo Fonte de Dados (AFD)
 * Conforme Portaria MTP nº 671/2021 - Art. 81
 *
 * O AFD contém os dados brutos das marcações de ponto em formato padronizado
 */
class AFDService
{
    private CertificateService $certificateService;
    private int $nsr = 0; // Número Sequencial do Registro

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Gera arquivo AFD para um período
     *
     * @param Tenant $tenant
     * @param string $startDate Data início (Y-m-d)
     * @param string $endDate Data fim (Y-m-d)
     * @param int|null $generatedBy ID do usuário que está gerando
     * @return TimeEntryFile|null
     */
    public function generateAFD(Tenant $tenant, string $startDate, string $endDate, ?int $generatedBy = null): ?TimeEntryFile
    {
        try {
            DB::beginTransaction();

            $this->nsr = 0; // Reset do contador sequencial

            // Gera o conteúdo do AFD
            $content = $this->buildAFDContent($tenant, $startDate, $endDate);

            if (empty($content)) {
                return null;
            }

            // Salva o arquivo
            $fileName = $this->generateFileName($tenant, $startDate, $endDate);
            $filePath = "afd/{$tenant->id}/{$fileName}";

            Storage::disk('local')->put($filePath, $content);

            // Calcula estatísticas
            $statistics = $this->calculateStatistics($tenant, $startDate, $endDate);

            // Cria registro no banco
            $fileRecord = TimeEntryFile::create([
                'tenant_id' => $tenant->id,
                'generated_by' => $generatedBy,
                'file_type' => 'AFD',
                'period_start' => $startDate,
                'period_end' => $endDate,
                'file_path' => $filePath,
                'total_records' => $this->nsr,
                'file_size' => strlen($content),
                'file_hash' => hash('sha256', $content),
                'statistics' => $statistics,
            ]);

            // Tenta assinar digitalmente se certificado estiver disponível
            if ($tenant->certificate_active) {
                $this->signAFD($fileRecord);
            }

            DB::commit();

            return $fileRecord;

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erro ao gerar AFD: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Constrói o conteúdo completo do arquivo AFD
     */
    private function buildAFDContent(Tenant $tenant, string $startDate, string $endDate): string
    {
        $lines = [];

        // Tipo 1: Cabeçalho
        $lines[] = $this->buildHeaderRecord($tenant);

        // Tipo 2: Dados do empregador
        $lines[] = $this->buildEmployerRecord($tenant);

        // Tipo 3: Identificação do REP (Registrador Eletrônico de Ponto)
        $lines[] = $this->buildREPRecord($tenant);

        // Busca todos os funcionários com marcações no período
        $employees = Employee::where('tenant_id', $tenant->id)
            ->whereHas('timeEntries', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->get();

        foreach ($employees as $employee) {
            // Tipo 4: Identificação do empregado
            $lines[] = $this->buildEmployeeRecord($employee);

            // Busca marcações do funcionário no período
            $timeEntries = TimeEntry::where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date')
                ->orderBy('clock_in')
                ->get();

            foreach ($timeEntries as $entry) {
                // Tipo 3: Marcações de ponto
                $lines = array_merge($lines, $this->buildClockingRecords($entry));

                // Se houver ajustes, gerar registros de ajuste
                if ($entry->has_adjustment) {
                    $lines = array_merge($lines, $this->buildAdjustmentRecords($entry));
                }
            }
        }

        // Tipo 9: Trailer (totalizador)
        $lines[] = $this->buildTrailerRecord($tenant, $startDate, $endDate);

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Tipo 1: Registro de Cabeçalho do AFD
     */
    private function buildHeaderRecord(Tenant $tenant): string
    {
        $this->nsr++;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // NSR (9 dígitos)
            '1',                                            // Tipo de registro: 1 = Header
            'AFD',                                          // Identificador AFD
            '01',                                           // Versão do layout
            now()->format('dmYHis'),                        // Data/hora de geração
            str_pad('NEXT PONTO', 30),                      // Razão social do fabricante
            str_pad(config('app.inpi_registration', ''), 20), // Registro INPI
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 2: Registro de Identificação do Empregador
     */
    private function buildEmployerRecord(Tenant $tenant): string
    {
        $this->nsr++;

        $cnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '00000000000000');
        $cei = ''; // CEI se houver
        $razaoSocial = mb_convert_encoding($tenant->name ?? '', 'ISO-8859-1', 'UTF-8');
        $nomeFantasia = mb_convert_encoding($tenant->trade_name ?? $tenant->name ?? '', 'ISO-8859-1', 'UTF-8');

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // NSR
            '2',                                            // Tipo: 2 = Empregador
            str_pad($cnpj, 14, '0', STR_PAD_LEFT),         // CNPJ
            str_pad($cei, 12, '0', STR_PAD_LEFT),          // CEI
            str_pad($razaoSocial, 150),                    // Razão social
            str_pad($nomeFantasia, 150),                   // Nome fantasia
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 3: Registro de Identificação do REP
     */
    private function buildREPRecord(Tenant $tenant): string
    {
        $this->nsr++;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // NSR
            '3',                                            // Tipo: 3 = REP
            'WEB001',                                       // Número de série do REP
            'REP-P',                                        // Tipo de REP (P=Programa)
            '1.0.0',                                        // Versão do firmware
            now()->format('dmY'),                           // Data de fabricação
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 4: Registro de Identificação do Empregado
     */
    private function buildEmployeeRecord(Employee $employee): string
    {
        $this->nsr++;

        $pis = preg_replace('/[^0-9]/', '', $employee->pis ?? '00000000000');
        $cpf = preg_replace('/[^0-9]/', '', $employee->cpf ?? '00000000000');
        $nome = mb_convert_encoding($employee->name ?? '', 'ISO-8859-1', 'UTF-8');
        $matricula = $employee->registration_number ?? '';

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // NSR
            '4',                                            // Tipo: 4 = Empregado
            str_pad($pis, 11, '0', STR_PAD_LEFT),          // PIS
            str_pad($cpf, 11, '0', STR_PAD_LEFT),          // CPF (adicional)
            str_pad($nome, 52),                            // Nome
            str_pad($matricula, 20),                       // Matrícula
        ];

        return implode("\t", $fields);
    }

    /**
     * Tipo 3: Registros de Marcação de Ponto
     */
    private function buildClockingRecords(TimeEntry $entry): array
    {
        $records = [];
        $employee = $entry->employee;
        $pis = preg_replace('/[^0-9]/', '', $employee->pis ?? '00000000000');

        // Marcação de entrada
        if ($entry->clock_in) {
            $this->nsr++;
            $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->clock_in);

            $records[] = implode("\t", [
                str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                '3',                                        // Tipo: 3 = Marcação
                $datetime->format('dmYHis'),                // Data/hora
                str_pad($pis, 11, '0', STR_PAD_LEFT),      // PIS
                'E',                                        // Tipo: E=Entrada
                '0',                                        // Origem: 0=Original
            ]);
        }

        // Marcação de início de almoço
        if ($entry->lunch_start) {
            $this->nsr++;
            $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->lunch_start);

            $records[] = implode("\t", [
                str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                '3',
                $datetime->format('dmYHis'),
                str_pad($pis, 11, '0', STR_PAD_LEFT),
                'S',                                        // Tipo: S=Saída
                '0',
            ]);
        }

        // Marcação de fim de almoço
        if ($entry->lunch_end) {
            $this->nsr++;
            $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->lunch_end);

            $records[] = implode("\t", [
                str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                '3',
                $datetime->format('dmYHis'),
                str_pad($pis, 11, '0', STR_PAD_LEFT),
                'E',
                '0',
            ]);
        }

        // Marcação de saída
        if ($entry->clock_out) {
            $this->nsr++;
            $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->clock_out);

            $records[] = implode("\t", [
                str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                '3',
                $datetime->format('dmYHis'),
                str_pad($pis, 11, '0', STR_PAD_LEFT),
                'S',
                '0',
            ]);
        }

        return $records;
    }

    /**
     * Registros de ajuste de marcação
     */
    private function buildAdjustmentRecords(TimeEntry $entry): array
    {
        $records = [];
        $employee = $entry->employee;
        $pis = preg_replace('/[^0-9]/', '', $employee->pis ?? '00000000000');

        // Se há ajuste na entrada
        if ($entry->adjusted_clock_in && $entry->original_clock_in !== $entry->adjusted_clock_in) {
            $this->nsr++;
            $datetime = Carbon::parse($entry->date->format('Y-m-d') . ' ' . $entry->adjusted_clock_in);

            $records[] = implode("\t", [
                str_pad($this->nsr, 9, '0', STR_PAD_LEFT),
                '5',                                        // Tipo: 5 = Ajuste
                $datetime->format('dmYHis'),
                str_pad($pis, 11, '0', STR_PAD_LEFT),
                'E',
                '1',                                        // Origem: 1=Ajustado
                mb_convert_encoding(substr($entry->adjustment_reason ?? '', 0, 100), 'ISO-8859-1', 'UTF-8'),
            ]);
        }

        // Ajustes similares para lunch_start, lunch_end e clock_out
        // (implementação similar à entrada)

        return $records;
    }

    /**
     * Tipo 9: Registro Trailer (totalizador)
     */
    private function buildTrailerRecord(Tenant $tenant, string $startDate, string $endDate): string
    {
        $this->nsr++;

        $fields = [
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // NSR
            '9',                                            // Tipo: 9 = Trailer
            str_pad($this->nsr, 9, '0', STR_PAD_LEFT),     // Total de registros
        ];

        return implode("\t", $fields);
    }

    /**
     * Assina digitalmente o arquivo AFD usando certificado ICP-Brasil
     */
    private function signAFD(TimeEntryFile $fileRecord): bool
    {
        try {
            $tenant = $fileRecord->tenant;
            $certificates = $this->certificateService->getCertificateForSigning($tenant);

            if (!$certificates) {
                \Log::warning("Certificado não disponível para tenant {$tenant->id}");
                return false;
            }

            // Lê o conteúdo do arquivo
            $content = $fileRecord->getFileContent();
            if (!$content) {
                return false;
            }

            // Cria assinatura CAdES (CMS Advanced Electronic Signature)
            $signaturePath = str_replace('.txt', '.p7s', $fileRecord->file_path);

            // Assina usando OpenSSL (formato PKCS7 detached)
            $success = openssl_pkcs7_sign(
                storage_path('app/' . $fileRecord->file_path),
                storage_path('app/' . $signaturePath . '.tmp'),
                $certificates['cert'],
                $certificates['pkey'],
                [],
                PKCS7_DETACHED | PKCS7_BINARY
            );

            if ($success) {
                // Extrai apenas a assinatura (remove headers MIME)
                $signed = file_get_contents(storage_path('app/' . $signaturePath . '.tmp'));
                $signatureOnly = $this->extractSignatureFromPKCS7($signed);

                Storage::disk('local')->put($signaturePath, $signatureOnly);
                @unlink(storage_path('app/' . $signaturePath . '.tmp'));

                // Atualiza o registro
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
            \Log::error('Erro ao assinar AFD: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Extrai a assinatura binária do arquivo PKCS7
     */
    private function extractSignatureFromPKCS7(string $pkcs7Content): string
    {
        // Remove headers MIME e retorna apenas a assinatura DER
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
     * Calcula estatísticas do período
     */
    private function calculateStatistics(Tenant $tenant, string $startDate, string $endDate): array
    {
        $stats = DB::table('time_entries')
            ->join('employees', 'time_entries.employee_id', '=', 'employees.id')
            ->where('employees.tenant_id', $tenant->id)
            ->whereBetween('time_entries.date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(DISTINCT time_entries.employee_id) as total_employees,
                COUNT(time_entries.id) as total_entries,
                SUM(CASE WHEN time_entries.has_adjustment = 1 THEN 1 ELSE 0 END) as total_adjustments,
                SUM(time_entries.total_minutes) as total_minutes_worked
            ')
            ->first();

        return [
            'total_employees' => $stats->total_employees ?? 0,
            'total_entries' => $stats->total_entries ?? 0,
            'total_adjustments' => $stats->total_adjustments ?? 0,
            'total_hours_worked' => round(($stats->total_minutes_worked ?? 0) / 60, 2),
        ];
    }

    /**
     * Gera nome do arquivo AFD
     */
    private function generateFileName(Tenant $tenant, string $startDate, string $endDate): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $tenant->cnpj ?? '00000000000000');
        $start = Carbon::parse($startDate)->format('Ymd');
        $end = Carbon::parse($endDate)->format('Ymd');

        return "AFD_{$cnpj}_{$start}_{$end}.txt";
    }
}
