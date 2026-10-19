@extends('layouts.pos')

@section('titulo', 'Configuración de domicilios')

@section('contenido')
@php
    $input = 'width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;';
    $label = 'display:block;font-size:12px;color:#555;margin-bottom:4px;';
    $card  = 'background:#fff;border-radius:12px;padding:18px;margin-bottom:14px;';
    $estados = [
        'ready' => ['Conectado', '#d4edda', '#155724'],
        'qr_ready' => ['Esperando escanear el código QR', '#fff3cd', '#856404'],
        'authenticating' => ['Conectando…', '#fff3cd', '#856404'],
        'initializing' => ['Iniciando…', '#fff3cd', '#856404'],
        'disconnected' => ['Desconectado', '#f8d7da', '#721c24'],
        'failed' => ['Con error', '#f8d7da', '#721c24'],
    ];
    [$txtCon, $bgCon, $colCon] = $conexion ? ($estados[$conexion] ?? [$conexion, '#f0f0f0', '#555']) : ['Sin conexión con el servidor de WhatsApp', '#f0f0f0', '#555'];
@endphp

<div style="padding:16px;max-width:860px;margin:0 auto;">
    <a href="/domicilios" style="font-size:13px;color:#555;text-decoration:none;">← Volver a domicilios</a>
    <h1 style="font-size:22px;margin:8px 0 14px;">Configuración de domicilios por WhatsApp</h1>

    @unless($plan)
        <div style="padding:12px;border-radius:8px;background:#fff3cd;color:#856404;font-size:14px;margin-bottom:14px;">
            El bot de domicilios está incluido en el <strong>plan anual</strong>. Puedes dejar todo configurado; se activa cuando el plan esté vigente.
        </div>
    @endunless

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    {{-- WhatsApp --}}
    <div style="{{ $card }}">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
            <h2 style="font-size:17px;">WhatsApp</h2>
            <span style="padding:4px 12px;border-radius:12px;font-size:12px;font-weight:bold;background:{{ $bgCon }};color:{{ $colCon }};">{{ $txtCon }}</span>
        </div>
        <label style="display:flex;gap:10px;align-items:center;font-size:15px;margin-bottom:14px;cursor:pointer;">
            <input type="checkbox" id="wa_activo" {{ $tenant->wa_activo ? 'checked' : '' }} style="width:20px;height:20px;">
            <span><strong>Bot activo</strong> — responde automáticamente a los clientes que escriben</span>
        </label>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            <div>
                <label style="{{ $label }}" for="wa_numero">Número de WhatsApp del bot</label>
                <input id="wa_numero" value="{{ $tenant->wa_numero }}" placeholder="573001234567" style="{{ $input }}">
            </div>
            <div>
                <label style="{{ $label }}" for="wa_session_id">ID de sesión (lo da Avanzas Digital)</label>
                <input id="wa_session_id" value="{{ $tenant->wa_session_id }}" placeholder="gaspar" style="{{ $input }}">
            </div>
        </div>
        <div style="margin-top:12px;">
            <label style="{{ $label }}" for="wa_bienvenida">Mensaje de bienvenida (opcional)</label>
            <textarea id="wa_bienvenida" maxlength="500" rows="2" placeholder="¡Hola! 👋 Bienvenido a {{ $tenant->nombre }} 🍦" style="{{ $input }}resize:vertical;font-family:inherit;">{{ $tenant->wa_bienvenida }}</textarea>
        </div>
    </div>

    {{-- Pagos --}}
    <div style="{{ $card }}">
        <h2 style="font-size:17px;margin-bottom:12px;">Pagos</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div><label style="{{ $label }}" for="pago_nequi">Número Nequi</label><input id="pago_nequi" value="{{ $tenant->pago_nequi }}" style="{{ $input }}"></div>
            <div><label style="{{ $label }}" for="pago_daviplata">Número Daviplata</label><input id="pago_daviplata" value="{{ $tenant->pago_daviplata }}" style="{{ $input }}"></div>
            <div><label style="{{ $label }}" for="pago_titular">A nombre de</label><input id="pago_titular" value="{{ $tenant->pago_titular }}" style="{{ $input }}"></div>
            <div><label style="{{ $label }}" for="domicilio_minimo">Pedido mínimo (sin domicilio)</label><input id="domicilio_minimo" type="number" min="0" step="500" value="{{ $tenant->domicilio_minimo ?: '' }}" placeholder="0" style="{{ $input }}"></div>
        </div>
    </div>

    {{-- Tarifas --}}
    <div style="{{ $card }}">
        <h2 style="font-size:17px;margin-bottom:4px;">Valor del domicilio por distancia</h2>
        <p style="font-size:13px;color:#888;margin-bottom:12px;">Se mide desde la sede más cercana al cliente. Más allá del último rango no se atiende.</p>
        <div id="tarifas"></div>
        <button type="button" onclick="agregarTarifa()" style="padding:8px 12px;background:#fff;border:1px dashed #999;border-radius:8px;cursor:pointer;font-size:13px;">+ Agregar rango</button>
    </div>

    {{-- Sedes --}}
    <div style="{{ $card }}">
        <h2 style="font-size:17px;margin-bottom:4px;">Sedes que despachan</h2>
        <p style="font-size:13px;color:#888;margin-bottom:12px;line-height:1.5;">
            Para la ubicación: abre Google Maps, busca la sede, mantén presionado el punto exacto y copia las coordenadas
            (ej. <code>4.6047, -74.1385</code>) o pega el enlace de Google Maps.
        </p>
        @foreach($sedes as $s)
            <div class="sede" data-id="{{ $s->id }}" style="border:1px solid #eee;border-radius:10px;padding:12px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
                    <strong>{{ $s->nombre }} <span style="font-weight:normal;color:#888;font-size:13px;">— {{ $s->direccion }}</span></strong>
                    <label style="display:flex;gap:6px;align-items:center;font-size:14px;"><input type="checkbox" class="s-acepta" {{ $s->acepta_domicilios ? 'checked' : '' }}> Despacha domicilios</label>
                </div>
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:10px;">
                    <div>
                        <label style="{{ $label }}">Coordenadas o enlace de Google Maps</label>
                        <input class="s-coord" value="{{ $s->latitud !== null ? $s->latitud . ', ' . $s->longitud : '' }}" placeholder="4.6047, -74.1385" style="{{ $input }}" oninput="leerCoordenadas(this)">
                        <div class="s-ayuda" style="font-size:12px;color:#888;margin-top:4px;">
                            @if($s->latitud !== null)
                                <a href="https://www.google.com/maps?q={{ $s->latitud }},{{ $s->longitud }}" target="_blank" rel="noopener">Ver en el mapa</a>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label style="{{ $label }}">Radio máximo (km)</label>
                        <input class="s-radio" type="number" min="0" step="0.5" value="{{ $s->radio_cobertura_km }}" placeholder="Sin límite" style="{{ $input }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button id="btn-guardar" onclick="guardar()" style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:17px;font-weight:bold;cursor:pointer;">
        Guardar configuración
    </button>
