@extends('layouts.app')

@section('title', 'Recursos productivos')

@section('content')
@php
    $totalCultivos = $cultivos->count();
    $totalInsumos = $insumos->count();
    $stockBajo = $insumos->filter(fn($i) => (float) $i->stock <= (float) $i->stockminimo)->count();
    $tipos = $insumos->pluck('tipo.nombre')->filter()->unique()->sort()->values();
@endphp

@push('styles')
<style>
.x-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(18,38,63,.08)}
.kpi{border-radius:12px;color:#fff;padding:16px 18px}
.kpi h4{margin:0;font-weight:700}
.kpi p{margin:2px 0 0;opacity:.9}
.kpi.c1{background:linear-gradient(135deg,#2c5530,#4a7c59)}
.kpi.c2{background:linear-gradient(135deg,#1565c0,#42a5f5)}
.kpi.c3{background:linear-gradient(135deg,#e65100,#ff7043)}
.chip{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.chip.ok{background:#e8f5e9;color:#2e7d32}
.chip.warn{background:#fff3e0;color:#ef6c00}
</style>
@endpush

<div class="alert alert-info x-card">
    <strong>¿Para qué sirve esta vista?</strong>
    Te muestra en un solo lugar qué cultivos maneja la operación y qué insumos están disponibles para abastecerlos.
    Úsala para detectar insumos críticos y decidir compras/reabastecimiento.
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-2"><div class="kpi c1"><h4>{{ $totalCultivos }}</h4><p>Cultivos activos</p></div></div>
    <div class="col-md-4 mb-2"><div class="kpi c2"><h4>{{ $totalInsumos }}</h4><p>Insumos registrados</p></div></div>
    <div class="col-md-4 mb-2"><div class="kpi c3"><h4>{{ $stockBajo }}</h4><p>Insumos en nivel crítico</p></div></div>
</div>

<div class="card x-card mb-3">
    <div class="card-body py-3">
        <div class="form-row">
            <div class="form-group col-md-5 mb-2">
                <input type="text" id="rpSearch" class="form-control" placeholder="Buscar por nombre de insumo o proveedor...">
            </div>
            <div class="form-group col-md-4 mb-2">
                <select id="rpTipo" class="form-control">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ strtolower($tipo) }}">{{ $tipo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <select id="rpStock" class="form-control">
                    <option value="">Todos los niveles</option>
                    <option value="bajo">Stock bajo</option>
                    <option value="normal">Stock normal</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card x-card">
            <div class="card-header"><strong>Cultivos (referencia operativa)</strong></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($cultivos as $cultivo)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $cultivo->nombre }}</span>
                            <span class="chip ok">Cultivo</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Sin cultivos registrados. Cárgalos desde `Catálogos > Cultivos`.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card x-card">
            <div class="card-header"><strong>Insumos disponibles para abastecimiento</strong></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Nivel</th>
                            <th>Stock</th>
                            <th>Stock mín.</th>
                            <th>Actor / proveedor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($insumos as $insumo)
                            @php
                                $esBajo = (float) $insumo->stock <= (float) $insumo->stockminimo;
                                $actor = $insumo->actorAbastecimiento->nombre ?? $insumo->proveedor ?? '-';
                            @endphp
                            <tr class="rp-row"
                                data-search="{{ strtolower($insumo->nombre.' '.$actor) }}"
                                data-tipo="{{ strtolower($insumo->tipo->nombre ?? '') }}"
                                data-stock="{{ $esBajo ? 'bajo' : 'normal' }}">
                                <td>{{ $insumo->nombre }}</td>
                                <td>{{ $insumo->tipo->nombre ?? '-' }}</td>
                                <td>
                                    <span class="chip {{ $esBajo ? 'warn' : 'ok' }}">
                                        {{ $esBajo ? 'Crítico' : 'Normal' }}
                                    </span>
                                </td>
                                <td>{{ number_format((float)$insumo->stock, 2) }} {{ $insumo->unidadMedida->abreviatura ?? '' }}</td>
                                <td>{{ number_format((float)$insumo->stockminimo, 2) }}</td>
                                <td>{{ $actor }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Sin insumos registrados. Puedes crearlos desde `Inventario > Insumos`.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const q = document.getElementById('rpSearch');
    const t = document.getElementById('rpTipo');
    const s = document.getElementById('rpStock');
    const rows = Array.from(document.querySelectorAll('.rp-row'));

    function filtrar() {
        const vq = (q?.value || '').toLowerCase().trim();
        const vt = (t?.value || '').toLowerCase();
        const vs = (s?.value || '').toLowerCase();
        rows.forEach((tr) => {
            const okQ = !vq || (tr.dataset.search || '').includes(vq);
            const okT = !vt || (tr.dataset.tipo || '') === vt;
            const okS = !vs || (tr.dataset.stock || '') === vs;
            tr.style.display = (okQ && okT && okS) ? '' : 'none';
        });
    }

    [q, t, s].forEach((el) => el && el.addEventListener('input', filtrar));
    [t, s].forEach((el) => el && el.addEventListener('change', filtrar));
});
</script>
@endpush

