<?php

namespace App\Support;

use App\Models\CatalogoTamanoConteo;
use App\Models\Insumo;
use App\Models\LoteProduccionPedido;
use App\Models\UnidadMedida;
use App\Services\AlmacenCapacidadService;
use Illuminate\Support\Str;

class ProductoPlantaCatalogo
{
    /** @deprecated Usar EmpaquePlantaCatalogo::pesoNetoKg('lata') */
    public const PESO_KG_POR_UNIDAD_PURE = 0.4;

    /** @deprecated Usar EmpaquePlantaCatalogo::RENDIMIENTO_TRANSFORMACION */
    public const RENDIMIENTO_PURE = 0.85;

    public static function esProductoPorUnidades(?string $producto): bool
    {
        if ($producto === null || trim($producto) === '') {
            return false;
        }

        $lower = Str::lower(trim($producto));

        return Str::contains($lower, 'puré')
            || Str::contains($lower, 'pure')
            || Str::contains($lower, 'salsa')
            || Str::contains($lower, 'chips');
    }

    public static function loteTieneEmpaquePlanificado(LoteProduccionPedido $lote): bool
    {
        return EmpaquePlantaCatalogo::esSlugValido($lote->empaque_catalogo_slug ?? null);
    }

    public static function pesoKgPorUnidadLote(LoteProduccionPedido $lote): ?float
    {
        if (self::loteTieneEmpaquePlanificado($lote)) {
            return EmpaquePlantaCatalogo::pesoNetoKg(
                $lote->empaque_catalogo_slug,
                $lote->empaque_peso_neto_kg
            );
        }

        $lote->loadMissing('presentacion');

        if ($lote->presentacion) {
            return $lote->presentacion->pesoNetoKg();
        }

        return self::pesoKgPorUnidad(self::nombreProducto($lote));
    }

    public static function pesoKgPorUnidad(?string $producto): ?float
    {
        return self::esProductoPorUnidades($producto) ? self::PESO_KG_POR_UNIDAD_PURE : null;
    }

    public static function rendimiento(?string $producto): float
    {
        return EmpaquePlantaCatalogo::rendimiento();
    }

    public static function rendimientoLote(LoteProduccionPedido $lote): float
    {
        return EmpaquePlantaCatalogo::rendimiento();
    }

    public static function esProduccionPorUnidades(LoteProduccionPedido $lote): bool
    {
        if (self::loteTieneEmpaquePlanificado($lote)) {
            return true;
        }

        return self::esProductoPorUnidades(self::nombreProducto($lote));
    }

    public static function unidadMedidaIdPorDefecto(?string $producto): ?int
    {
        if (! self::esProductoPorUnidades($producto)) {
            return null;
        }

        return UnidadMedida::query()
            ->where(function ($q) {
                $q->whereRaw("LOWER(TRIM(COALESCE(abreviatura, ''))) = ?", ['und'])
                    ->orWhereRaw('LOWER(TRIM(nombre)) = ?', ['unidad']);
            })
            ->value('unidadmedidaid');
    }

    public static function unidadEtiqueta(?string $producto, ?UnidadMedida $unidad, ?LoteProduccionPedido $lote = null): string
    {
        if ($lote && self::loteTieneEmpaquePlanificado($lote)) {
            return EmpaquePlantaCatalogo::etiquetaUnidad($lote->empaque_catalogo_slug, $lote->empaque_tipo_envase);
        }

        if (self::esProductoPorUnidades($producto)) {
            return 'und';
        }

        return $unidad?->abreviatura ?? $unidad?->nombre ?? 'kg';
    }

    public static function resolverUnidadMedidaId(?string $producto, ?int $unidadmedidaid): ?int
    {
        $defecto = self::unidadMedidaIdPorDefecto($producto);

        if ($defecto === null) {
            return $unidadmedidaid;
        }

        if ($unidadmedidaid === null) {
            return $defecto;
        }

        $unidad = UnidadMedida::query()->find($unidadmedidaid);
        if ($unidad === null) {
            return $defecto;
        }

        $abbr = Str::lower(trim($unidad->abreviatura ?? $unidad->nombre ?? ''));
        if (str_contains($abbr, 'kg') || str_contains($abbr, 'kilogramo')) {
            return $defecto;
        }

        return $unidadmedidaid;
    }

    public static function nombreProducto(LoteProduccionPedido $lote): string
    {
        return LoteProduccionNombre::productoDesdeLote($lote);
    }

    public static function masaMateriasPrimasKg(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): float
    {
        $lote->loadMissing('materiasPrimas.insumo.unidadMedida');

        return (float) $lote->materiasPrimas->sum(function ($mp) use ($capacidad) {
            return $capacidad->convertirAKg(
                (float) $mp->cantidad_usada,
                $mp->insumo?->unidadMedida
            );
        });
    }

