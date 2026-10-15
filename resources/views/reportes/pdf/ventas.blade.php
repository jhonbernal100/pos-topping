@extends('reportes.pdf.layout')

@section('reporte', 'REPORTE DE VENTAS')
@section('periodo', $unDia ? $desde->locale('es')->translatedFormat('l j \d\e F \d\e Y') : $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y'))

@section('contenido')
@php $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.'); @endphp

<table class="tarjetas">
    <tr>
        <td class="tarjeta"><div class="k">Ventas</div><div class="v">{{ $dinero($total) }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Pedidos</div><div class="v">{{ $pedidos }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Ticket promedio</div><div class="v">{{ $dinero($promedio) }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Anulados</div><div class="v {{ $anuladas->count() ? 'rojo' : '' }}">{{ $anuladas->count() }}</div>
            @if($anuladas->count())<div class="gris" style="font-size:8px;">{{ $dinero($anuladas->sum('total')) }}</div>@endif
        </td>
    </tr>
</table>

<h2>Pagos recibidos</h2>
<table class="datos">
    <tr><th>Método</th><th class="der">Valor</th><th class="der" style="width:12%;">%</th><th style="width:30%;"></th></tr>
    @forelse($porMetodo as $m)
        @php $pct = $total > 0 ? $m['total'] / $total * 100 : 0; @endphp
        <tr>
            <td>{{ $m['nombre'] }}</td>
            <td class="der">{{ $dinero($m['total']) }}</td>
            <td class="der">{{ number_format($pct, 1, ',', '.') }}%</td>
            <td><div class="barra"><div style="width:{{ min(100, round($pct)) }}%;"></div></div></td>
        </tr>
    @empty
        <tr><td colspan="4" class="cen gris">Sin ventas en el periodo.</td></tr>
    @endforelse
    @if($porMetodo->isNotEmpty())
        <tr class="total"><td>Total</td><td class="der">{{ $dinero($porMetodo->sum('total')) }}</td><td></td><td></td></tr>
    @endif
</table>

@if($variasSedes && $porSede->isNotEmpty())
    <h2>Por sede</h2>
    <table class="datos">
        <tr><th>Sede</th><th class="der">Pedidos</th><th class="der">Ventas</th><th class="der">Ticket promedio</th></tr>
        @foreach($porSede as $s)
            <tr>
                <td>{{ $s['nombre'] }}</td>
                <td class="der">{{ $s['pedidos'] }}</td>
                <td class="der">{{ $dinero($s['total']) }}</td>
                <td class="der">{{ $dinero($s['pedidos'] ? $s['total'] / $s['pedidos'] : 0) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if($porDia->isNotEmpty())
    <h2>Por día</h2>
    @php $maxDia = max(1, $porDia->max('total')); @endphp
    <table class="datos">
        <tr><th>Día</th><th class="der">Pedidos</th><th class="der">Ventas</th><th style="width:35%;"></th></tr>
        @foreach($porDia as $d)
            <tr>
                <td>{{ $d['dia']->locale('es')->translatedFormat('D d/m') }}</td>
                <td class="der">{{ $d['pedidos'] }}</td>
                <td class="der">{{ $dinero($d['total']) }}</td>
                <td><div class="barra"><div style="width:{{ round($d['total'] / $maxDia * 100) }}%;"></div></div></td>
            </tr>
        @endforeach
    </table>
@endif

@if($porHora->isNotEmpty())
    <h2>Por hora del día</h2>
    @php $maxHora = max(1, $porHora->max('total')); @endphp
    <table class="datos">
        <tr><th>Hora</th><th class="der">Pedidos</th><th class="der">Ventas</th><th style="width:40%;"></th></tr>
        @foreach($porHora as $h)
            <tr>
                <td>{{ \Carbon\Carbon::createFromTime($h['hora'])->format('g a') }} – {{ \Carbon\Carbon::createFromTime(($h['hora'] + 1) % 24)->format('g a') }}</td>
                <td class="der">{{ $h['pedidos'] }}</td>
                <td class="der">{{ $dinero($h['total']) }}</td>
                <td><div class="barra"><div style="width:{{ round($h['total'] / $maxHora * 100) }}%;"></div></div></td>
            </tr>
        @endforeach
    </table>
@endif

@if($topProductos->isNotEmpty())
    <h2>Productos más vendidos</h2>
    <table class="datos">
        <tr><th style="width:6%;">#</th><th>Producto</th><th class="der">Unidades</th><th class="der">Ventas</th></tr>
        @foreach($topProductos as $i => $p)
            <tr>
                <td class="gris">{{ $i + 1 }}</td>
                <td>{{ $p->nombre_producto }}</td>
                <td class="der">{{ (int) $p->cantidad }}</td>
                <td class="der">{{ $dinero($p->total) }}</td>
            </tr>
        @endforeach
    </table>
@endif

@if($listaPedidos->isNotEmpty())
    <h2>Pedidos del día</h2>
    <table class="datos">
        <tr><th>Hora</th><th>Pedido</th>@if($variasSedes)<th>Sede</th>@endif<th>Pago</th><th class="der">Total</th><th class="cen">Estado</th></tr>
        @foreach($listaPedidos as $v)
            <tr>
                <td>{{ $v->created_at->format('h:i a') }}</td>
                <td>{{ $v->etiquetaPedido() }}</td>
                @if($variasSedes)<td>{{ $v->sede?->nombre }}</td>@endif
                <td>{{ $v->pagos->map->nombreMetodo()->unique()->implode(' + ') ?: '—' }}</td>
                <td class="der">{{ $dinero($v->total) }}</td>
                <td class="cen {{ $v->estado === 'anulada' ? 'rojo' : '' }}">{{ $v->estado === 'anulada' ? 'Anulado' : 'OK' }}</td>
            </tr>
        @endforeach
    </table>
@endif
@endsection
