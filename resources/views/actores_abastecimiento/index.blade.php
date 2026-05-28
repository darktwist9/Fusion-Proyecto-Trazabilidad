@extends('layouts.app')

@section('title', 'Actores de abastecimiento')

@section('content')
@push('styles')
<style>
.x-card{border:0;border-radius:14px;box-shadow:0 8px 24px rgba(18,38,63,.08)}
.kpi{border-radius:12px;color:#fff;padding:16px 18px}
.kpi h4{margin:0;font-weight:700}
.kpi p{margin:2px 0 0;opacity:.9}
.kpi.c1{background:linear-gradient(135deg,#2c5530,#4a7c59)}
.kpi.c2{background:linear-gradient(135deg,#1565c0,#42a5f5)}
.kpi.c3{background:linear-gradient(135deg,#6f42c1,#8e64e8)}
.chip{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
.chip.ok{background:#e8f5e9;color:#2e7d32}
.chip.off{background:#ffebee;color:#c62828}
</style>
@endpush

@php
    $total = $actores->total();
    $activos = $actores->where('activo', true)->count();
    $tipos = $actores->pluck('tipo_actor')->filter()->unique()->sort()->values();
@endphp

@if($errors->any())
    <div class="alert alert-danger x-card">
        <strong>No se pudo guardar el actor.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="alert alert-info x-card">
    <strong>¿Para qué sirve?</strong> Centraliza productores/proveedores en una sola ficha para evitar duplicados y mantener trazabilidad de abastecimiento.
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-2"><div class="kpi c1"><h4>{{ $total }}</h4><p>Actores registrados</p></div></div>
    <div class="col-md-4 mb-2"><div class="kpi c2"><h4>{{ $activos }}</h4><p>Actores activos</p></div></div>
    <div class="col-md-4 mb-2"><div class="kpi c3"><h4>{{ $tipos->count() }}</h4><p>Tipos de actor</p></div></div>
</div>

<div class="card x-card mb-3">
    <div class="card-header"><strong>Nuevo actor de abastecimiento</strong></div>
    <div class="card-body">
        <form method="POST" action="{{ route('actores-abastecimiento.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-4 mb-2">
                    <input name="nombre" class="form-control" placeholder="Nombre o razón social" value="{{ old('nombre') }}" required>
                    <small class="text-muted">Nombre comercial del productor/proveedor.</small>
                </div>
                <div class="form-group col-md-2 mb-2">
                    <select name="tipo_actor" class="form-control" required>
                        <option value="productor" {{ old('tipo_actor') === 'productor' ? 'selected' : '' }}>Productor</option>
                        <option value="proveedor" {{ old('tipo_actor') === 'proveedor' ? 'selected' : '' }}>Proveedor</option>
                        <option value="mixto" {{ old('tipo_actor') === 'mixto' ? 'selected' : '' }}>Mixto</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-2"><input name="telefono" class="form-control" placeholder="Teléfono" value="{{ old('telefono') }}"></div>
                <div class="form-group col-md-3 mb-2"><input name="email" type="email" class="form-control" placeholder="Email" value="{{ old('email') }}"></div>
                <div class="form-group col-md-1 mb-2"><button class="btn btn-primary btn-block">Crear</button></div>
            </div>
        </form>
    </div>
</div>

<div class="card x-card">
    <div class="card-header"><strong>Actores consolidados</strong></div>
    <div class="card-body border-bottom pb-2">
        <div class="form-row">
            <div class="form-group col-md-5">
                <input type="text" id="actorSearch" class="form-control form-control-sm" placeholder="Buscar por nombre, teléfono o email...">
            </div>
            <div class="form-group col-md-3">
                <select id="actorTipo" class="form-control form-control-sm">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $tipo)
                        <option value="{{ strtolower($tipo) }}">{{ ucfirst($tipo) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <select id="actorEstado" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($actores as $actor)
                    <tr class="actor-row"
                        data-id="{{ $actor->actorid }}"
                        data-search="{{ strtolower($actor->nombre.' '.($actor->telefono ?? '').' '.($actor->email ?? '')) }}"
                        data-tipo="{{ strtolower($actor->tipo_actor) }}"
                        data-estado="{{ $actor->activo ? 'activo' : 'inactivo' }}"
                        data-nombre="{{ $actor->nombre }}"
                        data-telefono="{{ $actor->telefono }}"
                        data-email="{{ $actor->email }}"
                        data-tipo-texto="{{ ucfirst($actor->tipo_actor) }}"
                        data-activo="{{ $actor->activo ? '1' : '0' }}">
                        <td>{{ $actor->nombre }}</td>
                        <td>{{ ucfirst($actor->tipo_actor) }}</td>
                        <td>{{ $actor->telefono ?? '-' }}<br>{{ $actor->email ?? '' }}</td>
                        <td>
                            <span class="chip {{ $actor->activo ? 'ok' : 'off' }}">
                                {{ $actor->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <button type="button" class="btn btn-sm btn-info text-white actor-detalle-btn" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-warning text-white actor-editar-btn" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('actores-abastecimiento.destroy', $actor) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar actor?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No hay actores registrados. Crea el primero con el formulario superior.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $actores->links() }}</div>
</div>

<div class="modal fade" id="actorDetalleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del actor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><strong>Nombre:</strong> <span id="dNombre">-</span></p>
                <p class="mb-2"><strong>Tipo:</strong> <span id="dTipo">-</span></p>
                <p class="mb-2"><strong>Teléfono:</strong> <span id="dTelefono">-</span></p>
                <p class="mb-0"><strong>Email:</strong> <span id="dEmail">-</span></p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actorEditarModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form method="POST" id="actorEditarForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Editar actor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="eNombre" required maxlength="120">
                    </div>
                    <div class="form-group">
                        <label>Tipo</label>
                        <select class="form-control" name="tipo_actor" id="eTipo" required>
                            <option value="productor">Productor</option>
                            <option value="proveedor">Proveedor</option>
                            <option value="mixto">Mixto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" class="form-control" name="telefono" id="eTelefono" maxlength="30">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="eEmail" maxlength="120">
                    </div>
                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="eActivo" name="activo" value="1">
                            <label class="custom-control-label" for="eActivo">Activo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const q = document.getElementById('actorSearch');
    const t = document.getElementById('actorTipo');
    const e = document.getElementById('actorEstado');
    const rows = Array.from(document.querySelectorAll('.actor-row'));
    const editForm = document.getElementById('actorEditarForm');
    const updateUrlTemplate = @json(route('actores-abastecimiento.update', ['actores_abastecimiento' => '__ID__']));

    function filtrar() {
        const vq = (q?.value || '').toLowerCase().trim();
        const vt = (t?.value || '').toLowerCase();
        const ve = (e?.value || '').toLowerCase();
        rows.forEach((tr) => {
            const okQ = !vq || (tr.dataset.search || '').includes(vq);
            const okT = !vt || (tr.dataset.tipo || '') === vt;
            const okE = !ve || (tr.dataset.estado || '') === ve;
            tr.style.display = (okQ && okT && okE) ? '' : 'none';
        });
    }

    [q, t, e].forEach((el) => el && el.addEventListener('input', filtrar));
    [t, e].forEach((el) => el && el.addEventListener('change', filtrar));

    document.querySelectorAll('.actor-detalle-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            const tr = this.closest('tr');
            if (!tr) return;
            document.getElementById('dNombre').textContent = tr.dataset.nombre || '-';
            document.getElementById('dTipo').textContent = tr.dataset.tipoTexto || '-';
            document.getElementById('dTelefono').textContent = tr.dataset.telefono || '-';
            document.getElementById('dEmail').textContent = tr.dataset.email || '-';
            $('#actorDetalleModal').modal('show');
        });
    });

    document.querySelectorAll('.actor-editar-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            const tr = this.closest('tr');
            if (!tr || !editForm) return;

            const actorId = tr.dataset.id || '';
            editForm.action = updateUrlTemplate.replace('__ID__', actorId);
            document.getElementById('eNombre').value = tr.dataset.nombre || '';
            document.getElementById('eTipo').value = (tr.dataset.tipo || 'productor');
            document.getElementById('eTelefono').value = tr.dataset.telefono || '';
            document.getElementById('eEmail').value = tr.dataset.email || '';
            document.getElementById('eActivo').checked = (tr.dataset.activo || '0') === '1';

            $('#actorEditarModal').modal('show');
        });
    });
});
</script>
@endpush

