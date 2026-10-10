@extends('layouts.pos')

@section('titulo', 'Apariencia de la caja')

@section('contenido')
@php
    $estLabel = 'display:block;font-size:13px;color:#555;margin-bottom:4px;';
    $colores = [
        'color_primario'   => ['Color principal', 'Botones, precios y categoría seleccionada'],
        'color_secundario' => ['Color de títulos', 'Nombres de productos y encabezados'],
        'color_fondo'      => ['Color de fondo', 'Fondo de la pantalla de venta'],
    ];
@endphp

<div style="max-width:640px;margin:24px auto;padding:0 16px;">
    <a href="/menu" style="font-size:13px;color:#555;text-decoration:none;">← Volver al menú</a>
    <h1 style="font-size:22px;margin:8px 0 4px;">Apariencia de la caja</h1>
    <p style="font-size:13px;color:#888;margin-bottom:16px;">
        Así verán tus clientes la pantalla de Nueva venta mientras arman su pedido.
    </p>

    @if(session('ok'))
        <div style="padding:10px;border-radius:8px;background:#d4edda;color:#155724;font-size:14px;margin-bottom:12px;">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
        <div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:14px;margin-bottom:12px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="/menu/apariencia" enctype="multipart/form-data"
          style="background:#fff;border-radius:12px;padding:20px;">
        @csrf

        <div style="margin-bottom:18px;">
            <label style="{{ $estLabel }}">Logo del negocio</label>
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <div style="width:96px;height:96px;border-radius:12px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    @if($tenant->logo)
                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo actual" style="max-width:100%;max-height:100%;object-fit:contain;">
                    @else
                        <span style="font-size:12px;color:#999;">Sin logo</span>
                    @endif
                </div>
                <div>
                    <input type="file" name="logo" accept="image/*">
                    <div style="font-size:11px;color:#888;margin-top:4px;">Preferiblemente PNG con fondo transparente.</div>
                    @if($tenant->logo)
                        <label style="font-size:13px;display:block;margin-top:6px;">
                            <input type="checkbox" name="quitar_logo" value="1"> Quitar el logo actual
                        </label>
                    @endif
                </div>
            </div>
        </div>

        @foreach($colores as $campo => [$titulo, $ayuda])
            <div style="margin-bottom:14px;">
                <label style="{{ $estLabel }}" for="{{ $campo }}">{{ $titulo }}</label>
                <div style="display:flex;align-items:center;gap:10px;">
                    <input type="color" id="{{ $campo }}" name="{{ $campo }}" value="{{ $tenant->$campo }}"
                           oninput="document.getElementById('{{ $campo }}-txt').textContent = this.value.toUpperCase(); vistaPrevia();"
                           style="width:56px;height:40px;border:none;background:none;cursor:pointer;">
                    <code id="{{ $campo }}-txt" style="font-size:14px;">{{ $tenant->$campo }}</code>
                </div>
                <div style="font-size:11px;color:#888;margin-top:2px;">{{ $ayuda }}</div>
            </div>
        @endforeach

        <div style="margin:18px 0;">
            <div style="{{ $estLabel }}">Vista previa</div>
            <div id="preview" style="border-radius:12px;padding:14px;">
                <div id="pv-titulo" style="font-weight:bold;font-size:16px;margin-bottom:10px;">{{ $tenant->nombre }}</div>
                <div style="display:flex;gap:10px;">
                    <div style="background:#fff;border-radius:12px;padding:12px;flex:1;">
                        <div id="pv-prod" style="font-weight:bold;">Merengón Grande</div>
                        <div id="pv-precio" style="font-weight:bold;margin-top:4px;">$21.500</div>
                    </div>
                    <button type="button" id="pv-btn" style="border:none;border-radius:10px;color:#fff;font-weight:bold;padding:0 18px;">Cobrar</button>
                </div>
            </div>
        </div>

        <button type="submit"
            style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:17px;font-weight:bold;cursor:pointer;">
            Guardar apariencia
        </button>
    </form>
</div>

@section('scripts')
<script>
function vistaPrevia() {
    const p = document.getElementById('color_primario').value;
    const s = document.getElementById('color_secundario').value;
    const f = document.getElementById('color_fondo').value;
    document.getElementById('preview').style.background = f;
    document.getElementById('pv-titulo').style.color = s;
    document.getElementById('pv-prod').style.color = s;
    document.getElementById('pv-precio').style.color = p;
    document.getElementById('pv-btn').style.background = p;
}
vistaPrevia();
</script>
@endsection
@endsection
