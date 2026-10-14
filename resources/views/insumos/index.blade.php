@extends('layouts.pos')

@section('titulo', 'Insumos')

@section('contenido')
@php
    $esGerente = auth()->user()->rol === 'dueno';
    $dinero    = fn ($v) => '$' . number_format((int) round($v), 0, ',', '.');
    $estados   = [
        'agotado'    => ['Agotado', '#f8d7da', '#721c24'],
        'bajo'       => ['Bajo', '#fff3cd', '#856404'],
        'ok'         => ['OK', '#d4edda', '#155724'],
        'sin_conteo' => ['Sin conteo', '#f0f0f0', '#666'],
    ];
    $categorias = $insumos->pluck('categoria')->filter()->unique()->values();
@endphp

<div style="padding:16px;max-width:1150px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Insumos</h1>
            <div style="font-size:13px;color:#888;">Existencias de la sede {{ $sede?->nombre }}</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @if($esGerente)
                <a href="/compras/crear"
                   style="padding:10px 16px;background:#fff;color:#000;border:1px solid #ddd;border-radius:8px;text-decoration:none;font-size:14px;">
                    Registrar factura
                </a>
                <a href="/insumos/crear"
                   style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;">
                    + Nuevo insumo
                </a>
            @endif
        </div>
    </div>

    @if(session('ok'))
        <div style="padding:10px;border-radius:8px;background:#d4edda;color:#155724;font-size:14px;margin-bottom:12px;">{{ session('ok') }}</div>
    @endif
    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:14px;">
        <div style="background:#fff;border-radius:12px;padding:14px;">
            <div style="font-size:12px;color:#888;">Insumos</div>
            <div style="font-size:24px;font-weight:bold;">{{ $insumos->where('activo', true)->count() }}</div>
        </div>
        <div style="background:#fff;border-radius:12px;padding:14px;">
            <div style="font-size:12px;color:#888;">Agotados o bajos</div>
            <div style="font-size:24px;font-weight:bold;color:{{ $alertas ? '#c00' : '#155724' }};">{{ $alertas }}</div>
        </div>
        @if($esGerente)
            <div style="background:#fff;border-radius:12px;padding:14px;">
                <div style="font-size:12px;color:#888;">Valor del inventario</div>
                <div style="font-size:24px;font-weight:bold;">{{ $dinero($valor) }}</div>
            </div>
        @endif
    </div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
        <input id="buscar" placeholder="Buscar insumo..." oninput="filtrar()" aria-label="Buscar insumo"
               style="flex:1;min-width:200px;padding:10px;border:2px solid #ddd;border-radius:8px;font-size:14px;">
        <select id="filtro-cat" onchange="filtrar()" aria-label="Categoría"
                style="padding:10px;border:2px solid #ddd;border-radius:8px;font-size:14px;">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $c)
                <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
        </select>
        <label style="display:flex;align-items:center;gap:6px;font-size:14px;background:#fff;padding:0 12px;border-radius:8px;">
            <input type="checkbox" id="solo-alertas" onchange="filtrar()"> Solo alertas
        </label>
    </div>

    <div style="background:#fff;border-radius:12px;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:820px;">
            <thead>
                <tr style="background:#000;color:#fff;">
                    <th style="padding:12px;text-align:left;font-size:13px;">Insumo</th>
                    <th style="padding:12px;text-align:left;font-size:13px;">Categoría</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Existencia</th>
                    <th style="padding:12px;text-align:right;font-size:13px;">Mínimo</th>
                    <th style="padding:12px;text-align:center;font-size:13px;">Estado</th>
                    @if($esGerente)
                        <th style="padding:12px;text-align:right;font-size:13px;">Costo</th>
                    @endif
                    <th style="padding:12px;text-align:center;font-size:13px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($insumos as $i)
                    @php [$txtEstado, $fondo, $color] = $estados[$i->estado]; @endphp
                    <tr class="fila" data-nombre="{{ mb_strtolower($i->nombre) }}" data-cat="{{ $i->categoria }}"
                        data-alerta="{{ in_array($i->estado, ['agotado', 'bajo']) ? 1 : 0 }}"
                        style="border-bottom:1px solid #eee;{{ $i->activo ? '' : 'opacity:.45;' }}">
                        <td style="padding:12px;font-weight:bold;font-size:14px;">{{ $i->nombre }}</td>
                        <td style="padding:12px;font-size:13px;color:#555;">{{ $i->categoria ?? '—' }}</td>
                        <td style="padding:12px;text-align:right;font-size:14px;" id="stock-{{ $i->id }}">
                            {{ $i->con_conteo ? \App\Models\Insumo::formatear($i->stock_actual, $i->unidad) : '—' }}
                        </td>
                        <td style="padding:12px;text-align:right;font-size:13px;color:#555;">
                            {{ $i->stock_min > 0 ? \App\Models\Insumo::formatear($i->stock_min, $i->unidad) : '—' }}
                        </td>
                        <td style="padding:12px;text-align:center;">
                            <span style="padding:4px 10px;border-radius:12px;font-size:11px;font-weight:bold;background:{{ $fondo }};color:{{ $color }};">{{ $txtEstado }}</span>
                        </td>
                        @if($esGerente)
                            <td style="padding:12px;text-align:right;font-size:12px;color:#555;white-space:nowrap;">
                                @if($i->costo_unitario > 0)
                                    @if($i->unidad === 'und')
                                        {{ $dinero($i->costo_unitario) }}/und
                                    @else
                                        {{ $dinero($i->costo_unitario * 1000) }}/{{ $i->unidad === 'g' ? 'kg' : 'L' }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        <td style="padding:12px;text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                @if($esGerente)
                                    <button onclick='abrirAjuste({{ $i->id }}, @json($i->nombre), @json($i->unidad), "conteo")'
                                        style="padding:6px 10px;background:#000;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">Contar</button>
                                @endif
                                <button onclick='abrirAjuste({{ $i->id }}, @json($i->nombre), @json($i->unidad), "merma")'
                                    style="padding:6px 10px;background:#856404;color:#fff;border:none;border-radius:6px;font-size:11px;cursor:pointer;">Merma</button>
                                <a href="/insumos/{{ $i->id }}/movimientos"
                                   style="padding:6px 10px;background:#f0f0f0;color:#000;border-radius:6px;font-size:11px;text-decoration:none;">Kardex</a>
                                @if($esGerente)
                                    <a href="/insumos/{{ $i->id }}/editar"
                                       style="padding:6px 10px;background:#f0f0f0;color:#000;border-radius:6px;font-size:11px;text-decoration:none;">Editar</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:24px;text-align:center;color:#999;">
                            Aún no hay insumos.
                            @if($esGerente)
                                <a href="/insumos/crear" style="color:#000;">Crea el primero</a> o registra una factura.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Ventana de conteo / merma --}}
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div role="dialog" aria-modal="true" style="background:#fff;border-radius:14px;padding:20px;width:100%;max-width:420px;">
        <h2 id="m-titulo" style="font-size:18px;margin-bottom:4px;"></h2>
        <p id="m-ayuda" style="font-size:13px;color:#666;margin-bottom:14px;"></p>
        <label for="m-cantidad" id="m-label" style="display:block;font-size:13px;color:#555;margin-bottom:4px;"></label>
        <input id="m-cantidad" type="number" min="0" step="any" inputmode="decimal"
               style="width:100%;padding:12px;font-size:18px;border:2px solid #ddd;border-radius:8px;margin-bottom:10px;">
        <label for="m-nota" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nota (opcional)</label>
        <input id="m-nota" maxlength="191"
               style="width:100%;padding:10px;font-size:14px;border:2px solid #ddd;border-radius:8px;margin-bottom:14px;">
        <div id="m-error" style="display:none;color:#c00;font-size:13px;margin-bottom:10px;"></div>
        <div style="display:flex;gap:8px;">
            <button onclick="cerrarAjuste()" style="flex:1;padding:12px;background:#f0f0f0;border:none;border-radius:10px;cursor:pointer;">Cancelar</button>
            <button id="m-guardar" onclick="guardarAjuste()" style="flex:1;padding:12px;background:#000;color:#fff;border:none;border-radius:10px;font-weight:bold;cursor:pointer;">Guardar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const UNIDADES = { g: 'gramos', ml: 'mililitros', und: 'unidades' };
