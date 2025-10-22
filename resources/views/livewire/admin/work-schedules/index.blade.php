<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\WorkSchedule;
use Illuminate\Support\Str;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $modalAction = 'create';

    // Form fields
    public $scheduleId;
    public $name = '';
    public $code = '';
    public $description = '';
    public $weekly_hours = 44;
    public $daily_hours = 8;
    public $break_minutes = 60;
    public $default_start_time = '08:00';
    public $default_end_time = '17:00';
    public $default_break_start = '12:00';
    public $default_break_end = '13:00';
    public $tolerance_minutes_entry = 10;
    public $tolerance_minutes_exit = 10;
    public $consider_holidays = true;
    public $allow_overtime = true;
    public $is_active = true;

    // Days configuration
    public $days = [
        'monday' => ['active' => true, 'label' => 'Segunda-feira'],
        'tuesday' => ['active' => true, 'label' => 'Terça-feira'],
        'wednesday' => ['active' => true, 'label' => 'Quarta-feira'],
        'thursday' => ['active' => true, 'label' => 'Quinta-feira'],
        'friday' => ['active' => true, 'label' => 'Sexta-feira'],
        'saturday' => ['active' => false, 'label' => 'Sábado'],
        'sunday' => ['active' => false, 'label' => 'Domingo'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $schedules = WorkSchedule::where('tenant_id', auth()->user()->tenant_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return [
            'schedules' => $schedules,
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
        $schedule = WorkSchedule::findOrFail($id);

        $this->scheduleId = $schedule->id;
        $this->name = $schedule->name;
        $this->code = $schedule->code;
        $this->description = $schedule->description;
        $this->weekly_hours = $schedule->weekly_hours;
        $this->daily_hours = $schedule->daily_hours;
        $this->break_minutes = $schedule->break_minutes;
        $this->default_start_time = $schedule->default_start_time;
        $this->default_end_time = $schedule->default_end_time;
        $this->default_break_start = $schedule->default_break_start;
        $this->default_break_end = $schedule->default_break_end;
        $this->tolerance_minutes_entry = $schedule->tolerance_minutes_entry;
        $this->tolerance_minutes_exit = $schedule->tolerance_minutes_exit;
        $this->consider_holidays = $schedule->consider_holidays;
        $this->allow_overtime = $schedule->allow_overtime;
        $this->is_active = $schedule->is_active;

        // Load days config
        if ($schedule->days_config) {
            foreach ($schedule->days_config as $day => $config) {
                if (isset($this->days[$day])) {
                    $this->days[$day]['active'] = $config['active'] ?? false;
                }
            }
        }

        $this->modalAction = 'edit';
        $this->showModal = true;
    }

    public function saveSchedule()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:work_schedules,code,' . $this->scheduleId,
            'description' => 'nullable|string',
            'weekly_hours' => 'required|integer|min:1|max:60',
            'daily_hours' => 'required|integer|min:1|max:24',
            'break_minutes' => 'required|integer|min:0|max:480',
            'default_start_time' => 'nullable|date_format:H:i',
            'default_end_time' => 'nullable|date_format:H:i',
            'default_break_start' => 'nullable|date_format:H:i',
            'default_break_end' => 'nullable|date_format:H:i',
            'tolerance_minutes_entry' => 'required|integer|min:0|max:120',
            'tolerance_minutes_exit' => 'required|integer|min:0|max:120',
        ];

        $validated = $this->validate($rules);

        $data = [
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $this->name,
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'weekly_hours' => $this->weekly_hours,
            'daily_hours' => $this->daily_hours,
            'break_minutes' => $this->break_minutes,
            'default_start_time' => $this->default_start_time,
            'default_end_time' => $this->default_end_time,
            'default_break_start' => $this->default_break_start,
            'default_break_end' => $this->default_break_end,
            'tolerance_minutes_entry' => $this->tolerance_minutes_entry,
            'tolerance_minutes_exit' => $this->tolerance_minutes_exit,
            'consider_holidays' => $this->consider_holidays,
            'allow_overtime' => $this->allow_overtime,
            'is_active' => $this->is_active,
            'days_config' => $this->days,
        ];

        try {
            if ($this->modalAction === 'edit') {
                $schedule = WorkSchedule::findOrFail($this->scheduleId);
                $schedule->update($data);
                session()->flash('success', 'Jornada atualizada com sucesso!');
            } else {
                WorkSchedule::create($data);
                session()->flash('success', 'Jornada criada com sucesso!');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao salvar jornada: ' . $e->getMessage());
        }
    }

    public function deleteSchedule($id)
    {
        try {
            $schedule = WorkSchedule::findOrFail($id);

            // Verifica se há funcionários vinculados
            if ($schedule->employees()->count() > 0) {
                session()->flash('error', 'Não é possível excluir esta jornada pois existem funcionários vinculados.');
                return;
            }

            $schedule->delete();
            session()->flash('success', 'Jornada excluída com sucesso!');
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao excluir jornada: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $schedule = WorkSchedule::findOrFail($id);
            $schedule->update(['is_active' => !$schedule->is_active]);
            session()->flash('success', 'Status atualizado com sucesso!');
        } catch (\Exception $e) {
            session()->flash('error', 'Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->scheduleId = null;
        $this->name = '';
        $this->code = '';
        $this->description = '';
        $this->weekly_hours = 44;
        $this->daily_hours = 8;
        $this->break_minutes = 60;
        $this->default_start_time = '08:00';
        $this->default_end_time = '17:00';
        $this->default_break_start = '12:00';
        $this->default_break_end = '13:00';
        $this->tolerance_minutes_entry = 10;
        $this->tolerance_minutes_exit = 10;
        $this->consider_holidays = true;
        $this->allow_overtime = true;
        $this->is_active = true;

        // Reset days
        $this->days = [
            'monday' => ['active' => true, 'label' => 'Segunda-feira'],
            'tuesday' => ['active' => true, 'label' => 'Terça-feira'],
            'wednesday' => ['active' => true, 'label' => 'Quarta-feira'],
            'thursday' => ['active' => true, 'label' => 'Quinta-feira'],
            'friday' => ['active' => true, 'label' => 'Sexta-feira'],
            'saturday' => ['active' => false, 'label' => 'Sábado'],
            'sunday' => ['active' => false, 'label' => 'Domingo'],
        ];
    }
};
?>

<div class="p-6">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Jornadas de Trabalho
            </h1>
            <p class="text-gray-600 mt-1">Gerencie as escalas e horários de trabalho</p>
        </div>
        <button wire:click="openCreateModal" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nova Jornada
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Search Bar -->
    <div class="mb-6">
        <div class="relative">
            <input wire:model.live="search" type="text" placeholder="Buscar por nome, código ou descrição..."
                class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
            <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs font-semibold uppercase bg-gradient-to-r from-gray-50 to-slate-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-gray-700">Nome</th>
                        <th class="px-6 py-4 text-gray-700">Código</th>
                        <th class="px-6 py-4 text-gray-700">Horas Semanais</th>
                        <th class="px-6 py-4 text-gray-700">Horário Padrão</th>
                        <th class="px-6 py-4 text-gray-700">Dias Ativos</th>
                        <th class="px-6 py-4 text-gray-700">Status</th>
                        <th class="px-6 py-4 text-gray-700 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-150">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $schedule->name }}</div>
                                @if($schedule->description)
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($schedule->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-lg">
                                    {{ $schedule->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                {{ $schedule->weekly_hours }}h/sem
                            </td>
                            <td class="px-6 py-4 text-gray-700">
                                @if($schedule->default_start_time && $schedule->default_end_time)
                                    {{ substr($schedule->default_start_time, 0, 5) }} - {{ substr($schedule->default_end_time, 0, 5) }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700">{{ count($schedule->getWorkingDays()) }} dias</span>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $schedule->id }})"
                                    class="px-3 py-1 text-xs font-semibold rounded-lg transition-all {{ $schedule->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                    {{ $schedule->is_active ? 'Ativo' : 'Inativo' }}
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="openEditModal({{ $schedule->id }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Editar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button wire:click="deleteSchedule({{ $schedule->id }})"
                                        wire:confirm="Tem certeza que deseja excluir esta jornada?"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Excluir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-lg font-semibold">Nenhuma jornada encontrada</p>
                                <p class="text-sm mt-1">Comece criando uma nova jornada de trabalho</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($schedules->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4" wire:click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $modalAction === 'edit' ? 'Editar Jornada' : 'Nova Jornada' }}
                    </h2>
                    <button wire:click="closeModal" class="p-2 hover:bg-gray-100 rounded-lg transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit="saveSchedule">
                    <div class="p-6 space-y-6">
                        <!-- Informações Básicas -->
                        <div class="bg-gray-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome da Jornada *</label>
                                    <input wire:model="name" type="text" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Ex: Jornada Padrão 44h">
                                    @error('name') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Código *</label>
                                    <input wire:model="code" type="text" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all uppercase"
                                        placeholder="Ex: JOR-001">
                                    @error('code') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input wire:model="is_active" type="checkbox" class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        <span class="text-sm font-semibold text-gray-700">Jornada Ativa</span>
                                    </label>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                                    <textarea wire:model="description" rows="2"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                        placeholder="Descrição opcional da jornada"></textarea>
                                    @error('description') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Carga Horária -->
                        <div class="bg-blue-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Carga Horária</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Horas Semanais *</label>
                                    <input wire:model="weekly_hours" type="number" min="1" max="60" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('weekly_hours') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Horas Diárias *</label>
                                    <input wire:model="daily_hours" type="number" min="1" max="24" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('daily_hours') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Intervalo (minutos) *</label>
                                    <input wire:model="break_minutes" type="number" min="0" max="480" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('break_minutes') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Horários Padrão -->
                        <div class="bg-indigo-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Horários Padrão</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Horário de Entrada</label>
                                    <input wire:model="default_start_time" type="time"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('default_start_time') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Horário de Saída</label>
                                    <input wire:model="default_end_time" type="time"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('default_end_time') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Início do Intervalo</label>
                                    <input wire:model="default_break_start" type="time"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('default_break_start') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Fim do Intervalo</label>
                                    <input wire:model="default_break_end" type="time"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('default_break_end') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dias da Semana -->
                        <div class="bg-green-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dias de Trabalho</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($days as $key => $day)
                                    <label class="flex items-center gap-3 p-3 bg-white rounded-lg cursor-pointer hover:bg-gray-50 transition-all">
                                        <input wire:model="days.{{ $key }}.active" type="checkbox"
                                            class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-700">{{ $day['label'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Tolerâncias -->
                        <div class="bg-yellow-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tolerâncias (minutos)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tolerância Entrada *</label>
                                    <input wire:model="tolerance_minutes_entry" type="number" min="0" max="120" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('tolerance_minutes_entry') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tolerância Saída *</label>
                                    <input wire:model="tolerance_minutes_exit" type="number" min="0" max="120" required
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('tolerance_minutes_exit') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Configurações Adicionais -->
                        <div class="bg-purple-50 rounded-xl p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Configurações Adicionais</h3>
                            <div class="flex flex-col gap-3">
                                <label class="flex items-center gap-3 p-3 bg-white rounded-lg cursor-pointer hover:bg-gray-50 transition-all">
                                    <input wire:model="consider_holidays" type="checkbox"
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 block">Considerar Feriados</span>
                                        <span class="text-xs text-gray-500">Não contabilizar trabalho em feriados</span>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3 bg-white rounded-lg cursor-pointer hover:bg-gray-50 transition-all">
                                    <input wire:model="allow_overtime" type="checkbox"
                                        class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 block">Permitir Horas Extras</span>
                                        <span class="text-xs text-gray-500">Funcionários podem fazer horas além do expediente</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" wire:click="closeModal"
                            class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all duration-200">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                            {{ $modalAction === 'edit' ? 'Atualizar' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
