@extends('layouts.app')

@section('title', 'Nuevo pedido de distribución')
@section('page_title', 'Nuevo pedido de distribución')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@include('punto_venta.partials.modulo-styles')
<style>
.pedido-dist-page .pdv-hero {
    background: linear-gradient(135deg, #14532d 0%, #166534 45%, #22c55e 100%);
    border-radius: 14px 14px 0 0;
    padding: .85rem 1.15rem;
    color: #fff;
}
.pedido-dist-page .pdv-hero-kicker {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .85;
    margin-bottom: .25rem;
}
.pedido-dist-page .pdv-hero-title {
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.25;
}
.pedido-dist-page .pdv-hero-code {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-top: .35rem;
    padding: .2rem .6rem;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 999px;
    font-size: .82rem;
    font-weight: 600;
}
.pedido-dist-page .pdv-intro {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: .5rem .75rem;
    color: #166534;
    font-size: .82rem;
    line-height: 1.4;
}
.pedido-dist-page .pdv-form-block {
    background: #fff;
    border: 1px solid #e8f0ea;
    border-radius: 12px;
    padding: .65rem .85rem;
    margin-bottom: .55rem;
}
.pedido-dist-page .pdv-form-block-head {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-bottom: .5rem;
    padding-bottom: .4rem;
    border-bottom: 1px solid #f1f5f9;
}
.pedido-dist-page .pdv-form-block-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    flex-shrink: 0;
}
.pedido-dist-page .pdv-form-block-icon--destino { background: #dbeafe; color: #1d4ed8; }
.pedido-dist-page .pdv-form-block-icon--producto { background: #fef3c7; color: #b45309; }
.pedido-dist-page .pdv-form-block-icon--datos { background: #f3e8ff; color: #7c3aed; }
.pedido-dist-page .pdv-form-block-title {
    font-size: .88rem;
    font-weight: 800;
    color: #1e293b;
    margin: 0;
    line-height: 1.2;
}
.pedido-dist-page .pdv-form-block-sub {
    font-size: .72rem;
    color: #64748b;
    margin: .1rem 0 0;
}
.pedido-dist-page .pdv-destino-pick {
    display: flex;
    align-items: center;
    gap: .65rem;
    border: 1px solid #dbe3ea;
    border-radius: 10px;
    background: #fff;
    padding: .55rem .65rem;
}
.pedido-dist-page .pdv-destino-pick.is-empty { background: #f8fafc; }
.pedido-dist-page .pdv-destino-pick-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    background: #dbeafe;
    color: #1d4ed8;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .9rem;
}
.pedido-dist-page .pdv-destino-pick-body {
    flex: 1;
    min-width: 0;
}
.pedido-dist-page .pdv-destino-nombre {
    display: block;
    font-weight: 700;
    font-size: .9rem;
    color: #0f172a;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pedido-dist-page .pdv-destino-nombre.is-placeholder {
    color: #94a3b8;
    font-weight: 500;
}
.pedido-dist-page .pdv-destino-map-link {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-top: .15rem;
    padding: 0;
    border: 0;
    background: none;
    color: #166534;
    font-size: .74rem;
    font-weight: 600;
    text-decoration: underline;
    cursor: pointer;
}
.pedido-dist-page .pdv-destino-map-link:hover { color: #14532d; }
.pedido-dist-page .pdv-destino-map-link:disabled {
    color: #94a3b8;
    text-decoration: none;
    cursor: not-allowed;
}
.pedido-dist-page .pdv-destino-actions {
    display: flex;
    align-items: center;
    gap: .35rem;
    flex-shrink: 0;
}
.pedido-dist-page .pdv-destino-btn {
    border-radius: 8px;
    font-size: .78rem;
    font-weight: 700;
    padding: .35rem .7rem;
    white-space: nowrap;
}
#modalMapaPdvDestino .pdv-mapa-wrap {
    height: 340px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}
#modalMapaPdvDestino .pdv-mapa-lista {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .75rem;
    max-height: 110px;
    overflow-y: auto;
}
#modalMapaPdvDestino .pdv-mapa-chip {
    border: 1px solid #cbd5e1;
    background: #fff;
    border-radius: 999px;
    padding: .25rem .65rem;
    font-size: .75rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
}
#modalMapaPdvDestino .pdv-mapa-chip:hover,
#modalMapaPdvDestino .pdv-mapa-chip.is-active {
    border-color: #22c55e;
    background: #f0fdf4;
    color: #166534;
}
.pedido-dist-mapa-pin {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .72rem;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.pedido-dist-mapa-pin.is-selected { box-shadow: 0 0 0 3px rgba(34,197,94,.45); }
.pedido-dist-page .pdv-field-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: .3rem;
}
.pedido-dist-page .pdv-fecha-hint {
    font-size: .72rem;
    color: #b45309;
    margin-top: .2rem;
    line-height: 1.35;
}
.pedido-dist-page .pdv-form-block .form-group {
    margin-bottom: 0;
}
.pedido-dist-page .pdv-field-readonly {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    font-weight: 600;
    color: #475569;
}
.pedido-dist-page .pdv-cantidad-wrap {
    position: relative;
}
.pedido-dist-page .pdv-cantidad-wrap .form-control {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    font-size: .95rem;
    font-weight: 600;
    padding: .45rem .75rem;
    min-height: 40px;
}
.pedido-dist-page .pdv-cantidad-wrap .form-control:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 .15rem rgba(34,197,94,.12);
}
.pedido-dist-page .pdv-footer {
    background: #f8fafc;
    border-top: 1px solid #e8f0ea;
    border-radius: 0 0 14px 14px;
}
.pedido-dist-page .btn-enviar-pedido {
    border-radius: 10px;
    font-weight: 700;
    padding: .6rem 1.35rem;
    box-shadow: 0 4px 14px rgba(22,163,74,.25);
}
</style>
@endpush

