@extends('layouts.pos')

@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')

@section('contenido')
@php
    $dinero        = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $nombresDias   = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
    $diasActuales  = $producto->dias_disponibles ?? [];
    $todosLosDias  = empty($diasActuales);
    $gruposProd    = $producto->exists ? $producto->gruposModificadores->keyBy('id') : collect();
    $variantesJs   = $producto->exists
        ? $producto->variantes->map(fn ($v) => ['id' => $v->id, 'nombre' => $v->nombre, 'precio' => $v->precio, 'activo' => $v->activo])->values()
        : collect();

    $estInput = 'width:100%;padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;';
    $estLabel = 'display:block;font-size:13px;color:#555;margin-bottom:4px;';
    $estCard  = 'background:#fff;border-radius:12px;padding:18px;margin-bottom:14px;';
@endphp

<div style="max-width:760px;margin:24px auto;padding:0 16px;">
    <a href="/menu" style="font-size:13px;color:#555;text-decoration:none;">← Volver al menú</a>
    <h1 style="font-size:22px;margin:8px 0 16px;">
        {{ $producto->exists ? 'Editar: ' . $producto->nombre : 'Nuevo producto' }}
    </h1>

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    {{-- Datos --}}
    <div style="{{ $estCard }}">
        <h2 style="font-size:16px;margin-bottom:12px;">Datos del producto</h2>

        <div style="margin-bottom:12px;">
            <label style="{{ $estLabel }}">Nombre *</label>
            <input type="text" id="f-nombre" value="{{ $producto->nombre }}" placeholder="Ej: Merengón Grande" style="{{ $estInput }}">
        </div>

        <div style="margin-bottom:12px;">
            <label style="{{ $estLabel }}">Descripción</label>
            <textarea id="f-descripcion" rows="2" placeholder="Qué incluye el producto" style="{{ $estInput }}">{{ $producto->descripcion }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div>
                <label style="{{ $estLabel }}">Categoría *</label>
                <select id="f-categoria" style="{{ $estInput }}">
                    @foreach($categorias as $c)
                        <option value="{{ $c->id }}" {{ (int) $producto->categoria_menu_id === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $estLabel }}">Tipo</label>
                <select id="f-tipo" style="{{ $estInput }}">
                    <option value="preparado" {{ $producto->tipo_menu === 'preparado' ? 'selected' : '' }}>Preparado (se arma con insumos)</option>
                    <option value="reventa"   {{ $producto->tipo_menu === 'reventa' ? 'selected' : '' }}>Reventa (se vende tal cual, con stock)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Precio y variantes --}}
    <div style="{{ $estCard }}">
        <h2 style="font-size:16px;margin-bottom:12px;">Precio</h2>

        <div id="bloque-precio" style="margin-bottom:14px;">
            <label style="{{ $estLabel }}">Precio de venta *</label>
            <input type="number" id="f-precio" min="0" step="100" value="{{ (int) $producto->precio_venta }}" style="{{ $estInput }}max-width:220px;">
        </div>

        <div id="nota-precio" style="display:none;font-size:12px;color:#856404;background:#fff3cd;padding:8px 10px;border-radius:8px;margin-bottom:12px;">
            Este producto tiene variantes: el precio base es el de la variante activa más económica.
        </div>

        <div style="font-size:14px;font-weight:bold;margin-bottom:4px;">Variantes</div>
        <p style="font-size:12px;color:#888;margin-bottom:10px;">
            Úsalas cuando el precio cambia según la opción (ej: vainilla francesa o yogurt; porción o torta completa).
        </p>
        <div id="lista-variantes"></div>
        <button type="button" onclick="agregarVariante()"
            style="padding:8px 14px;background:#f0f0f0;border:none;border-radius:8px;font-size:13px;cursor:pointer;">
            + Agregar variante
        </button>
    </div>

    {{-- Disponibilidad --}}
    <div style="{{ $estCard }}">
        <h2 style="font-size:16px;margin-bottom:12px;">Disponibilidad</h2>

        <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px;font-size:14px;">
            <label><input type="checkbox" id="f-local" {{ $producto->disponible_local ? 'checked' : '' }}> Se vende en el local</label>
            <label><input type="checkbox" id="f-domicilio" {{ $producto->disponible_domicilio ? 'checked' : '' }}> Se vende a domicilio</label>
        </div>

        <label style="font-size:14px;">
            <input type="checkbox" id="f-todos-dias" {{ $todosLosDias ? 'checked' : '' }} onchange="toggleDias()"> Todos los días
        </label>
        <div id="dias" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
            @foreach($nombresDias as $num => $dia)
                <label style="padding:6px 10px;border:1px solid #ddd;border-radius:8px;font-size:13px;">
                    <input type="checkbox" class="dia" value="{{ $num }}" {{ in_array($num, $diasActuales, true) ? 'checked' : '' }}> {{ $dia }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- Grupos de opciones --}}
    <div style="{{ $estCard }}">
        <h2 style="font-size:16px;margin-bottom:4px;">Opciones del producto</h2>
        <p style="font-size:12px;color:#888;margin-bottom:12px;">
            <strong>Incluidos:</strong> sin costo. <strong>Mínimo:</strong> cuántas debe elegir el cliente.
            <strong>Máximo</strong> vacío = sin tope; lo que pase de los incluidos se cobra al precio del grupo.
        </p>

        @foreach($grupos as $grupo)
            @php $pv = $gruposProd->get($grupo->id)?->pivot; @endphp
            <div class="fila-grupo" data-grupo="{{ $grupo->id }}"
                 style="border:1px solid #eee;border-radius:10px;padding:12px;margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:bold;flex-wrap:wrap;">
                    <input type="checkbox" class="g-usar" {{ $pv ? 'checked' : '' }} onchange="actualizarGrupo(this)">
                    {{ $grupo->nombre }}
                    <span style="font-size:11px;color:#888;font-weight:normal;">
                        {{ $grupo->modificadores_count }} opciones ·
                        {{ $grupo->precio_extra > 0 ? 'adicional ' . $dinero($grupo->precio_extra) : 'sin costo' }}
                    </span>
                </label>

                <div class="g-campos" style="display:{{ $pv ? 'grid' : 'none' }};grid-template-columns:repeat(3,1fr);gap:8px;margin-top:10px;">
                    <div>
                        <label style="{{ $estLabel }}">Incluidos</label>
                        <input type="number" min="0" max="20" class="g-incluidos" value="{{ $pv->incluidos ?? 0 }}" style="{{ $estInput }}">
                    </div>
                    <div>
                        <label style="{{ $estLabel }}">Mínimo</label>
                        <input type="number" min="0" max="20" class="g-minimo" value="{{ $pv->minimo ?? 0 }}" style="{{ $estInput }}">
                    </div>
                    <div>
                        <label style="{{ $estLabel }}">Máximo</label>
                        <input type="number" min="0" max="20" class="g-maximo" value="{{ $pv?->maximo }}" placeholder="Sin tope" style="{{ $estInput }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button id="btn-guardar" onclick="guardar()"
        style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:18px;font-weight:bold;cursor:pointer;">
        Guardar producto
    </button>
    <a href="/menu" style="display:block;text-align:center;margin:12px 0 24px;color:#555;font-size:14px;">Cancelar</a>
