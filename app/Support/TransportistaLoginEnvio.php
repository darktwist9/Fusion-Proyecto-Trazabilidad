<?php

namespace App\Support;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Models\Usuario;

final class TransportistaLoginEnvio
{
    /**
     * @return array{codigo: string, url: string, producto: string}|null
     */
    public static function envioPrioritario(Usuario $user): ?array
    {
        if (! UsuarioRol::esTransportista($user)) {
            return null;
        }

        $asignacion = EnvioAsignacionMultiple::query()
            ->where('transportista_usuarioid', $user->usuarioid)
            ->whereIn('estado', ['asignado', 'asignada', 'pendiente', 'creada', 'en_ruta', 'en_transporte_planta', 'en_transito'])
            ->whereNull('fecha_recepcion_planta')
            ->with(['pedido.detalles'])
            ->orderByDesc('fecha_asignacion')
            ->first();

        if ($asignacion && ! EnvioAsignacionEstadoCatalogo::llegoADestino($asignacion)) {
            return [
                'codigo' => $asignacion->externo_envio_id ?? $asignacion->pedido?->numero_solicitud ?? '#'.$asignacion->envioasignacionmultipleid,
                'producto' => $asignacion->pedido?->detalles?->first()?->cultivo_personalizado ?? 'Envío agrícola',
                'url' => route('logistica.asignaciones.cierre.panel', $asignacion),
            ];
        }

        $traslado = RutaDistribucion::query()
            ->where('transportista_usuarioid', $user->usuarioid)
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA)
            ->whereIn('estado', [
                RutaDistribucionCatalogo::ESTADO_PLANIFICADA,
                RutaDistribucionCatalogo::ESTADO_EN_RUTA,
            ])
            ->with(['detallesTraslado.insumo'])
            ->orderByDesc('updated_at')
            ->first();

        if ($traslado) {
            $detalle = $traslado->detallesTraslado?->first();

            return [
                'codigo' => $traslado->codigo,
                'producto' => $detalle?->insumo?->nombre ?? $detalle?->producto_nombre ?? 'Traslado planta → mayorista',
                'url' => route('logistica.traslados-planta.cierre.panel', $traslado),
            ];
        }

        return null;
    }
}
