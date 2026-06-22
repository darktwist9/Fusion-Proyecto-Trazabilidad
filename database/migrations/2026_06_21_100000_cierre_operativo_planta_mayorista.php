<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ruta_distribucion')) {
            Schema::table('ruta_distribucion', function (Blueprint $table) {
                if (! Schema::hasColumn('ruta_distribucion', 'llegada_confirmada_at')) {
                    $table->timestamp('llegada_confirmada_at')->nullable()->after('fecha_salida');
                }
                if (! Schema::hasColumn('ruta_distribucion', 'llegada_confirmada_usuarioid')) {
                    $table->unsignedBigInteger('llegada_confirmada_usuarioid')->nullable()->after('llegada_confirmada_at');
                }
            });
        }

        $this->agregarRutaDistribucionId('checklist_condicion_logistica');
        $this->agregarRutaDistribucionId('checklist_incidente_envio', true);
        $this->agregarRutaDistribucionId('firma_transportista_envio', true);
        $this->agregarRutaDistribucionId('firma_recepcion_envio', true);
    }

    public function down(): void
    {
        $this->quitarRutaDistribucionId('firma_recepcion_envio', true);
        $this->quitarRutaDistribucionId('firma_transportista_envio', true);
        $this->quitarRutaDistribucionId('checklist_incidente_envio', true);
        $this->quitarRutaDistribucionId('checklist_condicion_logistica');

        if (Schema::hasTable('ruta_distribucion')) {
            Schema::table('ruta_distribucion', function (Blueprint $table) {
                if (Schema::hasColumn('ruta_distribucion', 'llegada_confirmada_usuarioid')) {
                    $table->dropColumn('llegada_confirmada_usuarioid');
                }
                if (Schema::hasColumn('ruta_distribucion', 'llegada_confirmada_at')) {
                    $table->dropColumn('llegada_confirmada_at');
                }
            });
        }
    }

    private function agregarRutaDistribucionId(string $tabla, bool $unique = false): void
    {
        if (! Schema::hasTable($tabla) || Schema::hasColumn($tabla, 'rutadistribucionid')) {
            return;
        }

        Schema::table($tabla, function (Blueprint $table) use ($unique, $tabla) {
            if (Schema::hasColumn($tabla, 'envioasignacionmultipleid')) {
                $table->unsignedBigInteger('envioasignacionmultipleid')->nullable()->change();
            }
            $col = $table->unsignedBigInteger('rutadistribucionid')->nullable();
            if ($unique) {
                $col->unique();
            }
            $table->foreign('rutadistribucionid')
                ->references('rutadistribucionid')
                ->on('ruta_distribucion')
                ->cascadeOnDelete();
        });
    }

    private function quitarRutaDistribucionId(string $tabla, bool $unique = false): void
    {
        if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, 'rutadistribucionid')) {
            return;
        }

        Schema::table($tabla, function (Blueprint $table) use ($unique) {
            $table->dropForeign(['rutadistribucionid']);
            if ($unique) {
                $table->dropUnique(['rutadistribucionid']);
            }
            $table->dropColumn('rutadistribucionid');
        });
    }
};
