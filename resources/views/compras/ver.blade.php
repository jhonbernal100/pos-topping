@extends('layouts.pos')

@section('titulo', 'Factura ' . ($compra->numero_factura ?? '#' . $compra->id))

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) round($v), 0, ',', '.');
@endphp

<div style="padding:16px;max-width:950px;margin:0 auto;">
    <a href="/compras" style="font-size:13px;color:#555;text-decoration:none;">← Volver a compras</a>

    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin:8px 0 14px;">
        <div>
            <h1 style="font-size:22px;">
                Factura {{ $compra->numero_factura ?? '#' . $compra->id }}
                @if($compra->estado === 'anulada')
                    <span style="padding:3px 10px;border-radius:12px;font-size:12px;background:#f8d7da;color:#721c24;vertical-align:middle;">Anulada</span>
                @endif
            </h1>
            <div style="font-size:13px;color:#888;line-height:1.6;">
                {{ $compra->proveedor?->nombre ?? 'Sin proveedor' }} · {{ $compra->fecha->format('d/m/Y') }} · Sede {{ $compra->sede?->nombre }}<br>
                Registrada por {{ $compra->usuario?->name ?? '—' }} el {{ $compra->created_at->format('d/m/Y h:i a') }}
            </div>
        </div>
        @if($compra->estado === 'registrada')
            <button onclick="anular()" id="btn-anular"
                style="padding:10px 16px;background:#fff;color:#c00;border:1px solid #c00;border-radius:8px;font-size:14px;cursor:pointer;">
                Anular factura
            </button>
        @endif
    </div>

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:720px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">En la factura</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Insumo</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Comprado</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Entró</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Costo unitario</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compra->items as $it)
                    @php $u = $it->insumo?->unidad ?? 'und'; @endphp
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:12px;font-size:13px;color:#555;">{{ $it->descripcion }}</td>
                        <td style="padding:12px;font-size:14px;font-weight:bold;">{{ $it->insumo?->nombre ?? '—' }}</td>
                        <td style="padding:12px;text-align:right;font-size:13px;">
                            {{ rtrim(rtrim(number_format($it->cantidad_factura, 3, ',', '.'), '0'), ',') }} {{ $it->unidad_factura }}
                        </td>
                        <td style="padding:12px;text-align:right;font-size:13px;">{{ \App\Models\Insumo::formatear((float) $it->cantidad, $u) }}</td>
                        <td style="padding:12px;text-align:right;font-size:12px;color:#555;white-space:nowrap;">
                            @if($u === 'und')
                                {{ $dinero($it->costoUnitario()) }}/und
                            @else
                                {{ $dinero($it->costoUnitario() * 1000) }}/{{ $u === 'g' ? 'kg' : 'L' }}
                            @endif
                        </td>
                        <td style="padding:12px;text-align:right;font-weight:bold;">{{ $dinero($it->costo_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="5" style="padding:8px 12px;text-align:right;color:#555;">Subtotal</td><td style="padding:8px 12px;text-align:right;">{{ $dinero($compra->subtotal) }}</td></tr>
                @if($compra->impuestos > 0)
                    <tr><td colspan="5" style="padding:8px 12px;text-align:right;color:#555;">Impuestos</td><td style="padding:8px 12px;text-align:right;">{{ $dinero($compra->impuestos) }}</td></tr>
                @endif
                <tr><td colspan="5" style="padding:10px 12px;text-align:right;font-weight:bold;">Total</td><td style="padding:10px 12px;text-align:right;font-weight:bold;font-size:18px;">{{ $dinero($compra->total) }}</td></tr>
            </tfoot>
        </table>
    </div>

    @if($compra->notas)
        <div style="background:#fff;border-radius:12px;padding:14px;margin-top:12px;font-size:14px;color:#555;">
            <strong>Notas:</strong> {{ $compra->notas }}
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
async function anular() {
    if (!confirm('¿Anular esta factura? Se descontarán de la sede las cantidades que entraron.')) return;

    const btn = document.getElementById('btn-anular');
    const box = document.getElementById('mensaje');
    btn.disabled = true;

    try {
        const res = await fetch('/compras/{{ $compra->id }}/anular', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.mensaje ?? data.message ?? 'No se pudo anular');
        location.reload();
    } catch (e) {
        box.textContent = e.message;
        box.style.display = 'block';
        box.style.background = '#f8d7da';
        box.style.color = '#721c24';
        btn.disabled = false;
    }
}
</script>
@endsection
