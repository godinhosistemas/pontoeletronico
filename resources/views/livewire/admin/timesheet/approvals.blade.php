<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\TimeEntry;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $status_filter = 'pending';
    public $date_from = '';
    public $date_to = '';

    // Modal de detalhes
    public $showDetailsModal = false;
    public $selectedEntry;

    protected $queryString = ['search', 'status_filter'];

    public function mount()
    {
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function viewDetails($entryId)
    {
        $this->selectedEntry = TimeEntry::with(['employee', 'approvedBy'])->find($entryId);
        $this->showDetailsModal = true;
    }

    public function closeModal()
    {
        $this->showDetailsModal = false;
        $this->selectedEntry = null;
    }

    public function approve($entryId)
    {
        try {
            $entry = TimeEntry::findOrFail($entryId);

            // Verifica se pertence ao mesmo tenant
            if ($entry->tenant_id !== auth()->user()->tenant_id && !auth()->user()->isSuperAdmin()) {
                session()->flash('error', 'Você não tem permissão para aprovar este registro.');
                return;
            }

            $entry->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            session()->flash('success', 'Registro aprovado com sucesso!');
            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao aprovar registro: ' . $e->getMessage());
        }
    }

    public function reject($entryId)
    {
        try {
            $entry = TimeEntry::findOrFail($entryId);

            // Verifica se pertence ao mesmo tenant
            if ($entry->tenant_id !== auth()->user()->tenant_id && !auth()->user()->isSuperAdmin()) {
                session()->flash('error', 'Você não tem permissão para rejeitar este registro.');
                return;
            }

            $entry->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            session()->flash('success', 'Registro rejeitado.');
            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao rejeitar registro: ' . $e->getMessage());
        }
    }

    public function bulkApprove()
    {
        try {
            $query = TimeEntry::where('status', 'pending')
                ->where('tenant_id', auth()->user()->tenant_id);

            if ($this->date_from) {
                $query->where('date', '>=', $this->date_from);
            }
            if ($this->date_to) {
                $query->where('date', '<=', $this->date_to);
            }

            $count = $query->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            session()->flash('success', "{$count} registros aprovados em lote!");
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao aprovar registros: ' . $e->getMessage());
        }
    }

    public function with()
    {
        $query = TimeEntry::with('employee')
            ->where('tenant_id', auth()->user()->tenant_id);

        // Filtro de status
        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        // Filtro de data
        if ($this->date_from) {
            $query->where('date', '>=', $this->date_from);
        }
        if ($this->date_to) {
            $query->where('date', '<=', $this->date_to);
        }

        // Busca por funcionário
        if ($this->search) {
            $query->whereHas('employee', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('registration_number', 'like', '%' . $this->search . '%');
            });
        }

        $entries = $query->latest('date')->latest('clock_in')->paginate(15);

        // Estatísticas
        $stats = [
            'pending' => TimeEntry::where('tenant_id', auth()->user()->tenant_id)
                ->where('status', 'pending')
                ->whereBetween('date', [$this->date_from, $this->date_to])
                ->count(),
            'approved' => TimeEntry::where('tenant_id', auth()->user()->tenant_id)
                ->where('status', 'approved')
                ->whereBetween('date', [$this->date_from, $this->date_to])
                ->count(),
            'rejected' => TimeEntry::where('tenant_id', auth()->user()->tenant_id)
                ->where('status', 'rejected')
                ->whereBetween('date', [$this->date_from, $this->date_to])
                ->count(),
        ];

        return [
            'entries' => $entries,
            'stats' => $stats,
        ];
    }
}; ?>

