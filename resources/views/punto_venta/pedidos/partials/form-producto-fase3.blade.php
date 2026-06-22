@php
    $tipoSolicitud = old('tipo_solicitud', 'stock');
    $tiposEnvase = \App\Support\SolicitudProduccionPlantaCatalogo::tiposEnvase();
@endphp

<input type="hidden" name="tipo_solicitud" id="tipo_solicitud" value="{{ $tipoSolicitud }}">

<div class="pdv-tipo-solicitud mb-2" role="tablist">
    <button type="button" class="pdv-tipo-btn {{ $tipoSolicitud !== 'custom' ? 'is-active' : '' }}" data-tipo="stock">
        <i class="fas fa-boxes"></i> Disponible ahora
    </button>
    <button type="button" class="pdv-tipo-btn {{ $tipoSolicitud === 'custom' ? 'is-active' : '' }}" data-tipo="custom">
        <i class="fas fa-pen"></i> Pedir algo nuevo
    </button>
</div>

<div id="bloqueStock" class="{{ $tipoSolicitud === 'custom' ? 'd-none' : '' }}">
    <p class="small text-muted mb-2">
        Productos en almacén mayorista. Elija producto y presentación; el pedido irá solo al mayorista que seleccione.
    </p>
    <input type="hidden" name="insumoid" id="insumoid_real" value="{{ old('insumoid', '') }}">
    <input type="hidden" name="almacen_mayorista_origenid" id="almacen_mayorista_origenid" value="{{ old('almacen_mayorista_origenid', '') }}">
    <div class="pdv-producto-grid pdv-producto-grid--duo">
        <div class="pdv-producto-col">
            <label class="pdv-field-label">Producto <span class="text-danger">*</span></label>
            @include('partials.selector-catalogo', [
                'id' => 'dist_producto_mayorista',
                'name' => '_catalogo_producto_id',
                'value' => old('_catalogo_producto_id', ''),
                'labelSelected' => old('_producto_mayorista_label', ''),
                'endpoint' => route('catalogo-selector.productos-mayorista-pdv'),
                'title' => 'Productos por mayorista',
                'searchPlaceholder' => 'Nombre del producto…',
                'required' => $tipoSolicitud !== 'custom',
                'inputGroup' => true,
                'registerScript' => false,
            ])
            <div id="panelProductoMeta" class="pdv-producto-meta d-none">
                <div class="pdv-producto-meta__row">
                    <span class="pdv-producto-meta__label">Mayorista</span>
                    <span id="txtMetaMayorista" class="pdv-producto-meta__value"></span>
                </div>
                <div class="pdv-producto-meta__row">
                    <span class="pdv-producto-meta__label">Ubicación</span>
                    <span id="txtMetaUbicacion" class="pdv-producto-meta__value"></span>
                </div>
                <div id="rowMetaStock" class="pdv-producto-meta__row d-none">
                    <span class="pdv-producto-meta__label">Stock</span>
                    <span id="txtMetaStockKg" class="pdv-producto-meta__value"></span>
                </div>
            </div>
        </div>
        <div class="pdv-producto-col">
            <label class="pdv-field-label" for="selectPresentacionMayorista">Presentación <span class="text-danger">*</span></label>
            <div class="pdv-producto-pick {{ old('insumoid') ? '' : 'is-locked' }}" id="pdvPresentacionPick">
                <select name="insumo_presentacionid" id="selectPresentacionMayorista" class="form-control form-control-sm" @if($tipoSolicitud !== 'custom') required @endif disabled>
                    <option value="">Elegir producto primero…</option>
                </select>
                <small id="txtPresentacionAyuda" class="form-text text-muted mb-0 d-none">Cargando presentaciones…</small>
                <small id="txtPresentacionVacia" class="form-text text-muted mb-0 d-none">Este producto no tiene presentaciones registradas. Use «Pedir algo nuevo».</small>
            </div>
            <div id="panelEsperaStock" class="pdv-espera-stock d-none" role="alert" aria-hidden="true"></div>
            <div id="panelStockDisponible" class="pdv-stock-disponible d-none">
                <i class="fas fa-check-circle text-success"></i>
                <span id="txtStockDisponible"></span>
            </div>
        </div>
    </div>
</div>

