@php
    $mensajeEspera = $mensajeEspera ?? 'No puede confirmar la llegada hasta estar físicamente en el destino.';
@endphp
@if($resumen['llegada_confirmada'] ?? false)
    {{-- cubierto en la vista padre --}}
@elseif(($resumen['puede_confirmar_llegada'] ?? false) || ($resumen['esperando_confirmacion'] ?? false))
    <p class="small text-muted mb-3">Confirme cuando el vehículo haya llegado al destino.</p>
    <form method="POST" action="{{ $confirmarUrl }}">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-warning font-weight-bold">
            <i class="fas fa-flag-checkered mr-1"></i> Confirmar llegada
        </button>
    </form>
@elseif($resumen['en_ruta'] ?? false)
    <div class="alert alert-warning small mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        {{ $mensajeEspera }}
    </div>
@else
    <p class="text-muted small mb-0">Disponible cuando el envío esté en ruta.</p>
@endif
