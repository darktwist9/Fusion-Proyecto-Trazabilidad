<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\DashboardFiltros;
use App\Support\DashboardPanelUsuario;
use App\Support\TransporteIngresoService;
use App\Support\UsuarioRol;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportistaIngresoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless(
            $user && (
                UsuarioRol::esTransportista($user)
                || UsuarioRol::esAdminGlobal($user)
                || $user->can('panel_transportista.view')
            ),
            403
        );

        $filtros = DashboardFiltros::desdeRequest($request);
        $ctx = DashboardPanelUsuario::resolver($user, $filtros, DashboardPanelUsuario::PANEL_TRANSPORTISTA);

        if ($ctx['todos']) {
            $resumen = TransporteIngresoService::resumenPeriodoGlobal($filtros);
            $servicios = TransporteIngresoService::listarCompletadosGlobal($filtros);
        } else {
            $transportista = $ctx['sujeto'] ?? $user;
            $transportistaId = (int) $transportista->usuarioid;
            $resumen = TransporteIngresoService::resumenPeriodo($transportistaId, $filtros);
            $servicios = TransporteIngresoService::listarCompletados($transportistaId, $filtros);
        }

        return view('logistica.transportista.ingresos', [
            'resumen' => $resumen,
            'servicios' => $servicios,
            'filtros' => $filtros,
            'mostrarUsuario' => $ctx['es_admin'],
            'usuariosPanel' => $ctx['es_admin']
                ? DashboardPanelUsuario::usuariosParaPanel(DashboardPanelUsuario::PANEL_TRANSPORTISTA)
                : collect(),
            'vistaTodosUsuarios' => $ctx['todos'],
            'usuarioFiltrado' => $ctx['sujeto'],
        ]);
    }
}
