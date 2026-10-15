@extends('layouts.pos')

@section('titulo', 'Registrar factura')

@section('contenido')
@php
    $input = 'width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;';
    $label = 'display:block;font-size:12px;color:#555;margin-bottom:4px;';
@endphp

<style>
    .linea { background:#fff;border-radius:12px;padding:14px;margin-bottom:10px;border:2px solid transparent; }
    .linea.con-error, .linea.pendiente { border-color:#f0c36d; }
    .linea.con-error { border-color:#e0a0a0; }
    .linea.servicio { background:#fafafa; }
    .linea-grid { display:grid;grid-template-columns:2fr 1fr 1.2fr 1.3fr 1.3fr;gap:10px;align-items:end; }
    .linea.servicio .solo-inventario { visibility:hidden; }
    .linea-pie { display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px; }
    .resultado { font-size:13px;color:#155724;background:#eef8f0;padding:6px 10px;border-radius:6px; }
    .chip { font-size:11px;font-weight:bold;padding:3px 8px;border-radius:10px;white-space:nowrap; }
    .ia-card { background:linear-gradient(135deg,#fff 0%,#f3fbf8 100%);border:2px dashed #27A88A;border-radius:12px;padding:16px;margin-bottom:14px; }
    @media (max-width: 760px) {
        .linea-grid { grid-template-columns:1fr 1fr; }
        .linea-grid .col-insumo { grid-column:1 / -1; }
    }
</style>

<div style="padding:16px;max-width:1000px;margin:0 auto;">
    <a href="/compras" style="font-size:13px;color:#555;text-decoration:none;">← Volver a compras</a>
    <h1 style="font-size:22px;margin:8px 0 2px;">Registrar factura de proveedor</h1>
    <div style="font-size:13px;color:#888;margin-bottom:14px;">
        Lo que registres entra a las existencias de la sede <strong>{{ $sede?->nombre }}</strong>.
    </div>

    {{-- Lectura con IA --}}
    <div class="ia-card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <div style="font-weight:bold;font-size:16px;">Leer factura con IA</div>
                <div style="font-size:13px;color:#555;margin-top:2px;">
                    Sube el PDF de la factura electrónica o una foto clara. Llenamos el formulario y tú lo revisas.
                </div>
            </div>
            <label style="padding:12px 18px;background:#27A88A;color:#fff;border-radius:10px;font-weight:bold;cursor:pointer;white-space:nowrap;">
                Subir PDF o foto
                <input type="file" id="ia-archivo" accept="application/pdf,image/*" onchange="leerFactura(this)" style="display:none;">
            </label>
        </div>
        <div id="ia-estado" style="display:none;margin-top:12px;font-size:14px;"></div>
    </div>

    <div style="background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="{{ $label }}" for="proveedor">Proveedor</label>
                <select id="proveedor" onchange="cambioProveedor()" style="{{ $input }}">
                    <option value="">Sin proveedor</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </select>
                <a href="/proveedores/crear" style="font-size:12px;color:#555;">+ Crear proveedor</a>
            </div>
            <div>
                <label style="{{ $label }}" for="numero">Número de factura</label>
                <input id="numero" maxlength="60" placeholder="Ej: FE-10234" style="{{ $input }}">
            </div>
            <div>
                <label style="{{ $label }}" for="fecha">Fecha de la factura</label>
                <input id="fecha" type="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" style="{{ $input }}">
            </div>
        </div>

        {{-- Proveedor leído que aún no existe --}}
        <div id="prov-nuevo" style="display:none;margin-top:12px;padding:12px;background:#fff8e1;border-radius:8px;">
            <div style="font-size:13px;color:#6b5200;margin-bottom:8px;">Proveedor nuevo: se creará al registrar la factura.</div>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                <input id="prov-nuevo-nombre" maxlength="191" placeholder="Nombre del proveedor" aria-label="Nombre del proveedor" style="{{ $input }}">
                <input id="prov-nuevo-nit" maxlength="20" placeholder="NIT" aria-label="NIT del proveedor" style="{{ $input }}">
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <h2 style="font-size:17px;">Productos de la factura</h2>
        <button type="button" onclick="abrirNuevoInsumo(null)"
            style="padding:8px 12px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;cursor:pointer;">
            + Nuevo insumo
        </button>
    </div>

    <div id="lineas"></div>

    <button type="button" onclick="agregarLinea()"
        style="width:100%;padding:12px;background:#fff;border:2px dashed #bbb;border-radius:12px;font-size:15px;cursor:pointer;margin-bottom:14px;">
        + Agregar otra línea
    </button>

    <div style="background:#fff;border-radius:12px;padding:16px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:end;">
            <div>
                <label style="{{ $label }}" for="impuestos">Impuestos (IVA, ICUI u otros)</label>
                <input id="impuestos" type="number" min="0" step="1" inputmode="numeric" placeholder="0" oninput="recalcular()" style="{{ $input }}">
                <div id="impuestos-detalle" style="font-size:12px;color:#888;margin-top:4px;"></div>
            </div>
            <div>
                <label style="{{ $label }}" for="notas">Notas (opcional)</label>
                <input id="notas" maxlength="500" style="{{ $input }}">
            </div>
        </div>

        <label style="display:flex;gap:8px;align-items:flex-start;margin-top:12px;font-size:14px;cursor:pointer;">
            <input type="checkbox" id="impuestos-costo" checked style="margin-top:3px;">
            <span>
                Sumar los impuestos al costo de los insumos
                <span style="display:block;font-size:12px;color:#888;">
                    Déjalo marcado si el negocio no descuenta el IVA de sus compras. El ICUI siempre es costo.
                </span>
            </span>
        </label>

        <div style="display:flex;justify-content:space-between;margin-top:16px;font-size:14px;color:#555;">
            <span>Subtotal</span><span id="t-subtotal">$0</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:20px;font-weight:bold;">
            <span>Total factura</span><span id="t-total">$0</span>
        </div>
        <div id="t-cuadre" style="display:none;margin-top:8px;font-size:13px;padding:8px 10px;border-radius:6px;"></div>

        <div id="error" style="display:none;margin-top:12px;padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:14px;"></div>

        <button id="btn-guardar" type="button" onclick="guardar()"
            style="width:100%;margin-top:14px;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:17px;font-weight:bold;cursor:pointer;">
            Registrar factura y sumar a existencias
        </button>
    </div>
</div>

{{-- Ventana: nuevo insumo --}}
<div id="modal-insumo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <div role="dialog" aria-modal="true" style="background:#fff;border-radius:14px;padding:20px;width:100%;max-width:420px;">
        <h2 style="font-size:18px;margin-bottom:12px;">Nuevo insumo</h2>
        <label style="{{ $label }}" for="ni-nombre">Nombre</label>
        <input id="ni-nombre" maxlength="120" placeholder="Ej: Leche condensada" style="{{ $input }}margin-bottom:10px;">
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
                    @foreach($categorias as $c)
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
let INSUMOS = @json($insumos->map(fn ($i) => ['id' => $i->id, 'nombre' => $i->nombre, 'unidad' => $i->unidad])->values());
const UNIDAD_TXT = { g: 'g', ml: 'ml', und: 'und' };
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let siguienteId = 1;
let lineaParaInsumo = null;   // línea que espera el insumo recién creado
let archivoToken = null;      // archivo leído con IA
let totalLeido = null;        // total impreso en la factura

const $ = id => document.getElementById(id);
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

function formatear(cantidad, unidad) {
    if (unidad !== 'und' && Math.abs(cantidad) >= 1000) {
        return (cantidad / 1000).toLocaleString('es-CO', { maximumFractionDigits: 2 }) + (unidad === 'g' ? ' kg' : ' L');
    }
    return cantidad.toLocaleString('es-CO', { maximumFractionDigits: 2 }) + ' ' + UNIDAD_TXT[unidad];
}

// ------------------------------------------------------------------
// Cuánto trae cada unidad de la factura, en la unidad del insumo.
// Ej: "Kilogramo" → 1000 g · "CAJA ... x 1000" → 1000 und · "BOLSA X 5KG" → 5000 g
// ------------------------------------------------------------------
function sugerirFactor(descripcion, unidadFactura, unidadInsumo) {
    const t = (String(descripcion || '') + ' ' + String(unidadFactura || '')).toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '');
    const num = s => Number(String(s).replace(',', '.'));
    let m;

    if (unidadInsumo === 'g') {
        if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*(kg|kilos?|kgs)\b/))) return num(m[1]) * 1000;
        if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*(g|gr|grs|gramos?)\b/))) return num(m[1]);
        if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*(lb|libras?)\b/))) return num(m[1]) * 500;
        if (/\b(kilogramos?|kg|kilos?)\b/.test(t)) return 1000;
        if (/\b(libras?|lb)\b/.test(t)) return 500;
    }
    if (unidadInsumo === 'ml') {
        if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*(l|lt|lts|litros?)\b/))) return num(m[1]) * 1000;
        if ((m = t.match(/(\d+(?:[.,]\d+)?)\s*(ml|cc)\b/))) return num(m[1]);
        if (/\b(galon|galones|gl)\b/.test(t)) return 3785;
        if (/\b(litros?|lt|lts)\b/.test(t)) return 1000;
    }
    if (unidadInsumo === 'und') {
        if ((m = t.match(/\bx\s*(\d+)\b(?!\s*(kg|g|gr|ml|l|lt|cc|oz)\b)/))) return num(m[1]);
        if ((m = t.match(/\b(\d+)\s*(und|unidades|uds)\b/)) && !/^\s*\d/.test(String(unidadFactura || ''))) return num(m[1]) > 1 ? num(m[1]) : 1;
    }
    return 1;
}

