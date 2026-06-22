@php
    $trayectosPermitidos = $trayectosPermitidos ?? ['planta', 'mayorista', 'punto-venta'];
    $mostrarSelectorTrayecto = $mostrarSelectorTrayecto ?? count($trayectosPermitidos) > 1;
@endphp
@if($mostrarSelectorTrayecto)
<div class="env-trayecto-inline mb-3 pb-3 border-bottom">
    <label class="small font-weight-bold text-muted d-block mb-2">Tipo de trayecto</label>
    <div class="env-destino-pick env-destino-pick--inline js-trayecto-pick mb-0">
        @if(in_array('planta', $trayectosPermitidos, true))
        <button type="button" class="env-destino-opcion env-destino-opcion--planta is-active" data-destino="planta">
            <span class="env-destino-opcion__icon env-trayecto-icon env-trayecto-icon--planta"><i class="fas fa-seedling"></i></span>
            <span class="env-destino-opcion__title">Agrícola → Planta</span>
        </button>
        @endif
        @if(in_array('mayorista', $trayectosPermitidos, true))
        <button type="button" class="env-destino-opcion env-destino-opcion--mayorista" data-destino="mayorista">
            <span class="env-destino-opcion__icon env-trayecto-icon env-trayecto-icon--mayorista"><i class="fas fa-industry"></i></span>
            <span class="env-destino-opcion__title">Planta → Mayorista</span>
        </button>
        @endif
        @if(in_array('punto-venta', $trayectosPermitidos, true))
        <button type="button" class="env-destino-opcion env-destino-opcion--pdv" data-destino="punto-venta">
            <span class="env-destino-opcion__icon env-trayecto-icon env-trayecto-icon--pdv"><i class="fas fa-store"></i></span>
            <span class="env-destino-opcion__title">Mayorista → Punto de Venta</span>
        </button>
        @endif
    </div>
    <p class="small text-muted mb-0 mt-2 env-trayecto-desc" id="env-trayecto-desc">
        Recogida de cosecha en almacenes agrícolas y entrega en planta procesadora.
    </p>
</div>
@endif
