<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class OperarioPlantaTareaNotificacionVista
{
    public static function clave(int $asignacionId): string
    {
        return 'tarea:'.$asignacionId;
    }

    /** @return list<string> */
    private static function clavesVistas(int $usuarioid): array
    {
        $claves = Cache::get(self::cacheKey($usuarioid), []);

        return is_array($claves) ? array_values(array_unique($claves)) : [];
    }

    /**
     * @param  list<array{clave: string, proceso: string, maquina: string, lote: string, url: string}>  $items
     * @return list<array{clave: string, proceso: string, maquina: string, lote: string, url: string}>
     */
    public static function filtrarPendientes(int $usuarioid, array $items): array
    {
        return array_values(array_filter(
            $items,
            fn (array $row) => ! in_array((string) ($row['clave'] ?? ''), self::clavesVistas($usuarioid), true)
        ));
    }

    /**
     * @param  list<array{clave: string, proceso: string, maquina: string, lote: string, url: string}>  $items
     */
    public static function marcarVistas(int $usuarioid, array $items): void
    {
        if ($items === []) {
            return;
        }

        $claves = self::clavesVistas($usuarioid);
        foreach ($items as $row) {
            $clave = (string) ($row['clave'] ?? '');
            if ($clave !== '') {
                $claves[] = $clave;
            }
        }

        Cache::forever(self::cacheKey($usuarioid), array_values(array_unique($claves)));
    }

    private static function cacheKey(int $usuarioid): string
    {
        return 'operario_planta_tarea_modal_vistas:'.$usuarioid;
    }
}
