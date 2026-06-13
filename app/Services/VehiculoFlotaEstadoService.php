<?php

namespace App\Services;

use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Models\Vehiculo;
use App\Support\EstadoVehiculoCatalogo;
use App\Support\RutaDistribucionCatalogo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VehiculoFlotaEstadoService
{
    /** Estados de envío en los que el vehículo está en uso activo. */
    private const ESTADOS_ENVIO_EN_USO = [
        'en_transporte_planta',
        'en_ruta',
        'en_transito',
    ];

    /** @var array{placas: array<string, true>, ids: array<int, true>}|null */
    private ?array $mapaEnRuta = null;

    /**
     * @return array{placas: array<string, true>, ids: array<int, true>}
     */
    public function mapaEnRuta(): array
    {
        if ($this->mapaEnRuta !== null) {
            return $this->mapaEnRuta;
        }

        $placas = [];
        foreach ($this->placasDesdeEnvios() as $placa) {
            $placas[$placa] = true;
        }

        $ids = [];
        foreach ($this->vehiculoIdsDesdeRutas() as $id) {
            $ids[$id] = true;
        }

        $this->mapaEnRuta = ['placas' => $placas, 'ids' => $ids];

        return $this->mapaEnRuta;
    }

    public function estaEnRuta(Vehiculo $vehiculo, ?array $mapa = null): bool
    {
        $mapa ??= $this->mapaEnRuta();
        $placa = strtoupper(trim((string) $vehiculo->placa));

        if ($placa !== '' && isset($mapa['placas'][$placa])) {
            return true;
        }

        return isset($mapa['ids'][(int) $vehiculo->vehiculoid]);
    }

    /** @return 'en_ruta'|'mantenimiento'|'operativo' */
    public function codigoVisual(Vehiculo $vehiculo, ?array $mapa = null): string
    {
        if ($this->estaEnRuta($vehiculo, $mapa)) {
            return 'en_ruta';
        }

        if (EstadoVehiculoCatalogo::enMantenimiento($vehiculo)) {
            return 'mantenimiento';
        }

        return 'operativo';
    }

    public function etiquetaVisual(Vehiculo $vehiculo, ?array $mapa = null): string
    {
        return match ($this->codigoVisual($vehiculo, $mapa)) {
            'en_ruta' => 'En ruta',
            'mantenimiento' => 'En mantenimiento',
            default => 'Operativo',
        };
    }

    public function badgeClaseVisual(Vehiculo $vehiculo, ?array $mapa = null): string
    {
        return match ($this->codigoVisual($vehiculo, $mapa)) {
            'en_ruta' => 'veh-estado veh-estado--ruta',
            'mantenimiento' => 'veh-estado veh-estado--mantenimiento',
            default => 'veh-estado veh-estado--operativo',
        };
    }

    /** @return Collection<int, Vehiculo> */
    public function todosOperativos(?Collection $vehiculos = null): Collection
    {
        $mapa = $this->mapaEnRuta();
        $coleccion = $vehiculos ?? Vehiculo::query()->with('estadoVehiculo')->get();

        return $coleccion->filter(
            fn (Vehiculo $v) => $this->codigoVisual($v, $mapa) === 'operativo'
        )->values();
    }

    public function contarPorEstadoVisual(?Collection $vehiculos = null): array
    {
        $mapa = $this->mapaEnRuta();
        $coleccion = $vehiculos ?? Vehiculo::query()->with('estadoVehiculo')->get();

        $conteo = ['operativo' => 0, 'mantenimiento' => 0, 'en_ruta' => 0];

        foreach ($coleccion as $vehiculo) {
            $codigo = $this->codigoVisual($vehiculo, $mapa);
            $conteo[$codigo]++;
        }

        return $conteo;
    }

    /** @return list<string> */
    public function opcionesFiltro(): array
    {
        return [
            'operativo' => 'Operativo',
            'mantenimiento' => 'En mantenimiento',
            'en_ruta' => 'En ruta',
        ];
    }

    public function aplicarFiltroVisual($query, string $filtro): void
    {
        $mapa = $this->mapaEnRuta();
        $placas = array_keys($mapa['placas']);
        $ids = array_keys($mapa['ids']);

        if ($filtro === 'en_ruta') {
            $query->where(function ($w) use ($placas, $ids) {
                $tieneCriterio = false;
                if ($placas !== []) {
                    $w->whereIn(DB::raw('UPPER(placa)'), $placas);
                    $tieneCriterio = true;
                }
                if ($ids !== []) {
                    $tieneCriterio
                        ? $w->orWhereIn('vehiculoid', $ids)
                        : $w->whereIn('vehiculoid', $ids);
                }
                if (! $tieneCriterio) {
                    $w->whereRaw('1 = 0');
                }
            });

            return;
        }

        if ($filtro === 'mantenimiento') {
            $idMant = EstadoVehiculoCatalogo::idMantenimiento();
            if ($idMant) {
                $query->where('estadovehiculoid', $idMant);
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        if ($filtro === 'operativo') {
            $idMant = EstadoVehiculoCatalogo::idMantenimiento();
            if ($idMant) {
                $query->where(function ($w) use ($idMant) {
                    $w->where('estadovehiculoid', '!=', $idMant)
                        ->orWhereNull('estadovehiculoid');
                });
            }

            if ($placas !== []) {
                $query->whereNotIn(DB::raw('UPPER(placa)'), $placas);
            }
            if ($ids !== []) {
                $query->whereNotIn('vehiculoid', $ids);
            }

            $query->where('activo', true);
        }
    }

    /** @return Collection<int, string> */
    private function placasDesdeEnvios(): Collection
    {
        return EnvioAsignacionMultiple::query()
            ->whereNotNull('vehiculo_ref')
            ->where('vehiculo_ref', '!=', '')
            ->whereIn('estado', self::ESTADOS_ENVIO_EN_USO)
            ->pluck('vehiculo_ref')
            ->map(fn ($placa) => strtoupper(trim((string) $placa)))
            ->filter()
            ->unique()
            ->values();
    }

    /** @return Collection<int, int> */
    private function vehiculoIdsDesdeRutas(): Collection
    {
        if (! Schema::hasTable('ruta_distribucion')) {
            return collect();
        }

        return RutaDistribucion::query()
            ->where('estado', RutaDistribucionCatalogo::ESTADO_EN_RUTA)
            ->whereNotNull('vehiculoid')
            ->pluck('vehiculoid')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
