<?php

use Livewire\Volt\Component;
use App\Models\TimeEntry;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $employee;
    public $todayEntry;
    public $currentTime;

    public function mount()
    {
        $this->loadEmployee();
        $this->loadTodayEntry();
        $this->currentTime = now()->format('H:i:s');
    }

    public function loadEmployee()
    {
        $user = auth()->user();

        // Primeiro tenta buscar pelo email
        $this->employee = Employee::where('tenant_id', $user->tenant_id)
            ->where('email', $user->email)
            ->where('is_active', true)
            ->first();

        // Se não encontrar, cria um registro temporário para o usuário poder usar
        // (Em produção, você deve criar o employee quando criar o user)
        if (!$this->employee && !$user->isSuperAdmin()) {
            // Por enquanto, vamos apenas informar que não há funcionário
            // Você pode criar automaticamente se preferir
        }
    }

    public function loadTodayEntry()
    {
        if ($this->employee) {
            $this->todayEntry = TimeEntry::where('employee_id', $this->employee->id)
                ->where('date', today())
                ->first();
        }
    }

    public function clockIn()
    {
        if (!$this->employee) {
            session()->flash('error', 'Funcionário não encontrado. Entre em contato com o RH.');
            return;
        }

        if ($this->todayEntry && $this->todayEntry->clock_in) {
            session()->flash('error', 'Você já registrou entrada hoje.');
            return;
        }

        try {
            DB::beginTransaction();

            $this->todayEntry = TimeEntry::create([
                'employee_id' => $this->employee->id,
                'tenant_id' => $this->employee->tenant_id,
                'date' => today(),
                'clock_in' => now()->format('H:i:s'),
                'ip_address' => request()->ip(),
                'type' => 'normal',
                'status' => 'pending',
            ]);

            DB::commit();
            session()->flash('success', 'Entrada registrada com sucesso às ' . now()->format('H:i'));
            $this->loadTodayEntry();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Erro ao registrar entrada: ' . $e->getMessage());
        }
    }

    public function startLunch()
    {
        if (!$this->todayEntry || !$this->todayEntry->clock_in) {
            session()->flash('error', 'Você precisa registrar entrada antes de iniciar o almoço.');
            return;
        }

        if ($this->todayEntry->lunch_start) {
            session()->flash('error', 'Você já iniciou o almoço hoje.');
            return;
        }

        try {
            $this->todayEntry->update([
                'lunch_start' => now()->format('H:i:s'),
            ]);

            session()->flash('success', 'Início do almoço registrado às ' . now()->format('H:i'));
            $this->loadTodayEntry();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao registrar início do almoço: ' . $e->getMessage());
        }
    }

    public function endLunch()
    {
        if (!$this->todayEntry || !$this->todayEntry->lunch_start) {
            session()->flash('error', 'Você precisa iniciar o almoço antes de finalizá-lo.');
            return;
        }

        if ($this->todayEntry->lunch_end) {
            session()->flash('error', 'Você já finalizou o almoço hoje.');
            return;
        }

        try {
            $this->todayEntry->update([
                'lunch_end' => now()->format('H:i:s'),
            ]);

            session()->flash('success', 'Fim do almoço registrado às ' . now()->format('H:i'));
            $this->loadTodayEntry();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao registrar fim do almoço: ' . $e->getMessage());
        }
    }

    public function clockOut()
    {
        if (!$this->todayEntry || !$this->todayEntry->clock_in) {
            session()->flash('error', 'Você precisa registrar entrada antes de registrar saída.');
            return;
        }

        if ($this->todayEntry->clock_out) {
            session()->flash('error', 'Você já registrou saída hoje.');
            return;
        }

        try {
            $this->todayEntry->update([
                'clock_out' => now()->format('H:i:s'),
            ]);

            $this->todayEntry->calculateTotalHours();

            session()->flash('success', 'Saída registrada com sucesso às ' . now()->format('H:i'));
            $this->loadTodayEntry();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao registrar saída: ' . $e->getMessage());
        }
    }

    public function with()
    {
        // Atualiza o tempo atual
        $this->currentTime = now()->format('H:i:s');

        // Busca últimos registros
        $recentEntries = [];
        if ($this->employee) {
            $recentEntries = TimeEntry::where('employee_id', $this->employee->id)
                ->where('date', '>=', today()->subDays(7))
                ->orderBy('date', 'desc')
                ->limit(7)
                ->get();
        }

        return [
            'recentEntries' => $recentEntries,
        ];
    }
}; ?>

