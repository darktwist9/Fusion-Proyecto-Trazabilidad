<?php

namespace App\Support;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Models\Usuario;
use Carbon\Carbon;

final class TransportistaLoginNotificacion
{
    /**
     * Envíos recién asignados al transportista que aún no se le notificaron en modal.
     *
     * @return list<array{clave: string, codigo: string, url: string, producto: string}>
     */
    public static function nuevasAsignacionesDesdeLogin(Usuario $user, ?Carbon $ultimoLoginPrevio): array
    {
        if (! UsuarioRol::esTransportista($user)) {
            return [];
        }

        $items = collect();

        EnvioAsignacionMultiple::query()
            ->where('transportista_usuarioid', $user->usuarioid)
            ->whereNotIn('estado', ['recibido_planta', 'entregado', 'entregada', 'cancelado', 'cancelada'])
            ->with(['pedido.detalles'])
            ->orderByDesc('fecha_asignacion')
            ->limit(8)
            ->get()
            ->each(function (EnvioAsignacionMultiple $a) use ($items, $ultimoLoginPrevio) {
                if (EnvioAsignacionEstadoCatalogo::llegoADestino($a)) {
                    return;
                }

                if (! PedidoCatalogo::envioOperativoParaTransportista($a)) {
                    return;
                }

                if (! self::asignacionEsNueva($a->fecha_asignacion, $ultimoLoginPrevio)) {
                    return;
                }

                $items->push([
                    'clave' => TransportistaAsignacionNotificacionVista::claveAgricola((int) $a->envioasignacionmultipleid),
                    'codigo' => $a->externo_envio_id ?? $a->pedido?->numero_solicitud ?? '#'.$a->envioasignacionmultipleid,
                    'url' => route('logistica.asignaciones.cierre.panel', $a),
                    'producto' => $a->pedido?->detalles?->first()?->cultivo_personalizado ?? 'Envío agrícola',
                ]);
            });

        RutaDistribucion::query()
            ->where('transportista_usuarioid', $user->usuarioid)
            ->whereNotIn('estado', ['completada', 'cancelada', 'rechazada'])
            ->with(['pedidos.detalles', 'detallesTraslado.insumo'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->each(function (RutaDistribucion $r) use ($items, $user, $ultimoLoginPrevio) {
                if ($r->esTrasladoPlantaMayorista()) {
                    if (! in_array($r->estado, [
                        RutaDistribucionCatalogo::ESTADO_PLANIFICADA,
                        RutaDistribucionCatalogo::ESTADO_EN_RUTA,
                        RutaDistribucionCatalogo::ESTADO_PENDIENTE_APROBACION,
                    ], true)) {
                        return;
                    }

                    if (! self::asignacionEsNueva($r->created_at, $ultimoLoginPrevio)) {
                        return;
                    }

                    $detalle = $r->detallesTraslado?->first();
                    $items->push([
                        'clave' => TransportistaAsignacionNotificacionVista::claveRuta((int) $r->rutadistribucionid),
                        'codigo' => $r->codigo ?? $r->nombre ?? 'Traslado #'.$r->rutadistribucionid,
                        'url' => RutaDistribucionNavegacion::urlVer($r, $user),
                        'producto' => $detalle?->insumo?->nombre ?? $detalle?->producto_nombre ?? 'Planta → Mayorista',
                    ]);

                    return;
                }

                if (! self::asignacionEsNueva($r->created_at, $ultimoLoginPrevio)) {
                    return;
                }

                $primer = $r->pedidos?->first()?->detalles?->first();
                $items->push([
                    'clave' => TransportistaAsignacionNotificacionVista::claveRuta((int) $r->rutadistribucionid),
                    'codigo' => $r->codigo ?? $r->nombre ?? 'Ruta #'.$r->rutadistribucionid,
                    'url' => RutaDistribucionNavegacion::urlVer($r, $user),
                    'producto' => $primer?->producto_nombre ?? $primer?->cultivo_personalizado ?? 'Distribución',
                ]);
            });

        return TransportistaAsignacionNotificacionVista::filtrarPendientes(
            (int) $user->usuarioid,
            $items
                ->unique(fn (array $row) => $row['clave'])
                ->values()
                ->all()
        );
    }

    private static function asignacionEsNueva(?Carbon $fechaAsignacion, ?Carbon $ultimoLoginPrevio): bool
    {
        if ($fechaAsignacion === null) {
            return false;
        }

        if ($ultimoLoginPrevio === null) {
            return $fechaAsignacion->greaterThanOrEqualTo(now()->subHours(24));
        }

        return $fechaAsignacion->greaterThan($ultimoLoginPrevio);
    }
}
