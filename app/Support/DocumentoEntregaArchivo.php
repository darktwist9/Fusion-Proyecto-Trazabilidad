<?php

namespace App\Support;

use App\Models\DocumentoEntrega;
use App\Models\EnvioAsignacionMultiple;
use App\Models\RutaDistribucion;
use App\Support\RutaDistribucionCatalogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

final class DocumentoEntregaArchivo
{
    private const PDF_VERSION = 6;

    /** @var array<string, string> */
    private const TIPOS_ETIQUETA = [
        'guia_entrega' => 'Guía de entrega',
        'guia_transporte' => 'Guía de transporte',
        'confirmacion_entrega' => 'Confirmación de entrega',
        'nota_entrega' => 'Nota de entrega',
        'pod' => 'POD / comprobante de entrega',
    ];

    public static function asegurarPdfOperativo(DocumentoEntrega $documento): bool
    {
        $path = trim((string) $documento->archivo_path);
        if ($path === '') {
            return false;
        }

        if (
            ! Storage::disk('public')->exists($path)
            || self::esArchivoPlaceholder($path)
            || ($documento->metadata['pdf_version'] ?? null) !== self::PDF_VERSION
        ) {
            return self::generarPdfOperativo($documento);
        }

        return true;
    }

    public static function materializarSiFalta(DocumentoEntrega $documento): bool
    {
        return self::asegurarPdfOperativo($documento);
    }

    public static function materializarTodosFaltantes(bool $forzarRegeneracion = false): int
    {
        if (! Schema::hasTable('documento_entrega')) {
            return 0;
        }

        $procesados = 0;

        DocumentoEntrega::query()
            ->whereNotNull('archivo_path')
            ->orderBy('documentoentregaid')
            ->chunkById(50, function ($documentos) use (&$procesados, $forzarRegeneracion) {
                foreach ($documentos as $documento) {
                    $ok = $forzarRegeneracion
                        ? self::generarPdfOperativo($documento)
                        : self::asegurarPdfOperativo($documento);

                    if ($ok) {
                        $procesados++;
                    }
                }
            }, 'documentoentregaid');

        return $procesados;
    }

    public static function generarPdfOperativo(DocumentoEntrega $documento): bool
    {
        $path = trim((string) $documento->archivo_path);
        if ($path === '') {
            return false;
        }

        $disk = Storage::disk('public');
        $dir = dirname(str_replace('\\', '/', $path));
        if ($dir !== '.' && $dir !== '') {
            $disk->makeDirectory($dir);
        }

        $contexto = self::contextoPdf($documento);
        $pdf = Pdf::loadView('logistica.documentos.pdf.comprobante', $contexto)
            ->setPaper('a4', 'portrait');

        $guardado = (bool) $disk->put($path, $pdf->output());

        if ($guardado) {
            $metadata = array_merge($documento->metadata ?? [], [
                'pdf_version' => self::PDF_VERSION,
                'pdf_generado_en' => now()->toIso8601String(),
            ]);
            $documento->update(['metadata' => $metadata]);
        }

        return $guardado;
    }

