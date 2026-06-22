<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Separa empaques comerciales de planta vs empaques logísticos agrícolas.
 */
final class TipoEmpaqueAmbito
{
    public const PLANTA = 'planta';

    public const AGRICOLA = 'agricola';

    /** @var list<string> */
    public const NOMBRES_PLANTA = [
        'Lata',
        'Frasco',
        'Bidón',
        'Pouch',
        'Bolsa plástica',
    ];

    /** @var list<string> */
    public const NOMBRES_AGRICOLA = [
        'Caja de cartón',
        'Caja plástica',
        'Bandeja',
        'Canasta',
        'Saco',
    ];

    /** El pallet es unidad de estiba; no se elige como empaque de producto. */
    public const NOMBRE_PALLET_EXCLUIDO = 'Pallet';

    public static function esEmpaqueProducto(?string $nombre): bool
    {
        return mb_strtolower(trim((string) $nombre)) !== mb_strtolower(self::NOMBRE_PALLET_EXCLUIDO);
    }

    public static function scopePlanta(Builder $query): void
    {
        if (self::columnaAmbitoDisponible()) {
            $query->where('ambito', self::PLANTA);
        } else {
            $query->whereIn('nombre', self::NOMBRES_PLANTA);
        }
    }

    public static function scopeAgricola(Builder $query): void
    {
        if (self::columnaAmbitoDisponible()) {
            $query->where('ambito', self::AGRICOLA);
        } else {
            $query->whereIn('nombre', self::NOMBRES_AGRICOLA);
        }

        $query->whereRaw('LOWER(TRIM(nombre)) <> ?', [mb_strtolower(self::NOMBRE_PALLET_EXCLUIDO)]);
    }

    public static function ambitoParaNombre(?string $nombre): ?string
    {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            return null;
        }

        if (in_array($nombre, self::NOMBRES_PLANTA, true)) {
            return self::PLANTA;
        }

        if (in_array($nombre, self::NOMBRES_AGRICOLA, true)) {
            return self::AGRICOLA;
        }

        return null;
    }

    /** En planta cada empaque comercial = 1 unidad de producto terminado. */
    public static function capacidadUnidadesPlanta(): int
    {
        return 1;
    }

    /**
     * Referencia logística: empaques de este tipo por pallet (solo planta).
     */
    public static function unidadesPorPalletPlanta(?string $nombre): int
    {
        $key = mb_strtolower(trim((string) $nombre));

        return match (true) {
            str_contains($key, 'lata') => 120,
            str_contains($key, 'frasco') => 96,
            str_contains($key, 'bidón') || str_contains($key, 'bidon') => 36,
            str_contains($key, 'pouch') => 200,
            str_contains($key, 'bolsa') => 80,
            default => 48,
        };
    }

    public static function columnaAmbitoDisponible(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('tipo_empaque')
            && \Illuminate\Support\Facades\Schema::hasColumn('tipo_empaque', 'ambito');
    }
}
