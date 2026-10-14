@extends('layouts.pos')

@section('titulo', 'Kardex ' . $insumo->nombre)

@section('contenido')
@php
    $fmt     = fn ($c) => \App\Models\Insumo::formatear((float) $c, $insumo->unidad);
    $colores = [
        'entrada'   => ['#d4edda', '#155724'],
        'venta'     => ['#f0f0f0', '#555'],
        'anulacion' => ['#e7f1ff', '#0c447c'],
        'merma'     => ['#fff3cd', '#856404'],
        'ajuste'    => ['#ede7f6', '#4527a0'],
        'traslado'  => ['#e0f7fa', '#006064'],
    ];
@endphp

<div style="padding:16px;max-width:1000px;margin:0 auto;">
    <a href="/insumos" style="font-size:13px;color:#555;text-decoration:none;">← Volver a insumos</a>
    <h1 style="font-size:22px;margin:8px 0 2px;">{{ $insumo->nombre }}</h1>
    <div style="font-size:13px;color:#888;margin-bottom:14px;">Movimientos en la sede {{ $sede?->nombre }}</div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Fecha</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Tipo</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Detalle</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Cantidad</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Saldo</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $m)
                    @php [$fondo, $color] = $colores[$m->tipo] ?? ['#f0f0f0', '#555']; @endphp
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px 12px;font-size:13px;white-space:nowrap;">{{ $m->created_at->format('d/m h:i a') }}</td>
                        <td style="padding:10px 12px;text-align:center;">
                            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:{{ $fondo }};color:{{ $color }};">
                                {{ \App\Models\MovimientoInventario::TIPOS[$m->tipo] ?? $m->tipo }}
                            </span>
                        </td>
                        <td style="padding:10px 12px;font-size:13px;color:#555;">
                            @if($m->venta)
                                Pedido {{ $m->venta->etiquetaPedido() }}
                            @elseif($m->compra_id)
                                <a href="/compras/{{ $m->compra_id }}" style="color:#000;">{{ $m->nota ?: 'Factura' }}</a>
                            @else
                                {{ $m->nota ?? '—' }}
                            @endif
                        </td>
                        <td style="padding:10px 12px;text-align:right;font-weight:bold;color:{{ $m->cantidad < 0 ? '#c00' : '#155724' }};">
                            {{ $m->cantidad > 0 ? '+' : '' }}{{ $fmt($m->cantidad) }}
                        </td>
                        <td style="padding:10px 12px;text-align:right;font-size:13px;">{{ $fmt($m->saldo) }}</td>
                        <td style="padding:10px 12px;font-size:12px;color:#555;">{{ $m->usuario?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:24px;text-align:center;color:#999;">Sin movimientos en esta sede.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $movimientos->links() }}</div>
</div>
@endsection
