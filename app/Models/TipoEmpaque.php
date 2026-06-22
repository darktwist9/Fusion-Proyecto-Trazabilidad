<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEmpaque extends Model
{
    protected $table = 'tipo_empaque';
    protected $primaryKey = 'tipoempaqueid';

    protected $fillable = ['nombre', 'descripcion', 'activo', 'ambito', 'largo_cm', 'ancho_cm', 'alto_cm', 'tara_kg', 'capacidad_unidades', 'unidades_por_pallet'];

    protected $casts = ['activo' => 'boolean'];

    public function cargasEnvio(): HasMany
    {
        return $this->hasMany(CargaEnvio::class, 'tipoempaqueid', 'tipoempaqueid');
    }

    public static function etiquetaUnidadPlural(?string $nombre): string
    {
        $n = mb_strtolower(trim((string) $nombre));

        return match (true) {
            str_contains($n, 'lata') => 'latas',
            str_contains($n, 'frasco') => 'frascos',
            str_contains($n, 'bidón') || str_contains($n, 'bidon') => 'bidones',
            str_contains($n, 'pouch') => 'pouches',
            str_contains($n, 'caja') => 'cajas',
            str_contains($n, 'bolsa') => 'bolsas',
            default => 'unidades',
        };
    }
}
