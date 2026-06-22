<?php

namespace Tests\Feature;

use App\Models\CondicionTransporte;
use App\Models\EnvioAsignacionMultiple;
use App\Models\TipoIncidenteTransporte;
use App\Models\Usuario;
use App\Services\CierreEnvioAgricolaService;
use App\Services\SimulacionRutaService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnvioCierreAgricolaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        CondicionTransporte::create(['codigo' => 'COND001', 'titulo' => 'Luces delanteras', 'descripcion' => 'Test']);
        TipoIncidenteTransporte::create(['codigo' => 'INC001', 'titulo' => 'Retraso en tráfico', 'descripcion' => 'Test']);
    }

    private function transportista(): Usuario
    {
        Role::findOrCreate('transportista', 'web');

        $user = Usuario::create([
            'nombre' => 'Juan',
            'apellido' => 'Chofer',
            'email' => 'transportista.cierre@test.local',
            'nombreusuario' => 'transportista_cierre',
            'passwordhash' => Hash::make('secret'),
            'role' => 'transportista',
            'fecharegistro' => now(),
            'activo' => true,
        ]);
        $user->assignRole('transportista');

        return $user;
    }

    private function envioAsignado(Usuario $transportista): EnvioAsignacionMultiple
    {
        return EnvioAsignacionMultiple::create([
            'externo_envio_id' => 'ENV-Cierre-Test-001',
            'transportista_usuarioid' => $transportista->usuarioid,
            'estado' => 'asignado',
            'fecha_asignacion' => now(),
            'simulacion_geojson' => [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [[-63.18, -17.78], [-63.17, -17.77]],
                    ],
                ]],
            ],
        ]);
    }

    public function test_no_puede_empezar_ruta_sin_condiciones(): void
    {
        $transportista = $this->transportista();
        $envio = $this->envioAsignado($transportista);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('condiciones del vehículo');

        app(SimulacionRutaService::class)->empezarAgricola($envio);
    }

    public function test_perfectas_condiciones_permiten_flujo_pre_ruta(): void
    {
        $transportista = $this->transportista();
        $envio = $this->envioAsignado($transportista);
        $cierre = app(CierreEnvioAgricolaService::class);

        $cierre->registrarCondicionesVehiculo($envio, $transportista, true);

        $this->assertTrue($cierre->tieneCondicionesVehiculo($envio->fresh()));
        $this->assertTrue($cierre->resumenPasos($envio->fresh())['puede_empezar_ruta']);
    }

    public function test_registrar_sin_incidentes_habilita_firmas(): void
    {
        $transportista = $this->transportista();
        $envio = $this->envioAsignado($transportista);
        $cierre = app(CierreEnvioAgricolaService::class);

        $cierre->registrarCondicionesVehiculo($envio, $transportista, true);

        $envio->refresh();
        $envio->update([
            'estado' => 'en_transporte_planta',
            'simulacion_inicio_at' => now()->subMinutes(5),
            'simulacion_duracion_seg' => 120,
        ]);

        $cierre->confirmarLlegada($envio->fresh(), $transportista);
        $cierre->registrarIncidentes($envio->fresh(), $transportista, true);

        $resumen = $cierre->resumenPasos($envio->fresh());
        $this->assertTrue($resumen['puede_firmar_transportista']);
        $this->assertFalse($resumen['puede_firmar_recepcion']);
        $this->assertTrue($resumen['tiene_incidentes']);
    }
}
