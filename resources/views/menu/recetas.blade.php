@extends('layouts.pos')

@section('titulo', 'Recetas')

@section('contenido')
@php
    $input = 'width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;';
    $label = 'display:block;font-size:12px;color:#555;margin-bottom:4px;';
@endphp

<style>
    .rc-tabs { display:flex;gap:6px;margin-bottom:12px; }
    .rc-tab { padding:10px 18px;border:none;border-radius:20px;background:#fff;font-size:14px;cursor:pointer; }
    .rc-tab.activa { background:#000;color:#fff; }
    .rc-card { background:#fff;border-radius:12px;padding:14px;margin-bottom:10px;border:2px solid transparent; }
    .rc-card.sucia { border-color:#f0c36d; }
    .rc-head { display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap; }
    .rc-nombre { font-weight:bold;font-size:15px; }
    .rc-sub { font-size:12px;color:#888;margin-top:2px; }
    .rc-badge { padding:3px 9px;border-radius:12px;font-size:11px;font-weight:bold; }
    .rc-linea { display:grid;grid-template-columns:2fr 1.4fr 1fr auto;gap:8px;align-items:center;margin-top:8px; }
    .rc-linea.sin-var { grid-template-columns:2fr 1fr auto; }
    .rc-linea select, .rc-linea input { width:100%;padding:8px;font-size:14px;border:2px solid #ddd;border-radius:8px; }
    .rc-cant { display:flex;align-items:center;gap:6px; }
    .rc-cant span { font-size:12px;color:#666;min-width:28px; }
    .rc-x { padding:6px 10px;background:#f0f0f0;border:none;border-radius:6px;cursor:pointer;font-size:13px; }
    .rc-pie { display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px; }
    .rc-costos { font-size:12px;color:#555;line-height:1.6; }
    .rc-add { padding:6px 12px;background:#fff;border:1px dashed #999;border-radius:8px;font-size:13px;cursor:pointer; }
    .rc-save { padding:8px 16px;background:#000;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:bold;cursor:pointer; }
    .rc-save:disabled { background:#bbb;cursor:default; }
    .rc-grupo-titulo { font-size:17px;margin:18px 0 8px; }
    .rc-opciones { display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:10px; }
    @media (max-width: 640px) {
        .rc-linea { grid-template-columns:1fr 1fr auto; }
        .rc-linea .rc-ins { grid-column:1 / -1; }
        .rc-linea.sin-var { grid-template-columns:1fr auto; }
        .rc-opciones { grid-template-columns:1fr; }
    }
</style>

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <a href="/menu" style="font-size:13px;color:#555;text-decoration:none;">← Volver al menú</a>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin:8px 0 12px;">
        <div>
            <h1 style="font-size:22px;">Recetas</h1>
            <div style="font-size:13px;color:#888;max-width:640px;line-height:1.5;">
                Indica qué insumos gasta cada producto y cada opción. Cada venta los descuenta de la sede donde se vendió.
                Las cantidades van en la unidad del insumo: gramos, mililitros o unidades.
            </div>
        </div>
        <button type="button" onclick="abrirNuevoInsumo()"
            style="padding:10px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:14px;cursor:pointer;">
            + Nuevo insumo
        </button>
    </div>

    <div style="background:#fff8e1;border-radius:10px;padding:10px 14px;font-size:13px;color:#6b5200;margin-bottom:12px;line-height:1.5;">
        <strong>Cómo se suma:</strong> producto (ej. vaso, cuchara, porción base) + cada opción que elige el cliente
        (ej. 1 bola de helado de mora = 90 g de helado de mora). Si una opción se elige 2 veces, gasta el doble.
        Lo que no tenga receta se vende normal, sin descontar insumos.
    </div>

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
        <div class="rc-tabs" style="margin:0;">
            <button class="rc-tab activa" id="tab-productos" onclick="verTab('productos')">Productos</button>
            <button class="rc-tab" id="tab-opciones" onclick="verTab('opciones')">Opciones</button>
        </div>
        <input id="buscar" placeholder="Buscar..." oninput="filtrar()" aria-label="Buscar"
               style="flex:1;min-width:180px;padding:10px;border:2px solid #ddd;border-radius:8px;font-size:14px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;background:#fff;padding:9px 12px;border-radius:8px;">
            <input type="checkbox" id="solo-sin" onchange="filtrar()"> Solo sin receta
        </label>
    </div>

    <div id="panel-productos"></div>
    <div id="panel-opciones" style="display:none;"></div>
</div>

{{-- Ventana: nuevo insumo --}}
<div id="modal-insumo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div role="dialog" aria-modal="true" style="background:#fff;border-radius:14px;padding:20px;width:100%;max-width:420px;">
        <h2 style="font-size:18px;margin-bottom:12px;">Nuevo insumo</h2>
        <label style="{{ $label }}" for="ni-nombre">Nombre</label>
        <input id="ni-nombre" maxlength="120" placeholder="Ej: Helado de mora" style="{{ $input }}margin-bottom:10px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
            <div>
                <label style="{{ $label }}" for="ni-unidad">Unidad de control</label>
                <select id="ni-unidad" style="{{ $input }}">
                    <option value="g">Gramos (g)</option>
                    <option value="ml">Mililitros (ml)</option>
                    <option value="und">Unidades (und)</option>
                </select>
            </div>
            <div>
                <label style="{{ $label }}" for="ni-categoria">Categoría</label>
                <select id="ni-categoria" style="{{ $input }}">
                    <option value="">Sin categoría</option>
                    @foreach($categoriasInsumo as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div id="ni-error" style="display:none;color:#c00;font-size:13px;margin-bottom:10px;"></div>
        <div style="display:flex;gap:8px;">
            <button type="button" onclick="cerrarNuevoInsumo()" style="flex:1;padding:12px;background:#f0f0f0;border:none;border-radius:10px;cursor:pointer;">Cancelar</button>
            <button type="button" id="ni-guardar" onclick="guardarNuevoInsumo()" style="flex:1;padding:12px;background:#000;color:#fff;border:none;border-radius:10px;font-weight:bold;cursor:pointer;">Crear</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const PRODUCTOS = @json($productos);
const GRUPOS    = @json($grupos);
let   INSUMOS   = @json($insumos);
const CSRF      = document.querySelector('meta[name="csrf-token"]').content;

const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');
const insumo = id => INSUMOS.find(i => i.id === Number(id)) || null;

// Estado editable: una "tarjeta" por producto (p-ID) y por opción (o-ID)
const tarjetas = {};
PRODUCTOS.forEach(c => c.productos.forEach(p => {
    tarjetas['p-' + p.id] = { tipo: 'producto', ref: p, lineas: p.receta.map(l => ({ ...l })), sucia: false };
}));
GRUPOS.forEach(g => g.opciones.forEach(o => {
    tarjetas['o-' + o.id] = { tipo: 'opcion', ref: o, grupo: g, lineas: o.insumos.map(l => ({ ...l, variante_id: null })), sucia: false };
}));

let tabActiva = 'productos';

// ------------------------------------------------------------------
// Render
// ------------------------------------------------------------------
function opcionesInsumo(sel) {
    return '<option value="">Elige insumo…</option>' + INSUMOS
        .filter(i => i.activo || i.id === Number(sel))
        .map(i => `<option value="${i.id}" ${i.id === Number(sel) ? 'selected' : ''}>${esc(i.nombre)} (${i.unidad})</option>`)
        .join('');
}

function htmlLineas(key) {
    const t = tarjetas[key];
    const variantes = t.tipo === 'producto' ? t.ref.variantes : [];
    return t.lineas.map((l, idx) => {
        const ins = insumo(l.insumo_id);
        const selVar = variantes.length ? `
            <select onchange="cambiar('${key}', ${idx}, 'variante_id', this.value)" aria-label="Aplica a">
                <option value="">Todas las presentaciones</option>
                ${variantes.map(v => `<option value="${v.id}" ${Number(l.variante_id) === v.id ? 'selected' : ''}>Solo ${esc(v.nombre)}</option>`).join('')}
            </select>` : '';
        return `
            <div class="rc-linea ${variantes.length ? '' : 'sin-var'}">
                <select class="rc-ins" onchange="cambiar('${key}', ${idx}, 'insumo_id', this.value)" aria-label="Insumo">${opcionesInsumo(l.insumo_id)}</select>
                ${selVar}
                <div class="rc-cant">
                    <input type="number" min="0" step="any" inputmode="decimal" value="${l.cantidad ?? ''}" placeholder="0"
                           oninput="cambiar('${key}', ${idx}, 'cantidad', this.value, true)" aria-label="Cantidad">
                    <span>${ins ? ins.unidad : ''}</span>
                </div>
                <button class="rc-x" onclick="quitar('${key}', ${idx})" aria-label="Quitar">✕</button>
            </div>`;
    }).join('');
}

// Costo de insumos por presentación (o del producto si no tiene variantes)
function htmlCostos(key) {
    const t = tarjetas[key];
    if (!t.lineas.length) return '<span style="color:#999;">Sin receta: no descuenta insumos.</span>';

    const costoDe = lineas => {
        let total = 0, faltan = 0;
        lineas.forEach(l => {
            const ins = insumo(l.insumo_id);
            if (!ins || !(Number(l.cantidad) > 0)) return;
            if (ins.costo > 0) total += ins.costo * Number(l.cantidad); else faltan++;
        });
        return { total, faltan };
    };
    const texto = (nombre, precio, c) => {
        let s = `${nombre ? esc(nombre) + ': ' : ''}costo ${dinero(c.total)}`;
        if (precio > 0 && c.total > 0) s += ` · margen ${Math.round((1 - c.total / precio) * 100)}%`;
        if (c.faltan) s += ` <span style="color:#a66;">(${c.faltan} insumo${c.faltan > 1 ? 's' : ''} sin costo aún)</span>`;
        return s;
    };

    if (t.tipo === 'opcion') return texto('', 0, costoDe(t.lineas));

    const vars = t.ref.variantes;
    if (!vars.length) return texto('', t.ref.precio, costoDe(t.lineas));

    return vars.map(v => texto(v.nombre, v.precio,
        costoDe(t.lineas.filter(l => !l.variante_id || Number(l.variante_id) === v.id)))).join('<br>');
}

function htmlTarjeta(key) {
    const t = tarjetas[key];
    const r = t.ref;
    const sub = t.tipo === 'producto'
        ? (r.variantes.length ? r.variantes.map(v => esc(v.nombre) + ' ' + dinero(v.precio)).join(' · ') : dinero(r.precio))
        : '';
    const badge = t.lineas.length
        ? `<span class="rc-badge" style="background:#d4edda;color:#155724;">${t.lineas.length} insumo${t.lineas.length > 1 ? 's' : ''}</span>`
        : `<span class="rc-badge" style="background:#f0f0f0;color:#666;">Sin receta</span>`;

    return `
        <div class="rc-card ${t.sucia ? 'sucia' : ''}" id="card-${key}" style="${r.activo ? '' : 'opacity:.6;'}">
            <div class="rc-head">
                <div>
                    <div class="rc-nombre">${esc(r.nombre)}${r.activo ? '' : ' <span style="font-size:11px;color:#999;">(no disponible)</span>'}</div>
                    ${sub ? `<div class="rc-sub">${sub}</div>` : ''}
                </div>
                ${badge}
            </div>
            <div id="lineas-${key}">${htmlLineas(key)}</div>
            <div class="rc-pie">
                <button class="rc-add" onclick="agregar('${key}')">+ Insumo</button>
                <button class="rc-save" id="save-${key}" onclick="guardar('${key}')" ${t.sucia ? '' : 'disabled'}>Guardar</button>
            </div>
            <div class="rc-costos" id="costos-${key}" style="margin-top:8px;">${htmlCostos(key)}</div>
        </div>`;
}

function renderTodo() {
    $('panel-productos').innerHTML = PRODUCTOS.map(c => `
        <div class="bloque">
            <h2 class="rc-grupo-titulo">${esc(c.categoria)}</h2>
            ${c.productos.map(p => `<div class="item" data-key="p-${p.id}" data-nombre="${esc(p.nombre.toLowerCase())}">${htmlTarjeta('p-' + p.id)}</div>`).join('')}
        </div>`).join('') || '<div style="color:#999;">No hay productos en el menú.</div>';

    $('panel-opciones').innerHTML = GRUPOS.map(g => `
        <div class="bloque">
            <h2 class="rc-grupo-titulo">${esc(g.nombre)}</h2>
            <div class="rc-opciones">
                ${g.opciones.map(o => `<div class="item" data-key="o-${o.id}" data-nombre="${esc((g.nombre + ' ' + o.nombre).toLowerCase())}">${htmlTarjeta('o-' + o.id)}</div>`).join('')}
            </div>
        </div>`).join('') || '<div style="color:#999;">No hay grupos de opciones.</div>';

    filtrar();
}

function renderTarjeta(key) {
    const cont = document.querySelector(`.item[data-key="${key}"]`);
    if (cont) cont.innerHTML = htmlTarjeta(key);
}

// ------------------------------------------------------------------
// Edición
// ------------------------------------------------------------------
function marcarSucia(key) {
    tarjetas[key].sucia = true;
    $('card-' + key)?.classList.add('sucia');
    const b = $('save-' + key);
    if (b) b.disabled = false;
}

function cambiar(key, idx, campo, valor, soloCostos = false) {
    const l = tarjetas[key].lineas[idx];
    l[campo] = campo === 'cantidad' ? valor : (valor === '' ? null : Number(valor));
    marcarSucia(key);
    if (soloCostos) {
        $('costos-' + key).innerHTML = htmlCostos(key);   // no redibujar: el cursor sigue en el campo
    } else {
        renderTarjeta(key);
    }
}

function agregar(key) {
    tarjetas[key].lineas.push({ insumo_id: null, variante_id: null, cantidad: '' });
    marcarSucia(key);
    renderTarjeta(key);
    const selects = document.querySelectorAll(`#lineas-${key} .rc-ins`);
    selects[selects.length - 1]?.focus();
}

function quitar(key, idx) {
    tarjetas[key].lineas.splice(idx, 1);
    marcarSucia(key);
    renderTarjeta(key);
}

async function guardar(key) {
    const t = tarjetas[key];
    const lineas = t.lineas.filter(l => l.insumo_id || Number(l.cantidad) > 0);

    for (const l of lineas) {
        if (!l.insumo_id) return mensaje(`${t.ref.nombre}: elige el insumo en cada línea.`, false);
        if (!(Number(l.cantidad) > 0)) return mensaje(`${t.ref.nombre}: escribe una cantidad mayor que cero.`, false);
    }

    const url = t.tipo === 'producto' ? `/menu/recetas/productos/${t.ref.id}` : `/menu/recetas/opciones/${t.ref.id}`;
    const b = $('save-' + key);
    b.disabled = true;
    b.textContent = 'Guardando…';

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                lineas: lineas.map(l => ({
                    insumo_id: l.insumo_id,
                    variante_id: t.tipo === 'producto' ? (l.variante_id || null) : undefined,
                    cantidad: Number(l.cantidad),
                })),
            }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo guardar');
        }

        t.lineas = lineas.map(l => ({ ...l }));
        t.sucia = false;
        renderTarjeta(key);
        mensaje(data.mensaje, true);
    } catch (e) {
        b.disabled = false;
        b.textContent = 'Guardar';
        mensaje(e.message, false);
    }
}

function mensaje(texto, ok) {
    const box = $('mensaje');
    box.textContent = texto;
    box.style.display = 'block';
    box.style.background = ok ? '#d4edda' : '#f8d7da';
    box.style.color = ok ? '#155724' : '#721c24';
    clearTimeout(box._t);
    box._t = setTimeout(() => { box.style.display = 'none'; }, 4000);
}

// ------------------------------------------------------------------
// Pestañas y filtro
// ------------------------------------------------------------------
function verTab(tab) {
    tabActiva = tab;
    $('tab-productos').classList.toggle('activa', tab === 'productos');
    $('tab-opciones').classList.toggle('activa', tab === 'opciones');
    $('panel-productos').style.display = tab === 'productos' ? '' : 'none';
    $('panel-opciones').style.display = tab === 'opciones' ? '' : 'none';
    try { sessionStorage.setItem('recetasTab', tab); } catch (e) {}
}

function filtrar() {
    const q = $('buscar').value.trim().toLowerCase();
    const sin = $('solo-sin').checked;
    document.querySelectorAll('.item').forEach(el => {
        const t = tarjetas[el.dataset.key];
        const ok = (!q || el.dataset.nombre.includes(q)) && (!sin || !t.lineas.length);
        el.style.display = ok ? '' : 'none';
    });
    document.querySelectorAll('.bloque').forEach(b => {
        b.style.display = [...b.querySelectorAll('.item')].some(i => i.style.display !== 'none') ? '' : 'none';
    });
}

window.addEventListener('beforeunload', e => {
    if (Object.values(tarjetas).some(t => t.sucia)) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// ------------------------------------------------------------------
// Crear insumo sin salir de la pantalla
// ------------------------------------------------------------------
function abrirNuevoInsumo() {
    $('ni-nombre').value = '';
    $('ni-error').style.display = 'none';
    $('modal-insumo').style.display = 'flex';
    setTimeout(() => $('ni-nombre').focus(), 50);
}

function cerrarNuevoInsumo() {
    $('modal-insumo').style.display = 'none';
}

async function guardarNuevoInsumo() {
    const nombre = $('ni-nombre').value.trim();
    if (!nombre) {
        $('ni-error').textContent = 'Escribe el nombre del insumo.';
        $('ni-error').style.display = 'block';
        return;
    }
    const btn = $('ni-guardar');
    btn.disabled = true;
    try {
        const res = await fetch('/insumos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ nombre, unidad: $('ni-unidad').value, categoria: $('ni-categoria').value || null }),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo crear el insumo.');
        }
        INSUMOS.push({ ...data.insumo, costo: 0, activo: true });
        INSUMOS.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));

        // Redibujar sin perder lo que se está editando
        Object.keys(tarjetas).forEach(renderTarjeta);
        filtrar();
        cerrarNuevoInsumo();
        mensaje(`Insumo ${data.insumo.nombre} creado. Ya lo puedes elegir.`, true);
    } catch (e) {
        $('ni-error').textContent = e.message;
        $('ni-error').style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

renderTodo();
try { if (sessionStorage.getItem('recetasTab') === 'opciones') verTab('opciones'); } catch (e) {}
</script>
@endsection
