@extends('layouts.app')



@section('title', 'Traslados planta → mayorista')

@section('page_title', $esVistaMayorista ?? false ? 'Recepciones de planta' : 'Traslados planta → mayorista')



@push('styles')

@include('partials.logistica-modulo-styles')

@if($esVistaMayorista ?? false)

<style>

.may-rec-filtros { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }

.may-rec-filtros .btn { border-radius: 999px; font-size: .8rem; font-weight: 600; }

.may-rec-filtros .badge { font-size: .72rem; vertical-align: middle; }

</style>

@endif

@endpush



@section('content')

<div class="modulo-inv">

    @if(($pendientesCount ?? 0) > 0 && ! ($esVistaMayorista ?? false))

        <div class="alert alert-warning">

            <i class="fas fa-bell mr-1"></i>

            Tiene <strong>{{ $pendientesCount }}</strong> traslado(s) pendiente(s) de aprobación del jefe de planta.

        </div>

    @endif



    @if(($esVistaMayorista ?? false) && ($conteosRecepcion['esperando_firma'] ?? 0) > 0)

        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2">

            <span>

                <i class="fas fa-file-signature mr-1"></i>

                Tiene <strong>{{ $conteosRecepcion['esperando_firma'] }}</strong> traslado(s) esperando su firma de recepción.

            </span>

            <a href="{{ route('almacen-mayorista.traslados-planta.index', ['filtro' => 'esperando_firma']) }}"

               class="btn btn-warning btn-sm font-weight-bold">

                Firmar ahora

            </a>

        </div>

    @endif



    @if($esVistaMayorista ?? false)

    <div class="may-rec-filtros">

        @php

            $filtros = [

                'todos' => ['Todos', $conteosRecepcion['activos'] ?? null],

                'en_camino' => ['En camino', $conteosRecepcion['en_camino'] ?? 0],

                'esperando_firma' => ['Esperando mi firma', $conteosRecepcion['esperando_firma'] ?? 0],

                'recibidos' => ['Recibidos', $conteosRecepcion['recibidos'] ?? 0],

            ];

            $filtroActivo = $filtroRecepcion ?? 'todos';

        @endphp

        @foreach($filtros as $key => [$label, $count])

            <a href="{{ route('almacen-mayorista.traslados-planta.index', ['filtro' => $key]) }}"

               class="btn btn-sm {{ $filtroActivo === $key ? 'btn-success' : 'btn-outline-secondary' }}">

                {{ $label }}

                @if($count !== null && $key !== 'todos')

                    <span class="badge badge-light text-dark ml-1">{{ $count }}</span>

                @endif

            </a>

        @endforeach

    </div>

    @endif



    <div class="card card-outline card-success card-modulo-main elevation-1">

        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">

            <h3 class="card-title mb-0">

                <i class="fas fa-truck-loading mr-2"></i>

                {{ ($esVistaMayorista ?? false) ? 'Envíos desde planta' : 'Traslados planta → mayorista' }}

            </h3>

            @if(! ($esVistaMayorista ?? false))

                @can('asignaciones.create')

                <a href="{{ route('pedidos.create', ['destino' => 'mayorista']) }}" class="btn btn-success btn-sm">

                    <i class="fas fa-plus mr-1"></i> Nuevo traslado

                </a>

                @endcan

            @endif

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover mb-0">

                    <thead class="thead-light">

                        <tr>

                            <th>Código</th>

                            <th>Origen (planta)</th>

                            <th>Destino (mayorista)</th>

                            <th>{{ ($esVistaMayorista ?? false) ? 'Estado recepción' : 'Estado' }}</th>

                            <th>Productos</th>

                            <th class="text-right">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($traslados as $t)

                            @php

                                $badge = \App\Support\RutaDistribucionCatalogo::badgeEstado($t);

                                $estRec = ($estadosRecepcion ?? [])[$t->rutadistribucionid] ?? null;

                            @endphp

                            <tr>

                                <td class="font-weight-bold text-nowrap">{{ $t->codigo }}</td>

                                <td>{{ $t->almacenPlantaOrigen?->nombre ?? '—' }}</td>

                                <td>{{ $t->almacenMayoristaDestino?->nombre ?? '—' }}</td>

                                <td>

                                    @if($estRec)

                                        <span class="badge badge-{{ $estRec['clase'] }}">{{ $estRec['etiqueta'] }}</span>

                                    @else

                                        <span class="badge badge-{{ $badge['clase'] }}">{{ $badge['etiqueta'] }}</span>

                                    @endif

                                </td>

                                <td>{{ $t->detallesTraslado->count() }}</td>

                                <td class="text-right text-nowrap">

                                    @if($estRec['puede_firmar'] ?? false)

                                        <a href="{{ $estRec['url_cierre'] }}" class="btn btn-sm btn-warning font-weight-bold mr-1">

                                            <i class="fas fa-file-signature"></i> Firmar

                                        </a>

                                    @endif

                                    <a href="{{ route(($rutaPrefijo ?? 'logistica.traslados-planta').'.show', $t) }}" class="btn btn-sm btn-outline-primary">

                                        <i class="fas fa-eye"></i> Ver

                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center text-muted py-4">No hay traslados registrados.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        @if($traslados->hasPages())

            <div class="card-footer">{{ $traslados->links() }}</div>

        @endif

    </div>

</div>

@endsection

