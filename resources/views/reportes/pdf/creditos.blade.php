<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; font-size: 11px; }
    .header { background: #000; color: #fff; padding: 12px 16px; margin-bottom: 16px; }
    .resumen { display:flex; gap:8px; margin: 0 16px 16px; }
    .card { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px; text-align:center; }
    .card .valor { font-size:14px; font-weight:bold; margin-top:4px; }
    .card .label { font-size:9px; color:#888; }
    table { width:calc(100% - 32px); margin:0 16px; border-collapse:collapse; }
    th { background:#000; color:#fff; padding:8px; font-size:10px; text-align:left; }
    td { padding:7px 8px; font-size:10px; border-bottom:1px solid #eee; }
    .barra-fondo { background:#f0f0f0; border-radius:4px; height:8px; }
    .barra-relleno { border-radius:4px; height:8px; }
    .estado-activo    { color:#155724; font-weight:bold; }
    .estado-bloqueado { color:#856404; font-weight:bold; }
    .alerta  { background:#fff3cd; }
    .critico { background:#f8d7da; }
    .seccion-titulo { font-size:12px; font-weight:bold; margin: 16px 16px 8px; padding-bottom:4px; }
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
                <p style="font-size:11px;opacity:.8;">
                    {{ auth()->user()->tenant->direccion ?? '' }} -
                    {{ auth()->user()->tenant->ciudad ?? '' }}
                </p>
            </td>
            <td style="width:30%;text-align:right;vertical-align:middle;">
                <p style="font-size:13px;font-weight:bold;color:#fff;">CARTERA DE CREDITOS</p>
                <p style="font-size:10px;opacity:.8;">{{ now()->format('d/m/Y H:i') }}</p>
            </td>
        </tr>
    </table>
</div>

{{-- Resumen --}}
<div class="resumen">
    <div class="card">
        <div class="label">Clientes con saldo</div>
        <div class="valor">{{ $totalClientes }}</div>
    </div>
    <div class="card" style="border-left:3px solid #EA4335;">
        <div class="label">Total cartera</div>
        <div class="valor" style="color:#EA4335;">$ {{ number_format($totalCartera, 0, ',', '.') }}</div>
    </div>
    <div class="card" style="border-left:3px solid #155724;">
        <div class="label">Creditos activos</div>
        <div class="valor" style="color:#155724;">{{ $creditosActivos }}</div>
    </div>
    <div class="card" style="border-left:3px solid #856404;">
        <div class="label">Bloqueados</div>
        <div class="valor" style="color:#856404;">{{ $creditosBloqueados }}</div>
    </div>
    <div class="card" style="border-left:3px solid #34A853;">
        <div class="label">Cupo sin usar</div>
        <div class="valor" style="color:#34A853;">$ {{ number_format($totalDisponible, 0, ',', '.') }}</div>
    </div>
</div>

{{-- Seccion 1: Clientes con saldo pendiente --}}
<div class="seccion-titulo" style="border-bottom:2px solid #EA4335;">
    CLIENTES CON SALDO PENDIENTE ({{ $conSaldo->count() }})
</div>

<table>
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:25%;">Cliente</th>
            <th style="width:15%;">Documento</th>
            <th style="width:12%;">Telefono</th>
            <th style="width:10%;text-align:right;">Tope</th>
            <th style="width:10%;text-align:right;">Usado</th>
            <th style="width:10%;text-align:right;">Disponible</th>
            <th style="width:8%;text-align:center;">Uso %</th>
            <th style="width:8%;text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($conSaldo as $i => $credito)
        @php
            $porcentaje = $credito->tope_credito > 0
                ? round(($credito->saldo_usado / $credito->tope_credito) * 100)
                : 100;
            $esCritico = $porcentaje >= 90;
            $esAlerta  = $porcentaje >= 70 && $porcentaje < 90;
        @endphp
        <tr class="{{ $esCritico ? 'critico' : ($esAlerta ? 'alerta' : '') }}">
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $credito->cliente->nombre ?? 'N/A' }}</strong></td>
            <td>{{ $credito->cliente->tipo_documento ?? '' }} {{ $credito->cliente->numero_documento ?? '-' }}</td>
            <td>{{ $credito->cliente->telefono ?? '-' }}</td>
            <td style="text-align:right;">$ {{ number_format($credito->tope_credito, 0, ',', '.') }}</td>
            <td style="text-align:right;font-weight:bold;color:#EA4335;">
                $ {{ number_format($credito->saldo_usado, 0, ',', '.') }}
            </td>
            <td style="text-align:right;color:#155724;">
                $ {{ number_format($credito->saldoDisponible(), 0, ',', '.') }}
            </td>
            <td style="text-align:center;">
                <div class="barra-fondo">
                    <div class="barra-relleno"
                         style="width:{{ min($porcentaje, 100) }}%;background:{{ $esCritico ? '#721c24' : ($esAlerta ? '#856404' : '#EA4335') }};">
                    </div>
                </div>
                <span style="font-size:9px;">{{ $porcentaje }}%</span>
            </td>
            <td style="text-align:center;" class="estado-{{ $credito->estado }}">
                {{ strtoupper($credito->estado) }}
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center;padding:20px;color:#999;">
                No hay clientes con saldo pendiente
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Totales cartera --}}
@if($conSaldo->count() > 0)
<div style="margin:8px 16px 16px;">
    <table>
        <tr style="background:#000;">
            <td style="padding:7px 8px;font-weight:bold;color:#fff;" colspan="4">TOTAL CARTERA</td>
            <td style="padding:7px 8px;text-align:right;color:#fff;font-weight:bold;">
                $ {{ number_format($conSaldo->sum('tope_credito'), 0, ',', '.') }}
            </td>
            <td style="padding:7px 8px;text-align:right;color:#fff;font-weight:bold;">
                $ {{ number_format($totalCartera, 0, ',', '.') }}
            </td>
            <td style="padding:7px 8px;text-align:right;color:#fff;font-weight:bold;">
                $ {{ number_format($conSaldo->sum(fn($c) => $c->saldoDisponible()), 0, ',', '.') }}
            </td>
            <td colspan="2" style="padding:7px 8px;text-align:center;color:#fff;">
                {{ $conSaldo->count() }} clientes
            </td>
        </tr>
    </table>
</div>
@endif

{{-- Seccion 2: Creditos aprobados sin usar --}}
@if($sinUsar->count() > 0)
<div class="seccion-titulo" style="border-bottom:2px solid #155724;">
    CREDITOS APROBADOS SIN USAR ({{ $sinUsar->count() }} clientes)
</div>

<table>
    <thead>
        <tr>
            <th style="background:#155724;width:5%;">#</th>
            <th style="background:#155724;width:35%;">Cliente</th>
            <th style="background:#155724;width:20%;">Documento</th>
            <th style="background:#155724;width:20%;">Telefono</th>
            <th style="background:#155724;width:15%;text-align:right;">Cupo disponible</th>
            <th style="background:#155724;width:5%;text-align:center;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sinUsar as $i => $credito)
        <tr style="background:{{ $i % 2 === 0 ? '#f9fff9' : '#fff' }}">
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $credito->cliente->nombre ?? 'N/A' }}</strong></td>
            <td>{{ $credito->cliente->tipo_documento ?? '' }} {{ $credito->cliente->numero_documento ?? '-' }}</td>
            <td>{{ $credito->cliente->telefono ?? '-' }}</td>
            <td style="text-align:right;font-weight:bold;color:#155724;">
                $ {{ number_format($credito->tope_credito, 0, ',', '.') }}
            </td>
            <td style="text-align:center;color:#155724;font-weight:bold;">DISPONIBLE</td>
        </tr>
        @endforeach
        <tr style="background:#155724;">
            <td colspan="4" style="padding:7px 8px;font-weight:bold;color:#fff;">TOTAL DISPONIBLE</td>
            <td style="padding:7px 8px;text-align:right;font-weight:bold;color:#fff;">
                $ {{ number_format($totalDisponible, 0, ',', '.') }}
            </td>
            <td style="padding:7px 8px;text-align:center;color:#fff;">{{ $sinUsar->count() }}</td>
        </tr>
    </tbody>
</table>
@endif

<div style="margin:12px 16px;font-size:9px;color:#888;">
    Rojo: uso mayor al 90% del cupo | Amarillo: uso entre 70% y 90%
</div>

<div class="footer">
    Sistema POS Topping - Avanzas Digital - pos-topping.avanzas.digital
</div>
</body>
</html>