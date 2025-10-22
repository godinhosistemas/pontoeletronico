<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Plan;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;

    // Form fields
    public $planId;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $price = '';
    public $max_users = 10;
    public $max_employees = 50;
    public $billing_cycle_days = 30;
    public $trial_days = 7;
    public $is_active = true;
    public $features = [];
    public $newFeature = '';

    protected $queryString = ['search'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedName()
    {
        if (!$this->editMode) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function addFeature()
    {
        if (!empty($this->newFeature)) {
            $this->features[] = $this->newFeature;
            $this->newFeature = '';
        }
    }

    public function removeFeature($index)
    {
        unset($this->features[$index]);
        $this->features = array_values($this->features);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editMode = false;
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);

        $this->planId = $plan->id;
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->description = $plan->description;
        $this->price = $plan->price;
        $this->max_users = $plan->max_users;
        $this->max_employees = $plan->max_employees;
        $this->billing_cycle_days = $plan->billing_cycle_days;
        $this->trial_days = $plan->trial_days;
        $this->is_active = $plan->is_active;
        $this->features = $plan->features ?? [];

        $this->showModal = true;
        $this->editMode = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug,' . ($this->planId ?? 'NULL'),
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_employees' => 'required|integer|min:1',
            'billing_cycle_days' => 'required|integer|min:1',
            'trial_days' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'max_users' => $this->max_users,
            'max_employees' => $this->max_employees,
            'billing_cycle_days' => $this->billing_cycle_days,
            'trial_days' => $this->trial_days,
            'is_active' => $this->is_active,
            'features' => $this->features,
        ];

        if ($this->editMode) {
            $plan = Plan::findOrFail($this->planId);
            $plan->update($data);
            session()->flash('success', 'Plano atualizado com sucesso!');
        } else {
            Plan::create($data);
            session()->flash('success', 'Plano criado com sucesso!');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $plan = Plan::findOrFail($id);

        // Verificar se existem assinaturas ativas
        if ($plan->activeSubscriptions()->exists()) {
            session()->flash('error', 'Não é possível excluir um plano com assinaturas ativas.');
            return;
        }

        $plan->delete();
        session()->flash('success', 'Plano excluído com sucesso!');
    }

    public function toggleStatus($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);

        session()->flash('success', 'Status atualizado com sucesso!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->planId = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->price = '';
        $this->max_users = 10;
        $this->max_employees = 50;
        $this->billing_cycle_days = 30;
        $this->trial_days = 7;
        $this->is_active = true;
        $this->features = [];
        $this->newFeature = '';
    }

    public function with()
    {
        return [
            'plans' => Plan::withCount('subscriptions')
                ->when($this->search, function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('slug', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div>
    <!-- Header with Search and Create Button -->
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-96">
            <input wire:model.live="search" type="text" placeholder="Buscar por nome ou slug..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <button wire:click="create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Novo Plano
        </button>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($plans as $plan)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 {{ $plan->is_active ? 'border-blue-500' : 'border-gray-300' }}">
            <!-- Plan Header -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-2xl font-bold">{{ $plan->name }}</h3>
                    <button wire:click="toggleStatus({{ $plan->id }})" class="focus:outline-none">
                        @if($plan->is_active)
                        <span class="px-2 py-1 text-xs font-semibold bg-green-500 rounded-full">Ativo</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold bg-red-500 rounded-full">Inativo</span>
                        @endif
                    </button>
                </div>
                <p class="text-sm opacity-90">{{ $plan->description }}</p>
            </div>

            <!-- Plan Price -->
            <div class="p-6 bg-gray-50">
                <div class="text-center">
                    <span class="text-4xl font-bold text-gray-900">{{ $plan->formatted_price }}</span>
                    <span class="text-gray-600">/{{ $plan->billing_cycle_days }} dias</span>
                </div>
            </div>

            <!-- Plan Details -->
            <div class="p-6">
                <div class="space-y-3 mb-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Máx. Usuários:</span>
                        <span class="font-semibold">{{ $plan->max_users }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Máx. Funcionários:</span>
                        <span class="font-semibold">{{ $plan->max_employees }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Trial:</span>
                        <span class="font-semibold">{{ $plan->trial_days }} dias</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Assinaturas:</span>
                        <span class="font-semibold">{{ $plan->subscriptions_count }}</span>
                    </div>
                </div>

                <!-- Features -->
                @if($plan->features && count($plan->features) > 0)
                <div class="border-t pt-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-2">Recursos:</h4>
                    <ul class="space-y-2">
                        @foreach($plan->features as $feature)
                        <li class="flex items-start text-sm">
                            <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Actions -->
                <div class="flex gap-2 mt-6 pt-4 border-t">
                    <button wire:click="edit({{ $plan->id }})"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Editar
                    </button>
                    <button wire:click="delete({{ $plan->id }})"
                        wire:confirm="Tem certeza que deseja excluir este plano?"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium">
                        Excluir
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-12 bg-white rounded-lg shadow">
            <p class="text-gray-500">Nenhum plano encontrado</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $plans->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ $editMode ? 'Editar Plano' : 'Novo Plano' }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Nome</label>
                                <input wire:model="name" wire:blur="updatedName" type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Slug</label>
                                <input wire:model="slug" type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                <textarea wire:model="description" rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Preço (R$)</label>
                                <input wire:model="price" type="number" step="0.01" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ciclo de Cobrança (dias)</label>
                                <input wire:model="billing_cycle_days" type="number" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('billing_cycle_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Máx. Usuários</label>
                                <input wire:model="max_users" type="number" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('max_users') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Máx. Funcionários</label>
                                <input wire:model="max_employees" type="number" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('max_employees') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Dias de Trial</label>
                                <input wire:model="trial_days" type="number" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('trial_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Recursos</label>
                                <div class="space-y-2">
                                    @foreach($features as $index => $feature)
                                    <div class="flex items-center gap-2">
                                        <input type="text" value="{{ $feature }}" readonly
                                            class="flex-1 rounded-md border-gray-300 bg-gray-50">
                                        <button type="button" wire:click="removeFeature({{ $index }})"
                                            class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                            Remover
                                        </button>
                                    </div>
                                    @endforeach
                                    <div class="flex items-center gap-2">
                                        <input wire:model="newFeature" type="text" placeholder="Adicionar recurso..."
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            wire:keydown.enter.prevent="addFeature">
                                        <button type="button" wire:click="addFeature"
                                            class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                            Adicionar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 flex items-center">
                                <input wire:model="is_active" type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900">Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $editMode ? 'Atualizar' : 'Criar' }}
                        </button>
                        <button type="button" wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
