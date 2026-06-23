<?php

namespace Tests\Unit;

use App\Services\LoteUbicacionTerrestreService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoteUbicacionTerrestreServiceTest extends TestCase
{
    public function test_radio_metros_desde_hectareas(): void
    {
        $svc = new LoteUbicacionTerrestreService();

        $this->assertEqualsWithDelta(56.42, $svc->radioMetros(1), 0.1);
        $this->assertSame(0.0, $svc->radioMetros(0));
    }

    #[DataProvider('puntosNominatimAgua')]
    public function test_clasifica_respuestas_nominatim_de_agua(array $payload, bool $esAgua): void
    {
        $svc = new LoteUbicacionTerrestreService();
        $ref = new \ReflectionClass($svc);
        $method = $ref->getMethod('esRespuestaNominatimAgua');
        $method->setAccessible(true);

        $this->assertSame($esAgua, $method->invoke($svc, $payload));
    }

    public static function puntosNominatimAgua(): array
    {
        return [
            'mar' => [['category' => 'natural', 'type' => 'sea'], true],
            'rio' => [['category' => 'waterway', 'type' => 'river'], true],
            'calle' => [['category' => 'highway', 'type' => 'residential'], false],
            'cultivo' => [['category' => 'landuse', 'type' => 'farmland'], false],
        ];
    }

    public function test_punto_dentro_de_poligono_simple(): void
    {
        $svc = new LoteUbicacionTerrestreService();
        $ref = new \ReflectionClass($svc);
        $method = $ref->getMethod('puntoEnPoligono');
        $method->setAccessible(true);

        $cuadrado = [
            [-17.0, -63.0],
            [-17.0, -62.9],
            [-16.9, -62.9],
            [-16.9, -63.0],
        ];

        $this->assertTrue($method->invoke($svc, -16.95, -62.95, $cuadrado));
        $this->assertFalse($method->invoke($svc, -17.5, -63.5, $cuadrado));
    }
}
