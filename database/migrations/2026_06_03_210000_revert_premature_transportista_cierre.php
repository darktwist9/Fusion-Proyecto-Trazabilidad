<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pedido') || ! Schema::hasTable('envio_asignacion_multiple')) {
            return;
        }

        $envioIds = DB::table('envio_asignacion_multiple as e')
            ->join('pedido as p', 'p.pedidoid', '=', 'e.pedidoid')
            ->whereNotIn('p.estado', ['confirmado'])
            ->pluck('e.envioasignacionmultipleid');

        if ($envioIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('checklist_condicion_logistica')) {
            $checklistIds = DB::table('checklist_condicion_logistica')
                ->whereIn('envioasignacionmultipleid', $envioIds)
                ->pluck('checklistcondicionid');

            if ($checklistIds->isNotEmpty() && Schema::hasTable('checklist_condicion_logistica_detalle')) {
                DB::table('checklist_condicion_logistica_detalle')
                    ->whereIn('checklistcondicionid', $checklistIds)
                    ->delete();
            }

            if ($checklistIds->isNotEmpty()) {
                DB::table('checklist_condicion_logistica')
                    ->whereIn('checklistcondicionid', $checklistIds)
                    ->delete();
            }
        }

        foreach ($envioIds as $envioId) {
            $envio = DB::table('envio_asignacion_multiple')
                ->where('envioasignacionmultipleid', $envioId)
                ->first(['estado', 'simulacion_inicio_at']);

            if ($envio === null) {
                continue;
            }

            $updates = [];

            if ($envio->simulacion_inicio_at !== null) {
                $updates['simulacion_inicio_at'] = null;
                $updates['simulacion_duracion_seg'] = null;
            }

            if (in_array((string) $envio->estado, ['en_ruta', 'en_transporte_planta', 'en_transito'], true)) {
                $updates['estado'] = 'pendiente';
            }

            if ($updates !== []) {
                DB::table('envio_asignacion_multiple')
                    ->where('envioasignacionmultipleid', $envioId)
                    ->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Limpieza operativa; no reversible.
    }
};
