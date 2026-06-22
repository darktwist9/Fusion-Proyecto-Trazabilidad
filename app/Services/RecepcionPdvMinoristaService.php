<?php

namespace App\Services;

use App\Models\PedidoDistribucion;
use App\Models\RutaDistribucion;
use App\Models\Usuario;
use App\Support\PuntoVentaAccess;
use App\Support\SimulacionRutaCatalogo;
use App\Support\UsuarioRol;

final class RecepcionPdvMinoristaService
{
    public function __construct(
        private readonly CierreEnvioDistribucionPdvService $cierre,
    ) {}

    public function esVistaMinorista(?Usuario $user): bool
    {
        if ($user === null) {
            return false;
        }

        return UsuarioRol::esMinorista($user)
            && ! UsuarioRol::esAdminGlobal($user)
            && ! UsuarioRol::esTransportista($user)
            && ! UsuarioRol::puedeGestionarDistribucionMayorista($user);
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
    public function estadoRecepcion(RutaDistribucion $ruta, ?PedidoDistribucion $pedido = null): array
    {
        if ($ruta->esTrasladoPlantaMayorista()) {
            return [];
        }

        $ruta->loadMissing(['firmaTransportista', 'firmaRecepcion']);
        $resumen = $this->cierre->resumenPasos($ruta);
        $rutaPrefijo = 'punto-venta.rutas';
        $documento = $this->cierre->documentoEntrega($ruta);
        $documentoUrl = $documento ? route('logistica.documentos.show', $documento) : null;
        $urlCierre = route($rutaPrefijo.'.cierre.panel', $ruta);

        if ($resumen['recibido_pdv'] ?? false) {
            return [
                'clave' => 'recibido',
                'etiqueta' => 'Recibido',
                'clase' => 'success',
                'descripcion' => 'Mercadería ingresada al inventario del punto de venta.',
                'puede_firmar' => false,
                'puede_ver_documento' => $documento !== null,
                'url_cierre' => $urlCierre,
                'url_documento' => $documentoUrl,
            ];
        }

        if ($resumen['puede_firmar_recepcion'] ?? false) {
            return [
                'clave' => 'esperando_firma',
                'etiqueta' => 'Esperando su firma',
                'clase' => 'warning',
                'descripcion' => 'El transportista entregó el pedido. Firme la recepción en su punto de venta.',
                'puede_firmar' => true,
                'puede_ver_documento' => false,
                'url_cierre' => $urlCierre,
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
                'url_cierre' => $urlCierre,
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

        if (SimulacionRutaCatalogo::simulacionActivaDistribucion($ruta) || ($resumen['en_ruta'] ?? false)) {
            return [
                'clave' => 'en_camino',
                'etiqueta' => 'En camino',
                'clase' => 'primary',
                'descripcion' => 'Pedido en ruta desde el centro mayorista hacia su punto de venta.',
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
            'descripcion' => 'Entrega registrada, aún no en ruta.',
            'puede_firmar' => false,
            'puede_ver_documento' => false,
            'url_cierre' => null,
            'url_documento' => null,
        ];
    }

    public function minoristaPuedeGestionarRuta(?Usuario $user, RutaDistribucion $ruta): bool
    {
        return PuntoVentaAccess::puedeFirmarRecepcionRuta($user, $ruta);
    }
}
