<?php

use App\Support\TipoEmpaqueAmbito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tipo_empaque')) {
            return;
        }

        if (! Schema::hasColumn('tipo_empaque', 'ambito')) {
            Schema::table('tipo_empaque', function (Blueprint $table) {
                $table->string('ambito', 20)->default(TipoEmpaqueAmbito::AGRICOLA)->after('activo');
            });
        }

        foreach (TipoEmpaqueAmbito::NOMBRES_PLANTA as $nombre) {
            DB::table('tipo_empaque')->where('nombre', $nombre)->update(['ambito' => TipoEmpaqueAmbito::PLANTA]);
        }

        foreach (TipoEmpaqueAmbito::NOMBRES_AGRICOLA as $nombre) {
            DB::table('tipo_empaque')->where('nombre', $nombre)->update(['ambito' => TipoEmpaqueAmbito::AGRICOLA]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tipo_empaque') || ! Schema::hasColumn('tipo_empaque', 'ambito')) {
            return;
        }

        Schema::table('tipo_empaque', function (Blueprint $table) {
            $table->dropColumn('ambito');
        });
    }
};
