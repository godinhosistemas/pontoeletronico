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
                    <div class="flex items-center justify-center px-4 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
                        <svg class="w-6 h-6 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-lg font-bold text-white">Ponto Digital</span>
                    </div>

                    <!-- Navigation -->
                    <div class="flex-1 overflow-y-auto px-3 py-2">
                        <ul class="space-y-1 font-medium text-sm">
                        @can('tenants.view')
                        <li>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center p-2 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 group {{ request()->routeIs('admin.dashboard') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : '' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                                <span class="ml-2">Dashboard</span>
                            </a>
                        </li>
                        @endcan

                        @if(auth()->user()->isSuperAdmin())
                        <!-- Administração -->
                        <li x-data="{ open: {{ request()->routeIs('admin.tenants.*', 'admin.plans.*', 'admin.subscriptions.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-2 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="ml-2">Administração</span>
                                </div>
                                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <ul x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <li>
                                    <a href="{{ route('admin.tenants.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.tenants.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Empresas
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.plans.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.plans.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Planos
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.subscriptions.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Assinaturas
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <!-- Financeiro -->
                        <li x-data="{ open: {{ request()->routeIs('admin.invoices.*', 'admin.payment-gateways.*', 'admin.payments.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-2 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="ml-2">Financeiro</span>
                                </div>
                                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <ul x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <li>
                                    <a href="{{ route('admin.invoices.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.invoices.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Faturas
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.payment-gateways.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.payment-gateways.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Gateways
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif

                        @can('employees.view')
                        <!-- Gestão -->
                        <li x-data="{ open: {{ request()->routeIs('admin.employees.*', 'admin.work-schedules.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-2 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="ml-2">Gestão</span>
                                </div>
                                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <ul x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <li>
                                    <a href="{{ route('admin.employees.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.employees.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Funcionários
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('admin.work-schedules.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.work-schedules.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Jornadas
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endcan

                        <!-- Ponto Eletrônico -->
                        <li x-data="{ open: {{ request()->routeIs('employee.clock-in', 'admin.timesheet.*', 'admin.legal-files.*') ? 'true' : 'false' }} }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-2 text-gray-300 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="ml-2">Ponto Eletrônico</span>
                                </div>
                                <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <ul x-show="open" x-collapse class="ml-4 mt-1 space-y-1">
                                <li>
                                    <a href="{{ route('employee.clock-in') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('employee.clock-in') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Registrar
                                    </a>
                                </li>
                                @can('timesheet.approve')
                                <li>
                                    <a href="{{ route('admin.timesheet.approvals') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.timesheet.approvals') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Aprovar
                                    </a>
                                </li>
                                @endcan
                                @can('reports.view')
                                <li>
                                    <a href="{{ route('admin.timesheet.reports') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.timesheet.reports') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        Relatórios
                                    </a>
                                </li>
                                @endcan
                                <li>
                                    <a href="{{ route('admin.legal-files.index') }}" class="flex items-center p-2 text-gray-400 rounded-lg hover:bg-slate-700 hover:text-white transition-all duration-200 text-xs {{ request()->routeIs('admin.legal-files.*') ? 'bg-slate-700 text-white' : '' }}">
                                        <span class="w-1 h-1 bg-gray-500 rounded-full mr-2"></span>
                                        AFD / AEJ
                                    </a>
                                </li>
                            </ul>
                        </li>
                        </ul>
                    </div>

                    <!-- User Section -->
                    <div class="border-t border-slate-700 p-3 bg-slate-900/50">
                        <div class="bg-slate-800 rounded-lg p-2 mb-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 flex items-center justify-center">
                                        <span class="text-white font-semibold text-xs">{{ substr(auth()->user()->name, 0, 2) }}</span>
                                    </div>
                                </div>
                                <div class="ml-2 flex-1 min-w-0">
                                    <p class="text-xs font-medium text-white truncate">
                                        {{ auth()->user()->name }}
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ auth()->user()->getRoleNames()->first() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <form id="logout-form" method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex items-center w-full p-2 text-gray-300 rounded-lg hover:bg-red-600 hover:text-white transition-all duration-200 group text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="ml-2">Sair</span>
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

                        <!-- Copyright -->
                        <div class="mt-3 pt-3 border-t border-slate-700 text-center">
                            <p class="text-xs font-semibold text-gray-300 mb-1">
                                Sistema Next Ponto
                            </p>
                            <p class="text-xs text-gray-400 mb-1">
                                Godinho Sistemas Ltda.
                            </p>
                            <a href="https://www.nextsystems.com.br" target="_blank" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                                www.nextsystems.com.br
                            </a>
                            <p class="text-xs text-gray-500 mt-2">
                                &copy; {{ date('Y') }}
                            </p>
                        </div>
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
