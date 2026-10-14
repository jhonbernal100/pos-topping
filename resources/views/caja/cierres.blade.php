@extends('layouts.pos')

@section('titulo', 'Historial de cierres')

@section('contenido')
@php $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.'); @endphp

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Historial de cierres</h1>
            <div style="font-size:13px;color:#888;">Sede {{ $sede?->nombre }}</div>
        </div>
        <a href="/caja/cierre"
           style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;">
            Cierre de hoy
        </a>
    </div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:820px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Fecha</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Cerró</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Pedidos</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Ventas</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Efectivo esperado</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Contado</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Diferencia</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cierres as $c)
                    @php
                        [$fondo, $color, $texto] = match ($c->estadoArqueo()) {
                            'cuadra'   => ['#d4edda', '#155724', 'Cuadra'],
                            'sobrante' => ['#e7f1ff', '#0c447c', '+' . $dinero($c->diferencia)],
                            default    => ['#f8d7da', '#721c24', '-' . $dinero(abs($c->diferencia))],
                        };
                    @endphp
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;font-size:14px;white-space:nowrap;">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td style="padding:12px;font-size:13px;">{{ $c->usuario?->name ?? '—' }}</td>
                        <td style="padding:12px;text-align:center;font-size:13px;">{{ $c->num_ventas }}</td>
                        <td style="padding:12px;text-align:right;font-weight:bold;">{{ $dinero($c->total_ventas) }}</td>
                        <td style="padding:12px;text-align:right;font-size:13px;">{{ $dinero($c->efectivo_esperado) }}</td>
                        <td style="padding:12px;text-align:right;font-size:13px;">{{ $dinero($c->efectivo_contado) }}</td>
                        <td style="padding:12px;text-align:center;">
                            <span style="padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:{{ $fondo }};color:{{ $color }};">{{ $texto }}</span>
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <a href="/caja/cierre?fecha={{ $c->fecha->toDateString() }}"
                                   style="padding:6px 10px;background:#f0f0f0;color:#000;border-radius:6px;font-size:11px;text-decoration:none;">Ver</a>
                                <a href="/caja/cierres/{{ $c->id }}/imprimir" target="_blank"
                                   style="padding:6px 10px;background:#000;color:#fff;border-radius:6px;font-size:11px;text-decoration:none;">Imprimir</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:24px;text-align:center;color:#999;">
                            Aún no hay cierres en esta sede.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $cierres->links() }}</div>
</div>
@endsection
