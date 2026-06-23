<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Valida que la ubicación de un lote esté en tierra firme (no mar, ríos ni cuerpos de agua).
 */
class LoteUbicacionTerrestreService
{
    private const MUESTRAS_PERIMETRO = 16;

    private const TIMEOUT_NOMINATIM_SEG = 4;

    private const TIMEOUT_OVERPASS_SEG = 3;

    /** @var list<list<array{0: float, 1: float}>>|null */
    private ?array $viasAguaCache = null;

    private ?string $viasAguaCacheKey = null;

    private const TIPOS_AGUA_NOMINATIM = [
        'sea', 'water', 'ocean', 'bay', 'strait', 'lake', 'reservoir', 'pond', 'river',
        'stream', 'canal', 'dock', 'marina', 'basin', 'lagoon', 'wetland', 'marsh', 'swamp',
    ];

    private const DISTANCIA_MAX_VIA_AGUA_METROS = 35;

    /** @return array{ok: bool, codigo?: string, mensaje?: string, hectareas_maximas?: float|null} */
    public function validarMarcador(float $lat, float $lng): array
    {
        try {
            $clasificacion = $this->clasificarPuntoNominatim($lat, $lng);
            if ($clasificacion === 'agua') {
                return [
                    'ok' => false,
                    'codigo' => 'en_agua',
                    'mensaje' => 'No se puede sembrar en el mar ni en cuerpos de agua. Elija un punto sobre tierra firme.',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Validación terrestre omitida (marcador)', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => true, 'verificacion_omitida' => true];
        }

        return ['ok' => true];
    }

    /** @return array{ok: bool, codigo?: string, mensaje?: string, hectareas_maximas?: float|null} */
    public function validarSuperficie(float $lat, float $lng, float $hectareas, bool $ligera = false): array
    {
        try {
            $marcador = $this->validarMarcador($lat, $lng);
            if (! ($marcador['ok'] ?? false)) {
                return $marcador;
            }

            if ($hectareas <= 0) {
                return ['ok' => true, 'hectareas_maximas' => null];
            }

            if ($ligera) {
                return [
                    'ok' => true,
                    'hectareas_maximas' => round($hectareas, 3),
                    'verificacion_ligera' => true,
                ];
            }

            $radio = $this->radioMetros($hectareas);
            $poligonosAgua = $this->poligonosAguaEnRadio($lat, $lng, $radio);
            $viasAgua = $this->viasAguaLinealesEnRadio($lat, $lng, max(120, (int) ceil($radio)));

            if ($this->puntoEnAlgunPoligonoAgua($lat, $lng, $poligonosAgua)) {
                return [
                    'ok' => false,
                    'codigo' => 'en_agua',
                    'mensaje' => 'No se puede sembrar en el mar ni en cuerpos de agua. Elija un punto sobre tierra firme.',
                ];
            }

            if ($this->circuloCabeEnTierra($lat, $lng, $radio, $poligonosAgua, $viasAgua)) {
                return ['ok' => true, 'hectareas_maximas' => round($hectareas, 3)];
            }

            $maxHa = $this->buscarHectareasMaximas($lat, $lng, $hectareas, $poligonosAgua, $viasAgua);

            if ($maxHa <= 0.01) {
                return [
                    'ok' => false,
                    'codigo' => 'circulo_en_agua',
                    'mensaje' => 'No hay suficiente tierra firme en esta ubicación para el área indicada.',
                    'hectareas_maximas' => 0,
                ];
            }

            return [
                'ok' => false,
                'codigo' => 'circulo_en_agua',
                'mensaje' => sprintf(
                    'El área indicada se extiende hacia el mar o un cuerpo de agua. En este punto solo puede planificar hasta %s ha como máximo.',
                    number_format($maxHa, 2, ',', '.')
                ),
                'hectareas_maximas' => $maxHa,
            ];
        } catch (\Throwable $e) {
            Log::warning('Validación terrestre omitida (superficie)', [
                'lat' => $lat,
                'lng' => $lng,
                'hectareas' => $hectareas,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => true, 'hectareas_maximas' => round($hectareas, 3), 'verificacion_omitida' => true];
        }
    }

    public function radioMetros(float $hectareas): float
    {
        if ($hectareas <= 0) {
            return 0.0;
        }

        return sqrt($hectareas * 10000 / M_PI);
    }

    private function buscarHectareasMaximas(float $lat, float $lng, float $haLimite, array $poligonosAgua, array $viasAgua): float
    {
        $bajo = 0.0;
        $alto = max(0.01, $haLimite);

        for ($i = 0; $i < 10; $i++) {
            $medio = ($bajo + $alto) / 2;
            $radio = $this->radioMetros($medio);
            if ($this->circuloCabeEnTierra($lat, $lng, $radio, $poligonosAgua, $viasAgua)) {
                $bajo = $medio;
            } else {
                $alto = $medio;
            }
        }

        return floor($bajo * 1000) / 1000;
    }

    /**
     * @param  list<list<array{0: float, 1: float}>>  $poligonosAgua
     * @param  list<array<string, mixed>>  $viasAgua
     */
    private function circuloCabeEnTierra(float $lat, float $lng, float $radioMetros, array $poligonosAgua, array $viasAgua = []): bool
    {
        foreach ($this->puntosMuestra($lat, $lng, $radioMetros) as [$plat, $plng]) {
            if ($this->puntoEnAlgunPoligonoAgua($plat, $plng, $poligonosAgua)) {
                return false;
            }
        }

        if ($viasAgua === []) {
            $viasAgua = $this->viasAguaLinealesEnRadio($lat, $lng, max(120, (int) ceil($radioMetros)));
        }

        foreach ($this->puntosMuestra($lat, $lng, $radioMetros) as [$plat, $plng]) {
            if ($this->puntoCercaDeViaAgua($plat, $plng, $viasAgua, self::DISTANCIA_MAX_VIA_AGUA_METROS)) {
                return false;
            }
        }

        return true;
    }

    private function puntoEnAguaNominatim(float $lat, float $lng): bool
    {
        return $this->clasificarPuntoNominatim($lat, $lng) === 'agua';
    }

    /** @return list<array{0: float, 1: float}> */
    private function puntosMuestra(float $lat, float $lng, float $radioMetros, ?int $muestras = null): array
    {
        $n = $muestras ?? self::MUESTRAS_PERIMETRO;
        $puntos = [[$lat, $lng]];

        if ($radioMetros <= 0) {
            return $puntos;
        }

        $dLatBase = $radioMetros / 111320;
        $dLngBase = $radioMetros / (111320 * max(0.2, cos(deg2rad($lat))));

        for ($i = 0; $i < $n; $i++) {
            $ang = 2 * M_PI * $i / $n;
            $puntos[] = [
                $lat + $dLatBase * sin($ang),
                $lng + $dLngBase * cos($ang),
            ];
        }

        return $puntos;
    }

    private function puntoEnAgua(float $lat, float $lng): bool
    {
        if ($this->clasificarPuntoNominatim($lat, $lng) === 'agua') {
            return true;
        }

        $poligonos = $this->poligonosAguaEnRadio($lat, $lng, 250);
        if ($this->puntoEnAlgunPoligonoAgua($lat, $lng, $poligonos)) {
            return true;
        }

        $vias = $this->viasAguaLinealesEnRadio($lat, $lng, 120);

        return $this->puntoCercaDeViaAgua($lat, $lng, $vias, self::DISTANCIA_MAX_VIA_AGUA_METROS);
    }

  /** @return 'agua'|'tierra'|'desconocido' */
    private function clasificarPuntoNominatim(float $lat, float $lng): string
    {
        $cacheKey = 'lote_nominatim_'.round($lat, 5).'_'.round($lng, 5);

        $datos = Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            try {
                $response = Http::timeout(self::TIMEOUT_NOMINATIM_SEG)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Accept-Language' => 'es',
                        'User-Agent' => 'AgroFusion-Trazabilidad/1.0 (validacion-lotes)',
                    ])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'format' => 'jsonv2',
                        'lat' => $lat,
                        'lon' => $lng,
                        'zoom' => 16,
                        'addressdetails' => 0,
                    ]);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json();
            } catch (\Throwable $e) {
                Log::debug('Nominatim no disponible para validación de lote', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });

