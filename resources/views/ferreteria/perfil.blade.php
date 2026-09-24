@extends('layouts.pos')

@section('titulo', 'Mi Negocio')

@section('contenido')
<div style="max-width:600px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:20px;">🏪 Datos de mi negocio</h2>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre</label>
            <input type="text" id="f-nombre" value="{{ $tenant->nombre }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">NIT</label>
            <input type="text" value="{{ $tenant->nit }}" disabled
                style="width:100%;padding:12px;font-size:16px;border:2px solid #eee;border-radius:8px;background:#f9f9f9;color:#999;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Teléfono</label>
                <input type="text" id="f-telefono" value="{{ $tenant->telefono }}"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Ciudad</label>
                <input type="text" id="f-ciudad" value="{{ $tenant->ciudad }}"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Dirección</label>
            <input type="text" id="f-direccion" value="{{ $tenant->direccion }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        <button onclick="guardar()"
            style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:18px;font-weight:bold;cursor:pointer;">
            💾 Guardar cambios
        </button>
    </div>
</div>

<script>
async function guardar() {
    const res = await fetch('/ferreteria/perfil', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nombre:    document.getElementById('f-nombre').value,
            telefono:  document.getElementById('f-telefono').value,
            ciudad:    document.getElementById('f-ciudad').value,
            direccion: document.getElementById('f-direccion').value,
        }),
    });
    const data = await res.json();
    const msg  = document.getElementById('mensaje');
    msg.style.display      = 'block';
    msg.style.background   = data.success ? '#d4edda' : '#f8d7da';
    msg.style.color        = data.success ? '#155724' : '#721c24';
    msg.textContent        = data.mensaje;
}
</script>
@endsection