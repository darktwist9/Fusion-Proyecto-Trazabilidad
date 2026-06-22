@php

    $tareasPlanta = session('operario_planta_nuevas_tareas', []);

@endphp



@if(! empty($tareasPlanta))



<div class="planta-tarea-scrim" id="plantaTareaScrim" aria-hidden="true"></div>



<div class="modal fade planta-tarea-modal-root" id="modalOperarioPlantaTarea" tabindex="-1" role="dialog" aria-labelledby="modalOperarioPlantaTareaTitulo" aria-hidden="true" data-backdrop="false" data-keyboard="true">

    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">

        <div class="modal-content border-0 shadow-lg planta-tarea-modal">

            <div class="planta-tarea-modal__accent" aria-hidden="true"></div>

            <div class="modal-header border-0 py-3 px-4 planta-tarea-modal__head">

                <div class="d-flex align-items-center flex-wrap">

                    <span class="planta-tarea-modal__badge mr-2 mb-1 mb-md-0">

                        <i class="fas fa-bell mr-1"></i> Pendiente

                    </span>

                    <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalOperarioPlantaTareaTitulo">

                        @if(count($tareasPlanta) === 1)

                            Nueva tarea de transformación

                        @else

                            {{ count($tareasPlanta) }} tareas de transformación

                        @endif

                    </h5>

                </div>

                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar" style="opacity:.85;text-shadow:none">

                    <span aria-hidden="true">&times;</span>

                </button>

            </div>

            <div class="modal-body px-4 py-4">

                <div class="planta-tarea-modal__highlight mb-3">

                    <div class="planta-tarea-modal__highlight-icon">

                        <i class="fas fa-industry"></i>

                    </div>

                    <div>

                        <strong class="d-block planta-tarea-modal__highlight-title">

                            @if(count($tareasPlanta) === 1)

                                Tiene un trabajo asignado en planta

                            @else

                                Tiene {{ count($tareasPlanta) }} trabajos asignados en planta

                            @endif

                        </strong>

                        <span class="small planta-tarea-modal__highlight-sub">

                            @if(count($tareasPlanta) === 1)

                                Abra la tarea y márquela como completada cuando termine la etapa.

                            @else

                                Elija con cuál comenzar y complete cada etapa desde su detalle.

                            @endif

                        </span>

                    </div>

                </div>

                <ul class="list-unstyled mb-0 planta-tarea-modal__lista">

                    @foreach($tareasPlanta as $row)

                    <li class="planta-tarea-modal__item">

                        <div class="planta-tarea-modal__item-info">

                            <span class="planta-tarea-modal__proceso">{{ $row['proceso'] }}</span>

                            <span class="planta-tarea-modal__meta">

                                <i class="fas fa-tools mr-1"></i>{{ $row['maquina'] }}

                                <span class="mx-1">·</span>

                                <i class="fas fa-box mr-1"></i>{{ $row['lote'] }}

                            </span>

                        </div>

                        <a href="{{ $row['url'] }}" class="btn btn-sm font-weight-bold planta-tarea-modal__cta">

                            <i class="fas fa-play mr-1"></i> Ir a la tarea

                        </a>

                    </li>

                    @endforeach

                </ul>

            </div>

            <div class="modal-footer border-0 px-4 py-3 d-flex justify-content-between planta-tarea-modal__foot">

                <a href="{{ route('tareas-planta.index') }}" class="btn btn-outline-secondary btn-sm font-weight-bold">

                    <i class="fas fa-list mr-1"></i> Mis tareas

                </a>

                <button type="button" class="btn btn-sm px-4 font-weight-bold planta-tarea-modal__btn-cerrar" data-dismiss="modal">Entendido</button>

            </div>

        </div>

    </div>

</div>



@once

@push('styles')

<style>

.planta-tarea-scrim {

    position: fixed;

    inset: 0;

    z-index: 1040;

    background: rgba(12, 28, 48, 0.88);

    backdrop-filter: blur(20px) saturate(110%);

    -webkit-backdrop-filter: blur(20px) saturate(110%);

    opacity: 0;

    visibility: hidden;

    transition: opacity .22s ease, visibility .22s ease;

}

.planta-tarea-scrim.is-visible { opacity: 1; visibility: visible; }

