@extends('layouts.pos')

@section('titulo', 'Menú')

@section('contenido')
@php
    $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $nombresDias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
@endphp

<div style="padding:16px;max-width:1100px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <div>
            <h1 style="font-size:22px;">Menú</h1>
            <p style="font-size:13px;color:#888;margin-top:4px;">
                Los cambios aplican a todas las sedes. Toca "Disponible" para ocultar un producto de la caja.
            </p>
        </div>
        <a href="/menu/productos/crear"
           style="padding:10px 20px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-size:14px;white-space:nowrap;">
            + Nuevo producto
        </a>
    </div>

    <div id="mensaje" style="display:none;padding:10px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

    {{-- Productos por categoría --}}
    @foreach($categorias as $categoria)
        <h2 style="font-size:17px;margin:20px 0 10px;">
            {{ $categoria->nombre }}
            <span style="font-size:12px;color:#888;font-weight:normal;">({{ $categoria->productos->count() }})</span>
        </h2>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px;">
            @forelse($categoria->productos as $producto)
                <div id="prod-{{ $producto->id }}"
                     style="background:#fff;border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:8px;opacity:{{ $producto->activo ? '1' : '.5' }};">

                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                        <div style="font-weight:bold;font-size:15px;">{{ $producto->nombre }}</div>
                        <button id="btn-prod-{{ $producto->id }}" onclick="toggleProducto({{ $producto->id }})"
                            style="flex-shrink:0;padding:4px 10px;border:none;border-radius:12px;font-size:11px;font-weight:bold;cursor:pointer;
                                   background:{{ $producto->activo ? '#d4edda' : '#f8d7da' }};
                                   color:{{ $producto->activo ? '#155724' : '#721c24' }};">
                            {{ $producto->activo ? 'Disponible' : 'No disponible' }}
                        </button>
                    </div>

                    @if($producto->descripcion)
                        <div style="font-size:12px;color:#666;line-height:1.4;">{{ $producto->descripcion }}</div>
                    @endif

                    {{-- Precio o variantes --}}
                    @if($producto->variantes->isNotEmpty())
                        <div style="font-size:13px;">
                            @foreach($producto->variantes as $v)
                                <div style="display:flex;justify-content:space-between;{{ $v->activo ? '' : 'text-decoration:line-through;color:#aaa;' }}">
                                    <span>{{ $v->nombre }}</span>
                                    <strong>{{ $dinero($v->precio) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font-size:16px;font-weight:bold;">{{ $dinero($producto->precio_venta) }}</div>
                    @endif

                    {{-- Reglas --}}
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        @if(!empty($producto->dias_disponibles))
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#fff3cd;color:#856404;">
                                Solo {{ collect($producto->dias_disponibles)->map(fn ($d) => $nombresDias[$d] ?? $d)->join(', ') }}
                            </span>
                        @endif
                        @if(!$producto->disponible_domicilio)
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#f0f0f0;color:#555;">
                                Solo en el local
                            </span>
                        @endif
                        @if(!$producto->disponible_local)
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#f0f0f0;color:#555;">
                                Solo a domicilio
                            </span>
                        @endif
                        @if($producto->tipo_menu === 'reventa')
                            <span style="padding:2px 8px;border-radius:10px;font-size:11px;background:#e7f1ff;color:#0c447c;">
                                Reventa · stock {{ $producto->stock }}
                            </span>
                        @endif
                    </div>

                    {{-- Grupos de opciones --}}
                    @if($producto->gruposModificadores->isNotEmpty())
                        <div style="font-size:12px;color:#555;border-top:1px solid #f0f0f0;padding-top:8px;line-height:1.6;">
                            @foreach($producto->gruposModificadores as $grupo)
                                @php
                                    $inc   = (int) $grupo->pivot->incluidos;
                                    $max   = $grupo->pivot->maximo;
                                    $extra = $grupo->pivot->precio_extra ?? $grupo->precio_extra;
                                    $permiteExtra = $extra > 0 && ($max === null || $max > $inc);
                                @endphp
                                <div>
                                    <strong>{{ $grupo->nombre }}:</strong>
                                    @if($inc > 0) {{ $inc }} incluido{{ $inc > 1 ? 's' : '' }} @endif
                                    @if($inc === 0 && !$permiteExtra) opcional @endif
                                    @if($permiteExtra)
                                        {{ $inc > 0 ? '·' : '' }} adicional {{ $dinero($extra) }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <a href="/menu/productos/{{ $producto->id }}/editar"
                       style="margin-top:auto;padding:8px;text-align:center;background:#000;color:#fff;border-radius:8px;font-size:13px;text-decoration:none;">
                        Editar
                    </a>
                </div>
            @empty
                <div style="color:#999;font-size:13px;">Sin productos en esta categoría.</div>
            @endforelse
        </div>
    @endforeach

    {{-- Opciones y toppings --}}
    <h2 style="font-size:17px;margin:28px 0 6px;">Opciones y toppings</h2>
    <p style="font-size:13px;color:#888;margin-bottom:10px;">
        Toca una opción para marcarla como agotada o disponible.
    </p>

    @foreach($grupos as $grupo)
        <div style="background:#fff;border-radius:12px;padding:14px;margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;gap:8px;flex-wrap:wrap;">
                <strong style="font-size:14px;">{{ $grupo->nombre }}</strong>
                <span style="font-size:12px;color:#888;">
                    {{ $grupo->precio_extra > 0 ? 'Adicional ' . $dinero($grupo->precio_extra) . ' c/u' : 'Sin costo adicional' }}
                </span>
            </div>

            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @foreach($grupo->modificadores as $mod)
                    <button id="mod-{{ $mod->id }}" onclick="toggleModificador({{ $mod->id }})"
                        style="padding:6px 12px;border-radius:16px;font-size:12px;cursor:pointer;
                               border:1px solid {{ $mod->activo ? '#ddd' : '#f5c2c7' }};
                               background:{{ $mod->activo ? '#fff' : '#f8d7da' }};
                               color:{{ $mod->activo ? '#000' : '#721c24' }};
                               text-decoration:{{ $mod->activo ? 'none' : 'line-through' }};">
                        {{ $mod->nombre }}
                        @if(!empty($mod->dias_disponibles))
                            <span style="font-size:10px;color:#856404;">(Sáb-Dom)</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@section('scripts')
<script>
async function enviarToggle(url) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    const data = await res.json();

    const msg = document.getElementById('mensaje');
    msg.style.display    = 'block';
    msg.style.background = data.success ? '#d4edda' : '#f8d7da';
    msg.style.color      = data.success ? '#155724' : '#721c24';
    msg.textContent      = data.mensaje ?? data.message ?? 'Ocurrio un error';
    clearTimeout(window._ocultarMsg);
    window._ocultarMsg = setTimeout(() => msg.style.display = 'none', 2500);

    return data;
}

async function toggleProducto(id) {
    const data = await enviarToggle(`/menu/productos/${id}/toggle`);
    if (!data.success) return;

    document.getElementById('prod-' + id).style.opacity = data.activo ? '1' : '.5';
    const btn = document.getElementById('btn-prod-' + id);
    btn.textContent      = data.activo ? 'Disponible' : 'No disponible';
    btn.style.background = data.activo ? '#d4edda' : '#f8d7da';
    btn.style.color      = data.activo ? '#155724' : '#721c24';
}

async function toggleModificador(id) {
    const data = await enviarToggle(`/menu/modificadores/${id}/toggle`);
    if (!data.success) return;

    const chip = document.getElementById('mod-' + id);
    chip.style.background     = data.activo ? '#fff' : '#f8d7da';
    chip.style.color          = data.activo ? '#000' : '#721c24';
    chip.style.borderColor    = data.activo ? '#ddd' : '#f5c2c7';
    chip.style.textDecoration = data.activo ? 'none' : 'line-through';
}
</script>
@endsection
@endsection