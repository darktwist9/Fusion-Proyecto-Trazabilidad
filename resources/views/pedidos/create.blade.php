@extends('layouts.app')

@section('title', 'Nuevo Pedido')
@section('page_title', 'Crear Pedido')

@push('styles')
@include('logistica.partials.mapa-ruta-styles')
<style>
#mapaPedidoEntrega { height: 420px; min-height: 420px; border-radius: 10px; border: 1px solid #dee2e6; }
.pedido-mapa-hint { font-size: .875rem; color: #64748b; }
.custom-marker { background: transparent; border: none; }
</style>
@endpush

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Registro de pedido</h3>
        </div>
        <div class="card-body">
            @if($productosDisponibles->isEmpty())
                <div class="alert alert-warning">
                    No hay productos agrícolas disponibles todavía.
                    Registre insumos de <strong>Material de Siembra</strong> en Inventario o producción/cosecha en Producción agrícola.
                </div>
            @else
                <div class="alert alert-info py-2">
                    <small>
                        Puede pedir insumos de cualquier productor o almacén agrícola del sistema.
                        Al confirmar el pedido se vincula al cultivo de producción correspondiente.
                    </small>
                </div>
            @endif

            <form action="{{ route('pedidos.store') }}" method="POST" id="form-pedido">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Número de solicitud</label>
                        <input type="text" class="form-control bg-light" value="{{ $numeroSolicitud }}" readonly>
                        <small class="text-muted">Se genera automáticamente al guardar.</small>
                    </div>
                    <div class="form-group col-md-8">
                        <label>Fecha de entrega deseada</label>
                        <input type="date" name="fechaEntregaDeseada" class="form-control" value="{{ old('fechaEntregaDeseada') }}">
                    </div>
                </div>

                <div class="card card-outline card-secondary mb-3">
                    <div class="card-header py-2">
                        <h5 class="card-title mb-0"><i class="fas fa-map-marked-alt mr-1"></i> Ruta de entrega</h5>
                    </div>
                    <div class="card-body">
                        <p class="pedido-mapa-hint mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Haga clic en el mapa: primero el <strong class="text-success">origen</strong> (recogida), luego el <strong class="text-danger">destino</strong> (entrega).
                            La línea azul sigue las calles automáticamente.
                        </p>

                        <div class="form-row mb-3">
                            <div class="form-group col-md-6">
                                <label class="text-success mb-1"><i class="fas fa-map-marker-alt"></i> Origen (recogida)</label>
                                <input type="text" id="txtNombreOrigen" class="form-control bg-light" readonly
                                       placeholder="Marque el origen en el mapa..."
                                       value="{{ old('origen_direccion') }}">
                                <small id="txtOrigenCoords" class="form-text text-muted"></small>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="text-danger mb-1"><i class="fas fa-map-marker-alt"></i> Destino (entrega)</label>
                                <input type="text" id="txtNombreDestino" class="form-control bg-light" readonly
                                       placeholder="Marque el destino en el mapa..."
                                       value="{{ old('direccion_texto') }}">
                                <small id="txtDestinoCoords" class="form-text text-muted"></small>
                            </div>
                        </div>

                        <div id="mapaPedidoEntrega" class="mb-2"></div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <small id="rutaResumen" class="text-muted"></small>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetMapaPedido">
                                <i class="fas fa-undo mr-1"></i> Limpiar mapa
                            </button>
                        </div>

                        <input type="hidden" name="origen_latitud" id="origen_latitud" value="{{ old('origen_latitud') }}">
                        <input type="hidden" name="origen_longitud" id="origen_longitud" value="{{ old('origen_longitud') }}">
                        <input type="hidden" name="origen_direccion" id="origen_direccion" value="{{ old('origen_direccion') }}">
                        <input type="hidden" name="latitud" id="latitud" value="{{ old('latitud') }}">
                        <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud') }}">
                        <input type="hidden" name="direccion_texto" id="direccion_texto" value="{{ old('direccion_texto') }}">
                        @error('latitud')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        @error('origen_latitud')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-group">
                    <label>Producto solicitado</label>
                    <div class="form-row">
                        <div class="col-md-5">
                            <select name="detalles[0][producto_ref]" class="form-control" required {{ $productosDisponibles->isEmpty() ? 'disabled' : '' }}>
                                <option value="">— Seleccione producto —</option>
                                @foreach($productosDisponibles as $producto)
                                    <option value="{{ $producto['value'] }}" @selected(old('detalles.0.producto_ref') === $producto['value'])>
                                        {{ $producto['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('detalles.0.producto_ref')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <input type="number" step="0.01" min="0.01" name="detalles[0][cantidad]" class="form-control" placeholder="Cantidad (kg)" value="{{ old('detalles.0.cantidad') }}" required {{ $productosDisponibles->isEmpty() ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="detalles[0][observaciones]" class="form-control" placeholder="Observaciones del ítem" value="{{ old('detalles.0.observaciones') }}" {{ $productosDisponibles->isEmpty() ? 'disabled' : '' }}>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Observaciones generales</label>
                    <textarea name="observaciones" class="form-control" rows="2">{{ old('observaciones') }}</textarea>
                </div>

                @can('pedidos.create')
                    <button type="submit" class="btn btn-primary" {{ $productosDisponibles->isEmpty() ? 'disabled' : '' }}>Guardar pedido</button>
                @endcan
                <a href="{{ route('pedidos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('logistica.partials.mapa-ruta-libs')
<script>
(function () {
    const hub = { lat: {{ $hubLat }}, lng: {{ $hubLng }} };
    const state = {
        map: null,
        capas: null,
        routeLayer: null,
        markers: { origin: null, destination: null },
        step: 0,
    };

    function iconMarker(color) {
        return L.divIcon({
            html: '<i class="fas fa-map-marker-alt" style="color:' + color + ';font-size:32px;"></i>',
            className: 'custom-marker',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
        });
    }

    function setHidden(prefix, lat, lng, address) {
        document.getElementById(prefix + '_latitud').value = lat.toFixed(7);
        document.getElementById(prefix === 'origen' ? 'origen_longitud' : 'longitud').value = lng.toFixed(7);
        if (prefix === 'origen') {
            document.getElementById('origen_direccion').value = address;
        } else {
            document.getElementById('direccion_texto').value = address;
        }
    }

    async function reverseGeocode(lat, lng) {
        try {
            const res = await fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng, {
                headers: { 'Accept-Language': 'es' },
            });
            const data = await res.json();
            return data.display_name || (lat.toFixed(6) + ', ' + lng.toFixed(6));
        } catch (e) {
            return lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
    }

    async function drawRoute() {
        if (!state.markers.origin || !state.markers.destination || !window.RutaPorCalles) return;

        const start = state.markers.origin.getLatLng();
        const end = state.markers.destination.getLatLng();
        const waypoints = [
            { lat: start.lat, lng: start.lng, orden: 1, label: 'Origen' },
            { lat: end.lat, lng: end.lng, orden: 2, label: 'Destino' },
        ];

        if (state.routeLayer) {
            state.capas.removeLayer(state.routeLayer);
            state.routeLayer = null;
        }

        const routeResult = await RutaPorCalles.fetchRoute(waypoints);
        if (routeResult?.geojson) {
            state.routeLayer = L.geoJSON(routeResult.geojson, {
                style: {
                    color: routeResult.straight ? '#e67e22' : '#2563eb',
                    weight: 5,
                    opacity: 0.85,
                    dashArray: routeResult.straight ? '8,8' : null,
                },
            }).addTo(state.capas);

            try {
                state.map.fitBounds(state.routeLayer.getBounds(), { padding: [40, 40] });
            } catch (e) { /* ignore */ }

            const km = routeResult.distance_m ? (routeResult.distance_m / 1000).toFixed(1) : null;
            const min = routeResult.duration_s ? Math.round(routeResult.duration_s / 60) : null;
            let resumen = routeResult.straight
                ? 'Ruta estimada (línea recta — servicio de calles no disponible)'
                : 'Ruta por calles trazada';
            if (km && min) resumen += ' · ~' + km + ' km · ~' + min + ' min';
            document.getElementById('rutaResumen').textContent = resumen;
        }
    }

    async function onMapClick(e) {
        const { lat, lng } = e.latlng;

        if (!state.markers.origin) {
            state.markers.origin = L.marker([lat, lng], { icon: iconMarker('#28a745') }).addTo(state.capas);
            document.getElementById('txtOrigenCoords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
            const address = await reverseGeocode(lat, lng);
            document.getElementById('txtNombreOrigen').value = address;
            setHidden('origen', lat, lng, address);
            state.step = 1;
            return;
        }

        if (!state.markers.destination) {
            state.markers.destination = L.marker([lat, lng], { icon: iconMarker('#dc3545') }).addTo(state.capas);
            document.getElementById('txtDestinoCoords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
            const address = await reverseGeocode(lat, lng);
            document.getElementById('txtNombreDestino').value = address;
            document.getElementById('latitud').value = lat.toFixed(7);
            document.getElementById('longitud').value = lng.toFixed(7);
            document.getElementById('direccion_texto').value = address;
            await drawRoute();
            state.step = 2;
        }
    }

    function resetMapa() {
        if (state.capas) state.capas.clearLayers();
        state.markers = { origin: null, destination: null };
        state.routeLayer = null;
        state.step = 0;
        ['origen_latitud', 'origen_longitud', 'origen_direccion', 'latitud', 'longitud', 'direccion_texto'].forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('txtNombreOrigen').value = '';
        document.getElementById('txtNombreDestino').value = '';
        document.getElementById('txtOrigenCoords').textContent = '';
        document.getElementById('txtDestinoCoords').textContent = '';
        document.getElementById('rutaResumen').textContent = '';
    }

    async function restoreFromOld() {
        const oLat = parseFloat(document.getElementById('origen_latitud').value);
        const oLng = parseFloat(document.getElementById('origen_longitud').value);
        const dLat = parseFloat(document.getElementById('latitud').value);
        const dLng = parseFloat(document.getElementById('longitud').value);

        if (!isNaN(oLat) && !isNaN(oLng)) {
            state.markers.origin = L.marker([oLat, oLng], { icon: iconMarker('#28a745') }).addTo(state.capas);
            document.getElementById('txtOrigenCoords').textContent = oLat.toFixed(6) + ', ' + oLng.toFixed(6);
        }
        if (!isNaN(dLat) && !isNaN(dLng)) {
            state.markers.destination = L.marker([dLat, dLng], { icon: iconMarker('#dc3545') }).addTo(state.capas);
            document.getElementById('txtDestinoCoords').textContent = dLat.toFixed(6) + ', ' + dLng.toFixed(6);
            await drawRoute();
        }
    }

    function initMapa() {
        const el = document.getElementById('mapaPedidoEntrega');
        if (!el || !window.L) return;

        state.map = L.map(el, { scrollWheelZoom: true }).setView([hub.lat, hub.lng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap',
        }).addTo(state.map);
        state.capas = L.layerGroup().addTo(state.map);
        state.map.on('click', onMapClick);

        document.getElementById('btnResetMapaPedido').addEventListener('click', resetMapa);
        document.getElementById('form-pedido').addEventListener('submit', function (e) {
            if (!document.getElementById('latitud').value || !document.getElementById('origen_latitud').value) {
                e.preventDefault();
                alert('Marque origen y destino en el mapa antes de guardar el pedido.');
            }
        });

        setTimeout(function () {
            state.map.invalidateSize();
            restoreFromOld();
        }, 200);
    }

    if (window.L && window.RutaPorCalles) {
        initMapa();
    } else {
        var t = setInterval(function () {
            if (window.L && window.RutaPorCalles) {
                clearInterval(t);
                initMapa();
            }
        }, 100);
    }
})();
</script>
@endpush
