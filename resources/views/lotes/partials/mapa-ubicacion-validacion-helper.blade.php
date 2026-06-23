<script>
    window.AgroFusionLoteUbicacion = window.AgroFusionLoteUbicacion || {};

    AgroFusionLoteUbicacion.vincular = function (config) {
        const urlValidar = config.urlValidar;
        const errorBox = document.getElementById(config.errorBoxId || 'mapaUbicacionError');
        const errorTexto = document.getElementById(config.errorTextoId || 'mapaUbicacionErrorTexto');
        const supInput = document.getElementById(config.superficieInputId || 'superficie');
        const latInput = document.getElementById(config.latitudInputId || 'latitud');
        const lngInput = document.getElementById(config.longitudInputId || 'longitud');
        const onAviso = typeof config.onAviso === 'function' ? config.onAviso : null;

        let estado = { ok: true, mensaje: '', hectareas_maximas: null };
        let timer = null;
        let validando = false;
        let abortActual = null;

        function mostrarErrorUi(msg) {
            if (!errorBox || !errorTexto) {
                return;
            }
            if (!msg) {
                errorBox.classList.add('d-none');
                errorTexto.textContent = '';
                return;
            }
            errorTexto.textContent = msg;
            errorBox.classList.remove('d-none');
        }

        function leerCoordenadas() {
            const lat = parseFloat(latInput?.value || '');
            const lng = parseFloat(lngInput?.value || '');
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return null;
            }
            return { lat: lat, lng: lng };
        }

        function leerSuperficie() {
            const ha = parseFloat(supInput?.value || '0');
            return Number.isFinite(ha) && ha > 0 ? ha : 0;
        }

        async function validar(opciones) {
            const opts = Object.assign({ mostrarModal: false, silencioso: false }, opciones || {});
            let coords = null;

            if (opts.lat !== undefined && opts.lng !== undefined) {
                const lat = parseFloat(opts.lat);
                const lng = parseFloat(opts.lng);
                if (Number.isFinite(lat) && Number.isFinite(lng)) {
                    coords = { lat: lat, lng: lng };
                }
            } else {
                coords = leerCoordenadas();
            }

            if (!coords || !urlValidar) {
                estado = { ok: true, mensaje: '', hectareas_maximas: null };
                mostrarErrorUi('');
                return estado;
            }

            const superficie = opts.superficie !== undefined
                ? parseFloat(opts.superficie)
                : leerSuperficie();
            const ha = Number.isFinite(superficie) && superficie > 0 ? superficie : 0;

            const params = new URLSearchParams({
                latitud: String(coords.lat),
                longitud: String(coords.lng),
                superficie: String(ha),
            });

            if (abortActual) {
                abortActual.abort();
            }
            abortActual = typeof AbortController !== 'undefined' ? new AbortController() : null;
            const signal = abortActual ? abortActual.signal : undefined;

            validando = true;
            try {
                const res = await fetch(urlValidar + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: signal,
                });
                const data = await res.json();
                estado = {
                    ok: !!data.ok,
                    mensaje: data.mensaje || '',
                    hectareas_maximas: data.hectareas_maximas ?? null,
                    codigo: data.codigo || null,
                };

                if (!opts.silencioso) {
                    mostrarErrorUi(estado.ok ? '' : estado.mensaje);
                }

                if (!estado.ok && opts.mostrarModal && onAviso) {
                    onAviso('Ubicación no válida', estado.mensaje);
                }

                if (typeof config.onResultado === 'function') {
                    config.onResultado(estado);
                }

                return estado;
            } catch (e) {
                if (e && e.name === 'AbortError') {
                    return estado;
                }
                estado = { ok: true, mensaje: '', hectareas_maximas: null };
                return estado;
            } finally {
                validando = false;
            }
        }

        function programarValidacion(opciones) {
            const opts = Object.assign({ silencioso: false, espera: 700 }, opciones || {});
            clearTimeout(timer);
            timer = setTimeout(function () {
                validar(opts);
            }, opts.espera);
        }

        return {
            validar: validar,
            programarValidacion: programarValidacion,
            getEstado: function () { return estado; },
            esValida: function () { return estado.ok; },
            estaValidando: function () { return validando; },
            mostrarErrorUi: mostrarErrorUi,
        };
    };
</script>
