@extends('layouts.app')

@section('title', 'Centro de Reportes | AgroFusion')
@section('page_title', 'Centro de Reportes')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Reportes</li>
@endsection

@push('styles')
@include('partials.modulo-reportes-styles')
@endpush

@section('content')
<div class="modulo-rep">

    <div class="rep-page-header rep-theme-brand mb-4">
        <div class="rep-page-header-inner">
            <div class="rep-page-header-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div class="rep-page-header-text">
                <h2>Centro de reportes</h2>
                <p>Consulta KPIs, gráficos y tablas filtrables. Los datos se registran en Ventas, Producción, Inventario y Actividades.</p>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-lg-3 col-6">
            <div class="small-box small-box-green">
                <div class="inner">
                    <h3>Bs. {{ number_format($stats['ventas_mes'] ?? 0, 0) }}</h3>
                    <p>Ventas del mes</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box small-box-orange">
                <div class="inner">
                    <h3>{{ number_format($stats['produccion_mes'] ?? 0, 0) }}</h3>
                    <p>Producción del mes (kg)</p>
                </div>
                <div class="icon"><i class="fas fa-seedling"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box small-box-red">
                <div class="inner">
                    <h3>{{ $stats['insumos_criticos'] ?? 0 }}</h3>
                    <p>Insumos críticos</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box small-box-purple">
                <div class="inner">
                    <h3>{{ $stats['actividades_pendientes'] ?? 0 }}</h3>
                    <p>Actividades pendientes</p>
                </div>
                <div class="icon"><i class="fas fa-tasks"></i></div>
            </div>
        </div>
    </div>

    @php
        $reportes = [
            ['title' => 'Ventas', 'desc' => 'Ingresos, clientes y tendencias', 'route' => 'reportes.ventas', 'bg' => 'bg-rep-ventas', 'icon' => 'fa-dollar-sign'],
            ['title' => 'Inventario', 'desc' => 'Stock, alertas y valorización', 'route' => 'reportes.inventario', 'bg' => 'bg-rep-inventario', 'icon' => 'fa-boxes'],
            ['title' => 'Producción', 'desc' => 'Cosechas y rendimiento por lote', 'route' => 'reportes.produccion', 'bg' => 'bg-rep-produccion', 'icon' => 'fa-tractor'],
            ['title' => 'Climático', 'desc' => 'Clima actual, pronóstico e historial', 'route' => 'reportes.climatico', 'bg' => 'bg-rep-climatico', 'icon' => 'fa-cloud-sun'],
            ['title' => 'Actividades', 'desc' => 'Tareas agrícolas y pendientes', 'route' => 'reportes.actividades', 'bg' => 'bg-rep-actividades', 'icon' => 'fa-clipboard-list'],
        ];
    @endphp

    <h4 class="section-title"><i class="fas fa-folder-open"></i>Reportes disponibles</h4>
    <div class="row mb-4">
        @foreach($reportes as $rep)
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card catalog-hub-card h-100">
                <div class="card-body text-center">
                    <div class="catalog-hub-icon {{ $rep['bg'] }} mx-auto">
                        <i class="fas {{ $rep['icon'] }}"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1">{{ $rep['title'] }}</h5>
                    <p class="text-muted small mb-3">{{ $rep['desc'] }}</p>
                    <a href="{{ route($rep['route']) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-chart-bar mr-1"></i> Abrir reporte
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        <div class="col-12"><h4 class="section-title mt-2"><i class="fas fa-file-export"></i>Exportación rápida</h4></div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card catalog-hub-card h-100">
                <div class="card-body text-center">
                    <div class="catalog-hub-icon bg-rep-export mx-auto">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h5 class="font-weight-bold mb-1">Exportar datos</h5>
                    <p class="text-muted small mb-3">Descarga CSV desde cada reporte con los filtros aplicados</p>
                    <div class="d-flex flex-wrap justify-content-center" style="gap: 6px;">
                        <a href="{{ route('reportes.exportar', 'ventas') }}" class="btn btn-outline-success btn-sm"><i class="fas fa-file-csv"></i> Ventas</a>
                        <a href="{{ route('reportes.exportar', 'produccion') }}" class="btn btn-outline-warning btn-sm"><i class="fas fa-file-csv"></i> Prod.</a>
                        <a href="{{ route('reportes.exportar', 'inventario') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-csv"></i> Inv.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
