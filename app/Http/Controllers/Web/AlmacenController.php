<?php



namespace App\Http\Controllers\Web;



use App\Http\Controllers\Controller;

use App\Models\Almacen;

use App\Models\AlmacenajeLoteProduccion;

use App\Models\Insumo;

use App\Models\ProduccionAlmacenamiento;

use App\Models\TipoAlmacen;

use App\Models\UnidadMedida;

use App\Services\AlmacenCapacidadService;

use App\Services\UbicacionesAlmacenService;

use App\Support\AlmacenAmbito;

use App\Support\AlmacenPlantaCosechaCatalogo;

use App\Support\InsumoCatalogo;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Schema;



class AlmacenController extends Controller

{

    public function __construct(

        private readonly AlmacenCapacidadService $capacidadService,

    ) {}



    public function index(Request $request)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $ambito = $ctx['ambito'];

        $q = AlmacenAmbito::scope(Almacen::query(), $ambito);



        $almacenesPagina = AlmacenAmbito::scope(

            Almacen::with(['unidadMedida']),

            $ambito

        )

            ->orderBy('almacenid', 'desc')

            ->paginate(15);



        $ocupacionPorId = [];

        $capacidadTotalKg = 0;

        $ocupadoTotalKg = 0;



        foreach ($almacenesPagina as $almacen) {

            $resumen = $this->capacidadService->resumen($almacen);

            $ocupacionPorId[$almacen->almacenid] = $resumen;

            $capacidadTotalKg += $resumen['capacidad_kg'];

            $ocupadoTotalKg += $resumen['ocupado_kg'];

        }



        $stats = [

            'total' => (clone $q)->count(),

            'capacidad_total' => $capacidadTotalKg,

            'ocupado_total' => $ocupadoTotalKg,

            'ocupacion_promedio' => $capacidadTotalKg > 0

                ? round(($ocupadoTotalKg / $capacidadTotalKg) * 100, 1)

                : 0,

        ];



