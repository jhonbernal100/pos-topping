<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }
    .header { background: #000; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
    .resumen { display: flex; gap: 12px; margin-bottom: 16px; padding: 0 16px; }
    .card { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 10px; text-align: center; }
    .card .valor { font-size: 16px; font-weight: bold; margin-top: 4px; }
    .card .label { font-size: 10px; color: #888; }
    table { width: calc(100% - 32px); margin: 0 16px; border-collapse: collapse; }
    th { background: #000; color: #fff; padding: 8px; text-align: left; font-size: 10px; }
    td { padding: 6px 8px; font-size: 10px; border-bottom: 1px solid #eee; }
    tr:nth-child(even) td { background: #f9f9f9; }
    .categoria { background: #f0f0f0; font-weight: bold; }
    .stock-ok   { color: #155724; }
    .stock-bajo { color: #856404; }
    .stock-cero { color: #721c24; font-weight: bold; }
    .footer { margin-top: 16px; text-align: center; font-size: 9px; color: #aaa; padding: 8px; border-top: 1px solid #eee; }
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
                <p style="font-size:11px;opacity:.8;">
                    {{ auth()->user()->tenant->direccion ?? '' }} -
                    {{ auth()->user()->tenant->ciudad ?? '' }}
                </p>
            </td>
            <td style="width:30%;text-align:right;vertical-align:middle;">
                <p style="font-size:13px;font-weight:bold;color:#99CF8E;">REPORTE DE INVENTARIO</p>
                <p style="font-size:10px;opacity:.8;">{{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>
</div>

<div class="resumen">
    <div class="card">
        <div class="label">Total productos</div>
        <div class="valor">{{ $totalProductos }}</div>
    </div>
    <div class="card">
        <div class="label">Valor costo</div>
        <div class="valor">$ {{ number_format($totalValorCosto, 0, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="label">Valor venta</div>
        <div class="valor">$ {{ number_format($totalValorVenta, 0, ',', '.') }}</div>
    </div>
    <div class="card">
        <div class="label">Agotados</div>
        <div class="valor" style="color:#721c24">{{ $productosAgotados }}</div>
    </div>
    <div class="card">
        <div class="label">Stock bajo</div>
        <div class="valor" style="color:#856404">{{ $productosBajos }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Marca</th>
            <th>Categoria</th>
            <th>Unidad</th>
            <th style="text-align:right">P. Costo</th>
            <th style="text-align:right">P. Venta</th>
            <th style="text-align:center">Stock</th>
            <th style="text-align:right">V. Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productos->groupBy('categoria') as $categoria => $items)
        <tr>
            <td colspan="8" class="categoria">{{ $categoria }}</td>
        </tr>
        @foreach($items as $p)
        <tr>
            <td>{{ $p->nombre }}</td>
            <td>{{ $p->marca ?? '-' }}</td>
            <td>{{ $p->categoria }}</td>
            <td>{{ $p->unidad }}</td>
            <td style="text-align:right">$ {{ number_format($p->precio_compra, 0, ',', '.') }}</td>
            <td style="text-align:right">$ {{ number_format($p->precio_venta, 0, ',', '.') }}</td>
            <td style="text-align:center" class="{{ $p->stock <= 0 ? 'stock-cero' : ($p->stock <= $p->stock_minimo ? 'stock-bajo' : 'stock-ok') }}">
                {{ $p->stock }}
            </td>
            <td style="text-align:right">$ {{ number_format($p->precio_venta * $p->stock, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>

<div class="footer">
    Sistema POS Topping - Avanzas Digital - pos-topping.avanzas.digital
</div>
</body>
</html>