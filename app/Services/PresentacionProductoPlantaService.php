<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Models\LoteProduccionPedido;
use App\Support\EmpaquePlantaCatalogo;
use Illuminate\Support\Facades\Schema;

class PresentacionProductoPlantaService
{
    /**
     * Asegura la presentación del lote sobre el insumo terminado y devuelve el registro.
     */
    public function resolverPresentacionParaLote(LoteProduccionPedido $lote, Insumo $insumoTerminado): InsumoPresentacion
    {
        if ($lote->insumo_presentacionid) {
            $existente = InsumoPresentacion::query()
                ->where('insumo_presentacionid', $lote->insumo_presentacionid)
                ->where('insumoid', $insumoTerminado->insumoid)
                ->where('activo', true)
                ->first();
            if ($existente) {
                return $existente;
            }
        }

        $slug = $lote->empaque_catalogo_slug;
        if (! EmpaquePlantaCatalogo::esSlugValido($slug)) {
            throw new \InvalidArgumentException('El lote no tiene un tipo de empaque planificado.');
        }

        $nombre = EmpaquePlantaCatalogo::nombrePresentacionDesdePlan(
            $slug,
            $lote->empaque_nombre_personalizado,
            $lote->empaque_peso_neto_kg
        );
        $peso = EmpaquePlantaCatalogo::pesoNetoKg($slug, $lote->empaque_peso_neto_kg);
        $tipoEnvase = $lote->empaque_tipo_envase
            ?: EmpaquePlantaCatalogo::tipoEnvaseDesdePlan($slug, $lote->empaque_tipo_envase);

        EmpaquePlantaCatalogo::asegurarTiposEmpaqueEnBd();
        $tipoEmpaqueId = EmpaquePlantaCatalogo::tipoEmpaqueIdPorSlug(
            $slug === EmpaquePlantaCatalogo::SLUG_PERSONALIZADO ? null : $slug
        );

        if ($slug === EmpaquePlantaCatalogo::SLUG_PERSONALIZADO && $lote->empaque_nombre_personalizado) {
            $tipoNombre = match ($tipoEnvase) {
                'lata' => 'Lata',
                'frasco' => 'Frasco',
                'bidon' => 'Bidón',
                'caja' => 'Caja de cartón',
                default => 'Bolsa plástica',
            };
            $tipoEmpaqueId = \App\Models\TipoEmpaque::query()->where('nombre', $tipoNombre)->value('tipoempaqueid')
                ?? $tipoEmpaqueId;
        }

        $presentacion = InsumoPresentacion::query()->firstOrCreate(
            [
                'insumoid' => $insumoTerminado->insumoid,
                'nombre' => $nombre,
            ],
            [
                'tipoempaqueid' => $tipoEmpaqueId,
                'tipo_envase' => $tipoEnvase,
                'peso_neto_kg' => $peso,
                'activo' => true,
                'orden' => 0,
            ]
        );

        if (! $presentacion->wasRecentlyCreated) {
            $presentacion->update([
                'tipoempaqueid' => $tipoEmpaqueId ?? $presentacion->tipoempaqueid,
                'tipo_envase' => $tipoEnvase,
                'peso_neto_kg' => $peso,
                'activo' => true,
            ]);
        }

        if (Schema::hasColumn('lote_produccion_pedido', 'insumo_presentacionid')
            && (int) $lote->insumo_presentacionid !== (int) $presentacion->insumo_presentacionid) {
            $lote->update(['insumo_presentacionid' => $presentacion->insumo_presentacionid]);
        }

        return $presentacion->fresh();
    }

    /**
     * Crea las 5 presentaciones estándar para un producto terminado si no tiene ninguna.
     */
    public function asegurarPresentacionesEstandar(int $insumoId): void
    {
        if (! Schema::hasTable('insumo_presentacion')) {
            return;
        }

        $tiene = InsumoPresentacion::query()
            ->where('insumoid', $insumoId)
            ->where('activo', true)
            ->exists();

        if ($tiene) {
            return;
        }

        EmpaquePlantaCatalogo::asegurarTiposEmpaqueEnBd();

        foreach (EmpaquePlantaCatalogo::TIPOS_PREDEFINIDOS as $idx => $tipo) {
            InsumoPresentacion::query()->firstOrCreate(
                [
                    'insumoid' => $insumoId,
                    'nombre' => EmpaquePlantaCatalogo::nombrePresentacionDesdePlan($tipo['slug']),
                ],
                [
                    'tipoempaqueid' => EmpaquePlantaCatalogo::tipoEmpaqueIdPorSlug($tipo['slug']),
                    'tipo_envase' => $tipo['tipo_envase'],
                    'peso_neto_kg' => $tipo['peso_neto_kg'],
                    'activo' => true,
                    'orden' => $idx,
                ]
            );
        }
    }
}
