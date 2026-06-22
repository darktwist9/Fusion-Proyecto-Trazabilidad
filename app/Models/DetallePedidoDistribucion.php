<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedidoDistribucion extends Model
{
    protected $table = 'detalle_pedido_distribucion';

    protected $primaryKey = 'detallepedidodistribucionid';

    public $timestamps = false;

    protected $fillable = [
        'pedidodistribucionid',
        'insumoid',
        'insumo_planta_referenciaid',
        'insumo_presentacionid',
        'tipo_envase',
        'es_solicitud_custom',
        'producto_nombre',
        'cantidad',
        'observaciones',
    ];

    protected $casts = [
        'detallepedidodistribucionid' => 'integer',
        'pedidodistribucionid' => 'integer',
        'insumoid' => 'integer',
        'insumo_planta_referenciaid' => 'integer',
        'insumo_presentacionid' => 'integer',
        'es_solicitud_custom' => 'boolean',
        'cantidad' => 'float',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoDistribucion::class, 'pedidodistribucionid', 'pedidodistribucionid');
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumoid', 'insumoid');
    }

    public function insumoPlantaReferencia(): BelongsTo
    {
        return $this->belongsTo(Insumo::class, 'insumo_planta_referenciaid', 'insumoid');
    }

    public function presentacion(): BelongsTo
    {
        return $this->belongsTo(InsumoPresentacion::class, 'insumo_presentacionid', 'insumo_presentacionid');
    }
}
