<!-- Aba 3: Endereço e Contato -->
<div x-show="currentTab === 'address'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Telefone -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Telefone *</label>
            <input type="text" wire:model="phone" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="(00) 00000-0000">
            @error('phone') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- CEP -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CEP *</label>
            <input type="text" wire:model="zip_code" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('zip_code') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="00000-000" maxlength="9">
            @error('zip_code') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Endereço -->
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
            <input type="text" wire:model="address" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Rua, Avenida, etc.">
            @error('address') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Número -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
            <input type="text" wire:model="address_number" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('address_number') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="123">
            @error('address_number') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Complemento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
            <input type="text" wire:model="address_complement" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Apto, Bloco, etc.">
            @error('address_complement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Bairro -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
            <input type="text" wire:model="neighborhood" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('neighborhood') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Nome do bairro">
            @error('neighborhood') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Cidade -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
            <input type="text" wire:model="city" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('city') border-red-500 bg-red-50 @else border-gray-300 @enderror" placeholder="Nome da cidade">
            @error('city') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

        <!-- Estado -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Estado (UF) *</label>
            <select wire:model="state" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('state') border-red-500 bg-red-50 @else border-gray-300 @enderror">
                <option value="">Selecione</option>
                <option value="AC">AC</option>
                <option value="AL">AL</option>
                <option value="AP">AP</option>
                <option value="AM">AM</option>
                <option value="BA">BA</option>
                <option value="CE">CE</option>
                <option value="DF">DF</option>
                <option value="ES">ES</option>
                <option value="GO">GO</option>
                <option value="MA">MA</option>
                <option value="MT">MT</option>
                <option value="MS">MS</option>
                <option value="MG">MG</option>
                <option value="PA">PA</option>
                <option value="PB">PB</option>
                <option value="PR">PR</option>
                <option value="PE">PE</option>
                <option value="PI">PI</option>
                <option value="RJ">RJ</option>
                <option value="RN">RN</option>
                <option value="RS">RS</option>
                <option value="RO">RO</option>
                <option value="RR">RR</option>
                <option value="SC">SC</option>
                <option value="SP">SP</option>
                <option value="SE">SE</option>
                <option value="TO">TO</option>
            </select>
            @error('state') <span class="text-red-500 text-sm font-semibold flex items-center mt-1"><svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>{{ $message }}</span> @enderror
        </div>

    </div>
</div>