// ------------------------------------------------------------------
// Líneas
// ------------------------------------------------------------------
function opcionesInsumo(seleccionado) {
    return '<option value="">Elige el insumo…</option>' + INSUMOS.map(i =>
        `<option value="${i.id}" ${i.id === seleccionado ? 'selected' : ''}>${esc(i.nombre)} (${UNIDAD_TXT[i.unidad]})</option>`
    ).join('')
    + `<option value="ninguno" ${seleccionado === 'ninguno' ? 'selected' : ''}>— No es inventario (domicilio, servicio)</option>`
    + '<option value="nuevo">+ Crear insumo nuevo…</option>';
}

function agregarLinea(datos = null) {
    const id = siguienteId++;
    const div = document.createElement('div');
    div.className = 'linea';
    div.id = 'linea-' + id;
    div.dataset.id = id;
    div.dataset.factorManual = datos && datos.factor ? '1' : '';
    div.dataset.coincidencia = datos?.coincidencia || '';

    const seleccion = datos ? (datos.es_servicio ? 'ninguno' : (datos.insumo_id || null)) : null;
    const v = (x) => x === null || x === undefined ? '' : esc(x);

    div.innerHTML = `
        <div class="linea-grid">
            <div class="col-insumo">
                <label style="display:flex;justify-content:space-between;gap:6px;font-size:12px;color:#555;margin-bottom:4px;">
                    <span>Insumo</span><span class="l-chip"></span>
                </label>
                <select class="l-insumo" onchange="cambioInsumo(${id}, this)" style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
                    ${opcionesInsumo(seleccion)}
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Cantidad comprada</label>
                <input class="l-cantidad" type="number" min="0" step="any" inputmode="decimal" placeholder="Ej: 2" oninput="recalcular()"
                       value="${v(datos?.cantidad_factura)}"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div class="solo-inventario">
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Presentación</label>
                <input class="l-unidad-factura" maxlength="30" placeholder="caja, bolsa, galón" value="${v(datos?.unidad_factura)}"
                       oninput="sugerirSiAuto(${id})"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div class="solo-inventario">
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Cada una trae (<span class="l-u">—</span>)</label>
                <input class="l-factor" type="number" min="0" step="any" inputmode="decimal" placeholder="Ej: 5000"
                       value="${v(datos?.factor ?? 1)}" oninput="this.closest('.linea').dataset.factorManual = '1'; recalcular()"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Valor total línea</label>
                <input class="l-costo" type="number" min="0" step="1" inputmode="numeric" placeholder="$" oninput="recalcular()"
                       value="${v(datos?.costo_total)}"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
        </div>
        <div class="linea-pie">
            <input class="l-descripcion" maxlength="191" placeholder="Cómo aparece en la factura (opcional)" value="${v(datos?.descripcion)}"
                   oninput="sugerirSiAuto(${id})"
                   style="flex:1;min-width:200px;padding:8px;font-size:13px;border:1px solid #ddd;border-radius:6px;">
            <span class="resultado l-resultado" style="display:none;"></span>
            <button type="button" onclick="quitarLinea(${id})" aria-label="Quitar línea"
                style="padding:6px 10px;background:#f0f0f0;border:none;border-radius:6px;font-size:12px;cursor:pointer;">Quitar</button>
        </div>`;
    $('lineas').appendChild(div);

    if (datos && datos.insumo_id && !datos.factor) sugerirSiAuto(id);
    recalcular();
}

