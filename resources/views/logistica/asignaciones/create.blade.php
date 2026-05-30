@extends('layouts.app')

@push('styles')
@include('logistica.partials.ayuda-logistica-styles')
@endpush

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0">Asignar envíos al chofer</h1>
        <p class="text-muted mb-0">Asignación paso a paso: elija transportista, marque los pedidos pendientes y confirme. La carga al camión se registra después en el almacén.</p>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        <div class="log-pasos" id="indicador-pasos">
            <div class="log-paso activo" data-paso="1">1. Transportista y vehículo</div>
            <div class="log-paso" data-paso="2">2. Elegir envíos</div>
        </div>

        <div class="log-guia log-guia-compact">
            <strong>Importante:</strong> solo puede asignar envíos cuyo pedido fue <em>aceptado por producción agrícola</em> y tiene stock reservado en el almacén agrícola.
        </div>

        <div id="asistente-asignacion">
            <div class="card x-card mb-3" id="step-1">
                <div class="card-header bg-light">
                    <h3 class="card-title mb-0 h5">Paso 1 — ¿Quién transporta los envíos?</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Transportista <span class="text-danger">*</span></label>
                            <select id="transportista" class="form-control" required>
                                <option value="">— Elija un transportista —</option>
                                @foreach($transportistas as $t)
                                    <option value="{{ $t->usuarioid }}">{{ $t->nombre }} {{ $t->apellido }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Persona con rol transportista en el sistema.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Vehículo asignado</label>
                            <input id="vehiculo_ref" class="form-control" placeholder="Se completa al elegir transportista" maxlength="80" readonly>
                            <small class="text-muted">Placa del vehículo vinculado al transportista.</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="to-step-2">
                        Continuar <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>

            <div class="card x-card mb-3 d-none" id="step-2">
                <div class="card-header bg-light">
                    <h3 class="card-title mb-0 h5">Paso 2 — ¿Qué envíos lleva?</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Marque con la casilla cada pedido o envío que este transportista debe llevar.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:40px">Elegir</th>
                                    <th>Código de envío</th>
                                    <th>Destino</th>
                                    <th>Dirección</th>
                                    <th>Pedido agrícola</th>
                                    <th>Situación</th>
                                </tr>
                            </thead>
                            <tbody id="envios-list">
                                @forelse($enviosPendientes as $envio)
                                    @php
                                        $listo = \App\Support\PedidoCatalogo::listoParaLogistica($envio->pedido);
                                    @endphp
                                    <tr class="{{ $listo ? '' : 'text-muted bg-light' }}">
                                        <td>
                                            <input type="checkbox" class="envio-checkbox" value="{{ $envio->externo_envio_id }}"
                                                   @disabled(! $listo)
                                                   title="{{ $listo ? 'Listo para asignar' : 'Pendiente de aceptación agrícola' }}">
                                        </td>
                                        <td><strong>{{ $envio->externo_envio_id }}</strong></td>
                                        <td>{{ $envio->pedido?->nombre_planta ?? '—' }}</td>
                                        <td>{{ $envio->pedido?->direccion_texto ?? '—' }}</td>
                                        <td>
                                            @if($envio->pedido)
                                                <span class="badge {{ $listo ? 'badge-success' : 'badge-warning' }}">
                                                    {{ \App\Support\PedidoCatalogo::etiquetaEstado($envio->pedido->estado) }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $envio->estado ?? 'pendiente' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No hay envíos pendientes. Cree un pedido en <strong>Envíos → Pedidos</strong>; producción agrícola debe aceptarlo primero.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" id="back-step-1"><i class="fas fa-arrow-left mr-1"></i> Atrás</button>
                        <button type="button" class="btn btn-success btn-lg" id="submit-asignacion">
                            <i class="fas fa-check mr-1"></i> Guardar asignación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    const vehiculosPorTransportista = @json($vehiculosPorTransportista);

    function marcarPaso(actual) {
        document.querySelectorAll('#indicador-pasos .log-paso').forEach(function (el) {
            const n = parseInt(el.getAttribute('data-paso'), 10);
            el.classList.remove('activo', 'hecho');
            if (n < actual) el.classList.add('hecho');
            if (n === actual) el.classList.add('activo');
        });
    }

    document.getElementById('transportista').addEventListener('change', function () {
        const placa = vehiculosPorTransportista[this.value] || '';
        document.getElementById('vehiculo_ref').value = placa;
    });

    document.getElementById('to-step-2').addEventListener('click', function () {
        if (!document.getElementById('transportista').value) {
            alert('Por favor elija el transportista que llevará los envíos.');
            return;
        }
        document.getElementById('step-1').classList.add('d-none');
        document.getElementById('step-2').classList.remove('d-none');
        marcarPaso(2);
    });

    document.getElementById('back-step-1').addEventListener('click', function () {
        document.getElementById('step-2').classList.add('d-none');
        document.getElementById('step-1').classList.remove('d-none');
        marcarPaso(1);
    });

    document.getElementById('submit-asignacion').addEventListener('click', async function () {
        const transportista = document.getElementById('transportista').value;
        const vehiculo = document.getElementById('vehiculo_ref').value;
        const envioIds = Array.from(document.querySelectorAll('.envio-checkbox')).filter(function (c) { return c.checked; }).map(function (c) { return c.value; });

        if (!transportista) { alert('Elija un transportista.'); return; }
        if (!envioIds.length) { alert('Marque al menos un envío aceptado por producción agrícola.'); return; }

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const form = new FormData();
        envioIds.forEach(function (id) { form.append('envio_ids[]', id); });
        form.append('transportista_usuarioid', transportista);
        form.append('vehiculo_ref', vehiculo);

        const btn = document.getElementById('submit-asignacion');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando…';

        try {
            const res = await fetch('{{ route('logistica.asignaciones.store-batch') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: form
            });

            if (res.ok || res.redirected) {
                window.location.href = '{{ route('logistica.asignaciones.create') }}';
                return;
            }
            const text = await res.text();
            let msg = 'No se pudo guardar. Revise los datos e intente de nuevo.';
            try {
                const json = JSON.parse(text);
                if (json.message) msg = json.message;
            } catch (_) {}
            alert(msg);
        } catch (err) {
            alert('Error de conexión. Verifique su internet e intente otra vez.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i> Guardar asignación';
        }
    });
})();
</script>
@endpush
@endsection
