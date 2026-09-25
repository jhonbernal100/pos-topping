<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiquete {{ $venta->etiquetaPedido() }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; width: 72mm; margin: 0 auto; padding: 4mm 0; font-size: 12px; color: #000; }
        .centro { text-align: center; }
        .fila { display: flex; justify-content: space-between; gap: 6px; }
        .linea { border-top: 1px dashed #000; margin: 6px 0; }
        .pedido { font-size: 22px; font-weight: bold; text-align: center; margin: 4px 0; word-break: break-word; }
        .mod { padding-left: 10px; font-size: 11px; }
        .no-print { margin-top: 14px; text-align: center; }
        .no-print button { padding: 8px 14px; font-size: 13px; margin: 0 4px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.'); @endphp

    <div class="centro">
        <strong style="font-size:14px;">{{ $venta->tenant->nombre }}</strong><br>
        NIT {{ $venta->tenant->nit }}<br>
        @if($venta->sede)
            Sede {{ $venta->sede->nombre }}<br>
            {{ $venta->sede->direccion }}<br>
        @endif
        {{ $venta->created_at->format('d/m/Y h:i a') }}
    </div>

    <div class="linea"></div>
    <div class="centro">Pedido de</div>
    <div class="pedido">{{ $venta->etiquetaPedido() }}</div>

    @if($venta->estado === 'anulada')
        <div class="pedido">*** ANULADA ***</div>
    @endif

    <div class="linea"></div>

    @foreach($venta->detalles as $d)
        <div class="fila" style="margin-top:4px;">
            <span>{{ $d->cantidad }} x {{ $d->nombreCompleto() }}</span>
            <span>{{ $dinero($d->subtotal) }}</span>
        </div>
        @foreach($d->modificadores as $m)
            <div class="mod fila">
                <span>{{ $m->cantidad > 1 ? $m->cantidad . ' x ' : '' }}{{ $m->nombre }}</span>
                <span>{{ $m->esAdicional() ? '+' . $dinero($m->precio_unitario * $m->cantidad) : '' }}</span>
            </div>
        @endforeach
        @if($d->notas)
            <div class="mod">Nota: {{ $d->notas }}</div>
        @endif
    @endforeach

    <div class="linea"></div>

    <div class="fila" style="font-size:15px;font-weight:bold;">
        <span>TOTAL</span>
        <span>{{ $dinero($venta->total) }}</span>
    </div>

    @foreach($venta->pagos as $p)
        <div class="fila">
            <span>{{ $p->nombreMetodo() }}{{ $p->referencia ? ' (' . $p->referencia . ')' : '' }}</span>
            <span>{{ $dinero($p->monto) }}</span>
        </div>
    @endforeach

    @if($venta->cambio > 0)
        <div class="fila"><span>Recibido</span><span>{{ $dinero($venta->monto_pagado) }}</span></div>
        <div class="fila"><span>Cambio</span><span>{{ $dinero($venta->cambio) }}</span></div>
    @endif

    <div class="linea"></div>
    <div class="centro">
        Gracias por tu compra<br>
        Te llamaremos por tu nombre<br><br>
        <span style="font-size:10px;">POS Topping · avanzas.digital</span>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>