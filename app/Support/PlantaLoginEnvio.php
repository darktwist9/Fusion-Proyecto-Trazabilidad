<?php

namespace App\Support;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Models\Usuario;

final class PlantaLoginEnvio
{
    /**
     * Envío logístico activo que requiere atención de planta (prioridad operativa).
     *
     * @return array{codigo: string, url: string, producto: string, tipo: string}|null
     */
    public static function envioPrioritario(?Usuario $user): ?array
    {
        if (! $user || ! self::aplicaRedirect($user)) {
            return null;
        }

        $trasladoEnRuta = RutaDistribucion::query()
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA)
            ->where('estado', RutaDistribucionCatalogo::ESTADO_EN_RUTA)
            ->with(['detallesTraslado.insumo'])
            ->orderByDesc('updated_at')
            ->first();

        if ($trasladoEnRuta) {
            return self::mapearTraslado($trasladoEnRuta, route('logistica.traslados-planta.cierre.panel', $trasladoEnRuta));
        }

        $trasladoPlanificado = RutaDistribucion::query()
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA)
            ->where('estado', RutaDistribucionCatalogo::ESTADO_PLANIFICADA)
            ->with(['detallesTraslado.insumo'])
            ->orderByDesc('updated_at')
            ->first();

        if ($trasladoPlanificado) {
            return self::mapearTraslado($trasladoPlanificado, route('logistica.traslados-planta.show', $trasladoPlanificado));
        }

        $trasladoPendiente = RutaDistribucion::query()
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA)
            ->where('estado', RutaDistribucionCatalogo::ESTADO_PENDIENTE_APROBACION)
            ->with(['detallesTraslado.insumo'])
            ->orderByDesc('created_at')
            ->first();

        if ($trasladoPendiente) {
            return self::mapearTraslado($trasladoPendiente, route('logistica.traslados-planta.show', $trasladoPendiente));
        }

        $asignacion = EnvioAsignacionMultiple::query()
            ->whereIn('estado', ['en_ruta', 'en_transporte_planta', 'en_transito'])
            ->whereNull('fecha_recepcion_planta')
            ->with(['pedido.detalles'])
            ->orderByDesc('fecha_asignacion')
            ->first();

        if ($asignacion) {
            $pedido = $asignacion->pedido;

            return [
                'tipo' => 'agricola',
                'codigo' => $asignacion->externo_envio_id ?? $pedido?->numero_solicitud ?? '#'.$asignacion->envioasignacionmultipleid,
                'producto' => $pedido?->detalles?->first()?->cultivo_personalizado ?? 'Envío agrícola hacia planta',
                'url' => route('logistica.asignaciones.show', $asignacion),
            ];
        }

        return null;
    }

    /**
     * @return list<array{codigo: string, url: string, producto: string, tipo: string}>
     */
    public static function enviosPendientesPlanta(?Usuario $user, int $limite = 5): array
    {
        if (! $user || ! self::aplicaRedirect($user)) {
            return [];
        }

        $items = collect();

        RutaDistribucion::query()
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA)
            ->whereNotIn('estado', [
                RutaDistribucionCatalogo::ESTADO_COMPLETADA,
                RutaDistribucionCatalogo::ESTADO_CANCELADA,
                RutaDistribucionCatalogo::ESTADO_RECHAZADA,
            ])
            ->with(['detallesTraslado.insumo'])
            ->orderByDesc('updated_at')
            ->limit($limite)
            ->get()
            ->each(function (RutaDistribucion $ruta) use ($items) {
                $url = $ruta->estado === RutaDistribucionCatalogo::ESTADO_EN_RUTA
                    ? route('logistica.traslados-planta.cierre.panel', $ruta)
                    : route('logistica.traslados-planta.show', $ruta);
                $items->push(self::mapearTraslado($ruta, $url));
            });

        EnvioAsignacionMultiple::query()
            ->whereIn('estado', ['en_ruta', 'en_transporte_planta', 'en_transito'])
            ->whereNull('fecha_recepcion_planta')
            ->with(['pedido.detalles'])
            ->orderByDesc('fecha_asignacion')
            ->limit($limite)
            ->get()
            ->each(function (EnvioAsignacionMultiple $a) use ($items) {
                $pedido = $a->pedido;
                $items->push([
                    'tipo' => 'agricola',
                    'codigo' => $a->externo_envio_id ?? $pedido?->numero_solicitud ?? '#'.$a->envioasignacionmultipleid,
                    'producto' => $pedido?->detalles?->first()?->cultivo_personalizado ?? 'Envío agrícola hacia planta',
                    'url' => route('logistica.asignaciones.show', $a),
                ]);
            });

        return $items
            ->unique(fn (array $row) => $row['url'])
            ->take($limite)
            ->values()
            ->all();
    }

    private static function aplicaRedirect(Usuario $user): bool
    {
        return UsuarioRol::esJefePlanta($user)
            || ($user->can('panel_planta.view') && $user->can('pedidos.view'));
    }

    /** @return array{codigo: string, url: string, producto: string, tipo: string} */
    private static function mapearTraslado(RutaDistribucion $ruta, string $url): array
    {
        $detalle = $ruta->detallesTraslado?->first();

        return [
            'tipo' => 'planta_mayorista',
            'codigo' => $ruta->codigo,
            'producto' => $detalle?->insumo?->nombre ?? $ruta->nombre ?? 'Traslado planta → mayorista',
            'url' => $url,
        ];
    }
}
