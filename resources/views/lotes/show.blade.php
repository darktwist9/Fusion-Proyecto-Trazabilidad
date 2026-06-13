@extends('layouts.app')

@section('title', 'Información del lote | AgroFusion')
@section('page_title', $lote->nombre)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('lotes.index') }}">Lotes</a></li>
    <li class="breadcrumb-item active">{{ $lote->nombre }}</li>
@endsection

@push('styles')
    @include('lotes.partials.detalle-styles')
@endpush

@section('content')
    @include('lotes.partials.detalle-header')
    @include('lotes.partials.detalle-stats')
    @include('lotes.partials.detalle-nav')

    <div class="card lote-section-card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    @include('lotes.partials.datos-lote-panel')
                </div>
                <div class="col-md-6">
                    @if($lote->imagenurl)
                        <h5 class="mb-3"><i class="fas fa-image mr-2 text-success"></i>Imagen</h5>
                        <div class="text-center mb-4">
                            <img src="{{ $lote->imagenurl }}" alt="Lote" class="img-fluid rounded shadow-sm" style="max-height: 300px;">
                        </div>
                    @endif
                    <h5 class="mb-3"><i class="fas fa-chart-pie mr-2 text-success"></i>Resumen operativo</h5>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-check-circle text-success fa-2x mb-2"></i>
                                <h4 class="mb-0">{{ $estadisticas['actividades_completadas'] }}</h4>
                                <small class="text-muted">Actividades completadas</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-clock text-warning fa-2x mb-2"></i>
                                <h4 class="mb-0">{{ $estadisticas['actividades_pendientes'] }}</h4>
                                <small class="text-muted">Pendientes</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-flask text-info fa-2x mb-2"></i>
                                <h4 class="mb-0">{{ $estadisticas['total_insumos'] }}</h4>
                                <small class="text-muted">Aplicaciones</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fas fa-leaf text-success fa-2x mb-2"></i>
                                <h4 class="mb-0">{{ number_format($estadisticas['produccion_total'], 0) }}</h4>
                                <small class="text-muted">Kg producidos</small>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('lotes.trazabilidad', $lote) }}" class="btn btn-success btn-block">
                        <i class="fas fa-project-diagram mr-1"></i> Ver trazabilidad
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('lotes.partials.detalle-actions')
@endsection
