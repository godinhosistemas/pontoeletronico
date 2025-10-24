<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\TimeEntryFile;
use App\Models\Employee;
use App\Services\AFDService;
use App\Services\AEJService;

new class extends Component {
    use WithPagination;

    public $fileTypeFilter = '';
    public $employeeFilter = '';
    public $periodStart = '';
    public $periodEnd = '';

    // Modal de geração AFD
    public $showAFDModal = false;
    public $afd_period_start = '';
    public $afd_period_end = '';

    // Modal de geração AEJ
    public $showAEJModal = false;
    public $aej_employee_id = '';
    public $aej_period_start = '';
    public $aej_period_end = '';

    // Modal de geração AEJ em lote
    public $showBulkAEJModal = false;
    public $bulk_period_start = '';
    public $bulk_period_end = '';

    public function mount()
    {
        // Define período padrão como mês atual
        $this->afd_period_start = now()->startOfMonth()->format('Y-m-d');
        $this->afd_period_end = now()->endOfMonth()->format('Y-m-d');
        $this->aej_period_start = now()->startOfMonth()->format('Y-m-d');
        $this->aej_period_end = now()->endOfMonth()->format('Y-m-d');
        $this->bulk_period_start = now()->startOfMonth()->format('Y-m-d');
        $this->bulk_period_end = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingFileTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingEmployeeFilter()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $tenant = auth()->user()->tenant;

        $query = TimeEntryFile::where('tenant_id', $tenant->id)
            ->with(['generatedBy', 'employee'])
            ->orderBy('created_at', 'desc');

        if ($this->fileTypeFilter) {
            $query->where('file_type', $this->fileTypeFilter);
        }

        if ($this->employeeFilter) {
            $query->where('employee_id', $this->employeeFilter);
        }

        if ($this->periodStart) {
            $query->where('period_start', '>=', $this->periodStart);
        }

        if ($this->periodEnd) {
            $query->where('period_end', '<=', $this->periodEnd);
        }

        $files = $query->paginate(15);

        $employees = Employee::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        $hasCertificate = $tenant->certificate_active;

        return [
            'files' => $files,
            'employees' => $employees,
            'hasCertificate' => $hasCertificate,
        ];
    }

    public function generateAFD()
    {
        $this->validate([
            'afd_period_start' => 'required|date',
            'afd_period_end' => 'required|date|after_or_equal:afd_period_start',
        ]);

        try {
            $afdService = app(AFDService::class);
            $tenant = auth()->user()->tenant;

            $file = $afdService->generateAFD(
                $tenant,
                $this->afd_period_start,
                $this->afd_period_end,
                auth()->id()
            );

            if (!$file) {
                session()->flash('error', 'Não foi possível gerar o arquivo AFD. Verifique se existem marcações no período.');
                return;
            }

            session()->flash('success', 'Arquivo AFD gerado com sucesso!');
            $this->showAFDModal = false;

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AFD: ' . $e->getMessage());
            session()->flash('error', 'Erro ao gerar arquivo AFD: ' . $e->getMessage());
        }
    }

    public function generateAEJ()
    {
        $this->validate([
            'aej_employee_id' => 'required|exists:employees,id',
            'aej_period_start' => 'required|date',
            'aej_period_end' => 'required|date|after_or_equal:aej_period_start',
        ]);

        try {
            $aejService = app(AEJService::class);
            $employee = Employee::findOrFail($this->aej_employee_id);

            $file = $aejService->generateAEJ(
                $employee,
                $this->aej_period_start,
                $this->aej_period_end,
                auth()->id()
            );

            if (!$file) {
                session()->flash('error', 'Não foi possível gerar o arquivo AEJ. Verifique se existem marcações no período.');
                return;
            }

            session()->flash('success', 'Arquivo AEJ gerado com sucesso!');
            $this->showAEJModal = false;

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AEJ: ' . $e->getMessage());
            session()->flash('error', 'Erro ao gerar arquivo AEJ: ' . $e->getMessage());
        }
    }

    public function generateBulkAEJ()
    {
        $this->validate([
            'bulk_period_start' => 'required|date',
            'bulk_period_end' => 'required|date|after_or_equal:bulk_period_start',
        ]);

        try {
            $aejService = app(AEJService::class);
            $tenant = auth()->user()->tenant;

            $files = $aejService->generateBulkAEJ(
                $tenant,
                $this->bulk_period_start,
                $this->bulk_period_end,
                auth()->id()
            );

            $count = count($files);

            if ($count === 0) {
                session()->flash('warning', 'Nenhum arquivo AEJ foi gerado. Verifique se existem marcações no período.');
                return;
            }

            session()->flash('success', "$count arquivo(s) AEJ gerado(s) com sucesso!");
            $this->showBulkAEJModal = false;

        } catch (\Exception $e) {
            \Log::error('Erro ao gerar AEJs em lote: ' . $e->getMessage());
            session()->flash('error', 'Erro ao gerar arquivos AEJ: ' . $e->getMessage());
        }
    }

    public function deleteFile($id)
    {
        try {
            $file = TimeEntryFile::findOrFail($id);

            // Verificar permissão
            if ($file->tenant_id !== auth()->user()->tenant_id) {
                session()->flash('error', 'Você não tem permissão para excluir este arquivo.');
                return;
            }

            $file->delete();
            session()->flash('success', 'Arquivo excluído com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro ao deletar arquivo: ' . $e->getMessage());
            session()->flash('error', 'Erro ao excluir arquivo: ' . $e->getMessage());
        }
    }
}; ?>

