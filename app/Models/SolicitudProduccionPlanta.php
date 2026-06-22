<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudProduccionPlanta extends Model
{
    protected $table = 'solicitud_produccion_planta';

    protected $primaryKey = 'solicitudproduccionplantaid';

    public $timestamps = false;

    protected $fillable = [
        'numero_solicitud',
        'pedidodistribucionid',
        'almacen_mayorista_destinoid',
        'insumo_planta_referenciaid',
        'insumo_presentacionid',
        'producto_nombre',
        'tipo_envase',
        'cantidad',
        'unidad_etiqueta',
        'estado',
        'fecha_entrega_deseada',
        'hora_entrega_deseada',
        'observaciones',
        'creado_por_usuarioid',
        'aceptado_por_usuarioid',
        'fecha_aceptacion',
        'fecha_completada',
        'fechapedido',
    ];

    protected $casts = [
        'solicitudproduccionplantaid' => 'integer',
        'pedidodistribucionid' => 'integer',
        'almacen_mayorista_destinoid' => 'integer',
        'insumo_planta_referenciaid' => 'integer',
        'insumo_presentacionid' => 'integer',
        'cantidad' => 'float',
        'fecha_entrega_deseada' => 'date',
        'fecha_aceptacion' => 'datetime',
        'fecha_completada' => 'datetime',
        'fechapedido' => 'datetime',
        'creado_por_usuarioid' => 'integer',
        'aceptado_por_usuarioid' => 'integer',
    ];

    public function pedidoDistribucion(): BelongsTo
    {
        return $this->belongsTo(PedidoDistribucion::class, 'pedidodistribucionid', 'pedidodistribucionid');
    }

    public function almacenMayoristaDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_mayorista_destinoid', 'almacenid');
    }

    public function insumoPlantaReferencia(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_planta_referenciaid', 'insumoid');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(InsumoPresentacion::class, 'insumo_presentacionid', 'insumo_presentacionid');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por_usuarioid', 'usuarioid');
    }

    public function aceptadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aceptado_por_usuarioid', 'usuarioid');
    }

    public function getRouteKeyName(): string
    {
        return 'solicitudproduccionplantaid';
    }
}
