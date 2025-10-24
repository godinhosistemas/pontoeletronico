@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                    Fatura {{ $invoice->invoice_number }}
                </h1>
                <p class="text-gray-600 mt-1">Detalhes e gerenciamento da fatura</p>
            </div>
            <a href="{{ route('admin.invoices.index') }}"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                Voltar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Detalhes da Fatura -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Informações da Fatura</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Número da Fatura</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $invoice->status_color === 'green' ? 'from-green-100 to-green-200 text-green-800' : ($invoice->status_color === 'red' ? 'from-red-100 to-red-200 text-red-800' : ($invoice->status_color === 'yellow' ? 'from-yellow-100 to-yellow-200 text-yellow-800' : 'from-gray-100 to-gray-200 text-gray-800')) }}">
                            {{ $invoice->status_badge }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Empresa</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->tenant->name }}</p>
                        <p class="text-xs text-gray-500">{{ $invoice->tenant->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Plano</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->subscription->plan->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Período</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->period_description }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Data de Emissão</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->issue_date->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Data de Vencimento</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->due_date->format('d/m/Y') }}</p>
                        @if($invoice->isOverdue())
                            <p class="text-xs text-red-600 font-medium mt-1">
                                Vencida há {{ $invoice->daysOverdue() }} dias
                            </p>
                        @endif
                    </div>
                    @if($invoice->paid_at)
                    <div>
                        <p class="text-sm text-gray-600">Data de Pagamento</p>
                        <p class="font-semibold text-green-600">{{ $invoice->paid_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="font-bold text-gray-900 mb-3">Itens da Fatura</h3>
                    @if($invoice->items)
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Descrição</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Qtd</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Valor Unit.</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $item['description'] }}</td>
                                    <td class="px-4 py-2 text-sm text-right">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-2 text-sm text-right">R$ {{ number_format($item['unit_price'], 2, ',', '.') }}</td>
                                    <td class="px-4 py-2 text-sm text-right font-semibold">R$ {{ number_format($item['total'], 2, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-end">
                        <div class="w-64 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Subtotal:</span>
                                <span class="text-sm font-semibold">R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
                            </div>
                            @if($invoice->discount > 0)
                            <div class="flex justify-between text-green-600">
                                <span class="text-sm">Desconto:</span>
                                <span class="text-sm font-semibold">- R$ {{ number_format($invoice->discount, 2, ',', '.') }}</span>
                            </div>
                            @endif
                            @if($invoice->tax > 0)
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Impostos:</span>
                                <span class="text-sm font-semibold">R$ {{ number_format($invoice->tax, 2, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-blue-600">{{ $invoice->formatted_total }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagamentos -->
            @if($invoice->payments->count() > 0)
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Pagamentos</h2>
                <div class="space-y-3">
                    @foreach($invoice->payments as $payment)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $payment->payment_number }}</p>
                                <p class="text-sm text-gray-600">{{ $payment->payment_method_name }} via {{ $payment->gateway->name }}</p>
                                <p class="text-xs text-gray-500">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">R$ {{ number_format($payment->amount, 2, ',', '.') }}</p>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r {{ $payment->status_color === 'green' ? 'from-green-100 to-green-200 text-green-800' : ($payment->status_color === 'red' ? 'from-red-100 to-red-200 text-red-800' : 'from-yellow-100 to-yellow-200 text-yellow-800') }}">
                                    {{ $payment->status_badge }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-sm text-blue-600 hover:text-blue-800">
                                Ver detalhes →
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Ações -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-xl p-6 sticky top-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Ações</h2>

                <div class="space-y-3">
                    <!-- Enviar 2ª Via -->
                    <form action="{{ route('admin.invoices.send-second-copy', $invoice) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Enviar 2ª Via por Email
                        </button>
                    </form>

                    <!-- Marcar como Paga -->
                    @if(!$invoice->isPaid())
                    <form action="{{ route('admin.invoices.mark-as-paid', $invoice) }}" method="POST"
                        onsubmit="return confirm('Tem certeza que deseja marcar esta fatura como paga?')">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Marcar como Paga
                        </button>
                    </form>
                    @endif

                    <!-- Cancelar Fatura -->
                    @if(!$invoice->isPaid() && $invoice->status !== 'cancelled')
                    <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST"
                        onsubmit="return confirm('Tem certeza que deseja cancelar esta fatura?')">
                        @csrf
                        <button type="submit"
                            class="w-full px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar Fatura
                        </button>
                    </form>
                    @endif

                    <!-- Gerar Pagamento -->
                    @if(!$invoice->isPaid() && $invoice->payments->count() === 0)
                    <div class="pt-3 border-t border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Gerar Pagamento:</p>
                        <form action="{{ route('admin.invoices.generate-payment', $invoice) }}" method="POST">
                            @csrf
                            <select name="payment_method" required
                                class="w-full mb-2 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Selecione o método</option>
                                <option value="boleto">Boleto</option>
                                <option value="pix">PIX</option>
                            </select>
                            <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-lg hover:shadow-xl text-sm">
                                Gerar Pagamento
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
