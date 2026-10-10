@extends('layouts.pos')

@section('titulo', 'Cocina')

@section('estilos')
<style>
    .kds { padding:12px; }
    .kds-cab { display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .kds-cols { display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; align-items:start; }
    @media (max-width: 900px) { .kds-cols { grid-template-columns:1fr; } }

    .col { background:#e4e4e4; border-radius:12px; padding:10px; min-height:60vh; }
    .col-titulo { display:flex; justify-content:space-between; align-items:center; font-weight:bold; font-size:15px; padding:4px 4px 10px; }
    .col-cnt { background:#000; color:#fff; border-radius:12px; padding:2px 10px; font-size:13px; }

    .tarjeta { border-radius:12px; padding:12px; margin-bottom:10px; border:1px solid #ddd; }
    .tarjeta-cab { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:8px; }
    .tarjeta-nombre { font-size:22px; font-weight:bold; line-height:1.1; word-break:break-word; }
    .tarjeta-turno { font-size:13px; color:#555; margin-top:2px; }
    .tarjeta-tiempo { font-size:14px; font-weight:bold; white-space:nowrap; }
    .tarjeta-item { border-top:1px solid rgba(0,0,0,.08); padding:6px 0; font-size:15px; }
    .tarjeta-op { font-size:13px; color:#333; padding-left:10px; }
    .tarjeta-op span { color:#777; }
    .tarjeta-nota { font-size:13px; font-weight:bold; color:#a00; padding-left:10px; }
    .tarjeta-acciones { display:flex; gap:6px; margin-top:8px; }

    .btn-pri { flex:1; padding:12px; border:none; border-radius:10px; background:#000; color:#fff; font-size:16px; font-weight:bold; cursor:pointer; font-family:inherit; }
    .btn-sec { padding:12px; border:none; border-radius:10px; background:#fff; color:#000; font-size:14px; cursor:pointer; border:1px solid #ccc; font-family:inherit; }
    .vacio { color:#888; text-align:center; padding:24px 0; font-size:14px; }
</style>
@endsection

@section('contenido')
@if(!$sede)
    <div style="max-width:520px;margin:40px auto;padding:20px;background:#fff3cd;color:#856404;border-radius:12px;">
        No hay una sede activa. Selecciona una sede en el encabezado.
    </div>
@else
<div class="kds">
    <div class="kds-cab">
        <h1 style="font-size:20px;">Cocina · Sede {{ $sede->nombre }}</h1>
        <div style="display:flex;gap:8px;align-items:center;">
            <span id="conexion" style="font-size:12px;color:#155724;">● En línea</span>
            <button class="btn-sec" id="btn-sonido" onclick="activarSonido()">Activar sonido</button>
        </div>
    </div>

    <div class="kds-cols">
        <div class="col">
            <div class="col-titulo"><span>Pendientes</span><span class="col-cnt" id="cnt-pendiente">0</span></div>
            <div id="col-pendiente"></div>
        </div>
        <div class="col">
            <div class="col-titulo"><span>En preparación</span><span class="col-cnt" id="cnt-en_preparacion">0</span></div>
            <div id="col-en_preparacion"></div>
        </div>
        <div class="col">
            <div class="col-titulo"><span>Listos · llamar al cliente</span><span class="col-cnt" id="cnt-listo">0</span></div>
            <div id="col-listo"></div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if($sede)
<script>
let pedidos   = [];
let conocidos = new Set();
let primera   = true;
let desfase   = 0;
let audio     = null;

const $   = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));

const SIGUIENTE = {
    pendiente:      ['en_preparacion', 'Preparar'],
    en_preparacion: ['listo', 'Listo'],
    listo:          ['entregado', 'Entregado'],
};
const ANTERIOR = { en_preparacion: 'pendiente', listo: 'en_preparacion' };

// ---------------- Sonido (el navegador exige un clic para activarlo) ----------------
function activarSonido() {
    audio = audio || new (window.AudioContext || window.webkitAudioContext)();
    audio.resume();
    $('btn-sonido').textContent = 'Sonido activado';
    sonar();
}

function sonar() {
    if (!audio) return;
    [0, 0.3].forEach(t => {
        const o = audio.createOscillator();
        const g = audio.createGain();
        o.frequency.value = 880;
        o.connect(g);
        g.connect(audio.destination);
        g.gain.setValueAtTime(0.3, audio.currentTime + t);
        g.gain.exponentialRampToValueAtTime(0.001, audio.currentTime + t + 0.25);
        o.start(audio.currentTime + t);
        o.stop(audio.currentTime + t + 0.25);
    });
}

// ---------------- Datos ----------------
async function cargar() {
    try {
        const res = await fetch('/cocina/pedidos', { headers: { 'Accept': 'application/json' } });
        if (res.status === 401 || res.status === 419) { location.reload(); return; }
        const data = await res.json();

        desfase = new Date(data.ahora).getTime() - Date.now();

        const nuevos = data.pedidos.filter(p => p.estado === 'pendiente' && !conocidos.has(p.id));
        data.pedidos.forEach(p => conocidos.add(p.id));
        if (!primera && nuevos.length) sonar();
        primera = false;

        pedidos = data.pedidos;
        render();
        conexion(true);
    } catch (e) {
        conexion(false);
    }
}

function conexion(ok) {
    $('conexion').textContent = ok ? '● En línea' : '● Sin conexión, reintentando…';
    $('conexion').style.color = ok ? '#155724' : '#a00';
}

async function cambiar(id, estado) {
    const pedido = pedidos.find(p => p.id === id);
    const previo = pedido ? pedido.estado : null;
    if (pedido) { pedido.estado = estado; render(); }

    try {
        const res = await fetch(`/cocina/${id}/estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ estado }),
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.mensaje ?? data.message ?? 'Error');

        if (estado === 'entregado') {
            pedidos = pedidos.filter(p => p.id !== id);
            render();
        }
    } catch (e) {
        if (pedido) { pedido.estado = previo; render(); }
        alert(e.message || 'No se pudo actualizar el pedido');
        cargar();
    }
}

// ---------------- Pantalla ----------------
function minutos(p) {
    return Math.max(0, Math.floor((Date.now() + desfase - new Date(p.creado).getTime()) / 60000));
}

function tarjeta(p) {
    const m = minutos(p);
    const fondo = p.estado === 'listo' ? '#d4edda'
                : m >= 10 ? '#f8d7da'
                : m >= 5  ? '#fff3cd'
                : '#fff';
    const sig   = SIGUIENTE[p.estado];
    const atras = ANTERIOR[p.estado];

    const items = p.items.map(i => `
        <div class="tarjeta-item">
            <strong>${i.cantidad} × ${esc(i.nombre)}</strong>
            ${i.opciones.map(o => `<div class="tarjeta-op"><span>${esc(o.grupo)}:</span> ${o.valores.map(esc).join(', ')}</div>`).join('')}
            ${i.notas ? `<div class="tarjeta-nota">⚠ ${esc(i.notas)}</div>` : ''}
        </div>`).join('');

    return `
        <div class="tarjeta" style="background:${fondo};">
            <div class="tarjeta-cab">
                <div>
                    <div class="tarjeta-nombre">${esc((p.nombre || 'Cliente').toUpperCase())}</div>
                    <div class="tarjeta-turno">#${p.turno ?? '-'} · ${esc(p.hora)}${p.canal === 'domicilio' ? ' · DOMICILIO' : ''}</div>
                </div>
                <div class="tarjeta-tiempo">${m} min</div>
            </div>
            ${items}
            <div class="tarjeta-acciones">
                ${atras ? `<button class="btn-sec" onclick="cambiar(${p.id}, '${atras}')" aria-label="Regresar al estado anterior">↩</button>` : ''}
                <button class="btn-sec" onclick="window.open('/cocina/${p.id}/comanda', '_blank')">Imprimir</button>
                ${sig ? `<button class="btn-pri" onclick="cambiar(${p.id}, '${sig[0]}')">${sig[1]}</button>` : ''}
            </div>
        </div>`;
}

function render() {
    ['pendiente', 'en_preparacion', 'listo'].forEach(estado => {
        const lista = pedidos.filter(p => p.estado === estado);
        $('col-' + estado).innerHTML = lista.length ? lista.map(tarjeta).join('') : '<div class="vacio">Sin pedidos</div>';
        $('cnt-' + estado).textContent = lista.length;
    });
}

cargar();
setInterval(cargar, 5000);
</script>
@endif
@endsection