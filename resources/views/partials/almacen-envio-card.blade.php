@php
    $usado = $almacen->almacenamientos->whereNull('fechasalida')->sum('cantidad');
    $disponible = $almacen->capacidad - $usado;
    $porcentaje = $almacen->capacidad > 0 ? ($usado / $almacen->capacidad) * 100 : 0;
    $fillClass = $porcentaje < 50 ? 'low' : ($porcentaje < 80 ? 'medium' : 'high');
    $isSelected = isset($isSelected) ? (bool) $isSelected : false;
    $colClass = $colClass ?? 'col-md-6 mb-2';
@endphp
<div class="{{ $colClass }}">
    <div class="almacen-card {{ $isSelected ? 'selected' : '' }}"
         data-id="{{ $almacen->almacenid }}"
         data-disponible="{{ $disponible }}"
         data-nombre="{{ $almacen->nombre }}"
         data-um-almacen="{{ $almacen->unidadMedida->abreviatura ?? 'kg' }}"
         data-tipo="{{ strtolower($almacen->tipoAlmacen->nombre ?? 'general') }}"
         data-tags="{{ strtolower($almacen->nombre . ' ' . ($almacen->tipoAlmacen->nombre ?? '') . ' ' . ($almacen->ubicacion ?? '')) }}">
        <div class="d-flex align-items-start">
            <div class="almacen-icon mr-2 text-center">
                @if(str_contains(strtolower($almacen->tipoAlmacen->nombre ?? ''), 'silo'))
                    <i class="fas fa-database"></i>
                @elseif(str_contains(strtolower($almacen->tipoAlmacen->nombre ?? ''), 'bodega'))
                    <i class="fas fa-warehouse"></i>
                @elseif(str_contains(strtolower($almacen->tipoAlmacen->nombre ?? ''), 'fría') || str_contains(strtolower($almacen->tipoAlmacen->nombre ?? ''), 'frio'))
                    <i class="fas fa-snowflake"></i>
                @else
                    <i class="fas fa-box"></i>
                @endif
            </div>
            <div class="flex-grow-1">
                <div class="almacen-nombre">{{ $almacen->nombre }}</div>
                <div class="almacen-tipo">
                    {{ $almacen->tipoAlmacen->nombre ?? 'General' }}
                    @if($almacen->ubicacion)
                        • {{ $almacen->ubicacion }}
                    @endif
                </div>
                <div class="small mt-1">
                    <span class="text-success font-weight-bold">{{ number_format($disponible, 0) }}</span>
                    <span class="text-muted">/ {{ number_format($almacen->capacidad, 0) }} {{ $almacen->unidadMedida->abreviatura ?? 'kg' }}</span>
                </div>
                <div class="capacidad-bar">
                    <div class="fill {{ $fillClass }}" style="width: {{ min($porcentaje, 100) }}%"></div>
                </div>
            </div>
            <div class="ml-2">
                <i class="fas fa-check-circle text-success fa-lg" style="{{ $isSelected ? '' : 'display: none;' }}"></i>
            </div>
        </div>
    </div>
</div>
