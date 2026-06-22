@extends('layouts.app')

@section('title', 'Inicio | AgroFusion')
@section('page_title', 'Inicio')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Inicio</li>
@endsection

@push('styles')
@include('dashboard.partials.inicio-estilos')
<style>
.inicio-transportista {
    --inicio-border: rgba(234, 88, 12, .18);
    --inicio-hero-bg: linear-gradient(135deg, #fff7ed 0%, #ffedd5 42%, #f8fafc 100%);
    --inicio-title: #9a3412;
    --inicio-icon-bg: linear-gradient(135deg, #ea580c, #f59e0b);
}
</style>
@endpush

@section('content')
<section class="content px-0 inicio-transportista">
    <div class="container-fluid px-0">

        <div class="inicio-dash-hero">
            <div class="inicio-dash-hero__row">
                <div>
                    <div class="inicio-dash-hero__title"><i class="fas fa-chart-line"></i>Tu desempeño en ruta</div>
                    <p class="inicio-dash-hero__sub">Hola, <strong>{{ auth()->user()->nombre }}</strong> · Entregas y productividad · {{ $filtros->etiquetaPeriodo() }}</p>
                </div>
                <a href="{{ route('dashboard.panel-transportista') }}" class="inicio-dash-link-panel">
                    <i class="fas fa-truck-moving"></i> Ir al panel operativo
                </a>
            </div>
        </div>

        @include('partials.dashboard-alertas')

        @if(($envios_pendientes_accion ?? collect())->isNotEmpty())
            @php $envioAsignado = $envios_pendientes_accion->first(); @endphp
            <div class="alert alert-success border-success shadow-sm mb-3" role="alert">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <i class="fas fa-truck-loading mr-1"></i>
                        <strong>Tiene {{ $envios_pendientes_accion->count() }} envío(s) asignado(s) listo(s) para recoger.</strong>
                        @if($envioAsignado)
                            <span class="d-block small mt-1">
                                Próximo: <strong>{{ $envioAsignado->externo_envio_id ?? $envioAsignado->pedido?->numero_solicitud }}</strong>
                                — {{ $envioAsignado->pedido?->detalles?->first()?->cultivo_personalizado ?? 'Producto agrícola' }}
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('logistica.asignaciones.show', $envioAsignado) }}" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-clipboard-check mr-1"></i> Ir al envío asignado
                    </a>
                </div>
            </div>
        @endif

        @if(($rutas_pendientes_salida ?? collect())->isNotEmpty())
            @php $rutaPendiente = $rutas_pendientes_salida->first(); @endphp
            <div class="alert alert-warning border-warning shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3" role="alert">
                <div>
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    <strong>Tiene {{ $rutas_pendientes_salida->count() }} envío(s) pendiente(s) de salida.</strong>
                    @if($rutaPendiente)
                        <span class="d-block small mt-1">Próximo: <strong>{{ $rutaPendiente->codigo }}</strong> — debe aceptar la solicitud e iniciar el cierre operativo.</span>
                    @endif
                </div>
                <a href="{{ \App\Support\RutaDistribucionNavegacion::urlVer($rutaPendiente) }}" class="btn btn-warning btn-sm font-weight-bold">
                    <i class="fas fa-shipping-fast mr-1"></i> Ir al envío pendiente
                </a>
            </div>
        @endif

        @include('dashboard.partials.filtros', [
            'filtros' => $filtros,
            'actionUrl' => route('dashboard'),
        ])

        <div class="inicio-kpi-row">
            <div class="inicio-kpi" style="background:linear-gradient(135deg,#15803d,#22c55e)">
                <i class="fas fa-clipboard-check inicio-kpi__icon"></i>
                <div class="inicio-kpi__val">{{ $stats['asignados'] }}</div>
                <p class="inicio-kpi__lbl">Asignados</p>
            </div>
            <div class="inicio-kpi" style="background:linear-gradient(135deg,#c2410c,#f59e0b)">
                <i class="fas fa-box inicio-kpi__icon"></i>
                <div class="inicio-kpi__val">{{ $stats['por_recoger'] }}</div>
                <p class="inicio-kpi__lbl">Por recoger</p>
            </div>
            <div class="inicio-kpi" style="background:linear-gradient(135deg,#0369a1,#0ea5e9)">
                <i class="fas fa-shipping-fast inicio-kpi__icon"></i>
                <div class="inicio-kpi__val">{{ $stats['en_camino'] }}</div>
                <p class="inicio-kpi__lbl">En camino</p>
            </div>
            <div class="inicio-kpi" style="background:linear-gradient(135deg,#7c3aed,#a855f7)">
                <i class="fas fa-tachometer-alt inicio-kpi__icon"></i>
                <div class="inicio-kpi__val">{{ $stats['productividad'] }}%</div>
                <p class="inicio-kpi__lbl">Productividad</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="inicio-chart-card">
                    <div class="inicio-chart-card__head">
                        <h3><i class="fas fa-chart-bar text-warning mr-2"></i>Envíos vs entregas · {{ $etiquetaGrafico }}</h3>
                    </div>
                    <div class="inicio-chart-wrap"><canvas id="chartTransBarras"></canvas></div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="inicio-chart-card">
                    <div class="inicio-chart-card__head">
                        <h3><i class="fas fa-chart-pie text-success mr-2"></i>Estado actual</h3>
                    </div>
                    <div class="inicio-chart-wrap inicio-chart-wrap--sm"><canvas id="chartTransEstados"></canvas></div>
                </div>
            </div>
        </div>

        <div class="inicio-chart-card mb-0">
            <div class="inicio-chart-card__head">
                <h3><i class="fas fa-percentage text-purple mr-2"></i>Tasa de entrega mensual (%)</h3>
            </div>
            <div class="inicio-chart-wrap inicio-chart-wrap--sm"><canvas id="chartTransProductividad"></canvas></div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var c = @json($charts);
    var grid = { color: '#f1f5f9' };

    new Chart(document.getElementById('chartTransBarras'), {
        type: 'bar',
        data: {
            labels: c.asignacionesMes.labels,
            datasets: [
                { label: c.asignacionesMes.label, data: c.asignacionesMes.data, backgroundColor: '#f59e0bcc', borderRadius: 6 },
                { label: c.entregasMes.label, data: c.entregasMes.data, backgroundColor: '#22c55ecc', borderRadius: 6 },
            ],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, grid: grid }, x: { grid: grid } } },
    });

    if (c.estadosAsignacion.labels.length) {
        new Chart(document.getElementById('chartTransEstados'), {
            type: 'doughnut',
            data: { labels: c.estadosAsignacion.labels, datasets: [{ data: c.estadosAsignacion.data, backgroundColor: c.estadosAsignacion.colors, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
    }

    new Chart(document.getElementById('chartTransProductividad'), {
        type: 'line',
        data: {
            labels: c.productividadMes.labels,
            datasets: [{ label: c.productividadMes.label, data: c.productividadMes.data, borderColor: '#7c3aed', backgroundColor: '#7c3aed22', fill: true, tension: .35 }],
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100, grid: grid }, x: { grid: grid } } },
    });
});
</script>
@endpush
