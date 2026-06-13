@php
    use App\Support\InsumoCatalogo;

    $insumo = $insumo ?? null;
    $tipos = $tipos ?? collect();
    $unidadesPorTipo = $unidadesPorTipo ?? [];
    $tipoSlugInicial = $insumo
        ? (InsumoCatalogo::slugFromNombreTipo($insumo->tipo?->nombre) ?? 'material_siembra')
        : (InsumoCatalogo::slugFromNombreTipo($tipos->first()?->nombre) ?? 'material_siembra');
    $umbral = InsumoCatalogo::UMBRAL_ALERTA_STOCK;

    $iconosTipo = [
        'material_siembra' => ['icon' => 'fa-seedling', 'color' => '#15803d', 'bg' => '#ecfdf5'],
        'fertilizantes' => ['icon' => 'fa-flask', 'color' => '#1d4ed8', 'bg' => '#eff6ff'],
        'pesticidas' => ['icon' => 'fa-bug', 'color' => '#b45309', 'bg' => '#fffbeb'],
        'material_riego' => ['icon' => 'fa-tint', 'color' => '#0e7490', 'bg' => '#ecfeff'],
    ];
@endphp

@push('styles')
<style>
.page-insumo-form .ins-form-shell {
    max-width: 920px;
    margin: 0 auto;
}
.page-insumo-form .ins-form-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(15, 23, 42, .08);
    overflow: hidden;
}
.page-insumo-form .ins-form-hero {
    background: linear-gradient(135deg, #14532d 0%, #166534 45%, #22c55e 100%);
    color: #fff;
    padding: 1.35rem 1.5rem;
}
.page-insumo-form .ins-form-hero h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 0 .35rem;
}
.page-insumo-form .ins-form-hero p {
    margin: 0;
    font-size: .88rem;
    opacity: .92;
}
.page-insumo-form .ins-form-body {
    padding: 1.35rem 1.5rem 1.1rem;
    background: #fff;
}
.page-insumo-form .ins-form-section {
    margin-bottom: 1.35rem;
}
.page-insumo-form .ins-form-section:last-child {
    margin-bottom: 0;
}
.page-insumo-form .ins-section-label {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: .75rem;
    display: flex;
    align-items: center;
    gap: .45rem;
}
.page-insumo-form .ins-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e2e8f0;
}
.page-insumo-form .ins-field-label {
    font-size: .86rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: .35rem;
}
.page-insumo-form .ins-field-hint {
    font-size: .78rem;
    color: #94a3b8;
    margin-top: .35rem;
    line-height: 1.35;
}
.page-insumo-form .ins-alert-strip {
    display: flex;
    align-items: flex-start;
    gap: .65rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: .65rem .85rem;
    font-size: .82rem;
    color: #92400e;
    margin-bottom: 1.25rem;
}
.page-insumo-form .ins-alert-strip i {
    margin-top: .15rem;
    color: #d97706;
}
.page-insumo-form .form-control {
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    min-height: 42px;
    font-size: .92rem;
}
.page-insumo-form .form-control:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
}
.page-insumo-form .ins-tipo-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
    margin-bottom: .65rem;
}
@media (min-width: 576px) {
    .page-insumo-form .ins-tipo-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}
