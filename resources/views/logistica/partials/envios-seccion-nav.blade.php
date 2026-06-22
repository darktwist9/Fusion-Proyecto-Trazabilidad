@php
    $mostrar = ! auth()->user()?->hasRole('transportista')
        && (
            \App\Support\RutaTiempoRealAcceso::puedeAccederModulo(auth()->user())
            || \App\Support\DocumentoEntregaAcceso::puedeAccederModulo(auth()->user())
            || auth()->user()?->can('asignaciones.view')
            || auth()->user()?->can('pedidos.view')
        );
    $tabListado = ($envListadoActivo ?? false) || request()->routeIs('logistica.asignaciones.*', 'logistica.envios.*');
    $tabTiempoReal = request()->routeIs('logistica.rutas-tiempo-real.*');
    $tabDocumentos = request()->routeIs('logistica.documentos.*');
@endphp
@if($mostrar)
@once
@push('styles')
<style>
.env-seccion-nav {
    display: flex; flex-wrap: wrap; gap: .5rem;
    margin-bottom: 1.25rem; padding: .35rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15,23,42,.04);
}
.env-seccion-nav__link {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .5rem .95rem; border-radius: 9px;
    font-size: .84rem; font-weight: 600; color: #475569;
    text-decoration: none; transition: background .12s ease, color .12s ease;
}
.env-seccion-nav__link:hover { background: #f1f5f9; color: #1e293b; text-decoration: none; }
.env-seccion-nav__link.active {
    background: linear-gradient(135deg, #1e4620, #2c5530);
    color: #fff; box-shadow: 0 2px 8px rgba(44,85,48,.25);
}
.env-seccion-nav__link.active:hover { color: #fff; }
</style>
@endpush
@endonce
<nav class="env-seccion-nav" aria-label="Secciones de envíos">
    @if(auth()->user()?->can('asignaciones.view') || auth()->user()?->can('pedidos.view'))
    <a href="{{ route('logistica.asignaciones.listado') }}"
       class="env-seccion-nav__link {{ $tabListado && ! $tabTiempoReal && ! $tabDocumentos ? 'active' : '' }}">
        <i class="fas fa-list"></i> Listado
    </a>
    @endif
    @if(\App\Support\RutaTiempoRealAcceso::puedeAccederModulo(auth()->user()))
    <a href="{{ route('logistica.rutas-tiempo-real.index') }}"
       class="env-seccion-nav__link {{ $tabTiempoReal ? 'active' : '' }}">
        <i class="fas fa-satellite-dish"></i> Ruta en tiempo real
    </a>
    @endif
    @if(\App\Support\DocumentoEntregaAcceso::puedeAccederModulo(auth()->user()))
    <a href="{{ route('logistica.documentos.index') }}"
       class="env-seccion-nav__link {{ $tabDocumentos ? 'active' : '' }}">
        <i class="fas fa-file-contract"></i> Documentos de entrega
    </a>
    @endif
</nav>
@endif
