@extends('layouts.app')



@section('title', 'Reportes de almacén')

@section('page_title', 'Reportes de almacén')



@push('styles')

@include('partials.modulo-inventario-styles')

<style>

.page-rep-almacen .rep-kpi-row { margin-left: -8px; margin-right: -8px; }

.page-rep-almacen .rep-kpi-col { padding-left: 8px; padding-right: 8px; margin-bottom: 1rem; }

.page-rep-almacen .rep-card {

    border: 1px solid #e2e8f0;

    border-radius: 12px;

    box-shadow: 0 2px 12px rgba(0,0,0,.06);

    margin-bottom: 1.25rem;

}

.page-rep-almacen .rep-card .card-header {
    background: linear-gradient(135deg, #f0f7f1, #fff);
    border-bottom: 1px solid #e8f0e9;
    border-radius: 12px 12px 0 0;
    padding: .85rem 1.15rem;
}
.page-rep-almacen .rep-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #2c5530;
    margin: 0;
    display: flex;
    align-items: center;
    gap: .45rem;
}
.page-rep-almacen .rep-section-title i { color: #4a7c59; }
.page-rep-almacen .rep-section-spaced { margin-top: 1.75rem; }

.page-rep-almacen .small-box { border-radius: 12px; margin-bottom: 0; }

.page-rep-almacen .small-box .inner h3 { font-size: 2rem; }

.page-rep-almacen .small-box .inner p { font-size: .9rem; opacity: .95; }

.page-rep-almacen .small-box .inner small { display: block; margin-top: .25rem; opacity: .85; font-size: .75rem; }

.page-rep-almacen .quick-period .btn { margin: 0 .35rem .35rem 0; border-radius: 20px; }

.page-rep-almacen .table-modulo thead th { background: #f2f7f3; }

.page-rep-almacen .rep-dual-row {
    align-items: flex-start;
}

.page-rep-almacen .rep-card-fit {
    height: auto;
}

.page-rep-almacen .rep-stock-panel {
    padding: .65rem 1rem 1rem;
}

.page-rep-almacen .rep-stock-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.page-rep-almacen .rep-stock-item {
    padding: .65rem .15rem;
    border-bottom: 1px solid #eef2f0;
}

.page-rep-almacen .rep-stock-item:last-child {
    border-bottom: 0;
    padding-bottom: .25rem;
}

.page-rep-almacen .rep-stock-item-head {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: .75rem;
    margin-bottom: .35rem;
}

.page-rep-almacen .rep-stock-name {
    font-weight: 600;
    color: #1e293b;
    font-size: .9rem;
    line-height: 1.3;
}

.page-rep-almacen .rep-stock-value {
    font-weight: 700;
    color: #2c5530;
    font-size: .95rem;
    white-space: nowrap;
}

.page-rep-almacen .rep-stock-bar {
    height: 6px;
    background: #e8f0ea;
    border-radius: 999px;
    overflow: hidden;
}

.page-rep-almacen .rep-stock-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #4a7c59, #2c5530);
    border-radius: 999px;
    min-width: 4px;
    transition: width .25s ease;
}

.page-rep-almacen .rep-stock-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: .75rem;
    padding-top: .65rem;
    border-top: 1px dashed #e2e8f0;
    font-size: .78rem;
    color: #64748b;
}

.page-rep-almacen .rep-stock-empty {
    text-align: center;
    padding: 1.25rem .5rem;
    color: #94a3b8;
    font-size: .88rem;
}

.page-rep-almacen .rep-stock-empty i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: .4rem;
    opacity: .6;
}

.page-rep-almacen .rep-panel {
    padding: .65rem 1rem 1rem;
}

.page-rep-almacen .rep-prod-item {
    padding: .7rem .15rem;
    border-bottom: 1px solid #eef2f0;
}

.page-rep-almacen .rep-prod-item:last-child {
    border-bottom: 0;
    padding-bottom: .25rem;
}

.page-rep-almacen .rep-prod-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .65rem;
    margin-bottom: .45rem;
}

.page-rep-almacen .rep-prod-name {
    font-weight: 600;
    color: #1e293b;
    font-size: .9rem;
    line-height: 1.3;
}

.page-rep-almacen .rep-prod-qty {
    font-weight: 700;
    color: #2c5530;
    font-size: .88rem;
    white-space: nowrap;
}

.page-rep-almacen .rep-prod-stats {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-bottom: .4rem;
}

.page-rep-almacen .rep-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    padding: .15rem .5rem;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 600;
}

.page-rep-almacen .rep-stat-pill.ingreso {
    background: #e8f5e9;
    color: #2e7d32;
}

