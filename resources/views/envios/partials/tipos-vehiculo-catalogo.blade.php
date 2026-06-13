@php
    $tiposLista = $tipos ?? collect();
@endphp
@if($tiposLista->isNotEmpty())
<div class="card card-outline card-secondary elevation-1 mt-3 tipos-vehiculo-catalogo">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-secondary">
            <i class="fas fa-book mr-1"></i> Catálogo de tipos de vehículo
        </h6>
        <span class="badge badge-light border">{{ $tiposLista->count() }} tipos</span>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Tipo</th>
                    <th>Tamaño</th>
                    <th class="text-right">Peso máx.</th>
                    <th class="text-right">Volumen máx.</th>
                    <th>Licencia mínima</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tiposLista as $tipo)
                <tr>
                    <td class="font-weight-bold">{{ $tipo->nombre }}</td>
                    <td>
                        @if($tipo->tamano)
                            <span class="badge badge-light border">
                                {{ \App\Support\VehiculoTamanoCatalogo::etiqueta($tipo->tamano) }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right">{{ $tipo->capacidad_kg ? number_format((float) $tipo->capacidad_kg, 0).' kg' : '—' }}</td>
                    <td class="text-right">{{ $tipo->capacidad_m3 ? number_format((float) $tipo->capacidad_m3, 1).' m³' : '—' }}</td>
                    <td>
                        @if($tipo->licencia_requerida)
                            {{ \App\Support\TiposLicenciaBolivia::etiqueta($tipo->licencia_requerida) }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer py-2 small text-muted">
        El <strong>tamaño</strong> (pequeño, mediano, grande, extra grande) define la categoría logística del tipo.
        Cada unidad hereda capacidad y licencia del tipo salvo que indique un ajuste propio.
    </div>
</div>
@endif
