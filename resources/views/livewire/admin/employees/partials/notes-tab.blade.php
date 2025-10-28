<!-- Aba 7: Observações -->
<div x-show="currentTab === 'notes'" x-transition>
    <div class="grid grid-cols-1 gap-6">

        <!-- Observações -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Observações Gerais</label>
            <textarea wire:model="notes" rows="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Adicione observações, anotações ou informações adicionais sobre o colaborador..."></textarea>
            @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-blue-800 mb-1">Dicas para Observações:</h4>
                    <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                        <li>Registre informações importantes sobre o colaborador</li>
                        <li>Adicione detalhes sobre habilidades especiais ou certificações</li>
                        <li>Anote preferências de trabalho ou restrições</li>
                        <li>Documente histórico de treinamentos</li>
                        <li>Registre informações relevantes para gestão</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
