<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class TimesheetReportController extends Controller
{
    /**
     * Exporta relatório em Excel (CSV)
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        // Busca dados filtrados
        $query = TimeEntry::with(['employee', 'adjuster'])
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'approved');

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $entries = $query->orderBy('date', 'desc')->get();

        // Prepara dados para CSV
        $filename = 'relatorio_ponto_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($entries) {
            $file = fopen('php://output', 'w');

            // BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeçalhos
            fputcsv($file, [
                'Data',
                'Dia da Semana',
                'Funcionário',
                'Matrícula',
                'Entrada',
                'Saída',
                'Início Almoço',
                'Fim Almoço',
                'Total Horas',
                'Tipo',
                'Status',
                'Ajustado',
                'Ajustado Por',
                'Motivo do Ajuste'
            ], ';');

            // Dados
            foreach ($entries as $entry) {
                // Determina os horários a exibir (ajustados ou originais)
                $clockIn = $entry->has_adjustment ? ($entry->formatTime('adjusted_clock_in') ?? '--:--') : ($entry->clock_in ?? '--:--');
                $clockOut = $entry->has_adjustment ? ($entry->formatTime('adjusted_clock_out') ?? '--:--') : ($entry->clock_out ?? '--:--');
                $lunchStart = $entry->has_adjustment ? ($entry->formatTime('adjusted_lunch_start') ?? '--:--') : ($entry->lunch_start ?? '--:--');
                $lunchEnd = $entry->has_adjustment ? ($entry->formatTime('adjusted_lunch_end') ?? '--:--') : ($entry->lunch_end ?? '--:--');

                fputcsv($file, [
                    Carbon::parse($entry->date)->format('d/m/Y'),
                    Carbon::parse($entry->date)->locale('pt_BR')->isoFormat('dddd'),
                    $entry->employee->name,
                    $entry->employee->registration_number,
                    $clockIn,
                    $clockOut,
                    $lunchStart,
                    $lunchEnd,
                    $entry->total_hours ? number_format($entry->total_hours, 2, ',', '.') . 'h' : '-',
                    $this->getTypeText($entry->type),
                    $this->getStatusText($entry->status),
                    $entry->has_adjustment ? 'Sim' : 'Não',
                    $entry->has_adjustment ? ($entry->adjuster->name ?? 'Sistema') : '-',
                    $entry->has_adjustment ? $entry->adjustment_reason : '-'
                ], ';');
            }

            // Linha de resumo
            fputcsv($file, [], ';');
            fputcsv($file, ['RESUMO'], ';');
            fputcsv($file, ['Total de Registros', $entries->count()], ';');
            fputcsv($file, ['Total de Horas', number_format($entries->sum('total_hours'), 2, ',', '.') . 'h'], ';');
            fputcsv($file, ['Média de Horas', number_format($entries->avg('total_hours'), 2, ',', '.') . 'h'], ';');

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Exporta relatório em PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();

        // Busca dados filtrados
        $query = TimeEntry::with(['employee', 'adjuster'])
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'approved');

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $entries = $query->orderBy('date', 'desc')->get();

        // Estatísticas
        $summary = [
            'total_days' => $entries->count(),
            'total_hours' => $entries->sum('total_hours'),
            'avg_hours' => $entries->avg('total_hours'),
            'employees_count' => $entries->pluck('employee_id')->unique()->count(),
        ];

        // Períodos
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->format('d/m/Y') : 'Início';
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->format('d/m/Y') : 'Hoje';

        // Retorna view HTML que pode ser impressa como PDF
        return view('reports.timesheet-pdf', [
            'entries' => $entries,
            'summary' => $summary,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'tenant' => $user->tenant,
            'generatedAt' => now()->format('d/m/Y H:i:s')
        ]);
    }

    private function getTypeText($type)
    {
        return match($type) {
            'normal' => 'Normal',
            'overtime' => 'Hora Extra',
            'absence' => 'Falta',
            'holiday' => 'Feriado',
            'vacation' => 'Férias',
            default => 'Indefinido',
        };
    }

    private function getStatusText($status)
    {
        return match($status) {
            'pending' => 'Pendente',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            default => 'Indefinido',
        };
    }

    /**
     * Exporta folha espelho de ponto mensal
     */
    public function exportTimesheetMirror(Request $request)
    {
        $user = auth()->user();

        // Validação
        if (!$request->employee_id) {
            return back()->with('error', 'Selecione um funcionário para gerar a folha espelho.');
        }

        if (!$request->date_from || !$request->date_to) {
            return back()->with('error', 'Selecione o período para gerar a folha espelho.');
        }

        // Busca funcionário com jornada
        $employee = Employee::with('workSchedule')->findOrFail($request->employee_id);

        // Busca TODOS os registros do período (não apenas aprovados)
        // Usa whereDate para comparar apenas a parte da data, ignorando hora/timezone
        $entries = TimeEntry::with('adjuster')
            ->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $request->date_from)
            ->whereDate('date', '<=', $request->date_to)
            ->orderBy('date')
            ->get();

        \Log::info('Folha Espelho - Dados:', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'total_entries' => $entries->count(),
            'entries_sample' => $entries->take(3)->map(function($e) {
                return [
                    'id' => $e->id,
                    'date' => $e->date,
                    'clock_in' => $e->formatted_clock_in,
                    'clock_out' => $e->formatted_clock_out,
                    'status' => $e->status,
                ];
            })
        ]);

        // Agrupa por data
        $entriesByDate = $entries->groupBy(function($entry) {
            return $entry->date instanceof \Carbon\Carbon
                ? $entry->date->format('Y-m-d')
                : $entry->date;
        });

        // Gera todos os dias do período
        $dateFrom = Carbon::parse($request->date_from);
        $dateTo = Carbon::parse($request->date_to);
        $allDays = [];

        for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dayData = [
                'date' => $date->copy(),
                'day_number' => $date->day,
                'day_name' => $date->locale('pt_BR')->isoFormat('dddd'),
                'entries' => $entriesByDate->get($dateKey, collect()),
            ];

            // Calcula totais do dia
            $dayEntries = $dayData['entries'];
            $dayData['total_hours'] = $dayEntries->sum('total_hours');
            $dayData['has_entries'] = $dayEntries->isNotEmpty();

            $allDays[] = $dayData;
        }

        // Calcula totalizadores
        $totalHours = $entries->sum('total_hours');
        $totalDays = $entries->groupBy('date')->count();

        // Obtém dados da jornada de trabalho
        $workSchedule = $employee->workSchedule;

        // Calcula jornada esperada baseado na jornada vinculada ao funcionário
        if ($workSchedule) {
            // Usa a jornada configurada
            $expectedWeeklyHours = $workSchedule->weekly_hours;
            $expectedDailyHours = $workSchedule->daily_hours;

            // Obtém dias da semana que trabalha
            $workingDays = $workSchedule->getWorkingDays();

            // Conta quantos dias úteis de trabalho existem no período
            $workDays = collect($allDays)->filter(function($day) use ($workingDays) {
                $dayName = strtolower($day['date']->locale('en')->dayName);
                return in_array($dayName, $workingDays);
            })->count();
        } else {
            // Fallback: jornada padrão 44h semanais (segunda a sexta)
            $expectedWeeklyHours = 44;
            $expectedDailyHours = 8.8; // 44h / 5 dias

            $workDays = collect($allDays)->filter(function($day) {
                return !in_array($day['date']->dayOfWeek, [0, 6]); // Não conta sábado e domingo
            })->count();
        }

        $expectedHours = $workDays * $expectedDailyHours;
        $overtimeHours = max(0, $totalHours - $expectedHours);
        $missingHours = max(0, $expectedHours - $totalHours);

        // Código autenticador único
        $authenticator = strtoupper(substr(md5($employee->id . $dateFrom->format('Y-m') . now()), 0, 22));

        return view('reports.timesheet-mirror', [
            'employee' => $employee,
            'tenant' => $user->tenant,
            'period' => $dateFrom->locale('pt_BR')->isoFormat('MMMM/YYYY'),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'allDays' => $allDays,
            'totalHours' => $totalHours,
            'totalDays' => $totalDays,
            'overtimeHours' => $overtimeHours,
            'missingHours' => $missingHours,
            'expectedHours' => $expectedHours,
            'authenticator' => $authenticator,
            'generatedBy' => $user->name,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'workSchedule' => $workSchedule,
            'expectedDailyHours' => $expectedDailyHours,
            'expectedWeeklyHours' => $expectedWeeklyHours ?? 44,
        ]);
    }
}
