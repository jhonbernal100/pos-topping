<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 26px 28px 40px; }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color:#222; }
    .encabezado { width:100%; border-collapse:collapse; background:#000; color:#fff; }
    .encabezado td { padding:12px 14px; vertical-align:middle; }
    .negocio { font-size:17px; font-weight:bold; }
    .sub { font-size:9px; opacity:.8; margin-top:2px; }
    .reporte { font-size:13px; font-weight:bold; text-align:right; }
    .tarjetas { width:100%; border-collapse:separate; border-spacing:0 6px; margin:8px 0 2px; }
    .tarjetas td + td { border-left-width:1px; }
    .tarjetas .sep { width:8px; border:none; }
    .tarjeta { border:1px solid #ddd; border-radius:6px; padding:8px; text-align:center; }
    .tarjeta .k { font-size:8.5px; color:#777; text-transform:uppercase; }
    .tarjeta .v { font-size:14px; font-weight:bold; margin-top:3px; }
    h2 { font-size:11.5px; margin:14px 0 6px; padding-bottom:3px; border-bottom:2px solid #000; }
    table.datos { width:100%; border-collapse:collapse; }
    table.datos th { background:#000; color:#fff; padding:6px; font-size:9px; text-align:left; }
    table.datos td { padding:5px 6px; font-size:9.5px; border-bottom:1px solid #eee; }
    table.datos tr.total td { font-weight:bold; border-top:2px solid #000; border-bottom:none; }
    .der, table.datos th.der, table.datos td.der { text-align:right; }
    .cen, table.datos th.cen, table.datos td.cen { text-align:center; }
    .gris { color:#888; }
    .rojo { color:#c00; }
    .verde { color:#155724; }
    .barra { background:#eee; height:7px; border-radius:3px; }
    .barra div { background:#27A88A; height:7px; border-radius:3px; }
    .nota { margin-top:10px; padding:7px 9px; background:#fff8e1; color:#6b5200; font-size:9px; border-radius:4px; }
    .pie { position:fixed; bottom:-24px; left:0; right:0; text-align:center; font-size:8px; color:#aaa; }
</style>
</head>
<body>
@php $dinero = fn ($v) => '$ ' . number_format((float) $v, 0, ',', '.'); @endphp

<table class="encabezado">
    <tr>
        <td style="width:62%;">
            <div class="negocio">{{ $tenant->nombre ?? 'Negocio' }}</div>
            <div class="sub">NIT {{ $tenant->nit ?? '' }}{{ $tenant->telefono ? ' · Tel ' . $tenant->telefono : '' }}</div>
            <div class="sub">{{ $titulo }}</div>
        </td>
        <td>
            <div class="reporte">@yield('reporte')</div>
            <div class="sub" style="text-align:right;">@yield('periodo')</div>
        </td>
    </tr>
</table>

@yield('contenido')

<div class="pie">Generado el {{ $generado->format('d/m/Y h:i a') }} · POS Topping by Avanzas Digital</div>
</body>
</html>
