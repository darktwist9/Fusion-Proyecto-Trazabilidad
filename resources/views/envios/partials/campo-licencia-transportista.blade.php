@php
    $t = $transportista ?? null;
    $tipoLicencia = old('tipo_licencia', $t?->tipo_licencia ?? $t?->perfilTransportista?->tipo_licencia ?? '');
    $numLicencia = old('licencia', $t?->perfilTransportista?->licencia ?? '');
@endphp
<div class="col-md-3">
    <div class="form-group">
        <label>Tipo de licencia <span class="text-danger">*</span></label>
        <select name="tipo_licencia" class="form-control" required>
            <option value="">— Seleccionar —</option>
            @foreach(\App\Support\TiposLicenciaBolivia::todos() as $codigo => $descripcion)
            <option value="{{ $codigo }}" @selected($tipoLicencia === $codigo)>{{ $codigo }} — {{ $descripcion }}</option>
            @endforeach
        </select>
        <small class="text-muted">Clasificación boliviana (M, P, A, B, C, T).</small>
    </div>
</div>
<div class="col-md-3">
    <div class="form-group">
        <label>Nº de licencia</label>
        <input name="licencia" class="form-control text-uppercase" value="{{ $numLicencia }}"
               placeholder="Ej. B-4521987" maxlength="50">
        <small class="text-muted">Número del documento de licencia.</small>
    </div>
</div>
