<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\AlmacenMovimiento;
use App\Models\DetallePedidoDistribucion;
use App\Models\Insumo;
use App\Models\PedidoDistribucion;
use App\Models\RutaDistribucion;
use App\Models\TipoInsumo;
use App\Models\TipoMovimientoAlmacen;
use App\Models\Usuario;
use App\Support\InsumoCatalogo;
use App\Support\PedidoDistribucionCatalogo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecepcionPuntoVentaService
{
    public function __construct(
        private readonly DistribucionRutaService $rutas
    ) {}

    public function confirmar(PedidoDistribucion $pedido, Usuario $usuario): void
    {
        if (! PedidoDistribucionCatalogo::puedeConfirmarRecepcion($pedido)) {
            throw new \InvalidArgumentException('El pedido no está en tránsito o ya fue recibido.');
        }

        $pedido->load(['detalles.insumo.unidadMedida', 'puntoVenta.almacen']);

        $puntoVenta = $pedido->puntoVenta;
        if ($puntoVenta === null) {
            throw new \InvalidArgumentException('Pedido sin punto de venta asociado.');
        }

        app(PuntoVentaAlmacenService::class)->crearAlmacenParaPuntoVenta($puntoVenta);
        $puntoVenta->refresh();

        $almacenPdv = $puntoVenta->almacen;
        if ($almacenPdv === null) {
            throw new \InvalidArgumentException('No se pudo vincular el almacén del punto de venta.');
        }

        $tipoIngreso = TipoMovimientoAlmacen::activosPorNaturaleza('ingreso')->firstOrFail();
        $tipoSalida = TipoMovimientoAlmacen::activosPorNaturaleza('salida')->firstOrFail();

        DB::transaction(function () use ($pedido, $usuario, $almacenPdv, $tipoIngreso, $tipoSalida) {
            foreach ($pedido->detalles as $detalle) {
                $this->transferirDetalle($detalle, $pedido, $usuario, $almacenPdv, $tipoIngreso, $tipoSalida);
            }

            $pedido->update([
                'estado' => PedidoDistribucionCatalogo::ESTADO_RECIBIDO,
                'fecha_recepcion' => now(),
            ]);
        });

        $pedido->refresh();
        if ($pedido->rutadistribucionid) {
            $ruta = RutaDistribucion::query()->find($pedido->rutadistribucionid);
            if ($ruta) {
                $this->rutas->sincronizarEstadoRuta($ruta);
            }
        }
    }

    private function transferirDetalle(
        DetallePedidoDistribucion $detalle,
        PedidoDistribucion $pedido,
        Usuario $usuario,
        Almacen $almacenPdv,
        TipoMovimientoAlmacen $tipoIngreso,
        TipoMovimientoAlmacen $tipoSalida
    ): void {
        $cantidad = (float) $detalle->cantidad;
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('Cantidad inválida en el detalle del pedido.');
        }

        $detalle->loadMissing('presentacion.tipoEmpaque');
        $presentacion = $detalle->presentacion;
        $kgMovimiento = $presentacion
            ? round($cantidad * $presentacion->pesoNetoKg(), 4)
            : $cantidad;

        $insumoOrigen = $detalle->insumo;
        if ($insumoOrigen === null) {
            throw new \InvalidArgumentException('Producto de planta no encontrado.');
        }

        if ($presentacion && $kgMovimiento > (float) $insumoOrigen->stock + 0.0001) {
            throw new \InvalidArgumentException(
                "Stock insuficiente en origen para «{$insumoOrigen->nombre}». Disponible: {$insumoOrigen->stock} kg."
            );
        }

        if (! $presentacion && ! $insumoOrigen->tieneStockSuficiente($cantidad)) {
            throw new \InvalidArgumentException(
                "Stock insuficiente en planta para «{$insumoOrigen->nombre}». Disponible: {$insumoOrigen->stock}."
            );
        }

        $nombrePdv = filled($detalle->producto_nombre)
            ? trim((string) $detalle->producto_nombre)
            : $insumoOrigen->nombre;

        $insumoDestino = Insumo::query()
            ->where('almacenid', $almacenPdv->almacenid)
            ->where(function ($q) use ($nombrePdv, $insumoOrigen) {
                $q->whereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($nombrePdv))])
                    ->orWhereRaw('LOWER(TRIM(nombre)) = ?', [Str::lower(trim($insumoOrigen->nombre))]);
            })
            ->first();

        if ($insumoDestino === null) {
            $codigo = 'TRZ-PDV-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
            $insumoDestino = Insumo::create([
                'nombre' => $nombrePdv,
                'codigo_trazabilidad' => $codigo,
                'tipoinsumoid' => $insumoOrigen->tipoinsumoid ?? TipoInsumo::query()->value('tipoinsumoid'),
                'unidadmedidaid' => $insumoOrigen->unidadmedidaid,
                'stock' => 0,
                'stockminimo' => InsumoCatalogo::UMBRAL_ALERTA_STOCK,
                'descripcion' => 'Producto recibido desde mayorista — '.$pedido->numero_solicitud,
                'almacenid' => $almacenPdv->almacenid,
            ]);
        }

        $ref = $pedido->numero_solicitud;
        $obsUnidades = $presentacion
            ? number_format($cantidad, 0).' '.$presentacion->etiquetaUnidad().' ('.number_format($kgMovimiento, 2).' kg)'
            : number_format($cantidad, 2).' ud';

        AlmacenMovimiento::create([
            'almacenid' => $insumoOrigen->almacenid,
            'insumoid' => $insumoOrigen->insumoid,
            'tipo_movimiento_almacenid' => $tipoSalida->tipo_movimiento_almacenid,
            'usuarioid' => $usuario->usuarioid,
            'fecha' => now()->toDateString(),
            'cantidad' => $kgMovimiento,
            'referencia' => $ref,
            'destino_motivo' => $almacenPdv->nombre,
            'observaciones' => '[Distribución PDV — salida mayorista] '.$ref.' · '.$obsUnidades,
        ]);

        AlmacenMovimiento::create([
            'almacenid' => $almacenPdv->almacenid,
            'insumoid' => $insumoDestino->insumoid,
            'tipo_movimiento_almacenid' => $tipoIngreso->tipo_movimiento_almacenid,
            'usuarioid' => $usuario->usuarioid,
            'fecha' => now()->toDateString(),
            'cantidad' => $kgMovimiento,
            'referencia' => $ref,
            'destino_motivo' => $almacenPdv->nombre,
            'observaciones' => '[Recepción PDV] '.$ref.' · '.$obsUnidades,
        ]);

        $insumoOrigen->decrementarStock($kgMovimiento);
        $insumoDestino->incrementarStock($kgMovimiento);
    }
}
