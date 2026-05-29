@extends('layouts.app')

@section('title', 'Aplicar Insumo | AgroFusion')
@section('page_title', 'Aplicar Insumo')

@section('content')
<div class="card">

    <div class="card-header bg-primary text-white">
        <h3 class="card-title"><i class="fas fa-box mr-2"></i>Registrar Aplicación de Insumo</h3>
    </div>

    {{-- Mostrar errores --}}
    @if($errors->any())
        <div class="alert alert-danger m-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lote-insumos.store') }}" method="POST">
        @csrf

        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    
                    {{-- Selección de Lote --}}
                    @include('partials.selector-catalogo', [
                        'id' => 'lote_insumo_lote',
                        'name' => 'loteid',
                        'label' => 'Lote',
                        'icon' => 'fa-map-marked-alt',
                        'value' => old('loteid'),
                        'labelSelected' => $loteLabel ?? '',
                        'endpoint' => route('catalogo-selector.lotes'),
                        'title' => 'Seleccionar lote',
                        'searchPlaceholder' => 'Nombre, código o ubicación…',
                        'required' => true,
                    ])

                    {{-- Usuario Responsable (automático, solo lectura) --}}
                    <div class="form-group">
                        <label><i class="fas fa-user mr-1"></i> Usuario Responsable</label>
                        <input type="text" id="responsable_display" class="form-control bg-light" readonly 
                               placeholder="Se asigna automáticamente al seleccionar el lote">
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle"></i> El responsable se toma automáticamente del lote seleccionado
                        </small>
                    </div>

                    {{-- Selección de Insumo --}}
                    @include('partials.selector-catalogo', [
                        'id' => 'lote_insumo_insumo',
                        'name' => 'insumoid',
                        'label' => 'Insumo',
                        'icon' => 'fa-flask',
                        'value' => old('insumoid'),
                        'labelSelected' => $insumoLabel ?? '',
                        'endpoint' => route('catalogo-selector.insumos'),
                        'params' => ['solo_con_stock' => '1'],
                        'title' => 'Seleccionar insumo',
                        'searchPlaceholder' => 'Nombre del insumo…',
                        'required' => true,
                    ])

                    {{-- Cantidad --}}
                    <div class="form-group">
                        <label><i class="fas fa-balance-scale mr-1"></i> Cantidad a usar <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="cantidadusada" id="cantidadusada"
                                   class="form-control" min="0.01" required value="{{ old('cantidadusada') }}">
                            <div class="input-group-append">
                                <span class="input-group-text" id="unidad_display">ud</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Stock disponible: <span id="stock_display" class="font-weight-bold">-</span>
                        </small>
                    </div>

                    {{-- Estado --}}
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i> Estado</label>
                        <select name="estadoloteinsumoid" class="form-control">
                            @foreach($estados as $e)
                                <option value="{{ $e->estadoloteinsumoid }}" {{ $e->nombre == 'aplicado' ? 'selected' : '' }}>
                                    {{ ucfirst($e->nombre) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Observaciones --}}
                    <div class="form-group">
                        <label><i class="fas fa-comment mr-1"></i> Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="2" 
                                  maxlength="200" placeholder="Opcional...">{{ old('observaciones') }}</textarea>
                    </div>

                </div>

                {{-- Panel lateral informativo --}}
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle mr-1"></i> Información</h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted mb-2">
                                <i class="fas fa-clock mr-1"></i> <strong>Fecha:</strong> Se registra automáticamente
                            </p>
                            <p class="small text-muted mb-2">
                                <i class="fas fa-calculator mr-1"></i> <strong>Costo:</strong> Se calcula automáticamente
                            </p>
                            <hr>
                            <p class="small mb-0">
                                <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                Al guardar, se descontará la cantidad del stock del insumo.
                            </p>
                        </div>
                    </div>

                    {{-- Costo estimado --}}
                    <div class="card mt-3 border-success">
                        <div class="card-body text-center">
                            <small class="text-muted">Costo Estimado</small>
                            <h3 class="text-success mb-0" id="costo_estimado">Bs. 0.00</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <a href="{{ route('lote-insumos.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Registrar Aplicación
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let precioActual = 0;

    document.getElementById('selector_wrap_lote_insumo_lote')?.addEventListener('selector-catalogo:change', function (e) {
        const extra = e.detail.extra || {};
        $('#responsable_display').val(extra.responsable || 'Sin responsable asignado');
    });

    document.getElementById('selector_wrap_lote_insumo_insumo')?.addEventListener('selector-catalogo:change', function (e) {
        const extra = e.detail.extra || {};
        const stock = extra.stock ?? 0;
        const unidad = extra.unidad || 'ud';
        precioActual = parseFloat(extra.precio) || 0;
        $('#stock_display').text(stock + ' ' + unidad);
        $('#unidad_display').text(unidad);
        $('#cantidadusada').attr('max', stock);
        calcularCosto();
    });

    // Al cambiar cantidad, calcular costo
    $('#cantidadusada').on('input', function() {
        calcularCosto();
    });

    function calcularCosto() {
        const cantidad = parseFloat($('#cantidadusada').val()) || 0;
        const costo = cantidad * precioActual;
        $('#costo_estimado').text('Bs. ' + costo.toFixed(2));
    }
});
</script>
@endpush