    public static function kgProducidos(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): float
    {
        $entradaKg = self::masaMateriasPrimasKg($lote, $capacidad);
        if ($entradaKg <= 0) {
            return 0.0;
        }

        return round($entradaKg * self::rendimientoLote($lote), 4);
    }

    public static function unidadesProducidas(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): int
    {
        $objetivo = (int) round((float) ($lote->cantidad_empaques_objetivo ?? 0));

        if ($objetivo > 0
            && self::loteTieneEmpaquePlanificado($lote)
            && ($lote->modo_planificacion ?? EmpaquePlantaCatalogo::MODO_EMPAQUES) === EmpaquePlantaCatalogo::MODO_EMPAQUES
        ) {
            return $objetivo;
        }

        $pesoUnd = self::pesoKgPorUnidadLote($lote);
        $kg = self::kgProducidos($lote, $capacidad);

        if ($pesoUnd === null || $pesoUnd <= 0 || $kg <= 0) {
            return max(0, $objetivo > 0 ? $objetivo : (int) round($kg));
        }

        return max(0, (int) round($kg / $pesoUnd));
    }

    public static function etiquetaCantidadObjetivo(LoteProduccionPedido $lote): ?string
    {
        if ((float) ($lote->cantidad_empaques_objetivo ?? 0) > 0 && self::loteTieneEmpaquePlanificado($lote)) {
            $und = (int) round((float) $lote->cantidad_empaques_objetivo);
            $etiq = EmpaquePlantaCatalogo::etiquetaUnidad($lote->empaque_catalogo_slug, $lote->empaque_tipo_envase);
            $empaque = EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
                $lote->empaque_catalogo_slug,
                $lote->empaque_nombre_personalizado,
                $lote->empaque_peso_neto_kg
            );

            return $und.' '.$etiq.' · '.$empaque;
        }

        if ((float) ($lote->cantidad_objetivo ?? 0) > 0) {
            $unidad = $lote->unidadMedida?->abreviatura ?? $lote->unidadMedida?->nombre ?? '';

            return number_format((float) $lote->cantidad_objetivo, 2).' '.$unidad;
        }

