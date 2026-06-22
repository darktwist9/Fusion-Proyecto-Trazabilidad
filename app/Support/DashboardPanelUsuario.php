<?php

namespace App\Support;

use App\Models\Usuario;
use Illuminate\Support\Collection;

final class DashboardPanelUsuario
{
    public const PANEL_TRANSPORTISTA = 'transportista';

    public const PANEL_MINORISTA = 'minorista';

    public const PANEL_MAYORISTA = 'mayorista';

    public const PANEL_PLANTA = 'planta';

    public const PANEL_AGRICOLA = 'agricola';

    /** @return list<string> */
    public static function rolesSpatie(string $panel): array
    {
        return match ($panel) {
            self::PANEL_TRANSPORTISTA => ['transportista'],
            self::PANEL_MINORISTA => ['minorista'],
            self::PANEL_MAYORISTA => ['mayorista', 'jefe_mayorista'],
            self::PANEL_PLANTA => ['planta', 'jefe_planta'],
            self::PANEL_AGRICOLA => ['jefe_agricultor', 'agricultor'],
            default => [],
        };
    }

    public static function etiquetaPanel(string $panel): string
    {
        return match ($panel) {
            self::PANEL_TRANSPORTISTA => 'Transportista',
            self::PANEL_MINORISTA => 'Minorista',
            self::PANEL_MAYORISTA => 'Mayorista',
            self::PANEL_PLANTA => 'Planta',
            self::PANEL_AGRICOLA => 'Agrícola',
            default => 'Usuario',
        };
    }

    /** @return Collection<int, Usuario> */
    public static function usuariosParaPanel(string $panel): Collection
    {
        $roles = self::rolesSpatie($panel);
        if ($roles === []) {
            return collect();
        }

        return Usuario::query()
            ->where('activo', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->get(['usuarioid', 'nombre', 'apellido', 'nombreusuario', 'email']);
    }

    public static function usuarioTieneRolPanel(Usuario $usuario, string $panel): bool
    {
        $roles = self::rolesSpatie($panel);

        return $roles !== [] && $usuario->hasAnyRole($roles);
    }

    /**
     * @return array{sujeto: ?Usuario, todos: bool, es_admin: bool}
     */
    public static function resolver(?Usuario $viewer, DashboardFiltros $filtros, string $panel): array
    {
        $esAdmin = UsuarioRol::esAdminGlobal($viewer);

        if (! $esAdmin) {
            return [
                'sujeto' => $viewer,
                'todos' => false,
                'es_admin' => false,
            ];
        }

        if ($filtros->usuarioId) {
            $sujeto = Usuario::query()->find($filtros->usuarioId);
            if ($sujeto && self::usuarioTieneRolPanel($sujeto, $panel)) {
                return [
                    'sujeto' => $sujeto,
                    'todos' => false,
                    'es_admin' => true,
                ];
            }
        }

        return [
            'sujeto' => null,
            'todos' => true,
            'es_admin' => true,
        ];
    }

    public static function etiquetaVista(bool $todos, ?Usuario $sujeto, string $panel): string
    {
        if ($todos) {
            return 'Vista global — todos los '.strtolower(self::etiquetaPanel($panel)).'s';
        }

        if ($sujeto) {
            return 'Viendo panel de '.$sujeto->nombreCompleto();
        }

        return '';
    }
}
