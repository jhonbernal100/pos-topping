<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; }
    .header { background: #000; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
    .info-producto { margin: 0 16px 16px; background:#f5f5f5; border-radius:6px; padding:12px; }
    .resumen { display:flex; gap:12px; margin: 0 16px 16px; }
    .card { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px; text-align:center; }
    .card .valor { font-size:14px; font-weight:bold; margin-top:4px; }
    .card .label { font-size:9px; color:#888; }
    table { width:calc(100% - 32px); margin:0 16px; border-collapse:collapse; }
    th { background:#000; color:#fff; padding:7px; font-size:10px; text-align:center; }
    th:first-child { text-align:left; }
    td { padding:6px 8px; font-size:10px; border-bottom:1px solid #eee; text-align:center; }
    td:first-child { text-align:left; }
    .entrada { color:#155724; font-weight:bold; }
    .salida  { color:#721c24; font-weight:bold; }
    .saldo-inicial { background:#e8f4f8; font-weight:bold; }
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
                <p style="font-size:13px;font-weight:bold;color:#fff;">KARDEX DE PRODUCTO</p>
                <p style="font-size:10px;opacity:.8;">{{ now()->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>
</div>

<div class="info-producto">
    <strong style="font-size:13px;">{{ $producto->nombre }}</strong>
    @if($producto->marca) - {{ $producto->marca }} @endif
    <br>
    Categoria: {{ $producto->categoria }} | Unidad: {{ $producto->unidad }} |
    Precio costo: $ {{ number_format($producto->precio_compra, 0, ',', '.') }} |
    Precio venta: $ {{ number_format($producto->precio_venta, 0, ',', '.') }}
</div>

<div class="resumen">
    <div class="card">
        <div class="label">Stock inicial</div>
        <div class="valor">{{ $stockInicial }}</div>
    </div>
    <div class="card">
        <div class="label">Total salidas</div>
        <div class="valor" style="color:#721c24;">{{ $totalSalidas }}</div>
    </div>
    <div class="card">
        <div class="label">Stock actual</div>
        <div class="valor" style="color:#155724;">{{ $producto->stock }}</div>
    </div>
    <div class="card">
        <div class="label">Total vendido</div>
        <div class="valor">$ {{ number_format($totalIngresos, 0, ',', '.') }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Documento</th>
            <th>Entrada</th>
            <th>Salida</th>
            <th>Saldo</th>
            <th>Costo unit.</th>
            <th>Valor total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($movimientos as $m)
        <tr class="{{ $m['tipo'] === 'SALDO INICIAL' ? 'saldo-inicial' : '' }}">
            <td style="text-align:left;">{{ $m['fecha'] }}</td>
            <td>{{ $m['tipo'] }}</td>
            <td>{{ $m['documento'] }}</td>
            <td class="{{ $m['entrada'] > 0 ? 'entrada' : '' }}">
                {{ $m['entrada'] > 0 ? $m['entrada'] : '-' }}
            </td>
            <td class="{{ $m['salida'] > 0 ? 'salida' : '' }}">
                {{ $m['salida'] > 0 ? $m['salida'] : '-' }}
            </td>
            <td style="font-weight:bold;">{{ $m['saldo'] }}</td>
            <td>$ {{ number_format($m['costo_unit'], 0, ',', '.') }}</td>
            <td>$ {{ number_format($m['valor_total'], 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    Sistema POS Topping - Avanzas Digital - pos-topping.avanzas.digital
</div>
</body>
</html>