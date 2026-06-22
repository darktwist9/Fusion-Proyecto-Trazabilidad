@extends('layouts.app')

@section('title', 'Documentos de entrega | AgroFusion')
@section('page_title', 'Documentos de entrega')

@push('styles')
@include('logistica.documentos.partials.documentos-estilos')
@endpush

@php
    $tiposDocumento = $tiposDocumento ?? \App\Support\DocumentoEntregaCatalogo::tiposDocumento();
@endphp

@section('content')
@include('logistica.partials.envios-seccion-nav')
<div class="log-doc-wrap">
    @if ($errors->any())
        <div class="alert alert-danger border-0" style="border-radius:10px">{{ $errors->first() }}</div>
    @endif

    <div class="log-doc-hero">
        <span class="log-doc-hero__icon"><i class="fas fa-file-contract"></i></span>
        <div class="log-doc-hero__body">
            <p class="log-doc-hero__eyebrow">Registro documental</p>
            <h2 class="log-doc-hero__title">Documentos de transporte</h2>
            <p class="log-doc-hero__text mb-0">
                @if(\App\Support\DocumentoEntregaAcceso::esVistaGlobal(auth()->user()))
                    Consulte los documentos generados automáticamente al cerrar cada envío: guías de transporte,
                    firmas y trazabilidad del recorrido.
                @elseif(\App\Support\UsuarioRol::esJefeAgricultor(auth()->user()))
                    Solo documentos de envíos agrícola → planta de sus transportistas.
                @elseif(\App\Support\UsuarioRol::esJefePlanta(auth()->user()))
                    Solo documentos de traslados planta → mayorista.
                @elseif(\App\Support\UsuarioRol::esMayorista(auth()->user()))
                    Solo documentos vinculados a sus almacenes mayoristas.
                @else
                    Documentos de entrega asociados a sus envíos.
                @endif
                Cada archivo queda vinculado al código de envío para auditoría.
            </p>
        </div>
    </div>

    <div class="row log-doc-metrics">
        <div class="col-6 col-md-4 mb-2 mb-md-0">
            <div class="log-doc-metric log-doc-metric--forest">
                <span class="log-doc-metric__icon"><i class="fas fa-folder"></i></span>
                <span>
                    <div class="log-doc-metric__val">{{ $resumenDocumentos['total'] ?? $documentos->total() }}</div>
                    <div class="log-doc-metric__lbl">Documentos (filtro actual)</div>
                </span>
            </div>
        </div>
        <div class="col-6 col-md-4 mb-2 mb-md-0">
            <div class="log-doc-metric log-doc-metric--navy">
                <span class="log-doc-metric__icon"><i class="fas fa-file-alt"></i></span>
                <span>
                    <div class="log-doc-metric__val">{{ $resumenDocumentos['guias'] ?? 0 }}</div>
                    <div class="log-doc-metric__lbl">Guías de transporte</div>
                </span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="log-doc-metric log-doc-metric--bronze">
                <span class="log-doc-metric__icon"><i class="fas fa-calendar-alt"></i></span>
                <span>
                    <div class="log-doc-metric__val">{{ $resumenDocumentos['hoy'] ?? 0 }}</div>
                    <div class="log-doc-metric__lbl">Generados hoy</div>
                </span>
            </div>
        </div>
    </div>

    <div class="log-doc-card">
        <div class="log-doc-card__head">
            <h2 class="log-doc-card__title">Listado de documentos</h2>
            <span class="log-doc-card__count">{{ $documentos->total() }} registros</span>
        </div>

        <div class="log-doc-filtros">
            <form method="GET" action="{{ route('logistica.documentos.index') }}">
                <div class="form-row align-items-end">
                    <div class="col-lg-4 col-md-6 form-group mb-md-0">
                        <label for="docFiltroBuscar">Buscar</label>
                        <input type="search" id="docFiltroBuscar" name="q" class="form-control form-control-sm"
                               value="{{ request('q') }}" placeholder="Título o código de envío…">
                    </div>
                    <div class="col-lg-3 col-md-6 form-group mb-md-0">
                        <label for="docFiltroTipo">Tipo</label>
                        <select id="docFiltroTipo" name="tipo" class="form-control form-control-sm">
                            <option value="">Todos</option>
                            @foreach($tiposDocumento as $valorTipo => $etiquetaTipo)
                                <option value="{{ $valorTipo }}" @selected(request('tipo') === $valorTipo)>
                                    {{ $etiquetaTipo }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 form-group mb-md-0">
                        <label for="docFiltroDesde">Desde</label>
                        <input type="date" id="docFiltroDesde" name="desde" class="form-control form-control-sm"
                               value="{{ request('desde') }}">
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 form-group mb-md-0">
                        <label for="docFiltroHasta">Hasta</label>
                        <input type="date" id="docFiltroHasta" name="hasta" class="form-control form-control-sm"
                               value="{{ request('hasta') }}">
                    </div>
                    <div class="col-auto form-group mb-0">
                        <button type="submit" class="btn btn-sm log-doc-btn-filter">
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>
            @if(request()->except('page'))
                <p class="small text-muted mb-0 mt-2">
                    Filtros activos.
                    <a href="{{ route('logistica.documentos.index') }}" class="log-doc-filtros__link">Limpiar</a>
                </p>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table mb-0 log-doc-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Documento</th>
                        <th>Tipo</th>
                        <th>Envío / pedido</th>
                        <th>Generado por</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documentos as $documento)
                        @php
                            $tipo = $documento->tipo_documento;
                            $esAutomatico = \App\Support\DocumentoEntregaCatalogo::esAutomatico($documento);
                            $chipClase = match (true) {
                                str_contains($tipo, 'guia') => 'log-doc-chip--guia',
                                str_contains($tipo, 'pod') => 'log-doc-chip--pod',
                                str_contains($tipo, 'nota') => 'log-doc-chip--nota',
                                default => 'log-doc-chip--default',
                            };
                        @endphp
                        <tr>
                            <td class="td-muted">{{ optional($documento->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="td-ref">{{ $documento->titulo }}</td>
                            <td>
                                <span class="log-doc-chip {{ $chipClase }}">
                                    {{ $tiposDocumento[$tipo] ?? \App\Support\DocumentoEntregaCatalogo::etiquetaTipo($tipo) }}
                                </span>
                            </td>
                            <td class="td-envio">{{ \App\Support\DocumentoEntregaCatalogo::etiquetaVinculo($documento) }}</td>
                            <td>{{ \App\Support\DocumentoEntregaCatalogo::etiquetaUsuario($documento->usuario) }}</td>
                            <td class="text-right text-nowrap">
                                <div class="log-doc-actions justify-content-end">
                                    <a class="log-doc-btn-icon log-doc-btn-icon--view"
                                       href="{{ route('logistica.documentos.show', $documento) }}"
                                       title="Ver documento">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a class="log-doc-btn-icon log-doc-btn-icon--down"
                                       href="{{ route('logistica.documentos.download', $documento) }}"
                                       title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @if(\App\Support\DocumentoEntregaCatalogo::puedeEditar($documento, auth()->user()))
                                        <a class="log-doc-btn-icon"
                                           href="{{ route('logistica.documentos.edit', $documento) }}"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    @if(\App\Support\DocumentoEntregaCatalogo::puedeEliminar($documento, auth()->user()))
                                        <form action="{{ route('logistica.documentos.destroy', $documento) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar este documento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="log-doc-btn-icon" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="log-doc-empty">
                                    <i class="far fa-folder-open d-block mb-2" style="font-size:1.5rem;"></i>
                                    No hay documentos con esos filtros.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($documentos->hasPages())
        <div class="log-doc-footer">{{ $documentos->links() }}</div>
        @endif
    </div>
</div>
@endsection
