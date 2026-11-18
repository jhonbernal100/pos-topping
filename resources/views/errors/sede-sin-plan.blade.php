<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sede sin plan activo</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 16px; }
        .card { background: #fff; border-radius: 12px; padding: 36px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .icon { font-size: 56px; margin-bottom: 16px; }
        h1 { font-size: 22px; margin-bottom: 12px; }
        p { color: #555; margin-bottom: 20px; line-height: 1.6; }
        form button { padding: 12px 24px; background: #000; color: #fff; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🏪</div>
        <h1>{{ $sede->nombre ?? 'Esta sede' }} no está incluida en el plan</h1>
        <p>
            El plan contratado por <strong>{{ $tenant->nombre }}</strong> no incluye esta sede.
            Para volver a usarla, el propietario debe agregarla al plan con Avanzas Digital.
            La información de la sede se conserva.
        </p>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit">Cerrar sesión</button>
        </form>
    </div>
</body>
</html>
