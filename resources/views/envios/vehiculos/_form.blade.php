@php
    $v = $vehiculo ?? null;
    $tiposJson = $tipos->map(fn ($t) => [
        'id' => $t->tipovehiculoid,
        'kg' => $t->capacidad_kg,
        'm3' => $t->capacidad_m3,
        'licencia' => $t->licencia_requerida,
        'tamano' => $t->tamano,
        'label' => \App\Support\VehiculoTamanoCatalogo::etiqueta($t->tamano),
    ])->values();
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Placa <span class="text-danger">*</span></label>
            <input name="placa" class="form-control text-uppercase" value="{{ old('placa', $v?->placa) }}" required maxlength="20">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Marca</label>
            <input name="marca" class="form-control" value="{{ old('marca', $v?->marca) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>Modelo</label>
            <input name="modelo" class="form-control" value="{{ old('modelo', $v?->modelo) }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Año</label>
            <input type="number" name="anio" class="form-control" value="{{ old('anio', $v?->anio) }}" min="1980" max="2100">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Color</label>
            <input name="color" class="form-control" value="{{ old('color', $v?->color) }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Tipo de vehículo <span class="text-danger">*</span></label>
            <select name="tipovehiculoid" id="tipovehiculoid" class="form-control" required>
                <option value="">— Seleccionar —</option>
                @foreach($tipos as $tipo)
                    @php
                        $capKg = $tipo->capacidad_kg ? number_format((float) $tipo->capacidad_kg, 0).' kg' : null;
                        $capM3 = $tipo->capacidad_m3 ? number_format((float) $tipo->capacidad_m3, 1).' m³' : null;
                        $capTxt = collect([$capKg, $capM3])->filter()->implode(' / ');
                        $lic = $tipo->licencia_requerida ? 'Lic. '.$tipo->licencia_requerida : null;
                        $optionLabel = $tipo->nombre;
                        if ($capTxt) {
                            $optionLabel .= ' — '.$capTxt;
                        }
                        if ($lic) {
                            $optionLabel .= ' · '.$lic;
                        }
                    @endphp
                    <option value="{{ $tipo->tipovehiculoid }}" @selected((string) old('tipovehiculoid', $v?->tipovehiculoid) === (string) $tipo->tipovehiculoid)>
                        {{ $optionLabel }}
                    </option>
                @endforeach
            </select>
            <div id="tipoVehiculoResumen" class="small text-muted mt-1"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Categoría <span class="text-danger">*</span></label>
            <select name="ambito_flota" class="form-control" required>
                @foreach(\App\Support\TransportistaFlotaCatalogo::etiquetas() as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('ambito_flota', $v?->ambito_flota ?? 'agricola') === $valor)>
                    {{ \App\Support\TransportistaFlotaCatalogo::categoriaCorta($valor) }}
                </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card card-outline card-secondary mb-0">
            <div class="card-header py-2">
                <strong class="small"><i class="fas fa-sliders-h mr-1"></i> Capacidad específica de esta unidad</strong>
                <span class="text-muted small ml-1">(opcional — solo si difiere del tipo)</span>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-md-0">
                            <label>Peso máximo (kg)</label>
                            <input type="number" step="0.01" min="0" name="capacidad_kg_override" class="form-control"
                                   value="{{ old('capacidad_kg_override', $v?->capacidad_kg_override) }}" placeholder="Hereda del tipo si se deja vacío">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <label>Volumen máximo (m³)</label>
                            <input type="number" step="0.01" min="0" name="capacidad_m3_override" class="form-control"
                                   value="{{ old('capacidad_m3_override', $v?->capacidad_m3_override) }}" placeholder="Hereda del tipo si se deja vacío">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 mt-3">
        <input type="hidden" name="activo" value="0">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="activoVehiculo" name="activo" value="1"
                   @checked(old('activo', $v?->activo ?? true))>
            <label class="custom-control-label" for="activoVehiculo">Vehículo activo en flota</label>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tipos = @json($tiposJson);
    const select = document.getElementById('tipovehiculoid');
    const resumen = document.getElementById('tipoVehiculoResumen');
    if (!select || !resumen) return;

    function pintarResumen() {
        const tipo = tipos.find(t => String(t.id) === String(select.value));
        if (!tipo) {
            resumen.textContent = '';
            return;
        }
        const partes = [];
        if (tipo.kg) partes.push(Number(tipo.kg).toLocaleString('es-BO') + ' kg');
        if (tipo.m3) partes.push(Number(tipo.m3).toLocaleString('es-BO') + ' m³');
        if (tipo.label) partes.push('Tamaño ' + tipo.label);
        if (tipo.licencia) partes.push('Licencia mínima ' + tipo.licencia);
        resumen.textContent = partes.length ? 'Catálogo del tipo: ' + partes.join(' · ') : '';
    }

    select.addEventListener('change', pintarResumen);
    pintarResumen();
})();
</script>
@endpush
