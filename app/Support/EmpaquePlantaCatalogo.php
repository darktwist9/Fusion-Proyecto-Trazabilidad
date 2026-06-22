<?php

namespace App\Support;

use App\Models\InsumoPresentacion;
use App\Models\TipoEmpaque;

class EmpaquePlantaCatalogo
{
    /** Merma estándar en transformación (todas las verduras). */
    public const RENDIMIENTO_TRANSFORMACION = 0.85;

    public const MODO_EMPAQUES = 'empaques';

    public const MODO_MATERIA_PRIMA = 'materia_prima';

    public const SLUG_PERSONALIZADO = 'personalizado';

    /** @var list<array{slug: string, nombre: string, tipo_envase: string, peso_neto_kg: float, etiqueta_unidad: string}> */
    public const TIPOS_PREDEFINIDOS = [
        [
            'slug' => 'lata',
            'nombre' => 'Lata',
            'tipo_envase' => 'lata',
            'peso_neto_kg' => 0.4,
            'etiqueta_unidad' => 'latas',
        ],
        [
            'slug' => 'frasco',
            'nombre' => 'Frasco',
            'tipo_envase' => 'frasco',
            'peso_neto_kg' => 0.34,
            'etiqueta_unidad' => 'frascos',
        ],
        [
            'slug' => 'bidon',
            'nombre' => 'Bidón',
            'tipo_envase' => 'bidon',
            'peso_neto_kg' => 5.0,
            'etiqueta_unidad' => 'bidones',
        ],
        [
            'slug' => 'pouch',
            'nombre' => 'Pouch',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 1.0,
            'etiqueta_unidad' => 'pouches',
        ],
        [
            'slug' => 'bolsa',
            'nombre' => 'Bolsa plástica',
            'tipo_envase' => 'bolsa',
            'peso_neto_kg' => 0.5,
            'etiqueta_unidad' => 'bolsas',
        ],
    ];

    public static function rendimiento(): float
    {
        return self::RENDIMIENTO_TRANSFORMACION;
    }

    public static function rendimientoPorcentaje(): int
    {
        return (int) round(self::RENDIMIENTO_TRANSFORMACION * 100);
    }

    public static function esSlugValido(?string $slug): bool
    {
        if ($slug === null || $slug === '') {
            return false;
        }

        if ($slug === self::SLUG_PERSONALIZADO) {
            return true;
        }

        return self::definicionPorSlug($slug) !== null;
    }

    /** @return array{slug: string, nombre: string, tipo_envase: string, peso_neto_kg: float, etiqueta_unidad: string}|null */
    public static function definicionPorSlug(?string $slug): ?array
    {
        foreach (self::TIPOS_PREDEFINIDOS as $tipo) {
            if ($tipo['slug'] === $slug) {
                return $tipo;
            }
        }

        return null;
    }

    public static function pesoNetoKg(?string $slug, ?float $pesoPersonalizado = null): float
    {
        if ($slug === self::SLUG_PERSONALIZADO) {
            return max(0.0001, (float) $pesoPersonalizado);
        }

        $def = self::definicionPorSlug($slug);

        return max(0.0001, (float) ($def['peso_neto_kg'] ?? 0.001));
    }

    public static function etiquetaUnidad(?string $slug, ?string $tipoEnvase = null): string
    {
        $def = self::definicionPorSlug($slug);
        if ($def) {
            return $def['etiqueta_unidad'];
        }

        return match ($tipoEnvase) {
            'lata' => 'latas',
            'frasco' => 'frascos',
            'bidon' => 'bidones',
            'caja' => 'cajas',
            default => 'unidades',
        };
    }

  /**
     * Objetivo: N empaques → kg de materia prima necesarios.
     *
     * @return array{unidades: float, salida_kg: float, entrada_kg: float, peso_neto_kg: float, rendimiento: float, etiqueta_unidad: string}
     */
    public static function calcularDesdeEmpaques(float $unidades, float $pesoNetoKg, ?string $slug = null): array
    {
        $unidades = max(0, $unidades);
        $pesoNetoKg = max(0.0001, $pesoNetoKg);
        $salidaKg = round($unidades * $pesoNetoKg, 4);
        $entradaKg = round($salidaKg / self::RENDIMIENTO_TRANSFORMACION, 4);

        return [
            'unidades' => $unidades,
            'salida_kg' => $salidaKg,
            'entrada_kg' => $entradaKg,
            'peso_neto_kg' => $pesoNetoKg,
            'rendimiento' => self::RENDIMIENTO_TRANSFORMACION,
            'etiqueta_unidad' => self::etiquetaUnidad($slug),
        ];
    }

    /**
     * Partir de kg de materia prima → empaques estimados.
     *
     * @return array{unidades: int, salida_kg: float, entrada_kg: float, peso_neto_kg: float, rendimiento: float, etiqueta_unidad: string}
     */
    public static function calcularDesdeMateriaPrima(float $entradaKg, float $pesoNetoKg, ?string $slug = null): array
    {
        $entradaKg = max(0, $entradaKg);
        $pesoNetoKg = max(0.0001, $pesoNetoKg);
        $salidaKg = round($entradaKg * self::RENDIMIENTO_TRANSFORMACION, 4);
        $unidades = (int) floor($salidaKg / $pesoNetoKg);

        return [
            'unidades' => $unidades,
            'salida_kg' => $salidaKg,
            'entrada_kg' => $entradaKg,
            'peso_neto_kg' => $pesoNetoKg,
            'rendimiento' => self::RENDIMIENTO_TRANSFORMACION,
            'etiqueta_unidad' => self::etiquetaUnidad($slug),
        ];
    }

