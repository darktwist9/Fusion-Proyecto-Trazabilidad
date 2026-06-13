<?php

namespace App\Support;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;

final class SimulacionRutaCatalogo
{
    public const TIPO_AGRICOLA = 'agricola';

    public const TIPO_DISTRIBUCION = 'distribucion';

    /** Segundos por kilómetro para la simulación (≈ 2 min/km). */
    public const SEGUNDOS_POR_KM = 120;

    public const DURACION_MIN_SEG = 90;

    public const DURACION_MAX_SEG = 600;

    public static function simulacionActivaAgricola(EnvioAsignacionMultiple $envio): bool
    {
        return $envio->simulacion_inicio_at !== null
            && ! EnvioAsignacionEstadoCatalogo::llegoADestino($envio);
    }

    public static function simulacionActivaDistribucion(RutaDistribucion $ruta): bool
    {
        return $ruta->simulacion_inicio_at !== null
            && $ruta->estado === RutaDistribucionCatalogo::ESTADO_EN_RUTA;
    }

    public static function puedeEmpezarAgricola(EnvioAsignacionMultiple $envio): bool
    {
        if ($envio->simulacion_inicio_at !== null) {
            return false;
        }

        if (EnvioAsignacionEstadoCatalogo::llegoADestino($envio)) {
            return false;
        }

        if (! $envio->transportista_usuarioid) {
            return false;
        }

        $estado = strtolower(trim((string) ($envio->estado ?? '')));
        if (! in_array($estado, ['asignado', 'asignada', 'pendiente', 'creada'], true)) {
            return false;
        }

        if ($envio->pedido && ! PedidoCatalogo::listoParaLogistica($envio->pedido)) {
            return false;
        }

        return true;
    }

    public static function puedeEmpezarDistribucion(RutaDistribucion $ruta): bool
    {
        if ($ruta->simulacion_inicio_at !== null) {
            return false;
        }

        if ($ruta->estado !== RutaDistribucionCatalogo::ESTADO_PLANIFICADA) {
            return false;
        }

        return $ruta->transportista_usuarioid !== null;
    }
}
