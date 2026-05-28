@extends('layouts.app')

@section('content')
    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Crear Insumo</h3>
        </div>

        <form action="{{ route('insumos.store') }}" method="POST">
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
                            <i class="fas fa-question-circle text-info mr-1"></i> Guía rápida de insumos
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body py-2 small" style="display:none;">
                        <p class="mb-1"><strong>1)</strong> Complete nombre, tipo, unidad y stock actual.</p>
                        <p class="mb-1"><strong>2)</strong> El stock mínimo se calcula solo (20% del stock) si lo deja vacío.</p>
                        <p class="mb-0"><strong>3)</strong> Proveedor, actor, precio y descripción son opcionales.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre del insumo</label>
                    <input type="text" name="nombre" class="form-control" maxlength="100" value="{{ old('nombre') }}" required>
                    <small class="text-muted">Nombre comercial u operativo del insumo.</small>
                </div>

                <div class="form-group">
                    <label>Tipo de insumo</label>
                    <select name="tipoinsumoid" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t->tipoinsumoid }}" @selected((string) old('tipoinsumoid') === (string) $t->tipoinsumoid)>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Clasifica el insumo para reportes y filtros.</small>
                </div>

                <div class="form-group">
                    <label>Unidad de medida</label>
                    <select name="unidadmedidaid" id="unidadmedidaid" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($unidades as $u)
                            <option value="{{ $u->unidadmedidaid }}" @selected((string) old('unidadmedidaid') === (string) $u->unidadmedidaid)>{{ $u->nombre }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Unidad base del stock (kg, litro, quintal, etc.).</small>
                </div>

                <div class="form-group">
                    <label>Stock actual</label>
                    <input type="number" step="0.01" name="stock" id="stock" class="form-control" min="0" value="{{ old('stock') }}" required>
                    <small class="text-muted">Cantidad disponible al momento de crear el insumo.</small>
                </div>

                <div class="form-group">
                    <label>Stock mínimo (automático)</label>
                    <input type="number" step="0.01" name="stockminimo" id="stockminimo" class="form-control" min="0" value="{{ old('stockminimo') }}">
                    <small class="text-muted">Si lo deja vacío, se sugerirá automáticamente el 20% del stock actual.</small>
                </div>

                <div class="card card-outline card-light collapsed-card mb-2">
                    <div class="card-header py-2">
                        <h3 class="card-title small mb-0">Opcionales avanzados</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body py-2 small" style="display:none;">
                        <div class="form-group">
                            <label>Proveedor (opcional)</label>
                            <input type="text" name="proveedor" id="proveedor" class="form-control" maxlength="100" value="{{ old('proveedor') }}">
                        </div>

                        <div class="form-group">
                            <label>Actor de abastecimiento (productor/proveedor)</label>
                            <select name="actorid" id="actorid" class="form-control">
                                <option value="">-- Sin vincular --</option>
                                @foreach($actores as $actor)
                                    <option value="{{ $actor->actorid }}" data-actor-nombre="{{ $actor->nombre }}"
                                        @selected((string) old('actorid') === (string) $actor->actorid)>
                                        {{ $actor->nombre }} ({{ ucfirst($actor->tipo_actor) }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Si el proveedor está vacío, se sugiere desde el actor seleccionado.</small>
                        </div>

                        <div class="form-group">
                            <label>Precio unitario (opcional)</label>
                            <input type="number" step="0.01" name="preciounitario" class="form-control" min="0" value="{{ old('preciounitario') }}">
                        </div>

                        <div class="form-group">
                            <label>Descripción (opcional)</label>
                            <textarea name="descripcion" class="form-control">{{ old('descripcion') }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer text-right">
                <a href="{{ route('insumos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-primary">Guardar</button>
            </div>

        </form>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const stockInput = document.getElementById('stock');
            const stockMinimoInput = document.getElementById('stockminimo');
            const proveedorInput = document.getElementById('proveedor');
            const actorSelect = document.getElementById('actorid');

            function sugerirStockMinimoSiVacio() {
                if (!stockInput || !stockMinimoInput) return;
                if ((stockMinimoInput.value || '').trim() !== '') return;
                const stock = parseFloat(stockInput.value || '0');
                if (!Number.isNaN(stock) && stock > 0) {
                    stockMinimoInput.value = (stock * 0.20).toFixed(2);
                }
            }

            if (stockInput && stockMinimoInput) {
                stockInput.addEventListener('blur', sugerirStockMinimoSiVacio);
            }

            if (actorSelect && proveedorInput) {
                actorSelect.addEventListener('change', function () {
                    if ((proveedorInput.value || '').trim() !== '') return;
                    const opt = actorSelect.options[actorSelect.selectedIndex];
                    const nombre = opt ? (opt.getAttribute('data-actor-nombre') || '') : '';
                    if (nombre) proveedorInput.value = nombre;
                });
            }

            // SMART UNIT CONVERSION (Stock variation)
            function checkSmartConversion() {
                const cantidadInput = $('input[name="stock"]');
                const unidadSelect = $('select[name="unidadmedidaid"]');
                const cantidad = parseFloat(cantidadInput.val()) || 0;
                const unidadOption = unidadSelect.find('option:selected');
                const unidadNombre = unidadOption.text().toLowerCase();

                $('#smartConversionAlert').remove();

                // Lógica idéntica, solo cambia el selector
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
                // LITROS -> m3 (Agregado para insumos líquidos)
                else if (unidadNombre.includes('litro') || unidadNombre.includes('lt')) {
                    if (cantidad >= 1000) {
                        const m3 = cantidad / 1000;
                        mostrarSugerenciaConversion(cantidadInput, 'm³', m3, 'metro cubico'); // Keyword aprox
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
                    $('input[name="stock"]').val(nuevoValor);
                    $('select[name="unidadmedidaid"] option').each(function () {
                        const text = $(this).text().toLowerCase();
                        // Busqueda un poco mas relajada para m3
                        if (text.includes(keywordNuevaUnidad) || (keywordNuevaUnidad === 'metro cubico' && text.includes('m3'))) {
                            $(this).prop('selected', true);
                            return false;
                        }
                    });
                    $('#smartConversionAlert').remove();
                });
            }

            $('input[name="stock"], select[name="unidadmedidaid"]').on('change keyup blur', function () {
                checkSmartConversion();
            });
        });
    </script>
@endpush