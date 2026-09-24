<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar cuenta — POS Topping</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: #fff;
            border-radius: 16px;
            padding: 36px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            text-align: center;
        }

        .logo { margin-bottom: 20px; }
        .logo img { width: 200px; }

        .pasos {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .paso { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #aaa; }
        .paso .num {
            width: 24px; height: 24px; border-radius: 50%;
            background: #ddd; color: #888;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 12px;
        }
        .paso.activo .num    { background: #99CF8E; color: #fff; }
        .paso.activo         { color: #000; font-weight: bold; }
        .paso.completado .num{ background: #000; color: #fff; }
        .separador { width: 24px; height: 2px; background: #ddd; }

        .icono-correo { font-size: 64px; margin: 16px 0; }

        h2 { font-size: 20px; margin-bottom: 8px; }
        p.subtitulo { font-size: 13px; color: #888; margin-bottom: 24px; line-height: 1.6; }

        .codigo-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .codigo-inputs input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
        }

        .codigo-inputs input:focus { border-color: #99CF8E; }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .mensaje.error  { background: #f8d7da; color: #721c24; }
        .mensaje.exito  { background: #d4edda; color: #155724; }

        .btn {
            width: 100%;
            padding: 14px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .btn:disabled { background: #aaa; cursor: not-allowed; }

        .reenviar {
            font-size: 13px;
            color: #888;
            margin-top: 8px;
        }

        .reenviar a {
            color: #000;
            font-weight: bold;
            cursor: pointer;
            text-decoration: underline;
        }

        .exito-box {
            display: none;
            background: #d4edda;
            border-radius: 12px;
            padding: 24px;
            margin-top: 16px;
        }

        .exito-box h3 { font-size: 18px; color: #155724; margin-bottom: 8px; }
        .exito-box p  { font-size: 13px; color: #555; line-height: 1.6; }

        .footer { text-align: center; margin-top: 20px; font-size: 11px; color: #bbb; }
        .footer a { color: #99CF8E; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="logo">
        <img src="/images/logo-pos-ferretero.png" alt="POS Topping">
    </div>

    <div class="pasos">
        <div class="paso completado">
            <div class="num">✓</div>
            <span>RUT</span>
        </div>
        <div class="separador"></div>
        <div class="paso completado">
            <div class="num">✓</div>
            <span>Datos</span>
        </div>
        <div class="separador"></div>
        <div class="paso activo">
            <div class="num">3</div>
            <span>Activar</span>
        </div>
    </div>

    <div id="panel-verificacion">
        <div class="icono-correo">📧</div>
        <h2>Verifica tu correo</h2>
        <p class="subtitulo">
            Enviamos un codigo de 6 digitos a<br>
            <strong id="email-display"></strong><br>
            Ingresalo a continuacion para activar tu demo de 30 dias.
        </p>

        <div class="mensaje" id="mensaje"></div>

        <div class="codigo-inputs">
            <input type="text" maxlength="1" class="digit" id="d1" oninput="moverFoco(this, 'd2')">
            <input type="text" maxlength="1" class="digit" id="d2" oninput="moverFoco(this, 'd3')">
            <input type="text" maxlength="1" class="digit" id="d3" oninput="moverFoco(this, 'd4')">
            <input type="text" maxlength="1" class="digit" id="d4" oninput="moverFoco(this, 'd5')">
            <input type="text" maxlength="1" class="digit" id="d5" oninput="moverFoco(this, 'd6')">
            <input type="text" maxlength="1" class="digit" id="d6" oninput="verificarAuto()">
        </div>

        <button class="btn" id="btn-verificar" onclick="verificarCodigo()">
            Activar mi demo gratis
        </button>

        <div class="reenviar">
            No recibiste el codigo?
            <a onclick="reenviarCodigo()">Reenviar codigo</a>
        </div>
    </div>

    <div class="exito-box" id="panel-exito">
        <h3>Cuenta activada</h3>
        <p>
            Tu negocio tiene 30 días de demo gratuito.<br><br>
            Revisa tu correo — te enviamos las credenciales de acceso para ingresar al sistema.
        </p>
        <br>
        <a href="/login" style="display:block;padding:12px;background:#000;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;">
            Ir al login
        </a>
    </div>

    <div class="footer">
        Sistema desarrollado por
        <a href="https://www.avanzas.digital/index.html" target="_blank">Avanzas Digital</a>
    </div>

</div>

<script>
window.onload = function() {
    const email = sessionStorage.getItem('trial_email') || '';
    document.getElementById('email-display').textContent = email;
    document.getElementById('d1').focus();
};

function moverFoco(input, nextId) {
    if (input.value.length === 1) {
        document.getElementById(nextId).focus();
    }
}

function verificarAuto() {
    const codigo = getCodigo();
    if (codigo.length === 6) verificarCodigo();
}

function getCodigo() {
    return ['d1','d2','d3','d4','d5','d6']
        .map(id => document.getElementById(id).value)
        .join('');
}

async function verificarCodigo() {
    const codigo   = getCodigo();
    const trialId  = sessionStorage.getItem('trial_id');

    if (codigo.length !== 6) {
        mostrarMensaje('error', 'Ingresa el codigo completo de 6 digitos');
        return;
    }

    const btn = document.getElementById('btn-verificar');
    btn.disabled    = true;
    btn.textContent = 'Verificando...';

    try {
        const res = await fetch('/trial/verificar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ trial_id: trialId, codigo: codigo }),
        });

        const data = await res.json();

        if (data.success) {
            document.getElementById('panel-verificacion').style.display = 'none';
            document.getElementById('panel-exito').style.display = 'block';
            sessionStorage.clear();
        } else {
            mostrarMensaje('error', data.mensaje);
            btn.disabled    = false;
            btn.textContent = 'Activar mi demo gratis';
        }
    } catch (e) {
        mostrarMensaje('error', 'Error de conexion: ' + e.message);
        btn.disabled    = false;
        btn.textContent = 'Activar mi demo gratis';
    }
}

async function reenviarCodigo() {
    const trialId = sessionStorage.getItem('trial_id');
    if (!trialId) return;

    const res = await fetch('/trial/reenviar-codigo', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ trial_id: trialId }),
    });

    const data = await res.json();
    mostrarMensaje(data.success ? 'exito' : 'error', data.mensaje);
}

function mostrarMensaje(tipo, texto) {
    const el = document.getElementById('mensaje');
    el.className     = 'mensaje ' + tipo;
    el.textContent   = texto;
    el.style.display = 'block';
}
</script>
</body>
</html>