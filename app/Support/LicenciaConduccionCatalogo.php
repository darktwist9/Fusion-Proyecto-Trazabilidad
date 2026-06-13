<?php

namespace App\Support;

final class LicenciaConduccionCatalogo
{
    /** Mayor rango = puede conducir vehículos que exigen licencias menores. */
    private const RANGO = [
        'M' => 1,
        'P' => 2,
        'A' => 3,
        'B' => 4,
        'C' => 5,
        'T' => 5,
    ];

    public static function puedeConducir(?string $licenciaConductor, ?string $licenciaRequerida): bool
    {
        $requerida = self::normalizar($licenciaRequerida);
        if ($requerida === null) {
            return true;
        }

        $conductor = self::normalizar($licenciaConductor);
        if ($conductor === null) {
            return false;
        }

        return (self::RANGO[$conductor] ?? 0) >= (self::RANGO[$requerida] ?? 99);
    }

    public static function mensajeBloqueo(?string $licenciaConductor, ?string $licenciaRequerida): string
    {
        $req = self::normalizar($licenciaRequerida) ?? '?';
        $cond = self::normalizar($licenciaConductor);

        if ($cond === null) {
            return "El transportista no tiene licencia registrada. Este vehículo requiere licencia {$req}.";
        }

        return 'La licencia '.TiposLicenciaBolivia::etiqueta($cond)
            ." no autoriza este vehículo (requiere licencia {$req} o superior).";
    }

    /** @return list<string> */
    public static function codigosAutorizados(?string $licenciaConductor): array
    {
        $conductor = self::normalizar($licenciaConductor);
        if ($conductor === null) {
            return [];
        }

        $rangoMax = self::RANGO[$conductor] ?? 0;
        if ($rangoMax <= 0) {
            return [];
        }

        return array_values(array_keys(array_filter(
            self::RANGO,
            fn (int $rango) => $rango <= $rangoMax
        )));
    }

    private static function normalizar(?string $codigo): ?string
    {
        if ($codigo === null || trim($codigo) === '') {
            return null;
        }

        return strtoupper(trim($codigo));
    }
}