</div>

@section('scripts')
<script>
let variantes = @json($variantesJs);
const urlGuardar = @json($producto->exists ? "/menu/productos/{$producto->id}/actualizar" : '/menu/productos');
const estInput = 'padding:10px;font-size:15px;border:2px solid #ddd;border-radius:8px;width:100%;';

function escaparHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderVariantes() {
    const cont = document.getElementById('lista-variantes');
    cont.innerHTML = variantes.map((v, i) => `
        <div style="display:grid;grid-template-columns:1fr 130px auto auto;gap:8px;align-items:center;margin-bottom:8px;">
            <input value="${escaparHtml(v.nombre)}" placeholder="Ej: Yogurt"
                   oninput="variantes[${i}].nombre = this.value" style="${estInput}">
            <input type="number" min="0" step="100" value="${parseInt(v.precio || 0, 10)}"
                   oninput="variantes[${i}].precio = parseInt(this.value || 0, 10)" style="${estInput}">
            <label style="font-size:12px;white-space:nowrap;">
                <input type="checkbox" ${v.activo ? 'checked' : ''} onchange="variantes[${i}].activo = this.checked"> Activa
            </label>
            <button type="button" onclick="quitarVariante(${i})" aria-label="Quitar variante"
                    style="padding:8px 10px;background:#f8d7da;color:#721c24;border:none;border-radius:8px;cursor:pointer;">✕</button>
        </div>`).join('');

    const hay = variantes.length > 0;
    document.getElementById('bloque-precio').style.display = hay ? 'none' : 'block';
    document.getElementById('nota-precio').style.display   = hay ? 'block' : 'none';
}

