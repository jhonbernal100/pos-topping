@extends('layouts.pos')

@section('titulo', 'Registrar factura')

@section('contenido')
@php
    $input = 'width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;';
    $label = 'display:block;font-size:12px;color:#555;margin-bottom:4px;';
@endphp

<style>
    .linea { background:#fff;border-radius:12px;padding:14px;margin-bottom:10px;border:2px solid transparent; }
    .linea.con-error { border-color:#e0a0a0; }
    .linea-grid { display:grid;grid-template-columns:2fr 1fr 1.2fr 1.3fr 1.3fr;gap:10px;align-items:end; }
    .linea-pie { display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px; }
    .resultado { font-size:13px;color:#155724;background:#eef8f0;padding:6px 10px;border-radius:6px; }
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

    <div style="background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="{{ $label }}" for="proveedor">Proveedor</label>
                <select id="proveedor" style="{{ $input }}">
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
                <label style="{{ $label }}" for="impuestos">Impuestos (IVA u otros, opcional)</label>
                <input id="impuestos" type="number" min="0" step="1" inputmode="numeric" placeholder="0" oninput="recalcular()" style="{{ $input }}">
            </div>
            <div>
                <label style="{{ $label }}" for="notas">Notas (opcional)</label>
                <input id="notas" maxlength="500" style="{{ $input }}">
            </div>
        </div>

        <div style="display:flex;justify-content:space-between;margin-top:16px;font-size:14px;color:#555;">
            <span>Subtotal</span><span id="t-subtotal">$0</span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:20px;font-weight:bold;">
            <span>Total factura</span><span id="t-total">$0</span>
        </div>

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

const $ = id => document.getElementById(id);
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

function formatear(cantidad, unidad) {
    if (unidad !== 'und' && Math.abs(cantidad) >= 1000) {
        return (cantidad / 1000).toLocaleString('es-CO', { maximumFractionDigits: 2 }) + (unidad === 'g' ? ' kg' : ' L');
    }
    return cantidad.toLocaleString('es-CO', { maximumFractionDigits: 2 }) + ' ' + UNIDAD_TXT[unidad];
}

function opcionesInsumo(seleccionado) {
    return '<option value="">Elige el insumo…</option>' + INSUMOS.map(i =>
        `<option value="${i.id}" ${i.id === seleccionado ? 'selected' : ''}>${esc(i.nombre)} (${UNIDAD_TXT[i.unidad]})</option>`
    ).join('') + '<option value="nuevo">+ Crear insumo nuevo…</option>';
}

function agregarLinea() {
    const id = siguienteId++;
    const div = document.createElement('div');
    div.className = 'linea';
    div.id = 'linea-' + id;
    div.dataset.id = id;
    div.innerHTML = `
        <div class="linea-grid">
            <div class="col-insumo">
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Insumo</label>
                <select class="l-insumo" onchange="cambioInsumo(${id}, this)" style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
                    ${opcionesInsumo(null)}
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Cantidad comprada</label>
                <input class="l-cantidad" type="number" min="0" step="any" inputmode="decimal" placeholder="Ej: 2" oninput="recalcular()"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Presentación</label>
                <input class="l-unidad-factura" maxlength="30" placeholder="caja, bolsa, galón"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Cada una trae (<span class="l-u">—</span>)</label>
                <input class="l-factor" type="number" min="0" step="any" inputmode="decimal" placeholder="Ej: 5000" value="1" oninput="recalcular()"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Valor total línea</label>
                <input class="l-costo" type="number" min="0" step="1" inputmode="numeric" placeholder="$" oninput="recalcular()"
                       style="width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;">
            </div>
        </div>
        <div class="linea-pie">
            <input class="l-descripcion" maxlength="191" placeholder="Cómo aparece en la factura (opcional)"
                   style="flex:1;min-width:200px;padding:8px;font-size:13px;border:1px solid #ddd;border-radius:6px;">
            <span class="resultado l-resultado" style="display:none;"></span>
            <button type="button" onclick="quitarLinea(${id})" aria-label="Quitar línea"
                style="padding:6px 10px;background:#f0f0f0;border:none;border-radius:6px;font-size:12px;cursor:pointer;">Quitar</button>
        </div>`;
    $('lineas').appendChild(div);
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

function cambioInsumo(id, select) {
    if (select.value === 'nuevo') {
        select.value = '';
        abrirNuevoInsumo(id);
        return;
    }
    const linea = $('linea-' + id);
    const ins = insumoDe(linea);
    // Si el insumo se controla en unidades, normalmente cada unidad comprada es 1
    if (ins && ins.unidad === 'und' && !linea.querySelector('.l-factor').value) {
        linea.querySelector('.l-factor').value = 1;
    }
    recalcular();
}

function leerLineas() {
    return [...document.querySelectorAll('.linea')].map(linea => {
        const ins = insumoDe(linea);
        return {
            el: linea,
            insumo: ins,
            cantidad_factura: Number(linea.querySelector('.l-cantidad').value) || 0,
            unidad_factura: linea.querySelector('.l-unidad-factura').value.trim(),
            factor: Number(linea.querySelector('.l-factor').value) || 0,
            costo_total: Math.round(Number(linea.querySelector('.l-costo').value) || 0),
            descripcion: linea.querySelector('.l-descripcion').value.trim(),
        };
    });
}

function recalcular() {
    let subtotal = 0;
    leerLineas().forEach(l => {
        subtotal += l.costo_total;
        l.el.querySelector('.l-u').textContent = l.insumo ? UNIDAD_TXT[l.insumo.unidad] : '—';

        const res = l.el.querySelector('.l-resultado');
        const total = l.cantidad_factura * l.factor;
        if (l.insumo && total > 0) {
            let costo = '';
            if (l.costo_total > 0) {
                const unit = l.costo_total / total;
                costo = l.insumo.unidad === 'und'
                    ? ` · ${dinero(unit)}/und`
                    : ` · ${dinero(unit * 1000)}/${l.insumo.unidad === 'g' ? 'kg' : 'L'}`;
            }
            res.textContent = 'Entran ' + formatear(total, l.insumo.unidad) + costo;
            res.style.display = 'inline-block';
        } else {
            res.style.display = 'none';
        }
    });

    const imp = Math.round(Number($('impuestos').value) || 0);
    $('t-subtotal').textContent = dinero(subtotal);
    $('t-total').textContent = dinero(subtotal + imp);
}

function mostrarError(msg) {
    $('error').textContent = msg;
    $('error').style.display = 'block';
    $('error').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function guardar() {
    $('error').style.display = 'none';
    document.querySelectorAll('.linea').forEach(l => l.classList.remove('con-error'));

    // Ignorar líneas totalmente vacías
    const lineas = leerLineas().filter(l => l.insumo || l.cantidad_factura || l.costo_total);

    if (!lineas.length) return mostrarError('Agrega al menos un insumo a la factura.');

    for (const l of lineas) {
        let msg = null;
        if (!l.insumo) msg = 'Elige el insumo en cada línea.';
        else if (l.cantidad_factura <= 0) msg = `Escribe la cantidad comprada de ${l.insumo.nombre}.`;
        else if (l.factor <= 0) msg = `Escribe cuánto trae cada presentación de ${l.insumo.nombre}.`;
        if (msg) {
            l.el.classList.add('con-error');
            return mostrarError(msg);
        }
    }

    const btn = $('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    try {
        const res = await fetch('/compras', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({
                proveedor_id: $('proveedor').value || null,
                numero_factura: $('numero').value.trim() || null,
                fecha: $('fecha').value,
                impuestos: Math.round(Number($('impuestos').value) || 0),
                notas: $('notas').value.trim() || null,
                items: lineas.map(l => ({
                    insumo_id: l.insumo.id,
                    descripcion: l.descripcion || null,
                    cantidad_factura: l.cantidad_factura,
                    unidad_factura: l.unidad_factura || null,
                    factor: l.factor,
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
    $('ni-nombre').value = '';
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
            const actual = Number(sel.value) || null;
            const elegir = Number(linea.dataset.id) === lineaParaInsumo ? data.insumo.id : actual;
            sel.innerHTML = opcionesInsumo(elegir);
        });

        cerrarNuevoInsumo();
        recalcular();
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
