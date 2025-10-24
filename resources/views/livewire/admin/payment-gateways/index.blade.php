<?php

use Livewire\Volt\Component;
use App\Models\PaymentGateway;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $gatewayId = null;

    // Form fields
    public $name = '';
    public $provider = '';
    public $api_key = '';
    public $api_secret = '';
    public $environment = 'sandbox';
    public $supported_methods = [];
    public $fee_percentage = null;
    public $fee_fixed = null;
    public $is_active = true;
    public $is_default = false;

    public function with(): array
    {
        return [
            'gateways' => PaymentGateway::orderBy('is_default', 'desc')
                ->orderBy('is_active', 'desc')
                ->orderBy('name')
                ->paginate(10),
        ];
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $gateway = PaymentGateway::findOrFail($id);

        $this->gatewayId = $gateway->id;
        $this->name = $gateway->name;
        $this->provider = $gateway->provider;
        $this->environment = $gateway->environment;
        $this->supported_methods = $gateway->supported_methods ?? [];
        $this->fee_percentage = $gateway->fee_percentage;
        $this->fee_fixed = $gateway->fee_fixed;
        $this->is_active = $gateway->is_active;
        $this->is_default = $gateway->is_default;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|in:asaas,mercadopago,pagarme',
            'api_key' => $this->editMode ? 'nullable|string' : 'required|string',
            'api_secret' => 'nullable|string',
            'environment' => 'required|in:sandbox,production',
            'supported_methods' => 'required|array|min:1',
            'fee_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_fixed' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        $validated['slug'] = \Str::slug($validated['name']);

        // Se marcado como padrão, desmarcar outros
        if ($validated['is_default']) {
            PaymentGateway::where('is_default', true)->update(['is_default' => false]);
        }

        if ($this->editMode) {
            $gateway = PaymentGateway::findOrFail($this->gatewayId);

            // Se api_key vazio, não atualizar
            if (empty($validated['api_key'])) {
                unset($validated['api_key']);
            }
            if (empty($validated['api_secret'])) {
                unset($validated['api_secret']);
            }

            $gateway->update($validated);
            session()->flash('success', 'Gateway atualizado com sucesso!');
        } else {
            PaymentGateway::create($validated);
            session()->flash('success', 'Gateway criado com sucesso!');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive($id)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->update(['is_active' => !$gateway->is_active]);

        $status = $gateway->is_active ? 'ativado' : 'desativado';
        session()->flash('success', "Gateway {$status} com sucesso!");
    }

    public function setDefault($id)
    {
        PaymentGateway::where('is_default', true)->update(['is_default' => false]);

        $gateway = PaymentGateway::findOrFail($id);
        $gateway->update([
            'is_default' => true,
            'is_active' => true,
        ]);

        session()->flash('success', 'Gateway definido como padrão!');
    }

    public function delete($id)
    {
        $gateway = PaymentGateway::findOrFail($id);

        if ($gateway->payments()->exists()) {
            session()->flash('error', 'Não é possível excluir gateway com pagamentos associados.');
            return;
        }

        $gateway->delete();
        session()->flash('success', 'Gateway removido com sucesso!');
    }

    public function resetForm()
    {
        $this->reset([
            'gatewayId', 'name', 'provider', 'api_key', 'api_secret',
            'environment', 'supported_methods', 'fee_percentage', 'fee_fixed',
            'is_active', 'is_default'
        ]);
        $this->is_active = true;
        $this->environment = 'sandbox';
    }
}; ?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Gateways de Pagamento
        </h1>
        <p class="text-gray-600 mt-1">Gerencie os gateways de pagamento disponíveis no sistema</p>
    </div>

    <!-- Mensagens -->
    @if (session('success'))
        <div class="mb-4 bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 rounded-xl p-4">
            <p class="text-green-700 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 rounded-xl p-4">
            <p class="text-red-700 font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Botão Adicionar -->
    <div class="mb-6">
        <button wire:click="create"
            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Novo Gateway
        </button>
    </div>

    <!-- Lista de Gateways -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Provedor</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Métodos</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ambiente</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($gateways as $gateway)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="text-sm font-medium text-gray-900">{{ $gateway->name }}</div>
                                    @if($gateway->is_default)
                                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800">
                                            Padrão
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 capitalize">{{ $gateway->provider }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($gateway->supported_methods ?? [] as $method)
                                        <span class="px-2 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-700">
                                            {{ ucfirst($method) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($gateway->environment === 'production')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800">
                                        Produção
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800">
                                        Sandbox
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($gateway->is_active)
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800">
                                        Ativo
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800">
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2">
                                    @if(!$gateway->is_default)
                                        <button wire:click="setDefault({{ $gateway->id }})"
                                            class="text-blue-600 hover:text-blue-900" title="Definir como padrão">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    <button wire:click="toggleActive({{ $gateway->id }})"
                                        class="text-yellow-600 hover:text-yellow-900" title="Ativar/Desativar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="edit({{ $gateway->id }})"
                                        class="text-indigo-600 hover:text-indigo-900" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $gateway->id }})"
                                        wire:confirm="Tem certeza que deseja excluir este gateway?"
                                        class="text-red-600 hover:text-red-900" title="Excluir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="mt-2">Nenhum gateway cadastrado</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="px-6 py-4 bg-gray-50">
            {{ $gateways->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="$set('showModal', false)"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto">
                    <form wire:submit="save">
                        <div class="bg-white px-6 pt-5 pb-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">
                                {{ $editMode ? 'Editar Gateway' : 'Novo Gateway' }}
                            </h3>

                            <div class="space-y-4">
                                <!-- Nome -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                    <input type="text" wire:model="name"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Provedor -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Provedor</label>
                                    <select wire:model="provider"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Selecione...</option>
                                        <option value="asaas">Asaas</option>
                                        <option value="mercadopago">Mercado Pago</option>
                                        <option value="pagarme">Pagar.me</option>
                                    </select>
                                    @error('provider') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- API Key -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        API Key {{ $editMode ? '(deixe em branco para manter a atual)' : '' }}
                                    </label>
                                    <input type="text" wire:model="api_key"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    @error('api_key') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- API Secret -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        API Secret (opcional) {{ $editMode ? '(deixe em branco para manter a atual)' : '' }}
                                    </label>
                                    <input type="text" wire:model="api_secret"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                    @error('api_secret') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Ambiente -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Ambiente</label>
                                    <select wire:model="environment"
                                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                        <option value="sandbox">Sandbox (Teste)</option>
                                        <option value="production">Produção</option>
                                    </select>
                                    @error('environment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Métodos Suportados -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Métodos de Pagamento</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="supported_methods" value="boleto"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Boleto</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="supported_methods" value="pix"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">PIX</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="supported_methods" value="credit_card"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">Cartão de Crédito</span>
                                        </label>
                                    </div>
                                    @error('supported_methods') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <!-- Taxas -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Taxa % (opcional)</label>
                                        <input type="number" step="0.01" wire:model="fee_percentage"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                        @error('fee_percentage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Taxa Fixa R$ (opcional)</label>
                                        <input type="number" step="0.01" wire:model="fee_fixed"
                                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                        @error('fee_fixed') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Checkboxes -->
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="is_active"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Ativo</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" wire:model="is_default"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Gateway Padrão</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="$set('showModal', false)"
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                {{ $editMode ? 'Atualizar' : 'Criar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