<div>
    @section('page-title', 'Arquivos Legais - Portaria MTP 671/2021')

    <!-- Mensagens de Sucesso/Erro/Warning -->
    @if (session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-green-800 font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if (session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="mb-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-red-800 font-medium">{{ session('error') }}</span>
    </div>
    @endif

    @if (session()->has('warning'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="mb-6 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <span class="text-yellow-800 font-medium">{{ session('warning') }}</span>
    </div>
    @endif

    <!-- Alerta de Certificado -->
    @if(!$hasCertificate)
    <div class="mb-6 bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-xl p-5">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-orange-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1">
                <h3 class="font-semibold text-orange-900 mb-1">Certificado Digital Não Configurado</h3>
                <p class="text-sm text-orange-800">Os arquivos serão gerados sem assinatura digital. Para conformidade legal completa, configure um certificado ICP-Brasil.</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Card de Informações -->
    <div class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-lg border border-blue-100 p-6">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-white rounded-xl shadow-md">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Sobre os Arquivos Legais</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-semibold text-blue-900 mb-1">AFD - Arquivo Fonte de Dados</h3>
                        <p class="text-sm text-gray-700">Contém os dados <strong>brutos</strong> das marcações de ponto. Deve ser gerado por período e entregue quando solicitado pela fiscalização.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-indigo-900 mb-1">AEJ - Arquivo Eletrônico de Jornada</h3>
                        <p class="text-sm text-gray-700">Contém os dados <strong>processados</strong> com totalizações, horas extras e faltosas. Gerado por funcionário.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botões de Ação -->
    <div class="mb-6 flex flex-wrap gap-3">
        <button wire:click="$set('showAFDModal', true)"
            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Gerar AFD
        </button>

        <button wire:click="$set('showAEJModal', true)"
            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            Gerar AEJ (Individual)
        </button>

        <button wire:click="$set('showBulkAEJModal', true)"
            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Gerar AEJ (Todos)
        </button>
    </div>

    <!-- Filtros -->
    <div class="mb-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Arquivo</label>
                <select wire:model.live="fileTypeFilter"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                    <option value="">Todos</option>
                    <option value="AFD">AFD</option>
                    <option value="AEJ">AEJ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Funcionário</label>
                <select wire:model.live="employeeFilter"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                    <option value="">Todos</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Período Início</label>
                <input type="date" wire:model.live="periodStart"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Período Fim</label>
                <input type="date" wire:model.live="periodEnd"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
            </div>
        </div>
    </div>

    <!-- Tabela de Arquivos -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs font-semibold uppercase bg-gradient-to-r from-gray-50 to-slate-50 text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">Tipo</th>
                        <th scope="col" class="px-6 py-4">Período</th>
                        <th scope="col" class="px-6 py-4">Funcionário</th>
                        <th scope="col" class="px-6 py-4">Registros</th>
                        <th scope="col" class="px-6 py-4">Tamanho</th>
                        <th scope="col" class="px-6 py-4">Assinatura</th>
                        <th scope="col" class="px-6 py-4">Gerado em</th>
                        <th scope="col" class="px-6 py-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                    <tr class="bg-white border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                        <td class="px-6 py-4">
                            <span class="px-3 py-1.5 bg-gradient-to-r {{ $file->file_type == 'AFD' ? 'from-blue-100 to-blue-200 text-blue-700' : 'from-indigo-100 to-indigo-200 text-indigo-700' }} rounded-lg text-xs font-bold">
                                {{ $file->file_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-700">
                            <div class="text-sm font-medium">{{ $file->period_start->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">até {{ $file->period_end->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($file->employee)
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold">
                                        {{ $file->employee->initials }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $file->employee->name }}</span>
                                </div>
                            @else
                                <span class="text-sm text-gray-400 italic">Todos os funcionários</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700 font-mono text-sm">{{ number_format($file->total_records, 0, ',', '.') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-600 text-sm">{{ number_format($file->file_size / 1024, 1) }} KB</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($file->is_signed)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs font-semibold text-green-700">Assinado</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-xs font-semibold text-amber-700">Sem assinatura</span>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">{{ $file->created_at->format('d/m/Y H:i') }}</div>
                            @if($file->generatedBy)
                                <div class="text-xs text-gray-500">por {{ $file->generatedBy->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.legal-files.download', $file) }}"
                                   class="p-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors"
                                   title="Download .txt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </a>

                                @if($file->is_signed)
                                    <a href="{{ route('admin.legal-files.download-bundle', $file) }}"
                                       class="p-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors"
                                       title="Download ZIP (txt + p7s)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>
                                @endif

                                <button wire:click="deleteFile({{ $file->id }})"
                                        wire:confirm="Tem certeza que deseja excluir este arquivo?"
                                        class="p-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors"
                                        title="Excluir">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">Nenhum arquivo gerado ainda</p>
                            <p class="text-gray-400 text-sm mt-1">Clique em um dos botões acima para gerar AFD ou AEJ</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($files->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $files->links() }}
        </div>
        @endif
    </div>

    <!-- Modal AFD -->
    @if($showAFDModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Gerar Arquivo AFD</h3>
                <button wire:click="$set('showAFDModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" wire:model="afd_period_start"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" wire:model="afd_period_end"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex gap-3 pt-4">
                    <button wire:click="generateAFD"
                        class="flex-1 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                        Gerar AFD
                    </button>
                    <button wire:click="$set('showAFDModal', false)"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal AEJ -->
    @if($showAEJModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Gerar Arquivo AEJ</h3>
                <button wire:click="$set('showAEJModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Funcionário</label>
                    <select wire:model="aej_employee_id"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">Selecione...</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" wire:model="aej_period_start"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" wire:model="aej_period_end"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>

                <div class="flex gap-3 pt-4">
                    <button wire:click="generateAEJ"
                        class="flex-1 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                        Gerar AEJ
                    </button>
                    <button wire:click="$set('showAEJModal', false)"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Bulk AEJ -->
    @if($showBulkAEJModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Gerar AEJs em Lote</h3>
                <button wire:click="$set('showBulkAEJModal', false)" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 mb-4">
                <p class="text-sm text-purple-800">
                    Será gerado um arquivo AEJ para cada funcionário com marcações no período selecionado.
                </p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" wire:model="bulk_period_start"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" wire:model="bulk_period_end"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <div class="flex gap-3 pt-4">
                    <button wire:click="generateBulkAEJ"
                        class="flex-1 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 font-medium shadow-lg hover:shadow-xl">
                        Gerar Todos
                    </button>
                    <button wire:click="$set('showBulkAEJModal', false)"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors font-medium">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
