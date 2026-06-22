@if(count($paradasMapa ?? []) >= 1)
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#mapaRecorridoEnvio {
    height: 420px; width: 100%; min-height: 420px;
    border-radius: 10px; border: 1px solid #dee2e6;
    background: #e8eef4; z-index: 1;
}
#mapaRecorridoEnvio.leaflet-container { font-family: inherit; }
.leaflet-div-icon.ruta-parada-marker {
    width: auto !important; height: auto !important; margin: 0 !important;
    padding: 0 !important; background: transparent !important; border: none !important;
}
</style>
@endpush

<div class="modal fade" id="modalMapaRecorrido" tabindex="-1" role="dialog" aria-labelledby="modalMapaRecorridoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:14px;">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold" id="modalMapaRecorridoLabel">
                    <i class="fas fa-route text-success mr-2"></i>Recorrido del envío
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-2">
                @if($trayectoPartes ?? null)
                <p class="small mb-2 px-1">@include('logistica.partials.trayecto-colores', ['trayectoPartes' => $trayectoPartes])</p>
                @endif
                <div id="mapaRecorridoEnvio"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@include('logistica.partials.mapa-ruta-libs')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const paradas = @json($paradasMapa);
    const urlTrazado = @json($urlTrazadoRuta ?? null);
    const modal = document.getElementById('modalMapaRecorrido');
    if (!modal || !paradas.length) return;

    let mapa = null;
    let capas = null;

    function redimensionarMapa() {
        if (!mapa) return;
        mapa.invalidateSize({ animate: false });
    }

    function asegurarMapa() {
        const el = document.getElementById('mapaRecorridoEnvio');
        if (!el || !window.L) return null;
        if (mapa) {
            redimensionarMapa();
            return mapa;
        }
        mapa = L.map(el, { scrollWheelZoom: true }).setView([paradas[0].lat, paradas[0].lng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap',
        }).addTo(mapa);
        capas = L.layerGroup().addTo(mapa);
        redimensionarMapa();
        return mapa;
    }

    async function dibujarRecorrido() {
        if (!window.L) return;
        const mapaActivo = asegurarMapa();
        if (!mapaActivo || !capas) return;
        redimensionarMapa();
        capas.clearLayers();

        let routeResult = null;
        if (urlTrazado) {
            try {
                const res = await fetch(urlTrazado);
                const data = await res.json();
                if (data.geo) {
                    routeResult = {
                        geojson: data.geo,
                        straight: data.geo?.features?.[0]?.properties?.provider === 'straight',
                    };
                }
            } catch (e) {
                console.warn(e);
            }
        }

        if (!routeResult && paradas.length >= 2 && window.RutaPorCalles) {
            routeResult = await RutaPorCalles.fetchRoute(paradas);
        }

        redimensionarMapa();
        if (window.RutaPorCalles) {
            RutaPorCalles.drawOnMap(mapaActivo, capas, paradas, routeResult);
        } else {
            paradas.forEach(function (p, i) {
                L.marker([p.lat, p.lng]).addTo(capas).bindPopup(p.label || ('Parada ' + (i + 1)));
            });
        }

        [100, 300, 600].forEach(function (ms) {
            setTimeout(redimensionarMapa, ms);
        });
    }

    function alAbrirModal() {
        setTimeout(dibujarRecorrido, 200);
    }

    if (window.jQuery) {
        window.jQuery(modal).on('shown.bs.modal', alAbrirModal);
    } else {
        modal.addEventListener('shown.bs.modal', alAbrirModal);
    }
});
</script>
@endpush
@endif
