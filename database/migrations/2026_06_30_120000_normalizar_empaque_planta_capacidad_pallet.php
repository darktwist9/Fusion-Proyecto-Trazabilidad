<?php

use App\Support\TipoEmpaqueAmbito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Planta: capacidad siempre 1 u.; unidades por pallet de referencia logística.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipo_empaque') || ! Schema::hasColumn('tipo_empaque', 'ambito')) {
            return;
        }

        $registros = DB::table('tipo_empaque')
            ->where('ambito', TipoEmpaqueAmbito::PLANTA)
            ->get(['tipoempaqueid', 'nombre']);

        foreach ($registros as $row) {
            DB::table('tipo_empaque')
                ->where('tipoempaqueid', $row->tipoempaqueid)
                ->update([
                    'capacidad_unidades' => TipoEmpaqueAmbito::capacidadUnidadesPlanta(),
                    'unidades_por_pallet' => TipoEmpaqueAmbito::unidadesPorPalletPlanta($row->nombre),
                ]);
        }
    }

    public function down(): void
    {
        // Corrección de datos; no reversible.
    }
};
