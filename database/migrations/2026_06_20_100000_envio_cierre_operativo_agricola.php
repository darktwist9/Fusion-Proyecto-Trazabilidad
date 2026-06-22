<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('envio_asignacion_multiple')) {
            Schema::table('envio_asignacion_multiple', function (Blueprint $table) {
                if (! Schema::hasColumn('envio_asignacion_multiple', 'llegada_confirmada_at')) {
                    $table->timestamp('llegada_confirmada_at')->nullable()->after('fecha_recepcion_planta');
                }
                if (! Schema::hasColumn('envio_asignacion_multiple', 'llegada_confirmada_usuarioid')) {
                    $table->unsignedBigInteger('llegada_confirmada_usuarioid')->nullable()->after('llegada_confirmada_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('envio_asignacion_multiple')) {
            Schema::table('envio_asignacion_multiple', function (Blueprint $table) {
                if (Schema::hasColumn('envio_asignacion_multiple', 'llegada_confirmada_usuarioid')) {
                    $table->dropColumn('llegada_confirmada_usuarioid');
                }
                if (Schema::hasColumn('envio_asignacion_multiple', 'llegada_confirmada_at')) {
                    $table->dropColumn('llegada_confirmada_at');
                }
            });
        }
    }
};
