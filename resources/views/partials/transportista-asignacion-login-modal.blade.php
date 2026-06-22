@php

    $asignaciones = session('transportista_nuevas_asignaciones', []);

@endphp

@if(! empty($asignaciones))

<div class="transp-asign-scrim" id="transpAsignScrim" aria-hidden="true"></div>

<div class="modal fade transp-asign-modal-root" id="modalTransportistaAsignacion" tabindex="-1" role="dialog" aria-labelledby="modalTransportistaAsignacionTitulo" aria-hidden="true" data-backdrop="false" data-keyboard="true">

    <div class="modal-dialog modal-dialog-centered" role="document">

        <div class="modal-content border-0 shadow-lg transp-asign-modal">

            <div class="modal-header border-0 py-3 px-4 transp-asign-modal__head">

                <h5 class="modal-title font-weight-bold mb-0" id="modalTransportistaAsignacionTitulo">

                    <i class="fas fa-truck mr-2"></i>

                    @if(count($asignaciones) === 1)

                        Nuevo envío asignado

                    @else

                        {{ count($asignaciones) }} envíos asignados

                    @endif

                </h5>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="opacity:.9">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body px-4 py-3">

                <p class="text-muted small mb-3">

                    @if(count($asignaciones) === 1)

                        Se le asignó un envío. Puede abrirlo directamente desde aquí.

                    @else

                        Se le asignaron varios envíos. Elija cuál revisar primero.

                    @endif

                </p>

                <ul class="list-unstyled mb-0 transp-asign-modal__lista">

                    @foreach($asignaciones as $row)

                    <li class="transp-asign-modal__item">

                        <div class="transp-asign-modal__item-info">

                            <strong class="transp-asign-modal__codigo">{{ $row['codigo'] }}</strong>

                            <span class="text-muted small d-block">{{ $row['producto'] }}</span>

                        </div>

                        <a href="{{ $row['url'] }}" class="btn btn-success btn-sm font-weight-bold">

                            <i class="fas fa-arrow-right mr-1"></i> Ver envío

                        </a>

                    </li>

                    @endforeach

                </ul>

            </div>

            <div class="modal-footer border-0 bg-light px-4 py-3 d-flex justify-content-between">

                <a href="{{ route('logistica.asignaciones.listado') }}" class="btn btn-outline-secondary btn-sm">

                    <i class="fas fa-list mr-1"></i> Mis envíos

                </a>

                <button type="button" class="btn btn-light btn-sm px-4" data-dismiss="modal">Entendido</button>

            </div>

        </div>

    </div>

</div>

@once

@push('styles')

<style>

.transp-asign-scrim {

    position: fixed;

    inset: 0;

    z-index: 1040;

    background: rgba(15, 23, 42, 0.78);

    backdrop-filter: blur(18px);

    -webkit-backdrop-filter: blur(18px);

    opacity: 0;

    visibility: hidden;

    transition: opacity .2s ease, visibility .2s ease;

}

.transp-asign-scrim.is-visible {

    opacity: 1;

    visibility: visible;

}

.transp-asign-modal-root {

    z-index: 1050;

}

body.transp-asign-modal-open .wrapper > .content-wrapper,
body.transp-asign-modal-open .main-sidebar,
body.transp-asign-modal-open .main-header {

    filter: blur(2px);

    pointer-events: none;

    user-select: none;

}

.transp-asign-modal { border-radius: 14px; overflow: hidden; }

.transp-asign-modal__head {

    background: linear-gradient(135deg, #1e4620, #2c5530);

    color: #fff;

}

.transp-asign-modal__lista { display: flex; flex-direction: column; gap: .65rem; }

.transp-asign-modal__item {

    display: flex; align-items: center; justify-content: space-between; gap: .75rem;

    padding: .75rem .85rem; border-radius: 10px;

    background: #f8fafc; border: 1px solid #e2e8f0;

}

.transp-asign-modal__codigo {

    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;

    color: #2c5530;

}

</style>

@endpush

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    var modalEl = document.getElementById('modalTransportistaAsignacion');

    var scrimEl = document.getElementById('transpAsignScrim');

    if (! window.jQuery || ! modalEl || ! scrimEl) {

        return;

    }

    var $modal = window.jQuery(modalEl);

    $modal.on('show.bs.modal', function () {

        scrimEl.classList.add('is-visible');

        document.body.classList.add('modal-open', 'transp-asign-modal-open');

    }).on('hidden.bs.modal', function () {

        scrimEl.classList.remove('is-visible');

        document.body.classList.remove('modal-open', 'transp-asign-modal-open');

    });

    scrimEl.addEventListener('click', function () {

        $modal.modal('hide');

    });

    $modal.modal({ backdrop: false, keyboard: true, show: true });

});

</script>

@endpush

@endonce

@php session()->forget('transportista_nuevas_asignaciones'); @endphp

@endif

