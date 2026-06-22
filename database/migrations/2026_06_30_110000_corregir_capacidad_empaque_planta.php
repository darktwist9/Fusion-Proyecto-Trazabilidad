<?php

use App\Support\TipoEmpaqueAmbito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige capacidad de empaques de planta (p. ej. Bolsa heredada del catálogo agrícola con 30 u.).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipo_empaque') || ! Schema::hasColumn('tipo_empaque', 'ambito')) {
            return;
        }

        $planta = [
            'Lata' => ['capacidad_unidades' => 1, 'unidades_por_pallet' => 120],
            'Frasco' => ['capacidad_unidades' => 1, 'unidades_por_pallet' => 96],
            'Bidón' => ['capacidad_unidades' => 1, 'unidades_por_pallet' => 36],
            'Pouch' => ['capacidad_unidades' => 1, 'unidades_por_pallet' => 200],
            'Bolsa plástica' => ['capacidad_unidades' => 1, 'unidades_por_pallet' => 80],
        ];

        foreach ($planta as $nombre => $valores) {
            DB::table('tipo_empaque')
                ->where('nombre', $nombre)
                ->where('ambito', TipoEmpaqueAmbito::PLANTA)
                ->update($valores);
        }
    }

    public function down(): void
    {
        // Corrección de datos demo; no reversible.
    }
};
