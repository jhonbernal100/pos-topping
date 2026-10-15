@extends('reportes.pdf.layout')

@section('reporte', 'COMPRAS A PROVEEDORES')
@section('periodo', $desde->format('d/m/Y') . ' al ' . $hasta->format('d/m/Y'))

@section('contenido')
@php $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.'); @endphp

<table class="tarjetas">
    <tr>
        <td class="tarjeta"><div class="k">Facturas</div><div class="v">{{ $compras->count() }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Total compras</div><div class="v">{{ $dinero($total) }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Impuestos incluidos</div><div class="v">{{ $dinero($impuestos) }}</div></td>
    </tr>
</table>

@if($porProveedor->isNotEmpty())
    <h2>Por proveedor</h2>
    <table class="datos">
        <tr><th>Proveedor</th><th class="der">Facturas</th><th class="der">Total</th><th class="der" style="width:12%;">%</th></tr>
        @foreach($porProveedor as $nombre => $p)
            <tr>
                <td>{{ $nombre }}</td>
                <td class="der">{{ $p['facturas'] }}</td>
                <td class="der">{{ $dinero($p['total']) }}</td>
                <td class="der">{{ $total > 0 ? number_format($p['total'] / $total * 100, 1, ',', '.') : 0 }}%</td>
            </tr>
        @endforeach
    </table>
@endif

<h2>Facturas</h2>
<table class="datos">
    <tr><th>Fecha</th><th>Proveedor</th><th>Factura</th>@if($variasSedes)<th>Sede</th>@endif<th class="cen">Líneas</th><th class="der">Impuestos</th><th class="der">Total</th></tr>
    @forelse($compras as $c)
        <tr>
            <td>{{ $c->fecha->format('d/m/Y') }}</td>
            <td>{{ $c->proveedor?->nombre ?? 'Sin proveedor' }}</td>
            <td>{{ $c->numero_factura ?? '—' }}{{ $c->origen === 'ia' ? ' · IA' : '' }}</td>
            @if($variasSedes)<td>{{ $c->sede?->nombre }}</td>@endif
            <td class="cen">{{ $c->items_count }}</td>
            <td class="der">{{ $c->impuestos > 0 ? $dinero($c->impuestos) : '—' }}</td>
            <td class="der">{{ $dinero($c->total) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="cen gris">No hay compras registradas en el periodo.</td></tr>
    @endforelse
    @if($compras->isNotEmpty())
        <tr class="total"><td colspan="{{ $variasSedes ? 5 : 4 }}">Total</td><td class="der">{{ $dinero($impuestos) }}</td><td class="der">{{ $dinero($total) }}</td></tr>
    @endif
</table>
@endsection
