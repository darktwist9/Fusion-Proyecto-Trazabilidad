<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Renovando sesión | AgroFusion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            var destino = document.referrer || @json(url('/'));
            try {
                window.location.replace(destino);
            } catch (e) {
                window.location.href = destino;
            }
        })();
    </script>
    <meta http-equiv="refresh" content="0;url={{ url('/') }}">
</head>
<body>
    <p style="font-family:system-ui,sans-serif;text-align:center;padding:2rem;color:#64748b;">
        Renovando la sesión… Si no avanza, <a href="{{ url('/') }}">continúe aquí</a>.
    </p>
</body>
</html>
