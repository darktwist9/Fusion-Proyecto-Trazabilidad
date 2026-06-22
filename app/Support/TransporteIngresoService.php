<?php

namespace App\Support;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class TransporteIngresoService
{
    /**
     * @return array{total_bs: float, servicios: int, agricola_bs: float, distribucion_bs: float}
     */
    public static function resumenPeriodo(int $transportistaId, ?DashboardFiltros $filtros = null): array
    {
        $filtros ??= DashboardFiltros::desdeRequest(request());

        $agricola = self::queryEnviosCompletados($transportistaId);
        $distribucion = self::queryRutasCompletadas($transportistaId);
        $filtros->aplicarFecha($agricola, 'fecha_recepcion_planta');
        $filtros->aplicarFecha($distribucion, 'fecha_salida');

        return self::totalesDesdeQueries($agricola, $distribucion);
    }

    /** Resumen agregado de todos los transportistas (vista admin global). */
    public static function resumenPeriodoGlobal(?DashboardFiltros $filtros = null): array
    {
        $filtros ??= DashboardFiltros::desdeRequest(request());

        $agricola = EnvioAsignacionMultiple::query()
            ->whereNotNull('transportista_usuarioid')
            ->whereNotNull('costo_bs')
            ->where(function (Builder $q) {
                $q->whereIn('estado', ['recibido_planta', 'entregado', 'entregada'])
                    ->orWhereNotNull('fecha_recepcion_planta');
            });
        $distribucion = RutaDistribucion::query()
            ->whereNotNull('transportista_usuarioid')
            ->where('estado', RutaDistribucionCatalogo::ESTADO_COMPLETADA)
            ->whereNotNull('costo_bs');
        $filtros->aplicarFecha($agricola, 'fecha_recepcion_planta');
        $filtros->aplicarFecha($distribucion, 'fecha_salida');

        return self::totalesDesdeQueries($agricola, $distribucion);
    }

