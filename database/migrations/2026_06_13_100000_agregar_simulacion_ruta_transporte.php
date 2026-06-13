<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('envio_asignacion_multiple', function (Blueprint $table) {
            $table->timestamp('simulacion_inicio_at')->nullable()->after('costo_bs');
            $table->unsignedInteger('simulacion_duracion_seg')->nullable()->after('simulacion_inicio_at');
            $table->json('simulacion_geojson')->nullable()->after('simulacion_duracion_seg');
        });

        Schema::table('ruta_distribucion', function (Blueprint $table) {
            $table->timestamp('simulacion_inicio_at')->nullable()->after('costo_bs');
            $table->unsignedInteger('simulacion_duracion_seg')->nullable()->after('simulacion_inicio_at');
            $table->json('simulacion_geojson')->nullable()->after('simulacion_duracion_seg');
        });
    }

    public function down(): void
    {
        Schema::table('envio_asignacion_multiple', function (Blueprint $table) {
            $table->dropColumn(['simulacion_inicio_at', 'simulacion_duracion_seg', 'simulacion_geojson']);
        });

        Schema::table('ruta_distribucion', function (Blueprint $table) {
            $table->dropColumn(['simulacion_inicio_at', 'simulacion_duracion_seg', 'simulacion_geojson']);
        });
    }
};
