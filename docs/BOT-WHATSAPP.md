# Bot de domicilios por WhatsApp — puesta en marcha

El POS Topping (Namecheap) habla con WhatsApp a través de **OpenWA**, que corre en un VPS aparte.

```
Cliente WhatsApp ⇄ OpenWA (VPS, wa.avanzas.digital) ⇄ POS Topping (pos-topping.avanzas.digital)
                       webhook firmado → /api/webhooks/openwa
                       ← envío de mensajes con X-API-Key
```

## 1. VPS (una sola vez para todos los clientes)

Recomendado: Namecheap VPS **Quasar** (4 núcleos, 6 GB) con **Ubuntu 24.04**.

1. En el DNS de `avanzas.digital`, crea un registro **A**: `wa` → IP del VPS.
2. Entra por SSH al VPS e instala Docker y Caddy (HTTPS automático):

```bash
apt update && apt -y upgrade
curl -fsSL https://get.docker.com | sh
apt -y install caddy ufw
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw --force enable
```

3. Instala OpenWA:

```bash
cd /opt && git clone https://github.com/rmyndharis/OpenWA.git && cd OpenWA
cp .env.example .env
# En .env deja (o agrega) estas líneas:
#   ENGINE_TYPE=whatsapp-web.js      ← menor riesgo de bloqueo
#   RESOLVE_LID_TO_PHONE=true        ← el POS recibe el teléfono del cliente
#   SEND_PACING_ENABLED=true         ← limita envíos por día (protección)
nano .env
docker compose up -d
docker exec openwa-api cat /app/data/.api-key   # copia la llave (sin el prompt que queda pegado)
```

4. Publica OpenWA con HTTPS (el puerto 2785 queda solo en localhost):

```bash
cat > /etc/caddy/Caddyfile <<'CADDY'
wa.avanzas.digital {
    reverse_proxy 127.0.0.1:2785
}
CADDY
systemctl reload caddy
```

Abre `https://wa.avanzas.digital` → panel de OpenWA (entra con la llave).

## 2. Variables en el POS (`.env` de Namecheap y de IDX)

```
OPENWA_URL=https://wa.avanzas.digital
OPENWA_API_KEY=<llave de OpenWA>
OPENWA_WEBHOOK_SECRET=<texto aleatorio de 32+ caracteres>
OPENWA_PERMITIR_TRIAL=false        # true solo para pruebas con un negocio en demo
```

Genera el secreto con: `php -r "echo bin2hex(random_bytes(24));"`
Luego: `php artisan optimize:clear` (y `php artisan optimize` en producción).

## 3. Conectar el WhatsApp de un negocio (por cada cliente con plan anual)

Usa un **número dedicado** para pedidos (no el personal). El número debe tener WhatsApp o WhatsApp Business instalado en un celular: OpenWA se conecta como "dispositivo vinculado".

1. Panel de OpenWA → **Sessions** → crear sesión con un ID corto, por ejemplo `gaspar` → **Start** → aparece el código QR.
2. En el celular del negocio: WhatsApp → Dispositivos vinculados → Vincular dispositivo → escanear el QR.
3. Panel de OpenWA → **Webhooks** de esa sesión:
   - URL: `https://pos-topping.avanzas.digital/api/webhooks/openwa`
   - Eventos: `message.received`, `session.status`
   - Secret: el mismo valor de `OPENWA_WEBHOOK_SECRET`
4. En el POS, el propietario entra a **Domicilios → Configuración**:
   - ID de sesión: `gaspar`, número del bot, Nequi / Daviplata, tarifas por km, ubicación y radio de cada sede.
   - Marca **Bot activo** y guarda. Arriba debe decir **Conectado**.

## 4. Prueba

Desde otro celular escribe "hola" al número del bot y sigue el flujo: ubicación → dirección → menú → pedido →
captura del pago → en la caja, **Domicilios → Pagó por Nequi** → cocina → **Despachar**.

## Cuidados (OpenWA no es la API oficial de WhatsApp)

- El bot solo responde a quien escribe primero. No uses el número para mensajes masivos.
- Si WhatsApp restringe el número, OpenWA lo reporta en el panel (`session.restriction`).
- Si algún día se pasa a la API oficial de Meta, solo se cambia `App\Services\WhatsApp\OpenWaGateway`.
- Respaldo: la carpeta `/opt/OpenWA/data` guarda las sesiones; inclúyela en tus copias de seguridad.