    /**
     * @return array{total_bs: float, servicios: int, agricola_bs: float, distribucion_bs: float}
     */
    private static function totalesDesdeQueries(Builder $agricola, Builder $distribucion): array
    {
        $agricolaBs = (float) (clone $agricola)->sum('costo_bs');
        $distribucionBs = (float) (clone $distribucion)->sum('costo_bs');

        return [
            'total_bs' => round($agricolaBs + $distribucionBs, 2),
            'servicios' => (clone $agricola)->count() + (clone $distribucion)->count(),
            'agricola_bs' => round($agricolaBs, 2),
            'distribucion_bs' => round($distribucionBs, 2),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function listarCompletadosGlobal(?DashboardFiltros $filtros = null): Collection
    {
        $filtros ??= DashboardFiltros::desdeRequest(request());

        $envios = EnvioAsignacionMultiple::query()
            ->whereNotNull('transportista_usuarioid')
            ->whereNotNull('costo_bs')
            ->where(function (Builder $q) {
                $q->whereIn('estado', ['recibido_planta', 'entregado', 'entregada'])
                    ->orWhereNotNull('fecha_recepcion_planta');
            })
            ->with(['pedido.detalles', 'transportista'])
            ->get();

        $rutas = RutaDistribucion::query()
            ->whereNotNull('transportista_usuarioid')
            ->where('estado', RutaDistribucionCatalogo::ESTADO_COMPLETADA)
            ->whereNotNull('costo_bs')
            ->with(['paradas', 'almacenOrigen', 'almacenPlantaOrigen', 'almacenMayoristaDestino', 'transportista', 'vehiculo'])
            ->get();

        return self::armarListadoDesdeColecciones($envios, $rutas, $filtros);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function listarCompletados(int $transportistaId, ?DashboardFiltros $filtros = null): Collection
    {
        $filtros ??= DashboardFiltros::desdeRequest(request());

        $envios = self::queryEnviosCompletados($transportistaId)
            ->with(['pedido.detalles', 'transportista'])
            ->get();
        $rutas = self::queryRutasCompletadas($transportistaId)
            ->with(['paradas', 'almacenOrigen', 'almacenPlantaOrigen', 'almacenMayoristaDestino', 'transportista', 'vehiculo'])
            ->get();

        return self::armarListadoDesdeColecciones($envios, $rutas, $filtros);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EnvioAsignacionMultiple>  $envios
     * @param  \Illuminate\Support\Collection<int, RutaDistribucion>  $rutas
     * @return Collection<int, array<string, mixed>>
     */
    private static function armarListadoDesdeColecciones(Collection $envios, Collection $rutas, DashboardFiltros $filtros): Collection
    {
        $items = collect();

        foreach ($envios as $envio) {
            $fecha = $envio->fecha_recepcion_planta ?? $envio->fecha_asignacion;
            if (! self::fechaEnPeriodo($fecha, $filtros)) {
                continue;
            }

            $items->push(self::mapearEnvio($envio, $fecha));
        }

        foreach ($rutas as $ruta) {
            $fecha = $ruta->fecha_salida ?? $ruta->created_at;
            if (! self::fechaEnPeriodo($fecha, $filtros)) {
                continue;
            }

            $items->push(self::mapearRuta($ruta, $fecha));
        }

        return $items
            ->sortByDesc(fn (array $item) => $item['fecha_orden'])
            ->values();
    }

    /** @return Builder<EnvioAsignacionMultiple> */
    private static function queryEnviosCompletados(int $transportistaId): Builder
    {
        return EnvioAsignacionMultiple::query()
            ->where('transportista_usuarioid', $transportistaId)
            ->whereNotNull('costo_bs')
            ->where(function (Builder $q) {
                $q->whereIn('estado', ['recibido_planta', 'entregado', 'entregada'])
                    ->orWhereNotNull('fecha_recepcion_planta');
            });
    }

    /** @return Builder<RutaDistribucion> */
    private static function queryRutasCompletadas(int $transportistaId): Builder
    {
        return RutaDistribucion::query()
            ->where('transportista_usuarioid', $transportistaId)
            ->where('estado', RutaDistribucionCatalogo::ESTADO_COMPLETADA)
            ->whereNotNull('costo_bs');
    }

    private static function fechaEnPeriodo(?Carbon $fecha, DashboardFiltros $filtros): bool
    {
        if ($fecha === null) {
            return false;
        }

        [$desde, $hasta] = $filtros->rangoFechas();
        if ($desde === null || $hasta === null) {
            return true;
        }

        return $fecha->between($desde, $hasta);
    }

    /** @return array<string, mixed> */
    private static function mapearEnvio(EnvioAsignacionMultiple $envio, ?Carbon $fecha): array
    {
        $pedido = $envio->pedido;
        $detalle = $pedido?->detalles?->first();

        return [
            'tipo' => 'agricola',
            'tipo_etiqueta' => 'Almacén → Planta',
            'codigo' => $envio->externo_envio_id ?? ('#'.$envio->envioasignacionmultipleid),
            'descripcion' => $detalle?->cultivo_personalizado ?? 'Envío agrícola',
            'transportista' => $envio->transportista?->nombreCompleto(),
            'costo_bs' => (float) $envio->costo_bs,
            'fecha' => $fecha,
            'fecha_orden' => $fecha?->timestamp ?? 0,
            'ver_url' => route('logistica.asignaciones.show', $envio),
        ];
    }

    /** @return array<string, mixed> */
    private static function mapearRuta(RutaDistribucion $ruta, ?Carbon $fecha): array
    {
        if (RutaDistribucionCatalogo::esTrasladoPlantaMayorista($ruta)) {
            $origen = $ruta->almacenPlantaOrigen?->nombre ?? 'Planta';
            $destino = $ruta->almacenMayoristaDestino?->nombre ?? 'Mayorista';

            return [
                'tipo' => 'planta_mayorista',
                'tipo_etiqueta' => 'Planta → Mayorista',
                'codigo' => $ruta->codigo,
                'descripcion' => $origen.' → '.$destino,
                'transportista' => $ruta->transportista?->nombreCompleto(),
                'costo_bs' => (float) $ruta->costo_bs,
                'fecha' => $fecha,
                'fecha_orden' => $fecha?->timestamp ?? 0,
                'ver_url' => \App\Support\RutaDistribucionNavegacion::urlVer($ruta, auth()->user()),
            ];
        }

        $paradas = $ruta->paradas?->where('tipo', RutaDistribucionCatalogo::PARADA_ENTREGA_PDV)->count() ?? 0;

        return [
            'tipo' => 'distribucion',
            'tipo_etiqueta' => 'Planta → PDV',
            'codigo' => $ruta->codigo,
            'descripcion' => ($ruta->almacenOrigen?->nombre ?? 'Planta').' · '.$paradas.' entrega(s)',
            'transportista' => $ruta->transportista?->nombreCompleto(),
            'costo_bs' => (float) $ruta->costo_bs,
            'fecha' => $fecha,
            'fecha_orden' => $fecha?->timestamp ?? 0,
            'ver_url' => \App\Support\RutaDistribucionNavegacion::urlVer($ruta),
        ];
    }
}
