<?php

namespace App\Support;

use App\Models\CertificacionLote;
use App\Models\EvaluacionFinalLoteProduccion;
use App\Models\Lote;
use App\Models\LoteProduccionPedido;
use Illuminate\Support\Collection;

class CertificacionIndexService
{
    public function __construct(
        private LoteProduccionTransformacionService $transformacion
    ) {}

    /** @return array{pendientes: Collection, certificados: Collection, stats: array<string, int>, ambito: string} */
    public function datosPlanta(): array
    {
        $lotes = LoteProduccionPedido::query()
            ->with(['evaluacionesFinales', 'plantillaTransformacion'])
            ->orderByDesc('loteproduccionpedidoid')
            ->get();

        $pendientes = $lotes->filter(function (LoteProduccionPedido $lote) {
            return $this->transformacion->transformacionCompleta($lote)
                && $lote->evaluacionesFinales->isEmpty();
        })->values();

        $evaluaciones = EvaluacionFinalLoteProduccion::query()
            ->with(['loteProduccionPedido', 'inspector'])
            ->orderByDesc('fecha_evaluacion')
            ->limit(20)
            ->get();

        $certificadosOk = $lotes->filter(function (LoteProduccionPedido $lote) {
            $ultima = $lote->evaluacionesFinales->sortByDesc('fecha_evaluacion')->first();

            return $ultima && $ultima->esCertificado();
        });

        return [
            'ambito' => 'planta',
            'pendientes' => $pendientes,
            'certificados' => $evaluaciones,
            'stats' => [
                'pendientes' => $pendientes->count(),
                'certificados' => $certificadosOk->count(),
                'total_lotes' => $lotes->count(),
            ],
        ];
    }

    /** @return array{pendientes: Collection, evaluaciones: Collection, stats: array<string, int>, ambito: string} */
    public function datosCampo(): array
    {
        $estadoCosechado = EstadoLoteCatalogo::idsPorSlugs(['cosechado']);

        $query = Lote::query()
            ->with(['cultivo', 'estadoTipo', 'usuario', 'producciones'])
            ->whereHas('producciones')
            ->orderByDesc('loteid');

        if ($estadoCosechado !== []) {
            $query->whereIn('estadolotetipoid', $estadoCosechado);
        }

        $lotesCosechados = $query->get();

        $idsEvaluados = CertificacionLote::query()
            ->pluck('loteid')
            ->unique()
            ->all();

        $pendientes = $lotesCosechados
            ->filter(fn (Lote $l) => ! in_array($l->loteid, $idsEvaluados, true))
            ->values();

        $evaluaciones = CertificacionLote::query()
            ->with(['lote.cultivo', 'usuario'])
            ->orderByDesc('fecha_certificacion')
            ->limit(20)
            ->get();

        $totalCertificados = CertificacionLote::query()
            ->where('resultado', CertificacionLote::RAZON_CERTIFICADO)
            ->distinct('loteid')
            ->count('loteid');

        $totalNoConformes = CertificacionLote::query()
            ->where('resultado', CertificacionLote::RAZON_NO_CONFORME)
            ->distinct('loteid')
            ->count('loteid');

        return [
            'ambito' => 'campo',
            'pendientes' => $pendientes,
            'evaluaciones' => $evaluaciones,
            'stats' => [
                'pendientes' => $pendientes->count(),
                'certificados' => $totalCertificados,
                'no_conformes' => $totalNoConformes,
                'total_lotes' => $lotesCosechados->count(),
            ],
        ];
    }
}