function quitarLinea(id) {
    $('linea-' + id)?.remove();
    if (!document.querySelector('.linea')) agregarLinea();
    recalcular();
}

function insumoDe(linea) {
    const v = Number(linea.querySelector('.l-insumo').value);
    return INSUMOS.find(i => i.id === v) || null;
}

function esServicio(linea) {
    return linea.querySelector('.l-insumo').value === 'ninguno';
}

// Si el usuario no ha escrito el factor a mano, se propone según la descripción y la presentación
function sugerirSiAuto(id) {
    const linea = $('linea-' + id);
    if (!linea || linea.dataset.factorManual) { recalcular(); return; }
    const ins = insumoDe(linea);
    if (ins) {
        linea.querySelector('.l-factor').value = sugerirFactor(
            linea.querySelector('.l-descripcion').value,
            linea.querySelector('.l-unidad-factura').value,
            ins.unidad
        );
    }
    recalcular();
}

function cambioInsumo(id, select) {
    const linea = $('linea-' + id);
    if (select.value === 'nuevo') {
        select.value = '';
        abrirNuevoInsumo(id);
        return;
    }
    linea.dataset.coincidencia = select.value ? 'manual' : '';
    linea.dataset.factorManual = '';
    if (select.value === 'ninguno') linea.querySelector('.l-factor').value = 1;
    sugerirSiAuto(id);
}