function agregarVariante() {
    variantes.push({ id: null, nombre: '', precio: 0, activo: true });
    renderVariantes();
}

function quitarVariante(i) {
    variantes.splice(i, 1);
    renderVariantes();
}

function toggleDias() {
    const todos = document.getElementById('f-todos-dias').checked;
    document.getElementById('dias').style.display = todos ? 'none' : 'flex';
}

function actualizarGrupo(chk) {
    chk.closest('.fila-grupo').querySelector('.g-campos').style.display = chk.checked ? 'grid' : 'none';
}

function mostrarMensaje(ok, texto) {
    const msg = document.getElementById('mensaje');
    msg.style.display    = 'block';
    msg.style.background = ok ? '#d4edda' : '#f8d7da';
    msg.style.color      = ok ? '#155724' : '#721c24';
    msg.textContent      = texto;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function guardar() {
    const nombre = document.getElementById('f-nombre').value.trim();
    if (!nombre) { alert('El nombre es obligatorio'); return; }

    if (variantes.some(v => !String(v.nombre).trim())) {
        alert('Cada variante necesita un nombre');
        return;
    }

    let dias = null;
    if (!document.getElementById('f-todos-dias').checked) {
        dias = [...document.querySelectorAll('.dia:checked')].map(c => parseInt(c.value, 10));
        if (!dias.length) { alert('Selecciona al menos un día'); return; }
    }

    const grupos = [...document.querySelectorAll('.fila-grupo')]
        .filter(f => f.querySelector('.g-usar').checked)
        .map(f => {
            const max = f.querySelector('.g-maximo').value;
            return {
                grupo_id:  parseInt(f.dataset.grupo, 10),
                incluidos: parseInt(f.querySelector('.g-incluidos').value || 0, 10),
                minimo:    parseInt(f.querySelector('.g-minimo').value || 0, 10),
                maximo:    max === '' ? null : parseInt(max, 10),
            };
        });

    for (const g of grupos) {
        if (g.maximo !== null && (g.minimo > g.maximo || g.incluidos > g.maximo)) {
            alert('En cada grupo, incluidos y mínimo no pueden superar el máximo');
            return;
        }
    }

    const payload = {
        nombre,
        descripcion:          document.getElementById('f-descripcion').value.trim(),
        categoria_menu_id:    parseInt(document.getElementById('f-categoria').value, 10),
        tipo_menu:            document.getElementById('f-tipo').value,
        precio_venta:         parseInt(document.getElementById('f-precio').value || 0, 10),
        disponible_local:     document.getElementById('f-local').checked,
        disponible_domicilio: document.getElementById('f-domicilio').checked,
        dias_disponibles:     dias,
        variantes:            variantes.map(v => ({ ...v, nombre: String(v.nombre).trim() })),
        grupos,
    };

    const btn = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        const res = await fetch(urlGuardar, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        mostrarMensaje(!!data.success, data.mensaje ?? data.message ?? 'Ocurrio un error');

        if (data.success) {
            setTimeout(() => window.location.href = '/menu', 1200);
            return;
        }
    } catch (e) {
        mostrarMensaje(false, 'No se pudo conectar. Intenta de nuevo.');
    }

    btn.disabled = false;
    btn.textContent = 'Guardar producto';
}

renderVariantes();
toggleDias();
</script>
@endsection
@endsection