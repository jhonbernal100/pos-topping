@php
    $c       = $cliente ?? null;
    $ajuste  = (float) ($c->ajuste_porcentaje ?? 0);
    $estilo  = 'width:100%;padding:12px;font-size:16px;border:2px solid #ddd;border-radius:8px;';
@endphp
<div style="border:2px solid #f0e6c8;background:#fffbf0;border-radius:10px;padding:16px;margin-bottom:20px;">
    <div style="font-size:15px;font-weight:bold;margin-bottom:4px;">Convenio en la caja</div>
    <div style="font-size:13px;color:#666;margin-bottom:14px;">
        Para plataformas como Rappi: al elegir este cliente en Nueva venta se aplica el % a cada producto de la carta.
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label for="f-ajuste-tipo" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Precio</label>
            <select id="f-ajuste-tipo" style="{{ $estilo }}">
                <option value="1" {{ $ajuste >= 0 ? 'selected' : '' }}>Recargo sobre la carta</option>
                <option value="-1" {{ $ajuste < 0 ? 'selected' : '' }}>Descuento sobre la carta</option>
            </select>
        </div>
        <div>
            <label for="f-ajuste" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Porcentaje (%)</label>
            <input type="number" id="f-ajuste" min="0" max="200" step="0.5" inputmode="decimal"
                   value="{{ $ajuste ? abs($ajuste) + 0 : '' }}" placeholder="0 = precio de la carta" style="{{ $estilo }}">
        </div>
    </div>

    <label style="display:flex;gap:10px;align-items:center;font-size:14px;margin-bottom:10px;cursor:pointer;">
        <input type="checkbox" id="f-credito" {{ ($c->venta_credito ?? false) ? 'checked' : '' }} style="width:20px;height:20px;"
               onchange="document.getElementById('bloque-dias').style.display = this.checked ? 'block' : 'none'">
        No se cobra en caja: queda por cobrar (paga después)
    </label>
    <div id="bloque-dias" style="display:{{ ($c->venta_credito ?? false) ? 'block' : 'none' }};margin:0 0 12px 30px;">
        <label for="f-dias" style="display:block;font-size:13px;color:#555;margin-bottom:4px;">Paga a los (días)</label>
        <input type="number" id="f-dias" min="0" max="180" inputmode="numeric" value="{{ $c->dias_credito ?? 20 }}" style="{{ $estilo }}max-width:140px;">
    </div>

    <label style="display:flex;gap:10px;align-items:center;font-size:14px;cursor:pointer;">
        <input type="checkbox" id="f-codigo" {{ ($c->pide_codigo ?? false) ? 'checked' : '' }} style="width:20px;height:20px;">
        Pedir el código del pedido de la plataforma al vender
    </label>
</div>