function leerLineas() {
    return [...document.querySelectorAll('.linea')].map(linea => ({
        el: linea,
        insumo: insumoDe(linea),
        servicio: esServicio(linea),
        cantidad_factura: Number(linea.querySelector('.l-cantidad').value) || 0,
        unidad_factura: linea.querySelector('.l-unidad-factura').value.trim(),
        factor: Number(linea.querySelector('.l-factor').value) || 0,
        costo_total: Math.round(Number(linea.querySelector('.l-costo').value) || 0),
        descripcion: linea.querySelector('.l-descripcion').value.trim(),
    }));
}

function recalcular() {
    let subtotal = 0;
    leerLineas().forEach(l => {
        subtotal += l.costo_total;
        const el = l.el;
        el.classList.toggle('servicio', l.servicio);
        el.classList.toggle('pendiente', !l.insumo && !l.servicio && (l.cantidad_factura > 0 || l.costo_total > 0));
        el.querySelector('.l-u').textContent = l.insumo ? UNIDAD_TXT[l.insumo.unidad] : '—';

        // Etiqueta de cómo se reconoció el insumo
        const chip = el.querySelector('.l-chip');
        const c = el.dataset.coincidencia;
        chip.innerHTML = l.servicio ? '<span class="chip" style="background:#f0f0f0;color:#555;">No suma a inventario</span>'
            : c === 'alias' && l.insumo ? '<span class="chip" style="background:#d4edda;color:#155724;">Reconocido</span>'
            : c === 'sugerido' && l.insumo ? '<span class="chip" style="background:#fff3cd;color:#856404;">Sugerido · confirma</span>'
            : !l.insumo && archivoToken ? '<span class="chip" style="background:#fff3cd;color:#856404;">Elige el insumo</span>'
            : '';

        const res = el.querySelector('.l-resultado');
        const total = l.cantidad_factura * l.factor;
        if (l.insumo && total > 0) {
            let costo = '';
            if (l.costo_total > 0) {
                const unit = l.costo_total / total;
                costo = l.insumo.unidad === 'und'
                    ? ` · ${dinero(unit)}/und`
                    : ` · ${dinero(unit * 1000)}/${l.insumo.unidad === 'g' ? 'kg' : 'L'}`;
            }
            if (costo && Number($('impuestos').value) > 0) costo += ' antes de impuestos';
            res.textContent = 'Entran ' + formatear(total, l.insumo.unidad) + costo;
            res.style.display = 'inline-block';
        } else {
            res.style.display = 'none';
        }
    });

    const imp = Math.round(Number($('impuestos').value) || 0);
    $('t-subtotal').textContent = dinero(subtotal);
    $('t-total').textContent = dinero(subtotal + imp);

    // Comparar con el total impreso en la factura leída
    const cuadre = $('t-cuadre');
    if (totalLeido !== null) {
        const dif = subtotal + imp - totalLeido;
        cuadre.style.display = 'block';
        if (Math.abs(dif) <= 2) {
            cuadre.style.background = '#d4edda'; cuadre.style.color = '#155724';
            cuadre.textContent = `Cuadra con el total de la factura (${dinero(totalLeido)}).`;
        } else {
            cuadre.style.background = '#fff3cd'; cuadre.style.color = '#856404';
            cuadre.textContent = `La factura dice ${dinero(totalLeido)}: hay una diferencia de ${dinero(Math.abs(dif))}. Revisa valores e impuestos.`;
        }
    } else {
        cuadre.style.display = 'none';
    }
}

