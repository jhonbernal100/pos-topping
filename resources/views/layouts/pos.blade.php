<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'POS Topping')</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="POS Topping">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f0f0; display: flex; flex-direction: column; min-height: 100vh; }

        .navbar {
            background: #000;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .navbar-brand { font-size: 18px; font-weight: bold; color: #fff; text-decoration: none; }
        .navbar-right  { display: flex; align-items: center; gap: 16px; }
        .navbar-user { font-size: 12px; color: #aaa; text-align: right; display: flex; flex-direction: column; align-items: flex-end; }
        .navbar-user span { display: block; color: #fff; font-size: 14px; }
        .navbar-rol { color: #888; font-size: 11px; }

        .toolbar {
            background: #111;
            padding: 8px 16px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .toolbar::-webkit-scrollbar { display: none; }

        .toolbar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 14px;
            white-space: nowrap;
            background: #222;
            border: 1px solid #333;
            transition: background .2s;
            flex-shrink: 0;
        }

        .toolbar a:hover { background: #333; }
        .toolbar a.activo { background: #fff; color: #000; font-weight: bold; }

        .contenido-principal { flex: 1; }

        .footer-avanzas {
            background: #000;
            color: #888;
            text-align: center;
            padding: 10px 16px;
            font-size: 11px;
        }

        .footer-avanzas a { color: #fff; text-decoration: none; font-weight: bold; }

        .alerta-trial {
            background: #fff3cd;
            color: #856404;
            text-align: center;
            padding: 8px 16px;
            font-size: 13px;
        }

        .alerta-trial a { color: #856404; font-weight: bold; }

        /* Campanita */
        .campana-wrapper {
            position: relative;
            cursor: pointer;
            padding: 4px;
        }

        .campana-icono { font-size: 20px; }

        .campana-badge {
            display: none;
            position: absolute;
            top: 0px;
            right: 0px;
            background: #c00;
            color: #fff;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            font-size: 9px;
            font-weight: bold;
            align-items: center;
            justify-content: center;
        }

        .panel-mensajes {
            display: none;
            position: absolute;
            top: 56px;
            right: 20px;
            width: 320px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            z-index: 9999;
            border: 1px solid #eee;
            overflow: hidden;
        }

        .panel-mensajes-header {
            background: #000;
            color: #fff;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: bold;
        }

        .panel-mensajes-header button {
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
        }

        .panel-mensajes-lista {
            max-height: 300px;
            overflow-y: auto;
        }

        .mensaje-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
        }

        .mensaje-item-asunto {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 4px;
        }

        .mensaje-item-contenido {
            font-size: 12px;
            color: #555;
            line-height: 1.4;
        }

        .mensaje-item-fecha {
            font-size: 10px;
            color: #aaa;
            margin-top: 4px;
        }

        .panel-mensajes-footer {
            padding: 10px 16px;
            border-top: 1px solid #eee;
        }

        .panel-mensajes-footer button {
            width: 100%;
            padding: 8px;
            background: #f0f0f0;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .panel-mensajes-footer button:hover { background: #e0e0e0; }
    </style>
    @yield('estilos')
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
</head>
<body>

    @php
        $usuarioActual = auth()->user();
        $rolVisible = match (true) {
            $usuarioActual->esSuperAdmin()  => 'Superadmin',
            $usuarioActual->esPropietario() => 'Propietario',
            $usuarioActual->esGerenteSede() => 'Gerente',
            default                         => 'Auxiliar',
        };
    @endphp

    <nav class="navbar">
        <a href="{{ $usuarioActual->rol === 'superadmin' ? '/admin/dashboard' : '/ventas/crear' }}"
           class="navbar-brand">
            POS Topping
        </a>

        <div class="navbar-right">

            {{-- Campanita solo para usuarios de negocio --}}
            @if($usuarioActual->rol !== 'superadmin')
            <div class="campana-wrapper" onclick="toggleMensajes()">
                <div class="campana-icono">🔔</div>
                <span class="campana-badge" id="campana-badge">0</span>
            </div>

            {{-- Panel de mensajes --}}
            <div class="panel-mensajes" id="panel-mensajes">
                <div class="panel-mensajes-header">
                    <span>Mensajes de Avanzas Digital</span>
                    <button onclick="cerrarMensajes()">&#x2715;</button>
                </div>
                <div class="panel-mensajes-lista" id="lista-mensajes">
                    <div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">
                        Cargando...
                    </div>
                </div>
                <div class="panel-mensajes-footer">
                    <button onclick="marcarTodosLeidos()">
                        Marcar todos como leidos
                    </button>
                </div>
            </div>
            @endif

            <div class="navbar-user">
                <div>
                    {{ $usuarioActual->name ?? 'Usuario' }}
                    <span class="navbar-rol" style="display:inline;">· {{ $rolVisible }}</span>
                </div>
                <span>{{ $usuarioActual->tenant->nombre ?? 'Avanzas Digital' }}</span>
                @include('partials.sede-activa')
            </div>

        </div>
    </nav>

    <div class="toolbar">

        {{-- SUPERADMIN: solo ve Panel Admin --}}
        @if($usuarioActual->rol === 'superadmin')
            <a href="/admin/dashboard"
               class="{{ request()->is('admin*') ? 'activo' : '' }}"
               style="background:#99CF8E;color:#000;font-weight:bold;border-color:#99CF8E;">
                Panel Admin
            </a>

        {{-- USUARIOS DEL NEGOCIO --}}
        @else
            <a href="/ventas/crear"
               class="{{ request()->is('ventas/crear') ? 'activo' : '' }}">
                Nueva venta
            </a>

            <a href="/ventas"
               class="{{ request()->is('ventas') && !request()->is('ventas/crear') ? 'activo' : '' }}">
                Ventas
            </a>

            <a href="/inventario/capturar"
               class="{{ request()->is('inventario/capturar') ? 'activo' : '' }}">
                Agregar inventario
            </a>

            <a href="/inventario/crear-manual"
               class="{{ request()->is('inventario/crear-manual') ? 'activo' : '' }}">
                Crear manual
            </a>

            <a href="/clientes"
               class="{{ request()->is('clientes*') ? 'activo' : '' }}">
                Clientes
            </a>

            {{-- Propietario y gerentes de sede --}}
            @if($usuarioActual->rol === 'dueno')
                <a href="/inventario"
                   class="{{ request()->is('inventario') && !request()->is('inventario/capturar') && !request()->is('inventario/crear-manual') ? 'activo' : '' }}">
                    Inventario
                </a>
                <a href="/proveedores"
                   class="{{ request()->is('proveedores*') ? 'activo' : '' }}">
                    Proveedores
                </a>
                <a href="/gastos"
                   class="{{ request()->is('gastos*') ? 'activo' : '' }}">
                    Gastos
                </a>
                <a href="/usuarios"
                   class="{{ request()->is('usuarios*') ? 'activo' : '' }}">
                    Usuarios
                </a>
                <a href="/reportes"
                   class="{{ request()->is('reportes*') ? 'activo' : '' }}">
                    Reportes
                </a>
            @endif

            {{-- Solo propietario --}}
            @if($usuarioActual->esPropietario())
                <a href="/menu"
                   class="{{ request()->is('menu*') ? 'activo' : '' }}">
                    Menú
                </a>
                <a href="/ferreteria/perfil"
                   class="{{ request()->is('ferreteria/perfil') ? 'activo' : '' }}">
                    Mi negocio
                </a>
                <a href="/ferreteria/suscripcion"
                   class="{{ request()->is('ferreteria/suscripcion') ? 'activo' : '' }}">
                    Suscripcion
                </a>
            @endif

        @endif

        <form method="POST" action="/logout" style="margin-left:auto;flex-shrink:0;">
            @csrf
            <button type="submit"
                style="background:#c00;color:#fff;border:none;padding:10px 16px;border-radius:6px;font-size:14px;cursor:pointer;white-space:nowrap;">
                Salir
            </button>
        </form>
    </div>

    @auth
    @if($usuarioActual->tenant && $usuarioActual->tenant->subscription_status === 'trial')
        @php $dias = $usuarioActual->tenant->diasRestantes(); @endphp
        @if($dias <= 7)
        <div class="alerta-trial">
            Tu periodo de prueba vence en <strong>{{ $dias }} dias</strong>.
            @if($usuarioActual->esPropietario())
                <a href="/ferreteria/suscripcion">Contacta a Avanzas Digital para continuar</a>
            @else
                Informa al propietario del negocio.
            @endif
        </div>
        @endif
    @endif
    @endauth

    <div class="contenido-principal">
        @yield('contenido')
    </div>

    <footer class="footer-avanzas">
        Sistema POS desarrollado por
        <a href="https://www.avanzas.digital/index.html" target="_blank" rel="noopener">Avanzas Digital</a>
        &nbsp;·&nbsp;
        <a href="https://www.avanzas.digital/index.html" target="_blank" rel="noopener">Quieres este sistema? Contactanos</a>
    </footer>

    @yield('scripts')

    {{-- Sistema de mensajes campanita --}}
    @if($usuarioActual->rol !== 'superadmin')
    <script>
    let mensajesAbierto = false;

    async function cargarMensajes() {
        try {
            const res  = await fetch('/mensajes/no-leidos');
            const data = await res.json();

            const badge = document.getElementById('campana-badge');
            if (!badge) return;

            if (data.count > 0) {
                badge.style.display = 'flex';
                badge.textContent   = data.count > 9 ? '9+' : data.count;
            } else {
                badge.style.display = 'none';
            }

            const lista = document.getElementById('lista-mensajes');
            if (!lista) return;

            if (data.mensajes.length === 0) {
                lista.innerHTML = '<div style="padding:20px;text-align:center;color:#aaa;font-size:13px;">Sin mensajes nuevos</div>';
            } else {
                lista.innerHTML = data.mensajes.map(m => `
                    <div class="mensaje-item">
                        <div class="mensaje-item-asunto">${m.asunto}</div>
                        <div class="mensaje-item-contenido">${m.contenido}</div>
                        <div class="mensaje-item-fecha">${m.created_at}</div>
                    </div>
                `).join('');
            }
        } catch (e) {}
    }

    function toggleMensajes() {
        mensajesAbierto = !mensajesAbierto;
        const panel = document.getElementById('panel-mensajes');
        panel.style.display = mensajesAbierto ? 'block' : 'none';
        if (mensajesAbierto) cargarMensajes();
    }

    function cerrarMensajes() {
        mensajesAbierto = false;
        document.getElementById('panel-mensajes').style.display = 'none';
    }

    async function marcarTodosLeidos() {
        await fetch('/mensajes/leer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        await cargarMensajes();
        cerrarMensajes();
    }

    // Cerrar al hacer click fuera
    document.addEventListener('click', function(e) {
        const wrapper = document.querySelector('.campana-wrapper');
        const panel   = document.getElementById('panel-mensajes');
        if (wrapper && panel && !wrapper.contains(e.target) && !panel.contains(e.target)) {
            cerrarMensajes();
        }
    });

    // Polling cada 30 segundos
    cargarMensajes();
    setInterval(cargarMensajes, 30000);
    </script>
    @endif

</body>
</html>