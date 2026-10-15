@extends('reportes.pdf.layout')

@section('reporte', 'ESTADO DE RESULTADOS')
@section('periodo', ucfirst($desde->locale('es')->translatedFormat('F \d\e Y')))

@section('contenido')
@php
    $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.');
    $pct = fn ($v) => $ingresos > 0 ? number_format($v / $ingresos * 100, 1, ',', '.') . '%' : '—';
@endphp

<table class="tarjetas">
    <tr>
        <td class="tarjeta"><div class="k">Ventas</div><div class="v">{{ $dinero($ingresos) }}</div><div class="gris" style="font-size:8px;">{{ $pedidos }} pedidos</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Costo de insumos</div><div class="v">{{ $dinero($costoInsumos) }}</div><div class="gris" style="font-size:8px;">{{ $pct($costoInsumos) }} de las ventas</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Utilidad</div><div class="v {{ $utilidad < 0 ? 'rojo' : 'verde' }}">{{ $dinero($utilidad) }}</div><div class="gris" style="font-size:8px;">{{ $pct($utilidad) }} de las ventas</div></td>
    </tr>
</table>

<h2>Resultado del mes</h2>
<table class="datos">
    <tr><th>Concepto</th><th class="der">Valor</th><th class="der" style="width:16%;">% ventas</th></tr>
    <tr><td><strong>Ventas</strong></td><td class="der"><strong>{{ $dinero($ingresos) }}</strong></td><td class="der">100%</td></tr>
    <tr><td style="padding-left:14px;">(–) Costo de insumos vendidos</td><td class="der">{{ $dinero($costoInsumos) }}</td><td class="der">{{ $pct($costoInsumos) }}</td></tr>
    <tr class="total"><td>Utilidad bruta</td><td class="der">{{ $dinero($utilidadBruta) }}</td><td class="der">{{ $pct($utilidadBruta) }}</td></tr>
    <tr><td style="padding-left:14px;">(–) Mermas (dañado, vencido)</td><td class="der">{{ $dinero($mermas) }}</td><td class="der">{{ $pct($mermas) }}</td></tr>
    <tr><td style="padding-left:14px;">(–) Diferencias en conteos físicos</td><td class="der">{{ $dinero($ajustes) }}</td><td class="der">{{ $pct($ajustes) }}</td></tr>
    @if($incluyeGastos)
        <tr><td style="padding-left:14px;">(–) Gastos del negocio</td><td class="der">{{ $dinero($totalGastos) }}</td><td class="der">{{ $pct($totalGastos) }}</td></tr>
    @endif
    <tr class="total"><td>Utilidad {{ $incluyeGastos ? 'del mes' : 'de la sede (antes de gastos)' }}</td>
        <td class="der {{ $utilidad < 0 ? 'rojo' : 'verde' }}">{{ $dinero($utilidad) }}</td><td class="der">{{ $pct($utilidad) }}</td></tr>
</table>

@if($incluyeGastos && $gastosPorCat->isNotEmpty())
    <h2>Gastos por categoría</h2>
    <table class="datos">
        <tr><th>Categoría</th><th class="der">Valor</th></tr>
        @foreach($gastosPorCat as $cat => $valor)
            <tr><td>{{ $cat }}</td><td class="der">{{ $dinero($valor) }}</td></tr>
        @endforeach
        <tr class="total"><td>Total gastos</td><td class="der">{{ $dinero($totalGastos) }}</td></tr>
    </table>
@endif

<h2>Para tener en cuenta</h2>
<table class="datos">
    <tr><td>Compras a proveedores registradas en el mes</td><td class="der">{{ $dinero($compras) }}</td></tr>
    @if(!$incluyeGastos)
        <tr><td>Gastos del negocio en el mes (todas las sedes)</td><td class="der">{{ $dinero($totalGastos) }}</td></tr>
    @endif
</table>

<div class="nota">
    El costo de insumos sale de las recetas: cada venta descuenta lo que gasta y se valora al costo promedio de las compras.
    Las compras no se restan aquí porque se vuelven costo a medida que se venden.
    @if($ventasSinReceta > 0)
        <br><strong>{{ $dinero($ventasSinReceta) }} en ventas ({{ $pct($ventasSinReceta) }}) son de productos sin receta</strong>:
        su costo no está incluido, así que la utilidad real es menor. Completa las recetas en Menú → Recetas.
    @endif
    @if(!$incluyeGastos)
        <br>Los gastos se registran para todo el negocio; para verlos restados, genera el reporte de todas las sedes.
    @endif
</div>
@endsection
