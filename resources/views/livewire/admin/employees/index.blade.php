<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $tenantFilter = '';
    public $statusFilter = '';
    public $showModal = false;
    public $modalAction = 'create';
    public $employeeId = null;

    // Modal de código único
    public $showCodeModal = false;
    public $generatedCode = '';
    public $generatedEmployeeName = '';

    // Campos do formulário
    public $tenant_id = '';
    public $name = '';
    public $email = '';
    public $cpf = '';
    public $registration_number = '';
    public $phone = '';
    public $birth_date = '';
    public $position = '';
    public $department = '';
    public $admission_date = '';
    public $salary = '';
    public $address = '';
    public $city = '';
    public $state = '';
    public $zip_code = '';
    public $status = 'active';
    public $work_schedule_id = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTenantFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Employee::with(['tenant', 'workSchedule']);

        // Se não for super admin, mostrar apenas funcionários do próprio tenant
        if (!auth()->user()->isSuperAdmin()) {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('cpf', 'like', '%' . $this->search . '%')
                  ->orWhere('registration_number', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->tenantFilter) {
            $query->where('tenant_id', $this->tenantFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $employees = $query->orderBy('created_at', 'desc')->paginate(15);
        $tenants = Tenant::where('is_active', true)->orderBy('name')->get();

        // Carregar jornadas de trabalho do tenant atual
        $workSchedules = WorkSchedule::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'employees' => $employees,
            'tenants' => $tenants,
            'workSchedules' => $workSchedules,
        ];
    }

    public function openCreateModal()
    {
        $this->resetForm();

        // Se não for super admin, preencher automaticamente o tenant_id
        if (!auth()->user()->isSuperAdmin()) {
            $this->tenant_id = auth()->user()->tenant_id;
        }

        $this->modalAction = 'create';
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $employee = Employee::findOrFail($id);

        $this->employeeId = $employee->id;
        $this->tenant_id = $employee->tenant_id;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->cpf = $employee->cpf;
        $this->registration_number = $employee->registration_number;
        $this->phone = $employee->phone;
        $this->birth_date = $employee->birth_date?->format('Y-m-d');
        $this->position = $employee->position;
        $this->department = $employee->department;
        $this->admission_date = $employee->admission_date->format('Y-m-d');
        $this->salary = $employee->salary;
        $this->address = $employee->address;
        $this->city = $employee->city;
        $this->state = $employee->state;
        $this->zip_code = $employee->zip_code;
        $this->status = $employee->status;
        $this->work_schedule_id = $employee->work_schedule_id;

        $this->modalAction = 'edit';
        $this->showModal = true;
    }

    public function saveEmployee()
    {
        try {
            // Log para debug
            \Log::info('Iniciando saveEmployee', [
                'modal_action' => $this->modalAction,
                'tenant_id' => $this->tenant_id,
                'user_tenant_id' => auth()->user()->tenant_id,
                'is_super_admin' => auth()->user()->isSuperAdmin(),
            ]);

            $rules = [
                'tenant_id' => 'required|exists:tenants,id',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'cpf' => 'required|string|min:11|max:14',
                'registration_number' => 'required|string|max:255',
                'phone' => 'nullable|string|max:20',
                'birth_date' => 'nullable|date',
                'position' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'admission_date' => 'required|date',
                'salary' => 'nullable|numeric|min:0',
                'address' => 'nullable|string',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:2',
                'zip_code' => 'nullable|string|max:9',
                'status' => 'required|in:active,inactive,vacation,leave',
            ];

            if ($this->modalAction === 'edit') {
                $rules['email'] .= '|unique:employees,email,' . $this->employeeId;
                $rules['cpf'] .= '|unique:employees,cpf,' . $this->employeeId;
                $rules['registration_number'] .= '|unique:employees,registration_number,' . $this->employeeId;
            } else {
                $rules['email'] .= '|unique:employees,email';
                $rules['cpf'] .= '|unique:employees,cpf';
                $rules['registration_number'] .= '|unique:employees,registration_number';
            }

            $validated = $this->validate($rules);

            \Log::info('Validação passou', ['validated' => $validated]);

            $data = [
                'tenant_id' => $this->tenant_id,
                'name' => $this->name,
                'email' => $this->email,
                'cpf' => $this->cpf,
                'registration_number' => $this->registration_number,
                'phone' => $this->phone,
                'birth_date' => $this->birth_date,
                'position' => $this->position,
                'department' => $this->department,
                'admission_date' => $this->admission_date,
                'salary' => $this->salary,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'zip_code' => $this->zip_code,
                'status' => $this->status,
                'is_active' => $this->status === 'active',
                'work_schedule_id' => $this->work_schedule_id ?: null,
            ];

            if ($this->modalAction === 'edit') {
                $employee = Employee::findOrFail($this->employeeId);
                $employee->update($data);
                \Log::info('Funcionário atualizado', ['employee_id' => $employee->id]);
                session()->flash('success', 'Funcionário atualizado com sucesso!');

                $this->closeModal();
                $this->dispatch('employee-saved');
            } else {
                // Gerar código único para PWA (6 dígitos aleatórios)
                do {
                    $uniqueCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (Employee::where('unique_code', $uniqueCode)->exists());

                $data['unique_code'] = $uniqueCode;

                \Log::info('Criando funcionário', ['data' => $data]);

                $employee = Employee::create($data);

                \Log::info('Funcionário criado', ['employee_id' => $employee->id, 'unique_code' => $uniqueCode]);

                // Preparar dados para o modal de código
                $this->generatedCode = $uniqueCode;
                $this->generatedEmployeeName = $employee->name;

                $this->closeModal();
                $this->showCodeModal = true;
                $this->dispatch('employee-saved');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Erro de validação', ['errors' => $e->errors()]);
            session()->flash('error', 'Erro de validação: ' . implode(', ', array_map(fn($arr) => implode(', ', $arr), $e->errors())));
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Erro ao salvar funcionário', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            session()->flash('error', 'Erro ao salvar funcionário: ' . $e->getMessage());
        }
    }

    public function deleteEmployee($id)
    {
        Employee::findOrFail($id)->delete();
        session()->flash('success', 'Funcionário excluído com sucesso!');
    }

    public function toggleStatus($id)
    {
        $employee = Employee::findOrFail($id);
        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';

        $employee->update([
            'status' => $newStatus,
            'is_active' => $newStatus === 'active',
        ]);

        session()->flash('success', 'Status do funcionário atualizado com sucesso!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function showEmployeeCode($id)
    {
        $employee = Employee::findOrFail($id);

        if (!$employee->unique_code) {
            session()->flash('error', 'Este funcionário não possui código de acesso cadastrado.');
            return;
        }

        $this->generatedCode = $employee->unique_code;
        $this->generatedEmployeeName = $employee->name;
        $this->showCodeModal = true;
    }

    public function closeCodeModal()
    {
        $this->showCodeModal = false;
        $this->generatedCode = '';
        $this->generatedEmployeeName = '';
    }

    private function resetForm()
    {
        $this->reset([
            'employeeId',
            'tenant_id',
            'name',
            'email',
            'cpf',
            'registration_number',
            'phone',
            'birth_date',
            'position',
            'department',
            'admission_date',
            'salary',
            'address',
            'city',
            'state',
            'zip_code',
            'work_schedule_id',
        ]);
        $this->status = 'active';
        $this->resetErrorBag();
    }
}; ?>

<div>
    @section('page-title', 'Funcionários')

    <!-- Mensagens de Sucesso/Erro -->
    @if (session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-green-800 font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if (session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
        class="mb-6 bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span class="text-red-800 font-medium">{{ session('error') }}</span>
    </div>
    @endif

    <!-- Filtros e Ações -->
    <div class="mb-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Busca -->
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                        placeholder="Buscar por nome, email, CPF ou matrícula...">
                </div>
            </div>

            <!-- Filtros -->
            <div class="flex flex-col sm:flex-row gap-3">
                @if(auth()->user()->isSuperAdmin())
                <select wire:model.live="tenantFilter"
                    class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                    <option value="">Todas as Empresas</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                @endif

                <select wire:model.live="statusFilter"
                    class="px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                    <option value="">Todos os Status</option>
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                    <option value="vacation">Férias</option>
                    <option value="leave">Afastado</option>
                </select>

                <button wire:click="openCreateModal"
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center whitespace-nowrap">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Novo Funcionário
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Funcionários -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs font-semibold uppercase bg-gradient-to-r from-gray-50 to-slate-50 text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">Funcionário</th>
                        @if(auth()->user()->isSuperAdmin())
                        <th scope="col" class="px-6 py-4">Empresa</th>
                        @endif
                        <th scope="col" class="px-6 py-4">CPF</th>
                        <th scope="col" class="px-6 py-4">Matrícula</th>
                        <th scope="col" class="px-6 py-4">Cargo</th>
                        <th scope="col" class="px-6 py-4">Departamento</th>
                        <th scope="col" class="px-6 py-4">Jornada</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4">Admissão</th>
                        <th scope="col" class="px-6 py-4 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr class="bg-white border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold mr-3">
                                    {{ $employee->initials }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $employee->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $employee->email }}</div>
                                </div>
                            </div>
                        </td>
                        @if(auth()->user()->isSuperAdmin())
                        <td class="px-6 py-4">
                            <span class="text-gray-700 font-medium">{{ $employee->tenant->name }}</span>
                        </td>
                        @endif
                        <td class="px-6 py-4 text-gray-600">{{ $employee->formatted_cpf }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold">
                                {{ $employee->registration_number }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $employee->position ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $employee->department ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($employee->workSchedule)
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $employee->workSchedule->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $employee->workSchedule->weekly_hours }}h/sem</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-400 text-sm">Sem jornada</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-{{ $employee->status_color }}-500 rounded-full mr-2 {{ $employee->status === 'active' ? 'animate-pulse' : '' }}"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-{{ $employee->status_color }}-100 to-{{ $employee->status_color }}-100 text-{{ $employee->status_color }}-700 rounded-lg text-xs font-semibold">
                                    {{ $employee->status_text }}
                                </span>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $employee->admission_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                @if($employee->unique_code)
                                <button wire:click="showEmployeeCode({{ $employee->id }})"
                                    class="p-2 text-purple-600 hover:bg-purple-100 rounded-lg transition-all duration-200"
                                    title="Ver Código de Acesso">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </button>
                                @endif

                                <button wire:click="openEditModal({{ $employee->id }})"
                                    class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-all duration-200"
                                    title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <button wire:click="toggleStatus({{ $employee->id }})"
                                    wire:confirm="Tem certeza que deseja alterar o status deste funcionário?"
                                    class="p-2 text-{{ $employee->status === 'active' ? 'red' : 'green' }}-600 hover:bg-{{ $employee->status === 'active' ? 'red' : 'green' }}-100 rounded-lg transition-all duration-200"
                                    title="{{ $employee->status === 'active' ? 'Inativar' : 'Ativar' }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                </button>

                                <button wire:click="deleteEmployee({{ $employee->id }})"
                                    wire:confirm="Tem certeza que deseja excluir este funcionário? Esta ação não pode ser desfeita."
                                    class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-all duration-200"
                                    title="Excluir">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isSuperAdmin() ? '9' : '8' }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <p class="text-gray-500 font-medium">Nenhum funcionário encontrado</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        @if($employees->hasPages())
        <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-blue-50 border-t border-gray-200">
            {{ $employees->links() }}
        </div>
        @endif
    </div>

    <!-- Modal de Criar/Editar -->
    @if($showModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">
                        {{ $modalAction === 'edit' ? 'Editar Funcionário' : 'Novo Funcionário' }}
                    </h3>
                    <button wire:click="closeModal" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <form wire:submit="saveEmployee" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Empresa -->
                    @if(auth()->user()->isSuperAdmin())
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Empresa *</label>
                        <select wire:model="tenant_id" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">Selecione uma empresa</option>
                            @foreach($tenants as $tenant)
                                <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                            @endforeach
                        </select>
                        @error('tenant_id') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                    @else
                    <!-- Campo oculto para admin-tenant -->
                    <input type="hidden" wire:model="tenant_id" value="{{ auth()->user()->tenant_id }}">
                    @endif

                    <!-- Nome -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                        <input wire:model="name" type="text" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('name') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input wire:model="email" type="email" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('email') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- CPF -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CPF *</label>
                        <input wire:model="cpf" type="text" required maxlength="14" placeholder="000.000.000-00"
                            x-data x-on:input="$el.value = $el.value.replace(/\D/g, '').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('cpf') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Matrícula -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Matrícula *</label>
                        <input wire:model="registration_number" type="text" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('registration_number') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Telefone -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
                        <input wire:model="phone" type="text" placeholder="(00) 00000-0000"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('phone') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Data de Nascimento -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Nascimento</label>
                        <input wire:model="birth_date" type="date"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('birth_date') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Data de Admissão -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data de Admissão *</label>
                        <input wire:model="admission_date" type="date" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('admission_date') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Cargo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cargo</label>
                        <input wire:model="position" type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('position') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Departamento -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Departamento</label>
                        <input wire:model="department" type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('department') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Salário -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Salário</label>
                        <input wire:model="salary" type="number" step="0.01" min="0"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('salary') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Status *</label>
                        <select wire:model="status" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                            <option value="vacation">Férias</option>
                            <option value="leave">Afastado</option>
                        </select>
                        @error('status') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Jornada de Trabalho -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Jornada de Trabalho
                        </label>
                        <select wire:model="work_schedule_id"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">Nenhuma jornada vinculada</option>
                            @foreach($workSchedules as $schedule)
                                <option value="{{ $schedule->id }}">
                                    {{ $schedule->name }} ({{ $schedule->code }}) - {{ $schedule->weekly_hours }}h/sem
                                </option>
                            @endforeach
                        </select>
                        @error('work_schedule_id') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                        @if(empty($workSchedules) || $workSchedules->count() === 0)
                            <p class="text-sm text-amber-600 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Nenhuma jornada cadastrada.
                                <a href="{{ route('admin.work-schedules.index') }}" class="underline hover:text-amber-700">Cadastrar agora</a>
                            </p>
                        @endif
                    </div>

                    <!-- Endereço -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Endereço</label>
                        <input wire:model="address" type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('address') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Cidade -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Cidade</label>
                        <input wire:model="city" type="text"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('city') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Estado -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                        <input wire:model="state" type="text" maxlength="2" placeholder="SP"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('state') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- CEP -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">CEP</label>
                        <input wire:model="zip_code" type="text" maxlength="9" placeholder="00000-000"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        @error('zip_code') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                    <button type="button" wire:click="closeModal"
                        class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <span wire:loading wire:target="saveEmployee">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                        <span wire:loading.remove wire:target="saveEmployee">
                            {{ $modalAction === 'edit' ? 'Atualizar' : 'Cadastrar' }}
                        </span>
                        <span wire:loading wire:target="saveEmployee">
                            {{ $modalAction === 'edit' ? 'Atualizando...' : 'Cadastrando...' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal de Código Único -->
    @if($showCodeModal)
    <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4" wire:click.self="closeCodeModal">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full mx-auto overflow-hidden transform transition-all animate-modal-appear"
            onclick="event.stopPropagation()">

            <!-- Header com Gradiente -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-center">
                <div class="flex justify-center mb-3">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-bold text-white mb-1">
                    Código de Acesso
                </h3>
                <p class="text-blue-100">
                    {{ $generatedEmployeeName }}
                </p>
            </div>

            <!-- Corpo do Modal -->
            <div class="p-6">
                <!-- Código em Destaque -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl py-12 px-6 border-2 border-blue-200 mb-4">
                    <div class="flex items-center justify-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        <p class="text-gray-700 font-semibold text-lg">Código Único</p>
                    </div>

                    <div class="text-center">
                        <p class="text-9xl font-bold text-blue-600 font-mono tracking-wider" style="letter-spacing: 0.20em;">
                            {{ $generatedCode }}
                        </p>
                    </div>
                </div>

                <!-- Instruções -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-yellow-800 mb-1">Importante!</p>
                            <p class="text-sm text-yellow-700">
                                Este código é único e necessário para o funcionário acessar o sistema de registro de ponto via PWA.
                                Certifique-se de anotá-lo e entregá-lo ao funcionário com segurança.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <div class="flex gap-2">
                    <button type="button" onclick="navigator.clipboard.writeText('{{ $generatedCode }}').then(() => alert('Código copiado!'))"
                        class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200 flex items-center justify-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Copiar
                    </button>
                    <button type="button" wire:click="closeCodeModal"
                        class="flex-1 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes modal-appear {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        .animate-modal-appear {
            animation: modal-appear 0.3s ease-out;
        }
    </style>
    @endif
</div>
