@if(count($paradasMapa ?? []) >= 1)
<button type="button"
        class="btn btn-sm {{ $clase ?? 'btn-outline-primary' }} {{ $bloque ?? false ? 'btn-block mt-2' : '' }}"
        data-toggle="modal"
        data-target="#modalMapaRecorrido"
        title="Ver ruta trazada de origen a destino">
    <i class="fas fa-map-marked-alt mr-1"></i> {{ $etiqueta ?? 'Ver recorrido' }}
</button>
@endif