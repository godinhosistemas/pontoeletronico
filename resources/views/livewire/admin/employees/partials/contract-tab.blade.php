<!-- Aba 4: Dados Contratuais -->
<div x-show="currentTab === 'contract'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Matrícula -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número de Matrícula *</label>
            <input type="text" wire:model="registration_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50" placeholder="Auto-gerado" readonly>
            @error('registration_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data de Admissão -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão *</label>
            <input type="date" wire:model="admission_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('admission_date') border-red-500 bg-red-50 @else border-gray-300 @enderror">
            @error('admission_date') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
            <select wire:model="status" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('status') border-red-500 bg-red-50 @else border-gray-300 @enderror">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
                <option value="vacation">Férias</option>
                <option value="leave">Afastado</option>
            </select>
            @error('status') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Cargo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cargo *</label>
            <input type="text" wire:model="position" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('position') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Ex: Analista de Sistemas">
            @error('position') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Departamento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Departamento *</label>
            <input type="text" wire:model="department" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('department') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Ex: TI, Vendas, RH">
            @error('department') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Salário -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Salário (R$) *</label>
            <input type="number" step="0.01" wire:model="salary" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('salary') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="0.00">
            @error('salary') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Tipo de Contrato -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Contrato *</label>
            <select wire:model="contract_type" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('contract_type') border-red-500 bg-red-50 @else border-gray-300 @enderror">
                <option value="CLT">CLT</option>
                <option value="PJ">PJ</option>
                <option value="Estágio">Estágio</option>
                <option value="Temporário">Temporário</option>
                <option value="Autônomo">Autônomo</option>
            </select>
            @error('contract_type') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Número do Contrato -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número do Contrato</label>
            <input type="text" wire:model="contract_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Número do contrato">
            @error('contract_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data Início Contrato -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início Contrato</label>
            <input type="date" wire:model="contract_start_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('contract_start_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data Fim Contrato -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim Contrato</label>
            <input type="date" wire:model="contract_end_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('contract_end_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Carga Horária Semanal -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Carga Horária Semanal (horas) *</label>
            <input type="number" step="0.01" wire:model="workload_hours" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('workload_hours') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="44.00">
            @error('workload_hours') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Jornada de Trabalho -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Jornada de Trabalho</label>
            <select wire:model="work_schedule_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                @foreach($workSchedules as $schedule)
                    <option value="{{ $schedule->id }}">{{ $schedule->name }}</option>
                @endforeach
            </select>
            @error('work_schedule_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Centro de Custo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Centro de Custo</label>
            <input type="text" wire:model="cost_center" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: CC-001">
            @error('cost_center') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Supervisor Imediato -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Supervisor Imediato</label>
            <input type="text" wire:model="immediate_supervisor" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nome do supervisor">
            @error('immediate_supervisor') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Benefícios -->
        <div class="flex items-center">
            <input type="checkbox" wire:model="has_benefits" id="has_benefits" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
            <label for="has_benefits" class="ml-2 text-sm font-medium text-gray-700">Possui Benefícios</label>
        </div>

    </div>
</div>