// ------------------------------------------------------------------
// Proveedor
// ------------------------------------------------------------------
function cambioProveedor() {
    $('prov-nuevo').style.display = $('proveedor').value === 'nuevo' ? 'block' : 'none';
}

function proveedorNuevoOpcion(nombre) {
    let opt = $('proveedor').querySelector('option[value="nuevo"]');
    if (!opt) {
        opt = document.createElement('option');
        opt.value = 'nuevo';
        $('proveedor').insertBefore(opt, $('proveedor').options[1] || null);
    }
    opt.textContent = 'Nuevo: ' + (nombre || 'proveedor de la factura');
}

// ------------------------------------------------------------------
// Lectura con IA
// ------------------------------------------------------------------

// Fotos grandes: se reducen en el navegador para que suban rápido
async function prepararArchivo(file) {
    if (!file.type.startsWith('image/') || file.size < 2.5 * 1024 * 1024) return file;
    try {
        const img = await createImageBitmap(file);
        const escala = Math.min(1, 2200 / Math.max(img.width, img.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(img.width * escala);
        canvas.height = Math.round(img.height * escala);
        canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.85));
        return blob ? new File([blob], 'factura.jpg', { type: 'image/jpeg' }) : file;
    } catch (e) {
        return file;
    }
}

function estadoIA(html, tipo) {
    const box = $('ia-estado');
    const colores = { info: ['#e7f1ff', '#0c447c'], ok: ['#d4edda', '#155724'], error: ['#f8d7da', '#721c24'] };
    box.style.display = 'block';
    box.style.padding = '10px';
    box.style.borderRadius = '8px';
    box.style.background = colores[tipo][0];
    box.style.color = colores[tipo][1];
    box.innerHTML = html;
}

