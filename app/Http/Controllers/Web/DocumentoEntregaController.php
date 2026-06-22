<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DocumentoEntrega;
use App\Support\DocumentoEntregaAcceso;
use App\Support\DocumentoEntregaArchivo;
use App\Support\DocumentoEntregaCatalogo;
use App\Support\DocumentoEntregaTransportista;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoEntregaController extends Controller
{
    public function index(Request $request): View
    {
        $q = DocumentoEntrega::query()
            ->with(['usuario', 'pedido'])
            ->tap(fn ($query) => DocumentoEntregaCatalogo::aplicarFiltroOperativo($query))
            ->orderByDesc('created_at');

        $user = auth()->user();
        DocumentoEntregaAcceso::aplicarFiltroRol($q, $user);

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->toString();
            $q->where(function ($w) use ($term) {
                $w->where('titulo', 'like', "%{$term}%")
                    ->orWhere('externo_envio_id', 'like', "%{$term}%")
                    ->orWhere('tipo_documento', 'like', "%{$term}%")
                    ->orWhereHas('usuario', function ($u) use ($term) {
                        $u->where('nombreusuario', 'like', "%{$term}%")
                            ->orWhere('nombre', 'like', "%{$term}%")
                            ->orWhere('apellido', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('tipo')) {
            $q->where('tipo_documento', $request->string('tipo')->toString());
        }

        if ($request->filled('envio')) {
            $q->where('externo_envio_id', 'like', '%'.$request->string('envio')->trim().'%');
        }

        if ($request->filled('cargado_por')) {
            $q->whereHas('usuario', fn ($u) => $u->where('nombreusuario', $request->string('cargado_por')->toString()));
        }

        if ($request->filled('desde')) {
            $q->whereDate('created_at', '>=', $request->string('desde')->toString());
        }

        if ($request->filled('hasta')) {
            $q->whereDate('created_at', '<=', $request->string('hasta')->toString());
        }

        $resumenDocumentos = [
            'total' => (clone $q)->count(),
            'guias' => (clone $q)->where('tipo_documento', 'guia_transporte')->count(),
            'hoy' => (clone $q)->whereDate('created_at', today())->count(),
        ];

        $documentos = $q->paginate(15)->withQueryString();

        $tiposDocumento = DocumentoEntregaCatalogo::tiposDocumento();

        return view('logistica.documentos.index', compact(
            'documentos',
            'tiposDocumento',
            'resumenDocumentos'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'string', 'max:50'],
            'externo_envio_id' => ['nullable', 'string', 'max:64'],
            'pedidoid' => ['nullable', 'integer', 'exists:pedido,pedidoid'],
            'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'almacenid' => ['nullable', 'integer', 'exists:almacen,almacenid'],
        ]);

        $user = $request->user();
        if ($user->hasRole('transportista')) {
            $ext = $validated['externo_envio_id'] ?? null;
            $ped = $validated['pedidoid'] ?? null;
            if (($ext === null || $ext === '') && $ped === null) {
                return back()
                    ->withErrors(['externo_envio_id' => 'Indique el ID de envío o el pedido correspondiente a su asignación.'])
                    ->withInput();
            }
            if (! DocumentoEntregaTransportista::puedeSubirParaSusAsignaciones($user->usuarioid, $ext, $ped)) {
                return back()
                    ->withErrors(['externo_envio_id' => 'Solo puede cargar comprobantes para envíos que tenga asignados.'])
                    ->withInput();
            }
        }

        $path = $request->file('archivo')->store('documentos_entrega', 'public');

        $documento = DocumentoEntrega::create([
            'titulo' => $validated['titulo'],
            'tipo_documento' => $validated['tipo_documento'],
            'externo_envio_id' => $validated['externo_envio_id'] ?? null,
            'pedidoid' => $validated['pedidoid'] ?? null,
            'almacenid' => $validated['almacenid'] ?? null,
            'archivo_path' => $path,
            'usuarioid' => auth()->id(),
            'metadata' => [
                'original_name' => $request->file('archivo')->getClientOriginalName(),
                'mime' => $request->file('archivo')->getClientMimeType(),
                'size' => $request->file('archivo')->getSize(),
            ],
        ]);

        return redirect()->route('logistica.documentos.show', $documento)
            ->with('success', 'Documento cargado correctamente.');
    }

    public function show(DocumentoEntrega $documento): View
    {
        $this->autorizarAccesoDocumento($documento);
        $documento->load(['usuario', 'pedido']);

        DocumentoEntregaArchivo::asegurarPdfOperativo($documento->fresh());
        $documento->refresh();

        $puedePrevisualizar = $documento->archivo_path
            && Storage::disk('public')->exists($documento->archivo_path)
            && str_ends_with(strtolower($documento->archivo_path), '.pdf');

        return view('logistica.documentos.show', compact('documento', 'puedePrevisualizar'));
    }

    public function preview(DocumentoEntrega $documento): Response|RedirectResponse
    {
        $this->autorizarAccesoDocumento($documento);

        DocumentoEntregaArchivo::asegurarPdfOperativo($documento->fresh());
        $documento->refresh();

        if (! $documento->archivo_path || ! Storage::disk('public')->exists($documento->archivo_path)) {
            return redirect()
                ->route('logistica.documentos.show', $documento)
                ->with('error', 'El archivo no está disponible para previsualizar.');
        }

        $filename = $documento->metadata['original_name'] ?? ($documento->titulo.'.pdf');

        return response()->file(
            Storage::disk('public')->path($documento->archivo_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
            ]
        );
    }

    public function edit(DocumentoEntrega $documento): View
    {
        $this->autorizarAccesoDocumento($documento);
        $this->autorizarEdicionDocumento($documento);

        return view('logistica.documentos.edit', compact('documento'));
    }

    public function update(Request $request, DocumentoEntrega $documento): RedirectResponse
    {
        $this->autorizarAccesoDocumento($documento);
        $this->autorizarEdicionDocumento($documento);

        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo_documento' => ['required', 'string', 'max:50'],
            'externo_envio_id' => ['nullable', 'string', 'max:64'],
            'pedidoid' => ['nullable', 'integer', 'exists:pedido,pedidoid'],
            'archivo' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($request->hasFile('archivo')) {
            if ($documento->archivo_path && Storage::disk('public')->exists($documento->archivo_path)) {
                Storage::disk('public')->delete($documento->archivo_path);
            }
            $path = $request->file('archivo')->store('documentos_entrega', 'public');
            $validated['archivo_path'] = $path;
            $validated['metadata'] = [
                'original_name' => $request->file('archivo')->getClientOriginalName(),
                'mime' => $request->file('archivo')->getClientMimeType(),
                'size' => $request->file('archivo')->getSize(),
            ];
        }

        unset($validated['archivo']);
        $documento->update($validated);

        DocumentoEntregaArchivo::asegurarPdfOperativo($documento->fresh());

        return redirect()->route('logistica.documentos.show', $documento)
            ->with('success', 'Documento actualizado.');
    }

    public function destroy(DocumentoEntrega $documento): RedirectResponse
    {
        $this->autorizarAccesoDocumento($documento);
        abort_unless(
            DocumentoEntregaCatalogo::puedeEliminar($documento, auth()->user()),
            403,
            'No tiene permiso para eliminar este documento.'
        );

        if ($documento->archivo_path && Storage::disk('public')->exists($documento->archivo_path)) {
            Storage::disk('public')->delete($documento->archivo_path);
        }

        $documento->delete();

        return redirect()->route('logistica.documentos.index')
            ->with('success', 'Documento eliminado.');
    }

    public function download(DocumentoEntrega $documento): StreamedResponse|RedirectResponse
    {
        $this->autorizarAccesoDocumento($documento);

        DocumentoEntregaArchivo::asegurarPdfOperativo($documento->fresh());
        $documento->refresh();

        if (! $documento->archivo_path || ! Storage::disk('public')->exists($documento->archivo_path)) {
            return redirect()
                ->route('logistica.documentos.show', $documento)
                ->with('error', 'El archivo no está disponible. Vuelva a cargarlo desde Editar.');
        }

        return Storage::disk('public')->download(
            $documento->archivo_path,
            ($documento->metadata['original_name'] ?? $documento->titulo.'.pdf')
        );
    }

    private function autorizarAccesoDocumento(DocumentoEntrega $documento): void
    {
        abort_unless(
            DocumentoEntregaAcceso::puedeVerDocumento($documento, auth()->user()),
            403
        );
    }

    private function autorizarEdicionDocumento(DocumentoEntrega $documento): void
    {
        abort_unless(
            DocumentoEntregaCatalogo::puedeEditar($documento, auth()->user()),
            403,
            'No tiene permiso para modificar este documento.'
        );
    }
}
