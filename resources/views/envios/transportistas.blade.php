@extends('layouts.app')

@section('title', 'Transportistas | AgroNexus')
@section('page_title', 'Transportistas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('envios.seguimiento') }}">Envíos</a></li>
    <li class="breadcrumb-item active">Transportistas</li>
@endsection

@push('styles')
@include('partials.modulo-envios-styles')
@endpush

@section('content')
<div class="modulo-env page-env-transportistas">

    <div class="row mb-2">
        <div class="col-md-4">
            <div class="small-box small-box-green">
                <div class="inner">
                    <h3>{{ count($transportistas ?? []) }}</h3>
                    <p>Transportistas registrados</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-modulo-main">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-id-card text-success mr-2"></i>Personal de transporte</h3>
            <div class="card-tools">
                <span class="contador-filtro mr-2" id="contadorTransportistas"></span>
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Colapsar">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <div class="filtros-panel">
            <div class="row align-items-end">
                <div class="col-lg-4 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Buscar</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchTransportista" class="form-control" placeholder="Nombre, correo o estado...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Estado logístico</label>
                    <select id="filterEstadoTransportista" class="form-control form-control-sm">
                        <option value="">Todos los estados</option>
                        @foreach($estadosFiltro ?? [] as $estado)
                            <option value="{{ strtolower($estado) }}">{{ $estado }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-block" id="btnLimpiarTransportistas">
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
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-transportistas">
                    @forelse($transportistas ?? [] as $i => $t)
                    @php
                        $persona = $t['persona'] ?? [];
                        $nombre = trim(($persona['nombre'] ?? '').' '.($persona['apellido'] ?? '')) ?: ($t['nombre'] ?? 'N/D');
                        $correo = $t['usuario']['correo'] ?? $t['correo'] ?? '—';
                        $estado = $t['estado']['nombre'] ?? $t['estadotransportista']['nombre'] ?? ($t['estado'] ?? '—');
                    @endphp
                    <tr class="fila-filtro"
                        data-texto="{{ strtolower($nombre.' '.$correo.' '.$estado) }}"
                        data-estado="{{ strtolower($estado) }}">
                        <td class="col-num">{{ $i + 1 }}</td>
                        <td class="font-weight-bold">{{ $nombre }}</td>
                        <td>{{ $correo }}</td>
                        <td><span class="badge badge-info badge-estado">{{ $estado }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-user-slash mr-1"></i> No hay transportistas registrados.
                        </td>
                    </tr>
                    @endforelse
                    <tr id="sinResultadosTransportistas" style="display: none;">
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-filter mr-1"></i> Ningún transportista coincide con los filtros aplicados.
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
        'searchId' => 'searchTransportista',
        'filterIds' => ['filterEstadoTransportista'],
        'dataKeys' => ['estado'],
        'contadorId' => 'contadorTransportistas',
        'sinResultadosId' => 'sinResultadosTransportistas',
        'limpiarId' => 'btnLimpiarTransportistas',
    ];
@endphp
@include('partials.envios-tabla-filtros')
@endpush
