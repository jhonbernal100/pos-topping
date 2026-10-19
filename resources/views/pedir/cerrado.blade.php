<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>{{ $tenant->nombre ?? 'Pedido a domicilio' }}</title>
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: {{ $tenant->color_fondo ?? '#FFF6EE' }};
           margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
    .caja { background: #fff; border-radius: 18px; padding: 32px 24px; max-width: 420px; text-align: center; }
    h1 { font-size: 20px; color: {{ $tenant->color_secundario ?? '#4A2C2A' }}; margin: 12px 0 8px; }
    p { color: #555; line-height: 1.5; }
    a { display: inline-block; margin-top: 18px; padding: 14px 22px; border-radius: 12px; background: {{ $tenant->color_primario ?? '#E4387A' }}; color: #fff; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<div class="caja">
    <div style="font-size:52px;">🍦</div>
    <h1>{{ $tenant->nombre ?? 'Pedido a domicilio' }}</h1>
    <p>{{ str_replace('*', '', $mensaje) }}</p>
    @if($tenant?->wa_numero)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $tenant->wa_numero) }}">Ir a WhatsApp</a>
    @endif
</div>
</body>
</html>
