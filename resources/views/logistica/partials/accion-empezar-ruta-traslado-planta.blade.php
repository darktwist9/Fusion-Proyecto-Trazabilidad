@php
    use App\Support\RutaDistribucionCatalogo;

    $usuario = auth()->user();
    $rutaPrefijo = $rutaPrefijo ?? 'logistica.traslados-planta';
    $puedeEmpezar = \App\Support\SimulacionRutaCatalogo::usuarioPuedeEmpezarDistribucion($usuario, $ruta);
    $simulacionActiva = \App\Support\SimulacionRutaCatalogo::simulacionActivaDistribucion($ruta);
    $estado = (string) ($ruta->estado ?? '');
    $enCierre = app(\App\Services\CierreEnvioPlantaMayoristaService::class)->tieneCondicionesVehiculo($ruta)
        || $simulacionActiva
        || $ruta->llegada_confirmada_at;
@endphp

@if($enCierre && $estado !== RutaDistribucionCatalogo::ESTADO_COMPLETADA)
    @include('logistica.partials.accion-cierre-operativo-traslado', ['ruta' => $ruta, 'rutaPrefijo' => $rutaPrefijo])
@elseif($puedeEmpezar)
    <a href="{{ route($rutaPrefijo.'.cierre.panel', $ruta) }}" class="btn btn-success btn-block btn-lg font-weight-bold">
        <i class="fas fa-clipboard-check mr-1"></i> Aceptar solicitud
    </a>
    <p class="small text-muted mb-0 mt-2">
        Registre condiciones del vehículo y complete el traslado hacia el almacén mayorista.
    </p>
@elseif($estado === RutaDistribucionCatalogo::ESTADO_COMPLETADA)
    @php
        $estadoVehiculo = \App\Support\EnvioCierreAgricolaCatalogo::etiquetaEstadoVehiculo(
            $ruta->checklistCondicionVehiculo?->estado_general
        );
    @endphp
    <div class="alert alert-success small mb-3">
        <i class="fas fa-check-circle mr-1"></i>
        <strong>Traslado completado.</strong>
        La mercancía llegó al almacén mayorista destino.
        @if($ruta->fecha_salida)
            <br><span class="text-muted">Salida de planta:</span>
            {{ $ruta->fecha_salida->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
        @endif
    </div>
    @if($estadoVehiculo !== '—')
        <p class="small text-muted mb-2">
            <i class="fas fa-clipboard-check mr-1"></i>
            <strong>Estado del vehículo:</strong> {{ $estadoVehiculo }}
        </p>
    @endif
    <a href="{{ route($rutaPrefijo.'.cierre.panel', $ruta) }}" class="btn btn-outline-primary btn-sm btn-block font-weight-bold">
        <i class="fas fa-list-alt mr-1"></i> Ver cierre operativo
    </a>
@elseif($estado === RutaDistribucionCatalogo::ESTADO_EN_RUTA)
    <div class="alert alert-info small mb-0">
        <i class="fas fa-truck mr-1"></i>
        <strong>En ruta.</strong> Continúe el cierre operativo para confirmar llegada e incidentes.
    </div>
    <a href="{{ route($rutaPrefijo.'.cierre.panel', $ruta) }}" class="btn btn-success btn-block btn-sm mt-2 font-weight-bold">
        <i class="fas fa-tasks mr-1"></i> Continuar cierre operativo
    </a>
@elseif($estado === RutaDistribucionCatalogo::ESTADO_PLANIFICADA)
    @if(! $ruta->transportista_usuarioid)
        <p class="text-muted small mb-0">
            <i class="fas fa-user-clock mr-1"></i>
            Traslado aprobado. Falta asignar chofer y vehículo antes de marcar salida.
        </p>
    @else
        <p class="text-muted small mb-0">
            <i class="fas fa-hourglass-half mr-1"></i>
            Traslado listo. El transportista debe aceptar la solicitud.
        </p>
    @endif
@elseif($estado === RutaDistribucionCatalogo::ESTADO_CANCELADA)
    <div class="alert alert-secondary small mb-0">
        <i class="fas fa-ban mr-1"></i> Traslado cancelado.
    </div>
@else
    <p class="text-muted small mb-0">
        <i class="fas fa-info-circle mr-1"></i>
        Estado actual: <strong>{{ RutaDistribucionCatalogo::etiquetaEstado($estado) }}</strong>.
    </p>
@endif
