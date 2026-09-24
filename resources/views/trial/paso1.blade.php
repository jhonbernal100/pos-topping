<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Demo — POS Topping</title>
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

        .paso {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #aaa;
        }

        .paso .num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ddd;
            color: #888;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }

        .paso.activo .num { background: #99CF8E; color: #fff; }
        .paso.activo { color: #000; font-weight: bold; }

        .separador { width: 24px; height: 2px; background: #ddd; }

        h2 { font-size: 20px; margin-bottom: 6px; text-align: center; }
        p.subtitulo { font-size: 13px; color: #888; text-align: center; margin-bottom: 24px; }

        .upload-box {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 16px;
            transition: border-color .2s;
        }

        .upload-box:hover { border-color: #99CF8E; }
        .upload-box .icono { font-size: 48px; margin-bottom: 8px; }
        .upload-box p { font-size: 13px; color: #888; }

        #preview-rut {
            width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            display: none;
            margin-bottom: 12px;
            border: 1px solid #eee;
        }

        .estado {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }

        .estado.procesando { background: #fff3cd; color: #856404; }
        .estado.error      { background: #f8d7da; color: #721c24; }

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

        .link-login {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: #888;
        }

        .link-login a { color: #000; font-weight: bold; }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #bbb;
        }

        .footer a { color: #99CF8E; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    <div class="logo">
        <img src="/images/logo-pos-ferretero.png" alt="POS Topping">
    </div>

    <div class="pasos">
        <div class="paso activo">
            <div class="num">1</div>
            <span>Cargar RUT</span>
        </div>
        <div class="separador"></div>
        <div class="paso">
            <div class="num">2</div>
            <span>Verificar</span>
        </div>
        <div class="separador"></div>
        <div class="paso">
            <div class="num">3</div>
            <span>Activar</span>
        </div>
    </div>

    <h2>Solicita tu demo gratis</h2>
    <p class="subtitulo">Toma una foto de tu RUT y la IA extraera los datos de tu negocio automáticamente</p>

    <img id="preview-rut" src="" alt="Vista previa RUT">

    <div class="estado procesando" id="estado-procesando">
        Analizando RUT con IA... por favor espera
    </div>
    <div class="estado error" id="estado-error"></div>

    <label for="input-rut">
        <div class="upload-box" id="upload-box">
            <div class="icono">📄</div>
            <p><strong>Toca aqui para cargar tu RUT</strong></p>
            <p>Foto o imagen del documento</p>
        </div>
    </label>
    <input type="file" id="input-rut" accept="image/*" capture="environment"
           style="display:none" onchange="procesarRut(this)">

    <button class="btn" id="btn-continuar" disabled onclick="irAPaso2()">
        Continuar con mis datos
    </button>

    <div class="link-login">
        Ya tienes cuenta? <a href="/login">Inicia sesion</a>
    </div>

    <div class="footer">
        Sistema desarrollado por
        <a href="https://www.avanzas.digital/index.html" target="_blank">Avanzas Digital</a>
    </div>

</div>

<script>
let datosRut     = null;
let fotoBase64   = null;
let rutFotoPath  = null;

async function procesarRut(input) {
    const file = input.files[0];
    if (!file) return;

    // Preview
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-rut').src = e.target.result;
        document.getElementById('preview-rut').style.display = 'block';
        document.getElementById('upload-box').style.display = 'none';
    };
    reader.readAsDataURL(file);

    // Base64
    fotoBase64 = await fileToBase64(file);

    // Mostrar procesando
    document.getElementById('estado-procesando').style.display = 'block';
    document.getElementById('estado-error').style.display = 'none';
    document.getElementById('btn-continuar').disabled = true;

    try {
        const res = await fetch('/trial/procesar-rut', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ imagen: fotoBase64 }),
        });

        const data = await res.json();
        document.getElementById('estado-procesando').style.display = 'none';

        if (!data.success) {
            document.getElementById('estado-error').textContent = 'Error: ' + data.mensaje;
            document.getElementById('estado-error').style.display = 'block';
            return;
        }

        datosRut    = data.datos;
        rutFotoPath = data.rut_foto;
        document.getElementById('btn-continuar').disabled = false;

    } catch (e) {
        document.getElementById('estado-procesando').style.display = 'none';
        document.getElementById('estado-error').textContent = 'Error de conexion: ' + e.message;
        document.getElementById('estado-error').style.display = 'block';
    }
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = () => resolve(reader.result.split(',')[1]);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function irAPaso2() {
    if (!datosRut) return;
    sessionStorage.setItem('trial_datos', JSON.stringify(datosRut));
    sessionStorage.setItem('trial_rut_foto', rutFotoPath);
    window.location.href = '/trial/paso2';
}
</script>
</body>
</html>