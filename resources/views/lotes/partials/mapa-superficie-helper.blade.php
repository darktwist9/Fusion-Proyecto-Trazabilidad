<script>
    window.AgroFusionLoteMapa = window.AgroFusionLoteMapa || {};

    AgroFusionLoteMapa.calcularRadioMetros = function (hectareas) {
        const ha = parseFloat(hectareas);
        if (!ha || ha <= 0) {
            return 0;
        }
        return Math.sqrt(ha * 10000 / Math.PI);
    };

    AgroFusionLoteMapa.actualizarCirculo = function (map, circleRef, lat, lng, hectareas, opciones) {
        const opts = Object.assign({
            color: '#28a745',
            fillColor: '#28a745',
            fillOpacity: 0.25,
            ajustarVista: true,
        }, opciones || {});

        if (circleRef.current) {
            map.removeLayer(circleRef.current);
            circleRef.current = null;
        }

        const radio = this.calcularRadioMetros(hectareas);
        if (!lat || !lng || radio <= 0) {
            return null;
        }

        circleRef.current = L.circle([lat, lng], {
            color: opts.color,
            fillColor: opts.fillColor,
            fillOpacity: opts.fillOpacity,
            radius: radio,
        }).addTo(map);

        if (opts.ajustarVista) {
            map.fitBounds(circleRef.current.getBounds(), { padding: [24, 24], maxZoom: 15 });
        }

        return circleRef.current;
    };

    AgroFusionLoteMapa.vincularDosisSiembra = function (config) {
        const supInput = document.getElementById(config.superficieInputId || 'superficie');
        const preview = document.getElementById(config.previewId || 'dosisSiembraPreview');
        const texto = document.getElementById(config.textoId || 'dosisSiembraTexto');
        const wrap = document.getElementById('selector_wrap_' + (config.selectorId || 'lote_semilla'));

        if (!supInput || !preview || !texto) {
            return;
        }

        let dosisPorHa = 0;
        let dosisUnidad = 'kg';

        function renderDosis() {
            const ha = parseFloat(supInput.value);
            if (!dosisPorHa || !ha || ha <= 0) {
                preview.classList.add('d-none');
                return;
            }

            const total = Math.round(dosisPorHa * ha * 100) / 100;
            texto.textContent = 'Con ' + ha.toLocaleString('es-BO', { maximumFractionDigits: 2 })
                + ' hectáreas se sugieren aprox. '
                + total.toLocaleString('es-BO', { maximumFractionDigits: 2 })
                + ' ' + dosisUnidad
                + ' de semilla (' + dosisPorHa.toLocaleString('es-BO', { maximumFractionDigits: 3 })
                + ' ' + dosisUnidad + '/ha).';
            preview.classList.remove('d-none');
        }

        function aplicarExtra(extra) {
            dosisPorHa = parseFloat(extra?.dosis_por_ha || 0) || 0;
            dosisUnidad = extra?.dosis_unidad || extra?.unidad || 'kg';
            renderDosis();
        }

        supInput.addEventListener('input', renderDosis);
        supInput.addEventListener('change', renderDosis);

        if (wrap) {
            wrap.addEventListener('selector-catalogo:change', function (e) {
                aplicarExtra(e.detail?.extra || {});
            });
        }

        if (config.initialDosis) {
            aplicarExtra(config.initialDosis);
        } else {
            renderDosis();
        }
    };
</script>
