@extends('layouts.pos')

@section('titulo', 'Editar Usuario')

@section('contenido')
@php
    $actual      = auth()->user();
    $esGerente   = $actual->esGerenteSede();
    $esUnoMismo  = $usuario->id === $actual->id;
    $bloquearRol = $esGerente || $esUnoMismo;
@endphp

<div style="max-width:520px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:6px;">Editar usuario</h2>
        <p style="font-size:13px;color:#888;margin-bottom:20px;">
            Deja la contrasena en blanco para no cambiarla.
        </p>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre completo *</label>
            <input type="text" id="f-name" value="{{ $usuario->name }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Correo electronico *</label>
            <input type="email" id="f-email" value="{{ $usuario->email }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        @if($bloquearRol)
            <input type="hidden" id="f-rol" value="{{ $usuario->rol }}">
            <input type="hidden" id="f-sede" value="{{ $usuario->sede_id }}">

            <div style="padding:12px;border-radius:8px;background:#f5f5f5;font-size:14px;margin-bottom:14px;">
                <strong>Rol:</strong>
                @if($usuario->esPropietario()) Propietario — todas las sedes
                @elseif($usuario->esGerenteSede()) Gerente
                @else Auxiliar
                @endif
                <br>
                <strong>Sede:</strong> {{ $usuario->sede?->nombre ?? 'Todas las sedes' }}
                @if($esUnoMismo)
                    <div style="font-size:11px;color:#888;margin-top:6px;">
                        No puedes cambiar tu propio rol ni tu sede.
                    </div>
                @endif
            </div>
        @else
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Rol *</label>
                <select id="f-rol" onchange="actualizarSedes()"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="auxiliar" {{ $usuario->rol === 'auxiliar' ? 'selected' : '' }}>
                        Auxiliar — vende y registra inventario
                    </option>
                    <option value="dueno" {{ $usuario->rol === 'dueno' ? 'selected' : '' }}>
                        Gerente — acceso completo
                    </option>
                </select>
            </div>

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Sede *</label>
                <select id="f-sede"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="" id="opt-todas" {{ is_null($usuario->sede_id) ? 'selected' : '' }}>
                        Todas las sedes (propietario)
                    </option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ (int) $usuario->sede_id === $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }} — {{ $sede->direccion }}
                        </option>
                    @endforeach
                </select>
                <div id="ayuda-rol" style="font-size:11px;color:#888;margin-top:4px;"></div>
            </div>
        @endif

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">
                Nueva contrasena (opcional)
            </label>
            <input type="password" id="f-password" placeholder="Minimo 8 caracteres"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            <div style="font-size:11px;color:#888;margin-top:3px;">
                Deja en blanco para mantener la contrasena actual
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="/usuarios"
               style="flex:1;padding:14px;background:#f0f0f0;color:#000;border-radius:10px;text-align:center;text-decoration:none;font-size:15px;">
                Cancelar
            </a>
            <button id="btn-guardar" onclick="guardar()"
                style="flex:2;padding:14px;background:#000;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:bold;cursor:pointer;">
                Guardar cambios
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function mensaje(texto, ok) {
    const box = document.getElementById('mensaje');
    box.textContent = texto;
    box.style.display = 'block';
    box.style.background = ok ? '#d4edda' : '#f8d7da';
    box.style.color = ok ? '#155724' : '#721c24';
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

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
    const name     = document.getElementById('f-name').value.trim();
    const email    = document.getElementById('f-email').value.trim();
    const password = document.getElementById('f-password').value;
    if (!name || !email) return mensaje('Escribe el nombre y el correo.', false);
    if (password && password.length < 8) return mensaje('La nueva contraseña debe tener al menos 8 caracteres.', false);

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    try {
        const res = await fetch('/usuarios/{{ $usuario->id }}/actualizar', {
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
                password: password || null,
            }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(primero ?? data.mensaje ?? data.message ?? 'No se pudo guardar.');
        }
        mensaje(data.mensaje, true);
        setTimeout(() => window.location = '/usuarios', 1200);
    } catch (e) {
        mensaje(e.message, false);
        btn.disabled = false;
        btn.textContent = 'Guardar cambios';
    }
}
</script>
@endsection
