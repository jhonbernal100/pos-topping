<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comanda {{ $venta->etiquetaPedido() }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; width: 72mm; margin: 0 auto; padding: 4mm 0; font-size: 14px; color: #000; }
        .centro { text-align: center; }
        .linea { border-top: 2px dashed #000; margin: 6px 0; }
        .pedido { font-size: 28px; font-weight: bold; text-align: center; word-break: break-word; }
        .item { font-size: 16px; font-weight: bold; margin-top: 6px; }
        .op { padding-left: 10px; font-size: 13px; }
        .nota { padding-left: 10px; font-size: 14px; font-weight: bold; }
        .no-print { margin-top: 14px; text-align: center; }
        .no-print button { padding: 8px 14px; font-size: 13px; margin: 0 4px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="centro">COMANDA · Sede {{ $venta->sede?->nombre }}</div>
    <div class="pedido">{{ $venta->etiquetaPedido() }}</div>
    <div class="centro">
        {{ $venta->created_at->format('h:i a') }}
        @if($venta->canal === 'domicilio') · DOMICILIO @endif
    </div>
    <div class="linea"></div>

    @foreach($venta->detalles as $d)
        <div class="item">{{ $d->cantidad }} x {{ $d->nombreCompleto() }}</div>
        @foreach($d->modificadores->groupBy('nombre_grupo') as $grupo => $mods)
            <div class="op">
                {{ $grupo }}:
                {{ $mods->groupBy('nombre')->map(fn ($g, $n) => ($g->sum('cantidad') > 1 ? $g->sum('cantidad') . 'x ' : '') . $n)->join(', ') }}
            </div>
        @endforeach
        @if($d->notas)
            <div class="nota">*** {{ $d->notas }} ***</div>
        @endif
    @endforeach

    <div class="linea"></div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Cerrar</button>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 300));
    </script>
</body>
</html>