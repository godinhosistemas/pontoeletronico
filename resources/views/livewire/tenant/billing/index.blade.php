<?php

use Livewire\Volt\Component;
use App\Models\Invoice;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $statusFilter = 'all';

    public function with(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Invoice::where('tenant_id', $tenantId)
            ->with(['subscription.plan', 'approvedPayment']);

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        return [
            'invoices' => $query->orderBy('created_at', 'desc')->paginate(10),
        ];
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Minhas Faturas
        </h1>
        <p class="text-gray-600 mt-1">Gerencie suas faturas e pagamentos</p>
    </div>

    <!-- Notificação de faturas pendentes/vencidas -->
    @livewire('tenant.billing-notification')

    <!-- Filtros -->
    <div class="mb-6 flex gap-3">
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

    <!-- Lista de Faturas -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Número</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Vencimento</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invoice->period_description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $invoice->due_date->format('d/m/Y') }}</div>
                                @if($invoice->isOverdue())
                                    <div class="text-xs text-red-600 font-medium">
                                        Vencida há {{ $invoice->daysOverdue() }} dia(s)
                                    </div>
                                @elseif($invoice->isPending())
                                    @php
                                        $daysUntilDue = $invoice->daysUntilDue();
                                    @endphp
                                    @if($daysUntilDue <= 7)
                                        <div class="text-xs text-orange-600 font-medium">
                                            Vence em {{ $daysUntilDue }} dia(s)
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
                                <div class="flex gap-2">
                                    @if(!$invoice->isPaid())
                                        <a href="{{ route('tenant.billing.payment', $invoice) }}"
                                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5">
                                            Pagar
                                        </a>
                                    @endif
                                    <a href="{{ route('tenant.billing.show', $invoice) }}"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                        Detalhes
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
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
</div>
