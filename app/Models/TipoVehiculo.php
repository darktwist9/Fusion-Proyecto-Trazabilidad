<?php

namespace App\Models;

use App\Models\TipoTransporte;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Tipos de vehículo (columna codigo añadida en Bloque A para referencia estable).
 */
class TipoVehiculo extends Model
{
    protected $table = 'tipo_vehiculo';

    protected $primaryKey = 'tipovehiculoid';

    protected $fillable = ['nombre', 'descripcion', 'tamano', 'licencia_requerida', 'capacidad_kg', 'capacidad_m3', 'activo', 'codigo', 'largo_m', 'ancho_m', 'alto_m', 'factor_volumen_util'];

    protected $casts = [
        'capacidad_kg' => 'decimal:2',
        'capacidad_m3' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function tiposTransporte(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoTransporte::class,
            'tipo_vehiculo_tipo_transporte',
            'tipovehiculoid',
            'tipotransporteid',
            'tipovehiculoid',
            'tipotransporteid'
        );
    }
}
