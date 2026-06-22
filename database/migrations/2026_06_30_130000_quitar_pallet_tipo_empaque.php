<?php

use App\Support\TipoEmpaqueAmbito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El pallet es unidad de estiba (CargaCalculoService), no un tipo de empaque de producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipo_empaque')) {
            return;
        }

        $palletIds = DB::table('tipo_empaque')
            ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(TipoEmpaqueAmbito::NOMBRE_PALLET_EXCLUIDO)])
            ->pluck('tipoempaqueid');

        if ($palletIds->isEmpty()) {
            return;
        }

        foreach (['catalogo_tamano_conteo', 'carga_envio', 'insumo_presentacion'] as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'tipoempaqueid')) {
                DB::table($tabla)->whereIn('tipoempaqueid', $palletIds)->update(['tipoempaqueid' => null]);
            }
        }

        DB::table('tipo_empaque')->whereIn('tipoempaqueid', $palletIds)->delete();
    }

    public function down(): void
    {
        // No se restaura: el pallet no es empaque de producto.
    }
};
