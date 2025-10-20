@extends('layouts.app')

@section('title', 'Funcionários')
@section('page-title', 'Gerenciamento de Funcionários')

@section('content')
    @livewire('admin.employees.index')
@endsection
