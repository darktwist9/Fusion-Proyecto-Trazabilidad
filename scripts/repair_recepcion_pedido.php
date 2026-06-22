<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AlmacenMovimiento;
use App\Models\Pedido;
use App\Services\RecepcionPlantaEnvioService;

$numero = $argv[1] ?? 'PED-20260622-0001';
$pedido = Pedido::query()
    ->where('numero_solicitud', $numero)
    ->with(['detalles.insumo', 'envioAsignacion'])
    ->first();

if ($pedido === null) {
    echo "Pedido no encontrado: {$numero}\n";
    exit(1);
}

$asignacion = $pedido->envioAsignacion;
$ref = $asignacion?->externo_envio_id ?? '';
echo "Pedido: {$pedido->numero_solicitud}\n";
echo "Envío: {$ref} estado: ".($asignacion->estado ?? 'n/a')."\n";

foreach ($pedido->detalles as $detalle) {
    $nombre = $detalle->cultivo_personalizado ?? $detalle->insumo?->nombre ?? '?';
    echo " - {$nombre}: {$detalle->cantidad}\n";
}

$movs = AlmacenMovimiento::query()
    ->where('referencia', $ref)
    ->with('insumo')
    ->get();

echo 'Movimientos actuales: '.$movs->count()."\n";
foreach ($movs as $m) {
    echo "  {$m->insumo?->nombre}: +{$m->cantidad}\n";
}

if (($argv[2] ?? '') !== '--apply') {
    echo "\nEjecute con --apply para registrar detalles faltantes.\n";
    exit(0);
}

$service = app(RecepcionPlantaEnvioService::class);
$usuario = \App\Models\Usuario::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()
    ?? \App\Models\Usuario::query()->first();

if ($usuario === null) {
    echo "No hay usuario para la reparación.\n";
    exit(1);
}

// Permite reprocesar detalles que no tengan movimiento de recepción.
$almacen = null;
$tipoIngreso = (new ReflectionClass($service))->getMethod('tipoMovimientoIngresoRecepcion');
$tipoIngreso->setAccessible(true);
$tipoMov = $tipoIngreso->invoke($service);

$resolverAlmacen = (new ReflectionClass($service))->getMethod('resolverAlmacenPlantaDesdePedido');
$resolverAlmacen->setAccessible(true);
$almacen = $resolverAlmacen->invoke($service, $pedido);

if ($almacen === null) {
    echo "No se encontró almacén planta.\n";
    exit(1);
}

$registrados = 0;
foreach ($pedido->detalles as $detalle) {
    $cantidad = (float) $detalle->cantidad;
    if ($cantidad <= 0) {
        continue;
    }
    $producto = trim((string) ($detalle->cultivo_personalizado ?? $detalle->insumo?->nombre ?? ''));
    $yaExiste = $movs->contains(function ($m) use ($producto, $cantidad) {
        $nombre = strtolower((string) $m->insumo?->nombre);

        return str_contains($nombre, strtolower($producto)) && abs((float) $m->cantidad - $cantidad) < 0.01;
    });
    if ($yaExiste) {
        continue;
    }

    $resolverInsumo = (new ReflectionClass($service))->getMethod('resolverInsumoEnAlmacen');
    $resolverInsumo->setAccessible(true);
    $insumo = $resolverInsumo->invoke($service, $almacen, $producto);

    $crearInsumo = (new ReflectionClass($service))->getMethod('crearInsumoRecepcionEnAlmacen');
    $crearInsumo->setAccessible(true);
    $textoRecepcion = (new ReflectionClass($service))->getMethod('textoRecepcionPedido');
    $textoRecepcion->setAccessible(true);

    if ($insumo === null) {
        $insumo = $crearInsumo->invoke($service, $almacen, $producto, $pedido->numero_solicitud, $detalle->insumo);
    }

    AlmacenMovimiento::create([
        'almacenid' => $almacen->almacenid,
        'insumoid' => $insumo->insumoid,
        'tipo_movimiento_almacenid' => $tipoMov->tipo_movimiento_almacenid,
        'usuarioid' => $usuario->usuarioid,
        'fecha' => now()->toDateString(),
        'cantidad' => $cantidad,
        'referencia' => $ref,
        'destino_motivo' => $almacen->nombre,
        'observaciones' => '[Recepción planta — reparación — '.$ref.'] '.$producto.' · '.$textoRecepcion->invoke($service, $pedido->numero_solicitud),
    ]);
    $insumo->incrementarStock($cantidad);
    echo "Registrado: {$producto} +{$cantidad}\n";
    $registrados++;
}

echo "Reparación completada. Nuevos movimientos: {$registrados}\n";