<div id="bloqueCustom" class="{{ $tipoSolicitud !== 'custom' ? 'd-none' : '' }}">
    <p class="small text-muted mb-2">
        Si no está en almacén, describa lo que necesita. El mayorista coordinará con planta.
    </p>
    <div class="form-row">
        <div class="form-group col-md-7 mb-2">
            <label class="pdv-field-label" for="producto_nombre">Descripción del producto <span class="text-danger">*</span></label>
            <input type="text" name="producto_nombre" id="producto_nombre" class="form-control"
                value="{{ old('producto_nombre') }}" placeholder="Ej. Papas artesanales premium">
        </div>
        <div class="form-group col-md-5 mb-0">
            <label class="pdv-field-label" for="tipo_envase">Tipo de envase <span class="text-danger">*</span></label>
            <select name="tipo_envase" id="tipo_envase" class="form-control">
                <option value="">Seleccione…</option>
                @foreach($tiposEnvase as $tipo)
                    <option value="{{ $tipo }}" @selected(old('tipo_envase') === $tipo)>
                        {{ \App\Support\SolicitudProduccionPlantaCatalogo::etiquetaTipoEnvase($tipo) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="pdv-cantidad-row mt-2">
    <div class="pdv-cantidad-col">
        <label class="pdv-field-label" for="cantidad">Cantidad <span class="text-danger">*</span></label>
        <div class="pdv-cantidad-wrap input-group has-unidad" id="wrapCantidad">
            <input type="number" step="1" min="1" name="cantidad" id="cantidad" class="form-control" required
                value="{{ old('cantidad') }}" placeholder="0">
            <div class="input-group-append">
                <span class="input-group-text pdv-unidad-append" id="badgeUnidad">{{ old('_unidad_cantidad', 'unidades') }}</span>
            </div>
        </div>
        <small class="form-text text-muted mb-0" id="txtAyudaCantidad">Indique cuántas unidades necesita.</small>
        <div id="alertaStockExcedido" class="pdv-stock-alerta d-none">
            <i class="fas fa-exclamation-triangle"></i> <span id="txtAlertaStockExcedido">Supera el stock disponible.</span>
        </div>
    </div>
</div>

<style>
.pdv-tipo-solicitud { display: flex; gap: .45rem; flex-wrap: wrap; }
.pdv-tipo-btn {
    border: 2px solid #d1fae5; background: #fff; color: #047857;
    border-radius: 999px; padding: .4rem .85rem; font-weight: 600; font-size: .8rem;
}
.pdv-tipo-btn.is-active { background: linear-gradient(135deg,#047857,#10b981); color: #fff; border-color: #065f46; }
.pdv-producto-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: .65rem 1rem;
    align-items: start;
}
.pdv-cantidad-row { max-width: 9.5rem; }
.pdv-producto-meta {
    margin-top: .45rem; padding: .45rem .6rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: .76rem; line-height: 1.35;
}
.pdv-producto-meta__row {
    display: grid; grid-template-columns: 4.5rem 1fr; gap: .35rem .5rem;
    padding: .15rem 0;
}
.pdv-producto-meta__row + .pdv-producto-meta__row { border-top: 1px dashed #e2e8f0; }
.pdv-producto-meta__label {
    font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
    color: #64748b; font-size: .66rem;
}
.pdv-producto-meta__value { color: #334155; font-weight: 600; word-break: break-word; }
.pdv-stock-banner {
    display: flex; align-items: center; gap: .4rem;
    margin-top: .35rem; padding: .35rem .55rem;
    background: #ecfdf5; border: 1px solid #bbf7d0; border-radius: 8px;
    font-size: .78rem; font-weight: 600; color: #047857;
}
.pdv-stock-disponible {
    display: flex; align-items: flex-start; gap: .35rem;
    margin-top: .35rem; font-size: .78rem; font-weight: 600; color: #166534;
    line-height: 1.3;
}
.pdv-stock-alerta {
    margin-top: .3rem; font-size: .76rem; font-weight: 600; color: #b45309;
}
.pdv-espera-stock {
    display: flex; align-items: flex-start; gap: .45rem;
    margin-top: .4rem; padding: .5rem .65rem;
    background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;
    font-size: .76rem; color: #92400e; line-height: 1.35;
}
.pdv-espera-stock i { margin-top: .1rem; color: #d97706; }
.pdv-cantidad-wrap.is-invalid .form-control,
.pdv-cantidad-wrap.is-invalid .input-group-text {
    border-color: #f59e0b !important;
}
</style>
