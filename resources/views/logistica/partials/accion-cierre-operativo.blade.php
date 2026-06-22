@php
    $urlCierre = route('logistica.asignaciones.cierre.panel', $asignacion);
    $recibido = \App\Support\EnvioAsignacionEstadoCatalogo::llegoADestino($asignacion);
    $enCierre = ! $recibido && (
        app(\App\Services\CierreEnvioAgricolaService::class)->tieneCondicionesVehiculo($asignacion)
        || \App\Support\SimulacionRutaCatalogo::simulacionActivaAgricola($asignacion)
        || $asignacion->llegada_confirmada_at
    );
@endphp
@if($enCierre)
<div class="env-accion-cierre {{ ($conBordeSuperior ?? false) ? 'border-top pt-3 mt-3' : '' }}">
    <div class="env-accion-cierre__head">
        <span class="env-accion-cierre__icon"><i class="fas fa-clipboard-check"></i></span>
        <div>
            <strong class="d-block">Cierre operativo</strong>
            <span class="small text-muted">Registre condiciones, llegada, incidentes y firmas del envío.</span>
        </div>
    </div>
    <a href="{{ $urlCierre }}" class="btn btn-success btn-sm font-weight-bold mt-2">
        <i class="fas fa-tasks mr-1"></i> Continuar cierre operativo
    </a>
</div>
@endif
