<?php

use Livewire\Volt\Component;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Subscription;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    // Modal de Nova Fatura
    public $showCreateModal = false;
    public $tenant_id = '';
    public $subscription_id = null;
    public $due_date = '';
    public $issue_date = '';
    public $subtotal = 0;
    public $discount = 0;
    public $tax = 0;
    public $total = 0;
    public $description = '';
    public $items = [];
    public $payment_instructions = '';

    // Item atual sendo adicionado
    public $item_description = '';
    public $item_quantity = 1;
    public $item_unit_price = 0;

    public function mount()
    {
        $this->issue_date = now()->format('Y-m-d');
        $this->due_date = now()->addDays(10)->format('Y-m-d');
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->tenant_id = '';
        $this->subscription_id = null;
        $this->due_date = now()->addDays(10)->format('Y-m-d');
        $this->issue_date = now()->format('Y-m-d');
        $this->subtotal = 0;
        $this->discount = 0;
        $this->tax = 0;
        $this->total = 0;
        $this->description = '';
        $this->items = [];
        $this->payment_instructions = 'Pagamento pode ser realizado via Boleto ou PIX através do portal.';
        $this->resetItem();
    }

    public function resetItem()
    {
        $this->item_description = '';
        $this->item_quantity = 1;
        $this->item_unit_price = 0;
    }

    public function addItem()
    {
        $this->validate([
            'item_description' => 'required|min:3',
            'item_quantity' => 'required|numeric|min:1',
            'item_unit_price' => 'required|numeric|min:0',
        ], [
            'item_description.required' => 'Descrição é obrigatória',
            'item_description.min' => 'Descrição deve ter no mínimo 3 caracteres',
            'item_quantity.required' => 'Quantidade é obrigatória',
            'item_quantity.numeric' => 'Quantidade deve ser um número',
            'item_quantity.min' => 'Quantidade deve ser no mínimo 1',
            'item_unit_price.required' => 'Valor unitário é obrigatório',
            'item_unit_price.numeric' => 'Valor unitário deve ser um número',
            'item_unit_price.min' => 'Valor unitário deve ser maior que 0',
        ]);

        $itemTotal = $this->item_quantity * $this->item_unit_price;

        $this->items[] = [
            'description' => $this->item_description,
            'quantity' => $this->item_quantity,
            'unit_price' => $this->item_unit_price,
            'total' => $itemTotal,
        ];

        $this->calculateTotals();
        $this->resetItem();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items); // Reindexar array
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->subtotal = collect($this->items)->sum('total');
        $this->total = $this->subtotal - $this->discount + $this->tax;
    }

    public function updatedDiscount()
    {
        $this->calculateTotals();
    }

    public function updatedTax()
    {
        $this->calculateTotals();
    }

    public function createInvoice()
    {
        $this->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'due_date' => 'required|date',
            'issue_date' => 'required|date',
            'items' => 'required|array|min:1',
        ], [
            'tenant_id.required' => 'Selecione uma empresa',
            'tenant_id.exists' => 'Empresa não encontrada',
            'due_date.required' => 'Data de vencimento é obrigatória',
            'due_date.date' => 'Data de vencimento inválida',
            'issue_date.required' => 'Data de emissão é obrigatória',
            'issue_date.date' => 'Data de emissão inválida',
            'items.required' => 'Adicione pelo menos um item à fatura',
            'items.min' => 'Adicione pelo menos um item à fatura',
        ]);

        try {
            Invoice::create([
                'tenant_id' => $this->tenant_id,
                'subscription_id' => $this->subscription_id,
                'reference' => now()->format('Y-m') . '-manual',
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'total' => $this->total,
                'issue_date' => $this->issue_date,
                'due_date' => $this->due_date,
                'status' => 'pending',
                'items' => $this->items,
                'payment_instructions' => $this->payment_instructions ?: 'Pagamento pode ser realizado via Boleto ou PIX através do portal.',
                'notes' => 'Fatura criada manualmente pelo administrador',
            ]);

            $this->dispatch('invoice-created');
            session()->flash('success', 'Fatura criada com sucesso!');
            $this->closeCreateModal();
            $this->resetPage();

        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao criar fatura: ' . $e->getMessage());
        }
    }

    public function with(): array
    {
        $query = Invoice::with(['tenant', 'subscription.plan', 'approvedPayment']);

        // Filtro de busca
        if ($this->search) {
            $query->where(function($q) {
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhereHas('tenant', function($q2) {
                      $q2->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        // Filtro de status
        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        // Ordenação
        $query->orderBy($this->sortBy, $this->sortDirection);

        return [
            'invoices' => $query->paginate(15),
            'tenants' => Tenant::orderBy('name')->get(),
            'stats' => [
                'total' => Invoice::count(),
                'pending' => Invoice::pending()->count(),
                'overdue' => Invoice::overdue()->count(),
                'paid' => Invoice::paid()->count(),
                'total_pending_amount' => Invoice::pending()->sum('total'),
                'total_overdue_amount' => Invoice::overdue()->sum('total'),
            ],
        ];
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }
}; ?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Gerenciamento de Faturas
            </h1>
            <p class="text-gray-600 mt-1">Visualize e gerencie todas as faturas dos clientes</p>
        </div>
        <button wire:click="openCreateModal" type="button"
            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span wire:loading.remove wire:target="openCreateModal">Nova Fatura</span>
            <span wire:loading wire:target="openCreateModal">Abrindo...</span>
        </button>
    </div>

    <!-- Mensagens de Sucesso/Erro -->
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl">
            {{ session('error') }}
        </div>
    @endif

    <!-- Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Total de Faturas</p>
                    <p class="text-3xl font-bold">{{ $stats['total'] }}</p>
                </div>
                <svg class="w-12 h-12 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">Pendentes</p>
                    <p class="text-3xl font-bold">{{ $stats['pending'] }}</p>
                    <p class="text-xs text-yellow-100 mt-1">R$ {{ number_format($stats['total_pending_amount'], 2, ',', '.') }}</p>
                </div>
                <svg class="w-12 h-12 text-yellow-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">Vencidas</p>
                    <p class="text-3xl font-bold">{{ $stats['overdue'] }}</p>
                    <p class="text-xs text-red-100 mt-1">R$ {{ number_format($stats['total_overdue_amount'], 2, ',', '.') }}</p>
                </div>
                <svg class="w-12 h-12 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Pagas</p>
                    <p class="text-3xl font-bold">{{ $stats['paid'] }}</p>
                </div>
                <svg class="w-12 h-12 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filtros e Busca -->
    <div class="mb-6 bg-white rounded-xl shadow-lg p-4">
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Busca -->
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Buscar por número ou empresa..."
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- Filtros -->
            <div class="flex gap-2">
                <button wire:click="$set('statusFilter', 'all')"
                    class="px-4 py-2 rounded-lg transition-all {{ $statusFilter === 'all' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Todas
                </button>
                <button wire:click="$set('statusFilter', 'pending')"
                    class="px-4 py-2 rounded-lg transition-all {{ $statusFilter === 'pending' ? 'bg-gradient-to-r from-yellow-600 to-yellow-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Pendentes
                </button>
                <button wire:click="$set('statusFilter', 'overdue')"
                    class="px-4 py-2 rounded-lg transition-all {{ $statusFilter === 'overdue' ? 'bg-gradient-to-r from-red-600 to-red-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Vencidas
                </button>
                <button wire:click="$set('statusFilter', 'paid')"
                    class="px-4 py-2 rounded-lg transition-all {{ $statusFilter === 'paid' ? 'bg-gradient-to-r from-green-600 to-green-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    Pagas
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Faturas -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer" wire:click="sortBy('invoice_number')">
                            Número
                            @if($sortBy === 'invoice_number')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Plano</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer" wire:click="sortBy('due_date')">
                            Vencimento
                            @if($sortBy === 'due_date')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider cursor-pointer" wire:click="sortBy('total')">
                            Valor
                            @if($sortBy === 'total')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ $invoice->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice->tenant->name }}</div>
                                <div class="text-xs text-gray-500">{{ $invoice->tenant->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invoice->subscription->plan->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invoice->due_date->format('d/m/Y') }}</div>
                                @if($invoice->isOverdue())
                                    <div class="text-xs text-red-600 font-medium">
                                        Vencida há {{ $invoice->daysOverdue() }} dia(s)
                                    </div>
                                @elseif($invoice->isPending())
                                    @php $days = $invoice->daysUntilDue(); @endphp
                                    @if($days <= 7)
                                        <div class="text-xs text-orange-600 font-medium">
                                            {{ $days === 0 ? 'Vence hoje' : "Vence em {$days} dia(s)" }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ $invoice->formatted_total }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $invoice->status_color === 'green' ? 'from-green-100 to-green-200 text-green-800' : ($invoice->status_color === 'red' ? 'from-red-100 to-red-200 text-red-800' : ($invoice->status_color === 'yellow' ? 'from-yellow-100 to-yellow-200 text-yellow-800' : 'from-gray-100 to-gray-200 text-gray-800')) }}">
                                    {{ $invoice->status_badge }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.invoices.show', $invoice) }}"
                                    class="text-blue-600 hover:text-blue-900 mr-3" title="Ver detalhes">
                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="mt-2">Nenhuma fatura encontrada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="px-6 py-4 bg-gray-50">
            {{ $invoices->links() }}
        </div>
    </div>

    <!-- Modal de Nova Fatura -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay com z-index menor -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40" wire:click="closeCreateModal"></div>

        <!-- Container centralizado -->
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <!-- Modal Content com z-index maior -->
            <div class="relative z-50 w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-white">Nova Fatura Manual</h3>
                        <button wire:click="closeCreateModal" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                    <form wire:submit.prevent="createInvoice" class="space-y-6">
                        <!-- Informações Básicas -->
                        <div class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 border-b pb-2">Informações Básicas</h4>

                            <!-- Empresa -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Empresa Cliente *</label>
                                <select wire:model="tenant_id" required
                                    class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    <option value="">Selecione uma empresa</option>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }} - {{ $tenant->cnpj }}</option>
                                    @endforeach
                                </select>
                                @error('tenant_id') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Data de Emissão -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Data de Emissão *</label>
                                    <input type="date" wire:model="issue_date" required
                                        class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    @error('issue_date') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>

                                <!-- Data de Vencimento -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Data de Vencimento *</label>
                                    <input type="date" wire:model="due_date" required
                                        class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                    @error('due_date') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Itens da Fatura -->
                        <div class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 border-b pb-2">Itens da Fatura</h4>

                            <!-- Adicionar Item -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-3">Adicionar novo item</p>
                                <div class="space-y-3">
                                    <!-- Descrição em linha separada -->
                                    <div>
                                        <input type="text" wire:model="item_description" placeholder="Descrição do item (ex: Plano Premium, Consultoria, etc)"
                                            class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                        @error('item_description') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Quantidade, Valor e Botão -->
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-3">
                                            <input type="number" wire:model="item_quantity" placeholder="Quantidade" min="1" step="1"
                                                class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            @error('item_quantity') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-span-5">
                                            <input type="number" wire:model="item_unit_price" placeholder="Valor unitário (R$)" min="0" step="0.01"
                                                class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                            @error('item_unit_price') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-span-4">
                                            <button type="button" wire:click="addItem"
                                                class="w-full h-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-md hover:shadow-lg font-medium flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                Adicionar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lista de Itens -->
                            @if(count($items) > 0)
                                <div class="border rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Descrição</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-700">Qtd</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-700">Valor Unit.</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-700">Total</th>
                                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-700">Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($items as $index => $item)
                                                <tr>
                                                    <td class="px-4 py-2 text-sm">{{ $item['description'] }}</td>
                                                    <td class="px-4 py-2 text-sm text-center">{{ $item['quantity'] }}</td>
                                                    <td class="px-4 py-2 text-sm text-right">R$ {{ number_format($item['unit_price'], 2, ',', '.') }}</td>
                                                    <td class="px-4 py-2 text-sm text-right font-medium">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                                                    <td class="px-4 py-2 text-center">
                                                        <button type="button" wire:click="removeItem({{ $index }})"
                                                            class="text-red-600 hover:text-red-800">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p class="mt-2">Nenhum item adicionado ainda</p>
                                </div>
                            @endif
                            @error('items') <span class="text-red-600 text-sm block mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- Totalizadores -->
                        <div class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 border-b pb-2">Valores</h4>

                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-5 border border-blue-200">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Desconto (R$)</label>
                                        <input type="number" wire:model.live="discount" min="0" step="0.01" value="0"
                                            class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Impostos (R$)</label>
                                        <input type="number" wire:model.live="tax" min="0" step="0.01" value="0"
                                            class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white">
                                    </div>
                                    <div class="bg-white rounded-lg p-4 border-2 border-blue-300 shadow-md">
                                        <p class="text-xs font-medium text-gray-600 mb-1">Subtotal: R$ {{ number_format($subtotal, 2, ',', '.') }}</p>
                                        <p class="text-sm font-medium text-gray-700 mb-0.5">Total da Fatura</p>
                                        <p class="text-2xl font-bold text-blue-600">R$ {{ number_format($total, 2, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Instruções de Pagamento -->
                        <div class="space-y-4">
                            <h4 class="text-base font-semibold text-gray-900 border-b pb-2">Instruções de Pagamento</h4>
                            <textarea wire:model="payment_instructions" rows="3"
                                class="block w-full px-3 py-2.5 text-base border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                                placeholder="Ex: Pagamento pode ser realizado via Boleto ou PIX através do portal."></textarea>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 flex justify-between items-center border-t">
                    <p class="text-sm text-gray-600">
                        <span class="font-medium">Total:</span>
                        <span class="text-lg font-bold text-blue-600">R$ {{ number_format($total, 2, ',', '.') }}</span>
                    </p>
                    <div class="flex gap-3">
                        <button type="button" wire:click="closeCreateModal"
                            class="px-6 py-2.5 border-2 border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors font-medium">
                            Cancelar
                        </button>
                        <button type="button" wire:click="createInvoice"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all font-medium shadow-lg hover:shadow-xl flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Criar Fatura
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Estilo inline para o modal -->
    <style>
        /* Garantir que o modal fique acima de tudo */
        [role="dialog"] {
            position: fixed !important;
            z-index: 9999 !important;
        }
    </style>
</div>