</div>
@endsection

@section('scripts')
<script>
const TARIFAS = @json($tarifas->map(fn ($t) => ['hasta_km' => $t->hasta_km, 'valor' => $t->valor])->values());
const $ = id => document.getElementById(id);

function filaTarifa(t = { hasta_km: '', valor: '' }) {
    const div = document.createElement('div');
    div.className = 'tarifa';
    div.style.cssText = 'display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;';
    div.innerHTML = `
        <div style="display:flex;align-items:center;gap:6px;"><span style="font-size:13px;color:#555;white-space:nowrap;">Hasta</span>
            <input class="t-km" type="number" min="0.5" step="0.5" value="${t.hasta_km}" placeholder="2" style="width:100%;padding:10px;border:2px solid #ddd;border-radius:8px;font-size:15px;"><span style="font-size:13px;color:#555;">km</span></div>
        <div style="display:flex;align-items:center;gap:6px;"><span style="font-size:13px;color:#555;">$</span>
            <input class="t-valor" type="number" min="0" step="500" value="${t.valor}" placeholder="3000" style="width:100%;padding:10px;border:2px solid #ddd;border-radius:8px;font-size:15px;"></div>
        <button type="button" onclick="this.parentElement.remove()" aria-label="Quitar" style="padding:8px 12px;background:#f0f0f0;border:none;border-radius:8px;cursor:pointer;">✕</button>`;
    $('tarifas').appendChild(div);
}

