@extends('layouts.app')

@section('title', 'Historial de estados de lote | AgroFusion')
@section('page_title', 'Historial de estados de lote')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('catalogos.index') }}">Catálogos</a></li>
    <li class="breadcrumb-item active">Historial de estados</li>
@endsection

@push('styles')
@include('partials.modulo-catalogos-styles')
@endpush

@section('content')
<div class="modulo-cat">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    @endif

    <div class="row mb-2">
        <div class="col-lg-4 col-6">
            <div class="small-box small-box-green">
                <div class="inner">
                    <h3>{{ $historial->total() }}</h3>
                    <p>Registros de cambio</p>
                </div>
                <div class="icon"><i class="fas fa-history"></i></div>
                <span class="small-box-footer">Trazabilidad de estados por lote</span>
            </div>
        </div>
    </div>

    <div class="card card-outline card-success card-modulo-main elevation-1 mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="fas fa-history text-success mr-1"></i>
                Historial de estados de lote
                <span class="badge badge-light border text-muted badge-registros ml-2">{{ $historial->total() }} registros</span>
            </h3>
            <div class="card-tools d-flex align-items-center flex-wrap" style="gap: 6px;">
                <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#filtrosHistorialPanel" title="Filtros">
                    <i class="fas fa-filter"></i>
                </button>
                <a href="{{ route('historial-estados-lote.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo registro
                </a>
            </div>
        </div>

        <div id="filtrosHistorialPanel" class="filtros-panel collapse {{ request()->hasAny(['buscar','loteid','estadolotetipoid','fecha_desde','fecha_hasta']) ? 'show' : '' }}">
            <form method="GET" action="{{ route('historial-estados-lote.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Buscar</label>
                        <input type="text" name="buscar" class="form-control form-control-sm" value="{{ request('buscar') }}"
                            placeholder="Lote, estado u observaciones…">
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Lote</label>
                        <select name="loteid" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->loteid }}" @selected(request('loteid') == $lote->loteid)>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Estado</label>
                        <select name="estadolotetipoid" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($tiposEstado as $tipo)
                                <option value="{{ $tipo->estadolotetipoid }}" @selected(request('estadolotetipoid') == $tipo->estadolotetipoid)>{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                    </div>
                    <div class="col-md-2 mb-2 mb-md-0">
                        <label class="small text-muted mb-1">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                    </div>
                    <div class="col-md-1 d-flex" style="gap: 8px;">
                        <button type="submit" class="btn btn-success btn-sm" title="Filtrar"><i class="fas fa-search"></i></button>
                        <a href="{{ route('historial-estados-lote.index') }}" class="btn btn-outline-secondary btn-sm" title="Limpiar"><i class="fas fa-eraser"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-modulo table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 55px">#</th>
                        <th>Lote</th>
                        <th>Estado</th>
                        <th>Fecha cambio</th>
                        <th>Usuario</th>
                        <th>Observaciones</th>
                        <th style="width: 130px" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historial as $registro)
                    <tr>
                        <td class="text-muted font-weight-bold">#{{ $registro->historial_estado_id }}</td>
                        <td><strong>{{ $registro->lote->nombre ?? '—' }}</strong></td>
                        <td>
                            <span class="badge badge-light border">{{ $registro->estadoTipo->nombre ?? '—' }}</span>
                        </td>
                        <td class="text-muted">{{ $registro->fecha_cambio ? $registro->fecha_cambio->format('d/m/Y H:i') : '—' }}</td>
                        <td>{{ $registro->usuario ? $registro->usuario->nombre.' '.$registro->usuario->apellido : '—' }}</td>
                        <td class="text-muted small">{{ Str::limit($registro->observaciones, 60) ?: '—' }}</td>
                        <td class="text-center btn-actions">
                            <a href="{{ route('historial-estados-lote.show', $registro) }}" class="btn btn-sm btn-outline-info" title="Ver detalles">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('historial-estados-lote.edit', $registro) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('historial-estados-lote.destroy', $registro) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('¿Eliminar este registro?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-history fa-3x mb-3 d-block"></i>
                            No hay registros que coincidan con los filtros.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($historial->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted mb-2 mb-md-0">
                Mostrando {{ $historial->firstItem() }}–{{ $historial->lastItem() }} de {{ $historial->total() }}
            </small>
            {{ $historial->links() }}
        </div>
        @endif
    </div>

    <a href="{{ route('catalogos.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver a Catálogos
    </a>
</div>
@endsection
