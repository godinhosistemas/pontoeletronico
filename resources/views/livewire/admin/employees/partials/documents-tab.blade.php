<!-- Aba 2: Documentação -->
<div x-show="currentTab === 'documents'" x-transition>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- CTPS -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CTPS (Carteira de Trabalho)</label>
            <input type="text" wire:model="ctps" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0000000">
            @error('ctps') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- CTPS Série -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CTPS Série</label>
            <input type="text" wire:model="ctps_series" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0000">
            @error('ctps_series') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- CTPS UF -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CTPS UF</label>
            <input type="text" wire:model="ctps_uf" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="SP" maxlength="2">
            @error('ctps_uf') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- PIS/PASEP -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">PIS/PASEP</label>
            <input type="text" wire:model="pis_pasep" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="000.00000.00-0">
            @error('pis_pasep') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Título de Eleitor -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Título de Eleitor</label>
            <input type="text" wire:model="voter_registration" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0000 0000 0000">
            @error('voter_registration') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Zona Eleitoral -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Zona Eleitoral</label>
            <input type="text" wire:model="voter_zone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="000">
            @error('voter_zone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Seção Eleitoral -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Seção Eleitoral</label>
            <input type="text" wire:model="voter_section" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0000">
            @error('voter_section') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Certificado Militar -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Certificado Militar</label>
            <input type="text" wire:model="military_certificate" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="000000">
            @error('military_certificate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- CNH -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">CNH</label>
            <input type="text" wire:model="cnh" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="00000000000">
            @error('cnh') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Categoria CNH -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Categoria CNH</label>
            <select wire:model="cnh_category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Selecione</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="C">C</option>
                <option value="D">D</option>
                <option value="E">E</option>
            </select>
            @error('cnh_category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Validade CNH -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Validade CNH</label>
            <input type="date" wire:model="cnh_expiry" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            @error('cnh_expiry') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

    </div>
</div>