function agregarTarifa() { filaTarifa(); }

// Acepta "4.6047, -74.1385" o enlaces de Google Maps con @lat,lng o ?q=lat,lng
function extraerCoordenadas(texto) {
    const t = String(texto).trim();
    let m = t.match(/@(-?\d+\.\d+),\s*(-?\d+\.\d+)/) || t.match(/[?&](?:q|query|ll)=(-?\d+\.\d+),\s*(-?\d+\.\d+)/) || t.match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
    if (!m) return null;
    const lat = Number(m[1]), lng = Number(m[2]);
    return Math.abs(lat) <= 90 && Math.abs(lng) <= 180 ? { lat, lng } : null;
}

function leerCoordenadas(input) {
    const c = extraerCoordenadas(input.value);
    const ayuda = input.parentElement.querySelector('.s-ayuda');
    ayuda.innerHTML = input.value.trim() === '' ? '' : c
        ? `✔ ${c.lat}, ${c.lng} · <a href="https://www.google.com/maps?q=${c.lat},${c.lng}" target="_blank" rel="noopener">Ver en el mapa</a>`
        : '<span style="color:#c00;">No reconozco esas coordenadas. Copia las que muestra Google Maps (ej. 4.6047, -74.1385).</span>';
}

function mensaje(texto, ok) {
    const box = $('mensaje');
    box.textContent = texto;
    box.style.display = 'block';
    box.style.background = ok ? '#d4edda' : '#f8d7da';
    box.style.color = ok ? '#155724' : '#721c24';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function guardar() {
    const tarifas = [...document.querySelectorAll('.tarifa')]
        .map(f => ({ hasta_km: Number(f.querySelector('.t-km').value), valor: Number(f.querySelector('.t-valor').value) }))
        .filter(t => t.hasta_km > 0);

    const sedes = [];
    for (const s of document.querySelectorAll('.sede')) {
        const txt = s.querySelector('.s-coord').value.trim();
        const c = txt ? extraerCoordenadas(txt) : null;
        if (txt && !c) return mensaje('Revisa las coordenadas de la sede marcada en rojo.', false);
        const acepta = s.querySelector('.s-acepta').checked;
        if (acepta && !c) return mensaje('Cada sede que despacha domicilios necesita su ubicación.', false);
        sedes.push({
            id: Number(s.dataset.id),
            latitud: c ? c.lat : null,
            longitud: c ? c.lng : null,
            radio: s.querySelector('.s-radio').value ? Number(s.querySelector('.s-radio').value) : null,
            acepta,
        });
    }

    const btn = $('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando…';
    try {
        const res = await fetch('/domicilios/configuracion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({
                wa_activo: $('wa_activo').checked,
                wa_numero: $('wa_numero').value.trim() || null,
                wa_session_id: $('wa_session_id').value.trim() || null,
                wa_bienvenida: $('wa_bienvenida').value.trim() || null,
                pago_nequi: $('pago_nequi').value.trim() || null,
                pago_daviplata: $('pago_daviplata').value.trim() || null,
                pago_titular: $('pago_titular').value.trim() || null,
                domicilio_minimo: Number($('domicilio_minimo').value) || 0,
                tarifas,
                sedes,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo guardar.');
        }
        mensaje(data.mensaje, true);
    } catch (e) {
        mensaje(e.message, false);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar configuración';
    }
}

(TARIFAS.length ? TARIFAS : [{ hasta_km: 2, valor: 3000 }, { hasta_km: 4, valor: 5000 }]).forEach(filaTarifa);
</script>
@endsection
