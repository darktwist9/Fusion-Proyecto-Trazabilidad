<?php

namespace Tests\Unit;

use App\Models\LoteProduccionPedido;
use App\Support\EmpaquePlantaCatalogo;
use App\Support\ProductoPlantaCatalogo;
use PHPUnit\Framework\TestCase;

class ProductoPlantaCatalogoTest extends TestCase
{
    public function test_recomendacion_materia_prima_para_pure_de_papa(): void
    {
        $rec = ProductoPlantaCatalogo::recomendacionMateriaPrima('Puré de papa', 100);

        $this->assertNotNull($rec);
        $this->assertSame(100.0, $rec['unidades']);
        $this->assertSame(40.0, $rec['salida_kg']);
        $this->assertEqualsWithDelta(47.06, $rec['entrada_kg'], 0.01);
    }

    public function test_recomendacion_null_si_no_es_pure(): void
    {
        $this->assertNull(ProductoPlantaCatalogo::recomendacionMateriaPrima('Papas fritas', 100));
    }

    public function test_unidades_producidas_respeta_objetivo_en_modo_empaques(): void
    {
        $lote = new LoteProduccionPedido([
            'modo_planificacion' => EmpaquePlantaCatalogo::MODO_EMPAQUES,
            'cantidad_empaques_objetivo' => 10,
            'empaque_catalogo_slug' => 'bidon',
            'empaque_peso_neto_kg' => 5.0,
        ]);

        $capacidad = $this->createMock(\App\Services\AlmacenCapacidadService::class);

        $this->assertSame(10, ProductoPlantaCatalogo::unidadesProducidas($lote, $capacidad));
    }

    public function test_pluralizar_nombre_insumo(): void
    {
        $this->assertSame('Papa', ProductoPlantaCatalogo::pluralizarNombreInsumo('Papa', 1));
        $this->assertSame('Papas', ProductoPlantaCatalogo::pluralizarNombreInsumo('Papa', 336));
    }
}
