@extends('layouts.pos')

@section('titulo', 'Nuevo Usuario')

@section('contenido')
@php
    $actual       = auth()->user();
    $esGerente    = $actual->esGerenteSede();
    $sedeGerente  = $esGerente ? $sedes->first() : null;
@endphp

<div style="max-width:520px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:6px;">Nuevo usuario</h2>
        <p style="font-size:13px;color:#888;margin-bottom:20px;">
            El usuario recibira sus credenciales de acceso por correo electronico.
        </p>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        @if($sedes->isEmpty())
            <div style="padding:12px;border-radius:8px;background:#fff3cd;color:#856404;font-size:14px;margin-bottom:16px;">
                Aun no hay sedes registradas. Crea al menos una sede antes de agregar usuarios.
            </div>
        @endif

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre completo *</label>
            <input type="text" id="f-name" placeholder="Ej: Maria Gonzalez"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Correo electronico *</label>
            <input type="email" id="f-email" placeholder="correo@ejemplo.com"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        @if($esGerente)
            {{-- El gerente de sede solo crea auxiliares de su propia sede --}}
            <input type="hidden" id="f-rol" value="auxiliar">
            <input type="hidden" id="f-sede" value="{{ $sedeGerente?->id }}">

            <div style="padding:12px;border-radius:8px;background:#f5f5f5;font-size:14px;margin-bottom:20px;">
                Se creara un <strong>auxiliar</strong> para la sede
                <strong>{{ $sedeGerente?->nombre }}</strong>.
            </div>
        @else
            <div style="margin-bottom:14px;">
                <label for="f-rol" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Rol *</label>
                <select id="f-rol" onchange="actualizarSedes()"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="auxiliar">Auxiliar — vende, cocina, insumos y cierre de caja</option>
                    <option value="dueno">Gerente — además compras, reportes y usuarios de su sede</option>
                </select>
            </div>

            <div style="margin-bottom:20px;">
                <label for="f-sede" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Sede *</label>
                <select id="f-sede"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="" id="opt-todas">Todas las sedes (propietario)</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $sede->nombre }} — {{ $sede->direccion }}
                        </option>
                    @endforeach
                </select>
                <div id="ayuda-rol" style="font-size:11px;color:#888;margin-top:4px;"></div>
            </div>
        @endif

        <div style="display:flex;gap:8px;">
            <a href="/usuarios"
               style="flex:1;padding:14px;background:#f0f0f0;color:#000;border-radius:10px;text-align:center;text-decoration:none;font-size:15px;">
                Cancelar
            </a>
            <button id="btn-guardar" onclick="guardar()" {{ $sedes->isEmpty() ? 'disabled' : '' }}
                style="flex:2;padding:14px;background:#000;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:bold;cursor:pointer;">
                Crear usuario
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function mensaje(html, ok) {
    const box = document.getElementById('mensaje');
    box.innerHTML = html;
    box.style.display = 'block';
    box.style.background = ok ? '#d4edda' : '#f8d7da';
    box.style.color = ok ? '#155724' : '#721c24';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Auxiliar siempre tiene sede; el gerente puede ser de una sede o propietario (todas las sedes)
function actualizarSedes() {
    const rol = document.getElementById('f-rol');
    if (!rol || rol.type === 'hidden') return;
    const sede = document.getElementById('f-sede');
    const todas = document.getElementById('opt-todas');
    const ayuda = document.getElementById('ayuda-rol');

    if (rol.value === 'auxiliar') {
        todas.hidden = true;
        if (!sede.value) sede.selectedIndex = 1;
        ayuda.textContent = 'El auxiliar solo trabaja en la sede elegida.';
    } else {
        todas.hidden = false;
        ayuda.textContent = sede.value
            ? 'Gerente de la sede elegida: maneja compras, reportes y usuarios de esa sede.'
            : 'Propietario: ve y administra todas las sedes.';
    }
}
document.getElementById('f-sede')?.addEventListener('change', actualizarSedes);
actualizarSedes();

async function guardar() {
    const name  = document.getElementById('f-name').value.trim();
    const email = document.getElementById('f-email').value.trim();
    if (!name || !email) return mensaje('Escribe el nombre y el correo.', false);

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Creando…';

    try {
        const res = await fetch('/usuarios', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                name,
                email,
                rol: document.getElementById('f-rol').value,
                sede_id: document.getElementById('f-sede').value || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo crear el usuario.');
        }

        if (data.password_temporal) {
            // El correo no salió: se muestra la contraseña para entregarla en persona
            const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
            mensaje(esc(data.mensaje) + '<br><br>Contraseña temporal: <strong style="font-size:18px;letter-spacing:1px;">'
                + esc(data.password_temporal) + '</strong><br><a href="/usuarios" style="color:#155724;">Volver a usuarios</a>', true);
            btn.textContent = 'Usuario creado';
            return;
        }

        mensaje(data.mensaje, true);
        setTimeout(() => window.location = '/usuarios', 1500);
    } catch (e) {
        mensaje(e.message, false);
        btn.disabled = false;
        btn.textContent = 'Crear usuario';
    }
}
</script>
@endsection