        if (! is_array($datos)) {
            return 'desconocido';
        }

        return $this->esRespuestaNominatimAgua($datos) ? 'agua' : 'tierra';
    }

    /** @param  array<string, mixed>  $datos */
    private function esRespuestaNominatimAgua(array $datos): bool
    {
        $category = strtolower((string) ($datos['category'] ?? ''));
        $type = strtolower((string) ($datos['type'] ?? ''));
        $class = strtolower((string) ($datos['class'] ?? ''));

        if (in_array($category, ['water', 'waterway'], true)) {
            return true;
        }

        if ($class === 'waterway' || ($class === 'natural' && $type === 'water')) {
            return true;
        }

        return in_array($type, self::TIPOS_AGUA_NOMINATIM, true);
    }

    /** @return list<list<array{0: float, 1: float}>>> */
    private function poligonosAguaEnRadio(float $lat, float $lng, float $radioMetros): array
    {
        $radioMetros = max(50, $radioMetros);
        $cacheKey = 'lote_agua_osm_'
            .round($lat, 4).'_'
            .round($lng, 4).'_'
            .(int) ceil($radioMetros / 50);

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $radioMetros) {
            $bbox = $this->bboxDesdeRadio($lat, $lng, $radioMetros * 1.05);
            $query = sprintf(
                '[out:json][timeout:25];('
                .'way["natural"="water"](%f,%f,%f,%f);'
                .'way["natural"="bay"](%f,%f,%f,%f);'
                .'way["water"](%f,%f,%f,%f);'
                .'way["waterway"="riverbank"](%f,%f,%f,%f);'
                .'way["landuse"="reservoir"](%f,%f,%f,%f);'
                .'relation["natural"="water"](%f,%f,%f,%f);'
                .');out geom;',
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
                $bbox['south'], $bbox['west'], $bbox['north'], $bbox['east'],
            );

            try {
                $response = Http::timeout(self::TIMEOUT_OVERPASS_SEG)
                    ->withHeaders(['User-Agent' => 'AgroFusion-Trazabilidad/1.0 (validacion-lotes)'])
                    ->asForm()
                    ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

                if (! $response->successful()) {
                    return [];
                }

                $json = $response->json();
                if (! is_array($json) || ! isset($json['elements']) || ! is_array($json['elements'])) {
                    return [];
                }

                return $this->extraerPoligonosAgua($json['elements']);
            } catch (\Throwable $e) {
                Log::debug('Overpass no disponible para validación de lote', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /** @param  list<array<string, mixed>>  $elements @return list<list<array{0: float, 1: float}>> */
    private function extraerPoligonosAgua(array $elements): array
    {
        $poligonos = [];

        foreach ($elements as $element) {
            if (! is_array($element)) {
                continue;
            }

            $geometry = $element['geometry'] ?? null;
            if (! is_array($geometry) || count($geometry) < 3) {
                continue;
            }

            $anello = [];
            foreach ($geometry as $punto) {
                if (! is_array($punto)) {
                    continue;
                }
                $plat = $punto['lat'] ?? null;
                $plng = $punto['lon'] ?? null;
                if ($plat === null || $plng === null) {
                    continue;
                }
                $anello[] = [(float) $plat, (float) $plng];
            }

            if (count($anello) >= 3) {
                $poligonos[] = $anello;
            }
        }

        return $poligonos;
    }

    /** @return list<list<array{0: float, 1: float}>> */
    private function viasAguaLinealesEnRadio(float $lat, float $lng, int $radioMetros): array
    {
        $radioMetros = max(40, $radioMetros);
        $cacheKey = 'lote_agua_via_'
            .round($lat, 4).'_'
            .round($lng, 4).'_'
            .(int) ceil($radioMetros / 40);

        if ($this->viasAguaCacheKey === $cacheKey && is_array($this->viasAguaCache)) {
            return $this->viasAguaCache;
        }

        $vias = Cache::remember($cacheKey, 3600, function () use ($lat, $lng, $radioMetros) {
            $query = sprintf(
                '[out:json][timeout:15];('
                .'way["waterway"~"^(river|stream|canal|drain|ditch)$"](around:%d,%f,%f);'
                .');out geom;',
                $radioMetros,
                $lat,
                $lng
            );

            try {
                $response = Http::timeout(self::TIMEOUT_OVERPASS_SEG)
                    ->withHeaders(['User-Agent' => 'AgroFusion-Trazabilidad/1.0 (validacion-lotes)'])
                    ->asForm()
                    ->post('https://overpass-api.de/api/interpreter', ['data' => $query]);

                if (! $response->successful()) {
                    return [];
                }

                $json = $response->json();
                if (! is_array($json) || ! isset($json['elements']) || ! is_array($json['elements'])) {
                    return [];
                }

                $resultado = [];
                foreach ($json['elements'] as $element) {
                    if (! is_array($element)) {
                        continue;
                    }
                    $geometry = $element['geometry'] ?? null;
                    if (! is_array($geometry) || count($geometry) < 2) {
                        continue;
                    }
                    $linea = [];
                    foreach ($geometry as $punto) {
                        if (! is_array($punto)) {
                            continue;
                        }
                        $plat = $punto['lat'] ?? null;
                        $plng = $punto['lon'] ?? null;
                        if ($plat === null || $plng === null) {
                            continue;
                        }
                        $linea[] = [(float) $plat, (float) $plng];
                    }
                    if (count($linea) >= 2) {
                        $resultado[] = $linea;
                    }
                }

                return $resultado;
            } catch (\Throwable $e) {
                Log::debug('Overpass vías de agua no disponible', [
                    'lat' => $lat,
                    'lng' => $lng,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        });

        $this->viasAguaCacheKey = $cacheKey;
        $this->viasAguaCache = $vias;

        return $vias;
    }

    /** @param  list<list<array{0: float, 1: float}>>  $vias */
    private function puntoCercaDeViaAgua(float $lat, float $lng, array $vias, float $maxMetros): bool
    {
        foreach ($vias as $linea) {
            $n = count($linea);
            for ($i = 0; $i < $n - 1; $i++) {
                $dist = $this->distanciaPuntoSegmentoMetros(
                    $lat,
                    $lng,
                    $linea[$i][0],
                    $linea[$i][1],
                    $linea[$i + 1][0],
                    $linea[$i + 1][1]
                );
                if ($dist <= $maxMetros) {
                    return true;
                }
            }
        }

        return false;
    }

    private function distanciaPuntoSegmentoMetros(
        float $pLat,
        float $pLng,
        float $aLat,
        float $aLng,
        float $bLat,
        float $bLng
    ): float {
        $aprox = $this->distanciaMetros($pLat, $pLng, $aLat, $aLng);
        $bprox = $this->distanciaMetros($pLat, $pLng, $bLat, $bLng);
        $ab = $this->distanciaMetros($aLat, $aLng, $bLat, $bLng);

        if ($ab <= 0.5) {
            return min($aprox, $bprox);
        }

        $t = max(0, min(1, (
            (($pLat - $aLat) * ($bLat - $aLat)) + (($pLng - $aLng) * ($bLng - $aLng))
        ) / ((($bLat - $aLat) ** 2) + (($bLng - $aLng) ** 2) ?: 1e-12)));

        $proyLat = $aLat + $t * ($bLat - $aLat);
        $proyLng = $aLng + $t * ($bLng - $aLng);

        return $this->distanciaMetros($pLat, $pLng, $proyLat, $proyLng);
    }

    private function distanciaMetros(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    /** @param  list<list<array{0: float, 1: float}>>  $poligonos */
    private function puntoEnAlgunPoligonoAgua(float $lat, float $lng, array $poligonos): bool
    {
        foreach ($poligonos as $anello) {
            if ($this->puntoEnPoligono($lat, $lng, $anello)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<array{0: float, 1: float}>  $anello */
    private function puntoEnPoligono(float $lat, float $lng, array $anello): bool
    {
        $n = count($anello);
        if ($n < 3) {
            return false;
        }

        $dentro = false;
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $anello[$i][0];
            $xi = $anello[$i][1];
            $yj = $anello[$j][0];
            $xj = $anello[$j][1];

            $intersecta = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersecta) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }

    /** @return array{south: float, west: float, north: float, east: float} */
    private function bboxDesdeRadio(float $lat, float $lng, float $radioMetros): array
    {
        $dLat = $radioMetros / 111320;
        $dLng = $radioMetros / (111320 * max(0.2, cos(deg2rad($lat))));

        return [
            'south' => $lat - $dLat,
            'west' => $lng - $dLng,
            'north' => $lat + $dLat,
            'east' => $lng + $dLng,
        ];
    }
}
