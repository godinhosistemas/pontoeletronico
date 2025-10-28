<!-- Aba 1: Dados Pessoais -->
<div x-show="currentTab === 'personal'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Foto -->
        <div class="md:col-span-2 lg:col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-2">Foto</label>
            <div class="flex items-center gap-4">
                @if ($existingPhoto)
                    <img src="{{ Storage::url($existingPhoto) }}" class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">
                @else
                    <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                @endif
                <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Nome -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" wire:model="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Digite o nome completo">
            @error('name') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">E-mail *</label>
            <input type="email" wire:model="email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="email@exemplo.com">
            @error('email') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- CPF -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
            <input type="text" wire:model="cpf" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('cpf') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="000.000.000-00" maxlength="14">
            @error('cpf') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- RG -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">RG</label>
            <input type="text" wire:model="rg" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="00.000.000-0">
            @error('rg') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Órgão Emissor RG -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Órgão Emissor</label>
            <input type="text" wire:model="rg_issuer" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="SSP-SP">
            @error('rg_issuer') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data Emissão RG -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Emissão RG</label>
            <input type="date" wire:model="rg_issue_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('rg_issue_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Data de Nascimento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
            <input type="date" wire:model="birth_date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('birth_date') border-red-500 bg-red-50 @else border-gray-300 @enderror">
            @error('birth_date') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Gênero -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Gênero</label>
            <select wire:model="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="M">Masculino</option>
                <option value="F">Feminino</option>
                <option value="Outro">Outro</option>
            </select>
            @error('gender') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Estado Civil -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado Civil</label>
            <select wire:model="marital_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="Solteiro(a)">Solteiro(a)</option>
                <option value="Casado(a)">Casado(a)</option>
                <option value="Divorciado(a)">Divorciado(a)</option>
                <option value="Viúvo(a)">Viúvo(a)</option>
                <option value="União Estável">União Estável</option>
            </select>
            @error('marital_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Nacionalidade -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nacionalidade</label>
            <input type="text" wire:model="nationality" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Brasileira">
            @error('nationality') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Naturalidade -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Naturalidade</label>
            <input type="text" wire:model="birth_place" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Cidade-UF">
            @error('birth_place') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Nome da Mãe -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Mãe</label>
            <input type="text" wire:model="mothers_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nome completo da mãe">
            @error('mothers_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Nome do Pai -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Pai</label>
            <input type="text" wire:model="fathers_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Nome completo do pai">
            @error('fathers_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Escolaridade -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Escolaridade</label>
            <select wire:model="education_level" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="Fundamental Incompleto">Fundamental Incompleto</option>
                <option value="Fundamental Completo">Fundamental Completo</option>
                <option value="Médio Incompleto">Médio Incompleto</option>
                <option value="Médio Completo">Médio Completo</option>
                <option value="Superior Incompleto">Superior Incompleto</option>
                <option value="Superior Completo">Superior Completo</option>
                <option value="Pós-Graduação">Pós-Graduação</option>
                <option value="Mestrado">Mestrado</option>
                <option value="Doutorado">Doutorado</option>
            </select>
            @error('education_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>
</div>
