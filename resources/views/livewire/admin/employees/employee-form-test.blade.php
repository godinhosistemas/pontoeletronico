<div>
    <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
            Teste - {{ $isEditing ? 'Editar Colaborador' : 'Novo Colaborador' }}
        </h2>
        <p class="text-gray-600 mt-2">Se você está vendo isso, o componente Livewire está funcionando!</p>

        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">✅ Componente carregou com sucesso</p>
            <p class="text-green-700 text-sm mt-1">Matrícula gerada: {{ $registration_number }}</p>
        </div>

        <form wire:submit.prevent="save" class="mt-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                    <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" wire:model="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3">
                    <a href="{{ route('admin.employees.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">
                        Voltar
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg">
                        Testar Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
