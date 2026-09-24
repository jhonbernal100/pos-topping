<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes y Precios — POS Topping</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #000;
        }

        /* NAVBAR */
        .navbar {
            background: #000;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            color: #fff;
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
        }

        .navbar-links { display: flex; gap: 12px; }

        .navbar-links a {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background .2s;
        }

        .navbar-links a:hover { background: #222; color: #fff; }

        .navbar-links .btn-demo {
            background: #99CF8E;
            color: #000;
            font-weight: bold;
        }

        .navbar-links .btn-demo:hover { background: #7db872; }

        /* HERO */
        .hero {
            text-align: center;
            padding: 60px 24px 40px;
            background: #000;
            color: #fff;
        }

        .hero h1 { font-size: 36px; margin-bottom: 12px; }
        .hero p  { font-size: 16px; color: #aaa; max-width: 560px; margin: 0 auto 24px; line-height: 1.6; }

        .badge-ahorro {
            display: inline-block;
            background: #99CF8E;
            color: #000;
            font-size: 13px;
            font-weight: bold;
            padding: 6px 16px;
            border-radius: 20px;
        }

        /* PLANES GRID */
        .planes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            max-width: 1100px;
            margin: -20px auto 40px;
            padding: 0 24px;
        }

        .plan-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            border: 2px solid transparent;
            transition: transform .2s;
        }

        .plan-card:hover { transform: translateY(-4px); }
        .plan-card.popular { border-color: #99CF8E; }
        .plan-card.anual   { border-color: #000; }

        .badge-popular {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #99CF8E;
            color: #000;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 16px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .badge-ahorro-card {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: #000;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 4px 16px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .plan-nombre {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #888;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .plan-precio {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 4px;
            line-height: 1;
        }

        .plan-precio span { font-size: 16px; font-weight: normal; color: #888; }
        .plan-periodo { font-size: 13px; color: #888; margin-bottom: 6px; }
        .plan-cobro   { font-size: 12px; color: #555; margin-bottom: 24px; font-style: italic; }

        .plan-divider {
            border: none;
            border-top: 1px solid #eee;
            margin-bottom: 20px;
        }

        .plan-features { list-style: none; margin-bottom: 28px; }

        .plan-features li {
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .plan-features li:last-child { border-bottom: none; }

        .check { color: #99CF8E; font-weight: bold; flex-shrink: 0; }
        .cross  { color: #ccc; flex-shrink: 0; }

        .plan-features .seccion-titulo {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #aaa;
            letter-spacing: 1px;
            padding: 10px 0 4px;
            border-bottom: none;
        }

        .btn-plan {
            display: block;
            width: 100%;
            padding: 14px;
            text-align: center;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: opacity .2s;
        }

        .btn-plan:hover { opacity: .85; }
        .btn-trial      { background: #f0f0f0; color: #000; }
        .btn-trimestral { background: #99CF8E; color: #000; }
        .btn-anual      { background: #000; color: #fff; }

        /* COMPARATIVO */
        .comparativo {
            max-width: 900px;
            margin: 0 auto 60px;
            padding: 0 24px;
        }

        .comparativo h2 {
            font-size: 24px;
            text-align: center;
            margin-bottom: 24px;
        }

        .comparativo table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }

        .comparativo th {
            background: #000;
            color: #fff;
            padding: 14px;
            font-size: 13px;
            text-align: center;
        }

        .comparativo th:first-child { text-align: left; }

        .comparativo td {
            padding: 12px 14px;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
            text-align: center;
        }

        .comparativo td:first-child { text-align: left; font-weight: bold; }
        .comparativo tr:last-child td { border-bottom: none; }
        .comparativo tr:nth-child(even) td { background: #fafafa; }

        .si  { color: #99CF8E; font-weight: bold; font-size: 16px; }
        .no  { color: #ddd; font-size: 16px; }

        /* FAQ */
        .faq {
            max-width: 700px;
            margin: 0 auto 60px;
            padding: 0 24px;
        }

        .faq h2 { font-size: 24px; text-align: center; margin-bottom: 24px; }

        .faq-item {
            background: #fff;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        .faq-item h4 { font-size: 14px; margin-bottom: 6px; }
        .faq-item p  { font-size: 13px; color: #555; line-height: 1.6; }

        /* CTA FINAL */
        .cta-final {
            background: #000;
            color: #fff;
            text-align: center;
            padding: 60px 24px;
        }

        .cta-final h2 { font-size: 28px; margin-bottom: 12px; }
        .cta-final p  { font-size: 15px; color: #aaa; margin-bottom: 28px; }

        .cta-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

        .cta-btn-primary {
            padding: 14px 32px;
            background: #99CF8E;
            color: #000;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
        }

        .cta-btn-secondary {
            padding: 14px 32px;
            background: transparent;
            color: #fff;
            border: 2px solid #444;
            border-radius: 10px;
            font-size: 15px;
            text-decoration: none;
        }

        /* FOOTER */
        .footer {
            background: #111;
            color: #555;
            text-align: center;
            padding: 20px;
            font-size: 12px;
        }

        .footer a { color: #888; text-decoration: none; }

        @media (max-width: 600px) {
            .hero h1 { font-size: 26px; }
            .plan-precio { font-size: 32px; }
            .navbar-links .btn-login { display: none; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="/" class="navbar-brand">POS Topping</a>
    <div class="navbar-links">
        <a href="/login" class="btn-login">Iniciar sesion</a>
        <a href="/trial" class="btn-demo">Solicitar demo gratis</a>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>Planes simples, sin sorpresas</h1>
    <p>
        Digitaliza tu negocio con el sistema POS mas completo de Colombia.
        Empieza gratis y escala cuando lo necesites.
    </p>
    <span class="badge-ahorro">Plan anual — ahorra $120,000 COP vs trimestral</span>
</div>

<!-- PLANES -->
<div class="planes-grid">

    <!-- TRIAL -->
    <div class="plan-card">
        <div class="plan-nombre">Trial</div>
        <div class="plan-precio">$0 <span>/ mes</span></div>
        <div class="plan-periodo">30 dias gratis</div>
        <div class="plan-cobro">Sin tarjeta de credito</div>
        <hr class="plan-divider">
        <ul class="plan-features">
            <li class="seccion-titulo">Limites</li>
            <li><span class="check">+</span> Hasta 250 productos</li>
            <li><span class="check">+</span> 2 usuarios (1 gerente + 1 auxiliar)</li>
            <li class="seccion-titulo">Punto de venta</li>
            <li><span class="check">+</span> Nueva venta rapida</li>
            <li><span class="check">+</span> Tiquete POS fisico</li>
            <li><span class="check">+</span> Ventas a credito</li>
            <li><span class="check">+</span> Devoluciones</li>
            <li><span class="cross">-</span> Factura electronica DIAN</li>
            <li class="seccion-titulo">Inventario</li>
            <li><span class="check">+</span> Captura con foto e IA</li>
            <li><span class="check">+</span> Crear productos manual</li>
            <li><span class="cross">-</span> Reportes PDF</li>
            <li class="seccion-titulo">Soporte</li>
            <li><span class="cross">-</span> Soporte prioritario</li>
            <li><span class="check">+</span> Actualizaciones incluidas</li>
        </ul>
        <a href="/trial" class="btn-plan btn-trial">Comenzar gratis</a>
    </div>

    <!-- TRIMESTRAL -->
    <div class="plan-card popular">
        <div class="badge-popular">MAS POPULAR</div>
        <div class="plan-nombre">Trimestral</div>
        <div class="plan-precio">$45,000 <span>/ mes</span></div>
        <div class="plan-periodo">Facturado cada 3 meses</div>
        <div class="plan-cobro">$135,000 COP cada trimestre</div>
        <hr class="plan-divider">
        <ul class="plan-features">
            <li class="seccion-titulo">Limites</li>
            <li><span class="check">+</span> Productos ilimitados</li>
            <li><span class="check">+</span> Usuarios ilimitados</li>
            <li class="seccion-titulo">Punto de venta</li>
            <li><span class="check">+</span> Nueva venta rapida</li>
            <li><span class="check">+</span> Tiquete POS fisico</li>
            <li><span class="check">+</span> Ventas a credito</li>
            <li><span class="check">+</span> Devoluciones</li>
            <li><span class="check">+</span> Factura electronica DIAN</li>
            <li class="seccion-titulo">Inventario y reportes</li>
            <li><span class="check">+</span> Captura con foto e IA</li>
            <li><span class="check">+</span> Reporte inventario completo</li>
            <li><span class="check">+</span> Reporte alertas stock bajo</li>
            <li><span class="check">+</span> Reporte ventas dia/semana/mes</li>
            <li><span class="check">+</span> Kardex de producto</li>
            <li><span class="check">+</span> Reporte cartera de creditos</li>
            <li><span class="check">+</span> Estado financiero mensual</li>
            <li class="seccion-titulo">Soporte</li>
            <li><span class="check">+</span> Soporte por WhatsApp</li>
            <li><span class="check">+</span> Actualizaciones sin costo</li>
            <li><span class="check">+</span> Renovacion automatica</li>
        </ul>
        <a href="https://wa.me/573125625170?text=Hola,%20quiero%20activar%20el%20plan%20Trimestral%20de%20POS%20Topping"
           target="_blank" class="btn-plan btn-trimestral">
            Activar plan trimestral
        </a>
    </div>

    <!-- ANUAL -->
    <div class="plan-card anual">
        <div class="badge-ahorro-card">AHORRA $120,000/ANO</div>
        <div class="plan-nombre">Anual</div>
        <div class="plan-precio">$35,000 <span>/ mes</span></div>
        <div class="plan-periodo">Facturado anualmente</div>
        <div class="plan-cobro">$420,000 COP al ano</div>
        <hr class="plan-divider">
        <ul class="plan-features">
            <li class="seccion-titulo">Todo lo del plan trimestral mas:</li>
            <li><span class="check">+</span> Productos ilimitados</li>
            <li><span class="check">+</span> Usuarios ilimitados</li>
            <li><span class="check">+</span> Tiquete POS fisico</li>
            <li><span class="check">+</span> Factura electronica DIAN</li>
            <li><span class="check">+</span> Todos los reportes PDF</li>
            <li><span class="check">+</span> Kardex y estado financiero</li>
            <li><span class="check">+</span> Captura con foto e IA</li>
            <li class="seccion-titulo">Beneficios exclusivos</li>
            <li><span class="check">+</span> Capacitacion virtual incluida</li>
            <li><span class="check">+</span> Soporte prioritario 24/7</li>
            <li><span class="check">+</span> Renovacion automatica</li>
            <li><span class="check">+</span> Actualizaciones sin costo</li>
            <li><span class="check">+</span> Ahorro de $120,000 vs trimestral</li>
        </ul>
        <a href="https://wa.me/573125625170?text=Hola,%20quiero%20activar%20el%20plan%20Anual%20de%20POS%20Topping"
           target="_blank" class="btn-plan btn-anual">
            Activar plan anual
        </a>
    </div>

</div>

<!-- COMPARATIVO -->
<div class="comparativo">
    <h2>Comparativo de planes</h2>
    <table>
        <thead>
            <tr>
                <th>Caracteristica</th>
                <th>Trial</th>
                <th>Trimestral</th>
                <th>Anual</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Precio mensual</td>
                <td>Gratis</td>
                <td>$45,000</td>
                <td>$35,000</td>
            </tr>
            <tr>
                <td>Duracion</td>
                <td>30 dias</td>
                <td>3 meses</td>
                <td>12 meses</td>
            </tr>
            <tr>
                <td>Productos</td>
                <td>250</td>
                <td>Ilimitados</td>
                <td>Ilimitados</td>
            </tr>
            <tr>
                <td>Usuarios</td>
                <td>2</td>
                <td>Ilimitados</td>
                <td>Ilimitados</td>
            </tr>
            <tr>
                <td>Tiquete POS</td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Factura electronica DIAN</td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Captura con foto e IA</td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Reportes PDF completos</td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Estado financiero</td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Soporte WhatsApp</td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Soporte prioritario 24/7</td>
                <td><span class="no">-</span></td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Capacitacion virtual</td>
                <td><span class="no">-</span></td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Renovacion automatica</td>
                <td><span class="no">-</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
            <tr>
                <td>Actualizaciones incluidas</td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
                <td><span class="si">+</span></td>
            </tr>
        </tbody>
    </table>
</div>

<!-- FAQ -->
<div class="faq">
    <h2>Preguntas frecuentes</h2>

    <div class="faq-item">
        <h4>Necesito tarjeta de credito para el trial?</h4>
        <p>No. El trial de 30 dias es completamente gratuito y no requiere ningun dato de pago. Solo necesitas tu RUT y un correo electronico.</p>
    </div>

    <div class="faq-item">
        <h4>Que pasa cuando vence el trial?</h4>
        <p>Tu cuenta queda en modo lectura — puedes ver tus datos pero no realizar nuevas ventas. Puedes activar cualquier plan en cualquier momento para continuar.</p>
    </div>

    <div class="faq-item">
        <h4>Puedo cambiar de plan despues?</h4>
        <p>Si. Puedes pasar del plan trimestral al anual en cualquier momento. Contactanos por WhatsApp y hacemos el cambio sin perder ningun dato.</p>
    </div>

    <div class="faq-item">
        <h4>Como funciona la factura electronica?</h4>
        <p>Disponible desde el plan trimestral. El sistema se conecta directamente con la DIAN para emitir facturas electronicas validas. El tiquete POS es valido para ventas al consumidor final menores a 5 UVT (~$235,000 COP).</p>
    </div>

    <div class="faq-item">
        <h4>Mis datos estan seguros?</h4>
        <p>Si. Cada negocio tiene sus datos completamente aislados. Utilizamos cifrado SSL y copias de seguridad automaticas diarias. Ningun negocio puede ver los datos de otro.</p>
    </div>

    <div class="faq-item">
        <h4>Como activo el plan trimestral o anual?</h4>
        <p>Por ahora la activacion se hace por WhatsApp. Te contactamos, confirmamos el pago y activamos el plan en menos de 2 horas. Proximamente habra activacion automatica con tarjeta de credito.</p>
    </div>
</div>

<!-- CTA FINAL -->
<div class="cta-final">
    <h2>Listo para modernizar tu negocio?</h2>
    <p>Empieza hoy con 30 dias gratis. Sin tarjeta, sin compromisos.</p>
    <div class="cta-btns">
        <a href="/trial" class="cta-btn-primary">Solicitar demo gratis</a>
        <a href="https://wa.me/573125625170?text=Hola,%20quiero%20informacion%20sobre%20POS%20Topping"
           target="_blank" class="cta-btn-secondary">
            Hablar con un asesor
        </a>
    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    POS Topping por
    <a href="https://www.avanzas.digital/index.html" target="_blank">Avanzas Digital</a>
    &nbsp;·&nbsp; Bogota, Colombia &nbsp;·&nbsp;
    <a href="/login">Iniciar sesion</a>
    &nbsp;·&nbsp;
    <a href="/trial">Solicitar demo</a>
</div>

</body>
</html>