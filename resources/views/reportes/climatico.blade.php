@extends('layouts.app')

@section('title', 'Reporte Climático | AgroFusion')
@section('page_title', 'Reporte Climático')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>
    <li class="breadcrumb-item active">Climático</li>
@endsection

@push('styles')
@include('partials.modulo-reportes-styles')
@endpush

@section('content')
<div class="modulo-rep">

@include('reportes.partials.toolbar', [
    'icono' => 'fa-cloud-sun',
    'titulo' => 'Reporte climático',
    'descripcion' => 'Consulta clima actual, pronóstico y tendencias del historial registrado.',
    'tema' => 'info',
    'moduloRuta' => route('climas.index'),
    'moduloLabel' => 'Módulo clima',
    'moduloIcono' => 'fa-cloud',
])
@include('reportes.partials.filtros-climatico')

    <div class="row">
        <div class="col-lg-4">
            <div class="weather-panel mb-3">
                <img src="https://openweathermap.org/img/wn/{{ $climaActual['icono'] ?? '01d' }}@2x.png" alt="" style="width: 80px;">
                <div class="temp-main">{{ $climaActual['temperatura'] ?? 0 }}°C</div>
                <div class="text-capitalize">{{ $climaActual['descripcion'] ?? 'Sin datos' }}</div>
                <small><i class="fas fa-map-marker-alt mr-1"></i>{{ $climaActual['ubicacion'] ?? 'Santa Cruz, Bolivia' }}</small>
                <div class="weather-detail-grid">
                    <div class="item"><i class="fas fa-tint"></i><div>{{ $climaActual['humedad'] ?? 0 }}%</div><small>Humedad</small></div>
                    <div class="item"><i class="fas fa-wind"></i><div>{{ $climaActual['viento'] ?? 0 }} km/h</div><small>Viento</small></div>
                    <div class="item"><i class="fas fa-thermometer-half"></i><div>{{ $climaActual['sensacion'] ?? 0 }}°C</div><small>Sensación</small></div>
                    <div class="item"><i class="fas fa-compress-arrows-alt"></i><div>{{ $climaActual['presion'] ?? 0 }}</div><small>Presión</small></div>
                </div>
            </div>

            <div class="card card-info card-outline elevation-2">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Pronóstico</h3></div>
                <div class="card-body">
                    <div class="row">
                        @forelse($pronostico as $item)
                            <div class="col-6 mb-2">
                                <div class="forecast-mini">
                                    <div class="font-weight-bold">{{ $item['dia'] ?? '-' }}</div>
                                    <img src="https://openweathermap.org/img/wn/{{ $item['icono'] ?? '01d' }}.png" style="width: 42px;" alt="">
                                    <div class="text-info font-weight-bold">{{ $item['temp_max'] ?? 0 }}° / {{ $item['temp_min'] ?? 0 }}°</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center text-muted">Sin pronóstico disponible.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-6 col-lg-4">
                    <div class="small-box small-box-yellow">
                        <div class="inner"><h3>{{ number_format($promedios['temperatura'], 1) }}°C</h3><p>Temp. promedio</p></div>
                        <div class="icon"><i class="fas fa-thermometer-half"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="small-box small-box-blue">
                        <div class="inner"><h3>{{ number_format($promedios['humedad'], 1) }}%</h3><p>Humedad promedio</p></div>
                        <div class="icon"><i class="fas fa-tint"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="small-box small-box-green">
                        <div class="inner"><h3>{{ $historialClima->count() }}</h3><p>Registros ({{ $dias }} días)</p></div>
                        <div class="icon"><i class="fas fa-history"></i></div>
                    </div>
                </div>
            </div>

            <div class="card card-info card-outline elevation-2 mb-3">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-line mr-1"></i> Tendencias climáticas</h3></div>
                <div class="card-body">
                    @if($datosGrafico->count() > 0)
                        <div class="chart-wrap"><canvas id="climaChart"></canvas></div>
                    @else
                        <div class="text-center py-5 text-muted"><i class="fas fa-chart-line fa-3x mb-2 text-light"></i><p>No hay datos para el gráfico.</p></div>
                    @endif
                </div>
            </div>

            <div class="card card-success card-outline elevation-2">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Historial de clima</h3>
                    <span class="badge badge-success">{{ $historialClima->count() }}</span>
                </div>
                <div class="card-body p-0" style="max-height: 340px; overflow-y: auto;">
                    @forelse($historialClima as $registro)
                        <div class="d-flex align-items-center justify-content-between border-bottom p-3">
                            <div>
                                <strong>{{ $registro->fecha->format('d/m/Y') }}</strong>
                                <div class="text-muted text-capitalize small">{{ $registro->observaciones ?? 'Sin descripción' }}</div>
                            </div>
                            <div>
                                <span class="badge badge-warning">{{ $registro->temperatura }}°C</span>
                                <span class="badge badge-info">{{ $registro->humedad }}%</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">No hay registros en el período.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        $(function () {
            @if($datosGrafico->count() > 0)
                const datos = @json($datosGrafico);
                new Chart(document.getElementById('climaChart'), {
                    type: 'line',
                    data: {
                        labels: datos.map(d => d.dia),
                        datasets: [{
                            label: 'Temperatura (°C)',
                            data: datos.map(d => parseFloat(d.temp)),
                            borderColor: '#fd7e14',
                            backgroundColor: 'rgba(253, 126, 20, 0.10)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            yAxisID: 'y'
                        }, {
                            label: 'Humedad (%)',
                            data: datos.map(d => parseFloat(d.hum)),
                            borderColor: '#17a2b8',
                            backgroundColor: 'rgba(23, 162, 184, 0.10)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        scales: {
                            y: { type: 'linear', position: 'left', title: { display: true, text: 'Temperatura (°C)' } },
                            y1: { type: 'linear', position: 'right', title: { display: true, text: 'Humedad (%)' }, grid: { drawOnChartArea: false } }
                        }
                    }
                });
            @endif
        });
    </script>
@endpush