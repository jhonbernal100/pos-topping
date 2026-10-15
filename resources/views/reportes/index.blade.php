@extends('layouts.pos')

@section('titulo', 'Reportes')

@section('contenido')
@php
    $input = 'width:100%;padding:9px;font-size:14px;border:2px solid #ddd;border-radius:8px;';
    $boton = 'width:100%;padding:11px;background:#000;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;';
    $card  = 'background:#fff;border-radius:12px;padding:18px;display:flex;flex-direction:column;gap:10px;';
    $mesActual = now()->month;
@endphp

<div style="padding:16px;max-width:1050px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
        <div>
            <h1 style="font-size:22px;">Reportes</h1>
            <div style="font-size:13px;color:#888;">Se abren en PDF en una pestaña nueva, listos para imprimir o compartir.</div>
        </div>
        <div style="min-width:220px;">
            <label for="sede" style="display:block;font-size:12px;color:#555;margin-bottom:4px;">Sede</label>
            <select id="sede" style="{{ $input }}">
                @foreach($sedes as $s)
                    <option value="{{ $s->id }}" {{ $s->id === $sedeActiva ? 'selected' : '' }}>{{ $s->nombre }}</option>
                @endforeach
                @if($puedeVerTodas)
                    <option value="todas">Todas las sedes</option>
                @endif
            </select>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px;">

        {{-- Ventas --}}
        <div style="{{ $card }}border-top:4px solid #27A88A;">
            <div>
                <h3 style="font-size:16px;">Ventas</h3>
                <p style="font-size:13px;color:#888;margin-top:4px;">Totales, pagos por método, horas pico, productos más vendidos y lista de pedidos del día.</p>
            </div>
            <select id="v-periodo" onchange="mostrarRango('v')" aria-label="Periodo de ventas" style="{{ $input }}">
                <option value="hoy">Hoy</option>
                <option value="ayer">Ayer</option>
                <option value="semana">Esta semana</option>
                <option value="mes">Este mes</option>
                <option value="mes_anterior">Mes anterior</option>
                <option value="rango">Elegir fechas…</option>
            </select>
            <div id="v-rango" style="display:none;gap:8px;">
                <input type="date" id="v-desde" value="{{ now()->startOfMonth()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Desde" style="{{ $input }}">
                <input type="date" id="v-hasta" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Hasta" style="{{ $input }}">
            </div>
            <button onclick="abrir('ventas', 'v')" style="{{ $boton }}margin-top:auto;">Generar PDF</button>
        </div>

        {{-- Estado de resultados --}}
        <div style="{{ $card }}border-top:4px solid #563B38;">
            <div>
                <h3 style="font-size:16px;">Estado de resultados</h3>
                <p style="font-size:13px;color:#888;margin-top:4px;">Ventas menos costo de insumos (según recetas), mermas y gastos: la utilidad del mes.</p>
            </div>
            <div style="display:flex;gap:8px;">
                <select id="r-mes" aria-label="Mes" style="{{ $input }}flex:1;">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $m === $mesActual ? 'selected' : '' }}>{{ ucfirst(\Carbon\Carbon::create(null, $m, 1)->locale('es')->monthName) }}</option>
                    @endforeach
                </select>
                <select id="r-anio" aria-label="Año" style="{{ $input }}width:100px;">
                    @foreach(range(now()->year, now()->year - 2) as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <button onclick="abrirResultados()" style="{{ $boton }}margin-top:auto;">Generar PDF</button>
        </div>

        {{-- Insumos --}}
        <div style="{{ $card }}border-top:4px solid #E4387A;">
            <div>
                <h3 style="font-size:16px;">Inventario de insumos</h3>
                <p style="font-size:13px;color:#888;margin-top:4px;">Existencias, costo y valor por insumo. Con varias sedes, una columna por sede.</p>
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-size:14px;">
                <input type="checkbox" id="i-alertas"> Solo agotados o bajos
            </label>
            <button onclick="abrirInsumos()" style="{{ $boton }}margin-top:auto;">Generar PDF</button>
        </div>

        {{-- Compras --}}
        <div style="{{ $card }}border-top:4px solid #f0a500;">
            <div>
                <h3 style="font-size:16px;">Compras a proveedores</h3>
                <p style="font-size:13px;color:#888;margin-top:4px;">Facturas registradas, totales e impuestos por proveedor.</p>
            </div>
            <select id="c-periodo" onchange="mostrarRango('c')" aria-label="Periodo de compras" style="{{ $input }}">
                <option value="mes">Este mes</option>
                <option value="mes_anterior">Mes anterior</option>
                <option value="semana">Esta semana</option>
                <option value="rango">Elegir fechas…</option>
            </select>
            <div id="c-rango" style="display:none;gap:8px;">
                <input type="date" id="c-desde" value="{{ now()->startOfMonth()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Desde" style="{{ $input }}">
                <input type="date" id="c-hasta" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" aria-label="Hasta" style="{{ $input }}">
            </div>
            <button onclick="abrir('compras', 'c')" style="{{ $boton }}margin-top:auto;">Generar PDF</button>
        </div>
    </div>

    <p style="font-size:12px;color:#888;margin-top:14px;">
        El kardex de cada insumo está en <a href="/insumos" style="color:#000;">Insumos</a> → Kardex. El cierre de caja diario, en
        <a href="/caja/cierres" style="color:#000;">Cierre de caja</a>.
    </p>
</div>
@endsection

@section('scripts')
<script>
const val = id => document.getElementById(id).value;

function mostrarRango(p) {
    document.getElementById(p + '-rango').style.display = val(p + '-periodo') === 'rango' ? 'flex' : 'none';
}

function ir(ruta, params) {
    params.sede = val('sede');
    window.open('/reportes/' + ruta + '?' + new URLSearchParams(params).toString(), '_blank');
}

function abrir(ruta, p) {
    const params = { periodo: val(p + '-periodo') };
    if (params.periodo === 'rango') {
        params.desde = val(p + '-desde');
        params.hasta = val(p + '-hasta');
    }
    ir(ruta, params);
}

function abrirResultados() {
    ir('resultados', { mes: val('r-mes'), anio: val('r-anio') });
}

function abrirInsumos() {
    ir('insumos', document.getElementById('i-alertas').checked ? { alertas: 1 } : {});
}
</script>
@endsection
