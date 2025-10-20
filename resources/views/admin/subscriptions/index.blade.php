@extends('layouts.app')

@section('title', 'Assinaturas')
@section('page-title', 'Gerenciamento de Assinaturas')

@section('content')
    @livewire('admin.subscriptions.index')
@endsection