@section('content')
<div class="pedido-dist-page">
    <div class="card pdv-card border-0 shadow-sm overflow-hidden">
        <div class="pdv-hero">
            <div class="pdv-hero-kicker">Nueva solicitud</div>
            <h2 class="pdv-hero-title"><i class="fas fa-truck-loading mr-2"></i>Pedido de distribución</h2>
            <div class="pdv-hero-code"><i class="fas fa-hashtag"></i> {{ $numeroSolicitud }}</div>
        </div>

        <div class="card-body px-3 py-3">
            <div class="pdv-intro mb-3">
                <i class="fas fa-info-circle mr-1"></i>
                @if($esMinorista ?? false)
                    Solicite producto terminado de planta para su punto de venta. Planta revisará stock y preparará el envío.
                @else
                    Registre una solicitud hacia un punto de venta. Planta revisará y despachará el producto.
                @endif
            </div>

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('punto-venta.pedidos.store') }}" id="formPedidoDist">
                @csrf

                <div class="pdv-form-block">
                    <div class="pdv-form-block-head">
                        <div class="pdv-form-block-icon pdv-form-block-icon--datos"><i class="fas fa-clipboard-list"></i></div>
                        <div>
                            <h3 class="pdv-form-block-title">Datos de la solicitud</h3>
                            <p class="pdv-form-block-sub">Número de referencia y fecha deseada de entrega</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-5 mb-md-0">
                            <label class="pdv-field-label">N° solicitud</label>
                            <input type="text" class="form-control pdv-field-readonly" value="{{ $numeroSolicitud }}" readonly>
                        </div>
                        <div class="form-group col-md-7 mb-0">
                            <label class="pdv-field-label" for="fecha_entrega_deseada">Fecha entrega deseada</label>
                            <input type="date" name="fecha_entrega_deseada" id="fecha_entrega_deseada" class="form-control form-control-sm"
                                value="{{ old('fecha_entrega_deseada') }}">
                            <small class="pdv-fecha-hint d-block">
                                <i class="fas fa-exclamation-circle mr-1"></i>Si no marca fecha, el pedido quedará solicitado para hoy.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="pdv-form-block">
                    <div class="pdv-form-block-head">
                        <div class="pdv-form-block-icon pdv-form-block-icon--destino"><i class="fas fa-store"></i></div>
                        <div>
                            <h3 class="pdv-form-block-title">Punto de venta destino</h3>
                            <p class="pdv-form-block-sub">Elija a qué local llegará el pedido</p>
                        </div>
                    </div>

                    @if(($esMinorista ?? false) && ($puntosMinorista ?? collect())->isEmpty())
                        <div class="alert alert-warning py-2 mb-0 small">
                            Registre un punto de venta activo antes de solicitar producto.
                            <a href="{{ route('punto-venta.puntos.create') }}">Crear punto de venta</a>
                        </div>
                    @else
                        <label class="pdv-field-label mb-2">Destino <span class="text-danger">*</span></label>
                        <div class="pdv-destino-pick {{ ($oldPuntoLabel ?? '') ? '' : 'is-empty' }}" id="pdvDestinoPick">
                            <div class="pdv-destino-pick-icon"><i class="fas fa-store"></i></div>
                            <div class="pdv-destino-pick-body">
                                <span class="pdv-destino-nombre {{ ($oldPuntoLabel ?? '') ? '' : 'is-placeholder' }}" id="pdvDestinoNombre">
                                    {{ $oldPuntoLabel ?: 'Seleccione un punto de venta' }}
                                </span>
                                <button type="button" class="pdv-destino-map-link" id="btnVerMapaPdvDestino"
                                    @if(empty($puntosVentaMapa)) disabled @endif>
                                    <i class="fas fa-map-marked-alt"></i> Ver ubicación en mapa
                                </button>
                            </div>
                            <div class="pdv-destino-actions">
                                <button type="button" class="btn btn-outline-success pdv-destino-btn" id="btnBuscarPdvDestino">
                                    <i class="fas fa-search mr-1"></i>Buscar
                                </button>
                            </div>
                        </div>
                        <div class="d-none" aria-hidden="true">
                            @include('partials.selector-catalogo', [
                                'id' => 'dist_punto_venta',
                                'name' => 'puntoventaid',
                                'value' => old('puntoventaid', $oldPuntoId ?? ''),
                                'labelSelected' => $oldPuntoLabel,
                                'endpoint' => route('catalogo-selector.puntos-venta'),
                                'title' => ($esMinorista ?? false) ? 'Mis puntos de venta' : 'Buscar punto de venta',
                                'searchPlaceholder' => ($esMinorista ?? false)
                                    ? 'Nombre o dirección del punto…'
                                    : 'Nombre, dirección o minorista…',
                                'searchLabel' => 'Buscar punto de venta',
                                'modalIcon' => 'fa-store',
                                'rowIcon' => 'fa-store',
                                'colDetalle' => 'Ubicación',
                                'required' => true,
                                'inputGroup' => true,
                                'filter' => (! ($esMinorista ?? false) && $minoristasFiltro->isNotEmpty()) ? [
                                    'param' => 'minorista_usuarioid',
                                    'options' => array_merge(
                                        [['value' => '', 'label' => 'Todos los minoristas']],
                                        $minoristasFiltro->map(fn ($m) => [
                                            'value' => (string) $m->usuarioid,
                                            'label' => trim($m->nombre.' '.$m->apellido),
                                        ])->all()
                                    ),
                                ] : null,
                            ])
                        </div>
                    @endif

                    @if($esAdmin ?? false)
                    <div class="mt-3">
                        <label class="pdv-field-label">Almacén planta (origen)</label>
                        @include('partials.selector-catalogo', [
                            'id' => 'dist_almacen_planta',
                            'name' => 'almacen_planta_origenid',
                            'value' => old('almacen_planta_origenid', ''),
                            'labelSelected' => $oldAlmacenLabel,
                            'endpoint' => route('catalogo-selector.almacenes'),
                            'params' => ['ambito' => 'planta'],
                            'title' => 'Buscar almacén de planta',
                            'searchPlaceholder' => 'Nombre o ubicación…',
                            'allowEmpty' => true,
                            'placeholderEmpty' => 'Automático según producto',
                            'inputGroup' => true,
                        ])
                    </div>
                    @endif
                </div>

                <div class="pdv-form-block">
                    <div class="pdv-form-block-head">
                        <div class="pdv-form-block-icon pdv-form-block-icon--producto"><i class="fas fa-box"></i></div>
                        <div>
                            <h3 class="pdv-form-block-title">Producto y cantidad</h3>
                            <p class="pdv-form-block-sub">Qué necesita y en qué cantidad</p>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label class="pdv-field-label">Producto (stock planta) <span class="text-danger">*</span></label>
                            @include('partials.selector-catalogo', [
                                'id' => 'dist_producto_planta',
                                'name' => 'insumoid',
                                'value' => old('insumoid', ''),
                                'labelSelected' => $oldProductoLabel,
                                'endpoint' => route('catalogo-selector.insumos'),
                                'params' => ['ambito_planta' => '1'],
                                'title' => 'Buscar producto en planta',
                                'searchPlaceholder' => 'Nombre del producto…',
                                'required' => true,
                                'inputGroup' => true,
                            ])
                            <small id="txtStockDisponible" class="form-text text-success font-weight-bold"></small>
                        </div>
                        <div class="form-group col-md-5">
                            <label class="pdv-field-label d-flex align-items-center justify-content-between" for="cantidad">
                                <span>Cantidad <span class="text-danger">*</span></span>
                                <span class="pdv-unidad-badge pdv-unidad-badge--inline {{ $oldProductoUnidad ? '' : 'd-none' }}" id="badgeUnidad">{{ $oldProductoUnidad }}</span>
                            </label>
                            <div class="pdv-cantidad-wrap">
                                <input type="number" step="0.01" min="0.01" name="cantidad" id="cantidad" class="form-control" required
                                    value="{{ old('cantidad') }}" placeholder="0.00">
                            </div>
                            <small class="form-text text-muted" id="txtAyudaCantidad">
                                @if($oldProductoUnidad)
                                    Indique la cantidad en {{ $oldProductoUnidad }}.
                                @else
                                    La unidad aparece al elegir el producto.
                                @endif
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="pdv-field-label" for="observaciones">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" rows="2" class="form-control"
                        style="border-radius:10px;border:2px solid #e2e8f0;"
                        placeholder="Instrucciones de entrega, horario preferido…">{{ old('observaciones') }}</textarea>
                </div>
            </form>
        </div>

        <div class="card-footer pdv-footer d-flex justify-content-between align-items-center py-2 px-3">
            <a href="{{ route('punto-venta.pedidos.index') }}" class="btn btn-light border">
                <i class="fas fa-arrow-left mr-1"></i> Cancelar
            </a>
            <button type="submit" form="formPedidoDist" class="btn btn-success btn-enviar-pedido" id="btnEnviarPedido">
                <i class="fas fa-paper-plane mr-1"></i> Enviar solicitud
            </button>
        </div>
    </div>
