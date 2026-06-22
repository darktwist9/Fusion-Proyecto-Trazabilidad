<?php

namespace App\Support;

use App\Models\AsignacionEtapaPlanta;
use App\Models\Usuario;
use Carbon\Carbon;

final class OperarioPlantaLoginNotificacion
{
    /**
     * Tareas de transformación recién asignadas al operario de planta.
     *
     * @return list<array{clave: string, proceso: string, maquina: string, lote: string, url: string}>
     */
    public static function nuevasTareasDesdeLogin(Usuario $user, ?Carbon $ultimoLoginPrevio): array
    {
        if (! UsuarioRol::esOperarioPlanta($user)) {
            return [];
        }

        $items = AsignacionEtapaPlanta::query()
            ->with(['proceso', 'maquina', 'loteProduccion'])
            ->where('operador_usuarioid', $user->usuarioid)
            ->pendientes()
            ->orderByDesc('creado_en')
            ->limit(8)
            ->get()
            ->filter(fn (AsignacionEtapaPlanta $a) => self::asignacionEsNueva($a->creado_en, $ultimoLoginPrevio))
            ->map(fn (AsignacionEtapaPlanta $a) => [
                'clave' => OperarioPlantaTareaNotificacionVista::clave((int) $a->asignacionetapaplantaid),
                'proceso' => $a->proceso?->nombre ?? 'Transformación',
                'maquina' => $a->maquina?->nombre ?? 'Maquinaria',
                'lote' => $a->loteProduccion?->codigo_lote ?? '—',
                'url' => route('tareas-planta.show', $a),
            ])
            ->values()
            ->all();

        return OperarioPlantaTareaNotificacionVista::filtrarPendientes((int) $user->usuarioid, $items);
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
