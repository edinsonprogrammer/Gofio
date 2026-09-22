# Gofio

**Gofio** es una red social comunitaria en español. Combina lo mejor de las plataformas que ya conoces — feed de publicaciones, perfiles, mensajes privados, vídeo corto y gamificación — en una sola experiencia fluida, pensada para crear contenido, conectar con otros y crecer dentro de la comunidad.

Construida con **Laravel 12**, **Inertia.js** y **Vue 3**, se siente como una aplicación moderna de una sola página, pero con la solidez de un monolito PHP bien estructurado.

---

## Tabla de contenidos

- [Qué puedes hacer en Gofio](#qué-puedes-hacer-en-gofio)
- [Feed y publicaciones](#feed-y-publicaciones)
- [Interacción social](#interacción-social)
- [Vidu — vídeo corto](#vidu--vídeo-corto)
- [Monedero y propinas](#monedero-y-propinas)
- [Gamificación](#gamificación)
- [Creator Plus](#creator-plus)
- [Personalización y medios](#personalización-y-medios)
- [Panel de administración](#panel-de-administración)
- [Herramientas internas y despliegue](#herramientas-internas-y-despliegue)
- [Stack técnico](#stack-técnico)
- [Instalación local](#instalación-local)
- [Despliegue en producción](#despliegue-en-producción)
- [Licencia](#licencia)

---

## Qué puedes hacer en Gofio

| Área | Lo esencial |
|------|-------------|
| **Publicar** | Posts con texto enriquecido, imágenes, embeds, emojis y GIFs |
| **Conversar** | Comentarios anidados, reacciones y chat privado en tiempo real |
| **Descubrir** | Feed por categorías, búsqueda global y perfiles públicos |
| **Crear vídeo** | Vidu: feed vertical estilo reels con subida, likes y guardados |
| **Ganar reputación** | Karma, rangos, medallas y premios comunitarios |
| **Apoyar creadores** | Monedas virtuales y propinas directas en publicaciones |
| **Destacarte** | Verificación de identidad, Creator Plus y temas personalizados |

---

## Feed y publicaciones

### Inicio y categorías

El **feed principal** carga publicaciones con scroll infinito. Puedes filtrar por **categorías temáticas** (tecnología, humor, debate, etc.) desde la barra lateral en escritorio o el menú móvil. Un carrusel de accesos rápidos conecta con Vidu y otros módulos.

### Compositor de posts

El editor de publicaciones incluye:

- Título, etiquetas y categoría
- Texto enriquecido: encabezados, listas, citas, bloques de código
- Imágenes subidas al servidor
- Emojis y **GIFs de GIPHY** (búsqueda y tendencias)
- Opciones avanzadas según tu rango: posts privados, bloqueo de comentarios, borradores

Los **límites diarios** de publicación, longitud del contenido y herramientas disponibles dependen de tu **rango de karma** y, si lo tienes, de **Creator Plus**.

### Vista de post

Cada publicación tiene su URL pública (`/post/{slug}`) con SEO optimizado. Desde el feed o la página del post puedes:

- Reaccionar con cinco emojis (Me gusta, Excelente, Lindo, Desacuerdo, Asombroso)
- Comentar y responder en hilos anidados
- Enviar propinas al autor
- Guardar, compartir o **denunciar** contenido inapropiado
- Ver contador de visitas únicas

---

## Interacción social

### Perfiles

Todo usuario tiene un perfil público con:

- Avatar, portada (con reposicionamiento), @nick y biografía
- GIF animado en la bio (GIPHY)
- País, edad y enlaces a redes (WhatsApp, Instagram, Facebook, X)
- Estadísticas: karma, seguidores, medallas y premios
- Historial de posts y vídeos Vidu

Desde **Configuración → Perfil** puedes editar todos estos campos y cambiar contraseña.

### Seguir y buscar

- **Seguir / dejar de seguir** con contador de seguidores en tiempo real
- Notificación cuando alguien te sigue o cuando un usuario que sigues publica
- **Búsqueda global** por @nick, nombre de usuario y contenido de posts

### Comentarios

- Hilos con respuestas anidadas
- Editor con emoji, GIF, imágenes y contador de límite diario
- Votos positivos (puntos) con peso según tu rango
- Marcador visual del autor del post en el hilo
- Guardar, compartir o denunciar comentarios

### Chat privado

Mensajería en tiempo real con **Laravel Reverb**:

- Panel flotante (dock) accesible desde la barra superior
- Lista de conversaciones y contactos (personas que sigues)
- Indicador de presencia en línea
- Notificaciones instantáneas de mensajes nuevos

### Notificaciones

Centro de notificaciones con campana en la barra superior. Tipos soportados:

- Nuevo seguidor, comentario, reacción, mensaje
- Propina recibida, subida de rango, medalla o premio otorgado
- Verificación aprobada, visita al perfil (Creator Plus)
- Publicación de alguien que sigues, acciones de moderación

Actualización por polling y WebSocket. Las propinas muestran un toast animado al instante.

### Denuncias

Los usuarios pueden reportar posts y comentarios con motivo y detalle opcional. El equipo de moderación revisa cada denuncia desde el panel admin.

---

## Vidu — vídeo corto

Módulo de **vídeo vertical** inspirado en reels y TikTok:

| Función | Detalle |
|---------|---------|
| **Feed Vidu** | Scroll vertical con pestañas: Para ti, Guardados, Mis videos |
| **Reproductor** | Autoplay, silencio, like, guardar, compartir, eliminar (propios) |
| **Subir vídeo** | Formulario con validación, miniatura automática y límites de duración |
| **Página pública** | URL compartible por vídeo (`/vidu/{id}`) con SEO |
| **Perfil** | Sección de vídeos recientes + historial completo |
| **Publicidad** | Espacios configurables entre vídeos (gestionados desde admin) |

Creator Plus permite subidas más largas. Los usuarios sin suscripción pueden ver anuncios según la configuración global.

---

## Monedero y propinas

- Cada usuario tiene un saldo de **monedas** visible en la barra lateral
- Puedes enviar **propinas** a autores de posts desde un botón integrado en cada publicación
- La plataforma aplica una comisión configurable (fee) sobre cada propina
- El destinatario recibe notificación en tiempo real con animación de celebración
- Creator Plus se puede pagar con monedas del monedero

---

## Gamificación

### Karma y rangos

El **karma** mide la actividad y la calidad de tu participación. Al acumular puntos subes de **rango** (Newbie → rangos superiores), desbloqueando:

- Más publicaciones diarias
- Mayor peso de voto en comentarios
- Herramientas extra en el compositor
- Permisos para enviar o recibir propinas

Las reglas de karma son configurables desde el panel admin (publicar, comentar, reacciones, seguidores, Vidu, propinas, etc.).

### Medallas y premios

- **Medallas**: logros automáticos (karma, posts, comentarios, verificación) o concedidos manualmente por el staff
- **Premios**: reconocimientos comunitarios organizados por categorías, otorgados por administradores
- Ambos se muestran en el perfil con tooltips descriptivos

### Verificación de identidad

Solicitud desde **Configuración → Verificación**:

1. Nombre completo y tipo de documento
2. Subida de identificación
3. Revisión por el equipo → badge azul de verificado en perfil y posts

---

## Creator Plus

Suscripción premium pagada con monedas (pasarela con tarjeta: próximamente):

| Beneficio | Descripción |
|-----------|-------------|
| Sin anuncios | Experiencia limpia en feed y Vidu |
| Check verificado dorado | Distintivo visual de suscriptor |
| Tema dorado exclusivo | Skin premium en apariencia |
| Karma x2 | Duplica puntos de karma |
| Vidu extendido | Subidas de mayor duración |
| Brillo en posts | Resaltado visual en el feed |
| Visitas al perfil | Ver quién visitó tu perfil |
| Soporte prioritario | Tickets con prioridad alta ante moderación |

Gestión en **Configuración → Creator Plus** con planes de 1 a 12 meses.

---

## Personalización y medios

### Temas visuales

Skins completos con variables CSS. Algunos requieren Creator Plus o rango staff. Selección instantánea en **Configuración → Apariencia**.

Temas incluidos en el repositorio: *Twitter Dark*, *Minimal Clean*, *Indigo Corporativo*, *Compact Pro*, entre otros. Nuevos temas se añaden como carpetas en `themes/` y se sincronizan con un comando Artisan.

### Paquetes de iconos

Sistema de iconografía personalizable para rangos, medallas y la interfaz. El pack **Gemas** integra miles de iconos Font Awesome con nombres en español. Sincronización desde `icon-packs/`.

### GIPHY

Integración nativa en posts, comentarios y biografía de perfil. Requiere `GIPHY_API_KEY` en `.env`.

### SEO y contenido público

Páginas indexables para posts, perfiles y vídeos. Archivos generados automáticamente:

- `/robots.txt`, `/sitemap.xml`, `/feed.xml`
- `/llms.txt`, `/ai.txt` (directrices para crawlers de IA)
- IndexNow para notificar buscadores de contenido nuevo

---

## Panel de administración

Acceso en `/admin` para administradores globales y staff con permisos por pestaña. **17 secciones** organizadas en seis grupos:

### General

| Sección | Qué permite |
|---------|-------------|
| **Panel** | Estadísticas globales (usuarios, posts, denuncias, tickets, verificaciones), logs recientes y estado del sitio |

### Moderación

| Sección | Qué permite |
|---------|-------------|
| **Usuarios** | Buscar, banear/desbanear, editar karma/monedas/rango, revocar verificación o Creator Plus |
| **Publicaciones** | Listar, banear, publicar, destacar o fijar (sticky) posts |
| **Moderación** | Revisar denuncias de posts/comentarios/usuarios, editar contenido reportado, suspender autores; resolver alertas automáticas |

### Contenido

| Sección | Qué permite |
|---------|-------------|
| **Categorías** | Crear, editar y eliminar categorías del feed (con icono) |
| **Palabras prohibidas** | Filtro (enmascarar) o bloqueo de términos en contenido generado por usuarios |

### Soporte

| Sección | Qué permite |
|---------|-------------|
| **Tickets** | Gestionar solicitudes de soporte (prioridad Creator Plus primero) |
| **Verificaciones** | Aprobar, rechazar o revocar solicitudes de identidad; descarga de documentos; acciones en lote |

### Gamificación

| Sección | Qué permite |
|---------|-------------|
| **Medallas** | CRUD de medallas, condiciones de desbloqueo, asignación/revocación manual |
| **Premios** | Categorías de premios, CRUD, otorgar o revocar a usuarios |

### Administración

| Sección | Qué permite |
|---------|-------------|
| **Configuración** | Identidad del sitio, logo, mensaje de bienvenida, límites diarios, comisiones, toggles (registro, mantenimiento, propinas, uploads) |
| **SEO** | Meta tags, Open Graph, IndexNow, verificación Google/Bing (dentro de Configuración) |
| **Rangos** | CRUD de rangos con permisos de post, votos, propinas y pestañas admin para staff |
| **Reglas de karma** | Puntos por acción: posts, comentarios, reacciones, Vidu, propinas, hitos de seguidores… |
| **Propinas / Monedas** | Analítica del monedero, historial de transacciones, ajustar comisión, depositar monedas |
| **Temas** | Sincronizar carpetas, crear combinaciones de color, marcar default o exclusivo Creator Plus |
| **Paquetes de iconos** | Sincronizar packs desde disco, explorar biblioteca de iconos |
| **Publicidad Vidu** | Creativos de vídeo, banners laterales, reglas por vídeo o masivas, umbrales de inserción |

Los **moderadores** acceden por defecto a dashboard, usuarios, posts, moderación, categorías, palabras, tickets, verificaciones, medallas y premios. Las secciones de administración avanzada quedan reservadas a admins globales.

---

## Herramientas internas y despliegue

### Comandos Artisan (`gofio:*`)

| Comando | Función | Programado |
|---------|---------|------------|
| `gofio:themes:sync` | Registra temas desde `themes/` | Manual / deploy |
| `gofio:icon-packs:sync` | Registra icon packs desde `icon-packs/` | Manual / deploy |
| `gofio:flush-post-views` | Persiste visitas de posts desde caché | Cada minuto |
| `gofio:prune-notifications` | Elimina notificaciones > 15 días | Diario |
| `gofio:expire-subscriptions` | Expira suscripciones Creator Plus vencidas | Diario |
| `gofio:seo:sitemap` | Regenera sitemap XML y caché RSS | Cada hora |

### Scripts Composer

| Script | Uso |
|--------|-----|
| `composer setup` | Instalación completa en local |
| `composer dev` | Servidor + cola + logs + Vite en paralelo |
| `composer gofio:sync` | Enlace storage + sync temas + iconos |
| `composer deploy` | Primer despliegue en producción |
| `composer deploy:update` | Actualización tras `git pull` |
| `composer test` | Suite PHPUnit |

### Colas y tiempo real

**Jobs en cola:** notificaciones, gamificación, avisos a seguidores, popularidad de posts, ping IndexNow.

**WebSocket (Reverb):** chat, propinas y menciones. Canales privados por usuario y por conversación.

**Procesos en producción:** Supervisor para workers de cola (`deploy/supervisor-gofio-worker.conf`) y Reverb (`deploy/supervisor-gofio-reverb.conf`).

### Importación de iconos Font Awesome

```bash
pnpm run import:fa-gemas          # Classic → pack Gemas
pnpm run import:fa-graphite-gemas # Estilo Graphite
pnpm run import:fa-slab-gemas     # Estilo Slab
php artisan gofio:icon-packs:sync # Registrar en BD
```

Documentación detallada en `themes/README.md`, `icon-packs/README.md` y `docs/SCALABILITY.md`.

---

## Stack técnico

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Vue 3, Inertia 3, Vite 7, Tailwind CSS 4 |
| Base de datos | MySQL 8 / MariaDB 10.6+ |
| Tiempo real | Laravel Reverb + Echo |
| Auth API | Laravel Sanctum |
| Producción | Redis (Predis), Nginx, PHP-FPM, Supervisor |
| Gestor JS | **pnpm** (no npm ni yarn) |

Arquitectura: **Controller → Service → Repository → Model**. Efectos secundarios pesados en Jobs. Comentarios técnicos en español en PHP, Vue y Blade.

---

## Instalación local

### Requisitos

PHP 8.2+ (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`), Composer 2.x, Node.js 20+, pnpm 9+, MySQL 8.0+.

### Pasos

```bash
git clone https://github.com/edinsonprogrammer/Gofio.git
cd Gofio
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate          # crear BD gofio en MySQL antes
php artisan storage:link
pnpm install && pnpm run build
php artisan gofio:themes:sync
php artisan gofio:icon-packs:sync
php artisan db:seed          # usuario admin de desarrollo
```

`composer setup` automatiza migrate, frontend y sync.

### Desarrollo

```bash
composer dev
# o en terminales separadas:
php artisan serve              # http://127.0.0.1:8000
php artisan queue:listen
php artisan reverb:start
pnpm run dev                   # HMR, puerto 5173
```

### Credenciales de desarrollo (tras `db:seed`)

| Campo | Valor |
|-------|-------|
| Usuario / email | `admin` / `admin@gofio.test` |
| Contraseña | `password` |

### Variables de entorno

Copia `.env.example` a `.env`. Mínimo: `APP_KEY`, `APP_URL`, `DB_*`, `REVERB_*` y `VITE_REVERB_*`.

Opcionales: `GIPHY_API_KEY`, `GOFIO_PLATFORM_FEE`, `GOFIO_CREATOR_PLUS_PRICE`.

Producción recomendada:

```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

Tras cambios en `resources/js/` o `resources/css/`, ejecuta `pnpm run build`.

---

## Despliegue en producción

### Servidor

Ubuntu 22.04/24.04, Nginx + PHP-FPM 8.2+, MySQL 8+, Redis 7+, Supervisor, TLS (Let's Encrypt).

### Despliegue inicial

```bash
composer deploy
# o: bash scripts/deploy-production.sh --first-run
```

### Actualizar

```bash
git pull origin main
composer deploy:update
sudo supervisorctl restart gofio-worker:* gofio-reverb
```

### Nginx (extracto)

```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/gofio/public;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_pass http://127.0.0.1:8080;
    }
}
```

TLS: `sudo certbot --nginx -d tudominio.com`

Cron: `* * * * * cd /var/www/gofio && php artisan schedule:run >> /dev/null 2>&1`

Plantillas Supervisor en `deploy/`. Permisos: `storage/` y `bootstrap/cache/` escribibles por `www-data`.

---

## Próximamente

- **Canales** y **Mix** (módulos anunciados en el carrusel del inicio)
- Pasarela de pago con tarjeta para Creator Plus
- Denunciar usuario desde el perfil (API lista, UI pendiente)

---

## Licencia

MIT — Copyright (c) 2026 **EdsonDev**. Ver [LICENSE](LICENSE).