</div>

@if(! (($esMinorista ?? false) && ($puntosMinorista ?? collect())->isEmpty()))
<div class="modal fade" id="modalMapaPdvDestino" tabindex="-1" role="dialog" aria-labelledby="modalMapaPdvDestinoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;border:0;">
            <div class="modal-header py-2" style="background:linear-gradient(135deg,#14532d,#22c55e);color:#fff;border:0;">
                <h5 class="modal-title font-weight-bold" id="modalMapaPdvDestinoLabel">
                    <i class="fas fa-map-marked-alt mr-2"></i>
                    {{ ($esMinorista ?? false) ? 'Mis puntos de venta' : 'Puntos de venta en mapa' }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body py-3">
                <p class="small text-muted mb-2">Clic en un marcador o en la lista para elegir el destino del pedido.</p>
                <div id="mapaPdvDestino" class="pdv-mapa-wrap"></div>
                <div class="pdv-mapa-lista" id="listaPdvMapaDestino"></div>
            </div>
            <div class="modal-footer py-2 bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var stockActual = {{ $oldProductoStock !== null ? json_encode($oldProductoStock) : 'null' }};
    var unidadActual = @json($oldProductoUnidad ?: '');
    var puntosVentaMapa = @json($puntosVentaMapa ?? []);
    var pdvSeleccionadoId = @json(old('puntoventaid', $oldPuntoId ?? ''));

    function getPdvWrap() {
        return document.getElementById('selector_wrap_dist_punto_venta');
    }

    function actualizarDestinoVisible(id, label) {
        var nombreEl = document.getElementById('pdvDestinoNombre');
        var pickEl = document.getElementById('pdvDestinoPick');
        if (!nombreEl || !pickEl) return;

        pdvSeleccionadoId = id ? String(id) : '';
        if (id && label) {
            nombreEl.textContent = label;
            nombreEl.classList.remove('is-placeholder');
            pickEl.classList.remove('is-empty');
        } else {
            nombreEl.textContent = 'Seleccione un punto de venta';
            nombreEl.classList.add('is-placeholder');
            pickEl.classList.add('is-empty');
        }
    }

    function aplicarPdvSeleccionado(pdv) {
        if (!pdv || !pdv.id) return;
        var wrap = getPdvWrap();
        if (!wrap) return;

        wrap.querySelector('.selector-catalogo-value').value = pdv.id;
        var display = wrap.querySelector('.selector-catalogo-label');
        if (display) {
            display.value = pdv.label;
            display.classList.remove('text-muted');
        }
        actualizarDestinoVisible(pdv.id, pdv.label);
        wrap.dispatchEvent(new CustomEvent('selector-catalogo:change', {
            bubbles: true,
            detail: {
                id: pdv.id,
                label: pdv.label,
                extra: {
                    ubicacion_resumen: pdv.resumen || '',
                    direccion: pdv.direccion || '',
                    lat: pdv.lat,
                    lng: pdv.lng,
                },
            },
        }));
    }

    function buscarPdvEnMapa(id) {
        return puntosVentaMapa.find(function (p) { return String(p.id) === String(id); }) || null;
    }

    function actualizarUnidad(extra) {
        var badge = document.getElementById('badgeUnidad');
        var ayuda = document.getElementById('txtAyudaCantidad');
        var unidad = (extra && extra.unidad) ? String(extra.unidad).trim() : '';
        unidadActual = unidad;
        if (badge) {
            if (unidad) {
                badge.textContent = unidad;
                badge.classList.remove('d-none');
            } else {
                badge.textContent = '';
                badge.classList.add('d-none');
            }
        }
        if (ayuda) {
            ayuda.textContent = unidad
                ? 'Indique la cantidad en ' + unidad + '.'
                : 'La unidad aparece al elegir el producto.';
        }
        stockActual = extra && typeof extra.stock === 'number' ? extra.stock : null;
        var txt = document.getElementById('txtStockDisponible');
        if (stockActual !== null && unidad) {
            txt.textContent = 'Disponible en planta: ' + stockActual.toFixed(2) + ' ' + unidad;
        } else {
            txt.textContent = '';
        }
    }

    if (unidadActual) {
        actualizarUnidad({ unidad: unidadActual, stock: stockActual });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.CatalogoSelector) return;

        var almWrap = document.getElementById('selector_wrap_dist_almacen_planta');
        var pdvWrap = getPdvWrap();
        var mapaPdv = null;
        var capasPdvMapa = null;

        document.getElementById('btnBuscarPdvDestino')?.addEventListener('click', function () {
            CatalogoSelector.open('dist_punto_venta');
        });

        function iconoPdvMapa(seleccionado) {
            var cls = seleccionado ? ' is-selected' : '';
            return L.divIcon({
                html: '<div class="pedido-dist-mapa-pin' + cls + '" style="background:#2563eb"><i class="fas fa-store"></i></div>',
                className: '',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
            });
        }

        function pintarListaPdvMapa() {
            var lista = document.getElementById('listaPdvMapaDestino');
            if (!lista) return;
            lista.innerHTML = '';
            puntosVentaMapa.forEach(function (pdv) {
                var chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'pdv-mapa-chip' + (String(pdvSeleccionadoId) === String(pdv.id) ? ' is-active' : '');
                chip.textContent = pdv.label;
                chip.addEventListener('click', function () {
                    aplicarPdvSeleccionado(pdv);
                    if (mapaPdv && pdv.lat && pdv.lng) {
                        mapaPdv.setView([parseFloat(pdv.lat), parseFloat(pdv.lng)], 15);
                    }
                    pintarMapaPdvDestino();
                    window.jQuery('#modalMapaPdvDestino').modal('hide');
                });
                lista.appendChild(chip);
            });
        }

        function pintarMapaPdvDestino() {
            if (!mapaPdv || !capasPdvMapa) return;
            capasPdvMapa.clearLayers();
            var bounds = [];
            var conCoords = puntosVentaMapa.filter(function (p) {
                return p.lat != null && p.lng != null && !isNaN(parseFloat(p.lat)) && !isNaN(parseFloat(p.lng));
            });

            conCoords.forEach(function (pdv) {
                var lat = parseFloat(pdv.lat);
                var lng = parseFloat(pdv.lng);
                var seleccionado = String(pdvSeleccionadoId) === String(pdv.id);
                var marker = L.marker([lat, lng], { icon: iconoPdvMapa(seleccionado) })
                    .bindTooltip(pdv.label, { direction: 'top', offset: [0, -12] })
                    .addTo(capasPdvMapa);
                marker.on('click', function () {
                    aplicarPdvSeleccionado(pdv);
                    pintarMapaPdvDestino();
                    pintarListaPdvMapa();
                    window.jQuery('#modalMapaPdvDestino').modal('hide');
                });
                bounds.push([lat, lng]);
            });

            if (bounds.length === 1) {
                mapaPdv.setView(bounds[0], 14);
            } else if (bounds.length > 1) {
                mapaPdv.fitBounds(bounds, { padding: [28, 28], maxZoom: 14 });
            }
            pintarListaPdvMapa();
        }

        function abrirMapaPdvDestino() {
            if (!puntosVentaMapa.length) return;
            window.jQuery('#modalMapaPdvDestino').modal('show');
        }

        window.jQuery('#modalMapaPdvDestino').on('shown.bs.modal', function () {
            if (!window.L) return;
            if (!mapaPdv) {
                mapaPdv = L.map('mapaPdvDestino').setView([-17.7833, -63.1821], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap',
                }).addTo(mapaPdv);
                capasPdvMapa = L.layerGroup().addTo(mapaPdv);
            }
            window.setTimeout(function () {
                mapaPdv.invalidateSize();
                pintarMapaPdvDestino();
                var sel = buscarPdvEnMapa(pdvSeleccionadoId);
                if (sel && sel.lat && sel.lng) {
                    mapaPdv.setView([parseFloat(sel.lat), parseFloat(sel.lng)], 15);
                }
            }, 180);
        });

        document.getElementById('btnVerMapaPdvDestino')?.addEventListener('click', abrirMapaPdvDestino);

        function paramsProducto() {
            var almId = document.querySelector('#selector_wrap_dist_almacen_planta .selector-catalogo-value')?.value || '';
            return {
                ambito_planta: '1',
                almacenid: almId,
            };
        }

        if (pdvWrap) {
            pdvWrap.addEventListener('selector-catalogo:change', function (e) {
                actualizarDestinoVisible(e.detail?.id, e.detail?.label);
            });
        }

        @if($esAdmin ?? false)
        CatalogoSelector.register('dist_almacen_planta', {
            endpoint: @json(route('catalogo-selector.almacenes')),
            title: 'Almacén de planta',
            searchPlaceholder: 'Nombre o ubicación…',
            params: { ambito: 'planta' },
            allowEmpty: true,
            emptyLabel: '— Automático según producto —',
            placeholderEmpty: 'Automático según producto',
            onSelect: function () {
                CatalogoSelector.instances.dist_producto_planta.params = paramsProducto();
                CatalogoSelector.clear('dist_producto_planta');
                actualizarUnidad(null);
            },
        });
        @endif

        CatalogoSelector.register('dist_producto_planta', {
            endpoint: @json(route('catalogo-selector.insumos')),
            title: 'Producto terminado (planta)',
            searchPlaceholder: 'Nombre del producto…',
            params: paramsProducto(),
            onSelect: function (item) {
                actualizarUnidad(item.extra || {});
            },
        });

        if (almWrap) {
            almWrap.addEventListener('selector-catalogo:change', function (e) {
                if (!e.detail.id) {
                    CatalogoSelector.instances.dist_producto_planta.params = paramsProducto();
                    actualizarUnidad(null);
                }
            });
        }

        document.getElementById('formPedidoDist').addEventListener('submit', function (e) {
            var pdvOk = document.querySelector('#selector_wrap_dist_punto_venta .selector-catalogo-value')?.value;
            if (!pdvOk) {
                e.preventDefault();
                alert('Seleccione un punto de venta destino.');
                return;
            }
            if (!document.querySelector('#selector_wrap_dist_producto_planta .selector-catalogo-value')?.value) {
                e.preventDefault();
                alert('Seleccione un producto de planta.');
            }
        });
    });
})();
</script>
@endpush
