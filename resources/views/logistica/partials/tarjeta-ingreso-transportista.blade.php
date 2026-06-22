@php
    $esMiAsignacion = (int) auth()->id() === (int) ($asignacion->transportista_usuarioid ?? 0);
    $costoBs = $asignacion->costo_bs !== null ? (float) $asignacion->costo_bs : null;
    $completado = \App\Support\TransporteIngresoCatalogo::envioCompletado($asignacion);
@endphp

@if($esMiAsignacion)
<div class="env-ingreso-transportista {{ !empty($conBordeSuperior) ? 'env-ingreso-transportista--separado' : '' }}">
    <div class="env-ingreso-transportista__head">
        <span class="env-ingreso-transportista__icon"><i class="fas fa-coins"></i></span>
        <span class="env-ingreso-transportista__lbl">Tu ingreso por este servicio</span>
    </div>
    @if($costoBs !== null)
        <div class="env-ingreso-transportista__monto">{{ number_format($costoBs, 2, ',', '.') }} Bs</div>
        <p class="env-ingreso-transportista__nota mb-0">
            @if($completado)
                <i class="fas fa-check-circle text-success mr-1"></i>
                Acreditado en
                <a href="{{ route('logistica.transportista.ingresos') }}">Mis ingresos</a>.
            @else
                <i class="fas fa-info-circle mr-1"></i>
                Se acredita cuando el envío sea recibido en planta.
            @endif
        </p>
    @else
        <p class="env-ingreso-transportista__nota mb-0">Logística aún no registró el monto de este servicio.</p>
    @endif
</div>
@endif
