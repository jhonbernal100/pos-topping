<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar datos — POS Topping</title>
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
            max-width: 520px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        .logo { text-align: center; margin-bottom: 20px; }
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
        .paso.activo .num  { background: #99CF8E; color: #fff; }
        .paso.activo       { color: #000; font-weight: bold; }
        .paso.completado .num { background: #000; color: #fff; }
        .separador { width: 24px; height: 2px; background: #ddd; }

        h2 { font-size: 20px; margin-bottom: 6px; text-align: center; }
        p.subtitulo { font-size: 13px; color: #888; text-align: center; margin-bottom: 24px; }

        .campo { margin-bottom: 14px; }
        .campo label { display: block; font-size: 13px; color: #555; margin-bottom: 4px; font-weight: bold; }
        .campo input {
            width: 100%; padding: 12px; font-size: 15px;
            border: 2px solid #e0e0e0; border-radius: 8px; outline: none;
        }
        .campo input:focus { border-color: #99CF8E; }
        .campo .hint { font-size: 11px; color: #aaa; margin-top: 3px; }

        .ia-badge {
            background: #f0f7ff;
            border: 1px solid #4285F4;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #4285F4;
            margin-bottom: 16px;
            text-align: center;
        }

        .mensaje {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .mensaje.error   { background: #f8d7da; color: #721c24; }
        .mensaje.exito   { background: #d4edda; color: #155724; }

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
        }

        .btn:disabled { background: #aaa; cursor: not-allowed; }
        .btn-volver {
            width: 100%;
            padding: 10px;
            background: none;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 8px;
            color: #555;
        }

        .footer { text-align: center; margin-top: 20px; font-size: 11px; color: #bbb; }
        .footer a { color: #99CF8E; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="logo">
        <img src="/images/logo-pos-topping.png" alt="POS Topping">
    </div>

    <div class="pasos">
        <div class="paso completado">
            <div class="num">✓</div>
            <span>RUT</span>
        </div>
        <div class="separador"></div>
        <div class="paso activo">
            <div class="num">2</div>
            <span>Confirmar datos</span>
        </div>
        <div class="separador"></div>
        <div class="paso">
            <div class="num">3</div>
            <span>Activar</span>
        </div>
    </div>

    <h2>Confirma los datos</h2>
    <p class="subtitulo">La IA extrajo estos datos de tu RUT. Verifica y completa la informacion.</p>

    <div class="ia-badge">
        Datos extraidos automaticamente con Google Gemini AI
    </div>

    <div class="mensaje" id="mensaje"></div>

    <div class="campo">
        <label>Nombre del negocio *</label>
        <input type="text" id="f-nombre-negocio" placeholder="Ej: Ferrelectricos Heidy">
    </div>

    <div class="campo">
        <label>NIT</label>
        <input type="text" id="f-nit" placeholder="Ej: 1002549971-2">
    </div>

    <div class="campo">
        <label>Nombre del representante legal *</label>
        <input type="text" id="f-representante" placeholder="Nombre completo">
    </div>

    <div class="campo">
        <label>Correo electronico *</label>
        <input type="email" id="f-email" placeholder="tu@correo.com">
        <div class="hint">Recibirás el código de verificación en este correo</div>
    </div>

    <div class="campo">
        <label>Telefono de contacto</label>
        <input type="text" id="f-telefono" placeholder="+57 300 000 0000">
    </div>

    <div class="campo">
        <label>Ciudad</label>
        <input type="text" id="f-ciudad" placeholder="Bogota">
    </div>

    <button class="btn" id="btn-enviar" onclick="enviarDatos()">
        Enviar codigo de verificacion
    </button>

    <button class="btn-volver" onclick="window.location.href='/trial'">
        Volver al paso anterior
    </button>

    <div class="footer">
        Sistema desarrollado por
        <a href="https://www.avanzas.digital/index.html" target="_blank">Avanzas Digital</a>
    </div>

</div>

<script>
// Cargar datos del paso 1
window.onload = function() {
    const datos = JSON.parse(sessionStorage.getItem('trial_datos') || '{}');
    if (datos.nombre_negocio)    document.getElementById('f-nombre-negocio').value = datos.nombre_negocio;
    if (datos.nit)               document.getElementById('f-nit').value = datos.nit;
    if (datos.nombre_representante) document.getElementById('f-representante').value = datos.nombre_representante;
    if (datos.ciudad)            document.getElementById('f-ciudad').value = datos.ciudad;
};

async function enviarDatos() {
    const nombre   = document.getElementById('f-nombre-negocio').value;
    const email    = document.getElementById('f-email').value;
    const repre    = document.getElementById('f-representante').value;

    if (!nombre || !email || !repre) {
        mostrarMensaje('error', 'Por favor completa los campos obligatorios');
        return;
    }

    const btn = document.getElementById('btn-enviar');
    btn.disabled    = true;
    btn.textContent = 'Enviando...';

    const payload = {
        nombre_negocio:       nombre,
        nit:                  document.getElementById('f-nit').value,
        nombre_representante: repre,
        email:                email,
        telefono:             document.getElementById('f-telefono').value,
        ciudad:               document.getElementById('f-ciudad').value,
        rut_foto:             sessionStorage.getItem('trial_rut_foto'),
    };

    try {
        const res = await fetch('/trial/confirmar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (data.success) {
            sessionStorage.setItem('trial_id', data.trial_id);
            sessionStorage.setItem('trial_email', email);
            window.location.href = '/trial/paso3';
        } else {
            mostrarMensaje('error', data.mensaje || 'Error al enviar datos');
            btn.disabled    = false;
            btn.textContent = 'Enviar codigo de verificacion';
        }
    } catch (e) {
        mostrarMensaje('error', 'Error de conexion: ' + e.message);
        btn.disabled    = false;
        btn.textContent = 'Enviar codigo de verificacion';
    }
}

function mostrarMensaje(tipo, texto) {
    const el = document.getElementById('mensaje');
    el.className  = 'mensaje ' + tipo;
    el.textContent = texto;
    el.style.display = 'block';
}
</script>
</body>
</html>