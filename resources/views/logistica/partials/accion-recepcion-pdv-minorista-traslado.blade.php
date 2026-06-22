@php

    $estado = $estadoRecepcion ?? [];

    $rutaPrefijo = $rutaPrefijo ?? 'punto-venta.rutas';

@endphp



@if(! empty($estado))

<div class="may-rec-panel">

    <div class="d-flex align-items-start gap-2 mb-2">

        <span class="badge badge-{{ $estado['clase'] ?? 'secondary' }} px-2 py-1">{{ $estado['etiqueta'] ?? '—' }}</span>

    </div>

    <p class="small text-muted mb-3">{{ $estado['descripcion'] ?? '' }}</p>



    @if($estado['puede_firmar'] ?? false)

        <a href="{{ $estado['url_cierre'] ?? route($rutaPrefijo.'.cierre.panel', $ruta) }}"

           class="btn btn-warning btn-block font-weight-bold">

            <i class="fas fa-file-signature mr-1"></i> Firmar recepción en tienda

        </a>

    @elseif($estado['puede_ver_documento'] ?? false)

        <a href="{{ $estado['url_documento'] }}" class="btn btn-outline-success btn-block font-weight-bold mb-2">

            <i class="fas fa-file-pdf mr-1"></i> Ver comprobante (POD)

        </a>

        <a href="{{ route($rutaPrefijo.'.cierre.panel', $ruta) }}" class="btn btn-outline-secondary btn-sm btn-block">

            <i class="fas fa-clipboard-check mr-1"></i> Ver resumen de cierre

        </a>

    @elseif(($estado['clave'] ?? '') === 'en_camino' && ($simulacionActiva ?? false) && $ruta)

        <a href="{{ route('logistica.rutas-tiempo-real.show', ['tipo' => 'distribucion', 'id' => $ruta->rutadistribucionid]) }}"

           class="btn btn-outline-primary btn-block btn-sm font-weight-bold">

            <i class="fas fa-map-marked-alt mr-1"></i> Seguir en tiempo real

        </a>

    @elseif($estado['url_cierre'] ?? null)

        <a href="{{ $estado['url_cierre'] }}" class="btn btn-outline-secondary btn-sm btn-block">

            <i class="fas fa-eye mr-1"></i> Ver estado del cierre

        </a>

    @endif

</div>

@endif

