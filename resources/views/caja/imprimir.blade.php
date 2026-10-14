<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre {{ $cierre->fecha->format('d/m/Y') }} · {{ $cierre->sede?->nombre }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; width: 72mm; margin: 0 auto; padding: 4mm 0; font-size: 12px; color: #000; }
        .centro { text-align: center; }
        .fila { display: flex; justify-content: space-between; gap: 6px; }
        .linea { border-top: 1px dashed #000; margin: 6px 0; }
        .grande { font-size: 15px; font-weight: bold; }
        .no-print { margin-top: 14px; text-align: center; }
        .no-print button { padding: 8px 14px; font-size: 13px; margin: 0 4px; cursor: pointer; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    @php
        $dinero  = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
        $detalle = $cierre->detalle ?? [];
    @endphp

    <div class="centro">
        <strong style="font-size:14px;">{{ $cierre->tenant?->nombre }}</strong><br>
        Sede {{ $cierre->sede?->nombre }}<br>
        <span class="grande">CIERRE DE CAJA</span><br>
        {{ $cierre->fecha->format('d/m/Y') }}
        @if(!empty($detalle['primera']))
            <br>{{ $detalle['primera'] }} a {{ $detalle['ultima'] }}
        @endif
    </div>

    <div class="linea"></div>

    <div class="fila"><span>Pedidos</span><span>{{ $cierre->num_ventas }}</span></div>
    <div class="fila grande"><span>VENTAS</span><span>{{ $dinero($cierre->total_ventas) }}</span></div>
    @if($cierre->num_anuladas)
        <div class="fila"><span>Anuladas ({{ $cierre->num_anuladas }})</span><span>{{ $dinero($cierre->total_anuladas) }}</span></div>
    @endif

    <div class="linea"></div>
    <div><strong>Medios de pago</strong></div>
    @foreach($detalle['metodos'] ?? [] as $m)
        <div class="fila"><span>{{ $m['nombre'] }} ({{ $m['n'] }})</span><span>{{ $dinero($m['total']) }}</span></div>
    @endforeach

    <div class="linea"></div>
    <div><strong>Arqueo de efectivo</strong></div>
    <div class="fila"><span>Base inicial</span><span>{{ $dinero($cierre->base_inicial) }}</span></div>
    <div class="fila"><span>+ Ventas efectivo</span><span>{{ $dinero($cierre->efectivo_ventas) }}</span></div>
    <div class="fila"><span>= Esperado</span><span>{{ $dinero($cierre->efectivo_esperado) }}</span></div>
    <div class="fila"><span>Contado</span><span>{{ $dinero($cierre->efectivo_contado) }}</span></div>
    <div class="fila grande">
        <span>
            @if($cierre->diferencia === 0) CUADRA
            @elseif($cierre->diferencia > 0) SOBRANTE
            @else FALTANTE
            @endif
        </span>
        <span>{{ $cierre->diferencia === 0 ? '$0' : $dinero(abs($cierre->diferencia)) }}</span>
    </div>

    @if(!empty($detalle['productos']))
        <div class="linea"></div>
        <div><strong>Más vendidos</strong></div>
        @foreach($detalle['productos'] as $p)
            <div class="fila"><span>{{ $p['cantidad'] }} x {{ $p['nombre'] }}</span><span>{{ $dinero($p['total']) }}</span></div>
        @endforeach
    @endif

    @if($cierre->notas)
        <div class="linea"></div>
        <div>Notas: {{ $cierre->notas }}</div>
    @endif

    <div class="linea"></div>
    <div class="centro">
        Cerró: {{ $cierre->usuario?->name ?? '—' }}<br>
        {{ $cierre->updated_at->format('d/m/Y h:i a') }}<br><br>
        Firma: ______________________<br><br>
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