        return null;
    }

    public static function empaquePlanificadoResumen(LoteProduccionPedido $lote): ?string
    {
        if (! self::loteTieneEmpaquePlanificado($lote)) {
            return null;
        }

        $empaque = EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
            $lote->empaque_catalogo_slug,
            $lote->empaque_nombre_personalizado,
            $lote->empaque_peso_neto_kg
        );
        $objetivo = (int) round((float) ($lote->cantidad_empaques_objetivo ?? 0));
        if ($objetivo > 0) {
            $etiq = EmpaquePlantaCatalogo::etiquetaUnidad($lote->empaque_catalogo_slug, $lote->empaque_tipo_envase);

            return $objetivo.' '.$etiq.' · '.$empaque;
        }

        return $empaque;
    }

    /**
     * @return list<array{nombre: string, cantidad: int, etiqueta: string}>
     */
    public static function estimadosUnidadesMateriaPrimaUsadas(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): array
    {
        $lote->loadMissing('materiasPrimas.insumo.unidadMedida');

        $estimados = [];
        foreach ($lote->materiasPrimas as $mp) {
            $kg = $capacidad->convertirAKg(
                (float) $mp->cantidad_usada,
                $mp->insumo?->unidadMedida
            );
            if ($kg <= 0) {
                continue;
            }

            $pesoUnd = self::pesoPromedioUnidadInsumo($mp->insumo);
            if ($pesoUnd === null || $pesoUnd <= 0) {
                continue;
            }

            $cantidad = max(1, (int) round($kg / $pesoUnd));
            $nombreBase = trim($mp->insumo?->nombre ?? 'Materia prima');
            $estimados[] = [
                'nombre' => $nombreBase,
                'cantidad' => $cantidad,
                'etiqueta' => self::pluralizarNombreInsumo($nombreBase, $cantidad),
            ];
        }

        return $estimados;
    }

    public static function formatearCantidadAlmacenaje(float $cantidad, LoteProduccionPedido $lote): string
    {
        $decimales = self::esProduccionPorUnidades($lote) ? 0 : 2;

        return number_format($cantidad, $decimales);
    }

    public static function cantidadAlmacenajeMostrar(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad, ?float $cantidadRegistrada): ?float
    {
        if ($cantidadRegistrada === null) {
            return null;
        }

        if (self::esProduccionPorUnidades($lote)) {
            return (float) self::unidadesProducidas($lote, $capacidad);
        }

        return $cantidadRegistrada;
    }

    public static function pesoPromedioUnidadInsumo(?Insumo $insumo): ?float
    {
        if ($insumo === null) {
            return null;
        }

        $peso = CatalogoTamanoConteo::query()
            ->where('insumoid', $insumo->insumoid)
            ->where('activo', true)
            ->where('peso_promedio_kg', '>', 0)
            ->avg('peso_promedio_kg');

        if ($peso !== null && (float) $peso > 0) {
            return (float) $peso;
        }

        CalibresVerdurasCatalogo::sincronizarParaInsumo((int) $insumo->insumoid);

        $peso = CatalogoTamanoConteo::query()
            ->where('insumoid', $insumo->insumoid)
            ->where('activo', true)
            ->where('peso_promedio_kg', '>', 0)
            ->avg('peso_promedio_kg');

        return $peso !== null && (float) $peso > 0 ? (float) $peso : null;
    }

    public static function pluralizarNombreInsumo(string $nombre, int $cantidad): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return $cantidad === 1 ? 'unidad' : 'unidades';
        }

        if ($cantidad === 1) {
            return $nombre;
        }

        $lower = mb_strtolower($nombre);
        if (str_ends_with($lower, 's')) {
            return $nombre;
        }

        return $nombre.'s';
    }

    /**
     * @return array{cantidad: float, kg: float, unidad: string, entrada_kg: float, rendimiento: float, unidades?: int, empaque?: string}
     */
    public static function resumenProduccion(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): array
    {
        $producto = self::nombreProducto($lote);
        $entradaKg = self::masaMateriasPrimasKg($lote, $capacidad);
        $salidaKg = self::kgProducidos($lote, $capacidad);
        $rendimiento = self::rendimientoLote($lote);
        $empaqueLabel = self::loteTieneEmpaquePlanificado($lote)
            ? EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
                $lote->empaque_catalogo_slug,
                $lote->empaque_nombre_personalizado,
                $lote->empaque_peso_neto_kg
            )
            : null;

        if (self::esProduccionPorUnidades($lote)) {
            $und = self::unidadesProducidas($lote, $capacidad);

            return [
                'cantidad' => (float) $und,
                'kg' => $salidaKg,
                'unidad' => self::unidadEtiqueta($producto, $lote->unidadMedida, $lote),
                'entrada_kg' => $entradaKg,
                'rendimiento' => $rendimiento,
                'unidades' => $und,
                'empaque' => $empaqueLabel,
            ];
        }

        $cantidadObjetivo = (float) ($lote->cantidad_objetivo ?? 0);
        if ($salidaKg > 0) {
            $cantidad = $salidaKg;
        } elseif ($cantidadObjetivo > 0) {
            $cantidad = $cantidadObjetivo;
        } else {
            $cantidad = 0.0;
        }

        return [
            'cantidad' => $cantidad,
            'kg' => $salidaKg > 0 ? $salidaKg : $cantidad,
            'unidad' => $lote->unidadMedida?->abreviatura ?? $lote->unidadMedida?->nombre ?? 'kg',
            'entrada_kg' => $entradaKg,
            'rendimiento' => $rendimiento,
            'empaque' => $empaqueLabel,
        ];
    }

    public static function cantidadParaAlmacenaje(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): float
    {
        $resumen = self::resumenProduccion($lote, $capacidad);

        if ($resumen['cantidad'] <= 0 && (float) ($lote->cantidad_objetivo ?? 0) > 0) {
            return (float) $lote->cantidad_objetivo;
        }

        return $resumen['cantidad'];
    }

    public static function kgParaAlmacenaje(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): float
    {
        $resumen = self::resumenProduccion($lote, $capacidad);

        return $resumen['kg'] > 0 ? $resumen['kg'] : (float) ($lote->cantidad_objetivo ?? 0);
    }

    /**
     * @return array{unidades: float, salida_kg: float, entrada_kg: float, rendimiento: float, peso_kg_por_unidad: float}|null
     */
    public static function recomendacionMateriaPrima(
        ?string $producto,
        float $unidadesObjetivo,
        ?string $empaqueSlug = null,
        ?float $pesoPersonalizado = null
    ): ?array {
        if ($unidadesObjetivo <= 0) {
            return null;
        }

        if ($empaqueSlug && EmpaquePlantaCatalogo::esSlugValido($empaqueSlug)) {
            $calc = EmpaquePlantaCatalogo::calcularDesdeEmpaques(
                $unidadesObjetivo,
                EmpaquePlantaCatalogo::pesoNetoKg($empaqueSlug, $pesoPersonalizado),
                $empaqueSlug
            );

            return [
                'unidades' => $calc['unidades'],
                'salida_kg' => $calc['salida_kg'],
                'entrada_kg' => $calc['entrada_kg'],
                'rendimiento' => $calc['rendimiento'],
                'peso_kg_por_unidad' => $calc['peso_neto_kg'],
            ];
        }

        if (! self::esProductoPorUnidades($producto)) {
            return null;
        }

        $pesoUnd = self::PESO_KG_POR_UNIDAD_PURE;
        $rendimiento = EmpaquePlantaCatalogo::rendimiento();
        $salidaKg = $unidadesObjetivo * $pesoUnd;
        $entradaKg = $salidaKg / $rendimiento;

        return [
            'unidades' => $unidadesObjetivo,
            'salida_kg' => round($salidaKg, 2),
            'entrada_kg' => round($entradaKg, 2),
            'rendimiento' => $rendimiento,
            'peso_kg_por_unidad' => $pesoUnd,
        ];
    }

    /**
     * @return array{unidades: float, salida_kg: float, entrada_kg: float, rendimiento: float, peso_kg_por_unidad: float}|null
     */
    public static function recomendacionDesdeMateriaPrima(
        float $entradaKg,
        ?string $empaqueSlug = null,
        ?float $pesoPersonalizado = null
    ): ?array {
        if ($entradaKg <= 0 || ! $empaqueSlug || ! EmpaquePlantaCatalogo::esSlugValido($empaqueSlug)) {
            return null;
        }

        $calc = EmpaquePlantaCatalogo::calcularDesdeMateriaPrima(
            $entradaKg,
            EmpaquePlantaCatalogo::pesoNetoKg($empaqueSlug, $pesoPersonalizado),
            $empaqueSlug
        );

        return [
            'unidades' => (float) $calc['unidades'],
            'salida_kg' => $calc['salida_kg'],
            'entrada_kg' => $calc['entrada_kg'],
            'rendimiento' => $calc['rendimiento'],
            'peso_kg_por_unidad' => $calc['peso_neto_kg'],
        ];
    }

    public static function etiquetaLoteAlmacen(LoteProduccionPedido $lote): string
    {
        $nombre = trim($lote->nombre ?? '');
        $producto = trim(self::nombreProducto($lote));

        if ($nombre === '') {
            return $producto !== '' ? $producto : ($lote->codigo_lote ?? 'Lote');
        }

        if ($producto === '' || mb_strtolower($producto) === mb_strtolower($nombre)) {
            return $nombre;
        }

        if (str_contains(mb_strtolower($nombre), mb_strtolower($producto))) {
            return $nombre;
        }

        return $nombre;
    }

    public static function detalleEmpaqueAlmacen(LoteProduccionPedido $lote, array $resumen): string
    {
        if (! self::loteTieneEmpaquePlanificado($lote)) {
            return '';
        }

        $empaque = EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
            $lote->empaque_catalogo_slug,
            $lote->empaque_nombre_personalizado,
            $lote->empaque_peso_neto_kg
        );
        $und = (int) round((float) ($resumen['cantidad'] ?? 0));
        $etiq = $resumen['unidad'] ?? EmpaquePlantaCatalogo::etiquetaUnidad($lote->empaque_catalogo_slug, $lote->empaque_tipo_envase);
        $kg = (float) ($resumen['kg'] ?? 0);

        return $und.' '.$etiq.' · '.$empaque.' · '.number_format($kg, 2).' kg producto';
    }

    /**
     * @return array{titulo: string, empaque: string, unidades: string, kg_producto: string, kg_materia: string}|null
     */
    public static function vistaPreviaEmpaquetado(LoteProduccionPedido $lote, AlmacenCapacidadService $capacidad): ?array
    {
        if (! self::loteTieneEmpaquePlanificado($lote)) {
            return null;
        }

        $resumen = self::resumenProduccion($lote, $capacidad);
        $empaque = EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
            $lote->empaque_catalogo_slug,
            $lote->empaque_nombre_personalizado,
            $lote->empaque_peso_neto_kg
        );

        return [
            'titulo' => 'Vista previa del empaquetado',
            'empaque' => $empaque,
            'unidades' => number_format((float) $resumen['cantidad'], 0).' '.($resumen['unidad'] ?? 'und'),
            'kg_producto' => number_format((float) $resumen['kg'], 2).' kg de producto terminado',
            'kg_materia' => number_format((float) $resumen['entrada_kg'], 2).' kg de materia prima',
        ];
    }

    public static function esProcesoEmpaquetado(?string $nombreProceso): bool
    {
        return ProcesoPlantaCatalogo::esCierreTransformacion($nombreProceso);
    }
}
