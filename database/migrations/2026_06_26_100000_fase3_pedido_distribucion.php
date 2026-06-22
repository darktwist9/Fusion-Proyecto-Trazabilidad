<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pedido_distribucion')) {
            Schema::table('pedido_distribucion', function (Blueprint $table) {
                if (! Schema::hasColumn('pedido_distribucion', 'hora_entrega_deseada')) {
                    $table->time('hora_entrega_deseada')->nullable()->after('fecha_entrega_deseada');
                }
                if (! Schema::hasColumn('pedido_distribucion', 'tipo_solicitud')) {
                    $table->string('tipo_solicitud', 20)->default('catalogo')->after('estado');
                }
                if (! Schema::hasColumn('pedido_distribucion', 'requiere_coordinacion_planta')) {
                    $table->boolean('requiere_coordinacion_planta')->default(false)->after('tipo_solicitud');
                }
                if (! Schema::hasColumn('pedido_distribucion', 'coordinacion_planta_resuelta')) {
                    $table->boolean('coordinacion_planta_resuelta')->default(false)->after('requiere_coordinacion_planta');
                }
            });
        }

        if (Schema::hasTable('detalle_pedido_distribucion')) {
            Schema::table('detalle_pedido_distribucion', function (Blueprint $table) {
                if (! Schema::hasColumn('detalle_pedido_distribucion', 'insumo_planta_referenciaid')) {
                    $table->unsignedBigInteger('insumo_planta_referenciaid')->nullable()->after('insumoid');
                }
                if (! Schema::hasColumn('detalle_pedido_distribucion', 'insumo_presentacionid')) {
                    $table->unsignedBigInteger('insumo_presentacionid')->nullable()->after('insumo_planta_referenciaid');
                }
                if (! Schema::hasColumn('detalle_pedido_distribucion', 'tipo_envase')) {
                    $table->string('tipo_envase', 30)->nullable()->after('insumo_presentacionid');
                }
                if (! Schema::hasColumn('detalle_pedido_distribucion', 'es_solicitud_custom')) {
                    $table->boolean('es_solicitud_custom')->default(false)->after('tipo_envase');
                }
            });
        }

        if (! Schema::hasTable('solicitud_produccion_planta')) {
            Schema::create('solicitud_produccion_planta', function (Blueprint $table) {
                $table->id('solicitudproduccionplantaid');
                $table->string('numero_solicitud', 40)->unique();
                $table->unsignedBigInteger('pedidodistribucionid')->nullable();
                $table->unsignedBigInteger('almacen_mayorista_destinoid')->nullable();
                $table->unsignedBigInteger('insumo_planta_referenciaid')->nullable();
                $table->unsignedBigInteger('insumo_presentacionid')->nullable();
                $table->string('producto_nombre');
                $table->string('tipo_envase', 30)->nullable();
                $table->decimal('cantidad', 12, 2);
                $table->string('unidad_etiqueta', 40)->nullable();
                $table->string('estado', 30)->default('pendiente');
                $table->date('fecha_entrega_deseada')->nullable();
                $table->time('hora_entrega_deseada')->nullable();
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('creado_por_usuarioid')->nullable();
                $table->unsignedBigInteger('aceptado_por_usuarioid')->nullable();
                $table->timestamp('fecha_aceptacion')->nullable();
                $table->timestamp('fecha_completada')->nullable();
                $table->timestamp('fechapedido')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_produccion_planta');

        if (Schema::hasTable('detalle_pedido_distribucion')) {
            Schema::table('detalle_pedido_distribucion', function (Blueprint $table) {
                foreach (['insumo_planta_referenciaid', 'insumo_presentacionid', 'tipo_envase', 'es_solicitud_custom'] as $col) {
                    if (Schema::hasColumn('detalle_pedido_distribucion', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('pedido_distribucion')) {
            Schema::table('pedido_distribucion', function (Blueprint $table) {
                foreach (['hora_entrega_deseada', 'tipo_solicitud', 'requiere_coordinacion_planta', 'coordinacion_planta_resuelta'] as $col) {
                    if (Schema::hasColumn('pedido_distribucion', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
