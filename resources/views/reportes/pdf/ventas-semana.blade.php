<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; }
    .header { background: #34A853; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
    .resumen { display:flex; gap:8px; margin: 0 16px 12px; }
    .card { flex:1; border:1px solid #ddd; border-radius:6px; padding:8px; text-align:center; }
    .card .valor { font-size:13px; font-weight:bold; margin-top:4px; }
    .card .label { font-size:9px; color:#888; }
    table { width:100%; border-collapse:collapse; }
    th { background:#34A853; color:#fff; padding:7px; font-size:10px; text-align:left; }
    td { padding:6px 8px; font-size:10px; border-bottom:1px solid #eee; }
    .seccion-titulo { font-size:12px; font-weight:bold; margin: 16px 16px 8px; border-bottom:2px solid #34A853; padding-bottom:4px; }
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
                <p style="font-size:13px;font-weight:bold;color:#fff;">VENTAS DE LA SEMANA</p>
                <p style="font-size:10px;opacity:.8;">{{ $inicio->format('d/m/Y') }} al {{ $fin->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>
</div>

{{-- Resumen tarjetas --}}
<div class="resumen">
    <div class="card">
        <div class="label">Total ventas</div>
        <div class="valor">{{ $ventas->count() }}</div>
    </div>
    <div class="card" style="border-left:3px solid #000;">
        <div class="label">Total ingresos</div>
        <div class="valor">$ {{ number_format($totalIngresos, 0, ',', '.') }}</div>
    </div>
    <div class="card" style="border-left:3px solid #28a745;">
        <div class="label">Efectivo</div>
        <div class="valor" style="color:#155724;">$ {{ number_format($totalEfectivo, 0, ',', '.') }}</div>
    </div>
    <div class="card" style="border-left:3px solid #4285F4;">
        <div class="label">Transferencia</div>
        <div class="valor" style="color:#4285F4;">$ {{ number_format($totalTransferencia, 0, ',', '.') }}</div>
    </div>
    <div class="card" style="border-left:3px solid #EA4335;">
        <div class="label">Credito</div>
        <div class="valor" style="color:#EA4335;">$ {{ number_format($totalCredito, 0, ',', '.') }}</div>
    </div>
</div>

{{-- Tabla discriminada --}}
<div style="margin:0 16px 16px;">
    <table>
        <thead>
            <tr>
                <th style="background:#155724;">Metodo de pago</th>
                <th style="background:#155724;text-align:center;">Cantidad</th>
                <th style="background:#155724;text-align:right;">Total</th>
                <th style="background:#155724;text-align:right;">Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#f9f9f9;">
                <td style="font-weight:bold;">Efectivo</td>
                <td style="text-align:center;">{{ $ventas->where('metodo_pago', 'efectivo')->count() }}</td>
                <td style="text-align:right;color:#155724;font-weight:bold;">$ {{ number_format($totalEfectivo, 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ $totalIngresos > 0 ? number_format(($totalEfectivo / $totalIngresos) * 100, 1) : 0 }}%</td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Transferencia</td>
                <td style="text-align:center;">{{ $ventas->where('metodo_pago', 'transferencia')->count() }}</td>
                <td style="text-align:right;color:#4285F4;font-weight:bold;">$ {{ number_format($totalTransferencia, 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ $totalIngresos > 0 ? number_format(($totalTransferencia / $totalIngresos) * 100, 1) : 0 }}%</td>
            </tr>
            <tr style="background:#f9f9f9;">
                <td style="font-weight:bold;">Credito</td>
                <td style="text-align:center;">{{ $ventas->where('metodo_pago', 'credito')->count() }}</td>
                <td style="text-align:right;color:#EA4335;font-weight:bold;">$ {{ number_format($totalCredito, 0, ',', '.') }}</td>
                <td style="text-align:right;">{{ $totalIngresos > 0 ? number_format(($totalCredito / $totalIngresos) * 100, 1) : 0 }}%</td>
            </tr>
            <tr style="background:#000;">
                <td style="padding:7px 8px;font-weight:bold;color:#fff;">TOTAL</td>
                <td style="padding:7px 8px;text-align:center;color:#fff;font-weight:bold;">{{ $ventas->count() }}</td>
                <td style="padding:7px 8px;text-align:right;color:#fff;font-weight:bold;">$ {{ number_format($totalIngresos, 0, ',', '.') }}</td>
                <td style="padding:7px 8px;text-align:right;color:#fff;">100%</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- Ventas por dia --}}
<div class="seccion-titulo">Ventas por dia</div>
<div style="margin:0 16px 16px;">
    <table>
        <thead>
            <tr>
                <th>Dia</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventasPorDia as $dia => $total)
            <tr>
                <td>{{ \Carbon\Carbon::parse($dia)->locale('es')->isoFormat('dddd D/MM') }}</td>
                <td style="text-align:right;font-weight:bold;">$ {{ number_format($total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Detalle --}}
<div class="seccion-titulo">Detalle de ventas</div>
<div style="margin:0 16px;">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Pago</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventas as $venta)
            <tr>
                <td>{{ str_pad($venta->id, 6, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $venta->cliente->nombre ?? 'Consumidor final' }}</td>
                <td>{{ ucfirst($venta->metodo_pago) }}</td>
                <td style="text-align:right;font-weight:bold;">$ {{ number_format($venta->total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center;padding:20px;color:#999;">No hay ventas esta semana</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="footer">Sistema POS Topping - Avanzas Digital</div>
</body>
</html>