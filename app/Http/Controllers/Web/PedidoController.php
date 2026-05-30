<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\EnvioAsignacionMultiple;
use App\Models\Pedido;
use App\Support\EnvioAsignacionEstadoCatalogo;
use App\Support\PedidoCatalogo;
use App\Support\RutaPorCallesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    public function index()
    {
        $pedidos = Pedido::with(['detalles.insumo'])->orderByDesc('pedidoid')->get();

        return view('pedidos.index', compact('pedidos'));
    }

    public function create()
    {
        $numeroSolicitud = PedidoCatalogo::generarNumeroSolicitud();
        $productosDisponibles = PedidoCatalogo::opcionesProductoPedido();

        return view('pedidos.create', [
            'numeroSolicitud' => $numeroSolicitud,
            'productosDisponibles' => $productosDisponibles,
            'hubLat' => RutaPorCallesService::HUB_LAT,
            'hubLng' => RutaPorCallesService::HUB_LNG,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'origen_latitud' => 'required|numeric|between:-90,90',
            'origen_longitud' => 'required|numeric|between:-180,180',
            'origen_direccion' => 'nullable|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'direccion_texto' => 'nullable|string|max:255',
            'fechaEntregaDeseada' => 'nullable|date',
            'observaciones' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_ref' => ['required', 'string', 'regex:/^(insumo|cosecha|cultivo):\d+$/'],
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.observaciones' => 'nullable|string',
        ], [
            'detalles.*.producto_ref.regex' => 'Seleccione un producto válido de producción agrícola.',
        ]);

        DB::transaction(function () use ($data) {
            $detallesInput = $data['detalles'];
            unset($data['detalles']);

            $pedido = Pedido::create([
                ...$data,
                'numero_solicitud' => PedidoCatalogo::generarNumeroSolicitud(),
                'nombre_planta' => null,
                'estado' => PedidoCatalogo::ESTADO_INICIAL,
                'fechapedido' => now(),
            ]);

            foreach ($detallesInput as $detalle) {
                $producto = PedidoCatalogo::resolverProductoPedido($detalle['producto_ref']);

                $pedido->detalles()->create([
                    'insumoid' => $producto['insumoid'],
                    'producto_ref' => $detalle['producto_ref'],
                    'produccionalmacenamientoid' => str_starts_with($detalle['producto_ref'], 'cosecha:')
                        ? (int) substr($detalle['producto_ref'], 8)
                        : null,
                    'cultivo_personalizado' => $producto['cultivo'],
                    'cantidad' => $detalle['cantidad'],
                    'observaciones' => $detalle['observaciones'] ?? null,
                ]);
            }

            EnvioAsignacionMultiple::firstOrCreate(
                ['externo_envio_id' => $pedido->numero_solicitud],
                EnvioAsignacionEstadoCatalogo::applyToAttributes([
                    'pedidoid' => $pedido->pedidoid,
                    'estado' => 'pendiente',
                ])
            );
        });

        return redirect()->route('pedidos.index')->with('success', 'Pedido registrado. Producción agrícola debe aceptarlo y reservar stock antes de asignar transportista.');
    }

    public function show($id)
    {
        $pedido = Pedido::with('detalles')->findOrFail($id);

        return view('pedidos.show', compact('pedido'));
    }

    public function update(Request $request, Pedido $pedido)
    {
        $data = $request->validate([
            'estado' => 'required|in:sin asignacion,pendiente,confirmado,en produccion,rechazado',
        ]);

        if (in_array($data['estado'], PedidoCatalogo::estadosListosParaLogistica(), true)
            && PedidoCatalogo::pendienteAprobacionAgricola($pedido)) {
            return back()->with('error', 'Solo producción agrícola puede aceptar el pedido y reservar stock del almacén.');
        }

        $pedido->update($data);

        return back();
    }

    public function destroy($id)
    {
        Pedido::findOrFail($id)->delete();

        return redirect()->route('pedidos.index');
    }
}
