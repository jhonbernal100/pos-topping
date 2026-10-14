@extends('layouts.pos')

@section('titulo', 'Cierre de caja')

@section('contenido')
@if(!$sede)
    <div style="max-width:520px;margin:40px auto;padding:20px;background:#fff3cd;color:#856404;border-radius:12px;">
        No hay una sede activa. Selecciona una sede en el encabezado para hacer el cierre.
    </div>
@else
@php
    $dinero      = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $esGerente   = auth()->user()->rol === 'dueno';
    $bloqueado   = $existente && !$esGerente;
    $efectivoVen = (int) $resumen['efectivo'];
    $esHoy       = $fecha->isToday();
    $cambiado    = $existente && (int) $existente->total_ventas !== (int) $resumen['total'];
    $card        = 'background:#fff;border-radius:12px;padding:16px;margin-bottom:14px;';
    $input       = 'width:100%;padding:12px;font-size:18px;border:2px solid #ddd;border-radius:8px;';
    $label       = 'display:block;font-size:13px;color:#555;margin-bottom:4px;';
@endphp

<div style="max-width:1100px;margin:0 auto;padding:16px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
        <div>
            <h1 style="font-size:22px;">Cierre de caja</h1>
            <div style="font-size:13px;color:#888;">
                Sede {{ $sede->nombre }} · {{ $fecha->format('d/m/Y') }}{{ $esHoy ? ' (hoy)' : '' }}
            </div>
        </div>
        @if($esGerente)
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="date" value="{{ $fecha->toDateString() }}" max="{{ today()->toDateString() }}"
                       onchange="if (this.value) location.href = '/caja/cierre?fecha=' + this.value"
                       aria-label="Fecha del cierre"
                       style="padding:9px;border:2px solid #ddd;border-radius:8px;font-size:14px;">
                <a href="/caja/cierres"
                   style="padding:10px 16px;background:#fff;color:#000;border:1px solid #ddd;border-radius:8px;text-decoration:none;font-size:14px;">
                    Historial
                </a>
            </div>
        @endif
    </div>

    @if(session('ok'))
        <div style="padding:12px;border-radius:10px;background:#d4edda;color:#155724;font-size:15px;margin-bottom:12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
            <span>{{ session('ok') }}</span>
            @if(session('imprimir'))
                <a href="/caja/cierres/{{ session('imprimir') }}/imprimir" target="_blank"
                   style="padding:8px 14px;background:#155724;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;">
                    Imprimir cierre
                </a>
            @endif
        </div>
    @endif

    @if($errors->any())
        <div style="padding:12px;border-radius:10px;background:#f8d7da;color:#721c24;font-size:14px;margin-bottom:12px;">
            {{ $errors->first() }}
        </div>
    @endif

    @if($existente && !session('ok'))
        @php
            $estado = $existente->estadoArqueo();
            [$fondo, $color] = match ($estado) {
                'cuadra'   => ['#d4edda', '#155724'],
                'sobrante' => ['#e7f1ff', '#0c447c'],
                default    => ['#f8d7da', '#721c24'],
            };
        @endphp
        <div style="padding:12px;border-radius:10px;background:{{ $fondo }};color:{{ $color }};font-size:14px;margin-bottom:12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
            <span>
                Caja cerrada por <strong>{{ $existente->usuario?->name ?? 'usuario' }}</strong>
                a las {{ $existente->updated_at->format('h:i a') }} ·
                @if($estado === 'cuadra') El efectivo cuadró.
                @elseif($estado === 'sobrante') Sobrante de {{ $dinero($existente->diferencia) }}.
                @else Faltante de {{ $dinero(abs($existente->diferencia)) }}.
                @endif
            </span>
            <a href="/caja/cierres/{{ $existente->id }}/imprimir" target="_blank"
               style="padding:8px 14px;background:#fff;color:#000;border-radius:8px;text-decoration:none;font-size:13px;">
                Imprimir
            </a>
        </div>
    @endif

    @if($cambiado)
        <div style="padding:12px;border-radius:10px;background:#fff3cd;color:#856404;font-size:14px;margin-bottom:12px;">
            Hubo ventas o anulaciones después del cierre (antes {{ $dinero($existente->total_ventas) }}, ahora {{ $dinero($resumen['total']) }}).
            {{ $esGerente ? 'Vuelve a contar el efectivo y actualiza el cierre.' : 'Avisa a tu gerente para que actualice el cierre.' }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;align-items:start;">

        {{-- Resumen del día --}}
        <div>
            <div style="{{ $card }}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <div style="font-size:12px;color:#888;">Ventas del día</div>
                        <div style="font-size:26px;font-weight:bold;">{{ $dinero($resumen['total']) }}</div>
                        <div style="font-size:12px;color:#555;">{{ $resumen['num_ventas'] }} pedidos</div>
                    </div>
                    <div>
                        <div style="font-size:12px;color:#888;">Anuladas</div>
                        <div style="font-size:20px;font-weight:bold;color:{{ $resumen['num_anuladas'] ? '#c00' : '#555' }};">
                            {{ $resumen['num_anuladas'] }}
                        </div>
                        <div style="font-size:12px;color:#555;">{{ $dinero($resumen['total_anuladas']) }}</div>
                    </div>
                </div>
                @if($resumen['primera'])
                    <div style="font-size:12px;color:#888;margin-top:10px;">
                        Primera venta {{ $resumen['primera'] }} · Última {{ $resumen['ultima'] }}
                    </div>
                @endif
            </div>

            <div style="{{ $card }}">
                <h2 style="font-size:15px;margin-bottom:10px;">Por medio de pago</h2>
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    @foreach($resumen['metodos'] as $m)
                        <tr style="border-bottom:1px solid #f2f2f2;">
                            <td style="padding:8px 0;">{{ $m['nombre'] }}</td>
                            <td style="padding:8px 0;text-align:center;color:#888;font-size:12px;">{{ $m['n'] }} pagos</td>
                            <td style="padding:8px 0;text-align:right;font-weight:bold;">{{ $dinero($m['total']) }}</td>
                        </tr>
                    @endforeach
                </table>
                <div style="font-size:12px;color:#888;margin-top:8px;">
                    Nequi, Daviplata y tarjeta no entran a la caja: compáralos con la app de cada medio.
                </div>
            </div>

            <div style="{{ $card }}">
                <h2 style="font-size:15px;margin-bottom:10px;">Productos más vendidos</h2>
                @forelse($resumen['productos'] as $p)
                    <div style="display:flex;justify-content:space-between;font-size:14px;padding:5px 0;border-bottom:1px solid #f7f7f7;">
                        <span>{{ $p['cantidad'] }} × {{ $p['nombre'] }}</span>
                        <span style="color:#555;">{{ $dinero($p['total']) }}</span>
                    </div>
                @empty
                    <div style="font-size:13px;color:#999;">Aún no hay ventas en este día.</div>
                @endforelse
            </div>
        </div>

        {{-- Arqueo del efectivo --}}
        <form method="POST" action="/caja/cierre" style="{{ $card }}" onsubmit="return confirmarCierre()">
            @csrf
            <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">

            <h2 style="font-size:15px;margin-bottom:12px;">Arqueo del efectivo</h2>

            <div style="margin-bottom:12px;">
                <label style="{{ $label }}" for="base">Base con la que abrió la caja</label>
                <input id="base" name="base_inicial" type="number" inputmode="numeric" min="0" step="100"
                       value="{{ old('base_inicial', $existente->base_inicial ?? 0) }}"
                       oninput="calcular()" {{ $bloqueado ? 'disabled' : '' }} style="{{ $input }}">
            </div>

            <div style="display:flex;justify-content:space-between;font-size:14px;padding:6px 0;">
                <span>+ Ventas en efectivo</span><strong>{{ $dinero($efectivoVen) }}</strong>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:16px;padding:8px 0;border-top:1px solid #eee;">
                <span>Debe haber en caja</span><strong id="esperado">$0</strong>
            </div>

            <div style="margin:12px 0;">
                <label style="{{ $label }}" for="contado">Efectivo contado en caja</label>
                <input id="contado" name="efectivo_contado" type="number" inputmode="numeric" min="0" step="50"
                       value="{{ old('efectivo_contado', $existente->efectivo_contado ?? '') }}"
                       oninput="calcular()" {{ $bloqueado ? 'disabled' : '' }} style="{{ $input }}" placeholder="0">
            </div>

            @unless($bloqueado)
                <details style="margin-bottom:12px;">
                    <summary style="cursor:pointer;font-size:13px;color:#555;">Contar por billetes y monedas</summary>
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:10px;">
                        @foreach([100000, 50000, 20000, 10000, 5000, 2000, 1000] as $den)
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                                <span style="min-width:70px;">{{ $dinero($den) }} ×</span>
                                <input type="number" min="0" class="den" data-valor="{{ $den }}" oninput="sumarBilletes()"
                                       style="width:100%;padding:8px;border:2px solid #ddd;border-radius:8px;">
                            </label>
                        @endforeach
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
                            <span style="min-width:70px;">Monedas $</span>
                            <input type="number" min="0" id="monedas" oninput="sumarBilletes()"
                                   style="width:100%;padding:8px;border:2px solid #ddd;border-radius:8px;">
                        </label>
                    </div>
                </details>
            @endunless

            <div id="resultado" style="padding:14px;border-radius:10px;font-size:18px;font-weight:bold;text-align:center;margin-bottom:12px;background:#f5f5f5;color:#555;">
                Escribe el efectivo contado
            </div>

            <div style="margin-bottom:14px;">
                <label style="{{ $label }}" for="notas">Notas (opcional)</label>
                <textarea id="notas" name="notas" rows="2" maxlength="500" {{ $bloqueado ? 'disabled' : '' }}
                          placeholder="Ej: se pagó un domicilio con dinero de la caja"
                          style="width:100%;padding:10px;font-size:14px;border:2px solid #ddd;border-radius:8px;">{{ old('notas', $existente->notas ?? '') }}</textarea>
            </div>

            @if($bloqueado)
                <div style="font-size:13px;color:#856404;background:#fff3cd;padding:10px;border-radius:8px;">
                    Esta caja ya fue cerrada. Solo un gerente o el propietario puede corregir el cierre.
                </div>
            @else
                <button type="submit"
                    style="width:100%;padding:16px;background:#000;color:#fff;border:none;border-radius:10px;font-size:18px;font-weight:bold;cursor:pointer;">
                    {{ $existente ? 'Actualizar cierre' : 'Cerrar caja' }}
                </button>
            @endif
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
@if($sede)
<script>
const EFECTIVO_VENTAS = {{ (int) $resumen['efectivo'] }};
const dinero = v => (v < 0 ? '-$' : '$') + Math.abs(Math.round(v || 0)).toLocaleString('es-CO');

function calcular() {
    const base     = parseInt(document.getElementById('base').value || 0, 10);
    const campo    = document.getElementById('contado');
    const esperado = base + EFECTIVO_VENTAS;
    document.getElementById('esperado').textContent = dinero(esperado);

    const caja = document.getElementById('resultado');
    if (campo.value === '') {
        caja.textContent = 'Escribe el efectivo contado';
        caja.style.background = '#f5f5f5';
        caja.style.color = '#555';
        return;
    }

    const diferencia = parseInt(campo.value, 10) - esperado;
    if (diferencia === 0) {
        caja.textContent = 'El efectivo cuadra';
        caja.style.background = '#d4edda';
        caja.style.color = '#155724';
    } else if (diferencia > 0) {
        caja.textContent = 'Sobran ' + dinero(diferencia);
        caja.style.background = '#e7f1ff';
        caja.style.color = '#0c447c';
    } else {
        caja.textContent = 'Faltan ' + dinero(-diferencia);
        caja.style.background = '#f8d7da';
        caja.style.color = '#721c24';
    }
}

function sumarBilletes() {
    let total = parseInt(document.getElementById('monedas').value || 0, 10);
    document.querySelectorAll('.den').forEach(i => {
        total += parseInt(i.value || 0, 10) * parseInt(i.dataset.valor, 10);
    });
    document.getElementById('contado').value = total;
    calcular();
}

function confirmarCierre() {
    if (document.getElementById('contado').value === '') {
        alert('Escribe el efectivo contado en caja.');
        return false;
    }
    return confirm('¿Confirmas el cierre de caja con estos valores?');
}

calcular();
</script>
@endif
@endsection
