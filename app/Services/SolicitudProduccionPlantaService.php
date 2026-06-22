<?php

namespace App\Services;

use App\Models\PedidoDistribucion;
use App\Models\SolicitudProduccionPlanta;
use App\Models\Usuario;
use App\Support\PedidoDistribucionCatalogo;
use App\Support\SolicitudProduccionPlantaCatalogo;
use App\Support\UsuarioRol;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SolicitudProduccionPlantaService
{
    public function crearDesdePedido(PedidoDistribucion $pedido, Usuario $mayorista): SolicitudProduccionPlanta
    {
        if (! UsuarioRol::puedeGestionarDistribucionMayorista($mayorista)) {
            throw new InvalidArgumentException('Solo el centro mayorista puede solicitar producción a planta.');
        }

        if (! $pedido->requiere_coordinacion_planta || $pedido->coordinacion_planta_resuelta) {
            throw new InvalidArgumentException('Este pedido no requiere coordinación con planta.');
        }

        $pendiente = SolicitudProduccionPlanta::query()
            ->where('pedidodistribucionid', $pedido->pedidodistribucionid)
            ->whereIn('estado', [
                SolicitudProduccionPlantaCatalogo::ESTADO_PENDIENTE,
                SolicitudProduccionPlantaCatalogo::ESTADO_ACEPTADA,
                SolicitudProduccionPlantaCatalogo::ESTADO_EN_PRODUCCION,
            ])
            ->exists();

        if ($pendiente) {
            throw new InvalidArgumentException('Ya existe una solicitud activa a planta para este pedido.');
        }

        $pedido->loadMissing(['detalles.presentacion', 'detalles.insumoPlantaReferencia', 'puntoVenta']);
        $detalle = $pedido->detalles->first();

        if ($detalle === null) {
            throw new InvalidArgumentException('El pedido no tiene líneas de producto.');
        }

        $unidad = $detalle->presentacion?->etiquetaUnidad()
            ?? SolicitudProduccionPlantaCatalogo::etiquetaTipoEnvase($detalle->tipo_envase);

        return SolicitudProduccionPlanta::create([
            'numero_solicitud' => SolicitudProduccionPlantaCatalogo::generarNumeroSolicitud(),
            'pedidodistribucionid' => $pedido->pedidodistribucionid,
            'almacen_mayorista_destinoid' => $pedido->almacen_mayorista_origenid,
            'insumo_planta_referenciaid' => $detalle->insumo_planta_referenciaid,
            'insumo_presentacionid' => $detalle->insumo_presentacionid,
            'producto_nombre' => $detalle->producto_nombre ?: ($detalle->insumoPlantaReferencia?->nombre ?? 'Producto'),
            'tipo_envase' => $detalle->tipo_envase ?: $detalle->presentacion?->tipo_envase,
            'cantidad' => (float) $detalle->cantidad,
            'unidad_etiqueta' => $unidad,
            'estado' => SolicitudProduccionPlantaCatalogo::ESTADO_PENDIENTE,
            'fecha_entrega_deseada' => $pedido->fecha_entrega_deseada,
            'hora_entrega_deseada' => $pedido->hora_entrega_deseada,
            'observaciones' => $pedido->observaciones,
            'creado_por_usuarioid' => $mayorista->usuarioid,
            'fechapedido' => now(),
        ]);
    }

    public function aceptar(SolicitudProduccionPlanta $solicitud, Usuario $planta): SolicitudProduccionPlanta
    {
        if (! SolicitudProduccionPlantaCatalogo::puedeAceptarPlanta($solicitud)) {
            throw new InvalidArgumentException('La solicitud ya fue procesada.');
        }

        $solicitud->update([
            'estado' => SolicitudProduccionPlantaCatalogo::ESTADO_ACEPTADA,
            'aceptado_por_usuarioid' => $planta->usuarioid,
            'fecha_aceptacion' => now(),
        ]);

        return $solicitud->fresh(['pedidoDistribucion', 'creadoPor']);
    }

    public function marcarEnProduccion(SolicitudProduccionPlanta $solicitud): SolicitudProduccionPlanta
    {
        if (! SolicitudProduccionPlantaCatalogo::puedeMarcarProduccion($solicitud)) {
            throw new InvalidArgumentException('No se puede marcar en producción.');
        }

        $solicitud->update(['estado' => SolicitudProduccionPlantaCatalogo::ESTADO_EN_PRODUCCION]);

        return $solicitud->fresh();
    }

    public function completar(SolicitudProduccionPlanta $solicitud): SolicitudProduccionPlanta
    {
        if (! SolicitudProduccionPlantaCatalogo::puedeCompletar($solicitud)) {
            throw new InvalidArgumentException('No se puede completar esta solicitud.');
        }

        return DB::transaction(function () use ($solicitud) {
            $solicitud->update([
                'estado' => SolicitudProduccionPlantaCatalogo::ESTADO_COMPLETADA,
                'fecha_completada' => now(),
            ]);

            if ($solicitud->pedidodistribucionid) {
                $pedido = PedidoDistribucion::query()->find($solicitud->pedidodistribucionid);
                if ($pedido !== null) {
                    $pedido->update(['coordinacion_planta_resuelta' => true]);
                }
            }

            return $solicitud->fresh(['pedidoDistribucion']);
        });
    }

    public function rechazar(SolicitudProduccionPlanta $solicitud, ?string $motivo, Usuario $planta): SolicitudProduccionPlanta
    {
        if (! SolicitudProduccionPlantaCatalogo::puedeAceptarPlanta($solicitud)) {
            throw new InvalidArgumentException('La solicitud ya fue procesada.');
        }

        $obs = trim(($solicitud->observaciones ?? '')."\n[Rechazada planta] ".($motivo ?: 'Sin motivo.'));

        $solicitud->update([
            'estado' => SolicitudProduccionPlantaCatalogo::ESTADO_RECHAZADA,
            'observaciones' => $obs,
            'aceptado_por_usuarioid' => $planta->usuarioid,
            'fecha_aceptacion' => now(),
        ]);

        return $solicitud->fresh();
    }
}
