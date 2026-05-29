@extends('layouts.app')

@section('content')
    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Registrar Almacenamiento de Producción</h3>
        </div>

        <form action="{{ route('producciones_almacenamiento.store') }}" method="POST">
            @csrf

            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <strong>No se pudo guardar:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card card-outline card-secondary collapsed-card mb-3">
                    <div class="card-header py-2">
                        <h3 class="card-title small mb-0">
                            <i class="fas fa-question-circle text-info mr-1"></i> Guía rápida de almacenamiento
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body py-2 small" style="display:none;">
                        <p class="mb-1"><strong>1)</strong> Elija producción y almacén. Son los dos campos base de trazabilidad.</p>
                        <p class="mb-1"><strong>2)</strong> Registre solo cantidad, temperatura y humedad actual (el resto es opcional).</p>
                        <p class="mb-0"><strong>3)</strong> Temperatura/humedad se sugieren automáticamente según lote o almacén seleccionado.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Producción <span class="text-danger">*</span></label>
                    <select name="produccionid" id="produccionid" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($producciones as $p)
                            <option value="{{ $p->produccionid }}"
                                data-unidadid="{{ $p->unidadmedidaid }}"
                                data-unidadnombre="{{ $p->unidadMedida?->nombre }}"
                                @selected((string) old('produccionid') === (string) $p->produccionid)>
                                #{{ $p->produccionid }}
                                @if($p->lote)
                                    - Lote: {{ $p->lote->nombre }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Documento origen del producto cosechado/transformado.</small>
                </div>

                <div class="form-group">
                    <label>Almacén <span class="text-danger">*</span></label>
                    <select name="almacenid" id="almacenid" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($almacenes as $a)
                            <option value="{{ $a->almacenid }}" @selected((string) old('almacenid') === (string) $a->almacenid)>{{ $a->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Bodega donde quedará físicamente el producto.</small>
                </div>

                <div class="form-group">
                    <label>Cantidad <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" value="{{ old('cantidad') }}" required>
                    <small class="text-muted">Volumen ingresado al almacén para control de stock.</small>
                </div>

                <div class="form-group">
                    <label>Unidad de medida</label>
                    <select name="unidadmedidaid" id="unidadmedidaid" class="form-control">
                        <option value="">Seleccione...</option>
                        @foreach($unidades as $u)
                            <option value="{{ $u->unidadmedidaid }}" @selected((string) old('unidadmedidaid') === (string) $u->unidadmedidaid)>{{ $u->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Se autocompleta desde la producción; puede ajustarla si corresponde.</small>
                </div>

                <hr>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Temperatura actual (°C)</label>
                        <input type="number" step="0.01" name="temperatura" id="temperatura" class="form-control" value="{{ old('temperatura') }}">
                        <small class="text-muted">Valor real al momento del ingreso.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Humedad actual (%)</label>
                        <input type="number" step="0.01" name="humedad" id="humedad" class="form-control" value="{{ old('humedad') }}">
                        <small class="text-muted">Valor real del ambiente en la bodega.</small>
                    </div>
                </div>
                <small id="sugerencia_ambiental" class="text-info d-block mb-2"></small>

                <div class="card card-outline card-light collapsed-card mb-3">
                    <div class="card-header py-2">
                        <h3 class="card-title small mb-0">Opcionales avanzados (rangos y salida)</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body py-2 small" style="display:none;">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Temperatura mínima recomendada (°C)</label>
                                <input type="number" step="0.01" name="temperatura_min" id="temperatura_min" class="form-control" value="{{ old('temperatura_min') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Temperatura máxima recomendada (°C)</label>
                                <input type="number" step="0.01" name="temperatura_max" id="temperatura_max" class="form-control" value="{{ old('temperatura_max') }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Humedad mínima recomendada (%)</label>
                                <input type="number" step="0.01" name="humedad_min" id="humedad_min" class="form-control" value="{{ old('humedad_min') }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Humedad máxima recomendada (%)</label>
                                <input type="number" step="0.01" name="humedad_max" id="humedad_max" class="form-control" value="{{ old('humedad_max') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <label>Fecha de entrada</label>
                    <input type="datetime-local" name="fechaentrada" class="form-control"
                        value="{{ old('fechaentrada') }}" id="fechaentrada">
                    <small class="text-muted">Se asigna automáticamente a la fecha/hora actual.</small>
                </div>

                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" maxlength="250">{{ old('observaciones') }}</textarea>
                    <small class="text-muted">Incidencias, lote mojado, condición del empaque, etc. (opcional).</small>
                </div>

            </div>

            <div class="card-footer text-right">
                <a href="{{ route('producciones_almacenamiento.index') }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-primary">Guardar</button>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const sugerenciasProduccion = @json($sugerenciasPorProduccion ?? []);
            const sugerenciasAlmacen = @json($sugerenciasPorAlmacen ?? []);

            // Automatización: usar fecha/hora actual cuando no venga pre-cargada.
            const fechaEntrada = document.getElementById('fechaentrada');
            if (fechaEntrada && !fechaEntrada.value) {
                const now = new Date();
                const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
                fechaEntrada.value = local;
            }

            // Automatización: al elegir producción, sugerir unidad de medida correspondiente.
            const produccionSelect = document.getElementById('produccionid');
            const almacenSelect = document.getElementById('almacenid');
            const unidadSelect = document.getElementById('unidadmedidaid');
            const temperatura = document.getElementById('temperatura');
            const humedad = document.getElementById('humedad');
            const temperaturaMin = document.getElementById('temperatura_min');
            const temperaturaMax = document.getElementById('temperatura_max');
            const humedadMin = document.getElementById('humedad_min');
            const humedadMax = document.getElementById('humedad_max');
            const sugerenciaAmbiental = document.getElementById('sugerencia_ambiental');

            function aplicarSugerenciaAmbiental() {
                const pid = produccionSelect ? produccionSelect.value : '';
                const aid = almacenSelect ? almacenSelect.value : '';
                const sugProd = pid ? sugerenciasProduccion[pid] : null;
                const sugAlm = aid ? sugerenciasAlmacen[aid] : null;
                const sug = sugProd || sugAlm;
                if (!sug) {
                    if (sugerenciaAmbiental) sugerenciaAmbiental.textContent = '';
                    return;
                }

                if (temperatura) temperatura.value = sug.temperatura ?? '';
                if (humedad) humedad.value = sug.humedad ?? '';
                if (temperaturaMin) temperaturaMin.value = sug.temperatura_min ?? '';
                if (temperaturaMax) temperaturaMax.value = sug.temperatura_max ?? '';
                if (humedadMin) humedadMin.value = sug.humedad_min ?? '';
                if (humedadMax) humedadMax.value = sug.humedad_max ?? '';

                if (sugerenciaAmbiental) {
                    const fuente = sugProd ? 'lote/producción' : 'almacén';
                    sugerenciaAmbiental.textContent = `Sugerencia automática aplicada por ${fuente}: ${sug.origen}. Puedes ajustar los valores.`;
                }
            }

            if (produccionSelect && unidadSelect) {
                produccionSelect.addEventListener('change', function () {
                    const opt = produccionSelect.options[produccionSelect.selectedIndex];
                    const unidadId = opt ? opt.getAttribute('data-unidadid') : '';
                    if (unidadId) {
                        unidadSelect.value = unidadId;
                    }
                    aplicarSugerenciaAmbiental();
                });
            }
            if (almacenSelect) {
                almacenSelect.addEventListener('change', aplicarSugerenciaAmbiental);
            }

            if (!('{{ old('temperatura') }}' || '{{ old('humedad') }}')) {
                aplicarSugerenciaAmbiental();
            }

            // SMART UNIT CONVERSION
            function checkSmartConversion() {
                const cantidadInput = $('input[name="cantidad"]');
                const unidadSelect = $('select[name="unidadmedidaid"]');
                const cantidad = parseFloat(cantidadInput.val()) || 0;
                const unidadOption = unidadSelect.find('option:selected');
                const unidadNombre = unidadOption.text().toLowerCase();

                $('#smartConversionAlert').remove();

                if (unidadNombre.includes('kilo') || unidadNombre.includes('kg')) {
                    if (cantidad >= 1000) {
                        const toneladas = cantidad / 1000;
                        mostrarSugerenciaConversion(cantidadInput, 'Ton', toneladas, 'tonelada');
                    }
                }
                else if (unidadNombre.includes('gramo') || unidadNombre.includes(' gr')) {
                    if (cantidad >= 1000) {
                        const kilos = cantidad / 1000;
                        mostrarSugerenciaConversion(cantidadInput, 'Kg', kilos, 'kilo');
                    }
                }
            }

            function mostrarSugerenciaConversion(inputElement, nuevaUnidadTexto, nuevoValor, keywordNuevaUnidad) {
                const alertHtml = `
                    <div id="smartConversionAlert" class="alert alert-info p-2 mt-2 shadow-sm d-flex justify-content-between align-items-center" style="border-radius: 8px;">
                        <div>
                            <i class="fas fa-lightbulb text-info mr-2"></i>
                            <strong>Sugerencia:</strong> ¿Convertir a <strong>${nuevoValor} ${nuevaUnidadTexto}</strong>?
                        </div>
                        <button type="button" class="btn btn-sm btn-light border font-weight-bold" id="btnAplicarConversion">
                            Sí, cambiar
                        </button>
                    </div>
                `;
                if ($('#smartConversionAlert').length === 0) {
                    inputElement.closest('.form-group').append(alertHtml);
                }
                $('#btnAplicarConversion').on('click', function (e) {
                    e.preventDefault();
                    $('input[name="cantidad"]').val(nuevoValor);
                    $('select[name="unidadmedidaid"] option').each(function () {
                        const text = $(this).text().toLowerCase();
                        if (text.includes(keywordNuevaUnidad)) {
                            $(this).prop('selected', true);
                            return false;
                        }
                    });
                    $('#smartConversionAlert').remove();
                });
            }

            $('input[name="cantidad"], select[name="unidadmedidaid"]').on('change keyup blur', function () {
                checkSmartConversion();
            });
        });
    </script>
@endpush