<?php

namespace App\Support;

use App\Models\ChecklistIncidenteEnvio;

final class IncidenteTransporteCatalogo
{
    /**
     * @param  array<int, array{titulo: string, ocurrio: string}>  $incidentesLineas
     * @return array{texto: ?string, alerta: bool}
     */
    public static function resolverObservacion(array $incidentesLineas, ?string $observacionesGuardadas = null): array
    {
        $ocurridos = array_values(array_filter(
            $incidentesLineas,
            static fn (array $fila): bool => ($fila['ocurrio'] ?? '') === 'Sí'
        ));

        if ($ocurridos !== []) {
            $mensajes = array_map(
                static fn (array $fila): string => self::mensajeIncidente($fila['titulo']),
                $ocurridos
            );

            return [
                'texto' => 'Incidentes reportados: '.implode(' ', $mensajes),
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

        if ($incidentesLineas !== []) {
            return ['texto' => 'Transporte sin incidentes reportados.', 'alerta' => false];
        }

        return ['texto' => null, 'alerta' => false];
    }

    /** @return array{texto: ?string, alerta: bool} */
    public static function observacionDesdeChecklist(?ChecklistIncidenteEnvio $checklist): array
    {
        if ($checklist === null) {
            return ['texto' => null, 'alerta' => false];
        }

        $checklist->loadMissing('detalles.tipoIncidente');

        $lineas = [];
        foreach ($checklist->detalles as $det) {
            $lineas[] = [
                'titulo' => $det->tipoIncidente?->titulo ?? 'Incidente',
                'ocurrio' => $det->ocurrio ? 'Sí' : 'No',
            ];
        }

        return self::resolverObservacion($lineas, $checklist->observaciones);
    }

    private static function mensajeIncidente(string $titulo): string
    {
        $tituloNorm = mb_strtolower(trim($titulo));

        return match (true) {
            str_contains($tituloNorm, 'retraso') => 'Se registró un retraso durante el transporte.',
            str_contains($tituloNorm, 'accidente') => 'Se reportó un accidente o incidente en ruta.',
            str_contains($tituloNorm, 'robo') || str_contains($tituloNorm, 'hurto') || str_contains($tituloNorm, 'extrav') => 'Se reportó robo, hurto o extravío de mercancía.',
            str_contains($tituloNorm, 'mecán') || str_contains($tituloNorm, 'falla') => 'Se registró una falla mecánica del vehículo.',
            str_contains($tituloNorm, 'clim') => 'Las condiciones climáticas afectaron el transporte.',
            str_contains($tituloNorm, 'daño') || str_contains($tituloNorm, 'carga') => 'Se detectó daño a la carga transportada.',
            str_contains($tituloNorm, 'bloqueo') || str_contains($tituloNorm, 'carretera') => 'Hubo bloqueo de carretera durante el traslado.',
            str_contains($tituloNorm, 'tráfico') || str_contains($tituloNorm, 'trafico') => 'El tráfico retrasó la entrega.',
            default => 'Se reportó: '.$titulo.'.',
        };
    }
}
