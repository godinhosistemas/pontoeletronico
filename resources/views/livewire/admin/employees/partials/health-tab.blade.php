<!-- Aba 6: Saúde -->
<div x-show="currentTab === 'health'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Tipo Sanguíneo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo Sanguíneo</label>
            <select wire:model="blood_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="A+">A+</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B-">B-</option>
                <option value="AB+">AB+</option>
                <option value="AB-">AB-</option>
                <option value="O+">O+</option>
                <option value="O-">O-</option>
            </select>
            @error('blood_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Plano de Saúde -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Plano de Saúde</label>
            <input type="text" wire:model="health_insurance" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nome do plano">
            @error('health_insurance') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Número do Plano -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número do Plano</label>
            <input type="text" wire:model="health_insurance_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Número da carteirinha">
            @error('health_insurance_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Alergias -->
        <div class="md:col-span-2 lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Alergias</label>
            <textarea wire:model="allergies" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Descreva as alergias conhecidas"></textarea>
            @error('allergies') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Medicações em Uso -->
        <div class="md:col-span-2 lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Medicações em Uso</label>
            <textarea wire:model="medications" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Liste as medicações de uso contínuo"></textarea>
            @error('medications') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Condições de Saúde -->
        <div class="md:col-span-2 lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Condições de Saúde</label>
            <textarea wire:model="health_conditions" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Descreva condições de saúde relevantes (diabetes, hipertensão, etc.)"></textarea>
            @error('health_conditions') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="md:col-span-2 lg:col-span-3">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Exames Médicos</h3>
        </div>

        <!-- Data Exame Admissional -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data do Exame Admissional</label>
            <input type="date" wire:model="admission_exam_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('admission_exam_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data Próximo Exame Periódico -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Próximo Exame Periódico</label>
            <input type="date" wire:model="next_periodic_exam_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('next_periodic_exam_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Número ASO -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número do ASO</label>
            <input type="text" wire:model="aso_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="ASO-000000">
            @error('aso_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
