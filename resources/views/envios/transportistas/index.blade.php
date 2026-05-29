@extends('layouts.app')

@section('title', 'Transportistas | AgroFusion')
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
    @include('envios.partials.alertas')

    <div class="row mb-2">
        <div class="col-md-4">
            <div class="small-box small-box-green">
                <div class="inner">
                    <h3>{{ $stats['total'] ?? 0 }}</h3>
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
                @can('transportistas.create')
                <a href="{{ route('envios.transportistas.create') }}" class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-plus mr-1"></i> Nuevo transportista
                </a>
                @endcan
                <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#filtrosTransportistas">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
        </div>

        <div id="filtrosTransportistas" class="filtros-panel collapse {{ request()->hasAny(['buscar','estado']) ? 'show' : '' }}">
            <form method="GET" action="{{ route('envios.transportistas') }}" class="p-3">
                <div class="row align-items-end">
                    <div class="col-lg-5 col-md-6 mb-2 mb-md-0">
                        <label class="text-muted">Buscar</label>
                        <input type="text" name="buscar" class="form-control form-control-sm" value="{{ request('buscar') }}" placeholder="Nombre, correo o teléfono">
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2 mb-md-0">
                        <label class="text-muted">Estado</label>
                        <select name="estado" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                            <option value="inactivo" @selected(request('estado') === 'inactivo')>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-lg-4 col-md-12">
                        <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-search mr-1"></i> Filtrar</button>
                        <a href="{{ route('envios.transportistas') }}" class="btn btn-outline-secondary btn-sm">Limpiar</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-modulo table-hover mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Estado</th>
                        <th style="width:130px" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transportistas as $t)
                    <tr>
                        <td class="font-weight-bold">{{ $t->nombreCompleto() }}</td>
                        <td>{{ $t->email }}</td>
                        <td>{{ $t->telefono ?? '—' }}</td>
                        <td>
                            @if($t->activo)
                                <span class="badge badge-success badge-estado">Activo</span>
                            @else
                                <span class="badge badge-secondary badge-estado">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @include('envios.partials.crud-acciones', [
                                'showRoute' => route('envios.transportistas.show', $t),
                                'editRoute' => route('envios.transportistas.edit', $t),
                                'destroyRoute' => route('envios.transportistas.destroy', $t),
                                'entityName' => $t->nombreCompleto(),
                                'readPermission' => 'transportistas.view',
                                'updatePermission' => 'transportistas.update',
                                'deletePermission' => 'transportistas.delete',
                            ])
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-user-slash mr-1"></i> No hay transportistas registrados.
                            <a href="{{ route('envios.transportistas.create') }}" class="d-block mt-2">Registrar el primero</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transportistas->hasPages())
            <div class="card-footer">{{ $transportistas->links() }}</div>
        @endif
    </div>
</div>
@endsection
