@extends('layouts.app')

@section('title', $solicitud->numero_solicitud)
@section('page_title', 'Solicitud '.$solicitud->numero_solicitud)

@section('content')
<div class="mb-3">
    <a href="{{ route('planta.solicitudes-produccion.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h4 class="mb-3">{{ $solicitud->producto_nombre }}</h4>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Cantidad</dt>
                    <dd class="col-sm-8">{{ number_format((float) $solicitud->cantidad, 0) }} {{ $solicitud->unidad_etiqueta }}</dd>
                    <dt class="col-sm-4">Tipo envase</dt>
                    <dd class="col-sm-8">{{ \App\Support\SolicitudProduccionPlantaCatalogo::etiquetaTipoEnvase($solicitud->tipo_envase) }}</dd>
                    <dt class="col-sm-4">Entrega deseada</dt>
                    <dd class="col-sm-8">
                        @if($solicitud->fecha_entrega_deseada)
                            {{ $solicitud->fecha_entrega_deseada->format('d/m/Y') }}
                            @if($solicitud->hora_entrega_deseada)
                                · {{ substr((string) $solicitud->hora_entrega_deseada, 0, 5) }}
                            @endif
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8">{{ \App\Support\SolicitudProduccionPlantaCatalogo::etiquetaEstado($solicitud->estado) }}</dd>
                    <dt class="col-sm-4">Pedido PDV</dt>
                    <dd class="col-sm-8">
                        @if($solicitud->pedidoDistribucion)
                            <a href="{{ route('punto-venta.pedidos.show', $solicitud->pedidoDistribucion) }}">{{ $solicitud->pedidoDistribucion->numero_solicitud }}</a>
                            · {{ $solicitud->pedidoDistribucion->puntoVenta?->nombre }}
                        @else
                            —
                        @endif
                    </dd>
                    <dt class="col-sm-4">Solicitado por</dt>
                    <dd class="col-sm-8">{{ $solicitud->creadoPor?->nombre }} {{ $solicitud->creadoPor?->apellido }}</dd>
                    @if($solicitud->observaciones)
                        <dt class="col-sm-4">Observaciones</dt>
                        <dd class="col-sm-8">{{ $solicitud->observaciones }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @if($puedeGestionarPlanta)
            <div class="card">
                <div class="card-header"><strong>Acciones planta</strong></div>
                <div class="card-body">
                    @if(\App\Support\SolicitudProduccionPlantaCatalogo::puedeAceptarPlanta($solicitud))
                        <form method="POST" action="{{ route('planta.solicitudes-produccion.aceptar', $solicitud) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success btn-block">Aceptar solicitud</button>
                        </form>
                        <form method="POST" action="{{ route('planta.solicitudes-produccion.rechazar', $solicitud) }}">
                            @csrf
                            <textarea name="motivo_rechazo" class="form-control form-control-sm mb-2" rows="2" placeholder="Motivo rechazo (opcional)"></textarea>
                            <button type="submit" class="btn btn-outline-danger btn-block">Rechazar</button>
                        </form>
                    @elseif(\App\Support\SolicitudProduccionPlantaCatalogo::puedeMarcarProduccion($solicitud))
                        @if($solicitud->estado === \App\Support\SolicitudProduccionPlantaCatalogo::ESTADO_ACEPTADA)
                            <form method="POST" action="{{ route('planta.solicitudes-produccion.produccion', $solicitud) }}" class="mb-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block">Marcar en producción</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('planta.solicitudes-produccion.completar', $solicitud) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">Marcar completada</button>
                        </form>
                        <p class="small text-muted mt-2 mb-0">Luego traslade el producto al mayorista con el flujo planta → mayorista existente.</p>
                    @else
                        <p class="text-muted small mb-0">Solicitud cerrada.</p>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
