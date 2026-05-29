@extends('layouts.app')

@section('title', 'Catálogos | Fusion-Proyectos')
@section('page_title', 'Gestión de Catálogos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Catálogos</li>
@endsection

@push('styles')
@include('partials.modulo-catalogos-styles')
<style>
    .modulo-cat .bg-cultivos { background: linear-gradient(135deg, #28a745, #20c997); }
    .modulo-cat .bg-actividades { background: linear-gradient(135deg, #6f42c1, #9775fa); }
    .modulo-cat .bg-insumos { background: linear-gradient(135deg, #fd7e14, #ffc107); }
    .modulo-cat .bg-almacenes { background: linear-gradient(135deg, #17a2b8, #6dd5ed); }
    .modulo-cat .bg-estados { background: linear-gradient(135deg, #dc3545, #f06595); }
    .modulo-cat .bg-unidades { background: linear-gradient(135deg, #343a40, #6c757d); }
    .modulo-cat .bg-prioridades { background: linear-gradient(135deg, #e83e8c, #f8a5c2); }
    .modulo-cat .bg-historial { background: linear-gradient(135deg, #2c5530, #4a7c59); }
    .modulo-cat .section-title {
        font-weight: 700;
        color: #1a252f;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 3px solid #2c5530;
        display: inline-block;
    }
    .modulo-cat .section-title i { color: #2c5530; margin-right: 10px; }
</style>
@endpush

@section('content')
<div class="modulo-cat">

    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 guia-campo mb-0">
                <div class="d-flex align-items-center">
                    <i class="fas fa-book fa-2x mr-3 text-success"></i>
                    <div>
                        <strong>Catálogos del sistema</strong>
                        <p class="mb-0">Administra los datos maestros reutilizados en lotes, inventario y operaciones.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $cards = [
            ['section' => 'Producción agrícola', 'icon' => 'fa-seedling', 'items' => [
                ['title' => 'Cultivos', 'desc' => 'Tipos de cultivos para lotes', 'count' => $counts['cultivos'] ?? 0, 'bg' => 'bg-cultivos', 'icon' => 'fa-leaf', 'index' => 'cultivos.index', 'create' => 'cultivos.create'],
                ['title' => 'Tipos de actividad', 'desc' => 'Clasificación de actividades', 'count' => $counts['tiposActividad'] ?? 0, 'bg' => 'bg-actividades', 'icon' => 'fa-tasks', 'index' => 'tipo-actividad.index', 'create' => 'tipo-actividad.create'],
                ['title' => 'Estados de lote', 'desc' => 'Ciclo de vida de los lotes', 'count' => $counts['estadosLote'] ?? 0, 'bg' => 'bg-estados', 'icon' => 'fa-flag', 'index' => 'estado-lote-tipos.index', 'create' => 'estado-lote-tipos.create'],
                ['title' => 'Prioridades', 'desc' => 'Niveles de prioridad operativa', 'count' => $counts['prioridades'] ?? 0, 'bg' => 'bg-prioridades', 'icon' => 'fa-exclamation-circle', 'index' => 'prioridades.index', 'create' => 'prioridades.create'],
                ['title' => 'Historial de estados', 'desc' => 'Cambios de estado por lote', 'count' => $counts['historialEstados'] ?? 0, 'bg' => 'bg-historial', 'icon' => 'fa-history', 'index' => 'historial-estados-lote.index', 'create' => 'historial-estados-lote.create'],
            ]],
            ['section' => 'Inventario y almacenamiento', 'icon' => 'fa-warehouse', 'items' => [
                ['title' => 'Tipos de insumo', 'desc' => 'Categorías de insumos', 'count' => $counts['tiposInsumo'] ?? 0, 'bg' => 'bg-insumos', 'icon' => 'fa-boxes', 'index' => 'tipo-insumos.index', 'create' => 'tipo-insumos.create'],
                ['title' => 'Tipos de almacén', 'desc' => 'Clasificación de almacenes', 'count' => $counts['tiposAlmacen'] ?? 0, 'bg' => 'bg-almacenes', 'icon' => 'fa-store', 'index' => 'tipoalmacenes.index', 'create' => 'tipoalmacenes.create'],
                ['title' => 'Unidades de medida', 'desc' => 'Unidades para cantidades', 'count' => $counts['unidadesMedida'] ?? 0, 'bg' => 'bg-unidades', 'icon' => 'fa-ruler', 'index' => 'unidades-medida.index', 'create' => 'unidades-medida.create'],
                ['title' => 'Estados de insumo', 'desc' => 'Estados al aplicar insumos', 'count' => $counts['estadosInsumo'] ?? 0, 'bg' => 'bg-insumos', 'icon' => 'fa-spray-can', 'index' => 'estado-lote-insumos.index', 'create' => 'estado-lote-insumos.create'],
            ]],
        ];
    @endphp

    @foreach($cards as $group)
    <h4 class="section-title"><i class="fas {{ $group['icon'] }}"></i>{{ $group['section'] }}</h4>
    <div class="row mb-4">
        @foreach($group['items'] as $card)
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card catalog-hub-card">
                <div class="card-body text-center">
                    <div class="catalog-hub-icon {{ $card['bg'] }} mx-auto">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1">{{ $card['title'] }}</h5>
                    <p class="text-muted small mb-2">{{ $card['desc'] }}</p>
                    <span class="badge badge-light border">{{ $card['count'] }} registros</span>
                </div>
                <div class="card-footer bg-white text-center d-flex justify-content-center flex-wrap" style="gap: 8px;">
                    <a href="{{ route($card['index']) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-list mr-1"></i> Ver
                    </a>
                    <a href="{{ route($card['create']) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Nuevo
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

</div>
@endsection
