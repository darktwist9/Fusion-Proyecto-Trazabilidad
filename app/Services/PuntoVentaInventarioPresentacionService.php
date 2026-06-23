<?php

namespace App\Services;

use App\Models\DetallePedidoDistribucion;
use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Models\PuntoVenta;
use App\Support\PedidoDistribucionCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PuntoVentaInventarioPresentacionService
{
    /**
     * Líneas de inventario PDV con unidades de empaque y equivalente en kg.
     *
     * @param  Collection<int, PuntoVenta>  $puntos
     * @return Collection<int, array{
     *     insumo: Insumo,
     *     punto: PuntoVenta,
     *     producto_nombre: string,
     *     presentacion_nombre: string,
     *     unidades: float,
     *     unidad_etiqueta: string,
     *     kg: float,
     *     etiqueta_stock: string
     * }>
     */
    public function lineasParaPuntos(Collection $puntos, ?string $terminoBusqueda = null): Collection
    {
        if ($puntos->isEmpty()) {
            return collect();
        }

        $puntoIds = $puntos->pluck('puntoventaid')->filter()->values()->all();
        $almacenIds = $puntos->pluck('almacenid')->filter()->values()->all();

        $detalles = DetallePedidoDistribucion::query()
            ->with([
                'presentacion.tipoEmpaque',
                'insumo.unidadMedida',
                'pedido' => fn ($q) => $q->with('puntoVenta'),
            ])
            ->whereHas('pedido', function ($q) use ($puntoIds) {
                $q->whereIn('puntoventaid', $puntoIds)
                    ->where('estado', PedidoDistribucionCatalogo::ESTADO_RECIBIDO);
            })
            ->orderByDesc('detallepedidodistribucionid')
            ->get();

        $lineas = collect();
        $clavesUsadas = [];

        foreach ($detalles as $detalle) {
            $punto = $detalle->pedido?->puntoVenta;
            if ($punto === null || ! $punto->almacenid) {
                continue;
            }

            $presentacion = $this->resolverPresentacion($detalle);
            $nombreBase = $this->nombreBaseProducto($detalle);
            $clave = $punto->puntoventaid.'|'.($presentacion?->insumo_presentacionid ?? 0).'|'.Str::lower($nombreBase);

            if (isset($clavesUsadas[$clave])) {
                continue;
            }

            $insumoPdv = $this->resolverInsumoPdv($punto, $detalle, $nombreBase);
            $kg = (float) ($insumoPdv?->stock ?? 0);
            if ($insumoPdv === null || $kg <= 0) {
                continue;
            }
            $pesoNeto = $presentacion?->pesoNetoKg() ?? 0;
            $unidades = $presentacion && $pesoNeto > 0
                ? round($kg / $pesoNeto, fmod($kg / $pesoNeto, 1.0) === 0.0 ? 0 : 2)
                : (float) $detalle->cantidad;
            $unidadEtiqueta = $presentacion?->etiquetaUnidad() ?? 'unidades';

            $linea = [
                'insumo' => $insumoPdv,
                'punto' => $punto,
                'producto_nombre' => $nombreBase,
                'presentacion_nombre' => $presentacion?->nombre ?? '—',
                'unidades' => $unidades,
                'unidad_etiqueta' => $unidadEtiqueta,
                'kg' => $kg,
                'etiqueta_stock' => $this->etiquetaStock($unidades, $unidadEtiqueta, $kg),
            ];

            if ($this->coincideBusqueda($linea, $terminoBusqueda)) {
                $lineas->push($linea);
                $clavesUsadas[$clave] = true;
            }
        }

        $insumosSinDetalle = Insumo::query()
            ->with(['unidadMedida', 'almacen'])
            ->whereIn('almacenid', $almacenIds)
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Insumo $insumo) => ! $lineas->contains(
                fn (array $row) => (int) $row['insumo']->insumoid === (int) $insumo->insumoid
            ));

        foreach ($insumosSinDetalle as $insumo) {
            $punto = $puntos->firstWhere('almacenid', $insumo->almacenid);
            if ($punto === null) {
                continue;
            }

            $inferida = $this->inferirDesdePresentacionProducto($insumo);
            $nombreProducto = str_contains($insumo->nombre, ' · ')
                ? trim(explode(' · ', $insumo->nombre, 2)[0])
                : $insumo->nombre;
            $linea = [
                'insumo' => $insumo,
                'punto' => $punto,
                'producto_nombre' => $nombreProducto,
                'presentacion_nombre' => $inferida['presentacion_nombre'],
                'unidades' => $inferida['unidades'],
                'unidad_etiqueta' => $inferida['unidad_etiqueta'],
                'kg' => (float) $insumo->stock,
                'etiqueta_stock' => $inferida['etiqueta_stock'],
            ];

            if ($this->coincideBusqueda($linea, $terminoBusqueda)) {
                $lineas->push($linea);
            }
        }

        return $lineas->sortBy(fn (array $row) => $row['producto_nombre'].'|'.$row['presentacion_nombre'])->values();
    }

    /** @return array{presentacion_nombre: string, unidades: float, unidad_etiqueta: string, etiqueta_stock: string} */
    private function inferirDesdePresentacionProducto(Insumo $insumo): array
    {
        $nombreInsumo = trim($insumo->nombre);
        $presentacion = null;

        if (str_contains($nombreInsumo, ' · ')) {
            [, $presNombre] = explode(' · ', $nombreInsumo, 2);
            $presentacion = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($presNombre))])
                ->where('activo', true)
                ->first();
        }

        if ($presentacion === null) {
            $presentacion = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->where('insumoid', $insumo->insumoid)
                ->where('activo', true)
                ->orderByDesc('peso_neto_kg')
                ->first();
        }

        if ($presentacion === null) {
            $presentacion = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->whereHas('insumo', fn ($q) => $q->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($nombreInsumo))]))
                ->where('activo', true)
                ->orderBy('peso_neto_kg')
                ->first();
        }

        if ($presentacion === null) {
            $kg = (float) $insumo->stock;

            return [
                'presentacion_nombre' => '—',
                'unidades' => $kg,
                'unidad_etiqueta' => $insumo->unidadMedida?->abreviatura ?? 'kg',
                'etiqueta_stock' => number_format($kg, 2).' kg',
            ];
        }

        $peso = $presentacion->pesoNetoKg();
        $unidades = $peso > 0 ? round((float) $insumo->stock / $peso, 0) : 0;
        $unidadEtiqueta = $presentacion->etiquetaUnidad();

        return [
            'presentacion_nombre' => $presentacion->nombre,
            'unidades' => $unidades,
            'unidad_etiqueta' => $unidadEtiqueta,
            'etiqueta_stock' => $this->etiquetaStock($unidades, $unidadEtiqueta, (float) $insumo->stock),
        ];
    }

    private function resolverInsumoPdv(PuntoVenta $punto, DetallePedidoDistribucion $detalle, string $nombreBase): ?Insumo
    {
        $candidatos = array_values(array_filter([
            $detalle->producto_nombre,
            $nombreBase,
            $detalle->insumo?->nombre,
        ], fn ($nombre) => filled($nombre)));

        foreach ($candidatos as $nombre) {
            $insumo = Insumo::query()
                ->where('almacenid', $punto->almacenid)
                ->where(function ($q) use ($nombre, $nombreBase) {
                    $q->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($nombre))])
                        ->orWhereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($nombreBase))])
                        ->orWhereRaw('LOWER(TRIM(nombre)) LIKE ?', [Str::lower(trim($nombreBase)).' · %']);
                })
                ->first();

            if ($insumo !== null) {
                return $insumo;
            }
        }

        return null;
    }

    private function resolverPresentacion(DetallePedidoDistribucion $detalle): ?InsumoPresentacion
    {
        $detalle->loadMissing('presentacion.tipoEmpaque');

        if ($detalle->presentacion) {
            return $detalle->presentacion;
        }

        if ($detalle->insumo_presentacionid) {
            return InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->find($detalle->insumo_presentacionid);
        }

        $producto = trim((string) $detalle->producto_nombre);
        if (str_contains($producto, ' · ')) {
            [, $presNombre] = explode(' · ', $producto, 2);
            $nombreBase = trim(explode(' · ', $producto, 2)[0]);

            $presentacion = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->whereHas('insumo', fn ($q) => $q->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower($nombreBase)]))
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($presNombre))])
                ->where('activo', true)
                ->first();

            if ($presentacion) {
                return $presentacion;
            }
        }

        if (filled($detalle->tipo_envase) && $detalle->insumoid) {
            return InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->where('insumoid', $detalle->insumoid)
                ->where('tipo_envase', $detalle->tipo_envase)
                ->where('activo', true)
                ->orderBy('peso_neto_kg')
                ->first();
        }

        return null;
    }

    private function nombreBaseProducto(DetallePedidoDistribucion $detalle): string
    {
        $producto = trim((string) $detalle->producto_nombre);
        if (str_contains($producto, ' · ')) {
            return trim(explode(' · ', $producto, 2)[0]);
        }

        return $producto !== '' ? $producto : (string) ($detalle->insumo?->nombre ?? 'Producto');
    }

    private function etiquetaStock(float $unidades, string $unidadEtiqueta, float $kg): string
    {
        $u = number_format($unidades, fmod($unidades, 1.0) === 0.0 ? 0 : 2);

        return $u.' '.$unidadEtiqueta.' · '.number_format($kg, 2).' kg';
    }

    /** @param  array<string, mixed>  $linea */
    private function coincideBusqueda(array $linea, ?string $termino): bool
    {
        if ($termino === null || trim($termino) === '') {
            return true;
        }

        $term = Str::lower(trim($termino));
        $haystack = Str::lower(implode(' ', [
            $linea['producto_nombre'] ?? '',
            $linea['presentacion_nombre'] ?? '',
            $linea['insumo']->codigo_trazabilidad ?? '',
        ]));

        return str_contains($haystack, $term);
    }
}
