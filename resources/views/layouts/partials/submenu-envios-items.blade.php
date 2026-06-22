@if(auth()->user()?->can('asignaciones.view') || auth()->user()?->can('pedidos.view'))
<li class="ag-sub-li">
    <a href="{{ route('logistica.asignaciones.listado') }}"
       class="ag-sub-a {{ ($envListadoActivo ?? false) ? 'active' : '' }}">
        {{ auth()->user()?->hasRole('transportista') ? 'Mis envíos' : 'Listado de envíos' }}
    </a>
</li>
@endif
@if(\App\Support\RutaTiempoRealAcceso::puedeAccederModulo(auth()->user()))
<li class="ag-sub-li">
    <a href="{{ route('logistica.rutas-tiempo-real.index') }}"
       class="ag-sub-a {{ request()->routeIs('logistica.rutas-tiempo-real.*') ? 'active' : '' }}">
        Ruta en tiempo real
    </a>
</li>
@endif
@if(\App\Support\DocumentoEntregaAcceso::puedeAccederModulo(auth()->user()))
<li class="ag-sub-li">
    <a href="{{ route('logistica.documentos.index') }}"
       class="ag-sub-a {{ request()->routeIs('logistica.documentos.*') ? 'active' : '' }}">
        Documentos de entrega
    </a>
</li>
@endif
