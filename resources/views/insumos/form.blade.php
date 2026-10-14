@extends('layouts.pos')

@section('titulo', $insumo->exists ? 'Editar insumo' : 'Nuevo insumo')

@section('contenido')
@php
    $input = 'width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;';
    $label = 'display:block;font-size:13px;color:#555;margin-bottom:4px;';
@endphp

<div style="max-width:560px;margin:24px auto;padding:0 16px;">
    <a href="/insumos" style="font-size:13px;color:#555;text-decoration:none;">← Volver a insumos</a>
    <h1 style="font-size:22px;margin:8px 0 16px;">{{ $insumo->exists ? 'Editar: ' . $insumo->nombre : 'Nuevo insumo' }}</h1>

    @if($errors->any())
        <div style="padding:10px;border-radius:8px;background:#f8d7da;color:#721c24;font-size:14px;margin-bottom:12px;">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $insumo->exists ? '/insumos/' . $insumo->id . '/actualizar' : '/insumos' }}"
          style="background:#fff;border-radius:12px;padding:20px;">
        @csrf

        <div style="margin-bottom:14px;">
            <label style="{{ $label }}" for="nombre">Nombre *</label>
            <input id="nombre" name="nombre" required maxlength="120" value="{{ old('nombre', $insumo->nombre) }}"
                   placeholder="Ej: Helado de vainilla francesa" style="{{ $input }}">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
            <div>
                <label style="{{ $label }}" for="unidad">Unidad de control *</label>
                <select id="unidad" name="unidad" style="{{ $input }}">
                    @foreach(\App\Models\Insumo::UNIDADES as $k => $txt)
                        <option value="{{ $k }}" {{ old('unidad', $insumo->unidad) === $k ? 'selected' : '' }}>{{ ucfirst($txt) }} ({{ $k }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="{{ $label }}" for="categoria">Categoría</label>
                <select id="categoria" name="categoria" style="{{ $input }}">
                    <option value="">Sin categoría</option>
                    @foreach($categorias as $c)
                        <option value="{{ $c }}" {{ old('categoria', $insumo->categoria) === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="font-size:12px;color:#888;margin:-6px 0 14px;line-height:1.5;">
            Usa <strong>gramos</strong> para helado, frutas y toppings; <strong>mililitros</strong> para salsas y líquidos;
            <strong>unidades</strong> para vasos, conos, cucharas o botellas.
        </div>

        <div style="margin-bottom:14px;">
            <label style="{{ $label }}" for="stock_minimo">Stock mínimo en esta sede</label>
            <input id="stock_minimo" name="stock_minimo" type="number" min="0" step="any"
                   value="{{ old('stock_minimo', $stockMin ?: '') }}" placeholder="0" style="{{ $input }}">
            <div style="font-size:12px;color:#888;margin-top:4px;">Cuando la existencia baje de aquí, el insumo aparece en las alertas.</div>
        </div>

        @if($insumo->exists)
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;margin-bottom:16px;">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" {{ old('activo', $insumo->activo) ? 'checked' : '' }}> Insumo activo
            </label>
        @endif

        <button type="submit"
            style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:17px;font-weight:bold;cursor:pointer;">
            Guardar
        </button>
    </form>

    @unless($insumo->exists)
        <p style="font-size:13px;color:#888;margin-top:12px;">
            Después de crearlo, usa <strong>Contar</strong> en la lista para cargar la existencia inicial de cada sede.
        </p>
    @endunless
</div>
@endsection
