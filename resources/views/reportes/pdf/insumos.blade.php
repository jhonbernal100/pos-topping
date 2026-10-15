@extends('reportes.pdf.layout')

@section('reporte', $soloAlertas ? 'INSUMOS AGOTADOS O BAJOS' : 'INVENTARIO DE INSUMOS')
@section('periodo', 'Corte: ' . $generado->format('d/m/Y h:i a'))

@section('contenido')
@php
    $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.');
    $cant = fn ($c, $u) => \App\Models\Insumo::formatear((float) $c, $u);
    $variasSedes = $sedes->count() > 1;
@endphp

<table class="tarjetas">
    <tr>
        <td class="tarjeta"><div class="k">Insumos</div><div class="v">{{ $insumos->count() }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Agotados o bajos</div><div class="v {{ $alertas ? 'rojo' : 'verde' }}">{{ $alertas }}</div></td><td class="sep"></td>
        <td class="tarjeta"><div class="k">Valor del inventario</div><div class="v">{{ $dinero($valorTotal) }}</div></td>
    </tr>
</table>

@forelse($insumos->groupBy('categoria') as $categoria => $grupo)
    <h2>{{ $categoria }}</h2>
    <table class="datos">
        <tr>
            <th>Insumo</th>
            @if($variasSedes)
                @foreach($sedes as $s)<th class="der">{{ $s->nombre }}</th>@endforeach
                <th class="der">Total</th>
            @else
                <th class="der">Existencia</th>
                <th class="der">Mínimo</th>
            @endif
            <th class="der">Costo</th>
            <th class="der">Valor</th>
            <th class="cen">Estado</th>
        </tr>
        @foreach($grupo as $i)
            <tr>
                <td>{{ $i->nombre }}</td>
                @if($variasSedes)
                    @foreach($sedes as $s)
                        @php $st = $i->porSede[$s->id]; @endphp
                        <td class="der {{ $st && in_array($st->estado(), ['agotado', 'bajo']) ? 'rojo' : '' }}">{{ $st ? $cant($st->cantidad, $i->unidad) : '—' }}</td>
                    @endforeach
                    <td class="der"><strong>{{ $i->contado ? $cant($i->cantidad, $i->unidad) : '—' }}</strong></td>
                @else
                    @php $st = $i->porSede->first(); @endphp
                    <td class="der {{ $i->alerta ? 'rojo' : '' }}">{{ $st ? $cant($st->cantidad, $i->unidad) : '—' }}</td>
                    <td class="der gris">{{ $st && $st->stock_minimo > 0 ? $cant($st->stock_minimo, $i->unidad) : '—' }}</td>
                @endif
                <td class="der gris">
                    @if($i->costo <= 0) —
                    @elseif($i->unidad === 'und') {{ $dinero($i->costo) }}/und
                    @else {{ $dinero($i->costo * 1000) }}/{{ $i->unidad === 'g' ? 'kg' : 'L' }}
                    @endif
                </td>
                <td class="der">{{ $i->valor > 0 ? $dinero($i->valor) : '—' }}</td>
                <td class="cen {{ $i->alerta ? 'rojo' : ($i->contado ? 'verde' : 'gris') }}">{{ $i->alerta ? 'Revisar' : ($i->contado ? 'OK' : 'Sin conteo') }}</td>
            </tr>
        @endforeach
    </table>
@empty
    <p class="gris" style="margin-top:14px;">{{ $soloAlertas ? 'No hay insumos agotados ni bajos.' : 'Aún no hay insumos registrados.' }}</p>
@endforelse
@endsection