.page-rep-almacen .rep-stat-pill.salida {
    background: #fff3e0;
    color: #e65100;
}

.page-rep-almacen .rep-prod-bar {
    height: 6px;
    background: #e8f0ea;
    border-radius: 999px;
    overflow: hidden;
    display: flex;
}

.page-rep-almacen .rep-prod-bar-ing {
    background: linear-gradient(90deg, #4a7c59, #2c5530);
    min-width: 2px;
}

.page-rep-almacen .rep-prod-bar-sal {
    background: linear-gradient(90deg, #f59e0b, #d97706);
    min-width: 2px;
}

.page-rep-almacen .rep-prod-meta,
.page-rep-almacen .rep-rec-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: .75rem;
    padding-top: .65rem;
    border-top: 1px dashed #e2e8f0;
    font-size: .78rem;
    color: #64748b;
}

.page-rep-almacen .rep-empty {
    text-align: center;
    padding: 1.25rem .5rem;
    color: #94a3b8;
    font-size: .88rem;
}

.page-rep-almacen .rep-empty i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: .4rem;
    opacity: .6;
}

.page-rep-almacen .rep-rec-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.page-rep-almacen .rep-rec-item {
    display: flex;
    gap: .75rem;
    padding: .65rem .15rem;
    border-bottom: 1px solid #eef2f0;
    align-items: flex-start;
}

.page-rep-almacen .rep-rec-item:last-child {
    border-bottom: 0;
    padding-bottom: .25rem;
}

.page-rep-almacen .rep-rec-marker {
    width: 4px;
    min-height: 44px;
    border-radius: 999px;
    flex-shrink: 0;
    margin-top: .15rem;
}

