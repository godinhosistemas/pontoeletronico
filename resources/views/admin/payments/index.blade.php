@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
            Gerenciamento de Pagamentos
        </h1>
        <p class="text-gray-600 mt-1">Visualize todos os pagamentos processados no sistema</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-6">
        <p class="text-gray-500">Listagem de pagamentos em desenvolvimento...</p>
        <p class="text-sm text-gray-400 mt-2">Por enquanto, acesse os pagamentos através das faturas em <a href="{{ route('admin.invoices.index') }}" class="text-blue-600 hover:underline">Gerenciamento de Faturas</a></p>
    </div>
</div>
@endsection
