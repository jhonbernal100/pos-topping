@extends('layouts.pos')

@section('titulo', 'Nueva venta')

@section('estilos')
@php
    $marca  = $tenant->color_primario   ?? '#E4387A';
    $titulo = $tenant->color_secundario ?? '#4A2C2A';
    $fondo  = $tenant->color_fondo      ?? '#FFF6EE';
@endphp
<style>
    :root { --marca: {{ $marca }}; --titulo: {{ $titulo }}; --fondo: {{ $fondo }}; }

    body { background: var(--fondo); }
    body.modo-cliente .navbar,
    body.modo-cliente .toolbar,
    body.modo-cliente .footer-avanzas,
    body.modo-cliente .alerta-trial { display: none; }

    .caja { display:grid; grid-template-columns:1fr 390px; gap:18px; padding:16px; max-width:1400px; margin:0 auto; }
    @media (max-width: 960px) { .caja { grid-template-columns:1fr; } }

    .encabezado { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
    .marca { display:flex; align-items:center; gap:12px; }
    .marca img { height:64px; max-width:160px; object-fit:contain; }
    .marca-nombre { font-size:22px; font-weight:bold; color:var(--titulo); line-height:1.1; }
    .marca-sede { font-size:13px; color:var(--titulo); opacity:.7; }
    .btn-modo { padding:8px 14px; border-radius:20px; border:1px solid var(--titulo); background:transparent; color:var(--titulo); cursor:pointer; font-size:13px; font-family:inherit; }

    .tabs { display:flex; gap:8px; overflow-x:auto; margin-bottom:14px; scrollbar-width:none; padding-bottom:2px; }
    .tabs::-webkit-scrollbar { display:none; }
    .tab { padding:12px 20px; border-radius:24px; border:2px solid transparent; background:#fff; color:var(--titulo); cursor:pointer; white-space:nowrap; font-size:15px; font-weight:bold; font-family:inherit; }
    .tab.activa { background:var(--marca); color:#fff; }

    .grid-prod { display:grid; grid-template-columns:repeat(auto-fill, minmax(190px, 1fr)); gap:14px; }
    .prod { background:#fff; border:none; border-radius:16px; padding:0; text-align:left; cursor:pointer; overflow:hidden;
            display:flex; flex-direction:column; font-family:inherit; box-shadow:0 2px 8px rgba(0,0,0,.06); transition:transform .12s; }
    .prod:active { transform:scale(.97); }
    .prod:disabled { cursor:not-allowed; }
    .prod-foto { width:100%; height:150px; object-fit:contain; display:block; background:var(--fondo); padding:8px; }
    .prod-inicial { width:100%; height:150px; display:flex; align-items:center; justify-content:center;
                    background:var(--marca); color:#fff; font-size:46px; font-weight:bold; opacity:.85; }
    .prod-info { padding:10px 12px 12px; display:flex; flex-direction:column; gap:4px; flex:1; }
    .prod-nombre { font-weight:bold; font-size:16px; color:var(--titulo); }
    .prod-desc { font-size:12px; color:#777; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .prod-precio { font-size:16px; font-weight:bold; color:var(--marca); margin-top:auto; padding-top:4px; }

    .panel { background:#fff; border-radius:16px; padding:18px; position:sticky; top:12px; align-self:start; box-shadow:0 2px 12px rgba(0,0,0,.06); }
    .panel-titulo { font-size:20px; font-weight:bold; color:var(--titulo); margin-bottom:10px; }
    .item { border-bottom:1px solid #f2f2f2; padding:10px 0; }

    .btn { padding:12px; border-radius:12px; border:none; cursor:pointer; font-size:15px; font-family:inherit; }
    .btn-marca { background:var(--marca); color:#fff; font-weight:bold; }
    .btn-gris { background:#f2f2f2; color:#000; }
    .btn:disabled { opacity:.5; cursor:not-allowed; }

    .metodo { flex:1; padding:10px 4px; border:2px solid #e5e5e5; border-radius:10px; background:#fff; cursor:pointer; font-size:13px; font-family:inherit; }
    .metodo.activo { border-color:var(--marca); background:var(--marca); color:#fff; font-weight:bold; }

    .campo { width:100%; padding:11px; font-size:15px; border:2px solid #e5e5e5; border-radius:10px; font-family:inherit; }
    .campo:focus { outline:none; border-color:var(--marca); }

    .overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:1000; align-items:flex-end; justify-content:center; }
    .overlay.abierto { display:flex; }
    .modal { background:#fff; width:100%; max-width:600px; max-height:94vh; overflow-y:auto; border-radius:20px 20px 0 0; }
    @media (min-width: 700px) { .overlay { align-items:center; } .modal { border-radius:20px; } }
    .modal-cuerpo { padding:18px; }
    .modal-foto { width:100%; height:170px; object-fit:contain; display:block; background:var(--fondo); padding:10px; }

    .seccion { margin-top:16px; }
    .seccion-titulo { font-size:15px; font-weight:bold; margin-bottom:8px; color:var(--titulo); }
    .chips { display:flex; gap:8px; flex-wrap:wrap; }
    .chip { padding:9px 14px; border-radius:20px; border:2px solid #e5e5e5; background:#fff; cursor:pointer; font-size:14px;
            display:inline-flex; gap:6px; align-items:center; font-family:inherit; }
    .chip.sel { background:var(--marca); color:#fff; border-color:var(--marca); }
    .contador { background:#fff; color:var(--marca); border-radius:10px; padding:0 7px; font-size:12px; font-weight:bold; }

    .salir-modo { display:none; position:fixed; right:10px; bottom:10px; z-index:900; padding:6px 10px; font-size:11px;
                  border-radius:12px; border:none; background:rgba(0,0,0,.25); color:#fff; cursor:pointer; }
    body.modo-cliente .salir-modo { display:block; }
</style>
@endsection

@section('contenido')
@if(!$sede)
    <div style="max-width:520px;margin:40px auto;padding:20px;background:#fff3cd;color:#856404;border-radius:12px;">
        No hay una sede activa. Selecciona una sede en el encabezado para empezar a vender.
    </div>
@else
<div class="caja">
    {{-- Menú --}}
    <div>
        <div class="encabezado">
            <div class="marca">
                @if($tenant->logo)
                    <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->nombre }}">
                @endif
                <div>
                    <div class="marca-nombre">{{ $tenant->nombre }}</div>
                    <div class="marca-sede">Sede {{ $sede->nombre }}</div>
                </div>
            </div>
            <button class="btn-modo" id="btn-modo" onclick="alternarModoCliente()">Modo cliente</button>
        </div>
        <div class="tabs" id="tabs"></div>
        <div class="grid-prod" id="productos"></div>
    </div>

    {{-- Pedido y pago --}}
    <div class="panel">
        <div class="panel-titulo">Tu pedido</div>

        <label for="nombre-pedido" style="display:block;font-size:13px;color:#666;margin-bottom:4px;">
            ¿A nombre de quién? *
        </label>
        <input id="nombre-pedido" class="campo" maxlength="60" placeholder="Ej: Laura" autocomplete="off" style="font-size:18px;">

        <div id="items" style="margin:12px 0;"></div>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:22px;font-weight:bold;padding-top:4px;color:var(--titulo);">
            <span>Total</span>
            <span id="total" style="color:var(--marca);">$0</span>
        </div>

        <div style="margin-top:14px;">
            <div style="font-size:13px;color:#666;margin-bottom:6px;">Pago</div>
            <div id="pagos"></div>
            <button type="button" class="btn btn-gris" id="btn-dividir" onclick="agregarPago()" style="width:100%;margin-top:8px;font-size:13px;padding:8px;">
                + Dividir pago
            </button>
            <div id="resumen-pago" style="font-size:14px;margin-top:10px;"></div>
        </div>

        <div id="error" style="display:none;margin-top:10px;padding:10px;border-radius:10px;background:#f8d7da;color:#721c24;font-size:13px;"></div>

        <button id="btn-cobrar" class="btn btn-marca" onclick="cobrar()" style="width:100%;margin-top:14px;font-size:19px;padding:16px;">
            Cobrar
        </button>
    </div>
</div>

{{-- Configurar producto --}}
<div class="overlay" id="modal-producto" onclick="if (event.target === this) cerrarModal()">
    <div class="modal" id="modal-contenido" role="dialog" aria-modal="true"></div>
</div>

{{-- Venta registrada --}}
<div class="overlay" id="modal-ok">
    <div class="modal" id="ok-contenido" role="dialog" aria-modal="true" style="text-align:center;padding:24px;"></div>
</div>

<button class="salir-modo" onclick="alternarModoCliente()">Salir del modo cliente</button>
@endif
@endsection

@section('scripts')
@if($sede)
<script>
const MENU    = @json($menu);
const METODOS = { efectivo: 'Efectivo', nequi: 'Nequi', daviplata: 'Daviplata', tarjeta: 'Tarjeta' };

let categoriaActiva = MENU.length ? MENU[0].id : null;
let carrito = [];
let pagos   = [{ metodo: 'efectivo', monto: null, referencia: '' }];
let config  = null;

const $      = id => document.getElementById(id);
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');
const esc    = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

// ------------------------------------------------------------------
// Modo cliente (oculta el menú del sistema)
// ------------------------------------------------------------------
function alternarModoCliente() {
    const activo = document.body.classList.toggle('modo-cliente');
    $('btn-modo').textContent = activo ? 'Modo cajero' : 'Modo cliente';
    try { localStorage.setItem('modoCliente', activo ? '1' : '0'); } catch (e) {}
}

try {
    if (localStorage.getItem('modoCliente') === '1') {
        document.body.classList.add('modo-cliente');
        $('btn-modo').textContent = 'Modo cajero';
    }
} catch (e) {}

// ------------------------------------------------------------------
// Menú
// ------------------------------------------------------------------
function imagen(p) {
    return p.foto
        ? `<img class="prod-foto" src="${esc(p.foto)}" alt="${esc(p.nombre)}" loading="lazy">`
        : `<div class="prod-inicial">${esc(p.nombre.charAt(0))}</div>`;
}

function renderTabs() {
    $('tabs').innerHTML = MENU.map(c => `
        <button class="tab ${c.id === categoriaActiva ? 'activa' : ''}" onclick="categoriaActiva = ${c.id}; renderTabs(); renderProductos();">
            ${esc(c.nombre)}
        </button>`).join('');
}

// Stock que queda descontando lo que ya está en el pedido (productos de reventa)
function disponibleEnStock(p) {
    const enPedido = carrito.filter(i => i.producto.id === p.id).reduce((s, i) => s + i.cantidad, 0);
    return p.stock - enPedido;
}

function renderProductos() {
    const cat = MENU.find(c => c.id === categoriaActiva);
    if (!cat) {
        $('productos').innerHTML = '<div style="color:#999;">No hay productos disponibles hoy.</div>';
        return;
    }
    $('productos').innerHTML = cat.productos.map(p => {
        const agotado = p.tipo === 'reventa' && disponibleEnStock(p) <= 0;
        return `
            <button class="prod" onclick="abrirProducto(${p.id})" ${agotado ? 'disabled style="opacity:.45;"' : ''}>
                ${imagen(p)}
                <div class="prod-info">
                    <span class="prod-nombre">${esc(p.nombre)}</span>
                    ${p.descripcion ? `<span class="prod-desc">${esc(p.descripcion)}</span>` : ''}
                    <span class="prod-precio">
                        ${agotado ? '<span style="color:#c00;">Agotado</span>' : `${p.variantes.length ? 'Desde ' : ''}${dinero(p.precio)}`}
                    </span>
                </div>
            </button>`;
    }).join('');
}

function buscarProducto(id) {
    for (const c of MENU) {
        const p = c.productos.find(x => x.id === id);
        if (p) return p;
    }
    return null;
}

// ------------------------------------------------------------------
// Configurar un producto
// ------------------------------------------------------------------
function abrirProducto(id) {
    const p = buscarProducto(id);
    config = {
        producto:   p,
        varianteId: p.variantes.length ? p.variantes[0].id : null,
        cantidad:   1,
        seleccion:  {},
        notas:      '',
    };

    // Sin variantes ni opciones (ej: agua): se agrega directo
    if (!p.variantes.length && !p.grupos.length) {
        agregarAlPedido();
        return;
    }

    renderModal();
    $('modal-producto').classList.add('abierto');
}

function cerrarModal() {
    $('modal-producto').classList.remove('abierto');
    config = null;
}

// Mismas reglas que CalculadoraPedido (el servidor recalcula siempre)
function calcular(c) {
    const p = c.producto;
    let base = p.precio;

    if (p.variantes.length) {
        const v = p.variantes.find(v => v.id === c.varianteId);
        base = v ? v.precio : 0;
    }

    let adicionales = 0;
    const errores = [];
    const resumen = [];

    for (const g of p.grupos) {
        let restantes = g.incluidos;
        let total = 0;

        for (const o of g.opciones) {
            const n = c.seleccion[o.id] || 0;
            if (!n) continue;

            total += n;
            const gratis   = Math.min(n, restantes);
            restantes     -= gratis;
            const cobrados = n - gratis;
            const precio   = o.precio ?? g.precio;
            adicionales   += cobrados * precio;

            resumen.push({ grupo: g.nombre, nombre: o.nombre, n, extra: cobrados * precio });
        }

        if (total < g.minimo) errores.push(`Elige al menos ${g.minimo} en ${g.nombre}`);
        if (g.maximo !== null && total > g.maximo) errores.push(`Máximo ${g.maximo} en ${g.nombre}`);
    }

    return { base, adicionales, unitario: base + adicionales, errores, resumen };
}

function seccion(titulo, contenido) {
    return `<div class="seccion"><div class="seccion-titulo">${titulo}</div><div class="chips">${contenido}</div></div>`;
}

function renderModal() {
    const c = config, p = c.producto, r = calcular(c);

    let html = p.foto ? `<img class="modal-foto" src="${esc(p.foto)}" alt="${esc(p.nombre)}">` : '';

    html += `
        <div class="modal-cuerpo">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div>
                <div style="font-size:22px;font-weight:bold;color:var(--titulo);">${esc(p.nombre)}</div>
                ${p.descripcion ? `<div style="font-size:13px;color:#666;margin-top:4px;">${esc(p.descripcion)}</div>` : ''}
            </div>
            <button class="btn btn-gris" onclick="cerrarModal()" aria-label="Cerrar" style="padding:6px 12px;">✕</button>
        </div>`;

    if (p.variantes.length) {
        html += seccion('Elige una opción', p.variantes.map(v => `
            <button class="chip ${c.varianteId === v.id ? 'sel' : ''}" onclick="config.varianteId = ${v.id}; renderModal();">
                ${esc(v.nombre)} · ${dinero(v.precio)}
            </button>`).join(''));
    }

    for (const g of p.grupos) {
        const elegidos = g.opciones.reduce((s, o) => s + (c.seleccion[o.id] || 0), 0);
        const ayuda = [];
        if (g.incluidos) ayuda.push(`${Math.min(elegidos, g.incluidos)} de ${g.incluidos} incluidos`);
        if (g.precio > 0 && (g.maximo === null || g.maximo > g.incluidos)) ayuda.push(`adicional ${dinero(g.precio)}`);
        if (g.maximo !== null) ayuda.push(`máx. ${g.maximo}`);
        if (g.minimo) ayuda.push('obligatorio');

        const chips = g.opciones.map(o => {
            const n = c.seleccion[o.id] || 0;
            return `<button class="chip ${n ? 'sel' : ''}" onclick="sumarOpcion(${g.id}, ${o.id})">
                        ${esc(o.nombre)}${n ? `<span class="contador">${n}</span>` : ''}
                    </button>`;
        }).join('');

        const quitar = elegidos
            ? `<button class="chip" onclick="limpiarGrupo(${g.id})" style="color:#c00;border-color:#f5c2c7;">Quitar</button>`
            : '';

        html += seccion(
            `${esc(g.nombre)} <span style="font-weight:normal;color:#888;font-size:12px;">${ayuda.join(' · ')}</span>`,
            chips + quitar
        );
    }

    html += `
        <div class="seccion">
            <div class="seccion-titulo">Nota para cocina</div>
            <input id="f-notas" class="campo" value="${esc(c.notas)}" oninput="config.notas = this.value"
                   maxlength="191" placeholder="Ej: sin salsa, poco hielo">
        </div>`;

    const deshabilitado = r.errores.length > 0;
    html += `
        <div style="display:flex;align-items:center;gap:10px;margin-top:18px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <button class="btn btn-gris" onclick="cambiarCantidad(-1)" aria-label="Menos">−</button>
                <strong style="min-width:24px;text-align:center;">${c.cantidad}</strong>
                <button class="btn btn-gris" onclick="cambiarCantidad(1)" aria-label="Más">+</button>
            </div>
            <button class="btn btn-marca" style="flex:1;font-size:17px;" onclick="agregarAlPedido()" ${deshabilitado ? 'disabled' : ''}>
                Agregar · ${dinero(r.unitario * c.cantidad)}
            </button>
        </div>`;

    if (deshabilitado) {
        html += `<div style="font-size:12px;color:#c00;margin-top:8px;">${r.errores.map(esc).join('<br>')}</div>`;
    }

    html += '</div>';
    $('modal-contenido').innerHTML = html;
}

function sumarOpcion(grupoId, opcionId) {
    const g = config.producto.grupos.find(x => x.id === grupoId);
    const elegidos = g.opciones.reduce((s, o) => s + (config.seleccion[o.id] || 0), 0);

    if (g.maximo === 1) {
        g.opciones.forEach(o => delete config.seleccion[o.id]);
        config.seleccion[opcionId] = 1;
    } else if (g.maximo !== null && elegidos >= g.maximo) {
        return;
    } else {
        config.seleccion[opcionId] = (config.seleccion[opcionId] || 0) + 1;
    }

    renderModal();
}

function limpiarGrupo(grupoId) {
    const g = config.producto.grupos.find(x => x.id === grupoId);
    g.opciones.forEach(o => delete config.seleccion[o.id]);
    renderModal();
}

function cambiarCantidad(delta) {
    config.cantidad = Math.max(1, Math.min(50, config.cantidad + delta));
    renderModal();
}

function agregarAlPedido() {
    const c = config, r = calcular(c);
    if (r.errores.length) return;

    const variante = c.producto.variantes.find(v => v.id === c.varianteId);

    carrito.push({
        uid:            Date.now() + Math.random(),
        producto:       c.producto,
        varianteId:     c.varianteId,
        varianteNombre: variante ? variante.nombre : null,
        cantidad:       c.cantidad,
        seleccion:      { ...c.seleccion },
        notas:          (c.notas || '').trim(),
        unitario:       r.unitario,
        resumen:        r.resumen,
    });

    cerrarModal();
    renderCarrito();
}

// ------------------------------------------------------------------
// Pedido
// ------------------------------------------------------------------
function totalPedido() {
    return carrito.reduce((s, i) => s + i.unitario * i.cantidad, 0);
}

function renderCarrito() {
    if (!carrito.length) {
        $('items').innerHTML = '<div style="color:#999;font-size:14px;padding:12px 0;">Elige tus productos del menú.</div>';
    } else {
        $('items').innerHTML = carrito.map((i, idx) => {
            const porGrupo = {};
            i.resumen.forEach(r => {
                (porGrupo[r.grupo] ??= []).push(`${esc(r.nombre)}${r.n > 1 ? ' ×' + r.n : ''}${r.extra ? ` <strong>+${dinero(r.extra)}</strong>` : ''}`);
            });
            const detalle = Object.entries(porGrupo)
                .map(([g, ops]) => `<div><span style="color:#888;">${esc(g)}:</span> ${ops.join(', ')}</div>`)
                .join('');

            return `
                <div class="item">
                    <div style="display:flex;justify-content:space-between;gap:8px;">
                        <strong style="font-size:15px;color:var(--titulo);">${i.cantidad} × ${esc(i.producto.nombre)}${i.varianteNombre ? ' (' + esc(i.varianteNombre) + ')' : ''}</strong>
                        <strong style="font-size:15px;white-space:nowrap;">${dinero(i.unitario * i.cantidad)}</strong>
                    </div>
                    <div style="font-size:12px;color:#555;margin-top:4px;line-height:1.5;">
                        ${detalle}
                        ${i.notas ? `<div style="color:#856404;">Nota: ${esc(i.notas)}</div>` : ''}
                    </div>
                    <div style="display:flex;gap:6px;margin-top:6px;">
                        <button class="btn btn-gris" style="padding:4px 10px;font-size:13px;" onclick="cambiarCantidadItem(${idx}, -1)" aria-label="Menos">−</button>
                        <button class="btn btn-gris" style="padding:4px 10px;font-size:13px;" onclick="cambiarCantidadItem(${idx}, 1)" aria-label="Más">+</button>
                        <button class="btn" style="padding:4px 10px;font-size:12px;background:#f8d7da;color:#721c24;margin-left:auto;" onclick="quitarItem(${idx})">Quitar</button>
                    </div>
                </div>`;
        }).join('');
    }

    $('total').textContent = dinero(totalPedido());
    renderProductos();
    renderPagos();
}

function cambiarCantidadItem(idx, delta) {
    const item = carrito[idx];
    if (delta > 0 && item.producto.tipo === 'reventa' && disponibleEnStock(item.producto) <= 0) {
        mostrarError(`No hay más ${item.producto.nombre} disponible.`);
        return;
    }
    item.cantidad = Math.max(1, Math.min(50, item.cantidad + delta));
    renderCarrito();
}

function quitarItem(idx) {
    carrito.splice(idx, 1);
    renderCarrito();
}

// ------------------------------------------------------------------
// Pagos
// ------------------------------------------------------------------
function montoPago(i) {
    const p = pagos[i];
    if (pagos.length === 1 && (p.monto === null || p.monto === '')) return totalPedido();
    return parseInt(p.monto || 0, 10);
}

function renderPagos() {
    const total = totalPedido();

    if (pagos.length === 1) {
        const p = pagos[0];
        let html = `<div style="display:flex;gap:6px;">
            ${Object.entries(METODOS).map(([k, n]) => `
                <button class="metodo ${p.metodo === k ? 'activo' : ''}" onclick="pagos[0].metodo = '${k}'; pagos[0].monto = null; renderPagos();">${n}</button>`).join('')}
        </div>`;

        if (p.metodo === 'efectivo') {
            html += `
                <div style="margin-top:10px;">
                    <label for="monto-0" style="font-size:12px;color:#666;">Recibe</label>
                    <input id="monto-0" type="number" inputmode="numeric" class="campo" value="${p.monto ?? ''}" placeholder="${total}"
                           oninput="pagos[0].monto = this.value === '' ? null : parseInt(this.value, 10); actualizarResumen();">
                    <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;">
                        ${[null, 20000, 50000, 100000].map(v => `
                            <button class="btn btn-gris" style="padding:8px 10px;font-size:12px;" onclick="pagos[0].monto = ${v}; renderPagos();">
                                ${v ? dinero(v) : 'Exacto'}
                            </button>`).join('')}
                    </div>
                </div>`;
        } else {
            html += `<input class="campo" style="margin-top:10px;" placeholder="Referencia (opcional)" maxlength="60"
                            value="${esc(p.referencia)}" oninput="pagos[0].referencia = this.value">`;
        }

        $('pagos').innerHTML = html;
        $('btn-dividir').textContent = '+ Dividir pago';
    } else {
        $('pagos').innerHTML = pagos.map((p, i) => `
            <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:6px;margin-bottom:6px;">
                <select class="campo" onchange="pagos[${i}].metodo = this.value; actualizarResumen();">
                    ${Object.entries(METODOS).map(([k, n]) => `<option value="${k}" ${p.metodo === k ? 'selected' : ''}>${n}</option>`).join('')}
                </select>
                <input type="number" inputmode="numeric" class="campo" value="${p.monto ?? ''}" placeholder="Monto"
                       oninput="pagos[${i}].monto = this.value === '' ? null : parseInt(this.value, 10); actualizarResumen();">
                <button class="btn btn-gris" onclick="quitarPago(${i})" aria-label="Quitar pago">✕</button>
            </div>`).join('');
        $('btn-dividir').textContent = '+ Agregar otro pago';
    }

    actualizarResumen();
}

function agregarPago() {
    if (pagos.length >= 4) return;

    if (pagos.length === 1 && (pagos[0].monto === null || pagos[0].monto === '')) {
        pagos[0].monto = 0;
    }

    const pagado = pagos.reduce((s, _, i) => s + montoPago(i), 0);
    const falta  = Math.max(0, totalPedido() - pagado);

    pagos.push({
        metodo:     pagos[0].metodo === 'efectivo' ? 'nequi' : 'efectivo',
        monto:      falta || null,
        referencia: '',
    });

    renderPagos();
}

function quitarPago(i) {
    pagos.splice(i, 1);
    if (pagos.length === 1) pagos[0].monto = null;
    renderPagos();
}

function estadoPago() {
    const total       = totalPedido();
    const pagado      = pagos.reduce((s, _, i) => s + montoPago(i), 0);
    const electronico = pagos.reduce((s, p, i) => s + (p.metodo !== 'efectivo' ? montoPago(i) : 0), 0);

    return {
        total,
        pagado,
        falta:  Math.max(0, total - pagado),
        cambio: Math.max(0, pagado - total),
        excedeElectronico: electronico > total,
    };
}

function actualizarResumen() {
    const e = estadoPago();
    let html = '';

    if (e.total > 0) {
        if (e.excedeElectronico) {
            html = `<div style="color:#c00;">Los pagos electrónicos no pueden superar el total.</div>`;
        } else if (e.falta > 0) {
            html = `<div style="display:flex;justify-content:space-between;color:#c00;"><span>Falta</span><strong>${dinero(e.falta)}</strong></div>`;
        } else if (e.cambio > 0) {
            html = `<div style="display:flex;justify-content:space-between;font-size:18px;"><span>Cambio</span><strong>${dinero(e.cambio)}</strong></div>`;
        } else {
            html = `<div style="color:#155724;">Pago completo</div>`;
        }
    }

    $('resumen-pago').innerHTML = html;
}

// ------------------------------------------------------------------
// Cobrar
// ------------------------------------------------------------------
function mostrarError(texto) {
    const el = $('error');
    el.style.display = texto ? 'block' : 'none';
    el.textContent = texto || '';
}

async function cobrar() {
    mostrarError('');

    const nombre = $('nombre-pedido').value.trim();
    if (!nombre) {
        mostrarError('Escribe el nombre del cliente para llamarlo.');
        $('nombre-pedido').focus();
        return;
    }
    if (!carrito.length) { mostrarError('Agrega al menos un producto.'); return; }

    const e = estadoPago();
    if (e.excedeElectronico) { mostrarError('Los pagos electrónicos no pueden superar el total.'); return; }
    if (e.falta > 0) { mostrarError('Faltan ' + dinero(e.falta) + ' por pagar.'); return; }

    const payload = {
        nombre_pedido: nombre,
        items: carrito.map(i => {
            const modificadores = [];
            i.producto.grupos.forEach(g => g.opciones.forEach(o => {
                for (let k = 0; k < (i.seleccion[o.id] || 0); k++) modificadores.push(o.id);
            }));
            return {
                producto_id:   i.producto.id,
                variante_id:   i.varianteId,
                cantidad:      i.cantidad,
                modificadores,
                notas:         i.notas || null,
            };
        }),
        pagos: pagos
            .map((p, i) => ({ metodo: p.metodo, monto: montoPago(i), referencia: p.referencia || null }))
            .filter(p => p.monto > 0),
    };

    const btn = $('btn-cobrar');
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    try {
        const res = await fetch('/ventas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!data.success) {
            mostrarError(data.mensaje ?? data.message ?? 'No se pudo registrar la venta.');
        } else {
            mostrarConfirmacion(data);
        }
    } catch (err) {
        mostrarError('No se pudo conectar. Revisa la conexión e intenta de nuevo.');
    }

    btn.disabled = false;
    btn.textContent = 'Cobrar';
}

function mostrarConfirmacion(data) {
    $('ok-contenido').innerHTML = `
        <div style="font-size:15px;color:#666;">¡Gracias! Te llamaremos como</div>
        <div style="font-size:38px;font-weight:bold;margin:10px 0;color:var(--marca);">${esc(data.etiqueta)}</div>
        <div style="font-size:17px;color:var(--titulo);">Total ${dinero(data.total)}</div>
        ${data.cambio > 0 ? `<div style="font-size:26px;font-weight:bold;margin-top:12px;background:#fff3cd;color:#856404;border-radius:12px;padding:10px;">Cambio ${dinero(data.cambio)}</div>` : ''}
        <div style="display:flex;gap:10px;margin-top:20px;">
            <button class="btn btn-gris" style="flex:1;" onclick="window.open('/ventas/${data.venta_id}/ticket', '_blank')">Imprimir tiquete</button>
            <button class="btn btn-marca" style="flex:1;" onclick="nuevaVenta()">Nueva venta</button>
        </div>`;
    $('modal-ok').classList.add('abierto');
}

function nuevaVenta() {
    carrito = [];
    pagos   = [{ metodo: 'efectivo', monto: null, referencia: '' }];
    $('nombre-pedido').value = '';
    $('modal-ok').classList.remove('abierto');
    mostrarError('');
    renderCarrito();
    $('nombre-pedido').focus();
}

// Inicio
renderTabs();
renderCarrito();
</script>
@endif
@endsection