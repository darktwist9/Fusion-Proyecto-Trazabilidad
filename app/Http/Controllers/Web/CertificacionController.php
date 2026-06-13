<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CertificacionLote;
use App\Models\EstadoLoteTipo;
use App\Models\HistorialEstadoLote;
use App\Models\Lote;
use App\Support\CertificacionIndexService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CertificacionController extends Controller
{
    public function __construct(
        private CertificacionIndexService $indexService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->input('ambito') === 'planta') {
            return redirect()
                ->route('certificaciones.index')
                ->with('info', 'Las certificaciones aplican solo a lotes de campo. La evaluación de calidad en planta se registra en Procesamiento de lote.');
        }

        $datos = $this->indexService->datosCampo();

        return view('certificaciones.index', [
            'lotesPendientes' => $datos['pendientes'],
            'certificados' => $datos['evaluaciones'],
            'stats' => $datos['stats'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'loteid'        => ['required', 'integer', 'exists:lote,loteid'],
            'resultado'     => ['nullable', 'string', Rule::in(CertificacionLote::RAZONES)],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $resultado = $validated['resultado'] ?? CertificacionLote::RAZON_CERTIFICADO;

        if ($resultado === CertificacionLote::RAZON_NO_CONFORME && blank($validated['observaciones'] ?? null)) {
            return back()
                ->withErrors(['observaciones' => 'Indique el motivo del no conforme (daños, plagas, calidad, etc.).'])
                ->withInput();
        }

        $this->evaluarLote((int) $validated['loteid'], $resultado, $validated['observaciones'] ?? null);

        $mensaje = $resultado === CertificacionLote::RAZON_CERTIFICADO
            ? 'Lote de campo certificado correctamente.'
            : 'Lote marcado como No conforme. No podrá enviarse al almacén.';

        return redirect()
            ->route('certificaciones.index')
            ->with('success', $mensaje);
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'lotes'         => ['required', 'array', 'min:1'],
            'lotes.*'       => ['integer', 'exists:lote,loteid'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $count = 0;
        foreach ($request->lotes as $loteid) {
            $this->evaluarLote((int) $loteid, CertificacionLote::RAZON_CERTIFICADO, $request->observaciones);
            $count++;
        }

        return redirect()
            ->route('certificaciones.index')
            ->with('success', "$count lote(s) de campo certificado(s) correctamente.");
    }

    public function show(CertificacionLote $certificacion): View
    {
        $certificacion->load([
            'lote.cultivo',
            'lote.estadoTipo',
            'lote.actorAbastecimiento',
            'lote.unidadSuperficie',
            'usuario',
        ]);

        return view('certificaciones.show', ['cert' => $certificacion]);
    }

    private function evaluarLote(int $loteid, string $resultado, ?string $observaciones): void
    {
        $lote = Lote::findOrFail($loteid);

        $prefijo = $resultado === CertificacionLote::RAZON_NO_CONFORME ? 'NCONF' : 'CERT';
        $codigo = $prefijo.'-'.now()->format('Y').'-'.str_pad((string) $lote->loteid, 4, '0', STR_PAD_LEFT);

        $estadoNombre = $resultado === CertificacionLote::RAZON_CERTIFICADO
            ? 'Certificado'
            : 'No conforme';
        $estadoDescripcion = $resultado === CertificacionLote::RAZON_CERTIFICADO
            ? 'Lote validado para despacho y trazabilidad'
            : 'Lote no apto para ingreso a almacén';

        $estado = EstadoLoteTipo::firstOrCreate(
            ['nombre' => $estadoNombre],
            ['descripcion' => $estadoDescripcion]
        );

        $certificacion = CertificacionLote::updateOrCreate(
            ['loteid' => $lote->loteid],
            [
                'usuarioid'           => auth()->id(),
                'codigo_certificado'  => $codigo,
                'resultado'           => $resultado,
                'observaciones'       => $observaciones,
                'fecha_certificacion' => now(),
            ]
        );

        $lote->update(['estadolotetipoid' => $estado->estadolotetipoid]);

        HistorialEstadoLote::create([
            'loteid'           => $lote->loteid,
            'estadolotetipoid' => $estado->estadolotetipoid,
            'fecha_cambio'     => now(),
            'observaciones'    => $resultado.': '.$certificacion->codigo_certificado
                .($observaciones ? ' — '.$observaciones : ''),
            'usuarioid'        => auth()->id(),
        ]);
    }
}
