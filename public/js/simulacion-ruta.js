/**

 * Simulación de ruta en tiempo real (enfoque A — progreso por reloj del servidor).

 */

(function () {

    const estadoUrlBase = document.querySelector('meta[name="simulacion-estado-base"]')?.content || '';



    function urlEstado(tipo, id) {

        if (estadoUrlBase) {

            return estadoUrlBase.replace('__TIPO__', tipo).replace('__ID__', String(id));

        }

        return `/logistica/simulacion/${tipo}/${id}/estado`;

    }



    function formatearEta(segundos, progreso, completada) {

        if (completada) return 'Envío recibido en planta.';

        if (progreso >= 100 || !segundos || segundos <= 0) return 'Finalizando recepción en planta…';

        const m = Math.ceil(segundos / 60);

        if (m < 60) return `Llegada estimada en ${m} min`;

        const h = Math.floor(m / 60);

        const rm = m % 60;

        return `Llegada estimada en ${h}h ${rm}m`;

    }



    function actualizarPanelVivo(container, data) {

        const pct = container.querySelector('.sim-live-pct');

        const bar = container.querySelector('.sim-live-bar');

        const eta = container.querySelector('.sim-live-eta');

        const badge = container.querySelector('.sim-live-badge');

        const progreso = data.progreso ?? 0;

        const completada = !!data.completada;



        if (pct) {

            pct.textContent = `${progreso}%`;

            pct.className = `h4 mb-0 text-${completada ? 'success' : 'primary'} sim-live-pct`;

        }

        if (bar) {

            bar.style.width = `${progreso}%`;

            bar.className = `progress-bar bg-${completada ? 'success' : 'primary'} sim-live-bar`;

        }

        if (eta) eta.textContent = formatearEta(data.segundos_restantes, progreso, completada);

        if (badge) {

            badge.textContent = completada ? 'Finalizada' : 'En curso';

            badge.className = `badge badge-${completada ? 'success' : 'primary'} sim-live-badge`;

        }

    }



    function manejarCompletada(container, data) {

        if (!data.completada) return;

        actualizarPanelVivo(container, data);

        setTimeout(() => {

            window.location.href = container.dataset.simVolverUrl || window.location.href;

        }, 1500);

    }



    function actualizarProgresoTransportista(el, data) {

        const pct = data.progreso ?? 0;

        const bar = el.querySelector('.sim-progreso-bar');

        const lbl = el.querySelector('.sim-progreso-pct');

        const eta = el.querySelector('.sim-progreso-eta');

        if (bar) bar.style.width = `${pct}%`;

        if (lbl) lbl.textContent = `${pct}%`;

        if (eta) {

            eta.textContent = data.completada

                ? 'Envío completado.'

                : formatearEta(data.segundos_restantes, pct, false);

        }

        if (data.completada) {

            setTimeout(() => window.location.reload(), 1500);

        }

    }



    function crearIconoCamion() {

        return L.divIcon({

            className: 'sim-camion-marker',

            html: '<div class="sim-camion-icono" aria-hidden="true"><i class="fas fa-truck"></i></div>',

            iconSize: [34, 34],

            iconAnchor: [17, 17],

        });

    }



    function haversineMetros(lat1, lng1, lat2, lng2) {

        const r = 6371000;

        const dLat = (lat2 - lat1) * Math.PI / 180;

        const dLng = (lng2 - lng1) * Math.PI / 180;

        const a = Math.sin(dLat / 2) ** 2

            + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLng / 2) ** 2;

        return r * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    }



    function posicionDesdeGeojson(geojson, ratio) {

        const coords = geojson?.features?.[0]?.geometry?.coordinates;

        if (!Array.isArray(coords) || coords.length === 0) return null;



        const progreso = Math.min(1, Math.max(0, Number(ratio) || 0));

        if (progreso >= 1) {

            const ultimo = coords[coords.length - 1];

            return { lat: ultimo[1], lng: ultimo[0] };

        }



        const distancias = [0];

        let total = 0;

        for (let i = 1; i < coords.length; i++) {

            total += haversineMetros(coords[i - 1][1], coords[i - 1][0], coords[i][1], coords[i][0]);

            distancias.push(total);

        }



        if (total <= 0) {

            return { lat: coords[0][1], lng: coords[0][0] };

        }



        const objetivo = total * progreso;

        for (let i = 1; i < coords.length; i++) {

            if (distancias[i] >= objetivo) {

                const segmento = distancias[i] - distancias[i - 1];

                const t = segmento > 0 ? (objetivo - distancias[i - 1]) / segmento : 0;

                const lng = coords[i - 1][0] + (coords[i][0] - coords[i - 1][0]) * t;

                const lat = coords[i - 1][1] + (coords[i][1] - coords[i - 1][1]) * t;

                return { lat, lng };

            }

        }



        const ultimo = coords[coords.length - 1];

        return { lat: ultimo[1], lng: ultimo[0] };

    }



    function resolverPosicion(data) {

        if (data?.posicion?.lat != null && data?.posicion?.lng != null) {

            return data.posicion;

        }

        const ratio = data?.progreso_ratio ?? ((data?.progreso ?? 0) / 100);

        return posicionDesdeGeojson(data?.geojson, ratio);

    }



    function initMapaVivo(container) {

        if (!window.L || !container) return null;



        const mapEl = container.querySelector('[data-sim-mapa]');

        if (!mapEl) return null;



        const tipo = container.dataset.simTipo;

        const id = container.dataset.simId;

        let mapa = null;

        let capasEstaticas = null;

        let capaCamion = null;

        let marcadorCamion = null;

        let rutaDibujada = false;

        let vistaAjustada = false;



        function asegurarMapa(data) {

            if (mapa || !data.paradas?.length) return;

            const p0 = data.paradas[0];

            mapa = L.map(mapEl, { scrollWheelZoom: true }).setView([p0.lat, p0.lng], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {

                maxZoom: 19,

                attribution: '© OpenStreetMap',

            }).addTo(mapa);

            capasEstaticas = L.layerGroup().addTo(mapa);

            capaCamion = L.layerGroup().addTo(mapa);

        }



        function dibujarRutaEstatica(data) {

            if (!data.geojson || !data.paradas?.length || rutaDibujada) return;

            asegurarMapa(data);

            if (!capasEstaticas) return;



            data.paradas.forEach((p, i) => {

                const isFirst = i === 0;

                const isLast = i === data.paradas.length - 1;

                const color = isFirst ? '#28a745' : (isLast ? '#dc3545' : '#17a2b8');

                L.marker([p.lat, p.lng], {

                    icon: L.divIcon({

                        className: 'ruta-parada-marker',

                        html: `<div style="background:${color};color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;border:2px solid #fff;">${p.orden ?? (i + 1)}</div>`,

                        iconSize: [24, 24],

                        iconAnchor: [12, 12],

                    }),

                }).bindPopup(p.label || `Parada ${i + 1}`).addTo(capasEstaticas);

            });



            L.geoJSON(data.geojson, {

                style: { color: '#2563eb', weight: 5, opacity: 0.85 },

            }).addTo(capasEstaticas);



            rutaDibujada = true;



            if (!vistaAjustada && mapa) {

                const bounds = capasEstaticas.getBounds();

                const posicion = resolverPosicion(data);

                if (posicion) bounds.extend([posicion.lat, posicion.lng]);

                if (bounds.isValid()) {

                    mapa.fitBounds(bounds.pad(0.15));

                    vistaAjustada = true;

                }

            }

        }



        function actualizarCamion(data) {

            const posicion = resolverPosicion(data);

            if (!posicion) return;

            asegurarMapa(data);

            if (!capaCamion) return;



            const latLng = [posicion.lat, posicion.lng];

            if (!marcadorCamion) {

                marcadorCamion = L.marker(latLng, {

                    icon: crearIconoCamion(),

                    zIndexOffset: 2000,

                    riseOnHover: true,

                    riseOffset: 2500,

                }).addTo(capaCamion).bindPopup('Camión en ruta');

            } else {

                marcadorCamion.setLatLng(latLng);

                if (!capaCamion.hasLayer(marcadorCamion)) {

                    marcadorCamion.addTo(capaCamion);

                }

            }

        }



        function dibujar(data) {

            if (!data) return;

            dibujarRutaEstatica(data);

            actualizarCamion(data);

            if (mapa) requestAnimationFrame(() => mapa.invalidateSize());

        }



        function leerEstadoInicial() {

            const scriptId = container.dataset.simEstadoId;

            if (!scriptId) return null;

            const el = document.getElementById(scriptId);

            if (!el?.textContent) return null;

            try {

                return JSON.parse(el.textContent);

            } catch (e) {

                console.warn('Estado inicial de simulación inválido', e);

                return null;

            }

        }



        function aplicarEstado(data) {

            actualizarPanelVivo(container, data);

            dibujar(data);

            const cierreManual = container.querySelector('.sim-cierre-manual');

            if (cierreManual) {

                cierreManual.style.display = data.completada ? 'none' : '';

            }

            if (data.completada) {

                manejarCompletada(container, data);

            }

        }



        function poll() {

            fetch(urlEstado(tipo, id), {

                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },

            })

                .then((r) => {

                    if (!r.ok) throw new Error(`estado ${r.status}`);

                    return r.json();

                })

                .then(aplicarEstado)

                .catch((e) => console.warn('Error actualizando simulación', e));

        }



        const estadoInicial = leerEstadoInicial();

        if (estadoInicial) {

            aplicarEstado(estadoInicial);

        }



        poll();

        setInterval(poll, 1000);

        return mapa;

    }



    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.sim-progreso-transportista').forEach((el) => {

            const tipo = el.dataset.simTipo;

            const id = el.dataset.simId;

            const poll = () => {

                fetch(urlEstado(tipo, id), {

                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },

                })

                    .then((r) => r.json())

                    .then((data) => actualizarProgresoTransportista(el, data))

                    .catch(() => {});

            };

            poll();

            setInterval(poll, 3000);

        });



        document.querySelectorAll('[data-sim-mapa-vivo]').forEach(initMapaVivo);

    });

})();

