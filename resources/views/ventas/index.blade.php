@extends('layouts.pos')

@section('titulo', 'Ventas')

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $sedeActual = \App\Models\Sede::find(session('sede_id'));
    $puedeAnular = auth()->user()->rol === 'dueno';

    $hoy = \App\Models\Venta::where('sede_id', session('sede_id'))
        ->where('estado', 'completada')
        ->whereDate('created_at', today());
    $totalHoy   = (clone $hoy)->sum('total');
    $pedidosHoy = (clone $hoy)->count();

    $estadosCocina = [
        'pendiente'      => ['Pendiente', '#fff3cd', '#856404'],
        'en_preparacion' => ['En preparación', '#e7f1ff', '#0c447c'],
        'listo'          => ['Listo', '#d4edda', '#155724'],
        'entregado'      => ['Entregado', '#f0f0f0', '#555'],
    ];
@endphp

<div style="padding:16px;max-width:1150px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Ventas</h1>
            <div style="font-size:13px;color:#888;">Sede {{ $sedeActual?->nombre }}</div>
        </div>
        <div style="background:#fff;border-radius:12px;padding:10px 16px;text-align:right;">
            <div style="font-size:12px;color:#888;">Hoy · {{ $pedidosHoy }} pedidos</div>
            <div style="font-size:20px;font-weight:bold;">{{ $dinero($totalHoy) }}</div>
        </div>
    </div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:820px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Fecha</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Pedido</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Productos</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Pago</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Total</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Cocina</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas as $venta)
                    @php
                        $anulada = $venta->estado === 'anulada';
                        $ec = $estadosCocina[$venta->estado_preparacion] ?? null;
                    @endphp
                    <tr id="venta-{{ $venta->id }}" style="border-bottom:1px solid #eee;{{ $anulada ? 'opacity:.5;' : '' }}">
                        <td style="padding:12px;font-size:13px;white-space:nowrap;">
                            {{ $venta->created_at->format('d/m h:i a') }}
                        </td>
                        <td style="padding:12px;">
                            <strong style="font-size:14px;">{{ $venta->etiquetaPedido() }}</strong>
                            @if($venta->canal === 'domicilio')
                                <div style="font-size:11px;color:#0c447c;">Domicilio</div>
                            @endif
                        </td>
                        <td style="padding:12px;text-align:center;font-size:13px;">{{ $venta->detalles_count }}</td>
                        <td style="padding:12px;font-size:13px;">{{ $venta->resumenPago() }}</td>
                        <td style="padding:12px;text-align:right;font-weight:bold;">{{ $dinero($venta->total) }}</td>
                        <td style="padding:12px;text-align:center;">
                            @if($anulada)
                                <span style="padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:#f8d7da;color:#721c24;">Anulada</span>
                            @elseif($ec)
                                <span style="padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:{{ $ec[1] }};color:{{ $ec[2] }};">{{ $ec[0] }}</span>
                            @endif
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                <a href="/ventas/{{ $venta->id }}/ticket" target="_blank"
                                   style="padding:6px 10px;background:#000;color:#fff;border-radius:6px;font-size:11px;text-decoration:none;">Tiquete</a>
                                <a href="/cocina/{{ $venta->id }}/comanda" target="_blank"
                                   style="padding:6px 10px;background:#f0f0f0;color:#000;border-radius:6px;font-size:11px;text-decoration:none;">Comanda</a>
                                @if($puedeAnular && !$anulada)
                                    <button onclick="anular({{ $venta->id }}, @js($venta->etiquetaPedido()))"
                                        style="padding:6px 10px;background:#c00;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">Anular</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:24px;text-align:center;color:#999;">
                            Aún no hay ventas en esta sede. <a href="/ventas/crear" style="color:#000;">Registrar la primera</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">
        {{ $ventas->links() }}
    </div>
</div>

@section('scripts')
<script>
async function anular(id, etiqueta) {
    if (!confirm(`¿Anular el pedido ${etiqueta}? Esta acción no se puede deshacer.`)) return;

    const res = await fetch(`/ventas/${id}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    const data = await res.json();

    if (data.success) {
        location.reload();
    } else {
        alert(data.mensaje ?? data.message ?? 'No se pudo anular');
    }
}
</script>
@endsection
@endsection