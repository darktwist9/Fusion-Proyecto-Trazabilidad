@once
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
#mapaRutaEntrega { height: 420px; width: 100%; border-radius: 12px; border: 2px solid #e2e8f0; }
.ruta-mapa-leyenda { font-size: .8rem; color: #64748b; }
</style>
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/ruta-por-calles.js') }}"></script>
@endpush
@endonce