let ajuste = null;

function filtrar() {
    const q   = document.getElementById('buscar').value.trim().toLowerCase();
    const cat = document.getElementById('filtro-cat').value;
    const al  = document.getElementById('solo-alertas').checked;
    document.querySelectorAll('.fila').forEach(f => {
        const ok = (!q || f.dataset.nombre.includes(q))
                && (!cat || f.dataset.cat === cat)
                && (!al || f.dataset.alerta === '1');
        f.style.display = ok ? '' : 'none';
    });
}

function abrirAjuste(id, nombre, unidad, tipo) {
    ajuste = { id, tipo };
    const u = UNIDADES[unidad] || unidad;
    document.getElementById('m-titulo').textContent = (tipo === 'conteo' ? 'Contar: ' : 'Merma: ') + nombre;
    document.getElementById('m-ayuda').textContent = tipo === 'conteo'
        ? 'Escribe lo que hay físicamente en esta sede. El sistema ajusta la diferencia.'
        : 'Registra lo que se dañó, se venció o se botó.';
    document.getElementById('m-label').textContent = (tipo === 'conteo' ? 'Cantidad contada' : 'Cantidad perdida') + ' (' + u + ')';
    document.getElementById('m-cantidad').value = '';
    document.getElementById('m-nota').value = '';
    document.getElementById('m-error').style.display = 'none';
    document.getElementById('modal').style.display = 'flex';
    setTimeout(() => document.getElementById('m-cantidad').focus(), 50);
}

function cerrarAjuste() {
    document.getElementById('modal').style.display = 'none';
    ajuste = null;
}

async function guardarAjuste() {
    const cantidad = document.getElementById('m-cantidad').value;
    const error = document.getElementById('m-error');
    if (cantidad === '' || Number(cantidad) < 0) {
        error.textContent = 'Escribe una cantidad válida.';
        error.style.display = 'block';
        return;
    }

    const btn = document.getElementById('m-guardar');
    btn.disabled = true;

    try {
        const res = await fetch(`/insumos/${ajuste.id}/ajuste`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ tipo: ajuste.tipo, cantidad: Number(cantidad), nota: document.getElementById('m-nota').value }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.mensaje ?? data.message ?? 'No se pudo guardar');

        sessionStorage.setItem('msgInsumos', data.mensaje);
        location.reload();
    } catch (e) {
        error.textContent = e.message;
        error.style.display = 'block';
        btn.disabled = false;
    }
}

try {
    const m = sessionStorage.getItem('msgInsumos');
    if (m) {
        const box = document.getElementById('mensaje');
        box.textContent = m;
        box.style.display = 'block';
        box.style.background = '#d4edda';
        box.style.color = '#155724';
        sessionStorage.removeItem('msgInsumos');
    }
} catch (e) {}
</script>
@endsection
