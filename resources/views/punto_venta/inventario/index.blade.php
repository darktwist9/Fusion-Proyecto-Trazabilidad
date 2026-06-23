@extends('layouts.app')

@section('title', 'Inventario — Puntos de venta')
@section('page_title', 'Inventario')

@push('styles')
@include('punto_venta.partials.modulo-styles')
<style>
.pdv-inv-tabla { table-layout: fixed; width: 100%; }
.pdv-inv-tabla th,
.pdv-inv-tabla td { vertical-align: middle; padding: .45rem .75rem; }
.pdv-inv-tabla th:nth-child(1),
.pdv-inv-tabla td:nth-child(1) { width: 28%; }
.pdv-inv-tabla th:nth-child(2),
.pdv-inv-tabla td:nth-child(2) { width: 22%; }
.pdv-inv-tabla th:nth-child(3),
.pdv-inv-tabla td:nth-child(3) { width: 16%; }
.pdv-inv-tabla th:nth-child(4),
.pdv-inv-tabla td:nth-child(4) { width: 14%; }
.pdv-inv-tabla th:nth-child(5),
.pdv-inv-tabla td:nth-child(5) { width: 10%; }
.pdv-inv-tabla th:nth-child(6),
.pdv-inv-tabla td:nth-child(6) { width: 10%; }
.pdv-inv-tabla thead th { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
.pdv-inv-tabla td:nth-child(2) a { font-weight: 600; color: #047857; text-decoration: none; }
.pdv-inv-tabla td:nth-child(2) a:hover { text-decoration: underline; }
.pdv-inv-tabla tbody tr + tr td { border-top-color: #eef2f7; }
.pdv-inv-tabla .pdv-inv-meta { font-size: .78rem; line-height: 1.25; }
</style>
@endpush

@section('content')
<x-modulo-index-header
    titulo="Inventario de puntos de venta"
    icono="fa-boxes"
    :registros="$lineas->count()"
    filtros-target="#filtrosInventarioPdv"
/>

<div class="card pdv-card border-0 shadow-sm">
    <div class="modulo-filtros-panel collapse show" id="filtrosInventarioPdv">
        <form method="GET" action="{{ route('punto-venta.inventario.index') }}" class="form-row align-items-end">
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="small text-muted mb-1">Punto de venta</label>
                @include('partials.selector-catalogo', [
                    'id' => 'inv_filtro_pdv',
                    'name' => 'puntoventaid',
                    'value' => $filtroPdv ?? '',
                    'labelSelected' => $filtroPdvNombre ?? '',
                    'endpoint' => route('catalogo-selector.puntos-venta'),
                    'title' => 'Filtrar por punto de venta',
                    'icon' => 'fa-store',
                    'searchPlaceholder' => 'Nombre, dirección o minorista…',
                    'searchLabel' => 'Buscar punto de venta',
                    'allowEmpty' => true,
                    'emptyLabel' => ($esAdmin ?? false) ? 'Todos los puntos' : 'Todos mis puntos',
                    'placeholderEmpty' => ($esAdmin ?? false) ? 'Todos los puntos' : 'Todos mis puntos',
                    'modalIcon' => 'fa-store',
                    'rowIcon' => 'fa-store',
                    'colNombre' => 'Punto de venta',
                    'colDetalle' => 'Minorista / ubicación',
                    'inputGroup' => true,
                    'variant' => 'filtros',
                ])
            </div>
            <div class="col-md-5 mb-2 mb-md-0">
                <label class="small text-muted mb-1">Buscar producto</label>
                <input type="text" name="q" class="form-control form-control-sm" value="{{ $filtroQ }}" placeholder="Nombre, empaque o código trazabilidad…">
            </div>
            <div class="col-md-3 mb-0 d-flex modulo-filtros-acciones">
                <button type="submit" class="btn btn-success btn-filtro-modulo"><i class="fas fa-search mr-1"></i> Filtrar</button>
                <a href="{{ route('punto-venta.inventario.index') }}" class="btn btn-light border btn-filtro-modulo">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover m-0 pdv-inv-tabla">
                <thead class="bg-light">
                    <tr>
                        <th>Producto</th>
                        <th>Punto de venta</th>
                        <th>Empaque</th>
                        <th>Unidades</th>
                        <th>Peso</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lineas as $linea)
                        @php
                            $insumo = $linea['insumo'];
                            $punto = $linea['punto'];
                            $unidadesFmt = number_format($linea['unidades'], fmod($linea['unidades'], 1.0) === 0.0 ? 0 : 2);
                        @endphp
                        <tr>
                            <td>
                                <strong class="d-block">{{ $linea['producto_nombre'] }}</strong>
                                @if($insumo->codigo_trazabilidad)
                                    <small class="text-muted pdv-inv-meta">{{ $insumo->codigo_trazabilidad }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('punto-venta.puntos.show', $punto) }}">{{ $punto->nombre }}</a>
                                @if($esAdmin ?? false)
                                    <br><small class="text-muted pdv-inv-meta">{{ $punto->nombreMinorista() }}</small>
                                @endif
                            </td>
                            <td>{{ $linea['presentacion_nombre'] }}</td>
                            <td>{{ $unidadesFmt }} {{ $linea['unidad_etiqueta'] }}</td>
                            <td>{{ number_format($linea['kg'], 2) }} kg</td>
                            <td class="text-center text-nowrap pdv-inv-acciones">
                                @can('punto_venta.view')
                                <a href="{{ route('punto-venta.puntos.show', $punto) }}" class="btn btn-xs btn-outline-primary" title="Ver punto de venta">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @endcan
                                @can('punto_venta.update')
                                <a href="{{ route('punto-venta.puntos.inventario.edit', [$punto, $insumo]) }}?return=inventario"
                                   class="btn btn-xs btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                @can('punto_venta.view')
                                <button type="button" class="btn btn-xs btn-outline-success btn-qr-inventario"
                                        title="Ver QR"
                                        data-url="{{ route('punto-venta.puntos.inventario.qr', [$punto, $insumo]) }}"
                                        data-producto="{{ $linea['producto_nombre'] }}">
                                    <i class="fas fa-qrcode"></i>
                                </button>
                                @endcan
                                @can('punto_venta.delete')
                                <form method="POST" action="{{ route('punto-venta.puntos.inventario.destroy', [$punto, $insumo]) }}" class="d-inline form-eliminar-insumo">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="return" value="inventario">
                                    <button type="button" class="btn btn-xs btn-outline-danger" title="Eliminar"
                                            data-confirm-modal data-confirm-title="Eliminar producto"
                                            data-confirm-message="¿Eliminar «{{ $linea['producto_nombre'] }}» del inventario?">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Sin productos en inventario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('punto_venta.inventario.partials.modal-qr')
@include('partials.modal-confirmar-accion')
@endsection

@push('scripts')
@include('punto_venta.inventario.partials.qr-scripts')
@endpush
