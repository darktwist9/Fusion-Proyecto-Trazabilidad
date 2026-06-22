<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Support\EnvioTrayectoCatalogo;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EnvioTrayectoPermisosTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $roleName): Usuario
    {
        $this->seed(RolePermissionSeeder::class);
        $role = Role::findOrCreate($roleName, 'web');

        $user = Usuario::create([
            'nombre' => 'Test',
            'apellido' => ucfirst($roleName),
            'email' => $roleName.'.trayecto@test.local',
            'nombreusuario' => $roleName.'_trayecto',
            'passwordhash' => Hash::make('secret123'),
            'role' => $roleName,
            'fecharegistro' => now(),
            'fechamodificacion' => now(),
            'activo' => true,
        ]);

        $user->syncRoles([$role->name]);

        return $user;
    }

    public function test_admin_puede_ver_los_tres_trayectos(): void
    {
        $admin = $this->createUser('admin');

        $this->assertSame(
            ['planta', 'mayorista', 'punto-venta'],
            EnvioTrayectoCatalogo::trayectosPermitidos($admin)
        );
    }

    public function test_jefe_agricultor_solo_trayecto_planta(): void
    {
        $user = $this->createUser('jefe_agricultor');

        $this->assertSame(['planta'], EnvioTrayectoCatalogo::trayectosPermitidos($user));
        $this->actingAs($user)->get(route('pedidos.create'))->assertOk();
        $this->actingAs($user)->get(route('pedidos.create', ['destino' => 'mayorista']))->assertForbidden();
    }

    public function test_jefe_planta_solo_trayecto_mayorista(): void
    {
        $user = $this->createUser('jefe_planta');

        $this->assertSame(['mayorista'], EnvioTrayectoCatalogo::trayectosPermitidos($user));
        $this->actingAs($user)->get(route('pedidos.create', ['destino' => 'mayorista']))->assertOk();
        $this->actingAs($user)->get(route('pedidos.create', ['destino' => 'planta']))->assertForbidden();
    }

    public function test_mayorista_no_inicia_envios_wizard_solo_gestiona_solicitudes_minorista(): void
    {
        $user = $this->createUser('mayorista');

        $this->assertSame([], EnvioTrayectoCatalogo::trayectosPermitidos($user));
        $this->assertFalse(EnvioTrayectoCatalogo::puedeCrearAlguno($user));
        $this->actingAs($user)->get(route('pedidos.create', ['destino' => 'punto-venta']))->assertForbidden();
        $this->actingAs($user)->get(route('punto-venta.pedidos.create', ['ctx' => 'mayorista']))->assertForbidden();
        $this->actingAs($user)->get(route('punto-venta.pedidos.index', ['ctx' => 'mayorista']))->assertOk();
    }
}
