<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::query()->where('name', 'planta')->first();
        if (! $role) {
            return;
        }

        foreach (['lote_produccion.create', 'lote_produccion.update', 'lote_produccion.delete'] as $perm) {
            $permission = Permission::query()->where('name', $perm)->first();
            if ($permission) {
                $role->revokePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        $role = Role::query()->where('name', 'planta')->first();
        if (! $role) {
            return;
        }

        $role->givePermissionTo([
            'lote_produccion.create',
            'lote_produccion.update',
            'lote_produccion.delete',
        ]);
    }
};
