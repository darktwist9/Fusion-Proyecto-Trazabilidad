<?php

namespace App\Support;

use App\Models\Cultivo;

final class CultivoSiembraCatalogo
{
    /**
     * Dosis de referencia por nombre de cultivo (semilla o plántulas por hectárea).
     *
     * @return array{por_ha: float, unidad: string, etiqueta_unidad: string}|null
     */
    public static function dosisPorNombreCultivo(?string $nombre): ?array
    {
        if ($nombre === null || trim($nombre) === '') {
            return null;
        }

        $key = mb_strtolower(trim($nombre));

        $tabla = [
            'zanahoria' => ['por_ha' => 3.0, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'tomate' => ['por_ha' => 0.4, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'papa' => ['por_ha' => 2500, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'cebolla' => ['por_ha' => 8.0, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'lechuga' => ['por_ha' => 0.35, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'maíz' => ['por_ha' => 25.0, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'maiz' => ['por_ha' => 25.0, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'mango' => ['por_ha' => 200.0, 'unidad' => 'unidad', 'etiqueta_unidad' => 'plantas'],
            'pimentón' => ['por_ha' => 0.25, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
            'pimenton' => ['por_ha' => 0.25, 'unidad' => 'kg', 'etiqueta_unidad' => 'kilogramos'],
        ];

        foreach ($tabla as $fragmento => $dosis) {
            if (str_contains($key, $fragmento)) {
                return $dosis;
            }
        }

        return null;
    }

    public static function sugerenciaParaInsumo(\App\Models\Insumo $insumo, float $superficieHa): array
    {
        $porHa = $insumo->dosis_por_ha;
        $unidad = $insumo->dosis_unidad;

        if ($porHa === null || $unidad === null || trim((string) $unidad) === '') {
            $fallback = self::dosisPorNombreCultivo(
                \App\Support\PedidoCatalogo::cultivoDesdeInsumo($insumo)
            );
            $porHa = $fallback['por_ha'] ?? null;
            $unidad = $fallback['unidad'] ?? ($insumo->unidadMedida?->abreviatura ?? 'kg');
            $etiqueta = $fallback['etiqueta_unidad'] ?? 'kilogramos';
        } else {
            $etiqueta = self::etiquetaUnidadAmigable((string) $unidad);
        }

        $superficie = max(0.0, $superficieHa);
        $tieneDosis = $porHa !== null && (float) $porHa > 0;
        $sugerido = $tieneDosis ? round((float) $porHa * $superficie, 2) : 0.0;

        return [
            'por_ha' => (float) ($porHa ?? 0),
            'unidad' => (string) ($unidad ?? 'kg'),
            'etiqueta_unidad' => $etiqueta ?? 'kilogramos',
            'superficie_ha' => $superficie,
            'sugerido' => $sugerido,
            'tiene_dosis' => $tieneDosis,
            'insumoid' => (int) $insumo->insumoid,
            'insumo_nombre' => $insumo->nombre,
        ];
    }

    /**
     * @return array{
     *     por_ha: float,
     *     unidad: string,
     *     etiqueta_unidad: string,
     *     superficie_ha: float,
     *     sugerido: float,
     *     tiene_dosis: bool
     * }
     */
    public static function sugerenciaParaLote(Cultivo $cultivo, float $superficieHa): array
    {
        $porHa = $cultivo->dosis_siembra_por_ha;
        $unidad = $cultivo->dosis_siembra_unidad;

        if ($porHa === null || $unidad === null || trim((string) $unidad) === '') {
            $fallback = self::dosisPorNombreCultivo($cultivo->nombre);
            $porHa = $fallback['por_ha'] ?? null;
            $unidad = $fallback['unidad'] ?? 'kg';
            $etiqueta = $fallback['etiqueta_unidad'] ?? 'kilogramos';
        } else {
            $etiqueta = self::etiquetaUnidadAmigable((string) $unidad);
        }

        $superficie = max(0.0, $superficieHa);
        $tieneDosis = $porHa !== null && (float) $porHa > 0;
        $sugerido = $tieneDosis ? round((float) $porHa * $superficie, 2) : 0.0;

        return [
            'por_ha' => (float) ($porHa ?? 0),
            'unidad' => (string) ($unidad ?? 'kg'),
            'etiqueta_unidad' => $etiqueta ?? 'kilogramos',
            'superficie_ha' => $superficie,
            'sugerido' => $sugerido,
            'tiene_dosis' => $tieneDosis,
        ];
    }

    public static function etiquetaUnidadAmigable(string $unidad): string
    {
        return match (mb_strtolower(trim($unidad))) {
            'kg', 'kilogramo' => 'kilogramos',
            'g', 'gramo' => 'gramos',
            'qq', 'quintal' => 'quintales',
            'und', 'unidad' => 'plantas o unidades',
            default => $unidad,
        };
    }
}