async function leerFactura(input) {
    const original = input.files[0];
    input.value = '';
    if (!original) return;

    const hayDatos = leerLineas().some(l => l.insumo || l.costo_total || l.cantidad_factura);
    if (hayDatos && !confirm('Se reemplazarán las líneas que ya escribiste. ¿Continuar?')) return;

    estadoIA('Leyendo la factura… puede tardar entre 10 y 40 segundos.', 'info');

    try {
        const archivo = await prepararArchivo(original);
        const fd = new FormData();
        fd.append('archivo', archivo);

        const res = await fetch('/compras/leer', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo leer la factura.');
        }

        llenarFormulario(data.datos);
        archivoToken = data.token;

        const lineas = data.datos.lineas;
        const pendientes = lineas.filter(l => !l.es_servicio && !l.insumo_id).length;
        const sugeridas = lineas.filter(l => l.coincidencia === 'sugerido').length;
        let msg = `<strong>Factura leída: ${lineas.length} línea${lineas.length === 1 ? '' : 's'}.</strong> Revisa los datos antes de registrar.`;
        if (pendientes) msg += `<br>${pendientes} línea${pendientes === 1 ? '' : 's'} sin insumo: elígelo o créalo. La próxima vez se reconocerá sola.`;
        if (sugeridas) msg += `<br>${sugeridas} insumo${sugeridas === 1 ? '' : 's'} sugerido${sugeridas === 1 ? '' : 's'} por parecido: confírmalo.`;
        msg += '<br>Revisa en cada línea cuánto trae cada presentación (ej. 1 kilo = 1000 g).';
        estadoIA(msg, 'ok');
        recalcular();
    } catch (e) {
        estadoIA(esc(e.message), 'error');
    }
}

function llenarFormulario(d) {
    // Proveedor
    if (d.proveedor.id) {
        $('proveedor').value = String(d.proveedor.id);
        $('proveedor').querySelector('option[value="nuevo"]')?.remove();
    } else if (d.proveedor.nombre) {
        proveedorNuevoOpcion(d.proveedor.nombre);
        $('proveedor').value = 'nuevo';
        $('prov-nuevo-nombre').value = d.proveedor.nombre;
        $('prov-nuevo-nit').value = d.proveedor.nit || '';
    } else {
        $('proveedor').value = '';
    }
    cambioProveedor();

    $('numero').value = d.numero_factura || '';
    if (d.fecha) $('fecha').value = d.fecha;

    // Impuestos
    $('impuestos').value = d.total_impuestos || '';
    $('impuestos-detalle').textContent = d.impuestos.length
        ? d.impuestos.map(i => `${i.nombre} ${dinero(i.valor)}`).join(' + ')
        : '';
    totalLeido = d.total_factura;

    // Líneas
    $('lineas').innerHTML = '';
    d.lineas.forEach(l => agregarLinea(l));
    if (!d.lineas.length) agregarLinea();
}

