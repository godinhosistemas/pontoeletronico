<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Ponto Eletrônico') }} - @yield('title', 'Login')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans antialiased bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
        <!-- Background Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDE2YzAgMS4xLS45IDItMiAycy0yLS45LTItMiAuOS0yIDItMiAyIC45IDIgMnptLTEyIDhjMCAxLjEtLjkgMi0yIDJzLTItLjktMi0yIC45LTIgMi0yIDIgLjkgMiAyem0yNCAwYzAgMS4xLS45IDItMiAycy0yLS45LTItMiAuOS0yIDItMiAyIC45IDIgMnptLTEyIDhjMCAxLjEtLjkgMi0yIDJzLTItLjktMi0yIC45LTIgMi0yIDIgLjkgMiAyem0tMTIgOGMwIDEuMS0uOSAyLTIgMnMtMi0uOS0yLTIgLjktMiAyLTIgMiAuOSAyIDJ6bTI0IDBjMCAxLjEtLjkgMi0yIDJzLTItLjktMi0yIC45LTIgMi0yIDIgLjkgMiAyem0tMTIgOGMwIDEuMS0uOSAyLTIgMnMtMi0uOS0yLTIgLjktMiAyLTIgMiAuOSAyIDJ6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>

        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <!-- Logo -->
            <div class="mb-8 text-center animate-fade-in">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-2xl mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Ponto Digital</h1>
                <p class="text-blue-200">Sistema de Gestão de Ponto Eletrônico</p>
            </div>

            <!-- Login Card -->
            <div class="w-full sm:max-w-md bg-white/95 backdrop-blur-lg shadow-2xl overflow-hidden rounded-2xl border border-white/20 animate-slide-up">
                <div class="px-8 py-8">
                    @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-green-800 text-sm font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-red-50 to-rose-50 border-l-4 border-red-500" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-red-800 text-sm font-medium">{{ session('error') }}</span>
                        </div>
                    </div>
                    @endif

                    @yield('content')
                </div>

                <!-- Footer -->
                <div class="px-8 py-4 bg-gradient-to-r from-slate-50 to-blue-50 border-t border-gray-100">
                    <div class="text-center space-y-1">
                        <p class="text-xs font-semibold text-gray-700">
                            Sistema Next Ponto
                        </p>
                        <p class="text-xs text-gray-600">
                            Desenvolvido por: <span class="font-medium">Godinho Sistemas Ltda.</span>
                        </p>
                        <p class="text-xs text-gray-500">
                            <a href="https://www.nextsystems.com.br" target="_blank" class="hover:text-blue-600 transition-colors">
                                www.nextsystems.com.br
                            </a>
                        </p>
                        <p class="text-xs text-gray-500 pt-1">
                            &copy; {{ date('Y') }} - Todos os direitos reservados.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Decorative Elements -->
            <div class="absolute top-10 left-10 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob"></div>
            <div class="absolute bottom-10 right-10 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000"></div>
        </div>

        <style>
            @keyframes fade-in {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slide-up {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes blob {
                0%, 100% { transform: translate(0, 0) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
            }
            .animate-fade-in { animation: fade-in 0.6s ease-out; }
            .animate-slide-up { animation: slide-up 0.6s ease-out; }
            .animate-blob { animation: blob 7s infinite; }
            .animation-delay-2000 { animation-delay: 2s; }
        </style>

        @livewireScripts
    </body>
</html>
