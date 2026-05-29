@extends('layouts.app')

@section('title', 'Cultivos | AgroFusion')
@section('page_title', 'Cultivos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('catalogos.index') }}">Catálogos</a></li>
    <li class="breadcrumb-item active">Cultivos</li>
@endsection

@push('styles')
@include('partials.modulo-catalogos-styles')
@endpush

@section('content')
@include('catalogos.partials.simple-show', [
    'item' => $item,
    'routePrefix' => 'cultivos',
    'pk' => 'cultivoid',
    'singular' => 'cultivo',
    'icono' => 'fa-seedling',
    'tieneDescripcion' => false,
])
@endsection