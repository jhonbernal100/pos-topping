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
                        <div style="font-size:10px;color:#aaa;">productos</div>
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
                                onclick="abrirModalPlan({{ $tenant->id }}, '{{ addslashes($tenant->nombre) }}')">
                                Plan
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
            <select id="input-meses">
                <option value="3">3 meses — Trimestral $45,000/mes</option>
                <option value="12">12 meses — Anual $35,000/mes</option>
            </select>
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

function abrirModalPlan(id, nombre) {
    tenantActual = id;
    document.getElementById('modal-plan-nombre').textContent = nombre;
    document.getElementById('modal-plan').classList.add('activo');
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
    const res   = await fetch(`/admin/ferreterias/${tenantActual}/cambiar-plan`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ plan, meses }),
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