<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Tenant;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $showModal = false;
    public $modalAction = '';

    // Form fields
    public $subscriptionId;
    public $tenant_id;
    public $plan_id;
    public $cancellation_reason = '';

    protected $queryString = ['search', 'statusFilter'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    // Renovar assinatura
    public function renewSubscription($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->renew();

        session()->flash('success', 'Assinatura renovada com sucesso!');
    }

    // Suspender assinatura
    public function suspendSubscription($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->suspend();

        session()->flash('success', 'Assinatura suspensa com sucesso!');
    }

    // Reativar assinatura
    public function reactivateSubscription($id)
    {
        $subscription = Subscription::findOrFail($id);
        $subscription->reactivate();

        session()->flash('success', 'Assinatura reativada com sucesso!');
    }

    // Abrir modal para cancelar
    public function openCancelModal($id)
    {
        $this->subscriptionId = $id;
        $this->modalAction = 'cancel';
        $this->showModal = true;
    }

    // Cancelar assinatura
    public function cancelSubscription()
    {
        $this->validate([
            'cancellation_reason' => 'required|string|min:10',
        ]);

        $subscription = Subscription::findOrFail($this->subscriptionId);
        $subscription->cancel($this->cancellation_reason);

        session()->flash('success', 'Assinatura cancelada com sucesso!');
        $this->closeModal();
        $this->resetPage();
    }

    // Abrir modal para alterar plano
    public function openChangePlanModal($id)
    {
        $subscription = Subscription::findOrFail($id);
        $this->subscriptionId = $id;
        $this->plan_id = $subscription->plan_id;
        $this->modalAction = 'changePlan';
        $this->showModal = true;
    }

    // Alterar plano
    public function changePlan()
    {
        $this->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $subscription = Subscription::findOrFail($this->subscriptionId);
        $subscription->update(['plan_id' => $this->plan_id]);

        session()->flash('success', 'Plano alterado com sucesso!');
        $this->closeModal();
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['subscriptionId', 'plan_id', 'cancellation_reason', 'modalAction']);
    }

    public function with()
    {
        return [
            'subscriptions' => Subscription::with(['tenant', 'plan'])
                ->when($this->search, function($query) {
                    $query->whereHas('tenant', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->statusFilter, function($query) {
                    $query->where('status', $this->statusFilter);
                })
                ->latest()
                ->paginate(15),
            'plans' => Plan::where('is_active', true)->get(),
        ];
    }
}; ?>

<div>
    @section('page-title', 'Gerenciar Assinaturas')

    <!-- Filters -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
            <input wire:model.live="search" type="text" placeholder="Buscar por empresa..."
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div>
            <select wire:model.live="statusFilter" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Todos os Status</option>
                <option value="trialing">Em Trial</option>
                <option value="active">Ativo</option>
                <option value="suspended">Suspenso</option>
                <option value="canceled">Cancelado</option>
                <option value="expired">Expirado</option>
            </select>
        </div>
    </div>

    <!-- Subscriptions List -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs font-semibold uppercase bg-gradient-to-r from-gray-50 to-slate-50 text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left">Empresa</th>
                        <th scope="col" class="px-6 py-4 text-left">Plano</th>
                        <th scope="col" class="px-6 py-4 text-left">Status</th>
                        <th scope="col" class="px-6 py-4 text-left">Início</th>
                        <th scope="col" class="px-6 py-4 text-left">Vencimento</th>
                        <th scope="col" class="px-6 py-4 text-left">Valor</th>
                        <th scope="col" class="px-6 py-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                    <tr class="bg-white border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold mr-3">
                                    {{ substr($subscription->tenant->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $subscription->tenant->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $subscription->tenant->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-lg text-xs font-semibold">
                                {{ $subscription->plan->name }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($subscription->status === 'trialing')
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2 animate-pulse"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-700 rounded-lg text-xs font-semibold">Trial</span>
                            </span>
                            @elseif($subscription->status === 'active')
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 rounded-lg text-xs font-semibold">Ativo</span>
                            </span>
                            @elseif($subscription->status === 'suspended')
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-orange-100 to-amber-100 text-orange-700 rounded-lg text-xs font-semibold">Suspenso</span>
                            </span>
                            @elseif($subscription->status === 'canceled')
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-red-100 to-rose-100 text-red-700 rounded-lg text-xs font-semibold">Cancelado</span>
                            </span>
                            @else
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">{{ ucfirst($subscription->status) }}</span>
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            {{ $subscription->start_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($subscription->end_date->isPast())
                            <span class="text-red-600 font-semibold">{{ $subscription->end_date->format('d/m/Y') }}</span>
                            @else
                            <span class="text-gray-600">{{ $subscription->end_date->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900">
                            {{ $subscription->plan->formatted_price }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                @if($subscription->status === 'active' || $subscription->status === 'trialing')
                                <button wire:click="openChangePlanModal({{ $subscription->id }})"
                                    class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-medium transition-colors"
                                    title="Alterar Plano">
                                    Alterar Plano
                                </button>
                                <button wire:click="suspendSubscription({{ $subscription->id }})"
                                    wire:confirm="Tem certeza que deseja suspender esta assinatura?"
                                    class="px-3 py-1.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-xs font-medium transition-colors"
                                    title="Suspender">
                                    Suspender
                                </button>
                                <button wire:click="openCancelModal({{ $subscription->id }})"
                                    class="px-3 py-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 text-xs font-medium transition-colors"
                                    title="Cancelar">
                                    Cancelar
                                </button>
                                @elseif($subscription->status === 'suspended')
                                <button wire:click="reactivateSubscription({{ $subscription->id }})"
                                    wire:confirm="Tem certeza que deseja reativar esta assinatura?"
                                    class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-xs font-medium transition-colors"
                                    title="Reativar">
                                    Reativar
                                </button>
                                @elseif($subscription->status === 'expired')
                                <button wire:click="renewSubscription({{ $subscription->id }})"
                                    wire:confirm="Tem certeza que deseja renovar esta assinatura?"
                                    class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xs font-medium transition-colors"
                                    title="Renovar">
                                    Renovar
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-500 font-medium">Nenhuma assinatura encontrada</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $subscriptions->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay com z-index menor -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40" aria-hidden="true" wire:click="closeModal"></div>

        <!-- Container centralizado -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Modal Content com z-index maior -->
            <div class="relative z-50 inline-block align-bottom bg-white rounded-2xl text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                @if($modalAction === 'cancel')
                <form wire:submit.prevent="cancelSubscription">
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center mb-4">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Cancelar Assinatura</h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Motivo do Cancelamento *</label>
                            <textarea wire:model="cancellation_reason" rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-red-500 focus:border-transparent focus:outline-none"
                                placeholder="Descreva o motivo do cancelamento..." required></textarea>
                            @error('cancellation_reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-sm text-gray-600">Esta ação não pode ser desfeita. A assinatura será cancelada imediatamente.</p>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="cancelSubscription">Confirmar Cancelamento</span>
                            <span wire:loading wire:target="cancelSubscription">Processando...</span>
                        </button>
                        <button type="button" wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
                @elseif($modalAction === 'changePlan')
                <form wire:submit.prevent="changePlan">
                    <div class="bg-white px-6 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Alterar Plano</h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Novo Plano *</label>
                            <select wire:model="plan_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:outline-none">
                                <option value="">Selecione um plano</option>
                                @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->formatted_price }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="changePlan">Alterar Plano</span>
                            <span wire:loading wire:target="changePlan">Processando...</span>
                        </button>
                        <button type="button" wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
                @endif
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
