@php
use Livewire\Volt\Component;
use App\Models\Invoice;

new class extends Component {
    public $invoice = null;
    public $daysUntilDue = null;
    public $show = false;

    public function mount()
    {
        // Apenas para usuários não super-admin
        if (!auth()->check() || !auth()->user()->tenant_id) {
            return;
        }

        $tenant = auth()->user()->tenant;

        // Busca fatura mais próxima do vencimento
        $this->invoice = Invoice::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();

        if ($this->invoice) {
            $this->daysUntilDue = $this->invoice->daysUntilDue();
            $this->show = true;
        }
    }

    public function dismiss()
    {
        $this->show = false;
    }
};
@endphp

<div>
    @if($show && $invoice)
    <div class="mb-6 bg-gradient-to-r {{ $daysUntilDue < 0 ? 'from-red-50 to-rose-50 border-red-200' : ($daysUntilDue <= 3 ? 'from-orange-50 to-amber-50 border-orange-200' : 'from-blue-50 to-indigo-50 border-blue-200') }} border rounded-2xl p-5">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-white rounded-xl shadow-md">
                <svg class="w-8 h-8 {{ $daysUntilDue < 0 ? 'text-red-600' : ($daysUntilDue <= 3 ? 'text-orange-600' : 'text-blue-600') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
            </div>

            <div class="flex-1">
                <h3 class="font-bold text-gray-900 text-lg mb-1">
                    @if($daysUntilDue < 0)
                        Fatura Vencida há {{ abs($daysUntilDue) }} dia(s)
                    @elseif($daysUntilDue == 0)
                        Fatura Vence Hoje!
                    @else
                        Fatura Vence em {{ $daysUntilDue }} dia(s)
                    @endif
                </h3>
                <p class="text-sm text-gray-700 mb-3">
                    Fatura <strong>{{ $invoice->invoice_number }}</strong> no valor de
                    <strong class="text-lg">R$ {{ number_format($invoice->total, 2, ',', '.') }}</strong>
                    <span class="text-gray-500">vencimento em {{ $invoice->due_date->format('d/m/Y') }}</span>
                </p>

                <div class="flex gap-2 flex-wrap">
                    <a href="{{ route('tenant.billing.payment', $invoice) }}"
                       class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl font-medium text-sm">
                        Pagar Agora
                    </a>
                    <a href="{{ route('tenant.billing.index') }}"
                       class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium text-sm">
                        Ver Detalhes
                    </a>
                    <button wire:click="dismiss"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700 transition-colors text-sm">
                        Dispensar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