<div>
    @section('page-title', 'Registro de Ponto')

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Relógio e Status Atual -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <div class="text-center">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ now()->format('d/m/Y') }}</h2>
                    <div class="text-6xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent"
                         x-data="{ time: '{{ now()->format('H:i:s') }}' }"
                         x-init="setInterval(() => { time = new Date().toLocaleTimeString('pt-BR') }, 1000)">
                        <span x-text="time"></span>
                    </div>
                </div>

                @if($employee)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600">Funcionário</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $employee->name }}</p>
                    <p class="text-sm text-gray-600">{{ $employee->position ?? 'Sem cargo definido' }}</p>
                </div>
                @else
                <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Funcionário não cadastrado</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Não foi encontrado um cadastro de funcionário vinculado ao seu usuário ({{ auth()->user()->email }}).</p>
                                <p class="mt-2">Para usar o registro de ponto, você precisa:</p>
                                <ol class="list-decimal ml-5 mt-1">
                                    <li>Acessar "Funcionários" no menu</li>
                                    <li>Cadastrar um funcionário com o email: <strong>{{ auth()->user()->email }}</strong></li>
                                    <li>Voltar a esta página</li>
                                </ol>
                            </div>
                            @can('employees.view')
                            <div class="mt-4">
                                <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-all">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Cadastrar Funcionário
                                </a>
                            </div>
                            @endcan
                        </div>
                    </div>
                </div>
                @endif

                <!-- Status do dia -->
                @if($todayEntry)
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Entrada</p>
                        <p class="text-lg font-bold {{ $todayEntry->clock_in ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $todayEntry->clock_in ?? '--:--' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Saída</p>
                        <p class="text-lg font-bold {{ $todayEntry->clock_out ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $todayEntry->clock_out ?? '--:--' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Início Almoço</p>
                        <p class="text-lg font-bold {{ $todayEntry->lunch_start ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $todayEntry->lunch_start ?? '--:--' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs text-gray-500">Fim Almoço</p>
                        <p class="text-lg font-bold {{ $todayEntry->lunch_end ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $todayEntry->lunch_end ?? '--:--' }}
                        </p>
                    </div>
                </div>

                @if($todayEntry->total_hours)
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 mb-6">
                    <p class="text-sm text-gray-600">Total Trabalhado</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($todayEntry->total_hours, 2) }}h</p>
                </div>
                @endif
                @endif
            </div>

            <!-- Botões de Ação -->
            @if($employee)
            <div class="grid grid-cols-2 gap-3">
                <button wire:click="clockIn"
                        {{ $todayEntry && $todayEntry->clock_in ? 'disabled' : '' }}
                        class="py-3 px-4 rounded-lg font-semibold transition-all {{ $todayEntry && $todayEntry->clock_in ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-green-500 to-emerald-500 text-white hover:from-green-600 hover:to-emerald-600 shadow-lg' }}">
                    Registrar Entrada
                </button>

                <button wire:click="startLunch"
                        {{ !$todayEntry || !$todayEntry->clock_in || $todayEntry->lunch_start ? 'disabled' : '' }}
                        class="py-3 px-4 rounded-lg font-semibold transition-all {{ !$todayEntry || !$todayEntry->clock_in || $todayEntry->lunch_start ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white hover:from-yellow-600 hover:to-orange-600 shadow-lg' }}">
                    Iniciar Almoço
                </button>

                <button wire:click="endLunch"
                        {{ !$todayEntry || !$todayEntry->lunch_start || $todayEntry->lunch_end ? 'disabled' : '' }}
                        class="py-3 px-4 rounded-lg font-semibold transition-all {{ !$todayEntry || !$todayEntry->lunch_start || $todayEntry->lunch_end ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-orange-500 to-amber-500 text-white hover:from-orange-600 hover:to-amber-600 shadow-lg' }}">
                    Finalizar Almoço
                </button>

                <button wire:click="clockOut"
                        {{ !$todayEntry || !$todayEntry->clock_in || $todayEntry->clock_out ? 'disabled' : '' }}
                        class="py-3 px-4 rounded-lg font-semibold transition-all {{ !$todayEntry || !$todayEntry->clock_in || $todayEntry->clock_out ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-red-500 to-rose-500 text-white hover:from-red-600 hover:to-rose-600 shadow-lg' }}">
                    Registrar Saída
                </button>
            </div>
            @endif
        </div>

        <!-- Histórico Recente -->
        <div class="bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Últimos Registros</h3>

            <div class="space-y-3">
                @forelse($recentEntries as $entry)
                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-all">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($entry->date)->format('d/m/Y') }}</p>
                            <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($entry->date)->locale('pt_BR')->isoFormat('dddd') }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $entry->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $entry->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $entry->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $entry->status === 'approved' ? 'Aprovado' : '' }}
                            {{ $entry->status === 'pending' ? 'Pendente' : '' }}
                            {{ $entry->status === 'rejected' ? 'Rejeitado' : '' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-gray-600">Entrada:</span>
                            <span class="font-semibold text-gray-900">{{ $entry->clock_in ?? '--:--' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Saída:</span>
                            <span class="font-semibold text-gray-900">{{ $entry->clock_out ?? '--:--' }}</span>
                        </div>
                    </div>

                    @if($entry->total_hours)
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <span class="text-xs text-gray-600">Total: </span>
                        <span class="text-sm font-bold text-blue-600">{{ number_format($entry->total_hours, 2) }}h</span>
                    </div>
                    @endif
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Nenhum registro encontrado</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
