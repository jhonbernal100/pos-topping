@extends('layouts.pos')

@section('titulo', 'Editar Cliente')

@section('contenido')
<div style="max-width:600px;margin:24px auto;padding:0 16px;">
    <div style="background:#fff;border-radius:12px;padding:24px;">
        <h2 style="font-size:20px;margin-bottom:20px;">✏️ Editar cliente</h2>

        <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Nombre *</label>
            <input type="text" id="f-nombre" value="{{ $cliente->nombre }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Tipo documento</label>
                <select id="f-tipo-documento"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
                    <option value="CC"  {{ $cliente->tipo_documento === 'CC'  ? 'selected' : '' }}>CC</option>
                    <option value="NIT" {{ $cliente->tipo_documento === 'NIT' ? 'selected' : '' }}>NIT</option>
                    <option value="CE"  {{ $cliente->tipo_documento === 'CE'  ? 'selected' : '' }}>CE</option>
                    <option value="PP"  {{ $cliente->tipo_documento === 'PP'  ? 'selected' : '' }}>Pasaporte</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Número documento</label>
                <input type="text" id="f-numero-documento" value="{{ $cliente->numero_documento }}"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Teléfono</label>
                <input type="text" id="f-telefono" value="{{ $cliente->telefono }}"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            </div>
            <div>
                <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Ciudad</label>
                <input type="text" id="f-ciudad" value="{{ $cliente->ciudad }}"
                    style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
            </div>
        </div>

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Email</label>
            <input type="email" id="f-email" value="{{ $cliente->email }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        <div style="margin-bottom:20px;">
            <label style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Dirección</label>
            <input type="text" id="f-direccion" value="{{ $cliente->direccion }}"
                style="width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;">
        </div>

        @include('clientes._convenio')

        <button onclick="guardar()"
            style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:18px;font-weight:bold;cursor:pointer;">
            💾 Guardar cambios
        </button>

        <a href="/clientes"
            style="display:block;text-align:center;margin-top:12px;color:#555;font-size:14px;">
            ← Volver a clientes
        </a>
    </div>
</div>

<script>
async function guardar() {
    const nombre = document.getElementById('f-nombre').value;
    if (!nombre) { alert('El nombre es obligatorio'); return; }

    const res = await fetch('/clientes/{{ $cliente->id }}/actualizar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            nombre:            nombre,
            tipo_documento:    document.getElementById('f-tipo-documento').value,
            numero_documento:  document.getElementById('f-numero-documento').value,
            telefono:          document.getElementById('f-telefono').value,
            ciudad:            document.getElementById('f-ciudad').value,
            email:             document.getElementById('f-email').value,
            direccion:         document.getElementById('f-direccion').value,
            ajuste_porcentaje: (parseFloat(document.getElementById('f-ajuste').value) || 0) * parseInt(document.getElementById('f-ajuste-tipo').value, 10),
            venta_credito:     document.getElementById('f-credito').checked,
            dias_credito:      parseInt(document.getElementById('f-dias').value || '0', 10),
            pide_codigo:       document.getElementById('f-codigo').checked,
        }),
    });

    const data = await res.json();
    const msg  = document.getElementById('mensaje');
    msg.style.display    = 'block';
    msg.style.background = data.success ? '#d4edda' : '#f8d7da';
    msg.style.color      = data.success ? '#155724' : '#721c24';
    msg.textContent      = data.mensaje ?? data.message;

    if (data.success) {
        setTimeout(() => window.location.href = '/clientes', 1500);
    }
}
</script>
@endsection