.page-rep-almacen .rep-rec-marker.ingreso { background: #4a7c59; }
.page-rep-almacen .rep-rec-marker.salida { background: #f59e0b; }

.page-rep-almacen .rep-rec-body { flex: 1; min-width: 0; }

.page-rep-almacen .rep-rec-top {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: baseline;
    gap: .5rem;
    margin-bottom: .25rem;
}

.page-rep-almacen .rep-rec-producto {
    font-weight: 600;
    color: #1e293b;
    font-size: .9rem;
}

.page-rep-almacen .rep-rec-cantidad {
    font-weight: 700;
    color: #2c5530;
    font-size: .88rem;
    white-space: nowrap;
}

.page-rep-almacen .rep-rec-sub {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .65rem;
    font-size: .78rem;
    color: #64748b;
}

.page-rep-almacen .rep-rec-tipo {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    padding: .1rem .45rem;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 600;
}

.page-rep-almacen .rep-rec-tipo.ingreso {
    background: #e8f5e9;
    color: #2e7d32;
}

.page-rep-almacen .rep-rec-tipo.salida {
    background: #fff3e0;
    color: #e65100;
}

</style>

@endpush



@section('content')

<div class="modulo-inv page-rep-almacen">



    <div class="alert alert-light border mb-3">

        <i class="fas fa-chart-bar text-success mr-1"></i>

        Resumen de <strong>movimientos</strong> (cada registro cuenta como un ingreso o salida), no la suma de kilos.

        Use las tablas inferiores para ver cantidades por producto.

    </div>



    <div class="row rep-kpi-row mb-1">

        <div class="col-md-4 rep-kpi-col">

            <div class="small-box small-box-green mb-0">

                <div class="inner">

                    <h3>{{ $totalIngresos ?? 0 }}</h3>

                    <p>Ingresos registrados</p>

                    <small>Operaciones de entrada en el período</small>

                </div>

                <div class="icon"><i class="fas fa-arrow-down"></i></div>

            </div>

        </div>

        <div class="col-md-4 rep-kpi-col">

            <div class="small-box small-box-yellow mb-0">

                <div class="inner">

                    <h3>{{ $totalSalidas ?? 0 }}</h3>

                    <p>Salidas registradas</p>

                    <small>Operaciones de salida en el período</small>

                </div>

                <div class="icon"><i class="fas fa-arrow-up"></i></div>

            </div>

        </div>

        <div class="col-md-4 rep-kpi-col">

            <div class="small-box small-box-blue mb-0">

                <div class="inner">

                    <h3>{{ $movimientos->count() }}</h3>

                    <p>Movimientos listados</p>

                    <small>Últimos registros del filtro aplicado</small>

                </div>

                <div class="icon"><i class="fas fa-exchange-alt"></i></div>

            </div>

        </div>

    </div>



    <div class="card rep-card">

        <div class="card-header py-3">
            <h3 class="rep-section-title mb-0"><i class="fas fa-filter"></i> Filtros del reporte</h3>
        </div>

        <form method="GET" action="{{ route(($rutaPrefijo ?? 'almacen-agricola').'.movimientos.reportes') }}">

            <div class="card-body">

                <div class="mb-3 quick-period">

                    <label class="d-block mb-2 text-muted small font-weight-bold">Período rápido</label>

                    @php

                        $periodos = [

                            'hoy' => 'Hoy',

                            '7d' => 'Últimos 7 días',

                            '30d' => 'Últimos 30 días',

                            '90d' => 'Últimos 90 días',

                            'mes_actual' => 'Mes actual',

                            'mes_pasado' => 'Mes pasado',

                        ];

                    @endphp

                    @foreach($periodos as $clave => $label)

                        <button type="submit"

                                name="periodo"

                                value="{{ $clave }}"

                                class="btn btn-sm {{ $periodoActivo === $clave ? 'btn-success' : 'btn-outline-success' }}">

                            {{ $label }}

                        </button>

                    @endforeach

                </div>

                <div class="form-row">

                    <div class="form-group col-md-4 mb-md-0">

                        <label class="small text-muted">Almacén</label>

                        <select class="form-control form-control-sm" name="almacenid">

                            <option value="">Todos</option>

                            @foreach($almacenes as $almacen)

                                <option value="{{ $almacen->almacenid }}" @selected((int) $almacenId === (int) $almacen->almacenid)>{{ $almacen->nombre }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div class="form-group col-md-3 mb-md-0">

                        <label class="small text-muted">Fecha desde</label>

                        <input type="date" class="form-control form-control-sm" name="fecha_desde" value="{{ $fechaDesde }}">

                    </div>

                    <div class="form-group col-md-3 mb-md-0">

                        <label class="small text-muted">Fecha hasta</label>

                        <input type="date" class="form-control form-control-sm" name="fecha_hasta" value="{{ $fechaHasta }}">

                    </div>

                    <div class="form-group col-md-2 d-flex align-items-end mb-md-0">

                        <button class="btn btn-success btn-sm w-100" name="periodo" value="personalizado">

                            <i class="fas fa-search mr-1"></i> Aplicar

                        </button>

                    </div>

                </div>

                <p class="text-muted small mb-0 mt-2">

                    Período activo:

                    <strong>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }}</strong>

                    —

                    <strong>{{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</strong>

                </p>

            </div>

        </form>

    </div>



    @php
        $stockMax = max(1, (float) ($stockPorAlmacen->max('stock') ?? 0));
        $stockTotal = (float) $stockPorAlmacen->sum('stock');
        $totalIngProd = (int) $resumenProducto->sum('ingresos');
        $totalSalProd = (int) $resumenProducto->sum('salidas');
        $totalKgIngProd = (float) $resumenProducto->sum('cantidad_ingresos');
    @endphp

    <div class="row rep-dual-row">

        <div class="col-lg-6 mb-3 mb-lg-0">

            <div class="card rep-card rep-card-fit">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="rep-section-title mb-0">
                        <i class="fas fa-boxes"></i> Movimientos por producto
                    </h3>
                    @if($resumenProducto->isNotEmpty())
                        <span class="badge badge-success">{{ $resumenProducto->count() }} producto{{ $resumenProducto->count() === 1 ? '' : 's' }}</span>
                    @endif
                </div>

                <div class="rep-panel">
                    @forelse($resumenProducto as $item)
                        @php
                            $ing = (int) $item->ingresos;
                            $sal = (int) $item->salidas;
                            $movTotal = max(1, $ing + $sal);
                            $pctIng = round(($ing / $movTotal) * 100, 1);
                            $pctSal = round(($sal / $movTotal) * 100, 1);
                            $kgIng = (float) ($item->cantidad_ingresos ?? 0);
                        @endphp
                        <div class="rep-prod-item">
                            <div class="rep-prod-head">
                                <span class="rep-prod-name">{{ $item->producto }}</span>
                                <span class="rep-prod-qty">{{ number_format($kgIng, 2) }} ingresados</span>
                            </div>
                            <div class="rep-prod-stats">
                                <span class="rep-stat-pill ingreso">
                                    <i class="fas fa-arrow-down"></i> {{ $ing }} ingreso{{ $ing === 1 ? '' : 's' }}
                                </span>
                                <span class="rep-stat-pill salida">
                                    <i class="fas fa-arrow-up"></i> {{ $sal }} salida{{ $sal === 1 ? '' : 's' }}
                                </span>
                            </div>
                            <div class="rep-prod-bar" title="Proporción ingresos vs salidas">
                                @if($ing > 0)
                                    <div class="rep-prod-bar-ing" style="width: {{ $pctIng }}%;"></div>
                                @endif
                                @if($sal > 0)
                                    <div class="rep-prod-bar-sal" style="width: {{ $pctSal }}%;"></div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="rep-empty">
                            <i class="fas fa-boxes"></i>
                            Sin movimientos en este período
                        </div>
                    @endforelse

                    @if($resumenProducto->isNotEmpty())
                        <div class="rep-prod-meta">
                            <span>{{ $totalIngProd }} ingresos · {{ $totalSalProd }} salidas</span>
                            <strong class="text-success">{{ number_format($totalKgIngProd, 2) }} kg/un. total</strong>
                        </div>
                    @endif
                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card rep-card rep-card-fit">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="rep-section-title mb-0">
                        <i class="fas fa-warehouse"></i> Stock actual por almacén
                    </h3>
                    @if($stockPorAlmacen->isNotEmpty())
                        <span class="badge badge-success">{{ $stockPorAlmacen->count() }} almacén{{ $stockPorAlmacen->count() === 1 ? '' : 'es' }}</span>
                    @endif
                </div>

                <div class="rep-stock-panel">
                    @forelse($stockPorAlmacen as $item)
                        @php
                            $stockValor = (float) $item->stock;
                            $porcentaje = min(100, round(($stockValor / $stockMax) * 100, 1));
                        @endphp
                        <div class="rep-stock-item">
                            <div class="rep-stock-item-head">
                                <span class="rep-stock-name">{{ $item->almacen }}</span>
                                <span class="rep-stock-value">{{ number_format($stockValor, 2) }}</span>
                            </div>
                            <div class="rep-stock-bar" title="{{ $porcentaje }}% del mayor stock">
                                <div class="rep-stock-bar-fill" style="width: {{ $porcentaje }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rep-stock-empty">
                            <i class="fas fa-warehouse"></i>
                            Sin stock registrado
                        </div>
                    @endforelse

                    @if($stockPorAlmacen->isNotEmpty())
                        <div class="rep-stock-meta">
                            <span>Total acumulado</span>
                            <strong class="text-success">{{ number_format($stockTotal, 2) }}</strong>
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>



    <div class="card rep-card mb-0 rep-section-spaced">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="rep-section-title mb-0">
                <i class="fas fa-history"></i> Movimientos recientes
            </h3>
            <span class="badge badge-success">{{ $movimientos->count() }} registro{{ $movimientos->count() === 1 ? '' : 's' }}</span>
        </div>

        <div class="rep-panel">
            <ul class="rep-rec-list">
                @forelse($movimientos as $mov)
                    @php
                        $esIngreso = $mov->tipo?->naturaleza === 'ingreso';
                        $unidad = $mov->insumo?->unidadMedida?->abreviatura ?? '';
                    @endphp
                    <li class="rep-rec-item">
                        <div class="rep-rec-marker {{ $esIngreso ? 'ingreso' : 'salida' }}"></div>
                        <div class="rep-rec-body">
                            <div class="rep-rec-top">
                                <span class="rep-rec-producto">{{ $mov->insumo?->nombre ?? '—' }}</span>
                                <span class="rep-rec-cantidad">
                                    {{ number_format((float) $mov->cantidad, 2) }}
                                    @if($unidad)<small class="text-muted">{{ $unidad }}</small>@endif
                                </span>
                            </div>
                            <div class="rep-rec-sub">
                                <span class="rep-rec-tipo {{ $esIngreso ? 'ingreso' : 'salida' }}">
                                    <i class="fas fa-arrow-{{ $esIngreso ? 'down' : 'up' }}"></i>
                                    {{ $mov->tipo?->nombre ?? 'Movimiento' }}
                                </span>
                                <span><i class="fas fa-calendar-alt mr-1"></i>{{ optional($mov->fecha)->format('d/m/Y') }}</span>
                                <span><i class="fas fa-warehouse mr-1"></i>{{ $mov->almacen?->nombre ?? '—' }}</span>
                            </div>
                        </div>
                    </li>
                @empty
                    <li>
                        <div class="rep-empty">
                            <i class="fas fa-history"></i>
                            Sin movimientos en el período
                        </div>
                    </li>
                @endforelse
            </ul>

            @if($movimientos->isNotEmpty())
                <div class="rep-rec-meta">
                    <span>Últimos movimientos del período filtrado</span>
                    <span>{{ \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') }}</span>
                </div>
            @endif
        </div>

    </div>



</div>

@endsection

