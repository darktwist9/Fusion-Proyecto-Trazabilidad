<?php

namespace App\Support;

use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\Pedido;
use App\Models\ProduccionAlmacenamiento;
use App\Models\TipoInsumo;
use Illuminate\Support\Collection;

final class PedidoCatalogo
{
    public const ESTADO_INICIAL = 'sin asignacion';

    public const ESTADO_CONFIRMADO = 'confirmado';

    public const ESTADO_RECHAZADO = 'rechazado';

    /** Estados en los que logística puede asignar transportista o avanzar el envío. */
    public static function estadosListosParaLogistica(): array
    {
        return ['confirmado', 'en produccion'];
    }

    public static function pendienteAprobacionAgricola(Pedido $pedido): bool
    {
        return in_array($pedido->estado, ['sin asignacion', 'pendiente'], true);
    }

    public static function listoParaLogistica(?Pedido $pedido): bool
    {
        return $pedido !== null
            && in_array($pedido->estado, self::estadosListosParaLogistica(), true);
    }

    public static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            'sin asignacion' => 'Pendiente agrícola',
            'pendiente' => 'Pendiente agrícola',
            'confirmado' => 'Aceptado — listo para envío',
            'en produccion' => 'En producción',
            'rechazado' => 'Rechazado',
            default => ucfirst($estado),
        };
    }

    /**
     * Opciones para el formulario de pedido: insumos agrícolas (material de siembra),
     * cosechas en almacén y cultivos de producción. Sin filtro por rol ni almacén del usuario.
     *
     * @return Collection<int, array{value: string, label: string, cultivo: string, origen: string}>
     */
    public static function opcionesProductoPedido(): Collection
    {
        $opciones = collect();

        foreach (self::insumosMaterialSiembraGlobales() as $insumo) {
            $almacen = $insumo->almacen?->nombre;
            $stock = number_format((float) $insumo->stock, 2);
            $unidad = $insumo->unidadMedida?->abreviatura ?? 'kg';
            $meta = trim(collect([$almacen, "Stock: {$stock} {$unidad}"])->filter()->implode(' · '));

            $opciones->push([
                'value' => 'insumo:'.$insumo->insumoid,
                'label' => $insumo->nombre.($meta ? " ({$meta})" : ''),
                'cultivo' => self::cultivoDesdeInsumo($insumo),
                'origen' => 'insumo',
            ]);
        }

        foreach (self::cosechasAgricolasDisponibles() as $cosecha) {
            $cultivo = $cosecha->produccion?->lote?->cultivo?->nombre ?? 'Cultivo';
            $lote = $cosecha->produccion?->lote?->nombre ?? 'Lote';
            $almacen = $cosecha->almacen?->nombre ?? 'Almacén agrícola';
            $cantidad = number_format((float) $cosecha->cantidad, 2);
            $unidad = $cosecha->unidadMedida?->abreviatura ?? 'kg';

            $opciones->push([
                'value' => 'cosecha:'.$cosecha->produccionalmacenamientoid,
                'label' => "{$cultivo} — {$lote} ({$almacen} · {$cantidad} {$unidad} disponibles)",
                'cultivo' => $cultivo,
                'origen' => 'cosecha',
            ]);
        }

        if ($opciones->isEmpty()) {
            foreach (Cultivo::query()->orderBy('nombre')->get() as $cultivo) {
                $opciones->push([
                    'value' => 'cultivo:'.$cultivo->cultivoid,
                    'label' => $cultivo->nombre.' (cultivo de producción agrícola)',
                    'cultivo' => $cultivo->nombre,
                    'origen' => 'cultivo',
                ]);
            }
        }

        return $opciones->unique('value')->values();
    }

    /** @return Collection<int, Insumo> */
    public static function insumosMaterialSiembraGlobales(): Collection
    {
        InsumoCatalogo::asegurarCatalogosBase();

        $tipoIds = self::tiposMaterialSiembraIds();
        if ($tipoIds->isEmpty()) {
            return collect();
        }

        return Insumo::query()
            ->with(['tipo', 'unidadMedida', 'almacen', 'actorAbastecimiento'])
            ->whereIn('tipoinsumoid', $tipoIds)
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, ProduccionAlmacenamiento> */
    public static function cosechasAgricolasDisponibles(): Collection
    {
        return ProduccionAlmacenamiento::query()
            ->with(['produccion.lote.cultivo', 'unidadMedida', 'almacen'])
            ->whereNull('fechasalida')
            ->where('cantidad', '>', 0)
            ->whereHas('almacen', fn ($q) => AlmacenAmbito::scope($q, AlmacenAmbito::AGRICOLA))
            ->orderByDesc('fechaentrada')
            ->get();
    }

    /** @return Collection<int, int> */
    private static function tiposMaterialSiembraIds(): Collection
    {
        return TipoInsumo::query()
            ->get()
            ->filter(function (TipoInsumo $tipo) {
                $slug = InsumoCatalogo::slugFromNombreTipo($tipo->nombre);
                if ($slug === 'material_siembra') {
                    return true;
                }

                $nombre = mb_strtolower(trim($tipo->nombre));

                return str_contains($nombre, 'siembra') || str_contains($nombre, 'semilla');
            })
            ->pluck('tipoinsumoid');
    }

    public static function generarNumeroSolicitud(): string
    {
        $fecha = now()->format('Ymd');
        $prefijo = "PED-{$fecha}-";
        $secuencia = Pedido::query()
            ->where('numero_solicitud', 'like', $prefijo.'%')
            ->count() + 1;

        return $prefijo.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Resuelve la referencia del producto seleccionado en el formulario.
     *
     * @return array{insumoid: ?int, cultivo: string}
     */
    public static function resolverProductoPedido(string $productoRef): array
    {
        if (str_starts_with($productoRef, 'insumo:')) {
            $insumo = Insumo::query()->with('tipo')->findOrFail((int) substr($productoRef, 7));
            $slug = InsumoCatalogo::slugFromNombreTipo($insumo->tipo?->nombre);
            $nombre = mb_strtolower(trim($insumo->tipo?->nombre ?? ''));
            if ($slug !== 'material_siembra' && ! str_contains($nombre, 'siembra') && ! str_contains($nombre, 'semilla')) {
                throw new \InvalidArgumentException('El insumo seleccionado no es Material de Siembra.');
            }

            return [
                'insumoid' => $insumo->insumoid,
                'cultivo' => self::cultivoDesdeInsumo($insumo),
            ];
        }

        if (str_starts_with($productoRef, 'cosecha:')) {
            $cosecha = ProduccionAlmacenamiento::query()
                ->with(['produccion.lote.cultivo'])
                ->findOrFail((int) substr($productoRef, 8));

            return [
                'insumoid' => null,
                'cultivo' => $cosecha->produccion?->lote?->cultivo?->nombre ?? 'Cultivo',
            ];
        }

        if (str_starts_with($productoRef, 'cultivo:')) {
            $cultivo = Cultivo::query()->findOrFail((int) substr($productoRef, 8));

            return [
                'insumoid' => null,
                'cultivo' => $cultivo->nombre,
            ];
        }

        throw new \InvalidArgumentException('Producto de pedido no válido.');
    }

    /** Cultivo de producción agrícola vinculado al insumo de material de siembra. */
    public static function cultivoDesdeInsumo(Insumo $insumo): string
    {
        $nombreInsumo = mb_strtolower(trim($insumo->nombre));

        $cultivo = Cultivo::query()
            ->get()
            ->first(function (Cultivo $c) use ($nombreInsumo) {
                $nombreCultivo = mb_strtolower(trim($c->nombre));

                return $nombreCultivo !== '' && str_contains($nombreInsumo, $nombreCultivo);
            });

        if ($cultivo) {
            return $cultivo->nombre;
        }

        $limpio = preg_replace('/^(semilla\s+certificada|semilla|material de siembra)\s+/iu', '', $insumo->nombre);

        return trim((string) $limpio) ?: $insumo->nombre;
    }
}
