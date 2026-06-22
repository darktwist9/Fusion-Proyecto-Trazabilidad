<?php

namespace App\Support;

use App\Models\Almacen;
use App\Models\AlmacenMovimiento;
use App\Models\EnvioAsignacionMultiple;
use App\Models\Insumo;
use App\Models\ProduccionAlmacenamiento;
use App\Services\AlmacenCapacidadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class AlmacenPlantaCosechaCatalogo
{
    public static function esRecepcionPedidoInsumo(Insumo $insumo): bool
    {
        return str_starts_with(trim((string) ($insumo->descripcion ?? '')), 'Recepción pedido');
    }

    public static function claveCultivo(string $nombre, ?string $cultivo = null): string
    {
        $base = trim((string) ($cultivo ?? ''));
        if ($base === '') {
            $base = trim(explode('·', $nombre)[0] ?? $nombre);
        }

        return Str::lower(preg_replace('/\s+/u', ' ', $base));
    }

    public static function etiquetaCultivo(string $nombre, ?string $clave = null): string
    {
        $clave = $clave ?? self::claveCultivo($nombre);

        return mb_convert_case($clave, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  Collection<int, object>  $items
     * @return Collection<int, object>
     */
    public static function consolidarItemsPlanta(Collection $items, Almacen $almacen, string $rutaPrefijo): Collection
    {
        $otros = collect();
        $cosechas = collect();

        foreach ($items as $item) {
            if (($item->categoria ?? '') === 'cosecha') {
                $cosechas->push($item);
            } else {
                $otros->push($item);
            }
        }

        if ($cosechas->isEmpty()) {
            return $items;
        }

        $consolidados = $cosechas
            ->groupBy(fn ($item) => $item->clave_cultivo ?? self::claveCultivo((string) $item->nombre))
            ->map(function (Collection $grupo, string $clave) use ($almacen, $rutaPrefijo) {
                $lineas = $grupo->values();
                $kgTotal = (float) $lineas->sum(fn ($l) => (float) ($l->kg ?? 0));
                $cantTotal = (float) $lineas->sum(fn ($l) => (float) ($l->cantidad ?? 0));
                $ultimaFecha = (int) $lineas->max(fn ($l) => (int) ($l->fecha_orden ?? 0));
                $nombre = self::etiquetaCultivo((string) $lineas->first()->nombre, $clave);
                $conteo = $lineas->count();
                $detalleFecha = $ultimaFecha > 0
                    ? ' · última '.date('d/m/Y', $ultimaFecha)
                    : '';
                $acciones = self::resolverAccionesConsolidadas($lineas, $almacen, $rutaPrefijo, $clave);

                return (object) [
                    'categoria' => 'cosecha_consolidada',
                    'tipo_label' => 'Cosecha',
                    'tipo_filtro' => 'cosecha',
                    'nombre' => $nombre,
                    'clave' => $clave,
                    'detalle' => ($conteo === 1 ? '1 entrada' : $conteo.' entradas').$detalleFecha,
                    'cantidad' => $cantTotal,
                    'unidad' => 'kg',
                    'kg' => $kgTotal,
                    'empaque' => null,
                    'fecha_orden' => $ultimaFecha,
                    'search' => strtolower(trim($nombre.' cosecha '.$clave)),
                    'lineas_count' => $conteo,
                    'accion_ver' => $acciones['ver'],
                    'accion_edit' => $acciones['edit'],
                    'accion_destroy' => $acciones['destroy'],
                    'destroy_es_gestion' => $acciones['destroy_es_gestion'],
                ];
            })
            ->values();

        return $otros->concat($consolidados)->sortByDesc('fecha_orden')->values();
    }

    /**
     * @param  Collection<int, object>  $lineas
     * @return array{ver: string, edit: string, destroy: string, destroy_es_gestion: bool}
     */
    private static function resolverAccionesConsolidadas(
        Collection $lineas,
        Almacen $almacen,
        string $rutaPrefijo,
        string $clave
    ): array {
        $detalle = route($rutaPrefijo.'.cosecha.show', [$almacen, $clave]);
        $gestionar = $detalle.'#gestionar';

        if ($lineas->count() === 1) {
            $linea = $lineas->first();
            if (($linea->origen_tipo ?? '') === 'recepcion_pedido' && ! empty($linea->insumoid)) {
                return [
                    'ver' => $detalle,
                    'edit' => $detalle,
                    'destroy' => route($rutaPrefijo.'.cosecha.destroy-recepcion', [$almacen, $clave, $linea->insumoid]),
                    'destroy_es_gestion' => false,
                ];
            }
            if (! empty($linea->produccionalmacenamientoid)) {
                return [
                    'ver' => $detalle,
                    'edit' => $detalle,
                    'destroy' => route($rutaPrefijo.'.cosecha.destroy-produccion', [$almacen, $clave, $linea->produccionalmacenamientoid]),
                    'destroy_es_gestion' => false,
                ];
            }
        }

        return [
            'ver' => $detalle,
            'edit' => $detalle,
            'destroy' => $gestionar,
            'destroy_es_gestion' => true,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function lineasDetalladas(
        Almacen $almacen,
        string $clave,
        AlmacenCapacidadService $capacidad,
        string $rutaPrefijo = 'almacen-planta'
    ): Collection {
        $lineas = collect();

        ProduccionAlmacenamiento::query()
            ->with(['produccion.lote.cultivo', 'unidadMedida'])
            ->where('almacenid', $almacen->almacenid)
            ->whereNull('fechasalida')
            ->get()
            ->filter(function (ProduccionAlmacenamiento $c) use ($clave) {
                $lote = $c->produccion?->lote;
                $cultivo = trim((string) ($lote?->cultivo?->nombre ?? ''));
                $nombreLote = trim((string) ($lote?->nombre ?? ''));

                return self::claveCultivo(
                    $cultivo !== '' ? $cultivo : $nombreLote,
                    $cultivo !== '' ? $cultivo : null
                ) === $clave;
            })
            ->each(function (ProduccionAlmacenamiento $c) use ($lineas, $capacidad, $almacen, $rutaPrefijo, $clave) {
                $lote = $c->produccion?->lote;
                $cultivo = $lote?->cultivo?->nombre ?? 'Cultivo';
                $kg = $capacidad->convertirAKg((float) $c->cantidad, $c->unidadMedida);
                $fecha = $c->fechaentrada ? \Carbon\Carbon::parse($c->fechaentrada) : null;

                $lineas->push([
                    'tipo' => 'produccion',
                    'titulo' => $cultivo.' · '.($lote?->nombre ?? 'Producción #'.$c->produccionid),
                    'cantidad' => (float) $c->cantidad,
                    'kg' => $kg,
                    'unidad' => $c->unidadMedida?->abreviatura ?? 'kg',
                    'fecha' => $fecha,
                    'origen_etiqueta' => 'Lote agrícola',
                    'origen_detalle' => $lote?->nombre ?? 'Producción en campo',
                    'referencia' => $lote?->nombre,
                    'produccionid' => $c->produccionid,
                    'produccionalmacenamientoid' => $c->produccionalmacenamientoid,
                    'insumoid' => null,
                    'pedidoid' => null,
                    'url_origen' => $c->produccionid ? route('producciones.show', $c->produccionid) : null,
                    'url_edit' => $c->produccionid ? route('producciones.edit', $c->produccionid) : null,
                    'url_destroy' => route($rutaPrefijo.'.cosecha.destroy-produccion', [$almacen, $clave, $c->produccionalmacenamientoid]),
                ]);
            });

        Insumo::query()
            ->with(['unidadMedida'])
            ->where('almacenid', $almacen->almacenid)
            ->where('stock', '>', 0)
            ->where('descripcion', 'like', 'Recepción pedido%')
            ->get()
            ->filter(fn (Insumo $insumo) => self::claveCultivo($insumo->nombre) === $clave)
            ->each(function (Insumo $insumo) use ($lineas, $almacen, $capacidad, $rutaPrefijo, $clave) {
                $kg = $capacidad->convertirAKg((float) $insumo->stock, $insumo->unidadMedida);
                $movimiento = self::ultimoMovimientoRecepcion($insumo);
                $origen = self::resolverOrigenRecepcion($movimiento);
                $fecha = $movimiento?->fecha ?? self::fechaDesdeDescripcionRecepcion($insumo->descripcion);
                $volver = route($rutaPrefijo.'.cosecha.show', [$almacen, $clave]);

                $lineas->push([
                    'tipo' => 'recepcion_pedido',
                    'titulo' => $insumo->nombre,
                    'cantidad' => (float) $insumo->stock,
                    'kg' => $kg,
                    'unidad' => $insumo->unidadMedida?->abreviatura ?? 'kg',
                    'fecha' => $fecha,
                    'origen_etiqueta' => $origen['etiqueta'],
                    'origen_detalle' => $origen['origen'],
                    'referencia' => $origen['referencia'],
                    'produccionid' => null,
                    'produccionalmacenamientoid' => null,
                    'insumoid' => $insumo->insumoid,
                    'pedidoid' => $origen['pedidoid'],
                    'url_origen' => route($rutaPrefijo.'.inventario.show', [$almacen, $insumo]).'?redirect='.urlencode($volver),
                    'url_edit' => route($rutaPrefijo.'.inventario.edit', [$almacen, $insumo]).'?redirect='.urlencode($volver),
                    'url_destroy' => route($rutaPrefijo.'.cosecha.destroy-recepcion', [$almacen, $clave, $insumo]),
                ]);
            });

        return $lineas->sortByDesc(fn (array $l) => $l['fecha']?->timestamp ?? 0)->values();
    }

    public static function ultimoMovimientoRecepcion(Insumo $insumo): ?AlmacenMovimiento
    {
        return AlmacenMovimiento::query()
            ->where('insumoid', $insumo->insumoid)
            ->where('observaciones', 'like', '%Recepción planta%')
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * @return array{etiqueta: string, origen: string, referencia: ?string, pedidoid: ?int, url: ?string}
     */
    public static function resolverOrigenRecepcion(?AlmacenMovimiento $movimiento): array
    {
        if ($movimiento === null) {
            return [
                'etiqueta' => 'Pedido agrícola',
                'origen' => 'Campo / almacén agrícola',
                'referencia' => null,
                'pedidoid' => null,
                'url' => null,
            ];
        }

        $ref = trim((string) ($movimiento->referencia ?? ''));
        $asignacion = $ref !== ''
            ? EnvioAsignacionMultiple::query()
                ->with(['pedido', 'almacen'])
                ->where('externo_envio_id', $ref)
                ->first()
            : null;
        $pedido = $asignacion?->pedido;
        $origen = trim((string) ($pedido?->origen_direccion ?? ''));
        if ($origen === '') {
            $origen = trim((string) ($pedido?->direccion_texto ?? ''));
        }
        if ($origen === '') {
            $origen = $asignacion?->almacen?->nombre ?? 'Campo / almacén agrícola';
        }

        $numero = $pedido?->numero_solicitud ?? $ref;

        return [
            'etiqueta' => $numero !== '' ? 'Pedido '.$numero : 'Envío agrícola',
            'origen' => $origen,
            'referencia' => $ref !== '' ? $ref : null,
            'pedidoid' => $pedido?->pedidoid,
            'url' => $pedido?->pedidoid ? route('pedidos.show', $pedido->pedidoid) : null,
        ];
    }

    public static function fechaDesdeDescripcionRecepcion(?string $descripcion): ?\Carbon\Carbon
    {
        if ($descripcion === null || ! preg_match('/(\d{2}\/\d{2}\/\d{4})/', $descripcion, $m)) {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $m[1])->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
