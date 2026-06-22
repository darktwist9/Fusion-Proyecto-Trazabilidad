@extends('layouts.app')

@section('title', 'Solicitudes de producción')
@section('page_title', 'Solicitudes de producción (mayorista → planta)')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h3 class="card-title mb-0">Bandeja planta</h3>
        <form method="GET" class="form-inline">
            <select name="estado" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach($etiquetasEstado as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected($estadoFiltro === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Solicitud</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Entrega</th>
                        <th>Pedido PDV</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($solicitudes as $sol)
                        <tr>
                            <td>{{ $sol->numero_solicitud }}</td>
                            <td>
                                {{ $sol->producto_nombre }}
                                @if($sol->tipo_envase)
                                    <br><small class="text-muted">{{ \App\Support\SolicitudProduccionPlantaCatalogo::etiquetaTipoEnvase($sol->tipo_envase) }}</small>
                                @endif
                            </td>
                            <td>{{ number_format((float) $sol->cantidad, 0) }} {{ $sol->unidad_etiqueta }}</td>
                            <td>
                                @if($sol->fecha_entrega_deseada)
                                    {{ $sol->fecha_entrega_deseada->format('d/m/Y') }}
                                    @if($sol->hora_entrega_deseada)
                                        · {{ substr((string) $sol->hora_entrega_deseada, 0, 5) }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $sol->pedidoDistribucion?->numero_solicitud ?? '—' }}</td>
                            <td>{{ \App\Support\SolicitudProduccionPlantaCatalogo::etiquetaEstado($sol->estado) }}</td>
                            <td class="text-right">
                                <a href="{{ route('planta.solicitudes-produccion.show', $sol) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay solicitudes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($solicitudes->hasPages())
        <div class="card-footer">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection
