@extends('layouts.pos')

@section('titulo', 'Menú')

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $nombresDias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
@endphp

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <div>
            <h1 style="font-size:22px;">Menú</h1>
            <p style="font-size:13px;color:#888;margin-top:4px;">
                Los cambios aplican a todas las sedes. Toca "Disponible" para ocultar un producto de la caja.
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="/menu/recetas"
               style="padding:10px 16px;background:#fff;color:#000;border:1px solid #ddd;border-radius:8px;text-decoration:none;font-size:14px;white-space:nowrap;">
                Recetas
            </a>
            <a href="/menu/apariencia"
               style="padding:10px 16px;background:#fff;color:#000;border:1px solid #ddd;border-radius:8px;text-decoration:none;font-size:14px;white-space:nowrap;">
                Apariencia
            </a>
            <a href="/menu/productos/crear"
               style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;white-space:nowrap;">
                + Nuevo producto
            </a>
        </div>
    </div>

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    {{-- Productos por categoría --}}
    @foreach($categorias as $categoria)
        <h2 style="font-size:17px;margin:20px 0 10px;">
            {{ $categoria->nombre }}
            <span style="font-size:12px;color:#888;font-weight:normal;">({{ $categoria->productos->count() }})</span>
        </h2>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
            @forelse($categoria->productos as $producto)
                <div id="prod-{{ $producto->id }}"
                     style="background:#fff;border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:8px;opacity:{{ $producto->activo ? '1' : '.5' }};">

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                        <div style="display:flex;gap:10px;align-items:center;">
                            @if($producto->foto)
                                <img src="{{ asset('storage/' . $producto->foto) }}" alt="" loading="lazy"
                                     style="width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0;">
                            @endif
                            <div style="font-weight:bold;font-size:15px;">{{ $producto->nombre }}</div>
                        </div>
                        <button id="btn-prod-{{ $producto->id }}" onclick="toggleProducto({{ $producto->id }})"
                            style="flex-shrink:0;padding:4px 10px;border:none;border-radius:12px;font-size:11px;font-weight:bold;cursor:pointer;
                                   background:{{ $producto->activo ? '#d4edda' : '#f8d7da' }};
                                   color:{{ $producto->activo ? '#155724' : '#721c24' }};">
                            {{ $producto->activo ? 'Disponible' : 'No disponible' }}
                        </button>
                    </div>

                    @if($producto->descripcion)
                        <div style="font-size:12px;color:#666;line-height:1.4;">{{ $producto->descripcion }}</div>
                    @endif

                    {{-- Precio o variantes --}}
                    @if($producto->variantes->isNotEmpty())
                        <div style="font-size:13px;">
                            @foreach($producto->variantes as $v)
                                <div style="display:flex;justify-content:space-between;{{ $v->activo ? '' : 'text-decoration:line-through;color:#aaa;' }}">
                                    <span>{{ $v->nombre }}</span>
                                    <strong>{{ $dinero($v->precio) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font-size:16px;font-weight:bold;">{{ $dinero($producto->precio_venta) }}</div>
                    @endif

                    {{-- Reglas --}}
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        @if(!empty($producto->dias_disponibles))
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#fff3cd;color:#856404;">
                                Solo {{ collect($producto->dias_disponibles)->map(fn ($d) => $nombresDias[$d] ?? $d)->join(', ') }}
                            </span>
                        @endif
                        @if(!$producto->disponible_domicilio)
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#f0f0f0;color:#555;">
                                Solo en el local
                            </span>
                        @endif
                        @if(!$producto->disponible_local)
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#f0f0f0;color:#555;">
                                Solo a domicilio
                            </span>
                        @endif
                        @if($producto->tipo_menu === 'reventa')
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#e7f1ff;color:#0c447c;">
                                Reventa · stock {{ $producto->stock }}
                            </span>
                        @endif
                    </div>

                    {{-- Grupos de opciones --}}
                    @if($producto->gruposModificadores->isNotEmpty())
                        <div style="font-size:12px;color:#555;border-top:1px solid #f0f0f0;padding-top:8px;line-height:1.6;">
                            @foreach($producto->gruposModificadores as $grupo)
                                @php
                                    $inc   = (int) $grupo->pivot->incluidos;
                                    $max   = $grupo->pivot->maximo;
                                    $extra = $grupo->pivot->precio_extra ?? $grupo->precio_extra;
                                    $permiteExtra = $extra > 0 && ($max === null || $max > $inc);
                                @endphp
                                <div>
                                    <strong>{{ $grupo->nombre }}:</strong>
                                    @if($inc > 0) {{ $inc }} incluido{{ $inc > 1 ? 's' : '' }} @endif
                                    @if($inc === 0 && !$permiteExtra) opcional @endif
                                    @if($permiteExtra)
                                        {{ $inc > 0 ? '·' : '' }} adicional {{ $dinero($extra) }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <a href="/menu/productos/{{ $producto->id }}/editar"
                       style="margin-top:auto;padding:8px;text-align:center;background:#000;color:#fff;border-radius:8px;font-size:13px;text-decoration:none;">
                        Editar
                    </a>
                </div>
            @empty
                <div style="color:#999;font-size:13px;">Sin productos en esta categoría.</div>
            @endforelse
        </div>
    @endforeach

    {{-- Opciones y toppings --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:10px;flex-wrap:wrap;margin:28px 0 10px;">
        <div>
            <h2 style="font-size:17px;margin-bottom:4px;">Opciones y toppings</h2>
            <p style="font-size:13px;color:#888;">
                Toca un topping para marcarlo agotado o disponible. Con ✎ cambias nombre, precio o días.
            </p>
        </div>
        <button onclick="abrirGrupo()" style="padding:9px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:14px;cursor:pointer;">
            + Nuevo grupo
        </button>
    </div>

    @foreach($grupos as $grupo)
        <div style="background:#fff;border-radius:12px;padding:14px;margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <strong style="font-size:14px;">{{ $grupo->nombre }}</strong>
                    <button onclick='abrirGrupo(@json(["id" => $grupo->id, "nombre" => $grupo->nombre, "precio" => (int) $grupo->precio_extra]))'
                            aria-label="Editar grupo {{ $grupo->nombre }}"
                            style="border:none;background:none;cursor:pointer;font-size:13px;color:#888;">✎</button>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:#888;">
                        {{ $grupo->precio_extra > 0 ? 'Adicional ' . $dinero($grupo->precio_extra) . ' c/u' : 'Sin costo adicional' }}
                    </span>
                    <button onclick='abrirTopping({{ $grupo->id }}, @json($grupo->nombre), {{ (int) $grupo->precio_extra }})'
                            style="padding:6px 12px;background:#000;color:#fff;border:none;border-radius:8px;font-size:12px;cursor:pointer;">
                        + Agregar topping
                    </button>
                </div>
            </div>

            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @forelse($grupo->modificadores as $mod)
                    @php
                        $diasMod = collect($mod->dias_disponibles ?? [])->map(fn ($d) => $nombresDias[$d] ?? $d)->join(', ');
                        $datos   = ['id' => $mod->id, 'grupo' => $grupo->id, 'grupoNombre' => $grupo->nombre, 'grupoPrecio' => (int) $grupo->precio_extra,
                                    'nombre' => $mod->nombre, 'precio' => $mod->precio_extra, 'dias' => $mod->dias_disponibles ?? []];
                    @endphp
                    <span style="display:inline-flex;align-items:stretch;border-radius:16px;overflow:hidden;
                                 border:1px solid {{ $mod->activo ? '#ddd' : '#f5c2c7' }};" id="chip-{{ $mod->id }}">
                        <button id="mod-{{ $mod->id }}" onclick="toggleModificador({{ $mod->id }})"
                            style="padding:6px 6px 6px 12px;font-size:12px;cursor:pointer;border:none;
                                   background:{{ $mod->activo ? '#fff' : '#f8d7da' }};
                                   color:{{ $mod->activo ? '#000' : '#721c24' }};
                                   text-decoration:{{ $mod->activo ? 'none' : 'line-through' }};">
                            {{ $mod->nombre }}
                            @if($mod->precio_extra !== null && $mod->precio_extra !== (int) $grupo->precio_extra)
                                <span style="font-size:10px;color:#0c447c;">{{ $mod->precio_extra ? '+' . $dinero($mod->precio_extra) : 'sin costo' }}</span>
                            @endif
                            @if($diasMod)
                                <span style="font-size:10px;color:#856404;">({{ $diasMod }})</span>
                            @endif
                        </button>
                        <button onclick='abrirTopping({{ $grupo->id }}, @json($grupo->nombre), {{ (int) $grupo->precio_extra }}, @json($datos))'
                                aria-label="Editar {{ $mod->nombre }}"
                                style="padding:6px 10px 6px 4px;border:none;background:{{ $mod->activo ? '#fff' : '#f8d7da' }};cursor:pointer;font-size:12px;color:#888;">✎</button>
                    </span>
                @empty
                    <span style="font-size:13px;color:#999;">Este grupo aún no tiene toppings.</span>
                @endforelse
            </div>
        </div>
    @endforeach

    {{-- Modal topping --}}
    <div id="modal-topping" onclick="if (event.target === this) cerrarModales()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:14px;padding:20px;width:100%;max-width:440px;" role="dialog" aria-modal="true">
            <h3 id="t-titulo" style="font-size:18px;margin-bottom:4px;">Nuevo topping</h3>
            <div id="t-grupo" style="font-size:13px;color:#888;margin-bottom:14px;"></div>

            <label for="t-nombre" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre *</label>
            <input id="t-nombre" maxlength="100" placeholder="Ej: Gomitas" autocomplete="off"
                   style="width:100%;padding:11px;font-size:16px;border:2px solid #ddd;border-radius:8px;margin-bottom:12px;">

            <label for="t-precio" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Precio como adicional</label>
            <input id="t-precio" type="number" min="0" step="100" inputmode="numeric"
                   style="width:100%;padding:11px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            <div id="t-ayuda-precio" style="font-size:12px;color:#888;margin:4px 0 12px;"></div>

            <div style="font-size:13px;color:#555;margin-bottom:6px;">Días disponibles</div>
            <div id="t-dias" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:4px;">
                @foreach($nombresDias as $n => $d)
                    <label style="display:inline-flex;gap:4px;align-items:center;padding:6px 10px;border:1px solid #ddd;border-radius:14px;font-size:12px;cursor:pointer;">
                        <input type="checkbox" value="{{ $n }}"> {{ $d }}
                    </label>
                @endforeach
            </div>
            <div style="font-size:12px;color:#888;margin-bottom:16px;">Sin marcar = todos los días.</div>

            <div id="t-error" style="display:none;padding:8px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:13px;margin-bottom:10px;"></div>

            <div style="display:flex;gap:8px;">
                <button id="t-eliminar" onclick="eliminarTopping()"
                        style="padding:11px 14px;background:#f8d7da;color:#721c24;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Eliminar</button>
                <button onclick="cerrarModales()" style="padding:11px 14px;background:#f2f2f2;border:none;border-radius:8px;font-size:14px;cursor:pointer;margin-left:auto;">Cancelar</button>
                <button id="t-guardar" onclick="guardarTopping()"
                        style="padding:11px 18px;background:#000;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;">Guardar</button>
            </div>
        </div>
    </div>

    {{-- Modal grupo --}}
    <div id="modal-grupo" onclick="if (event.target === this) cerrarModales()"
         style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;">
        <div style="background:#fff;border-radius:14px;padding:20px;width:100%;max-width:440px;" role="dialog" aria-modal="true">
            <h3 id="g-titulo" style="font-size:18px;margin-bottom:14px;">Nuevo grupo</h3>

            <label for="g-nombre" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre *</label>
            <input id="g-nombre" maxlength="100" placeholder="Ej: Salsas" autocomplete="off"
                   style="width:100%;padding:11px;font-size:16px;border:2px solid #ddd;border-radius:8px;margin-bottom:12px;">

            <label for="g-precio" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Precio de cada adicional</label>
            <input id="g-precio" type="number" min="0" step="100" inputmode="numeric" placeholder="0"
                   style="width:100%;padding:11px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            <div id="g-ayuda" style="font-size:12px;color:#888;margin:4px 0 16px;">
                Lo que se cobra por cada topping que pase de los incluidos en el producto.
            </div>

            <div id="g-error" style="display:none;padding:8px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:13px;margin-bottom:10px;"></div>

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button onclick="cerrarModales()" style="padding:11px 14px;background:#f2f2f2;border:none;border-radius:8px;font-size:14px;cursor:pointer;">Cancelar</button>
                <button id="g-guardar" onclick="guardarGrupo()"
                        style="padding:11px 18px;background:#000;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;">Guardar</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
async function enviarToggle(url) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    const data = await res.json();

    const msg = document.getElementById('mensaje');
    msg.style.display    = 'block';
    msg.style.background = data.success ? '#d4edda' : '#f8d7da';
    msg.style.color      = data.success ? '#155724' : '#721c24';
    msg.textContent      = data.mensaje ?? data.message ?? 'Ocurrio un error';
    clearTimeout(window._ocultarMsg);
    window._ocultarMsg = setTimeout(() => msg.style.display = 'none', 2500);

    return data;
}

async function toggleProducto(id) {
    const data = await enviarToggle(`/menu/productos/${id}/toggle`);
    if (!data.success) return;

    document.getElementById('prod-' + id).style.opacity = data.activo ? '1' : '.5';
    const btn = document.getElementById('btn-prod-' + id);
    btn.textContent      = data.activo ? 'Disponible' : 'No disponible';
    btn.style.background = data.activo ? '#d4edda' : '#f8d7da';
    btn.style.color      = data.activo ? '#155724' : '#721c24';
}

// ------------------------------------------------------------------
// Crear / editar toppings y grupos
// ------------------------------------------------------------------
const $m = id => document.getElementById(id);
let edicion = null;   // { grupo, id? }

function cerrarModales() {
    $m('modal-topping').style.display = 'none';
    $m('modal-grupo').style.display = 'none';
    edicion = null;
}

function abrirTopping(grupoId, grupoNombre, grupoPrecio, mod = null) {
    edicion = { grupo: grupoId, id: mod ? mod.id : null };
    $m('t-titulo').textContent = mod ? 'Editar topping' : 'Nuevo topping';
    $m('t-grupo').textContent  = 'Grupo: ' + grupoNombre;
    $m('t-nombre').value = mod ? mod.nombre : '';
    $m('t-precio').value = mod && mod.precio !== null ? mod.precio : '';
    $m('t-precio').placeholder = String(grupoPrecio);
    $m('t-ayuda-precio').textContent = 'Vacío = el precio del grupo ($' + grupoPrecio.toLocaleString('es-CO') + '). Solo se cobra cuando pasa de los incluidos.';
    document.querySelectorAll('#t-dias input').forEach(c => c.checked = !!(mod && mod.dias.includes(parseInt(c.value, 10))));
    $m('t-eliminar').style.display = mod ? 'block' : 'none';
    $m('t-error').style.display = 'none';
    $m('modal-topping').style.display = 'flex';
    $m('t-nombre').focus();
}

function abrirGrupo(grupo = null) {
    edicion = { grupoEditar: grupo ? grupo.id : null };
    $m('g-titulo').textContent = grupo ? 'Editar grupo' : 'Nuevo grupo';
    $m('g-nombre').value = grupo ? grupo.nombre : '';
    $m('g-precio').value = grupo ? grupo.precio : '';
    $m('g-ayuda').textContent = grupo
        ? 'Lo que se cobra por cada topping que pase de los incluidos. Aplica a todos los productos que usan este grupo.'
        : 'Después, en cada producto (Editar), activa este grupo y define cuántos toppings incluye.';
    $m('g-error').style.display = 'none';
    $m('modal-grupo').style.display = 'flex';
    $m('g-nombre').focus();
}

async function enviar(url, metodo, cuerpo, cajaError) {
    try {
        const res = await fetch(url, {
            method: metodo,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: cuerpo ? JSON.stringify(cuerpo) : null,
        });
        const data = await res.json();
        if (!data.success) {
            const err = $m(cajaError);
            err.textContent = data.mensaje ?? data.message ?? 'No se pudo guardar.';
            err.style.display = 'block';
            return null;
        }
        return data;
    } catch (e) {
        const err = $m(cajaError);
        err.textContent = 'No se pudo conectar. Intenta de nuevo.';
        err.style.display = 'block';
        return null;
    }
}

async function guardarTopping() {
    const nombre = $m('t-nombre').value.trim();
    if (!nombre) { $m('t-error').textContent = 'Escribe el nombre del topping.'; $m('t-error').style.display = 'block'; return; }

    const precio = $m('t-precio').value.trim();
    const cuerpo = {
        nombre,
        precio_extra: precio === '' ? null : parseInt(precio, 10),
        dias_disponibles: [...document.querySelectorAll('#t-dias input:checked')].map(c => parseInt(c.value, 10)),
    };
    const url = edicion.id ? `/menu/modificadores/${edicion.id}/actualizar` : `/menu/grupos/${edicion.grupo}/modificadores`;

    $m('t-guardar').disabled = true;
    const data = await enviar(url, 'POST', cuerpo, 't-error');
    $m('t-guardar').disabled = false;
    if (data) location.reload();
}

async function eliminarTopping() {
    const nombre = $m('t-nombre').value.trim();
    if (!confirm(`¿Eliminar ${nombre}? Si solo se acabó, mejor márcalo como agotado tocándolo en el menú.`)) return;
    const data = await enviar(`/menu/modificadores/${edicion.id}`, 'DELETE', null, 't-error');
    if (data) location.reload();
}

async function guardarGrupo() {
    const nombre = $m('g-nombre').value.trim();
    if (!nombre) { $m('g-error').textContent = 'Escribe el nombre del grupo.'; $m('g-error').style.display = 'block'; return; }

    const cuerpo = { nombre, precio_extra: parseInt($m('g-precio').value || '0', 10) };
    const url = edicion.grupoEditar ? `/menu/grupos/${edicion.grupoEditar}/actualizar` : '/menu/grupos';

    $m('g-guardar').disabled = true;
    const data = await enviar(url, 'POST', cuerpo, 'g-error');
    $m('g-guardar').disabled = false;
    if (data) location.reload();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarModales();
    if (e.key === 'Enter' && $m('modal-topping').style.display === 'flex') guardarTopping();
    if (e.key === 'Enter' && $m('modal-grupo').style.display === 'flex') guardarGrupo();
});

async function toggleModificador(id) {
    const data = await enviarToggle(`/menu/modificadores/${id}/toggle`);
    if (!data.success) return;

    const chip = document.getElementById('mod-' + id);
    chip.style.background     = data.activo ? '#fff' : '#f8d7da';
    chip.style.color          = data.activo ? '#000' : '#721c24';
    chip.style.textDecoration = data.activo ? 'none' : 'line-through';
    const cont = document.getElementById('chip-' + id);
    cont.style.borderColor = data.activo ? '#ddd' : '#f5c2c7';
    cont.lastElementChild.style.background = data.activo ? '#fff' : '#f8d7da';
}
</script>
@endsection
@endsection
