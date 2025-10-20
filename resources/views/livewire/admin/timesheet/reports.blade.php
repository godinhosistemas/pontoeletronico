<?php

use Livewire\Volt\Component;
use App\Models\TimeEntry;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $employee_id = '';
    public $date_from = '';
    public $date_to = '';
    public $report_type = 'summary'; // summary, detailed, monthly

    public function mount()
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }

    public function generateReport()
    {
        $this->dispatch('report-generated');
    }

    public function exportPdf()
    {
        session()->flash('info', 'Função de exportação PDF será implementada em breve.');
    }

    public function exportExcel()
    {
        session()->flash('info', 'Função de exportação Excel será implementada em breve.');
    }

    public function with()
    {
        $user = auth()->user();

        // Busca funcionários do tenant
        $employees = Employee::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Constrói query base
        $query = TimeEntry::with('employee')
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'approved');

        // Filtros
        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }

        if ($this->date_from) {
            $query->where('date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->where('date', '<=', $this->date_to);
        }

        $entries = $query->orderBy('date', 'desc')->get();

        // Calcula resumo
        $summary = [
            'total_days' => $entries->count(),
            'total_hours' => $entries->sum('total_hours'),
            'avg_hours' => $entries->avg('total_hours'),
            'employees_count' => $entries->pluck('employee_id')->unique()->count(),
        ];

        // Agrupa por funcionário
        $byEmployee = $entries->groupBy('employee_id')->map(function($employeeEntries) {
            return [
                'employee' => $employeeEntries->first()->employee,
                'total_days' => $employeeEntries->count(),
                'total_hours' => $employeeEntries->sum('total_hours'),
                'avg_hours' => $employeeEntries->avg('total_hours'),
                'entries' => $employeeEntries,
            ];
        });

        // Agrupa por dia
        $byDay = $entries->groupBy('date')->map(function($dayEntries, $date) {
            return [
                'date' => $date,
                'count' => $dayEntries->count(),
                'total_hours' => $dayEntries->sum('total_hours'),
                'entries' => $dayEntries,
            ];
        });

        return [
            'employees' => $employees,
            'entries' => $entries,
            'summary' => $summary,
            'byEmployee' => $byEmployee,
            'byDay' => $byDay,
        ];
    }
}; ?>

<div>
    @section('page-title', 'Relatórios de Ponto')

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Filtros do Relatório</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Funcionário</label>
                <select wire:model.live="employee_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos os Funcionários</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                <input wire:model.live="date_from" type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                <input wire:model.live="date_to" type="date"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Relatório</label>
                <select wire:model.live="report_type"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="summary">Resumo</option>
                    <option value="detailed">Detalhado</option>
                    <option value="by_employee">Por Funcionário</option>
                </select>
            </div>
        </div>

        <div class="flex gap-3">
            <button wire:click="exportPdf"
                class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 font-semibold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Exportar PDF
            </button>
            <button wire:click="exportExcel"
                class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 font-semibold shadow-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar Excel
            </button>
        </div>
    </div>

    <!-- Resumo Geral -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-600 font-medium">Total de Dias</p>
                    <p class="text-3xl font-bold text-blue-700">{{ $summary['total_days'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Total de Horas</p>
                    <p class="text-3xl font-bold text-green-700">{{ number_format($summary['total_hours'], 2) }}h</p>
                </div>
                <div class="w-12 h-12 bg-green-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-600 font-medium">Média por Dia</p>
                    <p class="text-3xl font-bold text-purple-700">{{ number_format($summary['avg_hours'], 2) }}h</p>
                </div>
                <div class="w-12 h-12 bg-purple-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-600 font-medium">Funcionários</p>
                    <p class="text-3xl font-bold text-orange-700">{{ $summary['employees_count'] }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo do Relatório -->
    @if($report_type === 'summary' || $report_type === 'detailed')
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                {{ $report_type === 'summary' ? 'Resumo Detalhado' : 'Relatório Detalhado' }}
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Data</th>
                        <th scope="col" class="px-6 py-3">Funcionário</th>
                        <th scope="col" class="px-6 py-3">Entrada</th>
                        <th scope="col" class="px-6 py-3">Saída</th>
                        <th scope="col" class="px-6 py-3">Almoço</th>
                        <th scope="col" class="px-6 py-3">Total Horas</th>
                        <th scope="col" class="px-6 py-3">Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($entry->date)->locale('pt_BR')->isoFormat('dddd') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $entry->employee->name }}</div>
                            <div class="text-xs text-gray-500">{{ $entry->employee->registration_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-green-600">{{ $entry->clock_in ?? '--:--' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-red-600">{{ $entry->clock_out ?? '--:--' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($entry->lunch_start && $entry->lunch_end)
                            <span class="text-xs">{{ $entry->lunch_start }} - {{ $entry->lunch_end }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($entry->total_hours)
                            <span class="font-bold text-blue-600">{{ number_format($entry->total_hours, 2) }}h</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full capitalize
                                {{ $entry->type === 'normal' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $entry->type === 'overtime' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $entry->type === 'absence' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $entry->type === 'holiday' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $entry->type === 'vacation' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ $entry->type }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Nenhum registro encontrado para o período selecionado
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Relatório Por Funcionário -->
    @if($report_type === 'by_employee')
    <div class="space-y-4">
        @forelse($byEmployee as $data)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $data['employee']->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $data['employee']->position ?? 'Cargo não definido' }} - Matrícula: {{ $data['employee']->registration_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total de Dias</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $data['total_days'] }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Total de Horas</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($data['total_hours'], 2) }}h</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Média por Dia</p>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($data['avg_hours'], 2) }}h</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <p class="text-sm text-gray-600">Dias Trabalhados</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $data['total_days'] }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Registros Detalhados</h4>
                    <div class="space-y-2">
                        @foreach($data['entries'] as $entry)
                        <div class="flex justify-between items-center py-2 px-3 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <div class="flex items-center gap-4">
                                <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</span>
                                <span class="text-sm text-green-600">{{ $entry->clock_in }}</span>
                                <span class="text-gray-400">→</span>
                                <span class="text-sm text-red-600">{{ $entry->clock_out ?? '--:--' }}</span>
                            </div>
                            <span class="font-bold text-blue-600">{{ number_format($entry->total_hours ?? 0, 2) }}h</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl shadow-lg p-8 text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <p class="text-lg">Nenhum dado encontrado para o período selecionado</p>
        </div>
        @endforelse
    </div>
    @endif
</div>
