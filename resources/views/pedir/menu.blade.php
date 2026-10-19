<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex">
<title>Pedir a domicilio · {{ $tenant->nombre }}</title>
@php
    $marca  = $tenant->color_primario   ?: '#E4387A';
    $titulo = $tenant->color_secundario ?: '#4A2C2A';
    $fondo  = $tenant->color_fondo      ?: '#FFF6EE';
@endphp
<style>
    :root { --marca: {{ $marca }}; --titulo: {{ $titulo }}; --fondo: {{ $fondo }}; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: var(--fondo); color: #222; padding-bottom: 96px; }
    button { font-family: inherit; }
    .cab { background: #fff; padding: 14px 16px 10px; display: flex; gap: 12px; align-items: center; }
    .cab img { width: 52px; height: 52px; object-fit: contain; border-radius: 12px; }
    .cab .nombre { font-size: 19px; font-weight: 800; color: var(--titulo); }
    .cab .sub { font-size: 12px; color: #666; margin-top: 2px; line-height: 1.35; }
    .tabs { position: sticky; top: 0; z-index: 5; display: flex; gap: 8px; overflow-x: auto; padding: 10px 16px; background: var(--fondo); scrollbar-width: none; }
    .tabs::-webkit-scrollbar { display: none; }
    .tab { padding: 9px 16px; border-radius: 20px; border: none; background: #fff; color: var(--titulo); font-weight: 700; font-size: 14px; white-space: nowrap; cursor: pointer; }
    .tab.activa { background: var(--marca); color: #fff; }
    .cat-titulo { font-size: 17px; font-weight: 800; color: var(--titulo); margin: 14px 16px 8px; scroll-margin-top: 60px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; padding: 0 16px; }
    .prod { background: #fff; border-radius: 14px; overflow: hidden; border: none; text-align: left; cursor: pointer; display: flex; flex-direction: column; }
    .prod .foto { height: 120px; display: flex; align-items: center; justify-content: center; background: #fff; }
    .prod .foto img { max-width: 100%; max-height: 120px; object-fit: contain; }
    .prod .foto .sin { font-size: 40px; }
    .prod .info { padding: 8px 10px 12px; }
    .prod .n { font-size: 14px; font-weight: 700; color: var(--titulo); line-height: 1.2; }
    .prod .p { font-size: 14px; font-weight: 800; color: var(--marca); margin-top: 4px; }
    .barra { position: fixed; left: 0; right: 0; bottom: 0; padding: 12px 16px calc(12px + env(safe-area-inset-bottom)); background: #fff; box-shadow: 0 -4px 16px rgba(0,0,0,.08); display: none; z-index: 10; }
    .barra.visible { display: block; }
    .btn { width: 100%; padding: 15px; border-radius: 14px; border: none; font-size: 16px; font-weight: 800; cursor: pointer; }
    .btn-marca { background: var(--marca); color: #fff; }
    .btn-marca:disabled { opacity: .45; }
    .btn-gris { background: #f0f0f0; color: #222; }
    .hoja { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: none; z-index: 20; align-items: flex-end; justify-content: center; }
    .hoja.abierta { display: flex; }
    .hoja-cuerpo { background: #fff; width: 100%; max-width: 560px; max-height: 92vh; overflow-y: auto; border-radius: 20px 20px 0 0; padding-bottom: env(safe-area-inset-bottom); }
    .hoja-foto { width: 100%; max-height: 220px; object-fit: contain; background: #fff; display: block; }
    .pad { padding: 16px; }
    .seccion { margin-top: 16px; }
    .seccion-titulo { font-size: 15px; font-weight: 800; color: var(--titulo); margin-bottom: 8px; }
    .seccion-titulo small { font-weight: 400; color: #888; font-size: 12px; }
    .chips { display: flex; flex-wrap: wrap; gap: 8px; }
    .chip { padding: 9px 13px; border-radius: 20px; border: 2px solid #e5e5e5; background: #fff; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .chip.sel { border-color: var(--marca); background: color-mix(in srgb, var(--marca) 10%, #fff); font-weight: 700; }
    .contador { background: var(--marca); color: #fff; border-radius: 10px; padding: 0 7px; font-size: 12px; }
    .campo { width: 100%; padding: 12px; border: 2px solid #e5e5e5; border-radius: 12px; font-size: 16px; font-family: inherit; }
    .cant { display: flex; align-items: center; gap: 10px; }
    .cant button { width: 44px; height: 44px; border-radius: 12px; border: none; background: #f0f0f0; font-size: 20px; cursor: pointer; }
    .linea { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
    .linea .det { font-size: 12px; color: #777; margin-top: 2px; line-height: 1.35; }
    .total { display: flex; justify-content: space-between; font-size: 14px; color: #555; margin-top: 6px; }
    .total.grande { font-size: 19px; font-weight: 800; color: #222; }
    .error { color: #c00; font-size: 13px; margin-top: 8px; }
    .ok { text-align: center; padding: 40px 20px; }
    .ok .icono { font-size: 64px; }
</style>
</head>
<body>

<div class="cab">
    @if($tenant->logo)
        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="">
    @endif
    <div>
        <div class="nombre">{{ $tenant->nombre }}</div>
        <div class="sub">
            🛵 Domicilio desde sede {{ $sede?->nombre }} · {{ $conv->costo_domicilio > 0 ? '$' . number_format($conv->costo_domicilio, 0, ',', '.') : 'gratis' }}<br>
            📍 {{ $conv->direccion }}
        </div>
    </div>
</div>

<div id="app">
    <div class="tabs" id="tabs"></div>
    <div id="catalogo"></div>
</div>

<div class="barra" id="barra">
    <button class="btn btn-marca" onclick="abrirCarrito()" id="btn-barra">Ver pedido</button>
</div>

<div class="hoja" id="hoja" onclick="if (event.target === this) cerrarHoja()">
    <div class="hoja-cuerpo" id="hoja-cuerpo" role="dialog" aria-modal="true"></div>
</div>

<script>
const MENU = @json($menu);
const DOMICILIO = {{ (int) $conv->costo_domicilio }};
const MINIMO = {{ (int) ($tenant->domicilio_minimo ?? 0) }};
const NOMBRE = @json($conv->nombre ?? '');
const URL_ENVIO = @json(url('/pedir/' . $conv->token));
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');

let carrito = [];
let config = null;

function buscarProducto(id) {
    for (const c of MENU) { const p = c.productos.find(x => x.id === id); if (p) return p; }
    return null;
}

// ------------------------------------------------------------------
// Catálogo
// ------------------------------------------------------------------
function precioDesde(p) {
    return p.variantes.length ? Math.min(...p.variantes.map(v => v.precio)) : p.precio;
}

function renderCatalogo() {
    $('tabs').innerHTML = MENU.map(c => `<button class="tab" data-cat="${c.id}" onclick="irA(${c.id})">${esc(c.nombre)}</button>`).join('');
    $('catalogo').innerHTML = MENU.length ? MENU.map(c => `
        <h2 class="cat-titulo" id="cat-${c.id}">${esc(c.nombre)}</h2>
        <div class="grid">
            ${c.productos.map(p => `
                <button class="prod" onclick="abrirProducto(${p.id})">
                    <div class="foto">${p.foto ? `<img src="${esc(p.foto)}" alt="${esc(p.nombre)}" loading="lazy">` : '<span class="sin">🍨</span>'}</div>
                    <div class="info">
                        <div class="n">${esc(p.nombre)}</div>
                        <div class="p">${p.variantes.length ? 'Desde ' : ''}${dinero(precioDesde(p))}</div>
                    </div>
                </button>`).join('')}
        </div>`).join('') : '<p style="padding:24px;text-align:center;color:#888;">No hay productos disponibles a domicilio en este momento.</p>';
    marcarTab();
}

function irA(id) {
    document.getElementById('cat-' + id).scrollIntoView({ behavior: 'smooth' });
}

function marcarTab() {
    let actual = MENU[0]?.id;
    for (const c of MENU) {
        const el = document.getElementById('cat-' + c.id);
        if (el && el.getBoundingClientRect().top < 120) actual = c.id;
    }
    document.querySelectorAll('.tab').forEach(t => t.classList.toggle('activa', Number(t.dataset.cat) === actual));
}
window.addEventListener('scroll', marcarTab, { passive: true });

// ------------------------------------------------------------------
// Configurar producto (mismas reglas que CalculadoraPedido; el servidor recalcula)
// ------------------------------------------------------------------
function abrirProducto(id) {
    const p = buscarProducto(id);
    config = { producto: p, varianteId: p.variantes[0]?.id ?? null, cantidad: 1, seleccion: {}, notas: '' };
    renderProducto();
    $('hoja').classList.add('abierta');
}

function cerrarHoja() {
    $('hoja').classList.remove('abierta');
    config = null;
}

function calcular(c) {
    const p = c.producto;
    let base = p.precio;
    if (p.variantes.length) {
        const v = p.variantes.find(v => v.id === c.varianteId);
        base = v ? v.precio : 0;
    }
    let adicionales = 0;
    const errores = [], resumen = [];
    for (const g of p.grupos) {
        let restantes = g.incluidos, total = 0;
        for (const o of g.opciones) {
            const n = c.seleccion[o.id] || 0;
            if (!n) continue;
            total += n;
            const gratis = Math.min(n, restantes);
            restantes -= gratis;
            const cobrados = n - gratis;
            adicionales += cobrados * (o.precio ?? g.precio);
            resumen.push({ grupo: g.nombre, nombre: o.nombre, n });
        }
        if (total < g.minimo) errores.push(`Elige al menos ${g.minimo} en ${g.nombre}`);
        if (g.maximo !== null && total > g.maximo) errores.push(`Máximo ${g.maximo} en ${g.nombre}`);
    }
    return { unitario: base + adicionales, errores, resumen };
}

function renderProducto() {
    const c = config, p = c.producto, r = calcular(c);
    let html = p.foto ? `<img class="hoja-foto" src="${esc(p.foto)}" alt="">` : '';
    html += `<div class="pad">
        <div style="display:flex;justify-content:space-between;gap:10px;">
            <div>
                <div style="font-size:21px;font-weight:800;color:var(--titulo);">${esc(p.nombre)}</div>
                ${p.descripcion ? `<div style="font-size:13px;color:#666;margin-top:4px;">${esc(p.descripcion)}</div>` : ''}
            </div>
            <button class="btn-gris" style="border:none;border-radius:10px;padding:6px 12px;height:36px;cursor:pointer;" onclick="cerrarHoja()" aria-label="Cerrar">✕</button>
        </div>`;

    if (p.variantes.length) {
        html += `<div class="seccion"><div class="seccion-titulo">Tamaño</div><div class="chips">` + p.variantes.map(v => `
            <button class="chip ${c.varianteId === v.id ? 'sel' : ''}" onclick="config.varianteId=${v.id};renderProducto()">${esc(v.nombre)} · ${dinero(v.precio)}</button>`).join('') + '</div></div>';
    }

    for (const g of p.grupos) {
        if (!g.opciones.length) continue;
        const elegidos = g.opciones.reduce((s, o) => s + (c.seleccion[o.id] || 0), 0);
        const ayuda = [];
        if (g.incluidos) ayuda.push(`${Math.min(elegidos, g.incluidos)} de ${g.incluidos} incluidos`);
        if (g.precio > 0 && (g.maximo === null || g.maximo > g.incluidos)) ayuda.push(`adicional ${dinero(g.precio)}`);
        if (g.maximo !== null) ayuda.push(`máx. ${g.maximo}`);
        if (g.minimo) ayuda.push('obligatorio');
        html += `<div class="seccion"><div class="seccion-titulo">${esc(g.nombre)} <small>${ayuda.join(' · ')}</small></div><div class="chips">`
            + g.opciones.map(o => {
                const n = c.seleccion[o.id] || 0;
                return `<button class="chip ${n ? 'sel' : ''}" onclick="sumarOpcion(${g.id},${o.id})">${esc(o.nombre)}${n ? `<span class="contador">${n}</span>` : ''}</button>`;
            }).join('')
            + (elegidos ? `<button class="chip" style="color:#c00;" onclick="limpiarGrupo(${g.id})">Quitar</button>` : '')
            + '</div></div>';
    }

    html += `<div class="seccion"><div class="seccion-titulo">¿Alguna indicación?</div>
        <input class="campo" maxlength="150" value="${esc(c.notas)}" oninput="config.notas=this.value" placeholder="Ej: sin salsa"></div>
        <div style="display:flex;gap:10px;align-items:center;margin-top:18px;">
            <div class="cant"><button onclick="cambiarCantidad(-1)" aria-label="Menos">−</button><strong>${c.cantidad}</strong><button onclick="cambiarCantidad(1)" aria-label="Más">+</button></div>
            <button class="btn btn-marca" style="flex:1;" onclick="agregar()" ${r.errores.length ? 'disabled' : ''}>Agregar · ${dinero(r.unitario * c.cantidad)}</button>
        </div>
        ${r.errores.length ? `<div class="error">${r.errores.map(esc).join('<br>')}</div>` : ''}
    </div>`;
    $('hoja-cuerpo').innerHTML = html;
}

function sumarOpcion(gid, oid) {
    const g = config.producto.grupos.find(x => x.id === gid);
    const elegidos = g.opciones.reduce((s, o) => s + (config.seleccion[o.id] || 0), 0);
    if (g.maximo === 1) {
        g.opciones.forEach(o => delete config.seleccion[o.id]);
        config.seleccion[oid] = 1;
    } else if (g.maximo !== null && elegidos >= g.maximo) {
        return;
    } else {
        config.seleccion[oid] = (config.seleccion[oid] || 0) + 1;
    }
    renderProducto();
}

function limpiarGrupo(gid) {
    config.producto.grupos.find(x => x.id === gid).opciones.forEach(o => delete config.seleccion[o.id]);
    renderProducto();
}

function cambiarCantidad(d) {
    config.cantidad = Math.max(1, Math.min(20, config.cantidad + d));
    renderProducto();
}

function agregar() {
    const c = config, r = calcular(c);
    if (r.errores.length) return;
    const v = c.producto.variantes.find(v => v.id === c.varianteId);
    carrito.push({
        producto_id: c.producto.id,
        variante_id: c.varianteId,
        cantidad: c.cantidad,
        modificadores: Object.entries(c.seleccion).flatMap(([id, n]) => Array(n).fill(Number(id))),
        notas: c.notas.trim() || null,
        _nombre: c.producto.nombre + (v ? ` (${v.nombre})` : ''),
        _detalle: r.resumen.map(x => (x.n > 1 ? x.n + '× ' : '') + x.nombre).join(', '),
        _unitario: r.unitario,
    });
    cerrarHoja();
    actualizarBarra();
}

// ------------------------------------------------------------------
// Carrito y envío
// ------------------------------------------------------------------
const subtotal = () => carrito.reduce((s, i) => s + i._unitario * i.cantidad, 0);

function actualizarBarra() {
    const n = carrito.reduce((s, i) => s + i.cantidad, 0);
    $('barra').classList.toggle('visible', n > 0);
    $('btn-barra').textContent = `Ver pedido (${n}) · ${dinero(subtotal() + DOMICILIO)}`;
}

function abrirCarrito() {
    const sub = subtotal();
    const faltaMinimo = MINIMO > 0 && sub < MINIMO;
    $('hoja-cuerpo').innerHTML = `<div class="pad">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div style="font-size:20px;font-weight:800;color:var(--titulo);">Tu pedido</div>
            <button class="btn-gris" style="border:none;border-radius:10px;padding:6px 12px;cursor:pointer;" onclick="cerrarHoja()" aria-label="Cerrar">✕</button>
        </div>
        <div style="margin-top:8px;">
            ${carrito.map((i, idx) => `
                <div class="linea">
                    <div><strong>${i.cantidad}× ${esc(i._nombre)}</strong>
                        ${i._detalle ? `<div class="det">${esc(i._detalle)}</div>` : ''}
                        ${i.notas ? `<div class="det">Nota: ${esc(i.notas)}</div>` : ''}
                    </div>
                    <div style="text-align:right;white-space:nowrap;">${dinero(i._unitario * i.cantidad)}<br>
                        <button onclick="quitar(${idx})" style="border:none;background:none;color:#c00;font-size:12px;cursor:pointer;margin-top:4px;">Quitar</button></div>
                </div>`).join('')}
        </div>
        <div class="total"><span>Subtotal</span><span>${dinero(sub)}</span></div>
        <div class="total"><span>Domicilio</span><span>${DOMICILIO > 0 ? dinero(DOMICILIO) : 'Gratis'}</span></div>
        <div class="total grande" style="margin-top:10px;"><span>Total</span><span>${dinero(sub + DOMICILIO)}</span></div>
        ${faltaMinimo ? `<div class="error">El pedido mínimo a domicilio es ${dinero(MINIMO)} (sin contar el domicilio).</div>` : ''}
        <div class="seccion"><div class="seccion-titulo">¿A nombre de quién?</div>
            <input class="campo" id="f-nombre" maxlength="60" value="${esc(NOMBRE)}" placeholder="Tu nombre"></div>
        <div class="seccion"><div class="seccion-titulo">Indicaciones para la entrega (opcional)</div>
            <input class="campo" id="f-notas" maxlength="300" placeholder="Ej: timbre dañado, llamar al llegar"></div>
        <div id="f-error" class="error"></div>
        <button class="btn btn-marca" id="btn-enviar" style="margin-top:16px;" onclick="enviar()" ${faltaMinimo || !carrito.length ? 'disabled' : ''}>Enviar pedido</button>
        <p style="font-size:12px;color:#888;text-align:center;margin-top:10px;">Al enviarlo te llegará por WhatsApp cómo pagar por Nequi o Daviplata.</p>
    </div>`;
    $('hoja').classList.add('abierta');
}

function quitar(idx) {
    carrito.splice(idx, 1);
    actualizarBarra();
    if (carrito.length) abrirCarrito(); else cerrarHoja();
}

async function enviar() {
    const nombre = $('f-nombre').value.trim();
    if (!nombre) { $('f-error').textContent = 'Escribe tu nombre.'; return; }
    const btn = $('btn-enviar');
    btn.disabled = true;
    btn.textContent = 'Enviando…';
    try {
        const res = await fetch(URL_ENVIO, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                nombre,
                notas: $('f-notas').value.trim() || null,
                items: carrito.map(({ producto_id, variante_id, cantidad, modificadores, notas }) => ({ producto_id, variante_id, cantidad, modificadores, notas })),
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? 'No se pudo enviar el pedido. Intenta de nuevo.');
        }
        cerrarHoja();
        $('barra').classList.remove('visible');
        $('app').innerHTML = `<div class="ok">
            <div class="icono">🎉</div>
            <h1 style="font-size:22px;color:var(--titulo);margin:10px 0;">¡Pedido enviado!</h1>
            <p style="color:#555;line-height:1.5;">Total: <strong>${dinero(data.total)}</strong><br>Te enviamos por WhatsApp los datos para pagar.
               Envía allí la captura del pago y empezamos a prepararlo.</p>
            ${data.whatsapp ? `<a href="${esc(data.whatsapp)}" class="btn btn-marca" style="display:block;margin-top:20px;text-decoration:none;">Volver a WhatsApp</a>` : ''}
        </div>`;
        window.scrollTo(0, 0);
    } catch (e) {
        $('f-error').textContent = e.message;
        btn.disabled = false;
        btn.textContent = 'Enviar pedido';
    }
}

renderCatalogo();
</script>
</body>
</html>