.page-insumo-form .ins-tipo-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: .7rem .55rem;
    text-align: center;
    cursor: pointer;
    background: #fff;
    transition: border-color .15s, box-shadow .15s, transform .15s;
    user-select: none;
}
.page-insumo-form .ins-tipo-card:hover {
    border-color: #86efac;
    transform: translateY(-1px);
}
.page-insumo-form .ins-tipo-card.is-active {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22, 163, 74, .14);
    background: #f0fdf4;
}
.page-insumo-form .ins-tipo-card__icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .95rem;
    margin-bottom: .4rem;
}
.page-insumo-form .ins-tipo-card__name {
    font-size: .72rem;
    font-weight: 700;
    color: #334155;
    line-height: 1.25;
}
.page-insumo-form .ins-tipo-select-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none;
}
.page-insumo-form .ins-flow-panel {
    background: linear-gradient(135deg, #f8fafc 0%, #f0fdf4 100%);
    border: 1px solid #d1fae5;
    border-radius: 12px;
    padding: .85rem 1rem;
    margin-top: 1.25rem;
}
.page-insumo-form .ins-flow-panel__title {
    font-size: .78rem;
    font-weight: 700;
    color: #166534;
    margin-bottom: .55rem;
}
.page-insumo-form .ins-flow-steps {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .35rem .5rem;
    font-size: .76rem;
    color: #475569;
}
.page-insumo-form .ins-flow-step {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: .2rem .55rem;
    font-weight: 600;
}
.page-insumo-form .ins-flow-arrow {
    color: #94a3b8;
    font-size: .7rem;
}
.page-insumo-form .ins-form-footer {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
}
.page-insumo-form .btn-success {
    background: #16a34a;
    border-color: #16a34a;
    border-radius: 10px;
    font-weight: 600;
    padding: .5rem 1.15rem;
}
.page-insumo-form .btn-outline-secondary {
    border-radius: 10px;
}
</style>
@endpush

<div class="modulo-inv page-insumo-form">
    <div class="ins-form-shell">
        <div class="card ins-form-card card-modulo-main">
            <div class="ins-form-hero">
                <h3><i class="fas fa-boxes mr-2"></i>{{ $tituloFormulario ?? ($insumo ? 'Editar insumo' : 'Registrar insumo') }}</h3>
                <p>Insumos del campo: semillas, fertilizantes, pesticidas y material de riego. No registre aquí productos cosechados.</p>
            </div>

            <form action="{{ $formAction }}" method="POST">
                @csrf
                @if($formMethod ?? false)
                    @method($formMethod)
                @endif

                <div class="ins-form-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2">
                            <ul class="mb-0 pl-3 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="ins-alert-strip">
                        <i class="fas fa-bell"></i>
                        <span>Alerta automática cuando el stock llegue a <strong>{{ $umbral }} o menos</strong> unidades (según la unidad elegida).</span>
                    </div>

                    <div class="ins-form-section">
                        <div class="ins-section-label"><i class="fas fa-tag"></i> Identificación</div>
                        <label class="ins-field-label" for="nombre">Nombre del insumo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control" maxlength="100" required
                            value="{{ old('nombre', $insumo->nombre ?? '') }}"
                            placeholder="Ej. Semilla híbrida F1, Urea 46%, Manguera 2&quot;">
                        <div class="ins-field-hint">Nombre con el que lo verá en inventario y al aplicarlo a un lote.</div>
                    </div>

                    <div class="ins-form-section">
                        <div class="ins-section-label"><i class="fas fa-layer-group"></i> Clasificación</div>

                        <label class="ins-field-label">Tipo de insumo <span class="text-danger">*</span></label>
                        <div class="ins-tipo-grid" id="insTipoGrid" role="radiogroup" aria-label="Tipo de insumo">
                            @foreach($tipos as $t)
                                @php
                                    $slug = InsumoCatalogo::slugFromNombreTipo($t->nombre) ?? 'material_siembra';
                                    $meta = $iconosTipo[$slug] ?? $iconosTipo['material_siembra'];
                                    $selected = (int) old('tipoinsumoid', $insumo->tipoinsumoid ?? 0) === (int) $t->tipoinsumoid;
                                @endphp
                                <div class="ins-tipo-card {{ $selected ? 'is-active' : '' }}"
                                     data-tipo-id="{{ $t->tipoinsumoid }}"
                                     data-slug="{{ $slug }}"
                                     role="radio"
                                     aria-checked="{{ $selected ? 'true' : 'false' }}"
                                     tabindex="0">
                                    <div class="ins-tipo-card__icon" style="background:{{ $meta['bg'] }};color:{{ $meta['color'] }}">
                                        <i class="fas {{ $meta['icon'] }}"></i>
                                    </div>
                                    <div class="ins-tipo-card__name">{{ $t->nombre }}</div>
                                </div>
                            @endforeach
                        </div>
                        <select name="tipoinsumoid" id="tipoinsumoid" class="ins-tipo-select-hidden" required tabindex="-1" aria-hidden="true">
                            @foreach($tipos as $t)
                                @php $slug = InsumoCatalogo::slugFromNombreTipo($t->nombre); @endphp
                                <option value="{{ $t->tipoinsumoid }}"
                                    data-slug="{{ $slug }}"
                                    @selected((int) old('tipoinsumoid', $insumo->tipoinsumoid ?? 0) === (int) $t->tipoinsumoid)>
                                    {{ $t->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <div class="ins-field-hint" id="guiaUnidad">
                            @if($tipoSlugInicial === 'pesticidas')
                                Pesticidas: peso (kg, g) o volumen (ml, L).
                            @elseif($tipoSlugInicial === 'material_riego')
                                Riego: metros para mangueras; unidades para equipos.
                            @elseif($tipoSlugInicial === 'fertilizantes')
                                Fertilizantes: kg, g, quintales o litros.
                            @else
                                Semillas: kg, g, quintales o unidades (bolsas, sobres).
                            @endif
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label class="ins-field-label" for="unidadmedidaid">Unidad de medida <span class="text-danger">*</span></label>
                                <select name="unidadmedidaid" id="unidadmedidaid" class="form-control" required
                                    data-selected="{{ old('unidadmedidaid', $insumo->unidadmedidaid ?? '') }}">
                                    <option value="">— Elija tipo primero —</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="ins-field-label" for="stock">Stock actual <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="stock" id="stock" class="form-control" min="0" required
                                    value="{{ old('stock', $insumo->stock ?? '') }}"
                                    placeholder="Ej. 120">
                                <div class="ins-field-hint">Cantidad disponible hoy en la unidad elegida.</div>
                            </div>
                        </div>
                    </div>

                    <div class="ins-form-section" id="insDosisSiembraSection" style="{{ $tipoSlugInicial === 'material_siembra' ? '' : 'display:none;' }}">
                        <div class="ins-section-label"><i class="fas fa-calculator"></i> Dosis de siembra</div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="ins-field-label" for="dosis_por_ha">Cantidad por hectárea</label>
                                <input type="number" step="0.001" min="0" name="dosis_por_ha" id="dosis_por_ha" class="form-control"
                                    value="{{ old('dosis_por_ha', $insumo->dosis_por_ha ?? '') }}"
                                    placeholder="Ej. 8">
                                <div class="ins-field-hint">Referencia para calcular semilla según la superficie del lote.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="ins-field-label" for="dosis_unidad">Unidad de la dosis</label>
                                <input type="text" name="dosis_unidad" id="dosis_unidad" class="form-control" maxlength="20"
                                    value="{{ old('dosis_unidad', $insumo->dosis_unidad ?? '') }}"
                                    placeholder="kg, g, und…">
                            </div>
                        </div>
                    </div>

                    <div class="ins-form-section">
                        <div class="ins-section-label"><i class="fas fa-sticky-note"></i> Notas</div>
                        <label class="ins-field-label" for="descripcion">Descripción <span class="text-muted font-weight-normal">(opcional)</span></label>
                        <textarea name="descripcion" id="descripcion" class="form-control" rows="2"
                            placeholder="Concentración, lote de compra, ubicación en bodega…">{{ old('descripcion', $insumo->descripcion ?? '') }}</textarea>
                    </div>

                    <div class="ins-flow-panel">
                        <div class="ins-flow-panel__title"><i class="fas fa-route mr-1"></i> ¿Dónde se usa después?</div>
                        <div class="ins-flow-steps">
                            <span class="ins-flow-step"><i class="fas fa-boxes text-success"></i> Inventario</span>
                            <span class="ins-flow-arrow"><i class="fas fa-chevron-right"></i></span>
                            <span class="ins-flow-step"><i class="fas fa-map text-primary"></i> Aplicación en lote</span>
                            <span class="ins-flow-arrow"><i class="fas fa-chevron-right"></i></span>
                            <span class="ins-flow-step"><i class="fas fa-minus-circle text-warning"></i> Descuenta stock</span>
                            <span class="ins-flow-arrow"><i class="fas fa-chevron-right"></i></span>
                            <span class="ins-flow-step"><i class="fas fa-history text-muted"></i> Trazabilidad del lote</span>
                        </div>
                    </div>
                </div>

                <div class="ins-form-footer">
                    <a href="{{ route('insumos.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-save mr-1"></i> {{ $botonGuardar ?? 'Guardar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const unidadesPorTipo = @json($unidadesPorTipo);
    const guiasUnidad = {
        material_siembra: 'Semillas: kg, g, quintales o unidades (bolsas, sobres).',
        fertilizantes: 'Fertilizantes: kg, g, quintales o litros.',
        pesticidas: 'Pesticidas: peso (kg, g) o volumen (ml, L).',
        material_riego: 'Riego: <strong>metro</strong> para mangueras; <strong>unidad</strong> para equipos.',
    };

    const selTipo = document.getElementById('tipoinsumoid');
    const selUm = document.getElementById('unidadmedidaid');
    const guia = document.getElementById('guiaUnidad');
    const grid = document.getElementById('insTipoGrid');
    const dosisSection = document.getElementById('insDosisSiembraSection');

    function slugTipo() {
        const opt = selTipo.options[selTipo.selectedIndex];
        return opt ? (opt.getAttribute('data-slug') || '') : '';
    }

    function marcarTarjetaActiva(tipoId) {
        if (!grid) return;
        grid.querySelectorAll('.ins-tipo-card').forEach(function (card) {
            const activa = String(card.getAttribute('data-tipo-id')) === String(tipoId);
            card.classList.toggle('is-active', activa);
            card.setAttribute('aria-checked', activa ? 'true' : 'false');
        });
    }

    function pintarUnidades() {
        const slug = slugTipo();
        const lista = unidadesPorTipo[slug] || [];
        const prev = selUm.getAttribute('data-selected') || selUm.value;

        selUm.innerHTML = '';
        if (!lista.length) {
            selUm.innerHTML = '<option value="">Sin unidades para este tipo</option>';
            return;
        }
        lista.forEach(function (u) {
            const o = document.createElement('option');
            o.value = u.id;
            o.textContent = u.nombre + (u.abreviatura ? ' (' + u.abreviatura + ')' : '');
            if (String(u.id) === String(prev)) {
                o.selected = true;
            }
            selUm.appendChild(o);
        });
        if (!selUm.value && lista.length) {
            selUm.selectedIndex = 0;
        }
        selUm.removeAttribute('data-selected');
    }

    function actualizarGuia() {
        const slug = slugTipo();
        if (guia) {
            guia.innerHTML = guiasUnidad[slug] || 'Elija la unidad con la que contará stock este insumo.';
        }
        if (dosisSection) {
            dosisSection.style.display = slug === 'material_siembra' ? '' : 'none';
        }
        pintarUnidades();
    }

    function elegirTipo(tipoId) {
        selTipo.value = String(tipoId);
        marcarTarjetaActiva(tipoId);
        actualizarGuia();
    }

    if (grid) {
        grid.addEventListener('click', function (e) {
            const card = e.target.closest('.ins-tipo-card');
            if (!card) return;
            elegirTipo(card.getAttribute('data-tipo-id'));
        });
        grid.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const card = e.target.closest('.ins-tipo-card');
            if (!card) return;
            e.preventDefault();
            elegirTipo(card.getAttribute('data-tipo-id'));
        });
    }

    selTipo.addEventListener('change', function () {
        marcarTarjetaActiva(selTipo.value);
        actualizarGuia();
    });

    marcarTarjetaActiva(selTipo.value);
    actualizarGuia();
})();
</script>
@endpush
