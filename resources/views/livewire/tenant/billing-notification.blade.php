<?php

use Livewire\Volt\Component;
use App\Models\Invoice;

new class extends Component {
    public $pendingInvoices = [];
    public $overdueInvoices = [];

    public function mount()
    {
        $this->loadInvoices();
    }

    public function loadInvoices()
    {
        $tenantId = auth()->user()->tenant_id;

        // Buscar faturas pendentes
        $this->pendingInvoices = Invoice::where('tenant_id', $tenantId)
            ->pending()
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $invoice->formatted_total,
                    'due_date' => $invoice->due_date->format('d/m/Y'),
                    'days_until_due' => $invoice->daysUntilDue(),
                ];
            })
            ->toArray();

        // Buscar faturas vencidas
        $this->overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->overdue()
            ->orderBy('due_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total' => $invoice->formatted_total,
                    'due_date' => $invoice->due_date->format('d/m/Y'),
                    'days_overdue' => $invoice->daysOverdue(),
                ];
            })
            ->toArray();
    }
}; ?>

<div>
    @if(count($overdueInvoices) > 0)
        <!-- Alerta de Faturas Vencidas -->
        <div class="mb-4 bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 rounded-xl p-4 shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-red-800">
                        Atenção! Você tem {{ count($overdueInvoices) }} fatura(s) vencida(s)
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p class="mb-2">Regularize sua situação para manter seu acesso ao sistema:</p>
                        <ul class="space-y-2">
                            @foreach($overdueInvoices as $invoice)
                                <li class="flex items-center justify-between bg-white/50 rounded-lg p-2">
                                    <div>
                                        <span class="font-semibold">{{ $invoice['invoice_number'] }}</span>
                                        <span class="mx-2">•</span>
                                        <span>{{ $invoice['total'] }}</span>
                                        <span class="mx-2">•</span>
                                        <span class="text-red-600 font-medium">Vencida há {{ $invoice['days_overdue'] }} dia(s)</span>
                                    </div>
                                    <a href="{{ route('tenant.billing.payment', $invoice['id']) }}"
                                       class="px-4 py-1.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-xs font-semibold rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5">
                                        Pagar Agora
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(count($pendingInvoices) > 0)
        <!-- Alerta de Faturas Pendentes -->
        @foreach($pendingInvoices as $invoice)
            @php
                $daysUntilDue = $invoice['days_until_due'];
                $alertClass = match(true) {
                    $daysUntilDue <= 3 => 'from-orange-50 to-orange-100 border-orange-500',
                    $daysUntilDue <= 7 => 'from-yellow-50 to-yellow-100 border-yellow-500',
                    default => 'from-blue-50 to-blue-100 border-blue-500'
                };
                $iconColor = match(true) {
                    $daysUntilDue <= 3 => 'text-orange-600',
                    $daysUntilDue <= 7 => 'text-yellow-600',
                    default => 'text-blue-600'
                };
                $textColor = match(true) {
                    $daysUntilDue <= 3 => 'text-orange-800',
                    $daysUntilDue <= 7 => 'text-yellow-800',
                    default => 'text-blue-800'
                };
                $buttonClass = match(true) {
                    $daysUntilDue <= 3 => 'from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800',
                    $daysUntilDue <= 7 => 'from-yellow-600 to-yellow-700 hover:from-yellow-700 hover:to-yellow-800',
                    default => 'from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700'
                };
                $message = match(true) {
                    $daysUntilDue === 0 => 'Vence hoje!',
                    $daysUntilDue === 1 => 'Vence amanhã!',
                    $daysUntilDue <= 3 => "Vence em {$daysUntilDue} dias",
                    $daysUntilDue <= 7 => "Vence em {$daysUntilDue} dias",
                    default => "Vence em {$daysUntilDue} dias"
                };
            @endphp

            <div class="mb-3 bg-gradient-to-r {{ $alertClass }} border-l-4 rounded-xl p-4 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium {{ $textColor }}">
                                Fatura <span class="font-bold">{{ $invoice['invoice_number'] }}</span>
                                no valor de <span class="font-bold">{{ $invoice['total'] }}</span> -
                                <span class="font-bold">{{ $message }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('tenant.billing.show', $invoice['id']) }}"
                           class="px-4 py-1.5 bg-white/50 text-gray-700 text-xs font-semibold rounded-lg hover:bg-white transition-all duration-200 shadow hover:shadow-md">
                            Ver Detalhes
                        </a>
                        <a href="{{ route('tenant.billing.payment', $invoice['id']) }}"
                           class="px-4 py-1.5 bg-gradient-to-r {{ $buttonClass }} text-white text-xs font-semibold rounded-lg transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5">
                            Pagar
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
