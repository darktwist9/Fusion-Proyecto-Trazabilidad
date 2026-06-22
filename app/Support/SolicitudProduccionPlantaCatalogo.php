<?php

namespace App\Support;

use App\Models\SolicitudProduccionPlanta;
use Illuminate\Support\Carbon;

final class SolicitudProduccionPlantaCatalogo
{
    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_ACEPTADA = 'aceptada';

    public const ESTADO_EN_PRODUCCION = 'en_produccion';

    public const ESTADO_COMPLETADA = 'completada';

    public const ESTADO_RECHAZADA = 'rechazada';

    /** @return list<string> */
    public static function tiposEnvase(): array
    {
        return ['bolsa', 'lata', 'frasco', 'bidon', 'caja'];
    }

    public static function etiquetaTipoEnvase(?string $tipo): string
    {
        return match ($tipo) {
            'bolsa' => 'Bolsa',
            'lata' => 'Lata',
            'frasco' => 'Frasco',
            'bidon' => 'Bidón',
            'caja' => 'Caja',
            default => $tipo ? ucfirst($tipo) : '—',
        };
    }

    public static function generarNumeroSolicitud(): string
    {
        $fecha = Carbon::now()->format('Ymd');
        $ultimo = SolicitudProduccionPlanta::query()
            ->where('numero_solicitud', 'like', "SOL-PLT-{$fecha}-%")
            ->orderByDesc('solicitudproduccionplantaid')
            ->value('numero_solicitud');

        $secuencia = 1;
        if ($ultimo && preg_match('/-(\d+)$/', $ultimo, $m)) {
            $secuencia = ((int) $m[1]) + 1;
        }

        return sprintf('SOL-PLT-%s-%04d', $fecha, $secuencia);
    }

    public static function puedeAceptarPlanta(SolicitudProduccionPlanta $solicitud): bool
    {
        return $solicitud->estado === self::ESTADO_PENDIENTE;
    }

    public static function puedeMarcarProduccion(SolicitudProduccionPlanta $solicitud): bool
    {
        return in_array($solicitud->estado, [self::ESTADO_ACEPTADA, self::ESTADO_EN_PRODUCCION], true);
    }

    public static function puedeCompletar(SolicitudProduccionPlanta $solicitud): bool
    {
        return in_array($solicitud->estado, [self::ESTADO_ACEPTADA, self::ESTADO_EN_PRODUCCION], true);
    }

    public static function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            self::ESTADO_PENDIENTE => 'Pendiente en planta',
            self::ESTADO_ACEPTADA => 'Aceptada',
            self::ESTADO_EN_PRODUCCION => 'En producción',
            self::ESTADO_COMPLETADA => 'Completada',
            self::ESTADO_RECHAZADA => 'Rechazada',
            default => ucfirst(str_replace('_', ' ', $estado)),
        };
    }
}
