<div>
    <form wire:submit.prevent="save" x-data="{ currentTab: 'personal' }">

        <!-- Alerta de Erros de Validação -->
        @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6 shadow-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-bold text-red-800 mb-2">
                        Por favor, corrija os seguintes erros:
                    </h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-4 text-red-500 hover:text-red-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Header do Formulário -->
        <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                        {{ $isEditing ? 'Editar Colaborador' : 'Novo Colaborador' }}
                    </h2>
                    <p class="text-gray-600 mt-1">Preencha os dados do colaborador</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.employees.index') }}"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salvar
                    </button>
                </div>
            </div>
        </div>

        <!-- Navegação das Abas -->
        <div class="bg-white rounded-2xl shadow-xl mb-6 overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px overflow-x-auto">
                    <button type="button"
                            @click="currentTab = 'personal'"
                            :class="currentTab === 'personal' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        Dados Pessoais
                    </button>

                    <button type="button"
                            @click="currentTab = 'documents'"
                            :class="currentTab === 'documents' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Documentação
                    </button>

                    <button type="button"
                            @click="currentTab = 'address'"
                            :class="currentTab === 'address' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Endereço e Contato
                    </button>

                    <button type="button"
                            @click="currentTab = 'contract'"
                            :class="currentTab === 'contract' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Dados Contratuais
                    </button>

                    <button type="button"
                            @click="currentTab = 'bank'"
                            :class="currentTab === 'bank' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                        Dados Bancários
                    </button>

                    <button type="button"
                            @click="currentTab = 'health'"
                            :class="currentTab === 'health' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        Saúde
                    </button>

                    <button type="button"
                            @click="currentTab = 'notes'"
                            :class="currentTab === 'notes' ? 'border-blue-600 text-blue-600 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-4 px-6 border-b-2 font-medium text-sm transition-all whitespace-nowrap">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Observações
                    </button>
                </nav>
            </div>

            <!-- Conteúdo das Abas -->
            <div class="p-6">

                @include('livewire.admin.employees.partials.personal-tab')
                @include('livewire.admin.employees.partials.documents-tab')
                @include('livewire.admin.employees.partials.address-tab')
                @include('livewire.admin.employees.partials.contract-tab')
                @include('livewire.admin.employees.partials.bank-tab')
                @include('livewire.admin.employees.partials.health-tab')
                @include('livewire.admin.employees.partials.notes-tab')

            </div>
        </div>

        <!-- Footer -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">* Campos obrigatórios</p>
                <div class="flex gap-3">
                    <a href="{{ route('admin.employees.index') }}"
                       class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Salvar Colaborador
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
