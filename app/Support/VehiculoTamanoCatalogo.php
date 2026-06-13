<?php

namespace App\Support;

final class VehiculoTamanoCatalogo
{
    /** @var array<string, string> */
    public const TAMANOS = [
        'pequeno' => 'Pequeño',
        'mediano' => 'Mediano',
        'grande' => 'Grande',
        'extra_grande' => 'Extra grande',
    ];

    public static function etiqueta(?string $tamano): ?string
    {
        if ($tamano === null || $tamano === '') {
            return null;
        }

        return self::TAMANOS[$tamano] ?? ucfirst(str_replace('_', ' ', $tamano));
    }
}
