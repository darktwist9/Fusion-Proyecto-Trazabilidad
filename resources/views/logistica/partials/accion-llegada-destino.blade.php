@php
    $esMiAsignacion = (int) auth()->id() === (int) ($asignacion->transportista_usuarioid ?? 0);
    $puedeConfirmar = auth()->user()?->can('asignaciones.update') || $esMiAsignacion;
    $enCamino = in_array($asignacion->estado, ['en_transporte_planta', 'en_ruta', 'en_transito'], true);
    $compacto = !empty($compacto);
@endphp

@if($enCamino && $puedeConfirmar && ! $asignacion->fecha_recepcion_planta)
    <a href="{{ route('logistica.asignaciones.cierre.panel', $asignacion) }}"
       class="btn btn-sm {{ $compacto ? 'btn-outline-success' : 'btn-success' }}"
       title="Cierre operativo — llegada, incidentes y firmas">
        <i class="fas fa-clipboard-list{{ $compacto ? '' : ' mr-1' }}"></i>{{ $compacto ? '' : 'Cierre de entrega' }}
    </a>
@endif
