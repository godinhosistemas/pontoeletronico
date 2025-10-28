<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $showModal = false;
    public $showCertificateModal = false;
    public $editMode = false;
    public $certificateTenantId;

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

    // Certificate fields
    public $certificate_file;
    public $certificate_password = '';

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

    public function openCertificateModal($tenantId)
    {
        $this->certificateTenantId = $tenantId;
        $this->certificate_file = null;
        $this->certificate_password = '';
        $this->showCertificateModal = true;
    }

    public function closeCertificateModal()
    {
        $this->showCertificateModal = false;
        $this->certificateTenantId = null;
        $this->certificate_file = null;
        $this->certificate_password = '';
    }

    public function uploadCertificate()
    {
        $this->validate([
            'certificate_file' => 'required|file|mimes:pfx,p12|max:2048',
            'certificate_password' => 'required|string',
        ], [
            'certificate_file.required' => 'O arquivo do certificado é obrigatório',
            'certificate_file.mimes' => 'O certificado deve ser um arquivo .pfx ou .p12',
            'certificate_file.max' => 'O arquivo não pode ser maior que 2MB',
            'certificate_password.required' => 'A senha do certificado é obrigatória',
        ]);

        try {
            $tenant = Tenant::findOrFail($this->certificateTenantId);

            // Salva arquivo temporário
            $tempPath = $this->certificate_file->getRealPath();

            \Log::info('Upload de certificado iniciado', [
                'tenant_id' => $tenant->id,
                'temp_path' => $tempPath,
                'file_exists' => file_exists($tempPath)
            ]);

            // Usa o serviço para validar e armazenar
            $certificateService = app(CertificateService::class);

            // Primeiro valida para obter detalhes do erro
            $validation = $certificateService->validateAndExtractInfo($tempPath, $this->certificate_password);

            if ($validation && isset($validation['valid']) && $validation['valid']) {
                // Se válido, armazena
                $success = $certificateService->storeCertificate(
                    $tenant,
                    $tempPath,
                    $this->certificate_password
                );

                if ($success) {
                    session()->flash('success', 'Certificado digital cadastrado com sucesso!');
                    $this->closeCertificateModal();
                } else {
                    session()->flash('error', 'Erro ao armazenar o certificado. Tente novamente.');
                }
            } else {
                // Mostra erro específico
                $errorMsg = 'Certificado inválido ou senha incorreta.';
                if (is_array($validation) && isset($validation['error'])) {
                    $errorMsg = $validation['error'];
                }
                session()->flash('error', $errorMsg);
                \Log::warning('Certificado rejeitado', ['error' => $errorMsg]);
            }

        } catch (\Exception $e) {
            \Log::error('Exceção ao processar certificado: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Erro ao processar certificado: ' . $e->getMessage());
        }
    }

    public function removeCertificate($tenantId)
    {
        try {
            $tenant = Tenant::findOrFail($tenantId);
            $certificateService = app(CertificateService::class);

            if ($certificateService->removeCertificate($tenant)) {
                session()->flash('success', 'Certificado removido com sucesso!');
            } else {
                session()->flash('error', 'Erro ao remover certificado.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao remover certificado: ' . $e->getMessage());
        }
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
    @section('page-title', 'Gerenciar Empresas')

    <!-- Header with Search and Create Button -->
    <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="w-full sm:w-96">
            <input wire:model.live="search" type="text" placeholder="Buscar por nome, email ou CNPJ..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <button wire:click="create"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Nova Empresa
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
                        <th scope="col" class="px-6 py-3">Certificado</th>
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
                            @if($tenant->hasCertificate())
                                @php
                                    $days = $tenant->certificateDaysRemaining();
                                @endphp
                                @if($days <= 7)
                                    <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full" title="{{ $tenant->certificate_status }}">
                                        ⚠️ {{ $days }} dias
                                    </span>
                                @elseif($days <= 30)
                                    <span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full" title="{{ $tenant->certificate_status }}">
                                        ⏰ {{ $days }} dias
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full" title="{{ $tenant->certificate_status }}">
                                        ✓ Válido
                                    </span>
                                @endif
                            @else
                                <button wire:click="openCertificateModal({{ $tenant->id }})"
                                    class="px-2 py-1 text-xs font-semibold text-gray-700 bg-gray-200 rounded-full hover:bg-gray-300 cursor-pointer">
                                    + Adicionar
                                </button>
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
                                @if($tenant->hasCertificate())
                                <button wire:click="openCertificateModal({{ $tenant->id }})"
                                    class="text-green-600 hover:text-green-800" title="Renovar certificado">
                                    🔐 Certificado
                                </button>
                                <button wire:click="removeCertificate({{ $tenant->id }})"
                                    wire:confirm="Tem certeza que deseja remover o certificado digital?"
                                    class="text-orange-600 hover:text-orange-800" title="Remover certificado">
                                    🗑️
                                </button>
                                @endif
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
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Nenhum tenant encontrado</td>
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
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay com z-index menor -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40" aria-hidden="true" wire:click="closeModal"></div>

        <!-- Container centralizado -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Modal Content com z-index maior -->
            <div class="relative z-50 inline-block align-bottom bg-white rounded-lg text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
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

    <!-- Modal de Upload de Certificado -->
    @if($showCertificateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="certificate-modal-title" role="dialog" aria-modal="true">
        <!-- Overlay com z-index menor -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-40" aria-hidden="true" wire:click="closeCertificateModal"></div>

        <!-- Container centralizado -->
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Modal Content com z-index maior -->
            <div class="relative z-50 inline-block align-bottom bg-white rounded-lg text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="uploadCertificate" enctype="multipart/form-data">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center mb-4">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <span class="text-2xl">🔐</span>
                            </div>
                            <h3 class="ml-3 text-lg font-medium text-gray-900" id="certificate-modal-title">
                                Certificado Digital ICP-Brasil
                            </h3>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-blue-900 mb-2">📋 Requisitos:</h4>
                                <ul class="text-xs text-blue-800 space-y-1">
                                    <li>• Certificado deve ser ICP-Brasil válido</li>
                                    <li>• Formato: .pfx ou .p12</li>
                                    <li>• Tamanho máximo: 2MB</li>
                                    <li>• Tipos aceitos: A1 (arquivo) ou A3</li>
                                </ul>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Arquivo do Certificado (.pfx ou .p12)
                                </label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition-colors">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="certificate-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Selecionar arquivo</span>
                                                <input id="certificate-upload" name="certificate-upload" type="file" class="sr-only"
                                                    wire:model="certificate_file" accept=".pfx,.p12">
                                            </label>
                                            <p class="pl-1">ou arraste aqui</p>
                                        </div>
                                        <p class="text-xs text-gray-500">.pfx ou .p12 até 2MB</p>
                                    </div>
                                </div>
                                @if($certificate_file)
                                    <p class="mt-2 text-sm text-green-600">
                                        ✓ Arquivo selecionado: {{ $certificate_file->getClientOriginalName() }}
                                    </p>
                                @endif
                                @error('certificate_file')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror

                                <div wire:loading wire:target="certificate_file" class="mt-2">
                                    <div class="flex items-center text-sm text-blue-600">
                                        <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Carregando arquivo...
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    Senha do Certificado
                                </label>
                                <input wire:model="certificate_password" type="password" placeholder="Digite a senha do certificado"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none">
                                @error('certificate_password')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>

                            @if(session()->has('error'))
                            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 text-sm">
                                {{ session('error') }}
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" wire:loading.attr="disabled" wire:target="uploadCertificate,certificate_file"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="uploadCertificate">Enviar e Validar</span>
                            <span wire:loading wire:target="uploadCertificate">Processando...</span>
                        </button>
                        <button type="button" wire:click="closeCertificateModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Estilo inline para os modais -->
    <style>
        /* Garantir que os modais fiquem acima de tudo */
        [role="dialog"] {
            position: fixed !important;
            z-index: 9999 !important;
        }
    </style>
</div>