    /** @return list<array{slug: string, nombre: string, peso_neto_kg: float, peso_etiqueta: string, etiqueta_unidad: string}> */
    public static function opcionesSelect(): array
    {
        $opciones = [];
        foreach (self::TIPOS_PREDEFINIDOS as $tipo) {
            $kg = $tipo['peso_neto_kg'];
            $opciones[] = [
                'slug' => $tipo['slug'],
                'nombre' => $tipo['nombre'],
                'peso_neto_kg' => $kg,
                'peso_etiqueta' => $kg >= 1
                    ? number_format($kg, $kg >= 10 ? 0 : 1, ',', '.').' kg'
                    : number_format($kg * 1000, 0, ',', '.').' g',
                'etiqueta_unidad' => $tipo['etiqueta_unidad'],
            ];
        }
        $opciones[] = [
            'slug' => self::SLUG_PERSONALIZADO,
            'nombre' => 'Presentación personalizada',
            'peso_neto_kg' => null,
            'peso_etiqueta' => 'Definir peso',
            'etiqueta_unidad' => 'unidades',
        ];

        return $opciones;
    }

    public static function etiquetaEmpaquePlanificado(
        ?string $slug,
        ?string $nombrePersonalizado = null,
        ?float $pesoPersonalizado = null
    ): string {
        if ($slug === self::SLUG_PERSONALIZADO) {
            $nombre = trim((string) $nombrePersonalizado) ?: 'Presentación personalizada';
            $peso = max(0.0001, (float) $pesoPersonalizado);
            $pesoTxt = $peso >= 1
                ? number_format($peso, 2, ',', '.').' kg'
                : number_format($peso * 1000, 0, ',', '.').' g';

            return $nombre.' ('.$pesoTxt.')';
        }

        $def = self::definicionPorSlug($slug);
        if (! $def) {
            return '—';
        }

        $peso = $def['peso_neto_kg'];
        $pesoTxt = $peso >= 1
            ? number_format($peso, $peso >= 10 ? 0 : 1, ',', '.').' kg'
            : number_format($peso * 1000, 0, ',', '.').' g';

        return $def['nombre'].' '.$pesoTxt;
    }

    public static function asegurarTiposEmpaqueEnBd(): void
    {
        foreach (self::TIPOS_PREDEFINIDOS as $tipo) {
            TipoEmpaque::query()->firstOrCreate(
                ['nombre' => $tipo['nombre']],
                [
                    'descripcion' => 'Empaque estándar de producto terminado en planta',
                    'activo' => true,
                    'ambito' => \App\Support\TipoEmpaqueAmbito::PLANTA,
                ]
            );
        }
    }

    public static function tipoEmpaqueIdPorSlug(?string $slug): ?int
    {
        $def = self::definicionPorSlug($slug);
        if (! $def) {
            return null;
        }

        self::asegurarTiposEmpaqueEnBd();

        return TipoEmpaque::query()->where('nombre', $def['nombre'])->value('tipoempaqueid');
    }

    public static function nombrePresentacionDesdePlan(
        ?string $slug,
        ?string $nombrePersonalizado = null,
        ?float $pesoPersonalizado = null
    ): string {
        if ($slug === self::SLUG_PERSONALIZADO) {
            return trim((string) $nombrePersonalizado) ?: 'Presentación personalizada';
        }

        $def = self::definicionPorSlug($slug);
        if (! $def) {
            return 'Presentación';
        }

        $peso = $def['peso_neto_kg'];
        $pesoTxt = $peso >= 1
            ? number_format($peso, $peso >= 10 ? 0 : 1, ',', '.').' kg'
            : number_format($peso * 1000, 0, ',', '.').' g';

        return $def['nombre'].' '.$pesoTxt;
    }

    public static function tipoEnvaseDesdePlan(?string $slug, ?string $tipoEnvasePersonalizado = null): string
    {
        if ($slug === self::SLUG_PERSONALIZADO) {
            return in_array($tipoEnvasePersonalizado, ['bolsa', 'lata', 'frasco', 'bidon', 'caja'], true)
                ? $tipoEnvasePersonalizado
                : 'bolsa';
        }

        return self::definicionPorSlug($slug)['tipo_envase'] ?? 'bolsa';
    }

    public static function resumenPlanificacionLote(
        ?string $modo,
        ?string $slug,
        ?float $cantidadEmpaquesObjetivo,
        ?float $cantidadObjetivoKg,
        ?string $nombrePersonalizado = null,
        ?float $pesoPersonalizado = null
    ): ?array {
        if (! self::esSlugValido($slug)) {
            return null;
        }

        $pesoNeto = self::pesoNetoKg($slug, $pesoPersonalizado);

        if ($modo === self::MODO_EMPAQUES && $cantidadEmpaquesObjetivo > 0) {
            return self::calcularDesdeEmpaques($cantidadEmpaquesObjetivo, $pesoNeto, $slug);
        }

        if ($modo === self::MODO_MATERIA_PRIMA && $cantidadObjetivoKg > 0) {
            return self::calcularDesdeMateriaPrima($cantidadObjetivoKg, $pesoNeto, $slug);
        }

        return null;
    }

    public static function etiquetaPresentacion(InsumoPresentacion $presentacion): string
    {
        return $presentacion->nombre;
    }
}
