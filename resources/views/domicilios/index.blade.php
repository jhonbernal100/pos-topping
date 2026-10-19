@extends('layouts.pos')

@section('titulo', 'Domicilios')

@section('contenido')
<style>
    .dom-cols { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:14px; align-items:start; }
    .dom-col h2 { font-size:15px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center; }
    .dom-col h2 span { background:#000; color:#fff; border-radius:10px; padding:1px 9px; font-size:12px; }
    .ped { background:#fff; border-radius:12px; padding:12px; margin-bottom:10px; border-left:5px solid #ccc; }
    .ped.verificar { border-left-color:#f0a500; }
    .ped.rechazado { border-left-color:#c00; }
    .ped.cocina { border-left-color:#4285F4; }
    .ped.listo { border-left-color:#27A88A; }
    .ped.salio { border-left-color:#999; opacity:.75; }
    .ped .top { display:flex; justify-content:space-between; gap:8px; }
    .ped .nom { font-weight:bold; font-size:15px; }
    .ped .sub { font-size:12px; color:#666; margin-top:2px; line-height:1.4; }
    .ped .items { font-size:13px; margin-top:8px; line-height:1.45; }
    .ped .items small { color:#777; display:block; padding-left:10px; }
    .lect { margin-top:8px; padding:8px; border-radius:8px; background:#f7f7f7; font-size:12px; line-height:1.5; }
    .ok { color:#155724; font-weight:bold; } .mal { color:#c00; font-weight:bold; }
    .acciones { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
    .acciones button, .acciones a { padding:8px 12px; border-radius:8px; border:none; font-size:13px; cursor:pointer; text-decoration:none; font-weight:bold; }
    .b-ok { background:#27A88A; color:#fff; } .b-no { background:#fff; color:#c00; border:1px solid #c00 !important; }
    .b-gris { background:#f0f0f0; color:#000; } .b-negro { background:#000; color:#fff; }
    .vacio { color:#999; font-size:13px; padding:10px; text-align:center; background:#fff; border-radius:12px; }
</style>

<div style="padding:16px;max-width:1300px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Domicilios por WhatsApp</h1>
            <div style="font-size:13px;color:#888;">Sede {{ $sede?->nombre }} · se actualiza solo · <span id="hora"></span></div>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <label style="font-size:13px;display:flex;gap:6px;align-items:center;"><input type="checkbox" id="sonido" checked> Sonido</label>
            @if(auth()->user()->esPropietario())
                <a href="/domicilios/configuracion" style="padding:9px 14px;background:#fff;border:1px solid #ddd;border-radius:8px;color:#000;text-decoration:none;font-size:14px;">Configuración</a>
            @endif
        </div>
    </div>

    @unless($tenant->wa_activo)
        <div style="padding:12px;border-radius:8px;background:#fff3cd;color:#856404;font-size:14px;margin-bottom:12px;">
            El bot está apagado: los clientes no reciben respuesta. Actívalo en Configuración.
        </div>
    @endunless

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    <div class="dom-cols">
        <div class="dom-col"><h2>💳 Verificar pago <span id="n-pago">0</span></h2><div id="col-pago"></div></div>
        <div class="dom-col"><h2>👩‍🍳 En cocina <span id="n-cocina">0</span></h2><div id="col-cocina"></div></div>
        <div class="dom-col"><h2>🛵 Listos para despachar <span id="n-listo">0</span></h2><div id="col-listo"></div></div>
        <div class="dom-col"><h2>✅ Salieron hoy <span id="n-salio">0</span></h2><div id="col-salio"></div></div>
    </div>
</div>

{{-- Ver comprobante --}}
<div id="modal-img" onclick="this.style.display='none'" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:1000;align-items:center;justify-content:center;padding:16px;">
    <img id="img-comprobante" alt="Comprobante de pago" style="max-width:100%;max-height:92vh;border-radius:8px;background:#fff;">
</div>
@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
const dinero = v => '$' + Math.round(v || 0).toLocaleString('es-CO');
let porVerificarAntes = null;

function mensaje(texto, ok) {
    const box = $('mensaje');
    box.textContent = texto;
    box.style.display = 'block';
    box.style.background = ok ? '#d4edda' : '#f8d7da';
    box.style.color = ok ? '#155724' : '#721c24';
    clearTimeout(box._t);
    box._t = setTimeout(() => box.style.display = 'none', 5000);
}

function pitido() {
    if (!$('sonido').checked) return;
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        [0, .25].forEach(t => {
            const o = ctx.createOscillator(), g = ctx.createGain();
            o.frequency.value = 880; o.connect(g); g.connect(ctx.destination);
            g.gain.setValueAtTime(.2, ctx.currentTime + t);
            o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + .18);
        });
    } catch (e) {}
}

function marca(v) {
    if (v === true) return '<span class="ok">✔</span>';
    if (v === false) return '<span class="mal">✘</span>';
    return '<span style="color:#999;">?</span>';
}

function tarjeta(p) {
    const items = p.items.map(i => `<div>${esc(i.texto)}${i.opciones.map(o => `<small>${esc(o)}</small>`).join('')}${i.notas ? `<small>Nota: ${esc(i.notas)}</small>` : ''}</div>`).join('');
    let clase = '', acciones = '', extra = '';

    if (p.estado === 'borrador') {
        clase = p.pago === 'rechazado' ? 'rechazado' : 'verificar';
        if (p.pago === 'pendiente') extra = '<div class="lect">⏳ Esperando que el cliente envíe la captura del pago.</div>';
        if (p.pago === 'rechazado') extra = `<div class="lect"><span class="mal">Pago rechazado:</span> ${esc(p.motivo)}<br>Esperando nueva captura.</div>`;
        if (p.pago === 'por_verificar') {
            const l = p.lectura;
            extra = l && l.leido ? `<div class="lect">
                    ${l.esPago === false ? '<span class="mal">La IA dice que la imagen no parece un comprobante.</span><br>' : ''}
                    ${esc(l.plataforma || 'Transferencia')} · ${l.fecha ? esc(l.fecha) : ''}<br>
                    Valor: <strong>${l.monto !== null ? dinero(l.monto) : '—'}</strong> ${marca(l.okMonto)} (pedido ${dinero(p.total)})<br>
                    Destino: ${esc(l.destino || '—')} ${marca(l.okDestino)}<br>
                    Ref: ${esc(l.referencia || '—')}
                </div>` : '<div class="lect">No se pudo leer con IA: revisa la captura.</div>';
            extra += '<div class="lect" style="background:#fff8e1;">Confírmalo viendo el movimiento en tu app de Nequi o Daviplata.</div>';
        }
        acciones = (p.comprobante ? `<button class="b-gris" onclick="verComprobante(${p.id})">Ver captura</button>` : '')
            + `<button class="b-ok" onclick="confirmar(${p.id}, 'nequi')">Pagó por Nequi</button>
               <button class="b-ok" onclick="confirmar(${p.id}, 'daviplata')">Pagó por Daviplata</button>`
            + (p.pago === 'por_verificar' ? `<button class="b-no" onclick="rechazar(${p.id})">Rechazar</button>` : '')
            + `<button class="b-gris" onclick="cancelar(${p.id})">Cancelar</button>`;
    } else if (p.cocina === 'entregado') {
        clase = 'salio';
    } else if (p.cocina === 'listo') {
        clase = 'listo';
        acciones = `<button class="b-negro" onclick="despachar(${p.id})">🛵 Despachar y avisar</button>`;
    } else {
        clase = 'cocina';
        extra = `<div class="lect">${p.cocina === 'en_preparacion' ? '👩‍🍳 En preparación' : '🧾 En cola de cocina'}</div>`;
        acciones = `<button class="b-gris" onclick="despachar(${p.id})">Despachar ya</button>`;
    }

    return `<div class="ped ${clase}">
        <div class="top">
            <div><div class="nom">${p.turno ? '#' + p.turno + ' · ' : ''}${esc(p.nombre)}</div>
                <div class="sub">${esc(p.hora)}${p.despachado ? ' · salió ' + esc(p.despachado) : ''} · 📞 ${esc(p.telefono || '—')}</div></div>
            <div style="text-align:right;"><strong>${dinero(p.total)}</strong><div class="sub">dom. ${dinero(p.domicilio)}</div></div>
        </div>
        <div class="sub" style="margin-top:6px;">📍 ${esc(p.direccion)}${p.distancia ? ` (${p.distancia} km)` : ''}
            ${p.mapa ? ` · <a href="${esc(p.mapa)}" target="_blank" rel="noopener">Ver mapa</a>` : ''}</div>
        ${p.notas ? `<div class="sub">📝 ${esc(p.notas)}</div>` : ''}
        <div class="items">${items}</div>
        ${extra}
        ${acciones ? `<div class="acciones">${acciones}</div>` : ''}
    </div>`;
}

async function cargar() {
    try {
        const res = await fetch('/domicilios/pedidos', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        const ped = data.pedidos;
        const grupos = {
            pago:   ped.filter(p => p.estado === 'borrador'),
            cocina: ped.filter(p => p.estado === 'completada' && ['pendiente', 'en_preparacion'].includes(p.cocina)),
            listo:  ped.filter(p => p.estado === 'completada' && p.cocina === 'listo'),
            salio:  ped.filter(p => p.estado === 'completada' && p.cocina === 'entregado').reverse(),
        };
        for (const [k, lista] of Object.entries(grupos)) {
            $('col-' + k).innerHTML = lista.length ? lista.map(tarjeta).join('') : '<div class="vacio">Nada por ahora</div>';
            $('n-' + k).textContent = lista.length;
        }
        const porVerificar = grupos.pago.filter(p => p.pago === 'por_verificar').map(p => p.id);
        if (porVerificarAntes && porVerificar.some(id => !porVerificarAntes.includes(id))) pitido();
        porVerificarAntes = porVerificar;
        $('hora').textContent = data.hora;
    } catch (e) {}
}

async function accion(url, body, ok) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body || {}),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo completar.');
        }
        mensaje(data.mensaje || ok, true);
    } catch (e) {
        mensaje(e.message, false);
    }
    cargar();
}

function confirmar(id, metodo) {
    if (!confirm(`¿Confirmas que el pago por ${metodo === 'nequi' ? 'Nequi' : 'Daviplata'} ya está en tu cuenta?`)) return;
    accion(`/domicilios/${id}/confirmar-pago`, { metodo });
}

function rechazar(id) {
    const motivo = prompt('¿Por qué no se confirma el pago? (se lo enviamos al cliente)', 'El valor no coincide con el pedido');
    if (motivo) accion(`/domicilios/${id}/rechazar-pago`, { motivo });
}

function cancelar(id) {
    if (confirm('¿Cancelar este pedido? Le avisamos al cliente.')) accion(`/domicilios/${id}/cancelar`);
}

function despachar(id) {
    accion(`/domicilios/${id}/despachar`);
}

function verComprobante(id) {
    $('img-comprobante').src = `/domicilios/${id}/comprobante?t=${Date.now()}`;
    $('modal-img').style.display = 'flex';
}

cargar();
setInterval(cargar, 8000);
</script>
@endsection
