@extends('layouts.pos')

@section('titulo', 'Por cobrar')

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $card   = 'background:#fff;border-radius:12px;padding:16px;';
    $campo  = 'padding:10px;font-size:14px;border:2px solid #ddd;border-radius:8px;font-family:inherit;';
    $pendiente = $estado === 'pendiente';
@endphp
<div style="padding:16px;max-width:1200px;margin:0 auto;">
    <h1 style="font-size:22px;margin-bottom:4px;">Por cobrar</h1>
    <div style="font-size:13px;color:#666;margin-bottom:16px;">
        Pedidos de convenios como Rappi que se pagan después. Cuando llegue la consignación, selecciona los pedidos y márcalos como cobrados.
    </div>

    @if(session('ok'))
        <div style="padding:12px;border-radius:8px;margin-bottom:12px;background:#d4edda;color:#155724;font-size:14px;">{{ session('ok') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div style="padding:12px;border-radius:8px;margin-bottom:12px;background:#f8d7da;color:#721c24;font-size:14px;">
            {{ session('error') ?? $errors->first() }}
        </div>
    @endif

    {{-- Resumen de lo pendiente --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:16px;">
        @forelse($resumen as $r)
            <div style="{{ $card }}">
                <div style="font-size:13px;color:#666;">{{ $r['nombre'] }} · {{ $r['pedidos'] }} {{ $r['pedidos'] === 1 ? 'pedido' : 'pedidos' }}</div>
                <div style="font-size:24px;font-weight:bold;margin-top:4px;">{{ $dinero($r['total']) }}</div>
                @if($r['vencido'] > 0)
                    <div style="font-size:13px;color:#c00;margin-top:2px;">Vencido: {{ $dinero($r['vencido']) }}</div>
                @else
                    <div style="font-size:13px;color:#155724;margin-top:2px;">Nada vencido</div>
                @endif
            </div>
        @empty
            <div style="{{ $card }}color:#155724;font-size:14px;">No hay nada pendiente por cobrar.</div>
        @endforelse
    </div>

    {{-- Filtros --}}
    <form method="GET" action="/cobros" style="{{ $card }}display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
        <div>
            <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Estado</label>
            <select name="estado" style="{{ $campo }}">
                <option value="pendiente" {{ $pendiente ? 'selected' : '' }}>Pendientes</option>
                <option value="cobrado" {{ !$pendiente ? 'selected' : '' }}>Cobrados</option>
            </select>
        </div>
        @if($clientes->count() > 1)
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Cliente</label>
                <select name="cliente" style="{{ $campo }}">
                    <option value="">Todos</option>
                    @foreach($clientes as $c)
                        <option value="{{ $c->id }}" {{ $clienteId === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if($variasSedes)
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Sede</label>
                <select name="sede" style="{{ $campo }}">
                    <option value="">Todas</option>
                    @foreach($sedes as $s)
                        <option value="{{ $s->id }}" {{ $sedeId === $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Desde</label>
            <input type="date" name="desde" value="{{ $desde?->format('Y-m-d') }}" style="{{ $campo }}">
        </div>
        <div>
            <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Hasta</label>
            <input type="date" name="hasta" value="{{ $hasta?->format('Y-m-d') }}" style="{{ $campo }}">
        </div>
        <button type="submit" style="padding:11px 18px;background:#000;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Filtrar</button>
    </form>

    <form method="POST" action="/cobros/marcar" id="form-cobro">
        @csrf
        <div style="background:#fff;border-radius:12px;overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;min-width:720px;">
                <thead>
                    <tr style="background:#000;color:#fff;">
                        @if($pendiente)
                            <th style="padding:12px;width:36px;"><input type="checkbox" id="todos" aria-label="Seleccionar todos"></th>
                        @endif
                        <th style="padding:12px;text-align:left;font-size:13px;">Fecha</th>
                        <th style="padding:12px;text-align:left;font-size:13px;">Cliente</th>
                        <th style="padding:12px;text-align:left;font-size:13px;">Código pedido</th>
                        @if($variasSedes)
                            <th style="padding:12px;text-align:left;font-size:13px;">Sede</th>
                        @endif
                        <th style="padding:12px;text-align:right;font-size:13px;">Valor</th>
                        <th style="padding:12px;text-align:left;font-size:13px;">{{ $pendiente ? 'Vence' : 'Cobrado' }}</th>
                        <th style="padding:12px;text-align:center;font-size:13px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventas as $v)
                        @php $vencido = $pendiente && $v->vence_cobro && $v->vence_cobro->lt($hoy); @endphp
                        <tr style="border-bottom:1px solid #eee;">
                            @if($pendiente)
                                <td style="padding:12px;text-align:center;">
                                    <input type="checkbox" name="ventas[]" value="{{ $v->id }}" data-total="{{ $v->total }}" class="sel" aria-label="Seleccionar pedido {{ $v->codigo_externo }}">
                                </td>
                            @endif
                            <td style="padding:12px;font-size:13px;white-space:nowrap;">{{ $v->created_at->format('d/m/Y h:i a') }}</td>
                            <td style="padding:12px;font-size:13px;">{{ $v->cliente?->nombre }}</td>
                            <td style="padding:12px;font-size:14px;font-weight:bold;">{{ $v->codigo_externo ?? '—' }}</td>
                            @if($variasSedes)
                                <td style="padding:12px;font-size:13px;">{{ $v->sede?->nombre }}</td>
                            @endif
                            <td style="padding:12px;text-align:right;font-weight:bold;">{{ $dinero($v->total) }}</td>
                            <td style="padding:12px;font-size:13px;{{ $vencido ? 'color:#c00;font-weight:bold;' : '' }}">
                                @if($pendiente)
                                    {{ $v->vence_cobro?->format('d/m/Y') ?? '—' }}{{ $vencido ? ' · vencido' : '' }}
                                @else
                                    {{ $v->cobrado_at?->format('d/m/Y') }}
                                    @if($v->referencia_cobro)<div style="font-size:11px;color:#888;">{{ $v->referencia_cobro }}</div>@endif
                                @endif
                            </td>
                            <td style="padding:12px;text-align:center;white-space:nowrap;">
                                <a href="/ventas/{{ $v->id }}/ticket" target="_blank"
                                   style="padding:6px 10px;background:#000;color:#fff;border-radius:6px;font-size:11px;text-decoration:none;">Tiquete</a>
                                @if(!$pendiente)
                                    <button type="submit" form="deshacer-{{ $v->id }}"
                                            style="padding:6px 10px;background:#f2f2f2;border:none;border-radius:6px;font-size:11px;cursor:pointer;">Deshacer</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:24px;text-align:center;color:#999;">
                                {{ $pendiente ? 'No hay pedidos pendientes con este filtro.' : 'No hay pedidos cobrados con este filtro.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pendiente && $ventas->isNotEmpty())
            <div style="{{ $card }}margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div style="flex:1;min-width:200px;">
                    <div style="font-size:13px;color:#666;">Seleccionados</div>
                    <div style="font-size:22px;font-weight:bold;" id="seleccion">0 pedidos · $0</div>
                    <div style="font-size:12px;color:#888;">Compáralo con el valor que consignó la plataforma.</div>
                </div>
                <div>
                    <label for="fecha" style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Fecha del pago</label>
                    <input type="date" id="fecha" name="fecha" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" style="{{ $campo }}">
                </div>
                <div>
                    <label for="referencia" style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Referencia (opcional)</label>
                    <input type="text" id="referencia" name="referencia" maxlength="60" placeholder="Ej: consignación Bancolombia" style="{{ $campo }}">
                </div>
                <button type="submit" id="btn-marcar" disabled
                        style="padding:12px 20px;background:#000;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:bold;cursor:pointer;">
                    Marcar como cobrados
                </button>
            </div>
        @endif
    </form>

    @if(!$pendiente)
        @foreach($ventas as $v)
            <form method="POST" action="/cobros/{{ $v->id }}/deshacer" id="deshacer-{{ $v->id }}" style="display:none;"
                  onsubmit="return confirm('¿Volver el pedido {{ $v->codigo_externo }} a pendiente?')">@csrf</form>
        @endforeach
    @endif
</div>

@if($pendiente)
<script>
(function () {
    const casillas = document.querySelectorAll('.sel');
    const todos    = document.getElementById('todos');
    const btn      = document.getElementById('btn-marcar');
    const texto    = document.getElementById('seleccion');
    if (!btn) return;

    function actualizar() {
        const sel   = [...casillas].filter(c => c.checked);
        const total = sel.reduce((s, c) => s + parseInt(c.dataset.total, 10), 0);
        texto.textContent = `${sel.length} ${sel.length === 1 ? 'pedido' : 'pedidos'} · $${total.toLocaleString('es-CO')}`;
        btn.disabled = sel.length === 0;
        btn.style.opacity = sel.length ? '1' : '.5';
    }

    casillas.forEach(c => c.addEventListener('change', actualizar));
    if (todos) todos.addEventListener('change', () => { casillas.forEach(c => c.checked = todos.checked); actualizar(); });
    document.getElementById('form-cobro').addEventListener('submit', e => {
        const n = [...casillas].filter(c => c.checked).length;
        if (!n || !confirm(`¿Marcar ${n} ${n === 1 ? 'pedido' : 'pedidos'} como cobrados?`)) e.preventDefault();
    });
    actualizar();
})();
</script>
@endif
@endsection
