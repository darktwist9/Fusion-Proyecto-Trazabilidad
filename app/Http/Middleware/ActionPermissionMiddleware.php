<?php

namespace App\Http\Middleware;

use App\Support\DocumentoEntregaAcceso;
use App\Support\RutaTiempoRealAcceso;
use App\Support\UsuarioRol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActionPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if (UsuarioRol::esAdminGlobal($user)) {
            return $next($request);
        }

        $permission = config("permission_matrix.modules.{$module}.{$action}");
        if (!$permission) {
            abort(403, "Permiso no definido para {$module}.{$action}");
        }

        if (!$user->can($permission)) {
            if ($module === 'documentos' && $action === 'read' && DocumentoEntregaAcceso::puedeAccederModulo($user)) {
                return $next($request);
            }
            if ($module === 'asignaciones' && $action === 'read' && RutaTiempoRealAcceso::puedeAccederModulo($user)) {
                return $next($request);
            }
            abort(403);
        }

        return $next($request);
    }
}

