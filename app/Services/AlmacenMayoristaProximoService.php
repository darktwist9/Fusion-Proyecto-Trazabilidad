<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\DetallePedidoDistribucion;
use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Models\PedidoDistribucion;
use App\Models\PuntoVenta;
use App\Support\AlmacenAmbito;
use App\Support\InsumoCatalogo;
use App\Support\UbicacionGpsParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AlmacenMayoristaProximoService
{
    public function __construct(
        private readonly InventarioPresentacionService $inventarioPresentacion
    ) {}

    /**
     * @return array{almacen: Almacen, insumo: Insumo, presentacion: InsumoPresentacion|null, distancia_m: float}|null
     */
    public function resolverParaPedido(PedidoDistribucion $pedido): ?array
    {
        $pedido->loadMissing(['detalles.presentacion', 'detalles.insumoPlantaReferencia', 'puntoVenta']);
        $detalle = $pedido->detalles->first();

        if ($detalle === null || $detalle->es_solicitud_custom) {
            return null;
        }

        $pdv = $pedido->puntoVenta;
        if ($pdv === null) {
            return null;
        }

        [$latPdv, $lngPdv] = $this->coordsPuntoVenta($pdv);
        $nombreProducto = $this->nombreProductoDetalle($detalle);
        $presentacionNombre = $detalle->presentacion?->nombre;
        $cantidadUnidades = (float) $detalle->cantidad;

        $candidatos = $this->candidatosConStock($nombreProducto, $presentacionNombre, $cantidadUnidades);

        if ($pedido->almacen_mayorista_origenid) {
            $candidatos = $candidatos->filter(
                fn (array $item) => (int) $item['almacen']->almacenid === (int) $pedido->almacen_mayorista_origenid
            );
        }

        if ($candidatos->isEmpty()) {
            return null;
        }

        $mejor = $candidatos
            ->map(function (array $item) use ($latPdv, $lngPdv) {
                $coords = UbicacionGpsParser::resolverAlmacen(
                    (int) $item['almacen']->almacenid,
                    $item['almacen']->nombre,
                    $item['almacen']->ubicacion
                );
                $item['distancia_m'] = $this->haversineMetros($latPdv, $lngPdv, $coords['lat'], $coords['lng']);

                return $item;
            })
            ->sortBy('distancia_m')
            ->first();

        return $mejor ?: null;
    }

    /**
     * @return Collection<int, array{almacen: Almacen, insumo: Insumo, presentacion: InsumoPresentacion|null}>
     */
    private function candidatosConStock(string $nombreProducto, ?string $presentacionNombre, float $cantidadUnidades): Collection
    {
        $almacenes = AlmacenAmbito::scope(Almacen::query()->where('activo', true), AlmacenAmbito::MAYORISTA)->get();
        $nombreNorm = Str::lower(trim($nombreProducto));
        $out = collect();

        foreach ($almacenes as $almacen) {
            $insumo = InsumoCatalogo::aplicarFiltroProductoTerminado(
                Insumo::query()
                    ->where('almacenid', $almacen->almacenid)
                    ->whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNorm])
            )->first();

            if ($insumo === null) {
                continue;
            }

            if ($presentacionNombre !== null && $presentacionNombre !== '') {
                $presentacion = InsumoPresentacion::query()
                    ->where('insumoid', $insumo->insumoid)
                    ->where('activo', true)
                    ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($presentacionNombre))])
                    ->first();

                if ($presentacion === null) {
                    continue;
                }

                $this->inventarioPresentacion->asegurarInventarioDesdeStock((int) $almacen->almacenid, (int) $insumo->insumoid);
                $stock = $this->inventarioPresentacion->stockTotalUnidades(
                    (int) $almacen->almacenid,
                    (int) $presentacion->insumo_presentacionid
                );

                if ($stock < $cantidadUnidades) {
                    $necesitaKg = $cantidadUnidades * $presentacion->pesoNetoKg();
                    if ((float) $insumo->stock < $necesitaKg) {
                        continue;
                    }
                }

                $out->push([
                    'almacen' => $almacen,
                    'insumo' => $insumo,
                    'presentacion' => $presentacion,
                ]);

                continue;
            }

            if ((float) $insumo->stock < $cantidadUnidades) {
                continue;
            }

            $out->push([
                'almacen' => $almacen,
                'insumo' => $insumo,
                'presentacion' => null,
            ]);
        }

        return $out;
    }

    private function nombreProductoDetalle(DetallePedidoDistribucion $detalle): string
    {
        if ($detalle->insumoPlantaReferencia) {
            return $detalle->insumoPlantaReferencia->nombre;
        }

        if ($detalle->insumo) {
            return $detalle->insumo->nombre;
        }

        if ($detalle->producto_nombre && str_contains($detalle->producto_nombre, ' · ')) {
            return explode(' · ', $detalle->producto_nombre, 2)[0];
        }

        return $detalle->producto_nombre ?: 'Producto';
    }

    /** @return array{0: float, 1: float} */
    private function coordsPuntoVenta(PuntoVenta $pdv): array
    {
        if ($pdv->latitud !== null && $pdv->longitud !== null) {
            return [(float) $pdv->latitud, (float) $pdv->longitud];
        }

        $fallback = UbicacionGpsParser::fallbackSantaCruz((int) $pdv->puntoventaid, $pdv->nombre);

        return [$fallback['lat'], $fallback['lng']];
    }

    private function haversineMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