<div>
    @section('page-title', 'Aprovação de Pontos')

    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-yellow-600 font-medium">Pendentes</p>
                    <p class="text-3xl font-bold text-yellow-700">{{ $stats['pending'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-600 font-medium">Aprovados</p>
                    <p class="text-3xl font-bold text-green-700">{{ $stats['approved'] }}</p>
                </div>
                <div class="w-12 h-12 bg-green-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-red-600 font-medium">Rejeitados</p>
                    <p class="text-3xl font-bold text-red-700">{{ $stats['rejected'] }}</p>
                </div>
                <div class="w-12 h-12 bg-red-200 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Funcionário</label>
                <input wire:model.live="search" type="text" placeholder="Nome ou matrícula..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select wire:model.live="status_filter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="pending">Pendentes</option>
                    <option value="approved">Aprovados</option>
                    <option value="rejected">Rejeitados</option>
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

            <div class="flex items-end">
                <button wire:click="bulkApprove"
                        wire:confirm="Tem certeza que deseja aprovar todos os registros pendentes do período?"
                        class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-lg hover:from-green-600 hover:to-emerald-600 font-semibold shadow-lg">
                    Aprovar em Lote
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Registros -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Funcionário</th>
                        <th scope="col" class="px-6 py-3">Data</th>
                        <th scope="col" class="px-6 py-3">Entrada</th>
                        <th scope="col" class="px-6 py-3">Saída</th>
                        <th scope="col" class="px-6 py-3">Total Horas</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $entry->employee->name }}</div>
                            <div class="text-xs text-gray-500">{{ $entry->employee->registration_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($entry->date)->locale('pt_BR')->isoFormat('dddd') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-green-600">{{ $entry->clock_in ?? '--:--' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-red-600">{{ $entry->clock_out ?? '--:--' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($entry->total_hours)
                            <span class="font-bold text-blue-600">{{ number_format($entry->total_hours, 2) }}h</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $entry->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $entry->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $entry->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $entry->status === 'approved' ? 'Aprovado' : '' }}
                                {{ $entry->status === 'pending' ? 'Pendente' : '' }}
                                {{ $entry->status === 'rejected' ? 'Rejeitado' : '' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="viewDetails({{ $entry->id }})"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    Detalhes
                                </button>
                                @if($entry->status === 'pending')
                                <button wire:click="approve({{ $entry->id }})"
                                    class="text-green-600 hover:text-green-800 font-medium">
                                    Aprovar
                                </button>
                                <button wire:click="reject({{ $entry->id }})"
                                    class="text-red-600 hover:text-red-800 font-medium">
                                    Rejeitar
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Nenhum registro encontrado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $entries->links() }}
        </div>
    </div>

    <!-- Modal de Detalhes -->
    @if($showDetailsModal && $selectedEntry)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Detalhes do Registro</h3>

                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Funcionário</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $selectedEntry->employee->name }}</p>
                            <p class="text-sm text-gray-600">Matrícula: {{ $selectedEntry->employee->registration_number }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Data</p>
                                <p class="font-semibold">{{ \Carbon\Carbon::parse($selectedEntry->date)->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Tipo</p>
                                <p class="font-semibold capitalize">{{ $selectedEntry->type }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Entrada</p>
                                <p class="text-lg font-bold text-green-600">{{ $selectedEntry->clock_in ?? '--:--' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Saída</p>
                                <p class="text-lg font-bold text-red-600">{{ $selectedEntry->clock_out ?? '--:--' }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Início Almoço</p>
                                <p class="font-semibold">{{ $selectedEntry->lunch_start ?? '--:--' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Fim Almoço</p>
                                <p class="font-semibold">{{ $selectedEntry->lunch_end ?? '--:--' }}</p>
                            </div>
                        </div>

                        @if($selectedEntry->total_hours)
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Total Trabalhado</p>
                            <p class="text-2xl font-bold text-blue-600">{{ number_format($selectedEntry->total_hours, 2) }}h</p>
                        </div>
                        @endif

                        @if($selectedEntry->notes)
                        <div>
                            <p class="text-sm text-gray-600">Observações</p>
                            <p class="text-gray-900">{{ $selectedEntry->notes }}</p>
                        </div>
                        @endif

                        @if($selectedEntry->ip_address)
                        <div>
                            <p class="text-sm text-gray-600">IP de Registro</p>
                            <p class="text-gray-900 font-mono text-sm">{{ $selectedEntry->ip_address }}</p>
                        </div>
                        @endif

                        @if($selectedEntry->approved_by)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600">Aprovado por</p>
                            <p class="font-semibold">{{ $selectedEntry->approvedBy->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $selectedEntry->approved_at ? \Carbon\Carbon::parse($selectedEntry->approved_at)->format('d/m/Y H:i') : '' }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    @if($selectedEntry->status === 'pending')
                    <button wire:click="approve({{ $selectedEntry->id }})"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 sm:w-auto sm:text-sm">
                        Aprovar
                    </button>
                    <button wire:click="reject({{ $selectedEntry->id }})"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:w-auto sm:text-sm">
                        Rejeitar
                    </button>
                    @endif
                    <button wire:click="closeModal"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
