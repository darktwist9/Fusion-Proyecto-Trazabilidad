@extends('layouts.app')

@section('content')
    <div class="card">

        <div class="card-header">
            <h3 class="card-title">Editar Insumo</h3>
        </div>

        <form action="{{ route('insumos.update', $insumo) }}" method="POST">
            @csrf
            @method('PUT')

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
                        <p class="mb-1"><strong>1)</strong> Edite nombre, tipo, unidad y stock actual.</p>
                        <p class="mb-1"><strong>2)</strong> El stock mínimo puede quedarse vacío y se recalcula al 20% del stock.</p>
                        <p class="mb-0"><strong>3)</strong> Complete datos opcionales solo si aportan trazabilidad comercial.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ $insumo->nombre }}" maxlength="100"
                        required>
                    <small class="text-muted">Nombre comercial u operativo del insumo.</small>
                </div>

                <div class="form-group">
                    <label>Tipo de insumo</label>
                    <select name="tipoinsumoid" class="form-control" required>
                        @foreach($tipos as $t)
                            <option value="{{ $t->tipoinsumoid }}" {{ $t->tipoinsumoid == $insumo->tipoinsumoid ? 'selected' : '' }}>
                                {{ $t->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Unidad de medida</label>
                    <select name="unidadmedidaid" class="form-control" required>
                        @foreach($unidades as $u)
                            <option value="{{ $u->unidadmedidaid }}" {{ $u->unidadmedidaid == $insumo->unidadmedidaid ? 'selected' : '' }}>
                                {{ $u->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Stock actual</label>
                    <input type="number" step="0.01" name="stock" id="stock" class="form-control" min="0" value="{{ $insumo->stock }}"
                        required>
                    <small class="text-muted">Cantidad disponible hoy.</small>
                </div>

                <div class="form-group">
                    <label>Stock mínimo (automático)</label>
                    <input type="number" step="0.01" name="stockminimo" id="stockminimo" class="form-control" min="0"
                        value="{{ $insumo->stockminimo }}">
                    <small class="text-muted">Si queda vacío, se sugiere automáticamente el 20% del stock.</small>
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
                            <label>Proveedor</label>
                            <input type="text" name="proveedor" id="proveedor" class="form-control" value="{{ $insumo->proveedor }}"
                                maxlength="100">
                        </div>

                        <div class="form-group">
                            <label>Actor de abastecimiento (productor/proveedor)</label>
                            <select name="actorid" id="actorid" class="form-control">
                                <option value="">-- Sin vincular --</option>
                                @foreach($actores as $actor)
                                    <option value="{{ $actor->actorid }}" data-actor-nombre="{{ $actor->nombre }}" {{ (int) $insumo->actorid === (int) $actor->actorid ? 'selected' : '' }}>
                                        {{ $actor->nombre }} ({{ ucfirst($actor->tipo_actor) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Precio unitario</label>
                            <input type="number" step="0.01" name="preciounitario" class="form-control" min="0"
                                value="{{ $insumo->preciounitario }}">
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" class="form-control">{{ $insumo->descripcion }}</textarea>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card-footer text-right">
                <a href="{{ route('insumos.index') }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-primary">Actualizar</button>
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
                else if (unidadNombre.includes('litro') || unidadNombre.includes('lt')) {
                    if (cantidad >= 1000) {
                        const m3 = cantidad / 1000;
                        mostrarSugerenciaConversion(cantidadInput, 'm³', m3, 'metro cubico');
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