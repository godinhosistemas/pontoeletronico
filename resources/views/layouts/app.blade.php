<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2563eb">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <title>{{ config('app.name', 'Ponto Eletrônico') }} - @yield('title', 'Dashboard')</title>

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <link rel="icon" type="image/png" sizes="192x192" href="/images/icon-192x192.png">
        <link rel="apple-touch-icon" href="/images/icon-192x192.png">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-gray-50 via-blue-50 to-gray-50">
        <div class="min-h-screen">
            <!-- Sidebar -->
            <aside class="fixed left-0 top-0 z-40 h-screen w-64 transition-transform -translate-x-full sm:translate-x-0 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 shadow-2xl">
                <div class="h-full flex flex-col">
                    <!-- Logo -->
                    <div class="flex items-center justify-center px-6 py-6 bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
                        <svg class="w-8 h-8 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-xl font-bold text-white">Ponto Digital</span>
                    </div>

                    <!-- Navigation -->
                    <div class="flex-1 overflow-y-auto px-4 py-4">
                        <ul class="space-y-2 font-medium">
                        @can('tenants.view')
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <span class="ml-3">Dashboard</span>
                            </a>
                        </li>
                        @endcan

                        @if(auth()->user()->isSuperAdmin())
                        <li>
                            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Administração
                            </div>
                        </li>
                        <li>
                            <a href="{{ route('admin.tenants.index') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.tenants.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span class="ml-3">Empresas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.plans.index') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.plans.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <span class="ml-3">Planos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.subscriptions.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                <span class="ml-3">Assinaturas</span>
                            </a>
                        </li>
                        @endif

                        @can('employees.view')
                        <li>
                            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4">
                                Gestão
                            </div>
                        </li>
                        @endcan

                        @can('employees.view')
                        <li>
                            <a href="{{ route('admin.employees.index') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.employees.*') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span class="ml-3">Funcionários</span>
                            </a>
                        </li>
                        @endcan

                        <li>
                            <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider mt-4">
                                Ponto Eletrônico
                            </div>
                        </li>

                        <li>
                            <a href="{{ route('employee.clock-in') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('employee.clock-in') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="ml-3">Registrar Ponto</span>
                            </a>
                        </li>

                        @can('timesheet.approve')
                        <li>
                            <a href="{{ route('admin.timesheet.approvals') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.timesheet.approvals') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="ml-3">Aprovar Pontos</span>
                            </a>
                        </li>
                        @endcan

                        @can('reports.view')
                        <li>
                            <a href="{{ route('admin.timesheet.reports') }}" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.timesheet.reports') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="ml-3">Relatórios</span>
                            </a>
                        </li>
                        @endcan

                        {{-- Módulos em desenvolvimento --}}
                        {{-- @can('timesheet.view')
                        <li>
                            <a href="#" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="ml-3">Registrar Ponto</span>
                            </a>
                        </li>
                        @endcan

                        @can('reports.view')
                        <li>
                            <a href="#" class="flex items-center p-3 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span class="ml-3">Relatórios</span>
                            </a>
                        </li>
                        @endcan --}}
                        </ul>
                    </div>

                    <!-- User Section -->
                    <div class="border-t border-slate-700 p-4 bg-slate-900/50">
                        <div class="bg-slate-800 rounded-lg p-3 mb-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center">
                                        <span class="text-white font-semibold text-sm">{{ substr(auth()->user()->name, 0, 2) }}</span>
                                    </div>
                                </div>
                                <div class="ml-3 flex-1 min-w-0">
                                    <p class="text-sm font-medium text-white truncate" wire:key="user-name-{{ auth()->id() }}">
                                        {{ auth()->user()->name }}
                                        <span class="text-xs text-yellow-300">(ID: {{ auth()->id() }})</span>
                                    </p>
                                    <p class="text-xs text-gray-400 truncate" wire:key="user-role-{{ auth()->id() }}">
                                        {{ auth()->user()->getRoleNames()->first() }}
                                        @if(!auth()->user()->isSuperAdmin() && auth()->user()->tenant)
                                            - {{ auth()->user()->tenant->name }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Super Admin: {{ auth()->user()->isSuperAdmin() ? 'SIM' : 'NÃO' }}
                                        | Tenant: {{ auth()->user()->tenant_id ?? 'Nenhum' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full p-3 text-gray-300 rounded-lg hover:bg-red-600 hover:text-white transition-all duration-200 group">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="ml-3">Sair</span>
                            </button>
                        </form>

                        <script>
                            document.getElementById('logout-form').addEventListener('submit', function(e) {
                                e.preventDefault();

                                // Tenta fazer logout via POST
                                fetch(this.action, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    },
                                    credentials: 'same-origin'
                                })
                                .then(response => {
                                    if (response.status === 419) {
                                        // Se der erro 419 (CSRF), usa a rota GET alternativa
                                        window.location.href = '{{ route("logout.get") }}';
                                    } else {
                                        // Sucesso - redireciona para login
                                        window.location.href = '{{ route("login") }}';
                                    }
                                })
                                .catch(error => {
                                    // Em caso de erro de rede, usa a rota GET
                                    window.location.href = '{{ route("logout.get") }}';
                                });
                            });
                        </script>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="p-6 sm:ml-64">
                <!-- Top Bar -->
                <div class="mb-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                                @yield('page-title', 'Dashboard')
                            </h1>
                            @if(isset($breadcrumbs))
                            <nav class="flex mt-2" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    {{ $breadcrumbs }}
                                </ol>
                            </nav>
                            @endif
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="px-4 py-2 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                                <p class="text-xs text-gray-600">Data/Hora</p>
                                <p class="text-sm font-semibold text-gray-900" id="current-time">
                                    {{ now()->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 shadow-lg" role="alert">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-green-800 font-medium">{{ session('success') }}</span>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500 shadow-lg" role="alert">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-red-800 font-medium">{{ session('error') }}</span>
                    </div>
                </div>
                @endif

                <!-- Page Content -->
                <main>
                    @yield('content')
                </main>
            </div>
        </div>

        @livewireScripts

        <!-- PWA Service Worker -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(registration => {
                            console.log('Service Worker registrado:', registration.scope);
                        })
                        .catch(error => {
                            console.log('Erro ao registrar Service Worker:', error);
                        });
                });
            }
        </script>
    </body>
</html>