    /** @return array<string, mixed> */
    public static function contextoPdf(DocumentoEntrega $documento): array
    {
        $documento->loadMissing(['usuario', 'pedido.detalles', 'almacen']);

        $envio = null;
        if ($documento->externo_envio_id) {
            $envio = EnvioAsignacionMultiple::query()
                ->with([
                    'transportista',
                    'pedido.detalles',
                    'almacen',
                    'ruta.paradas',
                    'checklistCondicionVehiculo.detalles.condicion',
                    'checklistIncidente.detalles.tipoIncidente',
                    'firmaTransportista',
                    'firmaRecepcion',
                    'llegadaConfirmadaPor',
                ])
                ->where('externo_envio_id', $documento->externo_envio_id)
                ->first();
        }

        $rutaTraslado = null;
        $rutaId = (int) ($documento->metadata['rutadistribucionid'] ?? 0);
        if ($envio === null && $rutaId > 0 && ($documento->metadata['envio_cierre_planta_mayorista'] ?? false)) {
            $rutaTraslado = RutaDistribucion::query()
                ->with([
                    'transportista',
                    'vehiculo.tipoVehiculo',
                    'almacenPlantaOrigen',
                    'almacenMayoristaDestino',
                    'paradas',
                    'detallesTraslado.insumo.unidadMedida',
                    'checklistCondicionVehiculo.detalles.condicion',
                    'checklistIncidente.detalles.tipoIncidente',
                    'firmaTransportista',
                    'firmaRecepcion',
                    'llegadaConfirmadaPor',
                ])
                ->find($rutaId);
        }

        $pedido = $documento->pedido ?? $envio?->pedido;
        $pedidoReferencia = $pedido?->numero_solicitud
            ?? ($documento->pedidoid ? '#'.$documento->pedidoid : null)
            ?? $rutaTraslado?->codigo
            ?? $documento->externo_envio_id;
        $vehiculoRef = $envio?->vehiculo_ref
            ?? $rutaTraslado?->vehiculo?->placa
            ?? null;
        $estadoVehiculo = EnvioCierreAgricolaCatalogo::etiquetaEstadoVehiculo(
            $envio?->checklistCondicionVehiculo?->estado_general
                ?? $rutaTraslado?->checklistCondicionVehiculo?->estado_general
        );
        $transportista = $envio?->transportista ?? $rutaTraslado?->transportista ?? $documento->usuario;
        $transportistaNombre = DocumentoEntregaCatalogo::etiquetaUsuario($transportista);

        $destinoCliente = '—';
        $direccionEntrega = '—';
        if ($pedido !== null) {
            $destinoCliente = EnvioPedidoService::etiquetaPlantaDestinoLista($pedido)
                ?? EnvioPedidoService::etiquetaPlantaDestinoPedido($pedido)
                ?? '—';
            $direccionEntrega = trim((string) ($pedido->direccion_texto ?? ''));
            if ($direccionEntrega === '' && $destinoCliente !== '—') {
                $direccionEntrega = $destinoCliente;
            }
            if ($direccionEntrega === '') {
                $direccionEntrega = '—';
            }
        } elseif ($rutaTraslado !== null) {
            $destinoCliente = $rutaTraslado->almacenMayoristaDestino?->nombre ?? '—';
            $direccionEntrega = trim((string) ($rutaTraslado->almacenMayoristaDestino?->direccion ?? ''));
            if ($direccionEntrega === '' && $destinoCliente !== '—') {
                $direccionEntrega = $destinoCliente;
            }
            if ($direccionEntrega === '') {
                $direccionEntrega = '—';
            }
        }

        $cargadoPor = DocumentoEntregaCatalogo::etiquetaUsuario($documento->usuario);

        $lineasProducto = [];
        foreach ($pedido?->detalles ?? [] as $detalle) {
            $empaque = PedidoCatalogo::descripcionEmpaqueDetalle($detalle->observaciones);
            $obsUsuario = PedidoCatalogo::observacionesUsuarioDetalle($detalle->observaciones);

            $lineasProducto[] = [
                'producto' => $detalle->cultivo_personalizado
                    ?? $detalle->insumo?->nombre
                    ?? 'Producto',
                'cantidad' => number_format((float) $detalle->cantidad, 2, '.', '').' u.',
                'empaquetaje' => $empaque ?? '—',
                'observaciones' => $obsUsuario ?: '—',
            ];
        }

        if ($lineasProducto === [] && $rutaTraslado !== null) {
            foreach ($rutaTraslado->detallesTraslado as $detalle) {
                $lineasProducto[] = [
                    'producto' => $detalle->producto_nombre ?? $detalle->insumo?->nombre ?? 'Producto',
                    'cantidad' => number_format((float) $detalle->cantidad, 2, '.', '')
                        .' '.($detalle->insumo?->unidadMedida?->abreviatura ?? 'kg'),
                    'empaquetaje' => $detalle->presentacion_nombre ?: '—',
                    'observaciones' => $detalle->observaciones ?: '—',
                ];
            }
        }

        $tipo = (string) $documento->tipo_documento;
        $tipoEtiqueta = self::TIPOS_ETIQUETA[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));

        $textoObservaciones = match ($tipo) {
            'guia_entrega', 'guia_transporte' => 'Documento de salida y trazabilidad del envío hacia el punto de entrega indicado.',
            'confirmacion_entrega', 'pod' => 'Comprobante de recepción conforme en destino.',
            'nota_entrega' => 'Registro de recepción en almacén o punto de despacho.',
            default => 'Comprobante logístico asociado al envío.',
        };

        if ($envio?->estado === 'entregado') {
            $textoObservaciones .= ' Envío marcado como entregado en el sistema.';
        } elseif ($rutaTraslado?->estado === RutaDistribucionCatalogo::ESTADO_COMPLETADA) {
            $textoObservaciones .= ' Traslado completado en el sistema.';
        }

        $operacionChecklistCondicion = $envio?->checklistCondicionVehiculo ?? $rutaTraslado?->checklistCondicionVehiculo;
        $operacionChecklistIncidente = $envio?->checklistIncidente ?? $rutaTraslado?->checklistIncidente;
        $operacionFirmaTransportista = $envio?->firmaTransportista ?? $rutaTraslado?->firmaTransportista;
        $operacionFirmaRecepcion = $envio?->firmaRecepcion ?? $rutaTraslado?->firmaRecepcion;
        $operacionLlegadaAt = $envio?->llegada_confirmada_at ?? $rutaTraslado?->llegada_confirmada_at;