.planta-tarea-modal-root { z-index: 1050; }

body.planta-tarea-modal-open .wrapper,

body.planta-tarea-modal-open .main-sidebar,

body.planta-tarea-modal-open .main-header,

body.planta-tarea-modal-open .content-wrapper {

    filter: blur(5px) brightness(0.78);

    pointer-events: none;

    user-select: none;

}

.planta-tarea-modal {

    border-radius: 16px;

    overflow: hidden;

    border: 1px solid rgba(201, 162, 39, 0.35);

    position: relative;

}

.planta-tarea-modal__accent {

    height: 4px;

    background: linear-gradient(90deg, #c9a227 0%, #e8c547 50%, #c9a227 100%);

}

.planta-tarea-modal__head {

    background: linear-gradient(135deg, #0c1c30 0%, #1a3a5c 55%, #1e4d6e 100%);

    color: #fff;

}

.planta-tarea-modal__badge {

    display: inline-flex;

    align-items: center;

    padding: .25rem .65rem;

    border-radius: 999px;

    font-size: .72rem;

    font-weight: 700;

    letter-spacing: .04em;

    text-transform: uppercase;

    background: #c9a227;

    color: #1a1a1a;

}

.planta-tarea-modal__highlight {

    display: flex;

    align-items: flex-start;

    gap: .85rem;

    padding: .9rem 1rem;

    border-radius: 12px;

    background: linear-gradient(135deg, #fff9eb 0%, #fef3c7 100%);

    border: 1px solid #e8c547;

    border-left: 4px solid #c9a227;

}

.planta-tarea-modal__highlight-icon {

    width: 42px;

    height: 42px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #1a3a5c;

    color: #e8c547;

    font-size: 1.1rem;

    flex-shrink: 0;

}

.planta-tarea-modal__highlight-title {

    color: #0c1c30;

    font-size: .95rem;

}

.planta-tarea-modal__highlight-sub {

    color: #475569;

}

.planta-tarea-modal__lista {

    display: flex;

    flex-direction: column;

    gap: .7rem;

}

.planta-tarea-modal__item {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: .85rem;

    padding: .85rem 1rem;

    border-radius: 12px;

    background: #f8fafc;

    border: 1px solid #cbd5e1;

    border-left: 4px solid #1a3a5c;

}

.planta-tarea-modal__proceso {

    display: block;

    font-weight: 700;

    color: #0c1c30;

    font-size: .95rem;

}

.planta-tarea-modal__meta {

    display: block;

    font-size: .8rem;

    color: #64748b;

    margin-top: .15rem;

}

.planta-tarea-modal__cta {

    background: #1a3a5c;

    border-color: #1a3a5c;

    color: #fff;

    white-space: nowrap;

}

.planta-tarea-modal__cta:hover {

    background: #0c1c30;

    border-color: #0c1c30;

    color: #fff;

}

.planta-tarea-modal__foot {

    background: #f1f5f9;

    border-top: 1px solid #e2e8f0;

}

.planta-tarea-modal__btn-cerrar {

    background: #fff;

    border: 1px solid #cbd5e1;

    color: #334155;

}

.planta-tarea-modal__btn-cerrar:hover {

    background: #e2e8f0;

    color: #0c1c30;

}

</style>

@endpush

@endonce



@once

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    var modalEl = document.getElementById('modalOperarioPlantaTarea');

    var scrimEl = document.getElementById('plantaTareaScrim');

    if (! window.jQuery || ! modalEl || ! scrimEl) {

        return;

    }

    var $modal = window.jQuery(modalEl);

    $modal.on('show.bs.modal', function () {

        scrimEl.classList.add('is-visible');

        document.body.classList.add('modal-open', 'planta-tarea-modal-open');

    }).on('hidden.bs.modal', function () {

        scrimEl.classList.remove('is-visible');

        document.body.classList.remove('modal-open', 'planta-tarea-modal-open');

    });

    scrimEl.addEventListener('click', function () {

        $modal.modal('hide');

    });

    $modal.modal({ backdrop: false, keyboard: true, show: true });

});

</script>

@endpush

@endonce



@php session()->forget('operario_planta_nuevas_tareas'); @endphp



@endif

