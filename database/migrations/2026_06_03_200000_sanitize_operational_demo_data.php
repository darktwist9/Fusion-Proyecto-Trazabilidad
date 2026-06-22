<?php

use App\Support\EtiquetaDemo;
use App\Support\EstadoLoteCatalogo;
use App\Support\TransportistaFlotaCatalogo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    private array $renombresExplicitos = [
        'prueba real' => 'Lote Lechuga Crespa Equipetrol',
        'lote demo manual f1' => 'Lote Tomate Norte Banzer',
        'prueba 1' => 'Lote Pimentón La Guardia',
        'mercado prueba' => 'Mercado Satélite Norte',
        'tienda demo minorista' => 'Mercado Satélite Norte',
        'almacén — mercado prueba' => 'Almacén Mercado Central',
        'almacen — mercado prueba' => 'Almacén Mercado Central',
    ];

    public function up(): void
    {
        $this->sanearEtiquetasTabla('insumo', 'insumoid', 'nombre');
        $this->sanearEtiquetasTabla('lote', 'loteid', 'nombre');
        $this->sanearEtiquetasTabla('almacen', 'almacenid', 'nombre');
        $this->sanearEtiquetasTabla('punto_venta', 'puntoventaid', 'nombre');

        if (Schema::hasTable('punto_venta') && Schema::hasColumn('punto_venta', 'direccion')) {
            DB::table('punto_venta')
                ->where('direccion', 'like', 'PDV GPS%')
                ->update(['direccion' => null]);
        }

        $this->normalizarAmbitoMayorista();
        $this->limpiarPedidosPendientesAgricola();
        $this->certificarLotesCosechadosPendientes();
    }

    public function down(): void
    {
        // Limpieza operativa; no reversible.
    }

    private function sanearEtiquetasTabla(string $tabla, string $pk, string $columna): void
    {
        if (! Schema::hasTable($tabla)) {
            return;
        }

        foreach (DB::table($tabla)->get([$pk, $columna]) as $row) {
            $actual = (string) ($row->{$columna} ?? '');
            if ($actual === '') {
                continue;
            }

            $clave = mb_strtolower(trim($actual));
            $nuevo = $this->renombresExplicitos[$clave] ?? null;

            if ($nuevo === null && EtiquetaDemo::esDemo($actual)) {
                $nuevo = $this->sustitutoGenerico($tabla, $actual);
            }

            if ($nuevo === null || $nuevo === $actual) {
                continue;
            }

            DB::table($tabla)->where($pk, $row->{$pk})->update([$columna => $nuevo]);
        }
    }

    private function sustitutoGenerico(string $tabla, string $actual): string
    {
        $limpio = preg_replace('/\b(prueba|demo|test)\b/iu', '', $actual) ?? '';
        $limpio = trim(preg_replace('/\s+/', ' ', $limpio) ?? '');

        if ($limpio !== '') {
            return $limpio;
        }

        return match ($tabla) {
            'lote' => 'Lote operativo',
            'insumo' => 'Insumo operativo',
            'almacen' => 'Almacén operativo',
            'punto_venta' => 'Punto de venta operativo',
            default => 'Registro operativo',
        };
    }

    private function normalizarAmbitoMayorista(): void
    {
        if (Schema::hasTable('vehiculo') && Schema::hasColumn('vehiculo', 'ambito_flota')) {
            DB::table('vehiculo')
                ->where('placa', 'like', 'SCZ-MAY-%')
                ->update(['ambito_flota' => TransportistaFlotaCatalogo::MAYORISTA]);
        }

        if (! Schema::hasTable('perfil_transportista')) {
            return;
        }

        $transportistaIds = DB::table('usuario')
            ->where(function ($q) {
                $q->where('nombreusuario', 'like', '%mayorista%')
                    ->orWhere('apellido', 'like', '%Mayorista%');
            })
            ->pluck('usuarioid');

        if ($transportistaIds->isEmpty()) {
            return;
        }

        DB::table('perfil_transportista')
            ->whereIn('usuarioid', $transportistaIds)
            ->update(['ambito_flota' => TransportistaFlotaCatalogo::MAYORISTA]);
    }

    private function limpiarPedidosPendientesAgricola(): void
    {
        if (! Schema::hasTable('pedido')) {
            return;
        }

        $pendientes = DB::table('pedido')
            ->whereIn('estado', ['sin asignacion', 'pendiente'])
            ->where('numero_solicitud', 'not like', 'INT-%')
            ->pluck('pedidoid');

        foreach ($pendientes as $pedidoid) {
            if (Schema::hasTable('envio_asignacion_multiple')) {
                DB::table('envio_asignacion_multiple')->where('pedidoid', $pedidoid)->delete();
            }
            if (Schema::hasTable('detallepedido')) {
                DB::table('detallepedido')->where('pedidoid', $pedidoid)->delete();
            }
            if (Schema::hasTable('documento_entrega')) {
                DB::table('documento_entrega')->where('pedidoid', $pedidoid)->update(['pedidoid' => null]);
            }
            DB::table('pedido')->where('pedidoid', $pedidoid)->delete();
        }
    }

    private function certificarLotesCosechadosPendientes(): void
    {
        if (! Schema::hasTable('lote') || ! Schema::hasTable('certificacion_lote')) {
            return;
        }

        $estadoCosechado = EstadoLoteCatalogo::idsPorSlugs(['cosechado']);
        if ($estadoCosechado === []) {
            return;
        }

        $certificados = DB::table('certificacion_lote')->pluck('loteid')->unique()->all();

        $pendientes = DB::table('lote')
            ->whereIn('estadolotetipoid', $estadoCosechado)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('produccion')
                    ->whereColumn('produccion.loteid', 'lote.loteid');
            })
            ->when($certificados !== [], fn ($q) => $q->whereNotIn('loteid', $certificados))
            ->pluck('loteid');

        $estadoCertificadoId = DB::table('estadolote_tipo')
            ->where('nombre', 'Certificado')
            ->value('estadolotetipoid');

        if (! $estadoCertificadoId) {
            $estadoCertificadoId = DB::table('estadolote_tipo')->insertGetId([
                'nombre' => 'Certificado',
                'descripcion' => 'Lote validado para despacho y trazabilidad',
            ], 'estadolotetipoid');
        }

        $adminId = DB::table('usuario')
            ->join('model_has_roles', 'usuario.usuarioid', '=', 'model_has_roles.model_id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'admin')
            ->where('model_has_roles.model_type', 'App\\Models\\Usuario')
            ->value('usuarioid');

        foreach ($pendientes as $loteid) {
            $codigo = 'CERT-'.now()->format('Y').'-'.str_pad((string) $loteid, 4, '0', STR_PAD_LEFT);

            DB::table('certificacion_lote')->updateOrInsert(
                ['loteid' => $loteid],
                [
                    'usuarioid' => $adminId,
                    'codigo_certificado' => $codigo,
                    'resultado' => 'Certificado',
                    'observaciones' => 'Certificación automática de saneamiento operativo.',
                    'recomendaciones' => null,
                    'fecha_certificacion' => now(),
                ]
            );

            DB::table('lote')->where('loteid', $loteid)->update([
                'estadolotetipoid' => $estadoCertificadoId,
            ]);

            if (Schema::hasTable('historial_estados_lote')) {
                DB::table('historial_estados_lote')->insert([
                    'loteid' => $loteid,
                    'estadolotetipoid' => $estadoCertificadoId,
                    'fecha_cambio' => now(),
                    'observaciones' => 'Certificado: '.$codigo.' — saneamiento operativo',
                    'usuarioid' => $adminId,
                ]);
            }
        }
    }
};
