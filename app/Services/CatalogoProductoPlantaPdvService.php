<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Support\AlmacenAmbito;
use App\Support\InsumoCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CatalogoProductoPlantaPdvService
{
    public function __construct(
        private readonly InventarioPresentacionService $inventarioPresentacion
    ) {}

    /**
     * Productos terminados de planta con stock agregado en la red mayorista (por nombre).
     *
     * @return Collection<int, array{insumo: Insumo, stock_mayorista_kg: float, stock_mayorista_etiqueta: string, presentaciones: list<array>}>
     */
    public function productosConDisponibilidad(): Collection
    {
        $almacenesPlanta = AlmacenAmbito::scope(Almacen::query()->where('activo', true), AlmacenAmbito::PLANTA)
            ->pluck('almacenid');

        if ($almacenesPlanta->isEmpty()) {
            return collect();
        }

        $productosPlanta = InsumoCatalogo::aplicarFiltroProductoTerminado(
            Insumo::query()
                ->with(['unidadMedida', 'presentaciones' => fn ($q) => $q->where('activo', true)->orderBy('orden')])
                ->whereIn('almacenid', $almacenesPlanta)
        )->orderBy('nombre')->get();

        $mayoristaPorNombre = $this->insumosMayoristaPorNombre();

        return $productosPlanta->map(function (Insumo $insumo) use ($mayoristaPorNombre) {
            $coincidencias = $mayoristaPorNombre->get($this->normalizarNombre($insumo->nombre), collect());
            $stockKg = (float) $coincidencias->sum(fn (Insumo $i) => (float) $i->stock);
            $unidad = $insumo->unidadMedida?->abreviatura ?? 'kg';

            return [
                'insumo' => $insumo,
                'stock_mayorista_kg' => $stockKg,
                'stock_mayorista_etiqueta' => $stockKg > 0
                    ? number_format($stockKg, 2).' '.$unidad.' en mayorista'
                    : 'Sin stock en mayorista (puede solicitar)',
                'presentaciones' => $this->presentacionesConStock($insumo, $coincidencias),
            ];
        });
    }

    /**
     * @param  Collection<int, Insumo>  $insumosMayorista
     * @return list<array{presentacion: InsumoPresentacion, stock_unidades: float, stock_etiqueta: string}>
     */
    private function presentacionesConStock(Insumo $insumoPlanta, Collection $insumosMayorista): array
    {
        $insumoPlanta->loadMissing('presentaciones');

        return $insumoPlanta->presentaciones->map(function (InsumoPresentacion $pres) use ($insumosMayorista) {
            $stockUnidades = 0.0;
            foreach ($insumosMayorista as $insumoMay) {
                $presMay = InsumoPresentacion::query()
                    ->where('insumoid', $insumoMay->insumoid)
                    ->where('activo', true)
                    ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($pres->nombre))])
                    ->first();

                if ($presMay === null) {
                    continue;
                }

                $this->inventarioPresentacion->asegurarInventarioDesdeStock((int) $insumoMay->almacenid, (int) $insumoMay->insumoid);
                $stockUnidades += $this->inventarioPresentacion->stockTotalUnidades(
                    (int) $insumoMay->almacenid,
                    (int) $presMay->insumo_presentacionid
                );
            }

            $etiquetaUnidad = $pres->etiquetaUnidad();

            return [
                'presentacion' => $pres,
                'stock_unidades' => $stockUnidades,
                'stock_etiqueta' => $stockUnidades > 0
                    ? number_format($stockUnidades, 0).' '.$etiquetaUnidad.' disponibles'
                    : 'Sin stock (solicitud permitida)',
            ];
        })->values()->all();
    }

    /** @return Collection<string, Collection<int, Insumo>> */
    private function insumosMayoristaPorNombre(): Collection
    {
        $almacenesMayorista = AlmacenAmbito::scope(Almacen::query()->where('activo', true), AlmacenAmbito::MAYORISTA)
            ->pluck('almacenid');

        if ($almacenesMayorista->isEmpty()) {
            return collect();
        }

        $insumos = InsumoCatalogo::aplicarFiltroProductoTerminado(
            Insumo::query()->whereIn('almacenid', $almacenesMayorista)
        )->get();

        return $insumos->groupBy(fn (Insumo $i) => $this->normalizarNombre($i->nombre));
    }

    private function normalizarNombre(string $nombre): string
    {
        return Str::lower(trim($nombre));
    }
}