        $condicionesLineas = [];
        foreach ($operacionChecklistCondicion?->detalles ?? [] as $det) {
            $condicionesLineas[] = [
                'titulo' => $det->condicion?->titulo ?? 'Condición',
                'valor' => $det->valor ? 'Sí' : 'No',
            ];
        }

        $observacionCondiciones = self::resolverObservacionCondiciones(
            $condicionesLineas,
            $operacionChecklistCondicion?->observaciones
        );

        $incidentesLineas = [];
        foreach ($operacionChecklistIncidente?->detalles ?? [] as $det) {
            $incidentesLineas[] = [
                'titulo' => $det->tipoIncidente?->titulo ?? 'Incidente',
                'ocurrio' => $det->ocurrio ? 'Sí' : 'No',
            ];
        }

        $observacionIncidentes = IncidenteTransporteCatalogo::resolverObservacion(
            $incidentesLineas,
            $operacionChecklistIncidente?->observaciones
        );

        $rutaLogistica = '—';
        if ($envio !== null) {
            $nombreRuta = trim((string) ($envio->ruta?->nombre ?? ''));
            $trayecto = EnvioPedidoService::trayectoTexto($envio);
            $rutaLogistica = $nombreRuta !== '' ? $nombreRuta : ($trayecto ?? '—');
            if ($rutaLogistica === '' || $rutaLogistica === null) {
                $rutaLogistica = '—';
            }
        } elseif ($rutaTraslado !== null) {
            $origen = $rutaTraslado->almacenPlantaOrigen?->nombre ?? 'Planta';
            $destino = $rutaTraslado->almacenMayoristaDestino?->nombre ?? 'Mayorista';
            $rutaLogistica = $origen.' → '.$destino;
        }

        return [
            'documento' => $documento,
            'envio' => $envio,
            'pedido' => $pedido,
            'pedidoReferencia' => $pedidoReferencia,
            'vehiculoRef' => $vehiculoRef,
            'estadoVehiculo' => $estadoVehiculo,
            'tipoEtiqueta' => $tipoEtiqueta,
            'transportistaNombre' => $transportistaNombre,
            'destinoCliente' => $destinoCliente,
            'direccionEntrega' => $direccionEntrega,
            'cargadoPor' => $cargadoPor,
            'rutaLogistica' => $rutaLogistica,
            'estadoEnvio' => ucfirst(str_replace('_', ' ', (string) ($envio?->estado ?? $rutaTraslado?->estado ?? 'pendiente'))),
            'lineasProducto' => $lineasProducto,
            'textoObservaciones' => $textoObservaciones,
            'condicionesLineas' => $condicionesLineas,
            'observacionCondiciones' => $observacionCondiciones,
            'incidentesLineas' => $incidentesLineas,
            'observacionIncidentes' => $observacionIncidentes,
            'observacionesIncidentes' => $operacionChecklistIncidente?->observaciones,
            'firmaTransportistaImg' => $operacionFirmaTransportista?->imagenfirma,
            'firmaRecepcionImg' => $operacionFirmaRecepcion?->imagenfirma,
            'llegadaConfirmadaAt' => $operacionLlegadaAt,
        ];
    }

    /**
     * @param  array<int, array{titulo: string, valor: string}>  $condicionesLineas
     * @return array{texto: ?string, alerta: bool}
     */
    private static function resolverObservacionCondiciones(array $condicionesLineas, ?string $observacionesGuardadas): array
    {
        $deficiencias = array_values(array_filter(
            $condicionesLineas,
            static fn (array $fila): bool => ($fila['valor'] ?? '') === 'No'
        ));

        if ($deficiencias !== []) {
            $titulos = array_map(static fn (array $fila): string => $fila['titulo'], $deficiencias);

            return [
                'texto' => 'Deficiencias detectadas: '.implode(', ', $titulos).'.',
                'alerta' => true,
            ];
        }

        $texto = trim((string) ($observacionesGuardadas ?? ''));
        if ($texto !== '') {
            $texto = preg_replace('/\s*\(registro\s+r[aá]pido\)\.?/iu', '', $texto) ?? $texto;
            $texto = trim($texto);
            if ($texto !== '' && ! str_ends_with($texto, '.')) {
                $texto .= '.';
            }

            return ['texto' => $texto !== '' ? $texto : null, 'alerta' => false];
        }

        if ($condicionesLineas !== []) {
            return ['texto' => 'Vehículo en perfectas condiciones.', 'alerta' => false];
        }

        return ['texto' => null, 'alerta' => false];
    }

    private static function esArchivoPlaceholder(string $path): bool
    {
        if (! Storage::disk('public')->exists($path)) {
            return false;
        }

        $contenido = Storage::disk('public')->get($path);

        return strlen($contenido) < 2000
            && str_contains($contenido, 'Documento generado por AgroFusion');
    }
}
