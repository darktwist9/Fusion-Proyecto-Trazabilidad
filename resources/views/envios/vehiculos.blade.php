@extends('layouts.app')

@section('title', 'Vehículos | AgroNexus')
@section('page_title', 'Vehículos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('envios.seguimiento') }}">Envíos</a></li>
    <li class="breadcrumb-item active">Vehículos</li>
@endsection

@push('styles')
@include('partials.modulo-envios-styles')
@endpush

@section('content')
<div class="modulo-env page-env-vehiculos">

    <div class="row mb-2">
        <div class="col-md-4">
            <div class="small-box small-box-orange">
                <div class="inner">
                    <h3>{{ count($vehiculos ?? []) }}</h3>
                    <p>Vehículos en flota</p>
                </div>
                <div class="icon"><i class="fas fa-truck"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-modulo-main">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-truck-moving text-success mr-2"></i>Flota logística</h3>
            <div class="card-tools">
                <span class="contador-filtro mr-2" id="contadorVehiculos"></span>
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Colapsar">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <div class="filtros-panel">
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Buscar</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchVehiculo" class="form-control" placeholder="Placa, tipo, estado...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Tipo de vehículo</label>
                    <select id="filterTipoVehiculo" class="form-control form-control-sm">
                        <option value="">Todos los tipos</option>
                        @foreach($tiposFiltro ?? [] as $tipo)
                            <option value="{{ strtolower($tipo) }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Estado</label>
                    <select id="filterEstadoVehiculo" class="form-control form-control-sm">
                        <option value="">Todos los estados</option>
                        @foreach($estadosFiltro ?? [] as $estado)
                            <option value="{{ strtolower($estado) }}">{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-block" id="btnLimpiarVehiculos">
                        <i class="fas fa-times mr-1"></i> Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-modulo table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Placa</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Capacidad</th>
                    </tr>
                </thead>
                <tbody id="tabla-vehiculos">
                    @forelse($vehiculos ?? [] as $i => $v)
                    @php
                        $tipo = $v['tipo_vehiculo']['nombre'] ?? $v['tipoVehiculo']['nombre'] ?? $v['tipo'] ?? '—';
                        $estado = $v['estado_vehiculo']['nombre'] ?? $v['estadoVehiculo']['nombre'] ?? $v['estado'] ?? '—';
                        $capacidad = $v['capacidad_carga'] ?? $v['capacidad'] ?? '—';
                        $placa = $v['placa'] ?? '—';
                    @endphp
                    <tr class="fila-filtro"
                        data-texto="{{ strtolower($placa.' '.$tipo.' '.$estado.' '.$capacidad) }}"
                        data-tipo="{{ strtolower($tipo) }}"
                        data-estado="{{ strtolower($estado) }}">
                        <td class="col-num">{{ $i + 1 }}</td>
                        <td><span class="badge badge-dark font-weight-bold">{{ $placa }}</span></td>
                        <td>{{ $tipo }}</td>
                        <td><span class="badge badge-success badge-estado">{{ $estado }}</span></td>
                        <td>{{ $capacidad }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-truck mr-1"></i> No hay vehículos registrados en la operación.
                        </td>
                    </tr>
                    @endforelse
                    <tr id="sinResultadosVehiculos" style="display: none;">
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-filter mr-1"></i> Ningún vehículo coincide con los filtros aplicados.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@php
    $filtrosConfig = [
        'rowSelector' => '.fila-filtro',
        'searchId' => 'searchVehiculo',
        'filterIds' => ['filterTipoVehiculo', 'filterEstadoVehiculo'],
        'dataKeys' => ['tipo', 'estado'],
        'contadorId' => 'contadorVehiculos',
        'sinResultadosId' => 'sinResultadosVehiculos',
        'limpiarId' => 'btnLimpiarVehiculos',
    ];
@endphp
@include('partials.envios-tabla-filtros')
@endpush
