<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $table = 'vehiculo';
    protected $primaryKey = 'vehiculoid';

    public function getRouteKeyName(): string
    {
        return 'vehiculoid';
    }

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'tipovehiculoid',
        'estadovehiculoid',
        'color',
        'activo',
        'ambito_flota',
        'capacidad_kg_override',
        'capacidad_m3_override',
    ];

    protected $casts = [
        'anio'   => 'integer',
        'activo' => 'boolean',
        'capacidad_kg_override' => 'decimal:2',
        'capacidad_m3_override' => 'decimal:2',
    ];

    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipovehiculoid', 'tipovehiculoid');
    }

    public function estadoVehiculo(): BelongsTo
    {
        return $this->belongsTo(EstadoVehiculo::class, 'estadovehiculoid', 'estadovehiculoid');
    }

    public function perfilesTransportista(): HasMany
    {
        return $this->hasMany(PerfilTransportista::class, 'vehiculoid', 'vehiculoid');
    }
}
