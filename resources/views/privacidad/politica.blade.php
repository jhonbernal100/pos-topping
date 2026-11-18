@php
    $esCliente = (bool) $tenant;
    $nombre    = $responsable['nombre'];
    $color     = $tenant->color_primario ?? '#1F3A5F';
    $contacto  = $responsable['correo'] ?: $avanzas['correo'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Política de tratamiento de datos personales · {{ $nombre }}</title>
<meta name="description" content="Política de tratamiento de datos personales de {{ $nombre }} (Ley 1581 de 2012).">
<style>
    :root {
        --marca: {{ $color }};
        --texto: #1f2328;
        --suave: #57606a;
        --fondo: #f6f7f9;
        --papel: #ffffff;
        --borde: #e3e6ea;
    }
    @media (prefers-color-scheme: dark) {
        :root { --texto: #e6e8eb; --suave: #a3abb5; --fondo: #0f1216; --papel: #171b21; --borde: #2a3038; }
    }
    * { box-sizing: border-box; }
    body { margin: 0; background: var(--fondo); color: var(--texto);
           font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.6; font-size: 16px; }
    header { background: var(--marca); color: #fff; padding: 28px 16px 24px; }
    .contenedor { max-width: 820px; margin: 0 auto; }
    header .etiqueta { font-size: 13px; opacity: .85; letter-spacing: .04em; text-transform: uppercase; }
    header h1 { font-size: clamp(22px, 5vw, 30px); margin: 6px 0 6px; line-height: 1.25; }
    header p { margin: 0; opacity: .9; font-size: 15px; }
    main { padding: 20px 16px 48px; }
    .tarjeta { background: var(--papel); border: 1px solid var(--borde); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
    h2 { font-size: 18px; margin: 0 0 10px; color: var(--texto); }
    p, li { margin: 0 0 10px; }
    ul, ol { padding-left: 22px; margin: 0 0 4px; }
    .datos { display: grid; grid-template-columns: 1fr; gap: 10px; margin: 0; }
    .datos div { border-left: 3px solid var(--marca); padding-left: 10px; }
    .datos dt { font-size: 12px; color: var(--suave); text-transform: uppercase; letter-spacing: .03em; }
    .datos dd { margin: 0; font-weight: 600; word-break: break-word; }
    @media (min-width: 640px) { .datos { grid-template-columns: 1fr 1fr; } }
    .tabla { width: 100%; border-collapse: collapse; font-size: 15px; }
    .tabla th, .tabla td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--borde); vertical-align: top; }
    .tabla th { font-size: 13px; color: var(--suave); font-weight: 600; }
    @media (max-width: 560px) {
        .tabla thead { display: none; }
        .tabla tr { display: block; border-bottom: 1px solid var(--borde); padding: 8px 0; }
        .tabla td { display: block; border: 0; padding: 4px 0; }
        .tabla td::before { content: attr(data-titulo); display: block; font-size: 12px; color: var(--suave); }
    }
    .plazos { display: grid; grid-template-columns: 1fr; gap: 10px; }
    @media (min-width: 640px) { .plazos { grid-template-columns: 1fr 1fr; } }
    .plazo { border: 1px solid var(--borde); border-radius: 10px; padding: 14px; }
    .plazo strong { display: block; font-size: 22px; color: var(--marca); }
    @media (prefers-color-scheme: dark) { .plazo strong, .datos div { filter: brightness(1.3); } }
    a { color: var(--marca); }
    @media (prefers-color-scheme: dark) { a { color: #8ab4f8; } }
    .boton { display: inline-block; background: var(--marca); color: #fff; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: 600; }
    footer { color: var(--suave); font-size: 13px; text-align: center; padding: 0 16px 32px; }
    @media print {
        header { background: none; color: #000; border-bottom: 2px solid #000; }
        .tarjeta { border: 0; padding: 0 0 8px; }
        .boton { display: none; }
    }
</style>
</head>
<body>
<header>
    <div class="contenedor">
        <div class="etiqueta">Política de tratamiento de datos personales</div>
        <h1>{{ $nombre }}</h1>
        <p>Vigente desde el {{ $vigencia }} · Ley 1581 de 2012 y Decreto 1074 de 2015</p>
    </div>
</header>

<main class="contenedor">

    <section class="tarjeta">
        <h2>1. Quién es el responsable de tus datos</h2>
        <dl class="datos">
            <div><dt>Responsable</dt><dd>{{ $nombre }}</dd></div>
            @if($responsable['identificacion'])
                <div><dt>Identificación</dt><dd>{{ $responsable['identificacion'] }}</dd></div>
            @endif
            @if(!$esCliente)
                <div><dt>Representante legal</dt><dd>{{ $avanzas['representante'] }}</dd></div>
            @endif
            @if($responsable['direccion'])
                <div><dt>Dirección</dt><dd>{{ $responsable['direccion'] }}</dd></div>
            @endif
            <div><dt>Correo para consultas y reclamos</dt><dd><a href="mailto:{{ $contacto }}">{{ $contacto }}</a></dd></div>
            @if($responsable['telefono'])
                <div><dt>Teléfono</dt><dd>{{ $responsable['telefono'] }}</dd></div>
            @endif
        </dl>
        @if($esCliente)
            <p style="margin-top:14px;">
                {{ $nombre }} usa la plataforma POS Topping de <strong>{{ $avanzas['nombre'] }}</strong>, que trata los datos
                por cuenta de {{ $nombre }} como <strong>encargado</strong> y solo para prestarle el servicio.
            </p>
        @endif
    </section>

    <section class="tarjeta">
        <h2>2. Qué datos tratamos y para qué</h2>
        <table class="tabla">
            <thead><tr><th>De quién</th><th>Qué datos</th><th>Para qué</th></tr></thead>
            <tbody>
            @if($esCliente)
                <tr>
                    <td data-titulo="De quién">Clientes que piden en el local o a domicilio</td>
                    <td data-titulo="Qué datos">Nombre, número de celular, dirección y ubicación de entrega, comprobantes de pago, mensajes de WhatsApp</td>
                    <td data-titulo="Para qué">Tomar, cobrar, preparar y entregar tu pedido, y comunicarnos contigo sobre él</td>
                </tr>
                <tr>
                    <td data-titulo="De quién">Empleados</td>
                    <td data-titulo="Qué datos">Nombre, correo, cargo, sede y registros de actividad en la plataforma</td>
                    <td data-titulo="Para qué">Dar acceso a la plataforma y llevar el control de ventas, caja e inventario</td>
                </tr>
                <tr>
                    <td data-titulo="De quién">Proveedores y empresas con convenio</td>
                    <td data-titulo="Qué datos">Nombre o razón social, NIT, datos de contacto, facturas</td>
                    <td data-titulo="Para qué">Gestionar compras, pagos y cuentas por cobrar</td>
                </tr>
            @else
                <tr>
                    <td data-titulo="De quién">Clientes de {{ $avanzas['nombre'] }} y sus representantes</td>
                    <td data-titulo="Qué datos">Nombre, identificación, NIT, dirección, correo, teléfono, datos de facturación y de pago</td>
                    <td data-titulo="Para qué">Celebrar y ejecutar contratos, facturar, cobrar, dar soporte y enviar avisos del servicio</td>
                </tr>
                <tr>
                    <td data-titulo="De quién">Usuarios de las plataformas</td>
                    <td data-titulo="Qué datos">Nombre, correo, rol, contraseña cifrada y registros de actividad</td>
                    <td data-titulo="Para qué">Dar acceso seguro, prevenir fraudes y atender solicitudes de soporte</td>
                </tr>
                <tr>
                    <td data-titulo="De quién">Personas interesadas</td>
                    <td data-titulo="Qué datos">Nombre, correo, teléfono, empresa</td>
                    <td data-titulo="Para qué">Responder solicitudes, enviar propuestas y, si lo autorizas, información comercial</td>
                </tr>
            @endif
            </tbody>
        </table>
        <p style="margin-top:12px;">
            No pedimos datos sensibles ni datos de niñas, niños o adolescentes. No vendemos ni cedemos tus datos.
            @if(!$esCliente)
                Los datos que nuestros clientes cargan en POS Topping son de ellos: allí actuamos como encargados y aplicamos la política de cada cliente.
            @endif
        </p>
    </section>

    <section class="tarjeta">
        <h2>3. Tus derechos</h2>
        <p>Como titular de los datos tienes derecho a (art. 8 de la Ley 1581 de 2012):</p>
        <ul>
            <li>Conocer, actualizar y rectificar tus datos.</li>
            <li>Pedir prueba de la autorización que diste.</li>
            <li>Saber qué uso se les ha dado a tus datos.</li>
            <li>Revocar la autorización o pedir que se borren tus datos, cuando no exista un deber legal o contractual de conservarlos.</li>
            <li>Acceder gratis a tus datos.</li>
            <li>Presentar quejas ante la Superintendencia de Industria y Comercio, después de haber consultado o reclamado ante nosotros.</li>
        </ul>
    </section>

    <section class="tarjeta">
        <h2>4. Cómo hacer una consulta o un reclamo</h2>
        <p>
            Escribe a <a href="mailto:{{ $contacto }}">{{ $contacto }}</a> con tu nombre, número de documento, un dato de contacto
            y lo que necesitas. Si actúas en nombre de otra persona, adjunta la autorización.
        </p>
        <div class="plazos">
            <div class="plazo">
                <strong>10 días hábiles</strong>
                Para responder una consulta. Si no alcanza el tiempo, te avisamos y respondemos en máximo 5 días hábiles más.
            </div>
            <div class="plazo">
                <strong>15 días hábiles</strong>
                Para resolver un reclamo, desde que lo recibimos completo. Si no alcanza el tiempo, te avisamos y lo resolvemos en máximo 8 días hábiles más.
            </div>
        </div>
        <p style="margin-top:12px;">
            Si el reclamo está incompleto, te pedimos lo que falta dentro de los 5 días hábiles siguientes. Si pasan 2 meses sin respuesta tuya, se entiende que desististe.
        </p>
    </section>

    <section class="tarjeta">
        <h2>5. Autorización y seguridad</h2>
        <p>
            Tratamos tus datos con tu autorización, que puedes dar por escrito, de forma oral o con conductas claras, como compartirnos tu
            ubicación y dirección para recibir un pedido a domicilio. No la necesitamos en los casos del art. 10 de la Ley 1581 de 2012.
        </p>
        <p>
            Los datos se guardan en servidores de Namecheap y, para leer facturas y comprobantes, se usan servicios de inteligencia artificial de Google.
            Algunos de estos servidores pueden estar fuera de Colombia; en ese caso se transmiten con contratos que obligan a protegerlos.
            Usamos conexiones cifradas, contraseñas protegidas y acceso por roles.
        </p>
        <p>
            Conservamos los datos mientras dure la relación contigo y el tiempo que exijan las normas contables, tributarias y comerciales.
        </p>
    </section>

    <section class="tarjeta">
        <h2>6. Vigencia y cambios</h2>
        <p>
            Esta política rige desde el {{ $vigencia }}. Si cambia en algo importante, lo publicaremos en esta misma página antes de aplicarlo.
        </p>
        @if($esCliente)
            <p>Política de {{ $avanzas['nombre'] }} como encargado: <a href="{{ url('/privacidad') }}">{{ url('/privacidad') }}</a></p>
        @endif
        <a class="boton" href="mailto:{{ $contacto }}?subject={{ rawurlencode('Datos personales - ' . $nombre) }}">Escribir sobre mis datos</a>
    </section>
</main>

<footer>
    {{ $nombre }}@if($esCliente) · Plataforma POS Topping de {{ $avanzas['nombre'] }}@endif
</footer>
</body>
</html>
