<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\DetallePedidoDistribucion;
use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Models\PedidoDistribucion;
use App\Models\PuntoVenta;
use App\Models\SolicitudProduccionPlanta;
use App\Models\TipoInsumo;
use App\Models\UnidadMedida;
use App\Models\Usuario;
use App\Support\AlmacenAmbito;
use App\Support\InsumoCatalogo;
use App\Support\PedidoDistribucionCatalogo;
use App\Support\SolicitudProduccionPlantaCatalogo;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidoDistribucionFase3Test extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        $this->seed(RolePermissionSeeder::class);
        foreach (['admin', 'mayorista', 'minorista', 'planta'] as $rol) {
            Role::findOrCreate($rol, 'web');
        }
    }

    private function usuario(string $role, string $email): Usuario
    {
        $this->seedRoles();
        $user = Usuario::create([
            'nombre' => ucfirst($role),
            'apellido' => 'Test',
            'email' => $email,
            'nombreusuario' => str_replace(['@', '.'], '_', $email),
            'passwordhash' => Hash::make('secret'),
            'role' => $role,
            'fecharegistro' => now(),
            'activo' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_minorista_puede_solicitar_producto_custom_sin_stock(): void
    {
        $minorista = $this->usuario('minorista', 'min.f3@test.local');
        $pdv = PuntoVenta::create([
            'usuarioid' => $minorista->usuarioid,
            'nombre' => 'PDV F3',
            'direccion' => 'Calle Test',
            'latitud' => -17.78,
            'longitud' => -63.18,
            'activo' => true,
        ]);

        $response = $this->actingAs($minorista)->post(route('punto-venta.pedidos.store'), [
            'puntoventaid' => $pdv->puntoventaid,
            'tipo_solicitud' => PedidoDistribucionCatalogo::TIPO_SOLICITUD_CUSTOM,
            'producto_nombre' => 'Snack artesanal de yuca',
            'tipo_envase' => 'bolsa',
            'cantidad' => 50,
            'fecha_entrega_deseada' => now()->addDay()->toDateString(),
            'hora_entrega_deseada' => '10:30',
        ]);

        $response->assertRedirect();
        $pedido = PedidoDistribucion::query()->first();
        $this->assertNotNull($pedido);
        $this->assertSame(PedidoDistribucionCatalogo::TIPO_SOLICITUD_CUSTOM, $pedido->tipo_solicitud);
        $this->assertNull($pedido->almacen_mayorista_origenid);
        $detalle = $pedido->detalles->first();
        $this->assertTrue($detalle->es_solicitud_custom);
        $this->assertSame('bolsa', $detalle->tipo_envase);
    }

    public function test_mayorista_acepta_custom_y_solicita_produccion_planta(): void
    {
        $mayorista = $this->usuario('mayorista', 'may.f3@test.local');
        $planta = $this->usuario('planta', 'plt.f3@test.local');
        $minorista = $this->usuario('minorista', 'min2.f3@test.local');
        $kg = UnidadMedida::create(['nombre' => 'Kilogramo', 'abreviatura' => 'kg']);

        Almacen::create([
            'nombre' => 'Mayorista Custom F3',
            'ubicacion' => 'Av. Test, Santa Cruz',
            'capacidad' => 300,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::MAYORISTA,
            'responsable_usuarioid' => $mayorista->usuarioid,
            'activo' => true,
        ]);

        $pdv = PuntoVenta::create([
            'usuarioid' => $minorista->usuarioid,
            'nombre' => 'PDV F3 B',
            'latitud' => -17.79,
            'longitud' => -63.17,
            'activo' => true,
        ]);

        $pedido = PedidoDistribucion::create([
            'numero_solicitud' => 'PDV-F3-0001',
            'puntoventaid' => $pdv->puntoventaid,
            'estado' => PedidoDistribucionCatalogo::ESTADO_PENDIENTE,
            'tipo_solicitud' => PedidoDistribucionCatalogo::TIPO_SOLICITUD_CUSTOM,
            'fechapedido' => now(),
            'fecha_entrega_deseada' => now()->addDays(2),
            'hora_entrega_deseada' => '14:00',
            'creado_por_usuarioid' => $minorista->usuarioid,
        ]);

        DetallePedidoDistribucion::create([
            'pedidodistribucionid' => $pedido->pedidodistribucionid,
            'producto_nombre' => 'Puré especial',
            'tipo_envase' => 'lata',
            'cantidad' => 120,
            'es_solicitud_custom' => true,
        ]);

        $this->actingAs($mayorista)
            ->post(route('punto-venta.pedidos.aceptar', $pedido))
            ->assertRedirect();

        $pedido->refresh();
        $this->assertTrue($pedido->requiere_coordinacion_planta);
        $this->assertFalse($pedido->coordinacion_planta_resuelta);

        $this->actingAs($mayorista)
            ->post(route('punto-venta.pedidos.solicitar-produccion-planta', $pedido))
            ->assertRedirect();

        $solicitud = SolicitudProduccionPlanta::query()->first();
        $this->assertNotNull($solicitud);
        $this->assertSame(SolicitudProduccionPlantaCatalogo::ESTADO_PENDIENTE, $solicitud->estado);

        $this->actingAs($planta)
            ->post(route('planta.solicitudes-produccion.aceptar', $solicitud))
            ->assertRedirect();

        $this->actingAs($planta)
            ->post(route('planta.solicitudes-produccion.completar', $solicitud))
            ->assertRedirect();

        $pedido->refresh();
        $this->assertTrue($pedido->coordinacion_planta_resuelta);
    }

    public function test_aceptar_asigna_almacen_cercano_con_stock(): void
    {
        $mayorista = $this->usuario('mayorista', 'may3.f3@test.local');
        $minorista = $this->usuario('minorista', 'min3.f3@test.local');
        $kg = UnidadMedida::create(['nombre' => 'Kilogramo', 'abreviatura' => 'kg']);
        InsumoCatalogo::asegurarCatalogosBase();
        $tipoProductoId = InsumoCatalogo::tipoProductoTerminadoId();

        $almacenPlanta = Almacen::create([
            'nombre' => 'Planta F3',
            'ubicacion' => 'Planta SCZ',
            'capacidad' => 1000,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::PLANTA,
            'activo' => true,
        ]);

        $almacenCercano = Almacen::create([
            'nombre' => 'Mayorista Cercano',
            'ubicacion' => 'GPS -17.78500, -63.18100',
            'capacidad' => 500,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::MAYORISTA,
            'responsable_usuarioid' => $mayorista->usuarioid,
            'activo' => true,
        ]);

        $almacenLejano = Almacen::create([
            'nombre' => 'Mayorista Lejano',
            'ubicacion' => 'GPS -17.85000, -63.25000',
            'capacidad' => 500,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::MAYORISTA,
            'activo' => true,
        ]);

        $insumoPlanta = Insumo::create([
            'nombre' => 'Chips de papa laminados',
            'stock' => 100,
            'tipoinsumoid' => $tipoProductoId,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'almacenid' => $almacenPlanta->almacenid,
        ]);

        $presentacionPlanta = InsumoPresentacion::create([
            'insumoid' => $insumoPlanta->insumoid,
            'nombre' => 'Bolsa 150 g',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 0.15,
            'orden' => 1,
            'activo' => true,
        ]);

        $insumoCercano = Insumo::create([
            'nombre' => 'Chips de papa laminados',
            'stock' => 200,
            'tipoinsumoid' => $tipoProductoId,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'almacenid' => $almacenCercano->almacenid,
        ]);

        InsumoPresentacion::create([
            'insumoid' => $insumoCercano->insumoid,
            'nombre' => 'Bolsa 150 g',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 0.15,
            'orden' => 1,
            'activo' => true,
        ]);

        $insumoLejano = Insumo::create([
            'nombre' => 'Chips de papa laminados',
            'stock' => 200,
            'tipoinsumoid' => $tipoProductoId,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'almacenid' => $almacenLejano->almacenid,
        ]);

        InsumoPresentacion::create([
            'insumoid' => $insumoLejano->insumoid,
            'nombre' => 'Bolsa 150 g',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 0.15,
            'orden' => 1,
            'activo' => true,
        ]);

        $pdv = PuntoVenta::create([
            'usuarioid' => $minorista->usuarioid,
            'nombre' => 'PDV Cercano',
            'latitud' => -17.784,
            'longitud' => -63.182,
            'activo' => true,
        ]);

        $presentacionCercano = InsumoPresentacion::query()
            ->where('insumoid', $insumoCercano->insumoid)
            ->where('activo', true)
            ->first();

        app(\App\Services\InventarioPresentacionService::class)
            ->asegurarInventarioDesdeStock((int) $almacenCercano->almacenid, (int) $insumoCercano->insumoid);

        $pedido = PedidoDistribucion::create([
            'numero_solicitud' => 'PDV-F3-0002',
            'puntoventaid' => $pdv->puntoventaid,
            'almacen_mayorista_origenid' => $almacenCercano->almacenid,
            'estado' => PedidoDistribucionCatalogo::ESTADO_PENDIENTE,
            'tipo_solicitud' => PedidoDistribucionCatalogo::TIPO_SOLICITUD_STOCK,
            'fechapedido' => now(),
            'fecha_entrega_deseada' => now()->addDay(),
            'creado_por_usuarioid' => $minorista->usuarioid,
        ]);

        DetallePedidoDistribucion::create([
            'pedidodistribucionid' => $pedido->pedidodistribucionid,
            'insumoid' => $insumoCercano->insumoid,
            'insumo_presentacionid' => $presentacionCercano->insumo_presentacionid,
            'producto_nombre' => 'Chips de papa laminados · Bolsa 150 g',
            'tipo_envase' => 'bolsa',
            'cantidad' => 10,
            'es_solicitud_custom' => false,
        ]);

        $this->actingAs($mayorista)
            ->post(route('punto-venta.pedidos.aceptar', $pedido))
            ->assertRedirect();

        $pedido->refresh();
        $this->assertSame((int) $almacenCercano->almacenid, (int) $pedido->almacen_mayorista_origenid);
        $this->assertFalse($pedido->requiere_coordinacion_planta);
        $this->assertSame((int) $insumoCercano->insumoid, (int) $pedido->detalles->first()?->insumoid);
    }

    public function test_minorista_puede_solicitar_espera_stock_cuando_excede_disponible(): void
    {
        $minorista = $this->usuario('minorista', 'min4.f3@test.local');
        $kg = UnidadMedida::create(['nombre' => 'Kilogramo', 'abreviatura' => 'kg']);
        InsumoCatalogo::asegurarCatalogosBase();
        $tipoProductoId = InsumoCatalogo::tipoProductoTerminadoId();

        $almacenMay = Almacen::create([
            'nombre' => 'Mayorista Stock',
            'ubicacion' => 'SCZ',
            'capacidad' => 500,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::MAYORISTA,
            'activo' => true,
        ]);

        $insumoMay = Insumo::create([
            'nombre' => 'Puré de zanahoria pasteurizado',
            'stock' => 40,
            'tipoinsumoid' => $tipoProductoId,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'almacenid' => $almacenMay->almacenid,
        ]);

        $presentacion = InsumoPresentacion::create([
            'insumoid' => $insumoMay->insumoid,
            'nombre' => 'Lata 400 g',
            'tipo_envase' => 'lata',
            'peso_neto_kg' => 0.4,
            'orden' => 1,
            'activo' => true,
        ]);

        app(\App\Services\InventarioPresentacionService::class)
            ->asegurarInventarioDesdeStock((int) $almacenMay->almacenid, (int) $insumoMay->insumoid);

        $pdv = PuntoVenta::create([
            'usuarioid' => $minorista->usuarioid,
            'nombre' => 'PDV Stock',
            'latitud' => -17.78,
            'longitud' => -63.18,
            'activo' => true,
        ]);

        $disponible = app(\App\Services\DisponibilidadMayoristaPdvService::class)
            ->stockUnidadesReales((int) $insumoMay->insumoid, (int) $presentacion->insumo_presentacionid);

        $this->assertGreaterThan(0, $disponible);

        $response = $this->actingAs($minorista)->post(route('punto-venta.pedidos.store'), [
            'puntoventaid' => $pdv->puntoventaid,
            'tipo_solicitud' => PedidoDistribucionCatalogo::TIPO_SOLICITUD_STOCK,
            'insumoid' => $insumoMay->insumoid,
            'almacen_mayorista_origenid' => $almacenMay->almacenid,
            'insumo_presentacionid' => $presentacion->insumo_presentacionid,
            'cantidad' => $disponible + 100,
            'fecha_entrega_deseada' => now()->addDays(3)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $pedido = PedidoDistribucion::query()->first();
        $this->assertNotNull($pedido);
        $this->assertTrue($pedido->espera_stock);
        $this->assertSame(PedidoDistribucionCatalogo::ESTADO_PENDIENTE, $pedido->estado);
    }

    public function test_minorista_puede_solicitar_presentacion_sin_stock(): void
    {
        $minorista = $this->usuario('minorista', 'min5.f3@test.local');
        $kg = UnidadMedida::create(['nombre' => 'Kilogramo', 'abreviatura' => 'kg']);
        InsumoCatalogo::asegurarCatalogosBase();
        $tipoProductoId = InsumoCatalogo::tipoProductoTerminadoId();

        $almacenMay = Almacen::create([
            'nombre' => 'Mayorista Papas',
            'ubicacion' => 'SCZ',
            'capacidad' => 500,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'ambito' => AlmacenAmbito::MAYORISTA,
            'activo' => true,
        ]);

        $insumoMay = Insumo::create([
            'nombre' => 'Papas fritas',
            'stock' => 25,
            'tipoinsumoid' => $tipoProductoId,
            'unidadmedidaid' => $kg->unidadmedidaid,
            'almacenid' => $almacenMay->almacenid,
        ]);

        $lata = InsumoPresentacion::create([
            'insumoid' => $insumoMay->insumoid,
            'nombre' => 'Lata 150 g',
            'tipo_envase' => 'lata',
            'peso_neto_kg' => 0.15,
            'orden' => 1,
            'activo' => true,
        ]);

        $bolsa = InsumoPresentacion::create([
            'insumoid' => $insumoMay->insumoid,
            'nombre' => 'Bolsa 200 g',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 0.2,
            'orden' => 2,
            'activo' => true,
        ]);

        app(\App\Services\InventarioPresentacionService::class)->ingresar(
            (int) $almacenMay->almacenid,
            (int) $insumoMay->insumoid,
            (int) $lata->insumo_presentacionid,
            null,
            'TEST-LATA',
            12,
            1.8
        );

        $pdv = PuntoVenta::create([
            'usuarioid' => $minorista->usuarioid,
            'nombre' => 'PDV Papas',
            'latitud' => -17.78,
            'longitud' => -63.18,
            'activo' => true,
        ]);

        $service = app(\App\Services\DisponibilidadMayoristaPdvService::class);
        $this->assertSame(0.0, $service->stockUnidadesReales((int) $insumoMay->insumoid, (int) $bolsa->insumo_presentacionid));
        $this->assertGreaterThan(0, $service->stockUnidadesReales((int) $insumoMay->insumoid, (int) $lata->insumo_presentacionid));

        $response = $this->actingAs($minorista)->post(route('punto-venta.pedidos.store'), [
            'puntoventaid' => $pdv->puntoventaid,
            'tipo_solicitud' => PedidoDistribucionCatalogo::TIPO_SOLICITUD_STOCK,
            'insumoid' => $insumoMay->insumoid,
            'almacen_mayorista_origenid' => $almacenMay->almacenid,
            'insumo_presentacionid' => $bolsa->insumo_presentacionid,
            'cantidad' => 10,
            'fecha_entrega_deseada' => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        $pedido = PedidoDistribucion::query()->first();
        $this->assertTrue($pedido->espera_stock);
        $this->assertSame((int) $bolsa->insumo_presentacionid, (int) $pedido->detalles->first()?->insumo_presentacionid);
    }
}
