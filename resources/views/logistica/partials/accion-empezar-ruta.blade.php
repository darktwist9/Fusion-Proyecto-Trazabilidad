@php
    $usuario = auth()->user();
    $puedeEmpezar = \App\Support\SimulacionRutaCatalogo::usuarioPuedeEmpezarAgricola($usuario, $asignacion);
    $simulacionActiva = \App\Support\SimulacionRutaCatalogo::simulacionActivaAgricola($asignacion);
    $compacto = !empty($compacto);
@endphp

@if($puedeEmpezar)
    @php
        $tieneCondiciones = app(\App\Services\CierreEnvioAgricolaService::class)->tieneCondicionesVehiculo($asignacion);
    @endphp
    @if($tieneCondiciones)
    <form method="POST" action="{{ route('logistica.asignaciones.empezar-ruta', $asignacion) }}" class="d-inline m-0">
        @csrf
        @method('PATCH')
        <button type="submit"
                class="btn btn-sm {{ ($bloque ?? false) ? 'btn-success btn-block btn-lg' : ($compacto ? 'btn-success' : 'btn-success btn-lg') }}"
                title="Empezar ruta simulada">
            <i class="fas fa-play{{ ($bloque ?? false) || ! $compacto ? ' mr-1' : '' }}"></i>{{ ($bloque ?? false) || ! $compacto ? 'Empezar ruta' : '' }}
        </button>
    </form>
    @else
    <a href="{{ route('logistica.asignaciones.cierre.panel', $asignacion) }}"
       class="btn btn-sm {{ ($bloque ?? false) ? 'btn-success btn-block btn-lg' : ($compacto ? 'btn-success' : 'btn-success btn-lg') }}"
       title="Registrar condiciones del vehículo">
        <i class="fas fa-clipboard-check{{ ($bloque ?? false) || ! $compacto ? ' mr-1' : '' }}"></i>{{ ($bloque ?? false) || ! $compacto ? 'Verificar condiciones del Vehículo' : '' }}
    </a>
    @endif
@elseif($simulacionActiva)
    @if(! \App\Support\UsuarioRol::esTransportista($usuario))
    @include('logistica.partials.progreso-simulacion-transportista', [
        'tipo' => 'agricola',
        'id' => $asignacion->envioasignacionmultipleid,
    ])
    @else
    <a href="{{ route('logistica.asignaciones.cierre.panel', $asignacion) }}"
       class="btn btn-sm {{ ($bloque ?? false) ? 'btn-primary btn-block btn-lg' : 'btn-primary' }} font-weight-bold">
        <i class="fas fa-tasks{{ ($bloque ?? false) ? ' mr-1' : '' }}"></i>{{ ($bloque ?? false) ? 'Continuar cierre operativo' : '' }}
    </a>
    @endif
@endif
