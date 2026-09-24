<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; color:#000; }
    .header { background:#000; color:#fff; padding:12px 16px; margin-bottom:12px; }
    .seccion { margin: 0 16px 14px; }
    .seccion-titulo {
        font-size:12px; font-weight:bold;
        background:#f0f0f0; padding:6px 8px;
        border-left:4px solid #000;
        margin-bottom:6px;
    }
    table { width:100%; border-collapse:collapse; }
    td { padding:5px 8px; font-size:10px; border-bottom:1px solid #eee; }
    .td-label { width:60%; }
    .td-valor { width:40%; text-align:right; font-weight:bold; }
    .positivo { color:#155724; }
    .negativo { color:#721c24; }
    .neutro   { color:#856404; }
    .total-row td { background:#000; color:#fff; font-weight:bold; padding:7px 8px; }
    .subtotal-row td { background:#f5f5f5; font-weight:bold; }
    .resumen-grid {
        display:flex; gap:10px; margin: 0 16px 14px;
    }
    .card-fin {
        flex:1; border:1px solid #ddd; border-radius:6px;
        padding:8px; text-align:center;
    }
    .card-fin .label { font-size:9px; color:#888; }
    .card-fin .valor { font-size:13px; font-weight:bold; margin-top:3px; }
    .footer { margin-top:12px; text-align:center; font-size:9px; color:#aaa; padding:8px; border-top:1px solid #eee; }
</style>
</head>
<body>

<div class="header">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:70%;vertical-align:middle;">
                <h1 style="font-size:18px;color:#fff;margin-bottom:4px;">
                    {{ auth()->user()->tenant->nombre ?? 'Negocio' }}
                </h1>
                <p style="font-size:11px;opacity:.8;">
                    NIT: {{ auth()->user()->tenant->nit ?? '' }} |
                    Tel: {{ auth()->user()->tenant->telefono ?? '' }}
                </p>
            </td>
            <td style="width:30%;text-align:right;vertical-align:middle;">
                <p style="font-size:13px;font-weight:bold;color:#fff;">ESTADO FINANCIERO</p>
                <p style="font-size:10px;opacity:.8;">{{ $inicio->locale('es')->isoFormat('MMMM YYYY') }}</p>
                <p style="font-size:9px;opacity:.6;">Generado: {{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>
</div>

{{-- Resumen ejecutivo --}}
<div class="resumen-grid">
    <div class="card-fin" style="border-left:3px solid #155724;">
        <div class="label">Ingresos totales</div>
        <div class="valor positivo">$ {{ number_format($totalIngresos, 0, ',', '.') }}</div>
    </div>
    <div class="card-fin" style="border-left:3px solid #721c24;">
        <div class="label">Costo de ventas</div>
        <div class="valor negativo">$ {{ number_format($costoVentas, 0, ',', '.') }}</div>
    </div>
    <div class="card-fin" style="border-left:3px solid #721c24;">
        <div class="label">Gastos operac.</div>
        <div class="valor negativo">$ {{ number_format($totalGastos, 0, ',', '.') }}</div>
    </div>
    <div class="card-fin" style="border-left:3px solid {{ $utilidadOperacional >= 0 ? '#155724' : '#721c24' }};">
        <div class="label">Utilidad neta</div>
        <div class="valor {{ $utilidadOperacional >= 0 ? 'positivo' : 'negativo' }}">
            $ {{ number_format($utilidadOperacional, 0, ',', '.') }}
        </div>
    </div>
    <div class="card-fin" style="border-left:3px solid #856404;">
        <div class="label">Cuentas x cobrar</div>
        <div class="valor neutro">$ {{ number_format($totalCuentasPorCobrar, 0, ',', '.') }}</div>
    </div>
</div>

{{-- Estado de resultados --}}
<div class="seccion">
    <div class="seccion-titulo">ESTADO DE RESULTADOS (P&G)</div>
    <table>
        <tr>
            <td class="td-label" style="font-weight:bold;background:#f9f9f9;">INGRESOS OPERACIONALES</td>
            <td class="td-valor" style="background:#f9f9f9;"></td>
        </tr>
        <tr>
            <td class="td-label" style="padding-left:16px;">Ventas en efectivo</td>
            <td class="td-valor positivo">$ {{ number_format($ingresoEfectivo, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="td-label" style="padding-left:16px;">Ventas por transferencia</td>
            <td class="td-valor positivo">$ {{ number_format($ingresoTransferencia, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="td-label" style="padding-left:16px;">Ventas a credito (devengado)</td>
            <td class="td-valor positivo">$ {{ number_format($ingresoCredito, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="td-label" style="padding-left:16px;">Abonos recibidos de cartera</td>
            <td class="td-valor positivo">$ {{ number_format($abonosRecibidos, 0, ',', '.') }}</td>
        </tr>
        <tr class="subtotal-row">
            <td class="td-label">Total ingresos</td>
            <td class="td-valor">$ {{ number_format($totalIngresos, 0, ',', '.') }}</td>
        </tr>
        <tr><td colspan="2" style="padding:4px;"></td></tr>
        <tr>
            <td class="td-label" style="font-weight:bold;background:#f9f9f9;">COSTO DE VENTAS</td>
            <td class="td-valor negativo" style="background:#f9f9f9;">
                - $ {{ number_format($costoVentas, 0, ',', '.') }}
            </td>
        </tr>
        <tr class="subtotal-row">
            <td class="td-label">UTILIDAD BRUTA</td>
            <td class="td-valor {{ $utilidadBruta >= 0 ? 'positivo' : 'negativo' }}">
                $ {{ number_format($utilidadBruta, 0, ',', '.') }}
            </td>
        </tr>
        <tr><td colspan="2" style="padding:4px;"></td></tr>
        <tr>
            <td class="td-label" style="font-weight:bold;background:#f9f9f9;">GASTOS OPERACIONALES</td>
            <td class="td-valor" style="background:#f9f9f9;"></td>
        </tr>
        @foreach($gastosPorCategoria as $cat => $monto)
        <tr>
            <td class="td-label" style="padding-left:16px;">{{ $cat }}</td>
            <td class="td-valor negativo">- $ {{ number_format($monto, 0, ',', '.') }}</td>
        </tr>
        @endforeach
        <tr class="subtotal-row">
            <td class="td-label">Total gastos</td>
            <td class="td-valor negativo">- $ {{ number_format($totalGastos, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td class="td-label">UTILIDAD OPERACIONAL NETA</td>
            <td class="td-valor">$ {{ number_format($utilidadOperacional, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

{{-- Cuentas por cobrar --}}
<div class="seccion">
    <div class="seccion-titulo">CUENTAS POR COBRAR (CARTERA)</div>
    <table>
        <tr>
            <td style="font-weight:bold;background:#f9f9f9;">Antiguedad de cartera</td>
            <td style="text-align:right;font-weight:bold;background:#f9f9f9;">Saldo</td>
            <td style="text-align:right;font-weight:bold;background:#f9f9f9;">%</td>
        </tr>
        <tr>
            <td style="padding-left:16px;">0 - 30 dias (corriente)</td>
            <td style="text-align:right;color:#155724;">$ {{ number_format($cartera030, 0, ',', '.') }}</td>
            <td style="text-align:right;">{{ $totalCuentasPorCobrar > 0 ? number_format(($cartera030/$totalCuentasPorCobrar)*100,1) : 0 }}%</td>
        </tr>
        <tr style="background:#f9f9f9;">
            <td style="padding-left:16px;">31 - 60 dias</td>
            <td style="text-align:right;color:#856404;">$ {{ number_format($cartera3160, 0, ',', '.') }}</td>
            <td style="text-align:right;">{{ $totalCuentasPorCobrar > 0 ? number_format(($cartera3160/$totalCuentasPorCobrar)*100,1) : 0 }}%</td>
        </tr>
        <tr>
            <td style="padding-left:16px;">61 - 90 dias</td>
            <td style="text-align:right;color:#EA4335;">$ {{ number_format($cartera6190, 0, ',', '.') }}</td>
            <td style="text-align:right;">{{ $totalCuentasPorCobrar > 0 ? number_format(($cartera6190/$totalCuentasPorCobrar)*100,1) : 0 }}%</td>
        </tr>
        <tr style="background:#f9f9f9;">
            <td style="padding-left:16px;">Mas de 90 dias (vencida)</td>
            <td style="text-align:right;color:#721c24;font-weight:bold;">$ {{ number_format($carteraMas90, 0, ',', '.') }}</td>
            <td style="text-align:right;">{{ $totalCuentasPorCobrar > 0 ? number_format(($carteraMas90/$totalCuentasPorCobrar)*100,1) : 0 }}%</td>
        </tr>
        <tr class="total-row">
            <td>TOTAL CUENTAS POR COBRAR</td>
            <td style="text-align:right;color:#fff;">$ {{ number_format($totalCuentasPorCobrar, 0, ',', '.') }}</td>
            <td style="text-align:right;color:#fff;">100%</td>
        </tr>
    </table>

    @if($cuentasPorCobrar->count() > 0)
    <table style="margin-top:8px;">
        <tr>
            <td style="font-weight:bold;font-size:10px;background:#f0f0f0;" colspan="3">Detalle por cliente</td>
        </tr>
        <tr style="background:#f5f5f5;">
            <td style="font-weight:bold;font-size:9px;">Cliente</td>
            <td style="font-weight:bold;font-size:9px;text-align:right;">Saldo usado</td>
            <td style="font-weight:bold;font-size:9px;text-align:right;">Cupo total</td>
        </tr>
        @foreach($cuentasPorCobrar as $c)
        <tr>
            <td style="font-size:9px;">{{ $c->cliente->nombre ?? 'N/A' }}</td>
            <td style="font-size:9px;text-align:right;color:#721c24;">$ {{ number_format($c->saldo_usado, 0, ',', '.') }}</td>
            <td style="font-size:9px;text-align:right;">$ {{ number_format($c->tope_credito, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>
    @endif
</div>

{{-- Valor inventario --}}
<div class="seccion">
    <div class="seccion-titulo">ACTIVOS — VALOR DE INVENTARIO</div>
    <table>
        <tr>
            <td class="td-label">Valor total inventario a precio de costo</td>
            <td class="td-valor positivo">$ {{ number_format($valorInventario, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="td-label" style="padding-left:16px;font-size:9px;color:#888;">
                Representa el capital invertido en mercancia disponible para la venta
            </td>
            <td class="td-valor"></td>
        </tr>
    </table>
</div>

<div class="footer">
    Sistema POS Topping - Avanzas Digital - pos-topping.avanzas.digital |
    Este reporte es de uso interno y no reemplaza estados financieros certificados por contador
</div>
</body>
</html>