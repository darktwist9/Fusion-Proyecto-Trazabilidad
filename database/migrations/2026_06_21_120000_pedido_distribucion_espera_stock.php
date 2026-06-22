<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pedido_distribucion')) {
            return;
        }

        Schema::table('pedido_distribucion', function (Blueprint $table) {
            if (! Schema::hasColumn('pedido_distribucion', 'espera_stock')) {
                $table->boolean('espera_stock')->default(false)->after('tipo_solicitud');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pedido_distribucion')) {
            return;
        }

        Schema::table('pedido_distribucion', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_distribucion', 'espera_stock')) {
                $table->dropColumn('espera_stock');
            }
        });
    }
};
