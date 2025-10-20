<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;

    // Form fields
    public $tenantId;
    public $name = '';
    public $slug = '';
    public $cnpj = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $is_active = true;

    // Subscription fields
    public $plan_id = '';
    public $trial_days = 7;

    // Supervisor user fields
    public $supervisor_name = '';
    public $supervisor_email = '';
    public $supervisor_password = '';
    public $supervisor_password_confirmation = '';

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

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editMode = false;
    }

    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);

        $this->tenantId = $tenant->id;
        $this->name = $tenant->name;
        $this->slug = $tenant->slug;
        $this->cnpj = $tenant->cnpj;
        $this->email = $tenant->email;
        $this->phone = $tenant->phone;
        $this->address = $tenant->address;
        $this->is_active = $tenant->is_active;

        $this->showModal = true;
        $this->editMode = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,' . ($this->tenantId ?? 'NULL'),
            'email' => 'required|email|unique:tenants,email,' . ($this->tenantId ?? 'NULL'),
            'cnpj' => 'nullable|string|max:18',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        if (!$this->editMode) {
            $rules['plan_id'] = 'required|exists:plans,id';
            $rules['supervisor_name'] = 'required|string|max:255';
            $rules['supervisor_email'] = 'required|email|unique:users,email';
            $rules['supervisor_password'] = 'required|string|min:8|confirmed';
        }

        $this->validate($rules);

        if ($this->editMode) {
            $tenant = Tenant::findOrFail($this->tenantId);
            $tenant->update([
                'name' => $this->name,
                'slug' => $this->slug,
                'cnpj' => $this->cnpj,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'is_active' => $this->is_active,
            ]);

            session()->flash('success', 'Tenant atualizado com sucesso!');
        } else {
            DB::beginTransaction();

            try {
                // Criar tenant
                $tenant = Tenant::create([
                    'name' => $this->name,
                    'slug' => $this->slug,
                    'cnpj' => $this->cnpj,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'address' => $this->address,
                    'is_active' => $this->is_active,
                ]);

                // Criar assinatura com trial
                $plan = Plan::findOrFail($this->plan_id);
                $subscription = $tenant->subscriptions()->create([
                    'plan_id' => $this->plan_id,
                    'start_date' => now(),
                    'end_date' => now()->addDays($this->trial_days),
                    'trial_ends_at' => now()->addDays($this->trial_days),
                    'status' => 'trialing',
                ]);

                // Criar usuário supervisor
                $supervisorUser = User::create([
                    'name' => $this->supervisor_name,
                    'email' => $this->supervisor_email,
                    'password' => Hash::make($this->supervisor_password),
                    'tenant_id' => $tenant->id,
                ]);

                // Atribuir role de admin-tenant ao supervisor
                $supervisorUser->assignRole('admin-tenant');

                DB::commit();
                session()->flash('success', 'Tenant e usuário supervisor criados com sucesso!');
            } catch (\Exception $e) {
                DB::rollBack();
                session()->flash('error', 'Erro ao criar tenant: ' . $e->getMessage());
                return;
            }
        }

        $this->closeModal();
        $this->resetPage();
    }

    public function delete($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();

        session()->flash('success', 'Tenant excluído com sucesso!');
    }

    public function toggleStatus($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['is_active' => !$tenant->is_active]);

        session()->flash('success', 'Status atualizado com sucesso!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->tenantId = null;
        $this->name = '';
        $this->slug = '';
        $this->cnpj = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->is_active = true;
        $this->plan_id = '';
        $this->trial_days = 7;
        $this->supervisor_name = '';
        $this->supervisor_email = '';
        $this->supervisor_password = '';
        $this->supervisor_password_confirmation = '';
    }

    public function with()
    {
        return [
            'tenants' => Tenant::with('activeSubscription.plan')
                ->when($this->search, function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('cnpj', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(10),
            'plans' => Plan::where('is_active', true)->get(),
        ];
    }
}; ?>

<div>
    @section('page-title', 'Gerenciar Tenants')

    <!-- Header with Search and Create Button -->
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-96">
            <input wire:model.live="search" type="text" placeholder="Buscar por nome, email ou CNPJ..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <button wire:click="create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Novo Tenant
        </button>
    </div>

    <!-- Tenants Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nome</th>
                        <th scope="col" class="px-6 py-3">Email</th>
                        <th scope="col" class="px-6 py-3">CNPJ</th>
                        <th scope="col" class="px-6 py-3">Plano</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $tenant->name }}</td>
                        <td class="px-6 py-4">{{ $tenant->email }}</td>
                        <td class="px-6 py-4">{{ $tenant->cnpj ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($tenant->activeSubscription)
                            <span class="text-blue-600">{{ $tenant->activeSubscription->plan->name }}</span>
                            @else
                            <span class="text-gray-400">Sem assinatura</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <button wire:click="toggleStatus({{ $tenant->id }})"
                                class="focus:outline-none">
                                @if($tenant->is_active)
                                <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full cursor-pointer">Ativo</span>
                                @else
                                <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full cursor-pointer">Inativo</span>
                                @endif
                            </button>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <button wire:click="edit({{ $tenant->id }})"
                                    class="text-blue-600 hover:text-blue-800">
                                    Editar
                                </button>
                                <button wire:click="delete({{ $tenant->id }})"
                                    wire:confirm="Tem certeza que deseja excluir este tenant?"
                                    class="text-red-600 hover:text-red-800">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Nenhum tenant encontrado</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $tenants->links() }}
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-data="{ show: true }">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeModal"></div>

            <!-- Espacer para centralizar -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-10">
                <form wire:submit.prevent="save" autocomplete="off">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ $editMode ? 'Editar Empresa' : 'Nova Empresa' }}
                        </h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nome</label>
                                <input wire:model="name" type="text"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Slug</label>
                                <input wire:model="slug" type="text"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input wire:model="email" type="email"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                                <input wire:model="cnpj" type="text"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('cnpj') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                                <input wire:model="phone" type="text"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Endereço</label>
                                <textarea wire:model="address" rows="2"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"></textarea>
                                @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            @if(!$editMode)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Plano Inicial</label>
                                <select wire:model="plan_id"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                    <option value="">Selecione um plano</option>
                                    @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} - {{ $plan->formatted_price }}</option>
                                    @endforeach
                                </select>
                                @error('plan_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dias de Trial</label>
                                <input wire:model="trial_days" type="number" min="0"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('trial_days') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-300 pt-4 mt-4">
                                <h4 class="text-md font-semibold text-gray-800 mb-3">Dados do Supervisor</h4>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nome do Supervisor</label>
                                <input wire:model="supervisor_name" type="text"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('supervisor_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email do Supervisor</label>
                                <input wire:model="supervisor_email" type="email"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('supervisor_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Senha</label>
                                <input wire:model="supervisor_password" type="password"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('supervisor_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                                <input wire:model="supervisor_password_confirmation" type="password"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                            </div>
                            @endif

                            <div class="flex items-center">
                                <input wire:model="is_active" type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900">Ativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="save">{{ $editMode ? 'Atualizar' : 'Criar' }}</span>
                            <span wire:loading wire:target="save">Processando...</span>
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
