<?php

use Livewire\Volt\Component;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

new class extends Component {
    public $totalTenants = 0;
    public $activeTenants = 0;
    public $totalSubscriptions = 0;
    public $totalRevenue = 0;
    public $recentTenants = [];

    public function mount(): void
    {
        if (auth()->user()->isSuperAdmin()) {
            $this->totalTenants = Tenant::count();
            $this->activeTenants = Tenant::where('is_active', true)->count();
            $this->totalSubscriptions = Subscription::where('status', 'active')->count();
            $this->totalRevenue = Subscription::where('status', 'active')
                ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
                ->sum('plans.price');
            $this->recentTenants = Tenant::with('activeSubscription.plan')
                ->latest()
                ->take(5)
                ->get();
        }
    }
}; ?>

<div>
    @section('page-title', 'Dashboard')

    @if(auth()->user()->isSuperAdmin())
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Tenants -->
        <div class="group relative bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-lg rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-white/80 text-sm font-medium mb-1">Total de Empresas</p>
                <p class="text-4xl font-bold text-white">{{ $totalTenants }}</p>
            </div>
        </div>

        <!-- Active Tenants -->
        <div class="group relative bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-lg rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-white/80 text-sm font-medium mb-1">Empresas Ativas</p>
                <p class="text-4xl font-bold text-white">{{ $activeTenants }}</p>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="group relative bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-lg rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-white/80 text-sm font-medium mb-1">Assinaturas Ativas</p>
                <p class="text-4xl font-bold text-white">{{ $totalSubscriptions }}</p>
            </div>
        </div>

        <!-- Total Revenue -->
        <div class="group relative bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl hover:shadow-2xl transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 backdrop-blur-lg rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-white/80 text-sm font-medium mb-1">Receita Mensal</p>
                <p class="text-4xl font-bold text-white">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</p>
            </div>
        </div>
    </div>

    <!-- Recent Tenants -->
    <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-slate-50 to-blue-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Empresas Recentes</h2>
                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                    Últimas 5
                </span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs font-semibold uppercase bg-gradient-to-r from-gray-50 to-slate-50 text-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4">Nome</th>
                        <th scope="col" class="px-6 py-4">Email</th>
                        <th scope="col" class="px-6 py-4">CNPJ</th>
                        <th scope="col" class="px-6 py-4">Plano</th>
                        <th scope="col" class="px-6 py-4">Status</th>
                        <th scope="col" class="px-6 py-4">Criado em</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTenants as $tenant)
                    <tr class="bg-white border-b border-gray-100 hover:bg-gradient-to-r hover:from-blue-50 hover:to-indigo-50 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold mr-3">
                                    {{ substr($tenant->name, 0, 2) }}
                                </div>
                                <span class="font-semibold text-gray-900">{{ $tenant->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $tenant->cnpj ?? '-' }}</td>
                        <td class="px-6 py-4">
                            @if($tenant->activeSubscription)
                            <span class="px-3 py-1 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-700 rounded-lg text-xs font-semibold">
                                {{ $tenant->activeSubscription->plan->name }}
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-lg text-xs font-semibold">Sem assinatura</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($tenant->is_active)
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-green-100 to-emerald-100 text-green-700 rounded-lg text-xs font-semibold">Ativo</span>
                            </span>
                            @else
                            <span class="flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                <span class="px-3 py-1 bg-gradient-to-r from-red-100 to-rose-100 text-red-700 rounded-lg text-xs font-semibold">Inativo</span>
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $tenant->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-gray-500 font-medium">Nenhuma empresa cadastrada ainda</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- Tenant Dashboard -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Bem-vindo ao Ponto Eletrônico</h2>
        <p class="text-gray-600">Seu painel de controle será configurado em breve.</p>
    </div>
    @endif
</div>
