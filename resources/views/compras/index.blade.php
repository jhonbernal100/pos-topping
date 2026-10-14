@extends('layouts.pos')

@section('titulo', 'Compras')

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
@endphp

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Compras</h1>
            <div style="font-size:13px;color:#888;">Facturas de proveedores de la sede {{ $sede?->nombre }}</div>
        </div>
        <a href="/compras/crear"
           style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;">
            + Registrar factura
        </a>
    </div>

    @if(session('ok'))
        <div style="padding:10px;border-radius:8px;background:#d4edda;color:#155724;font-size:14px;margin-bottom:12px;">{{ session('ok') }}</div>
    @endif

    <div style="background:#fff;border-radius:12px;padding:14px;margin-bottom:14px;max-width:260px;">
        <div style="font-size:12px;color:#888;">Compras de {{ now()->locale('es')->translatedFormat('F') }}</div>
        <div style="font-size:24px;font-weight:bold;">{{ $dinero($totalMes) }}</div>
    </div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:760px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Fecha</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Proveedor</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Factura</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Líneas</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Total</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Estado</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Registró</th>
                    <th style="padding:12px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $c)
                    <tr style="border-bottom:1px solid #eee;{{ $c->estado === 'anulada' ? 'opacity:.5;' : '' }}">
                        <td style="padding:12px;font-size:13px;white-space:nowrap;">{{ $c->fecha->format('d/m/Y') }}</td>
                        <td style="padding:12px;font-size:14px;font-weight:bold;">{{ $c->proveedor?->nombre ?? 'Sin proveedor' }}</td>
                        <td style="padding:12px;font-size:13px;color:#555;">{{ $c->numero_factura ?? '—' }}</td>
                        <td style="padding:12px;text-align:center;font-size:13px;">{{ $c->items_count }}</td>
                        <td style="padding:12px;text-align:right;font-weight:bold;">{{ $dinero($c->total) }}</td>
                        <td style="padding:12px;text-align:center;">
                            @if($c->estado === 'anulada')
                                <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:#f8d7da;color:#721c24;">Anulada</span>
                            @else
                                <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:#d4edda;color:#155724;">Registrada</span>
                            @endif
                        </td>
                        <td style="padding:12px;font-size:12px;color:#555;">{{ $c->usuario?->name ?? '—' }}</td>
                        <td style="padding:12px;text-align:center;">
                            <a href="/compras/{{ $c->id }}"
                               style="padding:6px 12px;background:#f0f0f0;color:#000;border-radius:6px;font-size:12px;text-decoration:none;">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="padding:24px;text-align:center;color:#999;">
                            Aún no hay facturas en esta sede. <a href="/compras/crear" style="color:#000;">Registra la primera</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $compras->links() }}</div>
</div>
@endsection