// ------------------------------------------------------------------
// Guardar
// ------------------------------------------------------------------
function mostrarError(msg) {
    $('error').textContent = msg;
    $('error').style.display = 'block';
    $('error').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function guardar() {
    $('error').style.display = 'none';
    document.querySelectorAll('.linea').forEach(l => l.classList.remove('con-error'));

    // Ignorar líneas totalmente vacías
    const lineas = leerLineas().filter(l => l.insumo || l.servicio || l.cantidad_factura || l.costo_total);

    if (!lineas.length) return mostrarError('Agrega al menos una línea a la factura.');

    for (const l of lineas) {
        let msg = null;
        if (!l.insumo && !l.servicio) msg = 'Elige el insumo en cada línea (o marca "No es inventario").';
        else if (l.servicio && !l.descripcion) msg = 'Escribe la descripción de las líneas que no son inventario.';
        else if (l.cantidad_factura <= 0) msg = `Escribe la cantidad comprada de ${l.insumo ? l.insumo.nombre : l.descripcion}.`;
        else if (!l.servicio && l.factor <= 0) msg = `Escribe cuánto trae cada presentación de ${l.insumo.nombre}.`;
        if (msg) {
            l.el.classList.add('con-error');
            return mostrarError(msg);
        }
    }

    const prov = $('proveedor').value;
    if (prov === 'nuevo' && !$('prov-nuevo-nombre').value.trim()) {
        return mostrarError('Escribe el nombre del proveedor nuevo.');
    }

    const btn = $('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    try {
        const res = await fetch('/compras', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                proveedor_id: prov && prov !== 'nuevo' ? prov : null,
                proveedor_nuevo: prov === 'nuevo'
                    ? { nombre: $('prov-nuevo-nombre').value.trim(), nit: $('prov-nuevo-nit').value.trim() || null }
                    : null,
                numero_factura: $('numero').value.trim() || null,
                fecha: $('fecha').value,
                impuestos: Math.round(Number($('impuestos').value) || 0),
                impuestos_al_costo: $('impuestos-costo').checked,
                total_factura: totalLeido,
                archivo_token: archivoToken,
                notas: $('notas').value.trim() || null,
                items: lineas.map(l => ({
                    insumo_id: l.insumo ? l.insumo.id : null,
                    descripcion: l.descripcion || null,
                    cantidad_factura: l.cantidad_factura,
                    unidad_factura: l.unidad_factura || null,
                    factor: l.servicio ? 1 : l.factor,
                    costo_total: l.costo_total,
                })),
            }),
        });
        const data = await res.json();

        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(data.mensaje ?? primero ?? data.message ?? 'No se pudo registrar la factura.');
        }

        window.location = '/compras/' + data.id;
    } catch (e) {
        mostrarError(e.message);
        btn.disabled = false;
        btn.textContent = 'Registrar factura y sumar a existencias';
    }
}

// ------------------------------------------------------------------
// Crear insumo sin salir de la factura
// ------------------------------------------------------------------
function abrirNuevoInsumo(lineaId) {
    lineaParaInsumo = lineaId;
    const desc = lineaId ? $('linea-' + lineaId)?.querySelector('.l-descripcion').value.trim() : '';
    // Nombre sugerido: la descripción de la factura en formato título
    $('ni-nombre').value = desc
        ? desc.toLowerCase().replace(/\s+/g, ' ').replace(/(^|\s)\S/g, c => c.toUpperCase()).slice(0, 120)
        : '';
    $('ni-error').style.display = 'none';
    $('modal-insumo').style.display = 'flex';
    setTimeout(() => $('ni-nombre').focus(), 50);
}

function cerrarNuevoInsumo() {
    $('modal-insumo').style.display = 'none';
    lineaParaInsumo = null;
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

        INSUMOS.push(data.insumo);
        INSUMOS.sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));

        // Refrescar todos los selectores conservando lo elegido
        document.querySelectorAll('.linea').forEach(linea => {
            const sel = linea.querySelector('.l-insumo');
            const actual = sel.value === 'ninguno' ? 'ninguno' : (Number(sel.value) || null);
            const esEsta = Number(linea.dataset.id) === lineaParaInsumo;
            sel.innerHTML = opcionesInsumo(esEsta ? data.insumo.id : actual);
            if (esEsta) {
                linea.dataset.coincidencia = 'manual';
                linea.dataset.factorManual = '';
            }
        });

        const lineaId = lineaParaInsumo;
        cerrarNuevoInsumo();
        if (lineaId) sugerirSiAuto(lineaId); else recalcular();
    } catch (e) {
        $('ni-error').textContent = e.message;
        $('ni-error').style.display = 'block';
    } finally {
        btn.disabled = false;
    }
}

agregarLinea();
</script>
@endsection
