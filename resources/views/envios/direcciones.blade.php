@extends('layouts.app')

@section('title', 'Direcciones | AgroNexus')
@section('page_title', 'Direcciones de envíos')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('envios.seguimiento') }}">Envíos</a></li>
    <li class="breadcrumb-item active">Direcciones</li>
@endsection

@push('styles')
@include('partials.modulo-envios-styles')
@endpush

@section('content')
@php
    $origenes = collect($direcciones ?? [])->where('tipo', 'Origen')->count();
    $destinos = collect($direcciones ?? [])->where('tipo', 'Destino')->count();
@endphp
<div class="modulo-env page-env-direcciones">

    <div class="row mb-2">
        <div class="col-md-4">
            <div class="small-box small-box-green">
                <div class="inner"><h3>{{ count($direcciones ?? []) }}</h3><p>Puntos logísticos</p></div>
                <div class="icon"><i class="fas fa-map-marked-alt"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box small-box-teal">
                <div class="inner"><h3>{{ $origenes }}</h3><p>Orígenes</p></div>
                <div class="icon"><i class="fas fa-arrow-up"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box small-box-blue">
                <div class="inner"><h3>{{ $destinos }}</h3><p>Destinos</p></div>
                <div class="icon"><i class="fas fa-arrow-down"></i></div>
            </div>
        </div>
    </div>

    <div class="card card-modulo-main">
        <div class="card-header">
            <h3 class="card-title mb-0"><i class="fas fa-map-pin text-success mr-2"></i>Direcciones de origen y destino</h3>
            <div class="card-tools">
                <span class="contador-filtro mr-2" id="contadorDirecciones"></span>
                <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Colapsar">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>

        <div class="filtros-panel">
            <div class="row align-items-end">
                <div class="col-lg-5 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Buscar en dirección</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchDireccion" class="form-control" placeholder="Ciudad, zona, almacén, planta...">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                    <label class="text-muted">Tipo de punto</label>
                    <select id="filterTipoDireccion" class="form-control form-control-sm">
                        <option value="">Origen y destino</option>
                        <option value="origen">Solo orígenes</option>
                        <option value="destino">Solo destinos</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="button" class="btn btn-outline-secondary btn-sm btn-block" id="btnLimpiarDirecciones">
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
                        <th style="width:110px">Tipo</th>
                        <th>Dirección</th>
                    </tr>
                </thead>
                <tbody id="tabla-direcciones">
                    @forelse($direcciones ?? [] as $i => $d)
                    <tr class="fila-filtro"
                        data-texto="{{ strtolower(($d['tipo'] ?? '').' '.($d['valor'] ?? '')) }}"
                        data-tipo="{{ strtolower($d['tipo'] ?? '') }}">
                        <td class="col-num">{{ $i + 1 }}</td>
                        <td>
                            @if(($d['tipo'] ?? '') === 'Origen')
                                <span class="badge badge-success badge-estado">Origen</span>
                            @else
                                <span class="badge badge-danger badge-estado">Destino</span>
                            @endif
                        </td>
                        <td>{{ $d['valor'] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fas fa-map mr-1"></i> No hay direcciones derivadas de envíos registrados.
                        </td>
                    </tr>
                    @endforelse
                    <tr id="sinResultadosDirecciones" style="display: none;">
                        <td colspan="3" class="text-center text-muted py-4">
                            <i class="fas fa-filter mr-1"></i> Ninguna dirección coincide con los filtros aplicados.
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
        'searchId' => 'searchDireccion',
        'filterIds' => ['filterTipoDireccion'],
        'dataKeys' => ['tipo'],
        'contadorId' => 'contadorDirecciones',
        'sinResultadosId' => 'sinResultadosDirecciones',
        'limpiarId' => 'btnLimpiarDirecciones',
    ];
@endphp
@include('partials.envios-tabla-filtros')
@endpush