        return view('almacenes.index', array_merge(compact('almacenesPagina', 'stats', 'ocupacionPorId'), $ctx, [

            'almacenes' => $almacenesPagina,

        ]));

    }



    public function create(Request $request)

    {

        $ctx = AlmacenAmbito::contexto($request);



        return view('almacenes.create', array_merge(

            $this->datosFormulario(null),

            $ctx

        ));

    }



    public function selectorUbicacion(Request $request)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $excluirAlmacenId = $request->integer('excluir_almacen_id') ?: null;

        $ubicacionesGrupos = app(UbicacionesAlmacenService::class)

            ->listarParaFormulario($excluirAlmacenId);



        return view('almacenes.selector-ubicacion', array_merge([

            'ubicacionesGrupos' => $ubicacionesGrupos,

        ], $ctx));

    }



    public function store(Request $request)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $data = $this->validarAlmacen($request);

        $data['ambito'] = $ctx['ambito'];

        $data['activo'] = true;

        $data['unidadmedidaid'] = $this->unidadKilogramoId();

        $data['tipoalmacenid'] = $this->tipoAlmacenPorDefecto();



        Almacen::create($data);



        return redirect()

            ->route($ctx['rutaPrefijo'].'.index')

            ->with('success', 'Almacén creado.');

    }



    public function show(Request $request, Almacen $almacen)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $this->asegurarAmbitoAlmacen($almacen, $ctx['ambito']);

        $almacen->load(['unidadMedida', 'almacenamientos']);

        $resumenCapacidad = $this->capacidadService->resumen($almacen);

        $contenidos = $this->contenidoAlmacen($almacen);

        if (($almacen->ambito ?? '') === AlmacenAmbito::PLANTA) {
            $contenidos = AlmacenPlantaCosechaCatalogo::consolidarItemsPlanta(
                $contenidos,
                $almacen,
                $ctx['rutaPrefijo']
            );
        }

        $tiposContenidoFiltro = $contenidos->pluck('tipo_label')->unique()->sort()->values();



        return view('almacenes.show', array_merge(compact(
            'almacen',
            'resumenCapacidad',
            'contenidos',
            'tiposContenidoFiltro'
        ), $ctx));

    }



    public function edit(Request $request, Almacen $almacen)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $this->asegurarAmbitoAlmacen($almacen, $ctx['ambito']);



        return view('almacenes.edit', array_merge(

            $this->datosFormulario($almacen),

            $ctx,

            ['almacen' => $almacen]

        ));

    }



    public function update(Request $request, Almacen $almacen)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $this->asegurarAmbitoAlmacen($almacen, $ctx['ambito']);



        $data = $this->validarAlmacen($request, $almacen);

        $data['ambito'] = $ctx['ambito'];

        $data['activo'] = true;

        $data['unidadmedidaid'] = $this->unidadKilogramoId();

        if (! $almacen->tipoalmacenid) {
            $data['tipoalmacenid'] = $this->tipoAlmacenPorDefecto();
        } else {
            unset($data['tipoalmacenid']);
        }



        $almacen->update($data);



        return redirect()

            ->route($ctx['rutaPrefijo'].'.index')

            ->with('success', 'Almacén actualizado.');

    }



    public function destroy(Request $request, Almacen $almacen)

    {

        $ctx = AlmacenAmbito::contexto($request);

        $this->asegurarAmbitoAlmacen($almacen, $ctx['ambito']);

        $almacen->delete();



        return redirect()

            ->route($ctx['rutaPrefijo'].'.index')

            ->with('success', 'Almacén eliminado.');

    }



    private function asegurarAmbitoAlmacen(Almacen $almacen, string $ambito): void

    {

        if (Schema::hasColumn('almacen', 'ambito') && $almacen->ambito !== $ambito) {

            abort(404);

        }

    }



    /**

     * @return array<string, mixed>

     */

    private function datosFormulario(?Almacen $almacen = null): array

    {

        return [

            'almacen' => $almacen,

            'guias' => config('almacenes', []),

        ];

    }



    /**

     * @return array<string, mixed>

     */

    private function validarAlmacen(Request $request, ?Almacen $almacen = null): array

    {

        $reglas = [

            'nombre' => 'required|string|max:100|unique:almacen,nombre'.($almacen ? ','.$almacen->almacenid.',almacenid' : ''),

            'descripcion' => 'nullable|string|max:250',

            'ubicacion' => 'nullable|string|max:200',

            'capacidad' => 'required|numeric|min:0.01',

        ];



        if (Schema::hasColumn('almacen', 'direccionlogisticaid')) {

            $reglas['direccionlogisticaid'] = 'nullable|exists:direccion_logistica,direccionlogisticaid';

        }



        $data = $request->validate($reglas);



        if (! Schema::hasColumn('almacen', 'direccionlogisticaid')) {

            unset($data['direccionlogisticaid']);

        } elseif (empty($data['direccionlogisticaid'])) {

            $data['direccionlogisticaid'] = null;

        }



        return $data;

    }



    private function unidadKilogramoId(): ?int

    {

        $id = UnidadMedida::query()

            ->where(function ($q) {

                $q->whereRaw('LOWER(abreviatura) = ?', ['kg'])

                    ->orWhereRaw('LOWER(nombre) LIKE ?', ['%kilogramo%']);

            })

            ->value('unidadmedidaid');



        return $id ? (int) $id : null;

    }



    private function tipoAlmacenPorDefecto(): ?int

    {

        $id = TipoAlmacen::query()

            ->whereIn('nombre', ['Central', 'Secundario', 'Planta'])

            ->orderByRaw("CASE nombre WHEN 'Central' THEN 1 WHEN 'Secundario' THEN 2 ELSE 3 END")

            ->value('tipoalmacenid');



        if ($id) {

            return (int) $id;

        }



        $fallback = TipoAlmacen::query()->orderBy('tipoalmacenid')->value('tipoalmacenid');



        return $fallback ? (int) $fallback : null;

    }



    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function contenidoAlmacen(Almacen $almacen): \Illuminate\Support\Collection
    {
        $items = collect();
        $esPlanta = ($almacen->ambito ?? '') === AlmacenAmbito::PLANTA;

        $insumosQuery = Insumo::query()->with(['tipo', 'unidadMedida'])
            ->where('almacenid', $almacen->almacenid);

        if ($esPlanta) {
            $insumosQuery = InsumoCatalogo::aplicarFiltroExcluirProductoTerminado($insumosQuery);
            $tipoSiembraId = InsumoCatalogo::tiposOrdenados()
                ->filter(fn ($t) => InsumoCatalogo::slugFromNombreTipo($t->nombre) === 'material_siembra')
                ->pluck('tipoinsumoid')
                ->first();
            if ($tipoSiembraId) {
                $insumosQuery->where(function ($q) use ($tipoSiembraId) {
                    $q->where('tipoinsumoid', '!=', (int) $tipoSiembraId)
                        ->orWhere('descripcion', 'like', 'Recepción pedido%');
                });
            }
        } else {
            $insumosQuery = InsumoCatalogo::aplicarFiltroOperativo($insumosQuery);
        }

        $insumos = $insumosQuery->orderBy('nombre')->get();

        foreach ($insumos as $insumo) {
            if ((float) $insumo->stock <= 0) {
                continue;
            }

            $tipoNombre = $insumo->tipo?->nombre ?? 'Insumo';
            $slug = InsumoCatalogo::slugFromNombreTipo($tipoNombre) ?? 'insumo';
            $esRecepcionPedido = AlmacenPlantaCosechaCatalogo::esRecepcionPedidoInsumo($insumo);
            $kg = $this->capacidadService->convertirAKg((float) $insumo->stock, $insumo->unidadMedida);
            $claveCultivo = AlmacenPlantaCosechaCatalogo::claveCultivo($insumo->nombre);

            $items->push((object) [
                'categoria' => $esRecepcionPedido ? 'cosecha' : 'insumo',
                'tipo_label' => $esRecepcionPedido ? 'Cosecha' : $tipoNombre,
                'tipo_filtro' => $esRecepcionPedido ? 'cosecha' : $slug,
                'nombre' => $esRecepcionPedido
                    ? AlmacenPlantaCosechaCatalogo::etiquetaCultivo($insumo->nombre)
                    : $insumo->nombre,
                'detalle' => $insumo->descripcion ? \Illuminate\Support\Str::limit($insumo->descripcion, 60) : '—',
                'cantidad' => (float) $insumo->stock,
                'unidad' => $insumo->unidadMedida?->abreviatura ?? $insumo->unidadMedida?->nombre ?? '',
                'kg' => $kg,
                'empaque' => null,
                'fecha_orden' => AlmacenPlantaCosechaCatalogo::fechaDesdeDescripcionRecepcion($insumo->descripcion)?->timestamp ?? 0,
                'search' => strtolower(trim($insumo->nombre.' '.$tipoNombre)),
                'insumoid' => $insumo->insumoid,
                'origen_tipo' => $esRecepcionPedido ? 'recepcion_pedido' : 'insumo',
                'clave_cultivo' => $claveCultivo,
            ]);
        }

        $cosechas = ProduccionAlmacenamiento::query()
            ->with(['produccion.lote.cultivo', 'unidadMedida'])
            ->where('almacenid', $almacen->almacenid)
            ->whereNull('fechasalida')
            ->orderByDesc('fechaentrada')
            ->get();

        foreach ($cosechas as $c) {
            $lote = $c->produccion?->lote;
            $cultivo = $lote?->cultivo?->nombre ?? 'Cultivo';
            $nombre = $cultivo.' · '.($lote?->nombre ?? 'Producción #'.$c->produccionid);
            $kg = $this->capacidadService->convertirAKg((float) $c->cantidad, $c->unidadMedida);

            $fechaEntrada = $c->fechaentrada ? \Carbon\Carbon::parse($c->fechaentrada) : null;
            $claveCultivo = AlmacenPlantaCosechaCatalogo::claveCultivo($cultivo !== '' ? $cultivo : $nombre, $cultivo !== '' ? $cultivo : null);

            $items->push((object) [
                'categoria' => 'cosecha',
                'tipo_label' => 'Cosecha',
                'tipo_filtro' => 'cosecha',
                'nombre' => AlmacenPlantaCosechaCatalogo::etiquetaCultivo($cultivo !== '' ? $cultivo : $nombre, $claveCultivo),
                'detalle' => $fechaEntrada ? $fechaEntrada->format('d/m/Y') : '—',
                'cantidad' => (float) $c->cantidad,
                'unidad' => $c->unidadMedida?->abreviatura ?? 'kg',
                'kg' => $kg,
                'empaque' => null,
                'fecha_orden' => $fechaEntrada?->timestamp ?? 0,
                'search' => strtolower(trim($nombre.' cosecha '.$cultivo)),
                'produccionid' => $c->produccionid,
                'produccionalmacenamientoid' => $c->produccionalmacenamientoid,
                'origen_tipo' => 'produccion',
                'clave_cultivo' => $claveCultivo,
                'lote_nombre' => $lote?->nombre,
            ]);
        }

        $productosPlanta = AlmacenajeLoteProduccion::query()
            ->with(['loteProduccionPedido.unidadMedida', 'loteProduccionPedido.materiasPrimas.insumo.unidadMedida'])
            ->whereNull('fecha_retiro')
            ->where('almacenid', $almacen->almacenid)
            ->orderByDesc('fecha_almacenaje')
            ->get();

        foreach ($productosPlanta as $ingreso) {
            $lote = $ingreso->loteProduccionPedido;
            if (! $lote) {
                continue;
            }

            $lote->loadMissing('materiasPrimas.insumo.unidadMedida', 'unidadMedida');
            $producto = \App\Support\LoteProduccionNombre::productoDesdeLote($lote);
            $nombre = \App\Support\ProductoPlantaCatalogo::etiquetaLoteAlmacen($lote);
            $resumen = \App\Support\ProductoPlantaCatalogo::resumenProduccion($lote, $this->capacidadService);
            $kg = (float) ($resumen['kg'] ?? 0);
            if ($kg <= 0) {
                $kg = $this->capacidadService->convertirAKg((float) $ingreso->cantidad, $lote->unidadMedida);
            }
            $cantidad = \App\Support\ProductoPlantaCatalogo::esProduccionPorUnidades($lote)
                ? (float) \App\Support\ProductoPlantaCatalogo::unidadesProducidas($lote, $this->capacidadService)
                : ((float) ($resumen['cantidad'] ?? 0) > 0 ? (float) $resumen['cantidad'] : (float) $ingreso->cantidad);
            $unidad = \App\Support\ProductoPlantaCatalogo::unidadEtiqueta($producto, $lote->unidadMedida, $lote);
            $empaqueLabel = \App\Support\ProductoPlantaCatalogo::loteTieneEmpaquePlanificado($lote)
                ? \App\Support\EmpaquePlantaCatalogo::etiquetaEmpaquePlanificado(
                    $lote->empaque_catalogo_slug,
                    $lote->empaque_nombre_personalizado,
                    $lote->empaque_peso_neto_kg
                )
                : null;
            $detalleEmpaque = \App\Support\ProductoPlantaCatalogo::detalleEmpaqueAlmacen($lote, $resumen);
            $fechaAlm = $ingreso->fecha_almacenaje ? \Carbon\Carbon::parse($ingreso->fecha_almacenaje) : null;
            $detalle = $detalleEmpaque !== ''
                ? $detalleEmpaque
                : trim(($ingreso->condicion ? $ingreso->condicion.' · ' : '').($fechaAlm ? $fechaAlm->format('d/m/Y H:i') : ''));

            $items->push((object) [
                'categoria' => 'producto_planta',
                'tipo_label' => 'Producto terminado',
                'tipo_filtro' => 'producto terminado',
                'nombre' => $nombre,
                'detalle' => $detalle !== '' ? \Illuminate\Support\Str::limit($detalle, 120) : ($lote->codigo_lote ?? '—'),
                'cantidad' => $cantidad,
                'unidad' => $unidad,
                'kg' => $kg,
                'empaque' => $empaqueLabel,
                'fecha_orden' => $fechaAlm?->timestamp ?? 0,
                'search' => strtolower(trim($nombre.' producto planta '.$producto.' '.$lote->codigo_lote.' '.($empaqueLabel ?? ''))),
                'lote_produccion_pedido_id' => $lote->loteproduccionpedidoid,
            ]);
        }

        return $items->sortByDesc('fecha_orden')->values();
    }

}

