<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Avanzas Digital</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; min-height: 100vh; }

        .navbar {
            background: #000;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand { color: #99CF8E; font-size: 18px; font-weight: bold; }
        .navbar-user  { color: #aaa; font-size: 13px; }

        .navbar-actions { display: flex; gap: 12px; align-items: center; }

        .navbar-actions a {
            color: #aaa;
            text-decoration: none;
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #333;
        }

        .navbar-actions a:hover { background: #222; color: #fff; }

        .container { max-width: 1400px; margin: 24px auto; padding: 0 20px; }

        .page-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .metricas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .metrica-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .metrica-card .valor {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .metrica-card .label { font-size: 12px; color: #888; }

        .alerta-vencimiento {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
        }

        .alerta-vencimiento h3 { font-size: 14px; color: #856404; margin-bottom: 10px; }

        .alerta-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #ffeaa7;
            font-size: 13px;
        }

        .alerta-item:last-child { border-bottom: none; }

        .seccion { background: #fff; border-radius: 12px; overflow: hidden; margin-bottom: 24px; }

        .seccion-header {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .seccion-header h2 { font-size: 16px; font-weight: bold; }

        .buscador {
            padding: 8px 14px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            width: 220px;
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            background: #000;
            color: #fff;
            padding: 10px 14px;
            font-size: 11px;
            text-align: left;
            white-space: nowrap;
        }

        td {
            padding: 10px 14px;
            font-size: 12px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        tr:hover td { background: #fafafa; }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-trial      { background: #fff3cd; color: #856404; }
        .badge-activa     { background: #d4edda; color: #155724; }
        .badge-vencida    { background: #f8d7da; color: #721c24; }
        .badge-suspendida { background: #f0f0f0; color: #555; }

        .btn-sm {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }

        .btn-ampliar  { background: #856404; color: #fff; }
        .btn-plan     { background: #4285F4; color: #fff; }
        .btn-eliminar { background: #c00; color: #fff; }
        .btn-mensaje  { background: #99CF8E; color: #000; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.activo { display: flex; }

        .modal {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            width: 400px;
            max-width: 90%;
        }

        .modal h3 { font-size: 18px; margin-bottom: 16px; }

        .modal-campo { margin-bottom: 14px; }
        .modal-campo label { display: block; font-size: 13px; color: #555; margin-bottom: 4px; }
        .modal-campo select,
        .modal-campo input,
        .modal-campo textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .modal-campo textarea { height: 100px; resize: none; }

        .modal-btns { display: flex; gap: 8px; margin-top: 16px; }
        .modal-btn-ok {
            flex: 1; padding: 10px; background: #000; color: #fff;
            border: none; border-radius: 8px; font-size: 14px; cursor: pointer;
        }
        .modal-btn-cancel {
            flex: 1; padding: 10px; background: #f0f0f0; color: #000;
            border: none; border-radius: 8px; font-size: 14px; cursor: pointer;
        }

        .mensaje-flash {
            display: none;
            position: fixed;
            top: 20px; right: 20px;
            background: #d4edda; color: #155724;
            padding: 12px 20px; border-radius: 8px;
            font-size: 13px; z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sku-alto  { color: #155724; font-weight: bold; }
        .sku-medio { color: #856404; font-weight: bold; }
        .sku-bajo  { color: #000; }

        .char-info { font-size: 11px; color: #aaa; margin-top: 3px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-brand">Avanzas Digital — Panel Admin</div>
    <div class="navbar-actions">
        <span class="navbar-user">{{ auth()->user()->name }}</span>
        <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            Salir
        </a>
        <form id="logout-form" method="POST" action="/logout" style="display:none;">
            @csrf
        </form>
    </div>
</nav>

<div class="container">

    <div class="page-title">
        Dashboard de Negocios
        <button onclick="document.getElementById('modal-masivo').classList.add('activo')"
            style="margin-left:auto;padding:8px 20px;background:#99CF8E;color:#000;
                   border:none;border-radius:8px;font-size:13px;font-weight:bold;cursor:pointer;">
            Mensaje masivo
        </button>
    </div>

    {{-- METRICAS --}}
    <div class="metricas">
        <div class="metrica-card">
            <div class="valor">{{ $totalFerreterias }}</div>
            <div class="label">Total negocios</div>
        </div>
        <div class="metrica-card">
            <div class="valor" style="color:#856404;">{{ $ferreteriasTrial }}</div>
            <div class="label">En trial</div>
        </div>
        <div class="metrica-card">
            <div class="valor" style="color:#155724;">{{ $ferreteriasActivas }}</div>
            <div class="label">Activas (pago)</div>
        </div>
        <div class="metrica-card">
            <div class="valor" style="color:#721c24;">{{ $ferreteriasVencidas }}</div>
            <div class="label">Vencidas</div>
        </div>
        <div class="metrica-card">
            <div class="valor">{{ $totalUsuarios }}</div>
            <div class="label">Usuarios totales</div>
        </div>
        <div class="metrica-card" style="border-top:3px solid #99CF8E;">
            <div class="valor" style="color:#155724;">
                $ {{ number_format($ingresosSuscripciones, 0, ',', '.') }}
            </div>
            <div class="label">Ingresos suscripciones este mes</div>
        </div>
    </div>

    {{-- ALERTAS VENCIMIENTO --}}
    @if($trialsProximosVencer->count() > 0)
    <div class="alerta-vencimiento">
        <h3>Trials proximos a vencer (menos de 7 dias)</h3>
        @foreach($trialsProximosVencer as $t)
        <div class="alerta-item">
            <span><strong>{{ $t->nombre }}</strong> — {{ $t->ciudad }}</span>
            <span style="color:#856404;font-weight:bold;">
                {{ now()->diffInDays($t->trial_ends_at) }} dias restantes
            </span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- TABLA DE FERRETERIAS --}}
    <div class="seccion">
        <div class="seccion-header">
            <h2>Todos los negocios ({{ $ferreterias->count() }})</h2>
            <input type="text" class="buscador" id="buscador"
                   placeholder="Buscar negocio..."
                   oninput="filtrar(this.value)">
        </div>
        <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Negocio</th>
                    <th>NIT</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                    <th>Tipo plan</th>
                    <th>Dias restantes</th>
                    <th>SKUs</th>
                    <th>Usuarios</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-ferreterias">
                @forelse($ferreterias as $i => $tenant)
                <tr class="fila-ferreteria" data-nombre="{{ strtolower($tenant->nombre) }}">
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div style="font-weight:bold;">{{ $tenant->nombre }}</div>
                        @if($tenant->telefono)
                        <div style="font-size:11px;color:#888;">{{ $tenant->telefono }}</div>
                        @endif
                    </td>
                    <td>{{ $tenant->nit ?? '—' }}</td>
                    <td>{{ $tenant->ciudad ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $tenant->subscription_status }}">
                            {{ strtoupper($tenant->subscription_status) }}
                        </span>
                        @php $sedesFuera = $tenant->sedes->where('activo', false); @endphp
                        @if($sedesFuera->isNotEmpty())
                            <div style="font-size:10px;color:#856404;margin-top:4px;">
                                Plan: {{ $tenant->sedes->where('activo', true)->pluck('nombre')->join(', ') }}
                                ({{ $tenant->sedes->where('activo', true)->count() }}/{{ $tenant->sedes->count() }})
                            </div>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($tenant->subscription_plan === 'anual')
                            <span style="background:#000;color:#fff;padding:3px 8px;border-radius:10px;font-size:10px;font-weight:bold;">
                                ANUAL
                            </span>
                        @elseif($tenant->subscription_plan === 'trimestral')
                            <span style="background:#4285F4;color:#fff;padding:3px 8px;border-radius:10px;font-size:10px;font-weight:bold;">
                                TRIMESTRAL
                            </span>
                        @else
                            <span style="color:#aaa;font-size:11px;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        @if($tenant->dias_restantes !== null)
                            <span style="color:{{ $tenant->dias_restantes <= 7 ? '#c00' : '#155724' }};font-weight:bold;">
                                {{ $tenant->dias_restantes }} dias
                            </span>
                        @else
                            <span style="color:#aaa;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center;">
                        <span class="{{ $tenant->total_skus > 200 ? 'sku-alto' : ($tenant->total_skus > 50 ? 'sku-medio' : 'sku-bajo') }}"
                              style="font-size:15px;">
                            {{ $tenant->total_skus }}
                        </span>
                        <div style="font-size:10px;color:#aaa;">
                            @if($tenant->subscription_status === 'trial')
                                de {{ \App\Models\Tenant::TRIAL_MAX_PRODUCTOS + (int) $tenant->productos_extra }}
                                @if($tenant->productos_extra)<span style="color:#856404;font-weight:bold;">(+{{ $tenant->productos_extra }})</span>@endif
                            @else
                                productos
                            @endif
                        </div>
                    </td>
                    <td style="text-align:center;">{{ $tenant->usuarios->count() }}</td>
                    <td>{{ $tenant->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            <button class="btn-sm btn-ampliar"
                                onclick="abrirModalAmpliar({{ $tenant->id }}, '{{ addslashes($tenant->nombre) }}')">
                                + Dias
                            </button>
                            <button class="btn-sm btn-plan"
                                onclick='abrirModalPlan({{ $tenant->id }}, @json($tenant->nombre), @json($tenant->sedes->map(fn ($s) => ["id" => $s->id, "nombre" => $s->nombre, "activo" => (bool) $s->activo])->values()))'>
                                Plan
                            </button>
                            <button class="btn-sm btn-plan"
                                onclick="abrirModalProductos({{ $tenant->id }}, '{{ addslashes($tenant->nombre) }}', {{ (int) $tenant->productos_extra }})">
                                Productos
                            </button>
                            @php $ultimoContrato = $tenant->contratos->first(); @endphp
                            <button class="btn-sm btn-plan" style="{{ $ultimoContrato ? 'background:#1e8e3e;' : '' }}"
                                title="{{ $ultimoContrato ? 'Firmado el ' . $ultimoContrato->fecha_firma->format('d/m/Y') : 'Sin contrato firmado' }}"
                                onclick='abrirModalContrato({{ $tenant->id }}, @json($tenant->nombre))'>
                                Contrato{{ $tenant->contratos->count() ? ' (' . $tenant->contratos->count() . ')' : '' }}
                            </button>
                            <button class="btn-sm btn-mensaje"
                                onclick="abrirModalMensaje({{ $tenant->id }}, '{{ addslashes($tenant->nombre) }}')">
                                Mensaje
                            </button>
                            <button class="btn-sm btn-eliminar"
                                onclick="eliminar({{ $tenant->id }}, '{{ addslashes($tenant->nombre) }}')">
                                Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" style="text-align:center;padding:24px;color:#999;">
                        No hay negocios registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

</div>

{{-- MODAL AMPLIAR TRIAL --}}
<div class="modal-overlay" id="modal-ampliar">
    <div class="modal">
        <h3>Ampliar trial</h3>
        <p id="modal-ampliar-nombre" style="font-size:13px;color:#888;margin-bottom:16px;"></p>
        <div class="modal-campo">
            <label>Dias a agregar</label>
            <input type="number" id="input-dias" value="15" min="1" max="365">
        </div>
        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cancelar</button>
            <button class="modal-btn-ok" onclick="confirmarAmpliar()">Ampliar</button>
        </div>
    </div>
</div>

{{-- MODAL CONTRATOS --}}
<div class="modal-overlay" id="modal-contrato">
    <div class="modal" style="max-width:560px;">
        <h3>Contratos firmados</h3>
        <p id="modal-contrato-nombre" style="font-size:13px;color:#888;margin-bottom:12px;"></p>

        <div id="lista-contratos" style="margin-bottom:16px;font-size:13px;"></div>

        <div style="border-top:1px solid #eee;padding-top:14px;">
            <div style="font-size:14px;font-weight:bold;margin-bottom:10px;">Cargar contrato firmado</div>
            <p style="font-size:12px;color:#888;margin-bottom:10px;">
                Descarga de <a href="https://sign.zoho.com/" target="_blank" rel="noopener">Zoho Sign</a> el PDF firmado con su certificado de finalización y súbelo aquí.
            </p>
            <div class="modal-campo">
                <label>PDF firmado *</label>
                <input type="file" id="contrato-archivo" accept="application/pdf,.pdf">
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;">
                <div class="modal-campo">
                    <label>Fecha de firma *</label>
                    <input type="date" id="contrato-firma" style="width:100%;min-width:0;box-sizing:border-box;" max="{{ now()->format('Y-m-d') }}" value="{{ now()->format('Y-m-d') }}">
                </div>
                <div class="modal-campo">
                    <label>Vigencia desde</label>
                    <input type="date" id="contrato-desde" style="width:100%;min-width:0;box-sizing:border-box;">
                </div>
                <div class="modal-campo">
                    <label>Vigencia hasta</label>
                    <input type="date" id="contrato-hasta" style="width:100%;min-width:0;box-sizing:border-box;">
                </div>
            </div>
            <div class="modal-campo">
                <label>Notas</label>
                <input type="text" id="contrato-notas" maxlength="255" placeholder="Ej: Plan anual sede Diagonal 48 Sur">
            </div>
            <p id="contrato-error" style="display:none;color:#c00;font-size:13px;margin-bottom:10px;"></p>
        </div>

        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cerrar</button>
            <button class="modal-btn-ok" id="btn-subir-contrato" onclick="subirContrato()">Cargar contrato</button>
        </div>
    </div>
</div>

{{-- MODAL PRODUCTOS EXTRA --}}
<div class="modal-overlay" id="modal-productos">
    <div class="modal">
        <h3>Productos adicionales en la demo</h3>
        <p id="modal-productos-nombre" style="font-size:13px;color:#888;margin-bottom:16px;"></p>
        <div class="modal-campo">
            <label>Productos extra (además de los {{ \App\Models\Tenant::TRIAL_MAX_PRODUCTOS }} de la demo)</label>
            <input type="number" id="input-productos-extra" value="0" min="0" max="500"
                   oninput="document.getElementById('productos-total').textContent = {{ \App\Models\Tenant::TRIAL_MAX_PRODUCTOS }} + (parseInt(this.value, 10) || 0)">
        </div>
        <p style="font-size:13px;margin-bottom:16px;">Límite total: <strong id="productos-total">{{ \App\Models\Tenant::TRIAL_MAX_PRODUCTOS }}</strong> productos. Pon 0 para quitar la excepción.</p>
        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cancelar</button>
            <button class="modal-btn-ok" onclick="confirmarProductos()">Guardar</button>
        </div>
    </div>
</div>

{{-- MODAL CAMBIAR PLAN --}}
<div class="modal-overlay" id="modal-plan">
    <div class="modal">
        <h3>Cambiar plan</h3>
        <p id="modal-plan-nombre" style="font-size:13px;color:#888;margin-bottom:16px;"></p>
        <div class="modal-campo">
            <label>Estado del plan</label>
            <select id="input-plan">
                <option value="trial">Trial</option>
                <option value="activa">Activa (pago)</option>
                <option value="vencida">Vencida</option>
                <option value="suspendida">Suspendida</option>
            </select>
        </div>
        <div class="modal-campo">
            <label>Meses de suscripcion (si es activa)</label>
            <select id="input-meses" onchange="valorPlan()">
                <option value="3">3 meses — Trimestral $45,000/mes por sede</option>
                <option value="12">12 meses — Anual $35,000/mes por sede</option>
            </select>
        </div>
        <div class="modal-campo" id="campo-sedes-plan">
            <label>Sedes incluidas en el plan</label>
            <div id="lista-sedes-plan" style="display:flex;flex-direction:column;gap:6px;"></div>
            <p id="valor-plan" style="font-size:13px;margin-top:8px;"></p>
            <p style="font-size:12px;color:#888;margin-top:4px;">Las sedes sin marcar quedan sin acceso: sus usuarios no pueden entrar y no se pueden vender ahí. Sus datos se conservan.</p>
        </div>
        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cancelar</button>
            <button class="modal-btn-ok" onclick="confirmarPlan()">Guardar</button>
        </div>
    </div>
</div>

{{-- MODAL ENVIAR MENSAJE INDIVIDUAL --}}
<div class="modal-overlay" id="modal-mensaje">
    <div class="modal" style="width:500px;">
        <h3>Enviar mensaje</h3>
        <p id="modal-mensaje-nombre" style="font-size:13px;color:#888;margin-bottom:16px;"></p>
        <div class="modal-campo">
            <label>Tipo de envio</label>
            <select id="input-tipo-mensaje">
                <option value="plataforma">Solo plataforma (campanita)</option>
                <option value="sms">Solo SMS</option>
                <option value="ambos">Plataforma + SMS</option>
            </select>
        </div>
        <div class="modal-campo">
            <label>Asunto *</label>
            <input type="text" id="input-asunto" placeholder="Ej: Actualizacion importante" maxlength="191">
        </div>
        <div class="modal-campo">
            <label>Mensaje * (max 500 caracteres)</label>
            <textarea id="input-contenido" maxlength="500"
                placeholder="Escribe el mensaje aqui..."></textarea>
            <div class="char-info"><span id="char-count">0</span>/500 caracteres</div>
        </div>
        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cancelar</button>
            <button class="modal-btn-ok" onclick="confirmarMensaje()">Enviar</button>
        </div>
    </div>
</div>

{{-- MODAL MENSAJE MASIVO --}}
<div class="modal-overlay" id="modal-masivo">
    <div class="modal" style="width:500px;">
        <h3>Mensaje masivo</h3>
        <p style="font-size:13px;color:#888;margin-bottom:16px;">
            Envia un mensaje a múltiples negocios a la vez.
        </p>
        <div class="modal-campo">
            <label>Enviar a</label>
            <select id="input-filtro-masivo">
                <option value="todas">Todos los negocios</option>
                <option value="trial">Solo en trial</option>
                <option value="activa">Solo activas (pago)</option>
                <option value="vencida">Solo vencidas</option>
            </select>
        </div>
        <div class="modal-campo">
            <label>Tipo de envio</label>
            <select id="input-tipo-masivo">
                <option value="plataforma">Solo plataforma (campanita)</option>
                <option value="sms">Solo SMS</option>
                <option value="ambos">Plataforma + SMS</option>
            </select>
        </div>
        <div class="modal-campo">
            <label>Asunto *</label>
            <input type="text" id="input-asunto-masivo"
                   placeholder="Ej: Mantenimiento programado" maxlength="191">
        </div>
        <div class="modal-campo">
            <label>Mensaje * (max 500 caracteres)</label>
            <textarea id="input-contenido-masivo" maxlength="500"
                placeholder="Escribe el mensaje aqui..."></textarea>
        </div>
        <div class="modal-btns">
            <button class="modal-btn-cancel" onclick="cerrarModales()">Cancelar</button>
            <button class="modal-btn-ok" onclick="confirmarMasivo()">Enviar a todas</button>
        </div>
    </div>
</div>

<div class="mensaje-flash" id="flash"></div>

<script>
let tenantActual  = null;
let tenantMensaje = null;

function filtrar(q) {
    document.querySelectorAll('.fila-ferreteria').forEach(fila => {
        fila.style.display = fila.dataset.nombre.includes(q.toLowerCase()) ? '' : 'none';
    });
}

function abrirModalAmpliar(id, nombre) {
    tenantActual = id;
    document.getElementById('modal-ampliar-nombre').textContent = nombre;
    document.getElementById('modal-ampliar').classList.add('activo');
}

const PLANES_PRECIO = { 3: 45000, 12: 35000 };

function abrirModalPlan(id, nombre, sedes) {
    tenantActual = id;
    document.getElementById('modal-plan-nombre').textContent = nombre;
    document.getElementById('lista-sedes-plan').innerHTML = (sedes || []).map(s => `
        <label style="display:flex;gap:8px;align-items:center;font-size:14px;cursor:pointer;">
            <input type="checkbox" class="sede-plan" value="${s.id}" ${s.activo ? 'checked' : ''} onchange="valorPlan()" style="width:18px;height:18px;">
            ${s.nombre.replace(/[<>&]/g, '')}
        </label>`).join('');
    valorPlan();
    document.getElementById('modal-plan').classList.add('activo');
}

function valorPlan() {
    const n     = document.querySelectorAll('.sede-plan:checked').length;
    const meses = parseInt(document.getElementById('input-meses').value, 10);
    const total = (PLANES_PRECIO[meses] || 0) * meses * n;
    document.getElementById('valor-plan').innerHTML = n
        ? `Valor del plan: <strong>$${total.toLocaleString('es-CO')}</strong> (${n} ${n === 1 ? 'sede' : 'sedes'} × ${meses} meses)`
        : '<span style="color:#c00;">Marca al menos una sede.</span>';
}

// ------------------------------------------------------------------
// Contratos firmados
// ------------------------------------------------------------------
const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

async function abrirModalContrato(id, nombre) {
    tenantActual = id;
    document.getElementById('modal-contrato-nombre').textContent = nombre;
    document.getElementById('contrato-archivo').value = '';
    document.getElementById('contrato-notas').value = '';
    document.getElementById('contrato-error').style.display = 'none';
    document.getElementById('modal-contrato').classList.add('activo');
    await cargarContratos();
}

async function cargarContratos() {
    const caja = document.getElementById('lista-contratos');
    caja.textContent = 'Cargando…';
    const res  = await fetch(`/admin/ferreterias/${tenantActual}/contratos`, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (!data.contratos.length) {
        caja.innerHTML = '<div style="color:#999;">Aún no hay contratos cargados.</div>';
        return;
    }
    caja.innerHTML = data.contratos.map(c => `
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:10px 0;border-bottom:1px solid #f0f0f0;">
            <div style="min-width:0;">
                <div style="font-weight:bold;word-break:break-word;">${escHtml(c.nombre)}</div>
                <div style="color:#666;">Firmado el ${escHtml(c.firma)}${c.vigencia ? ' · Vigencia ' + escHtml(c.vigencia) : ''}</div>
                ${c.notas ? `<div style="color:#666;">${escHtml(c.notas)}</div>` : ''}
                <div style="color:#aaa;font-size:11px;">Cargado ${escHtml(c.subido)} · ${(c.tamano / 1024).toFixed(0)} KB</div>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0;">
                <a class="btn-sm btn-plan" href="${c.descargar}" style="text-decoration:none;">Descargar</a>
                <button class="btn-sm btn-eliminar" onclick="eliminarContrato(${c.id})">Eliminar</button>
            </div>
        </div>`).join('');
}

async function subirContrato() {
    const err     = document.getElementById('contrato-error');
    const archivo = document.getElementById('contrato-archivo').files[0];
    err.style.display = 'none';
    if (!archivo) { err.textContent = 'Elige el PDF del contrato firmado.'; err.style.display = 'block'; return; }

    const form = new FormData();
    form.append('archivo', archivo);
    form.append('fecha_firma', document.getElementById('contrato-firma').value);
    form.append('vigencia_desde', document.getElementById('contrato-desde').value);
    form.append('vigencia_hasta', document.getElementById('contrato-hasta').value);
    form.append('notas', document.getElementById('contrato-notas').value);

    const btn = document.getElementById('btn-subir-contrato');
    btn.disabled = true;
    btn.textContent = 'Cargando…';
    try {
        const res  = await fetch(`/admin/ferreterias/${tenantActual}/contratos`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: form,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            err.textContent = data.mensaje ?? data.message ?? (res.status === 413 ? 'El archivo es demasiado grande para el servidor.' : 'No se pudo cargar el contrato.');
            err.style.display = 'block';
            return;
        }
        mostrarFlash(data.mensaje);
        setTimeout(() => location.reload(), 1200);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Cargar contrato';
    }
}

async function eliminarContrato(id) {
    if (!confirm('¿Eliminar este contrato? Se borra también el PDF del servidor.')) return;
    const res  = await fetch(`/admin/contratos/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    const data = await res.json();
    mostrarFlash(data.mensaje);
    cargarContratos();
}

function abrirModalProductos(id, nombre, extra) {
    tenantActual = id;
    document.getElementById('modal-productos-nombre').textContent = nombre;
    document.getElementById('input-productos-extra').value = extra;
    document.getElementById('productos-total').textContent = {{ \App\Models\Tenant::TRIAL_MAX_PRODUCTOS }} + extra;
    document.getElementById('modal-productos').classList.add('activo');
}

async function confirmarProductos() {
    const extra = parseInt(document.getElementById('input-productos-extra').value || '0', 10);
    const res   = await fetch(`/admin/ferreterias/${tenantActual}/productos-extra`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ productos_extra: extra }),
    });
    const data = await res.json();
    cerrarModales();
    mostrarFlash(data.mensaje ?? data.message);
    setTimeout(() => location.reload(), 1500);
}

function abrirModalMensaje(id, nombre) {
    tenantMensaje = id;
    document.getElementById('modal-mensaje-nombre').textContent = nombre;
    document.getElementById('input-asunto').value    = '';
    document.getElementById('input-contenido').value = '';
    document.getElementById('char-count').textContent = '0';
    document.getElementById('modal-mensaje').classList.add('activo');
}

function cerrarModales() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('activo'));
    tenantActual  = null;
    tenantMensaje = null;
}

// Contador de caracteres
document.addEventListener('DOMContentLoaded', () => {
    const contenido = document.getElementById('input-contenido');
    if (contenido) {
        contenido.addEventListener('input', () => {
            document.getElementById('char-count').textContent = contenido.value.length;
        });
    }
});

async function confirmarAmpliar() {
    const dias = document.getElementById('input-dias').value;
    const res  = await fetch(`/admin/ferreterias/${tenantActual}/ampliar-trial`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ dias }),
    });
    const data = await res.json();
    cerrarModales();
    mostrarFlash(data.mensaje);
    setTimeout(() => location.reload(), 1500);
}

async function confirmarPlan() {
    const plan  = document.getElementById('input-plan').value;
    const meses = document.getElementById('input-meses').value;
    const sedes = [...document.querySelectorAll('.sede-plan:checked')].map(c => parseInt(c.value, 10));
    if (!sedes.length) { alert('Marca al menos una sede.'); return; }
    const res   = await fetch(`/admin/ferreterias/${tenantActual}/cambiar-plan`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ plan, meses, sedes }),
    });
    const data = await res.json();
    cerrarModales();
    mostrarFlash(data.mensaje);
    setTimeout(() => location.reload(), 1500);
}

async function confirmarMensaje() {
    const asunto   = document.getElementById('input-asunto').value;
    const contenido= document.getElementById('input-contenido').value;
    const tipo     = document.getElementById('input-tipo-mensaje').value;

    if (!asunto || !contenido) { alert('Completa todos los campos'); return; }

    const res = await fetch('/admin/mensajes/enviar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ tenant_id: tenantMensaje, asunto, contenido, tipo }),
    });

    const data = await res.json();
    cerrarModales();
    mostrarFlash(data.success ? data.mensaje : 'Error: ' + data.mensaje);
}

async function confirmarMasivo() {
    const asunto   = document.getElementById('input-asunto-masivo').value;
    const contenido= document.getElementById('input-contenido-masivo').value;
    const tipo     = document.getElementById('input-tipo-masivo').value;
    const filtro   = document.getElementById('input-filtro-masivo').value;

    if (!asunto || !contenido) { alert('Completa todos los campos'); return; }

    const res = await fetch('/admin/mensajes/masivo', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ asunto, contenido, tipo, filtro }),
    });

    const data = await res.json();
    cerrarModales();
    mostrarFlash(data.mensaje);
}

async function eliminar(id, nombre) {
    if (!confirm(`Eliminar "${nombre}" y todos sus datos?\n\nEsta accion no se puede deshacer.`)) return;
    const res = await fetch(`/admin/ferreterias/${id}/eliminar`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });
    const data = await res.json();
    mostrarFlash(data.mensaje);
    setTimeout(() => location.reload(), 1500);
}

function mostrarFlash(msg) {
    const el = document.getElementById('flash');
    el.textContent   = msg;
    el.style.display = 'block';
    setTimeout(() => el.style.display = 'none', 3000);
}
</script>
</body>
</html>