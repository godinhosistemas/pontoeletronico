<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Holiday;
use Carbon\Carbon;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $modalAction = 'create';
    public $filterType = '';
    public $filterYear = '';
    public $selectedTenantId = null;

    // Form fields
    public $holidayId;
    public $name = '';
    public $date = '';
    public $type = 'municipal';
    public $city = '';
    public $state = '';
    public $is_recurring = false;
    public $description = '';
    public $is_active = true;

    public function mount()
    {
        $this->filterYear = Carbon::now()->year;

        // Se o usuário tem tenant_id, usar ele. Caso contrário (super admin), usar o primeiro tenant disponível
        $this->selectedTenantId = auth()->user()->tenant_id ?? \App\Models\Tenant::first()?->id;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $holidays = Holiday::where('tenant_id', $this->selectedTenantId)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('city', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType, function ($query) {
                $query->where('type', $this->filterType);
            })
            ->when($this->filterYear, function ($query) {
                $query->whereYear('date', $this->filterYear);
            })
            ->orderBy('date', 'asc')
            ->paginate(15);

        // Listar tenants para super admins
        $tenants = auth()->user()->tenant_id ? [] : \App\Models\Tenant::orderBy('name')->get();

        return [
            'holidays' => $holidays,
            'tenants' => $tenants,
        ];
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->modalAction = 'create';
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $holiday = Holiday::findOrFail($id);

        $this->holidayId = $holiday->id;
        $this->name = $holiday->name;
        $this->date = $holiday->date->format('Y-m-d');
        $this->type = $holiday->type;
        $this->city = $holiday->city ?? '';
        $this->state = $holiday->state ?? '';
        $this->is_recurring = $holiday->is_recurring;
        $this->description = $holiday->description ?? '';
        $this->is_active = $holiday->is_active;

        $this->modalAction = 'edit';
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'type' => 'required|in:national,state,municipal,custom',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:2',
            'description' => 'nullable|string',
        ]);

        $data = [
            'tenant_id' => $this->selectedTenantId,
            'name' => $this->name,
            'date' => $this->date,
            'type' => $this->type,
            'city' => $this->city,
            'state' => $this->state,
            'is_recurring' => $this->is_recurring,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->modalAction === 'create') {
            Holiday::create($data);
            session()->flash('success', 'Feriado cadastrado com sucesso!');
        } else {
            $holiday = Holiday::findOrFail($this->holidayId);
            $holiday->update($data);
            session()->flash('success', 'Feriado atualizado com sucesso!');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->delete();
        session()->flash('success', 'Feriado excluído com sucesso!');
    }

    public function toggleActive($id)
    {
        $holiday = Holiday::findOrFail($id);
        $holiday->update(['is_active' => !$holiday->is_active]);
        session()->flash('success', 'Status atualizado com sucesso!');
    }

    public function importNationalHolidays()
    {
        if (!$this->selectedTenantId) {
            session()->flash('error', 'Por favor, selecione uma empresa antes de importar os feriados.');
            return;
        }

        try {
            $count = Holiday::createDefaultNationalHolidays(
                $this->selectedTenantId,
                $this->filterYear
            );
            session()->flash('success', "{$count} feriados nacionais importados com sucesso!");
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao importar feriados: ' . $e->getMessage());
        }
    }

    public function resetForm()
    {
        $this->holidayId = null;
        $this->name = '';
        $this->date = '';
        $this->type = 'municipal';
        $this->city = '';
        $this->state = '';
        $this->is_recurring = false;
        $this->description = '';
        $this->is_active = true;
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Gerenciar Feriados</h2>
                        <p class="text-sm text-gray-600 mt-1">Configure os feriados municipais e personalizados da sua empresa</p>
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="importNationalHolidays"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            Importar Feriados Nacionais
                        </button>
                        <button wire:click="openCreateModal"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Novo Feriado
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <!-- Seletor de Empresa (apenas para Super Admins) -->
                @if(!auth()->user()->tenant_id && count($tenants) > 0)
                    <div class="mb-4 pb-4 border-b border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <span class="text-blue-600">👤 Super Admin:</span> Selecione a Empresa
                        </label>
                        <select wire:model.live="selectedTenantId" class="w-full md:w-1/2 border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm">
                            <option value="">Selecione uma empresa...</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Os feriados serão cadastrados para a empresa selecionada</p>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pesquisar</label>
                        <input type="text" wire:model.live="search"
                               placeholder="Nome, cidade..."
                               class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                        <select wire:model.live="filterType" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">Todos</option>
                            <option value="national">Nacional</option>
                            <option value="state">Estadual</option>
                            <option value="municipal">Municipal</option>
                            <option value="custom">Personalizado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                        <select wire:model.live="filterYear" class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @for ($year = date('Y') - 1; $year <= date('Y') + 2; $year++)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                @if(session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localidade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorrente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($holidays as $holiday)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $holiday->formatted_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $holiday->name }}</div>
                                        @if($holiday->description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($holiday->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $holiday->type_color }}-100 text-{{ $holiday->type_color }}-800">
                                            {{ $holiday->type_text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($holiday->type === 'municipal' && $holiday->city)
                                            {{ $holiday->city }}@if($holiday->state), {{ $holiday->state }}@endif
                                        @elseif($holiday->type === 'state' && $holiday->state)
                                            {{ $holiday->state }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($holiday->is_recurring)
                                            <span class="text-green-600 font-semibold">Sim</span>
                                        @else
                                            <span class="text-gray-400">Não</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button wire:click="toggleActive({{ $holiday->id }})"
                                                class="text-sm {{ $holiday->is_active ? 'text-green-600' : 'text-red-600' }} font-semibold hover:underline">
                                            {{ $holiday->is_active ? 'Ativo' : 'Inativo' }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button wire:click="openEditModal({{ $holiday->id }})"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            Editar
                                        </button>
                                        <button wire:click="delete({{ $holiday->id }})"
                                                wire:confirm="Tem certeza que deseja excluir este feriado?"
                                                class="text-red-600 hover:text-red-900">
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Nenhum feriado encontrado. Clique em "Importar Feriados Nacionais" para começar.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $holidays->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showModal', false)"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <form wire:submit.prevent="save">
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 mb-4">
                                {{ $modalAction === 'create' ? 'Novo Feriado' : 'Editar Feriado' }}
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nome do Feriado *</label>
                                    <input type="text" wire:model="name" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('name') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data *</label>
                                    <input type="date" wire:model="date" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('date') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tipo *</label>
                                    <select wire:model="type" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="national">Nacional</option>
                                        <option value="state">Estadual</option>
                                        <option value="municipal">Municipal</option>
                                        <option value="custom">Personalizado</option>
                                    </select>
                                    @error('type') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cidade</label>
                                        <input type="text" wire:model="city"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">UF</label>
                                        <input type="text" wire:model="state" maxlength="2"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Descrição</label>
                                    <textarea wire:model="description" rows="3"
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_recurring" id="is_recurring"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label for="is_recurring" class="ml-2 block text-sm text-gray-900">
                                        Recorrente (repete todo ano)
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_active" id="is_active"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        Ativo
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto">
                                {{ $modalAction === 'create' ? 'Criar' : 'Salvar' }}
                            </button>
                            <button type="button" wire:click="$set('showModal', false)"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
