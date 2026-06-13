@php
    $selectorId = $selectorId ?? 'lote_semilla';
    $insumoSemillaId = $insumoSemillaId ?? '';
    $insumoSemillaLabel = $insumoSemillaLabel ?? '';
@endphp

<div class="form-group">
    <label><i class="fas fa-seedling mr-1"></i> Semilla / cultivo a cosechar</label>
    <div class="d-flex flex-wrap align-items-start" style="gap: 6px;">
        @include('partials.selector-catalogo', [
            'id' => $selectorId,
            'name' => 'insumosemillaid',
            'value' => $insumoSemillaId,
            'labelSelected' => $insumoSemillaLabel,
            'endpoint' => route('catalogo-selector.insumos'),
            'params' => ['tipo_slug' => 'material_siembra'],
            'allowEmpty' => true,
            'placeholderEmpty' => 'Opcional — seleccione semilla del inventario',
            'title' => 'Seleccionar semilla',
            'searchPlaceholder' => 'Nombre de semilla o material de siembra…',
            'searchLabel' => 'Buscar semilla',
            'modalIcon' => 'fa-seedling',
            'rowIcon' => 'fa-seedling',
            'inputGroup' => true,
        ])
        <a href="{{ route('insumos.create') }}"
           target="_blank" rel="noopener"
           class="btn btn-outline-success" title="Registrar semilla en inventario">
            <i class="fas fa-plus"></i>
        </a>
    </div>
    <p class="campo-guia mb-1">
        Elija el insumo de semilla que sembrará en este lote. El cultivo se define por la semilla, no por un catálogo aparte.
    </p>
    <div id="dosisSiembraPreview" class="alert alert-light border small mb-0 d-none">
        <i class="fas fa-calculator text-success mr-1"></i>
        <span id="dosisSiembraTexto"></span>
    </div>
</div>
