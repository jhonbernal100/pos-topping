<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; }
    .header { background: #856404; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
    table { width: calc(100% - 32px); margin: 0 16px; border-collapse: collapse; }
    th { background: #856404; color: #fff; padding: 8px; font-size: 10px; text-align: left; }
    td { padding: 7px 8px; font-size: 11px; border-bottom: 1px solid #eee; }
    .agotado { background: #f8d7da; }
    .bajo { background: #fff3cd; }
    .footer { margin-top:16px; text-align:center; font-size:9px; color:#aaa; padding:8px; border-top:1px solid #eee; }
</style>
</head>
<body>

<div class="header">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:70%;vertical-align:middle;">
                <h1 style="font-size:20px;color:#fff;margin-bottom:4px;">
                    {{ auth()->user()->tenant->nombre ?? 'Negocio' }}
                </h1>
                <p style="font-size:11px;opacity:.8;">
                    NIT: {{ auth()->user()->tenant->nit ?? '' }} |
                    Tel: {{ auth()->user()->tenant->telefono ?? '' }}
                </p>
            </td>
            <td style="width:30%;text-align:right;vertical-align:middle;">
                <p style="font-size:13px;font-weight:bold;color:#fff;">ALERTA DE STOCK BAJO</p>
                <p style="font-size:10px;opacity:.8;">{{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>
</div>

<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Categoria</th>
            <th style="text-align:center">Stock actual</th>
            <th style="text-align:center">Stock minimo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos as $p)
        <tr class="{{ $p->stock <= 0 ? 'agotado' : 'bajo' }}">
            <td><strong>{{ $p->nombre }}</strong><br><small>{{ $p->marca }}</small></td>
            <td>{{ $p->categoria }}</td>
            <td style="text-align:center;font-weight:bold;">{{ $p->stock }}</td>
            <td style="text-align:center;">{{ $p->stock_minimo }}</td>
            <td>{{ $p->stock <= 0 ? 'AGOTADO' : 'STOCK BAJO' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">Sistema POS Topping - Avanzas Digital</div>
</body>
</html>