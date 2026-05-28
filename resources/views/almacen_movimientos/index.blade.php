@extends('layouts.app')

@section('title', 'Movimientos de almacén')
@section('page_title', 'Movimientos de almacén')
@push('styles')
<style>
.x-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(18,38,63,.08)}
.x-table thead th{background:#f2f7f3;border-bottom:0}
.card-footer .pagination{margin:0;justify-content:center}
.mov-filter-card{cursor:pointer;transition:transform .15s ease,box-shadow .15s ease;text-decoration:none!important;color:inherit!important;display:block}
.mov-filter-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(18,38,63,.12)}
.mov-filter-card.active{outline:3px solid rgba(255,255,255,.85);outline-offset:-3px}
.mov-filter-card .small-box .icon{font-size:70px!important;right:15px;top:10px}
.mov-filter-card .small-box .icon>i{font-size:inherit!important}
</style>
@endpush

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row mb-2">
        <div class="col-12">
            <p class="text-muted mb-0">
                <i class="fas fa-filter mr-1"></i>
                Pulse una tarjeta para filtrar por tipo de movimiento.
                @if($filtroNaturaleza)
                    Filtro activo: <strong>{{ $filtroNaturaleza === 'ingreso' ? 'Ingresos' : 'Salidas' }}</strong>
                    — <a href="{{ route('almacen-movimientos.index') }}">Ver todos</a>
                @endif
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <a href="{{ route('almacen-movimientos.index', ['naturaleza' => 'ingreso']) }}"
               class="mov-filter-card {{ $filtroNaturaleza === 'ingreso' ? 'active' : '' }}">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $totalIngresos }}</h3>
                        <p>Ingresos registrados</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-down"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('almacen-movimientos.index', ['naturaleza' => 'salida']) }}"
               class="mov-filter-card {{ $filtroNaturaleza === 'salida' ? 'active' : '' }}">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $totalSalidas }}</h3>
                        <p>Salidas registradas</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-up"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('almacen-movimientos.index') }}"
               class="mov-filter-card {{ $filtroNaturaleza === '' ? 'active' : '' }}">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $totalMovimientos }}</h3>
                        <p>Total de movimientos</p>
                    </div>
                    <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="card x-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h3 class="card-title mb-0">Registro de ingresos y salidas</h3>
            <div class="d-flex flex-wrap" style="gap:8px;">
                @can('almacen.ingresos.create')
                    <a class="btn btn-success btn-sm" href="{{ route('almacen-movimientos.create', ['naturaleza' => 'ingreso']) }}">Nuevo ingreso</a>
                @endcan
                @can('almacen.salidas.create')
                    <a class="btn btn-warning btn-sm" href="{{ route('almacen-movimientos.create', ['naturaleza' => 'salida']) }}">Nueva salida</a>
                @endcan
                @can('almacen.reportes.view')
                    <a class="btn btn-info btn-sm" href="{{ route('almacen-movimientos.reportes') }}">Reportes</a>
                @endcan
            </div>
        </div>
        <div class="card-body border-bottom pb-2">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <input type="text" id="movSearch" class="form-control form-control-sm" placeholder="Buscar por insumo, responsable o referencia...">
                </div>
                <div class="form-group col-md-3">
                    <select id="movFiltroAlmacen" class="form-control form-control-sm">
                        <option value="">Todos los almacenes</option>
                        @foreach($movimientos->pluck('almacen.nombre')->filter()->unique()->sort() as $nombreAlmacen)
                            <option value="{{ strtolower($nombreAlmacen) }}">{{ $nombreAlmacen }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <select id="movFiltroTipo" class="form-control form-control-sm">
                        <option value="">Todos los tipos</option>
                        @foreach($movimientos->pluck('tipo.nombre')->filter()->unique()->sort() as $nombreTipo)
                            <option value="{{ strtolower($nombreTipo) }}">{{ $nombreTipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <select id="movFiltroNaturaleza" class="form-control form-control-sm">
                        <option value="">Ingreso/Salida</option>
                        <option value="ingreso">Ingresos</option>
                        <option value="salida">Salidas</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped mb-0 x-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Almacen</th>
                        <th>Insumo</th>
                        <th class="text-right">Cantidad</th>
                        <th>Responsable</th>
                        <th>Referencia</th>
                        <th class="text-center" style="width:90px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                        <tr class="mov-row"
                            data-search="{{ strtolower(($mov->insumo?->nombre ?? '').' '.trim(($mov->usuario?->nombre ?? '').' '.($mov->usuario?->apellido ?? '')).' '.($mov->referencia ?? '')) }}"
                            data-almacen="{{ strtolower($mov->almacen?->nombre ?? '') }}"
                            data-tipo="{{ strtolower($mov->tipo?->nombre ?? '') }}"
                            data-naturaleza="{{ strtolower($mov->tipo?->naturaleza ?? '') }}">
                            <td>{{ optional($mov->fecha)->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge {{ $mov->tipo?->naturaleza === 'ingreso' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $mov->tipo?->nombre ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $mov->almacen?->nombre ?? '-' }}</td>
                            <td>{{ $mov->insumo?->nombre ?? '-' }}</td>
                            <td class="text-right">{{ number_format((float) $mov->cantidad, 3) }} {{ $mov->insumo?->unidadMedida?->abreviatura }}</td>
                            <td>{{ trim(($mov->usuario?->nombre ?? '') . ' ' . ($mov->usuario?->apellido ?? '')) ?: '-' }}</td>
                            <td>{{ $mov->referencia ?: '-' }}</td>
                            <td class="text-center">
                                <a href="{{ route('almacen-movimientos.show', ['almacenMovimiento' => $mov->almacen_movimientoid, 'naturaleza' => $filtroNaturaleza]) }}"
                                   class="btn btn-outline-primary btn-xs"
                                   title="Ver detalle">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay movimientos para este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movimientos->hasPages())
        <div class="card-footer d-flex justify-content-center">
            {{ $movimientos->links() }}
        </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const q = document.getElementById('movSearch');
    const fAlmacen = document.getElementById('movFiltroAlmacen');
    const fTipo = document.getElementById('movFiltroTipo');
    const fNaturaleza = document.getElementById('movFiltroNaturaleza');
    const rows = Array.from(document.querySelectorAll('.mov-row'));

    function aplicarFiltroMovimientos() {
        const val = (q?.value || '').toLowerCase().trim();
        const alm = (fAlmacen?.value || '').toLowerCase();
        const tipo = (fTipo?.value || '').toLowerCase();
        const nat = (fNaturaleza?.value || '').toLowerCase();

        rows.forEach((tr) => {
            const okSearch = !val || (tr.dataset.search || '').includes(val);
            const okAlm = !alm || (tr.dataset.almacen || '') === alm;
            const okTipo = !tipo || (tr.dataset.tipo || '') === tipo;
            const okNat = !nat || (tr.dataset.naturaleza || '') === nat;
            tr.style.display = (okSearch && okAlm && okTipo && okNat) ? '' : 'none';
        });
    }

    [q, fAlmacen, fTipo, fNaturaleza].forEach((el) => el && el.addEventListener('input', aplicarFiltroMovimientos));
    [fAlmacen, fTipo, fNaturaleza].forEach((el) => el && el.addEventListener('change', aplicarFiltroMovimientos));
});
</script>
@endpush
