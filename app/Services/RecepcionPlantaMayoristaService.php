<?php

namespace App\Services;

use App\Models\RutaDistribucion;
use App\Models\Usuario;
use App\Support\MayoristaAccess;
use App\Support\RutaDistribucionCatalogo;
use App\Support\SimulacionRutaCatalogo;
use App\Support\UsuarioRol;
use Illuminate\Database\Eloquent\Builder;

final class RecepcionPlantaMayoristaService
{
    public const FILTRO_TODOS = 'todos';

    public const FILTRO_EN_CAMINO = 'en_camino';

    public const FILTRO_ESPERANDO_FIRMA = 'esperando_firma';

    public const FILTRO_RECIBIDOS = 'recibidos';

    public function __construct(
        private readonly CierreEnvioPlantaMayoristaService $cierre,
    ) {}

    /** @return array{en_camino: int, esperando_firma: int, recibidos: int, activos: int} */
    public function conteos(Usuario $user): array
    {
        $base = $this->queryBase($user);

        return [
            'en_camino' => (clone $base)->tap(fn (Builder $q) => $this->aplicarFiltro($q, self::FILTRO_EN_CAMINO))->count(),
            'esperando_firma' => (clone $base)->tap(fn (Builder $q) => $this->aplicarFiltro($q, self::FILTRO_ESPERANDO_FIRMA))->count(),
            'recibidos' => (clone $base)->tap(fn (Builder $q) => $this->aplicarFiltro($q, self::FILTRO_RECIBIDOS))->count(),
            'activos' => (clone $base)
                ->where('estado', '!=', RutaDistribucionCatalogo::ESTADO_COMPLETADA)
                ->where('estado', '!=', RutaDistribucionCatalogo::ESTADO_RECHAZADA)
                ->count(),
        ];
    }

    /** @return Builder<RutaDistribucion> */
    public function queryListado(Usuario $user, ?string $filtro = null): Builder
    {
        $query = $this->queryBase($user)->orderByDesc('rutadistribucionid');

        if ($filtro !== null && $filtro !== '' && $filtro !== self::FILTRO_TODOS) {
            $this->aplicarFiltro($query, $filtro);
        }

        return $query;
    }

