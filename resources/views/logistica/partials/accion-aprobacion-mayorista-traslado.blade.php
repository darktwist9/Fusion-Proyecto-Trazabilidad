@php

    $rutaPrefijo = $rutaPrefijo ?? 'logistica.traslados-planta';

    $puedeAprobar = $puedeAprobarPlanta ?? $puedeGestionarMayorista ?? false;

@endphp



@if($pendienteAprobacion ?? false)

    @if($puedeAprobar)

        <div class="alert alert-info small">

            <i class="fas fa-info-circle mr-1"></i>

            Revise productos, chofer y destino. Como jefe de planta, apruebe o rechace la salida hacia el almacén mayorista.

        </div>

        <form method="POST" action="{{ route($rutaPrefijo.'.aceptar', $ruta) }}" class="mb-3">

            @csrf

            @method('PATCH')

            <button type="submit" class="btn btn-success btn-block font-weight-bold">

                <i class="fas fa-check mr-1"></i> Aprobar traslado

            </button>

        </form>

        <form method="POST" action="{{ route($rutaPrefijo.'.rechazar', $ruta) }}">

            @csrf

            @method('PATCH')

            <div class="form-group">

                <label for="motivo_rechazo" class="small text-muted">Motivo de rechazo (opcional)</label>

                <textarea name="motivo_rechazo" id="motivo_rechazo" rows="2" class="form-control form-control-sm" maxlength="500" placeholder="Ej: Stock insuficiente o datos incorrectos"></textarea>

            </div>

            <button type="submit" class="btn btn-outline-danger btn-block" onclick="return confirm('¿Rechazar este traslado desde planta?')">

                <i class="fas fa-times mr-1"></i> Rechazar traslado

            </button>

        </form>

    @else

        <p class="text-muted small mb-0">

            <i class="fas fa-hourglass-half mr-1"></i>

            Pendiente de aprobación por el jefe de planta.

        </p>

    @endif

@elseif(($ruta->estado ?? '') === \App\Support\RutaDistribucionCatalogo::ESTADO_RECHAZADA)

    <div class="alert alert-danger small mb-0">

        <i class="fas fa-ban mr-1"></i>

        Traslado rechazado por planta.

        @if($ruta->motivo_rechazo_mayorista)

            <br><span class="text-muted">Motivo:</span> {{ $ruta->motivo_rechazo_mayorista }}

        @endif

    </div>

@else

    @include('logistica.partials.accion-empezar-ruta-traslado-planta', ['ruta' => $ruta, 'rutaPrefijo' => $rutaPrefijo])

@endif

