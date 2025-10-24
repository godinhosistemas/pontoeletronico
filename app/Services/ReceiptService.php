<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\TimeEntryReceipt;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class ReceiptService
{
    /**
     * Gera um comprovante de registro de ponto
     */
    public function generateReceipt(
        TimeEntry $timeEntry,
        Employee $employee,
        string $action,
        array $additionalData = []
    ): TimeEntryReceipt {
        // Extrai o horário específico da ação
        $markedTime = $this->getActionTime($timeEntry, $action);

        // Cria o registro do comprovante
        $receipt = TimeEntryReceipt::create([
            'time_entry_id' => $timeEntry->id,
            'employee_id' => $employee->id,
            'tenant_id' => $employee->tenant_id,
            'action' => $action,
            'marked_at' => $timeEntry->date->format('Y-m-d') . ' ' . $markedTime,
            'ip_address' => $additionalData['ip_address'] ?? null,
            'gps_latitude' => $additionalData['gps_latitude'] ?? null,
            'gps_longitude' => $additionalData['gps_longitude'] ?? null,
            'gps_accuracy' => $additionalData['gps_accuracy'] ?? null,
            'photo_path' => $additionalData['photo_path'] ?? null,
        ]);

        // Gera o PDF do comprovante
        $this->generatePDF($receipt);

        return $receipt;
    }

    /**
     * Gera o PDF do comprovante
     */
    public function generatePDF(TimeEntryReceipt $receipt): string
    {
        $receipt->load(['employee', 'tenant', 'timeEntry']);

        // Dados para o template
        $data = [
            'receipt' => $receipt,
            'employee' => $receipt->employee,
            'tenant' => $receipt->tenant,
            'markedAt' => $receipt->marked_at,
            'authenticatorCode' => $receipt->authenticator_code,
        ];

        // Gera o PDF
        $pdf = Pdf::loadView('receipts.time-entry-receipt', $data);
        $pdf->setPaper('a4', 'portrait');

        // Caminho do arquivo
        $fileName = sprintf(
            'receipt_%s_%s_%s.pdf',
            $receipt->employee_id,
            $receipt->marked_at->format('Y-m-d_H-i-s'),
            $receipt->action
        );

        $path = "receipts/{$receipt->tenant_id}/{$fileName}";

        // Salva o PDF
        Storage::put($path, $pdf->output());

        // Atualiza o registro com o path do PDF
        $receipt->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Obtém o horário da ação específica
     */
    private function getActionTime(TimeEntry $timeEntry, string $action): string
    {
        return match($action) {
            'clock_in' => $timeEntry->clock_in,
            'clock_out' => $timeEntry->clock_out,
            'lunch_start' => $timeEntry->lunch_start,
            'lunch_end' => $timeEntry->lunch_end,
            default => now()->format('H:i:s'),
        };
    }

    /**
     * Busca comprovantes do funcionário no mês atual
     */
    public function getEmployeeCurrentMonthReceipts(int $employeeId)
    {
        return TimeEntryReceipt::forEmployee($employeeId)
            ->currentMonth()
            ->with(['timeEntry', 'tenant'])
            ->orderBy('marked_at', 'desc')
            ->get();
    }

    /**
     * Busca comprovante por código autenticador
     */
    public function getByAuthenticator(string $code): ?TimeEntryReceipt
    {
        return TimeEntryReceipt::byAuthenticator($code)
            ->with(['employee', 'tenant', 'timeEntry'])
            ->first();
    }

    /**
     * Verifica e limpa comprovantes expirados
     */
    public function cleanExpiredReceipts(): int
    {
        $expiredReceipts = TimeEntryReceipt::where('available_until', '<', now())->get();

        $count = 0;
        foreach ($expiredReceipts as $receipt) {
            // Deleta o arquivo PDF
            if ($receipt->pdf_path && Storage::exists($receipt->pdf_path)) {
                Storage::delete($receipt->pdf_path);
            }

            // Deleta o registro (opcional - pode manter por auditoria)
            // $receipt->delete();

            $count++;
        }

        return $count;
    }

    /**
     * Renova disponibilidade do comprovante (mais 48h)
     */
    public function renewReceipt(TimeEntryReceipt $receipt): TimeEntryReceipt
    {
        $receipt->update([
            'available_until' => now()->addHours(48)
        ]);

        return $receipt->fresh();
    }
}
