<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('almacenaje_lote_produccion') || ! Schema::hasTable('lote_produccion_pedido')) {
            return;
        }

        $lotes = DB::table('lote_produccion_pedido')
            ->where('modo_planificacion', 'empaques')
            ->where('cantidad_empaques_objetivo', '>', 0)
            ->whereNotNull('empaque_catalogo_slug')
            ->get(['loteproduccionpedidoid', 'cantidad_empaques_objetivo']);

        foreach ($lotes as $lote) {
            $objetivo = (int) round((float) $lote->cantidad_empaques_objetivo);
            if ($objetivo <= 0) {
                continue;
            }

            DB::table('almacenaje_lote_produccion')
                ->where('loteproduccionpedidoid', $lote->loteproduccionpedidoid)
                ->where('cantidad', '!=', $objetivo)
                ->update(['cantidad' => $objetivo]);
        }
    }

    public function down(): void
    {
        // No reversible: corrige datos históricos inconsistentes.
    }
};
