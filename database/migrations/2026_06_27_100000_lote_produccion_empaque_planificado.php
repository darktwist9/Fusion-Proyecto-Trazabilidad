<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lote_produccion_pedido')) {
            return;
        }

        Schema::table('lote_produccion_pedido', function (Blueprint $table) {
            if (! Schema::hasColumn('lote_produccion_pedido', 'empaque_catalogo_slug')) {
                $table->string('empaque_catalogo_slug', 40)->nullable()->after('cantidad_producida');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'empaque_nombre_personalizado')) {
                $table->string('empaque_nombre_personalizado', 120)->nullable()->after('empaque_catalogo_slug');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'empaque_peso_neto_kg')) {
                $table->decimal('empaque_peso_neto_kg', 12, 4)->nullable()->after('empaque_nombre_personalizado');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'empaque_tipo_envase')) {
                $table->string('empaque_tipo_envase', 30)->nullable()->after('empaque_peso_neto_kg');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'modo_planificacion')) {
                $table->string('modo_planificacion', 20)->nullable()->after('empaque_tipo_envase');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'cantidad_empaques_objetivo')) {
                $table->decimal('cantidad_empaques_objetivo', 12, 2)->nullable()->after('modo_planificacion');
            }
            if (! Schema::hasColumn('lote_produccion_pedido', 'insumo_presentacionid')) {
                $table->unsignedBigInteger('insumo_presentacionid')->nullable()->after('cantidad_empaques_objetivo');
                if (Schema::hasTable('insumo_presentacion')) {
                    $table->foreign('insumo_presentacionid')
                        ->references('insumo_presentacionid')
                        ->on('insumo_presentacion')
                        ->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lote_produccion_pedido')) {
            return;
        }

        Schema::table('lote_produccion_pedido', function (Blueprint $table) {
            if (Schema::hasColumn('lote_produccion_pedido', 'insumo_presentacionid')) {
                $table->dropForeign(['insumo_presentacionid']);
            }
            foreach ([
                'empaque_catalogo_slug',
                'empaque_nombre_personalizado',
                'empaque_peso_neto_kg',
                'empaque_tipo_envase',
                'modo_planificacion',
                'cantidad_empaques_objetivo',
                'insumo_presentacionid',
            ] as $col) {
                if (Schema::hasColumn('lote_produccion_pedido', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
