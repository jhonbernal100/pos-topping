<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Topping — Avanzas Digital</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            min-height: 100vh;
            display: flex;
        }

        .panel-login {
            width: 420px;
            min-width: 420px;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 36px;
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
            z-index: 1;
        }

        .logo-container { text-align: center; margin-bottom: 8px; }

        .version-badge {
            display: inline-block;
            background: #f0f0f0;
            color: #CEC8BF;
            font-size: 11px;
            padding: 3px 12px;
            border-radius: 12px;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .slogan {
            font-size: 12px;
            color: #99CF8E;
            text-align: center;
            margin-bottom: 28px;
            font-style: italic;
        }

        .divider {
            width: 100%;
            height: 1px;
            background: #f0f0f0;
            margin-bottom: 24px;
        }

        .form-titulo {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .campo { margin-bottom: 16px; width: 100%; }
        .campo label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .campo input {
            width: 100%;
            padding: 13px 16px;
            font-size: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: border-color .2s;
            outline: none;
        }
        .campo input:focus { border-color: #99CF8E; }

        .error {
            background: #fdf0f0;
            color: #c00;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
            border-left: 3px solid #c00;
            width: 100%;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
            transition: background .2s;
            margin-top: 4px;
        }

        .btn-login:hover { background: #222; }

        .footer-login {
            margin-top: 24px;
            width: 100%;
        }

        .footer-divider {
            width: 100%;
            height: 1px;
            background: #f0f0f0;
            margin-bottom: 16px;
        }

        .footer-demo {
            display: block;
            width: 100%;
            padding: 12px;
            background: #99CF8E;
            color: #000;
            text-align: center;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            transition: background .2s;
        }

        .footer-demo:hover { background: #7db872; }

        .footer-planes {
            display: block;
            width: 100%;
            padding: 10px;
            background: #f5f5f5;
            color: #000;
            text-align: center;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 12px;
            border: 1px solid #e0e0e0;
            transition: background .2s;
        }

        .footer-planes:hover { background: #eee; }

        .footer-contacto {
            text-align: center;
            font-size: 11px;
            color: #bbb;
        }

        .footer-contacto a {
            color: #99CF8E;
            text-decoration: none;
            font-weight: bold;
        }

        /* Panel derecho */
        .panel-video {
            flex: 1;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .panel-video::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(153,207,142,0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .video-titulo {
            color: #fff;
            font-size: 26px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            z-index: 1;
        }

        .video-titulo span { color: #99CF8E; }

        .video-subtitulo {
            color: #CEC8BF;
            font-size: 14px;
            text-align: center;
            margin-bottom: 24px;
            z-index: 1;
        }

        .carrusel-wrapper {
            width: 100%;
            max-width: 680px;
            z-index: 1;
        }

        .carrusel-box {
            width: 100%;
            aspect-ratio: 16/9;
            border-radius: 14px;
            overflow: hidden;
            background: #111;
            border: 1px solid #333;
            position: relative;
        }

        .carrusel-box img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.8s ease;
        }

        .carrusel-box img.activo { opacity: 1; }

        .indicadores {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
        }

        .indicador {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #444;
            cursor: pointer;
            transition: background .3s, transform .3s;
        }

        .indicador.activo {
            background: #99CF8E;
            transform: scale(1.3);
        }

        .features {
            display: flex;
            gap: 16px;
            margin-top: 20px;
            z-index: 1;
            flex-wrap: wrap;
            justify-content: center;
        }

        .feature {
            text-align: center;
            background: rgba(255,255,255,0.05);
            border: 1px solid #333;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .feature .icono { font-size: 22px; margin-bottom: 4px; }
        .feature .texto { font-size: 11px; color: #CEC8BF; line-height: 1.4; }

        .powered-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 16px;
            background: #222;
            border: 1px solid #333;
            border-radius: 20px;
            padding: 6px 16px;
            z-index: 1;
        }

        .powered-badge .gem-star { width: 16px; height: 16px; }
        .powered-badge span { font-size: 11px; color: #888; }
        .powered-badge strong { font-size: 12px; color: #fff; }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .panel-login { width: 100%; min-width: unset; }
            .panel-video { display: none; }
        }
    </style>
</head>
<body>

    <!-- Panel izquierdo — Login -->
    <div class="panel-login">

        <div class="logo-container">
            <img src="/images/logo-pos-ferretero.png"
                 alt="POS Topping"
                 style="width:260px;max-width:100%;">
        </div>

        @php
            $commits = (int) trim(shell_exec('git rev-list --count HEAD') ?? 0);
            $major   = 1;
            $minor   = floor($commits / 100);
            $patch   = str_pad($commits % 100, 3, '0', STR_PAD_LEFT);
        @endphp
        <div class="version-badge">v {{ $major }}.{{ $minor }}.{{ $patch }} · {{ date('Y') }}</div>

        <div class="slogan">Tu exito es nuestro objetivo</div>

        <div class="divider"></div>

        <div class="form-titulo">Iniciar sesion</div>

        @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="/login" style="width:100%">
            @csrf
            <div class="campo">
                <label>Correo electronico</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="tu@correo.com" required autofocus>
            </div>
            <div class="campo">
                <label>Contrasena</label>
                <input type="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Ingresar</button>
        </form>

        <div class="footer-login">
            <div class="footer-divider"></div>

            <a href="/trial" class="footer-demo">
                Solicitar demo gratuito — 30 dias gratis
            </a>

            <a href="/planes" class="footer-planes">
                Ver todos los planes y precios
            </a>

            <div class="footer-contacto">
                Necesitas ayuda?
                <a href="https://www.avanzas.digital/index.html" target="_blank">
                    Contacta a Avanzas Digital
                </a>
            </div>
        </div>
    </div>

    <!-- Panel derecho — Carrusel -->
    <div class="panel-video">

        <div class="video-titulo">
            El POS mas simple para<br>
            <span>heladerías y negocios de postres</span>
        </div>

        <div class="video-subtitulo">
            Vende, inventaria y controla tu negocio desde cualquier dispositivo.
            Sin complicaciones, sin instalaciones.
        </div>

        <div class="carrusel-wrapper">
            <div class="carrusel-box" id="carrusel">
                @for($i = 1; $i <= 10; $i++)
                <img src="/slides/slide-{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}.jpg"
                     alt="Slide {{ $i }}"
                     class="slide {{ $i === 1 ? 'activo' : '' }}">
                @endfor
            </div>

            <div class="indicadores" id="indicadores">
                @for($i = 0; $i < 10; $i++)
                <div class="indicador {{ $i === 0 ? 'activo' : '' }}"
                     data-index="{{ $i }}"
                     onclick="irASlide({{ $i }})">
                </div>
                @endfor
            </div>
        </div>

        <div class="features">
            <div class="feature">
                <div class="icono">📷</div>
                <div class="texto">Inventario<br>con IA</div>
            </div>
            <div class="feature">
                <div class="icono">🖨</div>
                <div class="texto">Tickets<br>termicos</div>
            </div>
            <div class="feature">
                <div class="icono">💳</div>
                <div class="texto">Control de<br>creditos</div>
            </div>
            <div class="feature">
                <div class="icono">📊</div>
                <div class="texto">Reportes<br>de ventas</div>
            </div>
            <div class="feature">
                <div class="icono">📱</div>
                <div class="texto">PWA<br>movil</div>
            </div>
        </div>

        <div class="powered-badge">
            <svg class="gem-star" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <radialGradient id="grd" cx="35%" cy="25%" r="75%">
                        <stop offset="0%" stop-color="#EA4335"/>
                        <stop offset="30%" stop-color="#4285F4"/>
                        <stop offset="65%" stop-color="#34A853"/>
                        <stop offset="100%" stop-color="#FBBC05"/>
                    </radialGradient>
                </defs>
                <path d="M12 2 C13 7 17 11 22 12 C17 13 13 17 12 22 C11 17 7 13 2 12 C7 11 11 7 12 2 Z"
                      fill="url(#grd)"/>
            </svg>
            <span>Powered by</span>
            <strong>Google Gemini</strong>
        </div>

    </div>

    <script>
    let slideActual = 0;
    const totalSlides = 10;
    let intervalo;

    function irASlide(index) {
        const slides      = document.querySelectorAll('.slide');
        const indicadores = document.querySelectorAll('.indicador');

        slides[slideActual].classList.remove('activo');
        indicadores[slideActual].classList.remove('activo');

        slideActual = index;

        slides[slideActual].classList.add('activo');
        indicadores[slideActual].classList.add('activo');
    }

    function siguienteSlide() {
        irASlide((slideActual + 1) % totalSlides);
    }

    intervalo = setInterval(siguienteSlide, 4000);

    document.getElementById('carrusel').addEventListener('mouseenter', () => clearInterval(intervalo));
    document.getElementById('carrusel').addEventListener('mouseleave', () => {
        intervalo = setInterval(siguienteSlide, 4000);
    });
    </script>

</body>
</html>