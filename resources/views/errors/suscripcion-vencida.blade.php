<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción vencida</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; padding: 40px; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .icon { font-size: 60px; margin-bottom: 20px; }
        h1 { font-size: 24px; margin-bottom: 12px; color: #c00; }
        p { color: #555; margin-bottom: 20px; line-height: 1.6; }
        .tenant-nombre { font-weight: bold; color: #000; }
        .btn { display: inline-block; padding: 14px 28px; background: #000; color: #fff; text-decoration: none; border-radius: 8px; font-size: 16px; }
        .footer { margin-top: 30px; font-size: 12px; color: #aaa; }
        .footer a { color: #000; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>Suscripción vencida</h1>
        <p>
            <span class="tenant-nombre">{{ $tenant->nombre }}</span>,
            tu período de acceso ha vencido.
            Para continuar usando el sistema contacta a tu proveedor.
        </p>
        <a href="https://www.avanzas.digital/index.html" target="_blank" class="btn">
            Contactar Avanzas Digital
        </a>
        <div class="footer">
            Sistema POS Topping desarrollado por
            <a href="https://www.avanzas.digital/index.html" target="_blank">Avanzas Digital</a>
        </div>
    </div>
</body>
</html>