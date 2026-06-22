<?php

namespace App\Support;

/**
 * Etiquetas de usuario para el tramo «llegó al destino, pendiente cierre/recepción».
 * La clave técnica del API sigue siendo {@code esperando_confirmacion}.
 */
final class EnvioEstadoRecepcionCatalogo
{
    public const ETIQUETA_ESPERANDO_RECEPCION = 'Esperando recepción';

    public const ETIQUETA_EN_CURSO = 'En curso';

    public const ETIQUETA_FINALIZADA = 'Finalizada';

    public const MENSAJE_ETA_ESPERANDO = 'Esperando recepción en destino. Confirme llegada, incidentes y firmas para cerrar el envío.';

    public const MENSAJE_LISTA_TIEMPO = 'Esperando recepción';

    public static function etiquetaBadge(bool $completada, bool $esperandoRecepcion): string
    {
        if ($completada) {
            return self::ETIQUETA_FINALIZADA;
        }

        return $esperandoRecepcion
            ? self::ETIQUETA_ESPERANDO_RECEPCION
            : self::ETIQUETA_EN_CURSO;
    }

    public static function etiquetaListaEnCamino(string $variante = 'destino'): string
    {
        return match ($variante) {
            'planta' => 'En camino a planta',
            'mayorista' => 'En camino al mayorista',
            'pdv' => 'En camino al PDV',
            default => 'En camino',
        };
    }

    public static function etiquetaListaEsperando(): string
    {
        return self::ETIQUETA_ESPERANDO_RECEPCION;
    }
}
