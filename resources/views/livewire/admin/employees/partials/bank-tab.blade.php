<!-- Aba 5: Dados Bancários -->
<div x-show="currentTab === 'bank'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Nome do Banco -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Banco</label>
            <input type="text" wire:model="bank_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: Banco do Brasil">
            @error('bank_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Código do Banco -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Código do Banco</label>
            <input type="text" wire:model="bank_code" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Ex: 001">
            @error('bank_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Agência -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Agência</label>
            <input type="text" wire:model="bank_agency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0000">
            @error('bank_agency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Conta -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Conta</label>
            <input type="text" wire:model="bank_account" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="00000-0">
            @error('bank_account') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Tipo de Conta -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Conta</label>
            <select wire:model="bank_account_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="Corrente">Corrente</option>
                <option value="Poupança">Poupança</option>
                <option value="Salário">Salário</option>
            </select>
            @error('bank_account_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Chave PIX -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Chave PIX</label>
            <input type="text" wire:model="pix_key" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="CPF, E-mail, Telefone ou Chave Aleatória">
            @error('pix_key') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
