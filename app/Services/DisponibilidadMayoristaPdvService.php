<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\Insumo;
use App\Models\InsumoPresentacion;
use App\Support\AlmacenAmbito;
use App\Support\InsumoCatalogo;
use App\Support\UbicacionGpsParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DisponibilidadMayoristaPdvService
{
    public function __construct(
        private readonly InventarioPresentacionService $inventarioPresentacion
    ) {}

    /**
     * Catálogo PDV: una fila por producto terminado en cada almacén mayorista.
     * El minorista solo ve lo que existe en mayorista y elige a quién pedir.
     *
     * @return Collection<int, array{
     *     catalogo_id: string,
     *     insumoid: int,
     *     almacen_mayorista_origenid: int,
     *     nombre: string,
     *     label: string,
     *     mayorista_nombre: string,
     *     almacen_nombre: string,
     *     ubicacion: string,
     *     stock_kg: float,
     *     stock_etiqueta: string,
     *     presentaciones_count: int
     * }>
     */
    public function productosConStock(): Collection
    {
        $almacenes = AlmacenAmbito::scope(
            Almacen::query()->where('activo', true)->with(['responsable', 'usuarios']),
            AlmacenAmbito::MAYORISTA
        )->orderBy('nombre')->get();

        if ($almacenes->isEmpty()) {
            return collect();
        }

        $ofertas = collect();

        foreach ($almacenes as $almacen) {
            $insumos = InsumoCatalogo::aplicarFiltroProductoTerminado(
                Insumo::query()
                    ->with('unidadMedida')
                    ->where('almacenid', $almacen->almacenid)
            )->orderBy('nombre')->get();

            foreach ($insumos as $insumo) {
                $ofertas->push($this->filaOfertaMayorista($almacen, $insumo));
            }
        }

        return $ofertas
            ->sortBy(fn (array $row) => [$row['stock_kg'] > 0 ? 0 : 1, $row['nombre'], $row['mayorista_nombre']])
            ->values();
    }

    /** @return array<string, mixed> */
    private function filaOfertaMayorista(Almacen $almacen, Insumo $insumo): array
    {
        $nombreNorm = $this->normalizarNombre($insumo->nombre);
        $stockKg = (float) $insumo->stock;
        $unidad = $insumo->unidadMedida?->abreviatura ?? 'kg';
        $mayoristaNombre = $this->nombreMayoristaAlmacen($almacen);
        $resuelto = UbicacionGpsParser::resolverAlmacen(
            (int) $almacen->almacenid,
            $almacen->nombre,
            $almacen->ubicacion
        );
        $direccionVisible = UbicacionGpsParser::textoDireccionVisible(
            $almacen->ubicacion,
            $almacen->nombre,
            (int) $almacen->almacenid
        );

        return [
            'catalogo_id' => (string) $insumo->insumoid,
            'insumoid' => (int) $insumo->insumoid,
            'almacen_mayorista_origenid' => (int) $almacen->almacenid,
            'nombre' => $insumo->nombre,
            'label' => $this->etiquetaOfertaCatalogo($insumo->nombre, $mayoristaNombre, $direccionVisible),
            'mayorista_nombre' => $mayoristaNombre,
            'almacen_nombre' => $almacen->nombre,
            'ubicacion' => $direccionVisible ?? '',
            'lat' => $resuelto['lat'],
            'lng' => $resuelto['lng'],
            'stock_kg' => $stockKg,
            'stock_etiqueta' => $stockKg > 0
                ? number_format($stockKg, 2).' '.$unidad.' en este mayorista'
                : 'Sin stock en este mayorista (puede solicitar)',
            'presentaciones_count' => $this->contarPresentacionesCatalogo(
                (int) $insumo->insumoid,
                $nombreNorm,
                (int) $almacen->almacenid
            ),
        ];
    }

    private function etiquetaOfertaCatalogo(string $producto, string $mayorista, ?string $ubicacion): string
    {
        $partes = [$producto, $mayorista];
        $ubicacion = trim((string) $ubicacion);
        if ($ubicacion !== '') {
            $partes[] = $ubicacion;
        }

        return implode(' · ', $partes);
    }

    private function nombreMayoristaAlmacen(Almacen $almacen): string
    {
        if ($almacen->relationLoaded('responsable') && $almacen->responsable) {
            return trim($almacen->responsable->nombre.' '.$almacen->responsable->apellido);
        }

        $usuario = $almacen->relationLoaded('usuarios')
            ? $almacen->usuarios->first(fn ($u) => (bool) $u->activo)
            : $almacen->usuarios()->where('activo', true)->first();

        if ($usuario) {
            return trim($usuario->nombre.' '.$usuario->apellido);
        }

        return $almacen->nombre;
    }

    /**
     * Todas las presentaciones activas del producto en red mayorista (incluye sin stock).
     *
     * @return Collection<int, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float, stock_etiqueta: string, unidad: string, tiene_stock: bool, presentacion_nombre: string}>
     */
    public function presentacionesParaSolicitud(int $insumoId, ?int $almacenMayoristaId = null): Collection
    {
        $insumo = Insumo::query()->with(['unidadMedida', 'almacen'])->find($insumoId);
        if ($insumo === null) {
            return collect();
        }

        $nombreNorm = $this->normalizarNombre($insumo->nombre);

        if ($almacenMayoristaId !== null && $almacenMayoristaId > 0) {
            $insumosGrupo = collect([$insumo])->filter(
                fn (Insumo $i) => (int) $i->almacenid === $almacenMayoristaId
                    && $i->almacen?->ambito === AlmacenAmbito::MAYORISTA
            );

            if ($insumosGrupo->isEmpty()) {
                $insumoEnAlmacen = InsumoCatalogo::aplicarFiltroProductoTerminado(
                    Insumo::query()
                        ->with(['unidadMedida', 'almacen'])
                        ->where('almacenid', $almacenMayoristaId)
                        ->whereRaw('LOWER(TRIM(nombre)) = ?', [$nombreNorm])
                )->first();

                $insumosGrupo = $insumoEnAlmacen ? collect([$insumoEnAlmacen]) : collect();
            }
        } else {
            $insumosGrupo = $this->insumosMayoristaTodos()
                ->filter(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);
        }

        if ($insumosGrupo->isEmpty()) {
            return collect();
        }

        foreach ($insumosGrupo as $insumoMay) {
            $this->sincronizarPresentacionesDesdePlanta($insumoMay);
        }

        /** @var array<string, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float}> $acumulado */
        $acumulado = [];

        foreach ($insumosGrupo as $insumoMay) {
            $presentaciones = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();

            foreach ($presentaciones as $pres) {
                $clave = mb_strtolower(trim($pres->nombre));
                $unidades = $this->inventarioPresentacion->stockTotalUnidades(
                    (int) $insumoMay->almacenid,
                    (int) $pres->insumo_presentacionid
                );
                $kg = $this->inventarioPresentacion->stockTotalKg(
                    (int) $insumoMay->almacenid,
                    (int) $pres->insumo_presentacionid
                );

                if (! isset($acumulado[$clave])) {
                    $acumulado[$clave] = [
                        'presentacion' => $pres,
                        'insumo_presentacionid' => (int) $pres->insumo_presentacionid,
                        'insumoid' => (int) $insumoMay->insumoid,
                        'stock_unidades' => 0.0,
                        'stock_kg' => 0.0,
                    ];
                }

                $acumulado[$clave]['stock_unidades'] += $unidades;
                $acumulado[$clave]['stock_kg'] += $kg;

                if ($unidades > 0) {
                    $acumulado[$clave]['insumo_presentacionid'] = (int) $pres->insumo_presentacionid;
                    $acumulado[$clave]['insumoid'] = (int) $insumoMay->insumoid;
                    $acumulado[$clave]['presentacion'] = $pres;
                }
            }
        }

        return collect($acumulado)
            ->map(function (array $row) {
                $pres = $row['presentacion'];
                $unidad = $pres->etiquetaUnidad();
                $u = (float) $row['stock_unidades'];
                $kg = (float) $row['stock_kg'];
                $tieneStock = $u > 0;

                return [
                    'presentacion' => $pres,
                    'presentacion_nombre' => $pres->nombre,
                    'insumo_presentacionid' => $row['insumo_presentacionid'],
                    'insumoid' => $row['insumoid'],
                    'stock_unidades' => $u,
                    'stock_kg' => $kg,
                    'unidad' => $unidad,
                    'tiene_stock' => $tieneStock,
                    'stock_etiqueta' => $tieneStock
                        ? number_format($u, 0).' '.$unidad.' · '.number_format($kg, 2).' kg'
                        : 'Sin stock actual',
                ];
            })
            ->sortBy(fn (array $row) => [$row['tiene_stock'] ? 0 : 1, $row['presentacion']->orden])
            ->values();
    }

    public function productoEnCatalogoMayorista(int $insumoId): bool
    {
        $insumo = Insumo::query()->find($insumoId);
        if ($insumo === null) {
            return false;
        }

        $nombreNorm = $this->normalizarNombre($insumo->nombre);

        return $this->insumosMayoristaTodos()->contains(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);
    }

    public function presentacionValidaParaProducto(int $insumoId, int $presentacionId): bool
    {
        $presRef = InsumoPresentacion::query()->find($presentacionId);
        if ($presRef === null || ! $presRef->activo) {
            return false;
        }

        $insumo = Insumo::query()->find($insumoId);
        if ($insumo === null) {
            return false;
        }

        $nombreNorm = $this->normalizarNombre($insumo->nombre);
        $clavePres = mb_strtolower(trim($presRef->nombre));

        foreach ($this->insumosMayoristaTodos()->filter(
            fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm
        ) as $insumoMay) {
            if ((int) $presRef->insumoid === (int) $insumoMay->insumoid) {
                return true;
            }

            $existe = InsumoPresentacion::query()
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [$clavePres])
                ->exists();

            if ($existe) {
                return true;
            }
        }

        $insumoPlanta = $this->insumosPlantaTerminados()
            ->first(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);

        if ($insumoPlanta !== null) {
            return InsumoPresentacion::query()
                ->where('insumoid', $insumoPlanta->insumoid)
                ->where('insumo_presentacionid', $presentacionId)
                ->where('activo', true)
                ->exists()
                || InsumoPresentacion::query()
                    ->where('insumoid', $insumoPlanta->insumoid)
                    ->where('activo', true)
                    ->whereRaw('LOWER(TRIM(nombre)) = ?', [$clavePres])
                    ->exists();
        }

        return false;
    }

    /** Stock empaquetado real (sin estimar desde kg a granel). */
    public function stockUnidadesReales(int $insumoId, int $presentacionId): float
    {
        $pres = InsumoPresentacion::query()->find($presentacionId);
        if ($pres === null) {
            return 0.0;
        }

        $nombreNorm = $this->normalizarNombre(
            Insumo::query()->where('insumoid', $insumoId)->value('nombre') ?? ''
        );

        $total = 0.0;
        foreach ($this->insumosMayoristaTodos()->filter(
            fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm
        ) as $insumoMay) {
            $presMay = InsumoPresentacion::query()
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($pres->nombre))])
                ->first();

            if ($presMay === null) {
                continue;
            }

            $total += $this->inventarioPresentacion->stockTotalUnidades(
                (int) $insumoMay->almacenid,
                (int) $presMay->insumo_presentacionid
            );
        }

        return $total;
    }

    public function necesitaEsperaStock(int $insumoId, int $presentacionId, float $cantidad): bool
    {
        return $this->stockUnidadesReales($insumoId, $presentacionId) < $cantidad;
    }

    /**
     * Presentaciones con unidades disponibles para un producto mayorista.
     *
     * @return Collection<int, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float, stock_etiqueta: string, unidad: string}>
     */
    public function presentacionesDisponibles(int $insumoId): Collection
    {
        $insumo = Insumo::query()->with('unidadMedida')->find($insumoId);
        if ($insumo === null) {
            return collect();
        }

        $nombreNorm = $this->normalizarNombre($insumo->nombre);
        $insumosGrupo = $this->insumosMayoristaTodos()
            ->filter(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);

        /** @var array<string, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float}> $acumulado */
        $acumulado = [];

        foreach ($insumosGrupo as $insumoMay) {
            $this->inventarioPresentacion->asegurarInventarioDesdeStock((int) $insumoMay->almacenid, (int) $insumoMay->insumoid);

            $presentaciones = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();

            foreach ($presentaciones as $pres) {
                $clave = mb_strtolower(trim($pres->nombre));
                $unidades = $this->inventarioPresentacion->stockTotalUnidades(
                    (int) $insumoMay->almacenid,
                    (int) $pres->insumo_presentacionid
                );
                $kg = $this->inventarioPresentacion->stockTotalKg(
                    (int) $insumoMay->almacenid,
                    (int) $pres->insumo_presentacionid
                );

                if ($unidades <= 0 && $kg <= 0) {
                    continue;
                }

                if (! isset($acumulado[$clave])) {
                    $acumulado[$clave] = [
                        'presentacion' => $pres,
                        'insumo_presentacionid' => (int) $pres->insumo_presentacionid,
                        'insumoid' => (int) $insumoMay->insumoid,
                        'stock_unidades' => 0.0,
                        'stock_kg' => 0.0,
                    ];
                }

                $acumulado[$clave]['stock_unidades'] += $unidades;
                $acumulado[$clave]['stock_kg'] += $kg;

                if ($unidades > 0) {
                    $acumulado[$clave]['insumo_presentacionid'] = (int) $pres->insumo_presentacionid;
                    $acumulado[$clave]['insumoid'] = (int) $insumoMay->insumoid;
                    $acumulado[$clave]['presentacion'] = $pres;
                }
            }
        }

        return collect($acumulado)
            ->map(function (array $row) {
                $pres = $row['presentacion'];
                $unidad = $pres->etiquetaUnidad();
                $u = (float) $row['stock_unidades'];
                $kg = (float) $row['stock_kg'];

                return [
                    'presentacion' => $pres,
                    'insumo_presentacionid' => $row['insumo_presentacionid'],
                    'insumoid' => $row['insumoid'],
                    'stock_unidades' => $u,
                    'stock_kg' => $kg,
                    'unidad' => $unidad,
                    'stock_etiqueta' => $u > 0
                        ? number_format($u, 0).' '.$unidad.' · '.number_format($kg, 2).' kg'
                        : number_format($kg, 2).' kg',
                ];
            })
            ->filter(fn (array $row) => $row['stock_unidades'] > 0 || $row['stock_kg'] > 0)
            ->whenEmpty(function () use ($insumosGrupo) {
                return $this->presentacionesEstimadasDesdeStockGrupo($insumosGrupo);
            })
            ->sortBy(fn (array $row) => $row['presentacion']->orden)
            ->values();
    }

    /**
     * Si no hay inventario por presentación pero sí stock en kg, estima unidades por peso neto.
     *
     * @param  Collection<int, Insumo>  $insumosGrupo
     * @return Collection<int, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float, stock_etiqueta: string, unidad: string}>
     */
    private function presentacionesEstimadasDesdeStockGrupo(Collection $insumosGrupo): Collection
    {
        /** @var array<string, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float}> $acumulado */
        $acumulado = [];

        foreach ($insumosGrupo as $insumoMay) {
            $stockKg = (float) $insumoMay->stock;
            if ($stockKg <= 0) {
                continue;
            }

            $presentaciones = InsumoPresentacion::query()
                ->with('tipoEmpaque')
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->orderBy('orden')
                ->get();

            if ($presentaciones->isEmpty()) {
                continue;
            }

            $shareKg = $stockKg / $presentaciones->count();

            foreach ($presentaciones as $pres) {
                $peso = $pres->pesoNetoKg();
                if ($peso <= 0) {
                    continue;
                }

                $unidades = (int) floor($shareKg / $peso);
                if ($unidades <= 0) {
                    continue;
                }

                $clave = mb_strtolower(trim($pres->nombre));
                $kg = $unidades * $peso;

                if (! isset($acumulado[$clave])) {
                    $acumulado[$clave] = [
                        'presentacion' => $pres,
                        'insumo_presentacionid' => (int) $pres->insumo_presentacionid,
                        'insumoid' => (int) $insumoMay->insumoid,
                        'stock_unidades' => 0.0,
                        'stock_kg' => 0.0,
                    ];
                }

                $acumulado[$clave]['stock_unidades'] += $unidades;
                $acumulado[$clave]['stock_kg'] += $kg;

                if ($unidades > 0) {
                    $acumulado[$clave]['insumo_presentacionid'] = (int) $pres->insumo_presentacionid;
                    $acumulado[$clave]['insumoid'] = (int) $insumoMay->insumoid;
                    $acumulado[$clave]['presentacion'] = $pres;
                }
            }
        }

        return collect($acumulado)
            ->map(function (array $row) {
                $pres = $row['presentacion'];
                $unidad = $pres->etiquetaUnidad();
                $u = (float) $row['stock_unidades'];
                $kg = (float) $row['stock_kg'];

                return [
                    'presentacion' => $pres,
                    'insumo_presentacionid' => $row['insumo_presentacionid'],
                    'insumoid' => $row['insumoid'],
                    'stock_unidades' => $u,
                    'stock_kg' => $kg,
                    'unidad' => $unidad,
                    'stock_etiqueta' => number_format($u, 0).' '.$unidad.' · '.number_format($kg, 2).' kg',
                ];
            })
            ->filter(fn (array $row) => $row['stock_unidades'] > 0)
            ->values();
    }

    public function stockUnidades(int $insumoId, int $presentacionId): float
    {
        $pres = InsumoPresentacion::query()->find($presentacionId);
        if ($pres === null) {
            return 0.0;
        }

        $nombreNorm = $this->normalizarNombre(
            Insumo::query()->where('insumoid', $insumoId)->value('nombre') ?? ''
        );

        $total = 0.0;
        foreach ($this->insumosMayoristaTodos()->filter(
            fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm
        ) as $insumoMay) {
            $presMay = InsumoPresentacion::query()
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($pres->nombre))])
                ->first();

            if ($presMay === null) {
                continue;
            }

            $this->inventarioPresentacion->asegurarInventarioDesdeStock((int) $insumoMay->almacenid, (int) $insumoMay->insumoid);
            $total += $this->inventarioPresentacion->stockTotalUnidades(
                (int) $insumoMay->almacenid,
                (int) $presMay->insumo_presentacionid
            );
        }

        return $total;
    }

    /**
     * @return array{insumo: Insumo, presentacion: InsumoPresentacion, almacen: Almacen}
     */
    public function resolverOrigenStock(int $insumoId, int $presentacionId, float $cantidad): array
    {
        $disponible = $this->stockUnidades($insumoId, $presentacionId);
        if ($cantidad > $disponible) {
            $pres = InsumoPresentacion::query()->find($presentacionId);
            $unidad = $pres?->etiquetaUnidad() ?? 'unidades';
            throw new InvalidArgumentException(
                'Solo hay '.number_format($disponible, 0).' '.$unidad.' disponibles en almacén mayorista.'
            );
        }

        $insumoRef = Insumo::query()->with('almacen')->findOrFail($insumoId);
        $presRef = InsumoPresentacion::query()->findOrFail($presentacionId);
        $nombreNorm = $this->normalizarNombre($insumoRef->nombre);

        foreach ($this->insumosMayoristaTodos()->filter(
            fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm
        ) as $insumoMay) {
            $presMay = InsumoPresentacion::query()
                ->where('insumoid', $insumoMay->insumoid)
                ->where('activo', true)
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($presRef->nombre))])
                ->first();

            if ($presMay === null) {
                continue;
            }

            $this->inventarioPresentacion->asegurarInventarioDesdeStock((int) $insumoMay->almacenid, (int) $insumoMay->insumoid);
            $stock = $this->inventarioPresentacion->stockTotalUnidades(
                (int) $insumoMay->almacenid,
                (int) $presMay->insumo_presentacionid
            );

            if ($stock >= $cantidad) {
                $insumoMay->load('almacen');

                return [
                    'insumo' => $insumoMay,
                    'presentacion' => $presMay,
                    'almacen' => $insumoMay->almacen ?? Almacen::query()->findOrFail($insumoMay->almacenid),
                ];
            }
        }

        throw new InvalidArgumentException('No se encontró un almacén mayorista con stock suficiente.');
    }

    /** @return Collection<int, Insumo> */
    private function insumosMayoristaConStock(): Collection
    {
        return $this->insumosMayoristaTodos()->filter(fn (Insumo $i) => (float) $i->stock > 0)->values();
    }

    /** @return Collection<int, Insumo> */
    private function insumosMayoristaTodos(): Collection
    {
        $almacenIds = AlmacenAmbito::scope(Almacen::query()->where('activo', true), AlmacenAmbito::MAYORISTA)
            ->pluck('almacenid');

        if ($almacenIds->isEmpty()) {
            return collect();
        }

        return InsumoCatalogo::aplicarFiltroProductoTerminado(
            Insumo::query()
                ->with(['unidadMedida', 'almacen'])
                ->whereIn('almacenid', $almacenIds)
        )->orderBy('nombre')->get();
    }

    /** @return Collection<int, Insumo> */
    private function insumosPlantaTerminados(): Collection
    {
        $almacenIds = AlmacenAmbito::scope(Almacen::query()->where('activo', true), AlmacenAmbito::PLANTA)
            ->pluck('almacenid');

        if ($almacenIds->isEmpty()) {
            return collect();
        }

        return InsumoCatalogo::aplicarFiltroProductoTerminado(
            Insumo::query()
                ->with(['unidadMedida', 'presentaciones'])
                ->whereIn('almacenid', $almacenIds)
        )->orderBy('nombre')->get();
    }

    private function contarPresentacionesCatalogo(int $insumoId, string $nombreNorm, ?int $almacenMayoristaId = null): int
    {
        $count = $this->presentacionesParaSolicitud($insumoId, $almacenMayoristaId)->count();
        if ($count > 0) {
            return $count;
        }

        $planta = $this->insumosPlantaTerminados()
            ->first(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);

        return $planta?->presentaciones?->where('activo', true)->count() ?? 0;
    }

    /**
     * @return Collection<int, array{presentacion: InsumoPresentacion, insumo_presentacionid: int, insumoid: int, stock_unidades: float, stock_kg: float, stock_etiqueta: string, unidad: string, tiene_stock: bool, presentacion_nombre: string}>
     */
    private function presentacionesDesdePlanta(Insumo $insumo): Collection
    {
        $nombreNorm = $this->normalizarNombre($insumo->nombre);
        $insumoPlanta = $this->insumosPlantaTerminados()
            ->first(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm)
            ?? $insumo;

        $insumoPlanta->loadMissing(['presentaciones' => fn ($q) => $q->where('activo', true)->orderBy('orden')]);

        return $insumoPlanta->presentaciones->map(function (InsumoPresentacion $pres) use ($insumoPlanta) {
            $unidad = $pres->etiquetaUnidad();

            return [
                'presentacion' => $pres,
                'presentacion_nombre' => $pres->nombre,
                'insumo_presentacionid' => (int) $pres->insumo_presentacionid,
                'insumoid' => (int) $insumoPlanta->insumoid,
                'stock_unidades' => 0.0,
                'stock_kg' => 0.0,
                'unidad' => $unidad,
                'tiene_stock' => false,
                'stock_etiqueta' => 'Sin stock actual',
            ];
        })->values();
    }

    private function sincronizarPresentacionesDesdePlanta(Insumo $insumoMay): void
    {
        $tieneActivas = InsumoPresentacion::query()
            ->where('insumoid', $insumoMay->insumoid)
            ->where('activo', true)
            ->exists();

        if ($tieneActivas) {
            return;
        }

        $nombreNorm = $this->normalizarNombre($insumoMay->nombre);
        $insumoPlanta = $this->insumosPlantaTerminados()
            ->first(fn (Insumo $i) => $this->normalizarNombre($i->nombre) === $nombreNorm);

        if ($insumoPlanta !== null) {
            $insumoPlanta->loadMissing(['presentaciones' => fn ($q) => $q->where('activo', true)->orderBy('orden')]);
            foreach ($insumoPlanta->presentaciones as $presPlanta) {
                InsumoPresentacion::query()->firstOrCreate(
                    [
                        'insumoid' => $insumoMay->insumoid,
                        'nombre' => $presPlanta->nombre,
                    ],
                    [
                        'tipo_envase' => $presPlanta->tipo_envase,
                        'peso_neto_kg' => $presPlanta->peso_neto_kg,
                        'orden' => $presPlanta->orden,
                        'activo' => true,
                    ]
                );
            }

            return;
        }

        if ((float) $insumoMay->stock <= 0) {
            return;
        }

        InsumoPresentacion::query()->firstOrCreate(
            [
                'insumoid' => $insumoMay->insumoid,
                'nombre' => 'Bolsa 200 g',
            ],
            [
                'tipo_envase' => 'bolsa',
                'peso_neto_kg' => 0.2,
                'orden' => 1,
                'activo' => true,
            ]
        );
    }

    private function normalizarNombre(string $nombre): string
    {
        return Str::lower(trim($nombre));
    }
}