    /**
     * @return array{
     *     clave: string,
     *     etiqueta: string,
     *     clase: string,
     *     descripcion: string,
     *     puede_firmar: bool,
     *     puede_ver_documento: bool,
     *     url_cierre: string|null,
     *     url_documento: string|null
     * }
     */
    public function estadoRecepcion(RutaDistribucion $ruta): array
    {
        $ruta->loadMissing(['firmaTransportista', 'firmaRecepcion']);
        $resumen = $this->cierre->resumenPasos($ruta);
        $rutaPrefijo = 'almacen-mayorista.traslados-planta';
        $documento = $this->cierre->documentoEntrega($ruta);
        $documentoUrl = $documento ? route('logistica.documentos.show', $documento) : null;

        if ($resumen['recibido_planta'] ?? false) {
            return [
                'clave' => 'recibido',
                'etiqueta' => 'Recibido',
                'clase' => 'success',
                'descripcion' => 'Mercadería ingresada al almacén y documento generado.',
                'puede_firmar' => false,
                'puede_ver_documento' => $documento !== null,
                'url_cierre' => route($rutaPrefijo.'.cierre.panel', $ruta),
                'url_documento' => $documentoUrl,
            ];
        }

        if ($resumen['puede_firmar_recepcion'] ?? false) {
            return [
                'clave' => 'esperando_firma',
                'etiqueta' => 'Esperando su firma',
                'clase' => 'warning',
                'descripcion' => 'El transportista entregó la carga. Firme la recepción en almacén.',
                'puede_firmar' => true,
                'puede_ver_documento' => false,
                'url_cierre' => route($rutaPrefijo.'.cierre.panel', $ruta),
                'url_documento' => null,
            ];
        }

        if ($resumen['llegada_confirmada'] ?? false) {
            return [
                'clave' => 'esperando_transportista',
                'etiqueta' => 'En proceso de cierre',
                'clase' => 'info',
                'descripcion' => 'Llegada confirmada. Espere que el transportista complete incidentes y firma.',
                'puede_firmar' => false,
                'puede_ver_documento' => false,
                'url_cierre' => route($rutaPrefijo.'.cierre.panel', $ruta),
                'url_documento' => null,
            ];
        }

        if ($resumen['esperando_confirmacion'] ?? false) {
            return [
                'clave' => 'esperando_recepcion',
                'etiqueta' => 'Esperando recepción',
                'clase' => 'warning',
                'descripcion' => 'El vehículo llegó al destino. Pendiente confirmación de llegada para cerrar la recepción.',
                'puede_firmar' => false,
                'puede_ver_documento' => false,
                'url_cierre' => null,
                'url_documento' => null,
            ];
        }

        if (SimulacionRutaCatalogo::simulacionActivaDistribucion($ruta)
            || ($resumen['en_ruta'] ?? false)) {
            return [
                'clave' => 'en_camino',
                'etiqueta' => 'En camino',
                'clase' => 'primary',
                'descripcion' => 'Traslado en ruta desde planta hacia su almacén.',
                'puede_firmar' => false,
                'puede_ver_documento' => false,
                'url_cierre' => null,
                'url_documento' => null,
            ];
        }

        if (RutaDistribucionCatalogo::pendienteAprobacionPlanta($ruta)) {
            return [
                'clave' => 'pendiente_planta',
                'etiqueta' => 'Pendiente en planta',
                'clase' => 'secondary',
                'descripcion' => 'El traslado aún no salió de planta (aprobación o salida pendiente).',
                'puede_firmar' => false,
                'puede_ver_documento' => false,
                'url_cierre' => null,
                'url_documento' => null,
            ];
        }

        return [
            'clave' => 'programado',
            'etiqueta' => 'Programado',
            'clase' => 'secondary',
            'descripcion' => 'Traslado registrado, aún no en ruta.',
            'puede_firmar' => false,
            'puede_ver_documento' => false,
            'url_cierre' => null,
            'url_documento' => null,
        ];
    }

    public function esVistaMayorista(?Usuario $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (UsuarioRol::esMayorista($user) || UsuarioRol::esJefeMayorista($user))
            && ! UsuarioRol::esAdminGlobal($user)
            && ! UsuarioRol::esJefePlanta($user)
            && ! UsuarioRol::esTransportista($user);
    }

    /** @return Builder<RutaDistribucion> */
    private function queryBase(Usuario $user): Builder
    {
        $query = RutaDistribucion::query()
            ->where('tipo_ruta', RutaDistribucionCatalogo::TIPO_RUTA_PLANTA_MAYORISTA);

        if (UsuarioRol::esMayorista($user) && ! UsuarioRol::esAdminGlobal($user)) {
            $ids = MayoristaAccess::idsAlmacenesMayorista($user);
            $query->whereIn('almacen_mayorista_destinoid', $ids !== [] ? $ids : [-1]);
        }

        return $query;
    }

    /** @param  Builder<RutaDistribucion>  $query */
    private function aplicarFiltro(Builder $query, string $filtro): void
    {
        match ($filtro) {
            self::FILTRO_EN_CAMINO => $query
                ->where('estado', RutaDistribucionCatalogo::ESTADO_EN_RUTA)
                ->whereNull('llegada_confirmada_at'),
            self::FILTRO_ESPERANDO_FIRMA => $query
                ->where('estado', RutaDistribucionCatalogo::ESTADO_EN_RUTA)
                ->whereNotNull('llegada_confirmada_at')
                ->whereHas('firmaTransportista')
                ->whereDoesntHave('firmaRecepcion'),
            self::FILTRO_RECIBIDOS => $query
                ->where('estado', RutaDistribucionCatalogo::ESTADO_COMPLETADA),
            default => null,
        };
    }
}
