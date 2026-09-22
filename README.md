# Gofio

Gofio es una red social en español: feed de publicaciones, vídeo corto (Vidu), chat privado en tiempo real, monedero virtual con propinas, gamificación por karma y un panel de administración. Está construida con Laravel 12, Inertia.js y Vue 3 — un monolito PHP con la sensación de una SPA moderna.

El backend sigue Controller → Service → Repository → Model; los efectos secundarios pesados van a Jobs en cola. Reverb lleva chat, propinas y notificaciones por WebSocket.

## Stack

PHP 8.2+ y Laravel 12. Vue 3, Inertia 3, Vite 7 y Tailwind CSS 4. MySQL, Laravel Reverb + Echo, Sanctum. En producción: Redis con Predis para caché, sesiones y colas. Frontend con pnpm (no npm ni yarn). Comentarios técnicos en español en PHP, Vue y Blade.

## Requisitos

Desarrollo: PHP 8.2+ (`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`), Composer 2.x, Node.js 20+, pnpm 9+, MySQL 8.0+ o MariaDB 10.6+.

Producción: Ubuntu 22.04/24.04, Nginx + PHP-FPM 8.2+, MySQL 8+, Redis 7+, Supervisor para workers y Reverb, TLS (Let's Encrypt).

## Instalación local

```bash
git clone https://github.com/edinsonprogrammer/Gofio.git
cd gofio
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate          # crear BD gofio en MySQL antes
php artisan storage:link
pnpm install && pnpm run build
php artisan gofio:themes:sync
php artisan gofio:icon-packs:sync
```

`composer setup` automatiza migrate, frontend y sync. Para desarrollo: `composer dev` (servidor, cola, Reverb y Vite juntos), o en terminales separadas:

```bash
php artisan serve              # http://127.0.0.1:8000
php artisan queue:listen
php artisan reverb:start
pnpm run dev                   # HMR, puerto 5173
```

## Variables de entorno

Copia `.env.example` a `.env`. Mínimo: `APP_KEY`, `APP_URL`, `DB_*`, `REVERB_*` y `VITE_REVERB_*`. Si falta Reverb: `php artisan reverb:install`.

Opcionales: `GIPHY_API_KEY`, `GOFIO_PLATFORM_FEE` (comisión propinas %), `GOFIO_CREATOR_PLUS_PRICE`. En producción:

```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
```

## Comandos de desarrollo

```bash
pnpm install && pnpm run dev    # o pnpm run build → public/build/
php artisan gofio:themes:sync
php artisan gofio:icon-packs:sync
php artisan gofio:flush-post-views
php artisan gofio:prune-notifications
php artisan gofio:expire-subscriptions
php artisan gofio:seo:sitemap
composer test
```

Tras cambios en `resources/js/` o `resources/css/`, ejecuta `pnpm run build`.

## Despliegue en VPS

### Servidor y código

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install nginx mysql-server redis-server php8.2-fpm php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath \
  php8.2-redis composer git -y
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
corepack enable && corepack prepare pnpm@latest --activate

cd /var/www && sudo git clone https://github.com/edinsonprogrammer/Gofio.git
cd gofio && sudo chown -R www-data:www-data /var/www/gofio
cp .env.example .env && nano .env && php artisan key:generate
```

Despliegue inicial — elige una vía:

```bash
composer deploy
# o: bash scripts/deploy-production.sh --first-run
# o manual:
# composer install --no-dev --optimize-autoloader
# pnpm install --frozen-lockfile && pnpm run build
# php artisan migrate --force && composer gofio:sync && composer optimize:prod
```

Scripts Composer: `setup` (local con dev), `deploy` (primer despliegue), `deploy:update` (tras `git pull`), `gofio:sync` (storage + temas + iconos), `optimize:prod` (cachés).

### Nginx

```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/gofio/public;
    index index.php;
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
    # Proxy Reverb (ajusta puerto según .env)
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

### Workers, cron y permisos

Plantillas en `deploy/supervisor-gofio-worker.conf` y `deploy/supervisor-gofio-reverb.conf` → `/etc/supervisor/conf.d/`. Luego `sudo supervisorctl reread && sudo supervisorctl update`.

Cron (`www-data`): `* * * * * cd /var/www/gofio && php artisan schedule:run >> /dev/null 2>&1`

Permisos: `sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 775 storage bootstrap/cache`

### Actualizar

```bash
cd /var/www/gofio && git pull origin main
composer deploy:update
sudo supervisorctl restart gofio-worker:* gofio-reverb
```

Equivalente: `bash scripts/deploy-production.sh`

## Colas y scheduler

Colas: notificaciones, gamificación, avisos a seguidores. En `routes/console.php`: `gofio:flush-post-views` (cada minuto), `gofio:seo:sitemap` (cada hora), `gofio:expire-subscriptions` y `gofio:prune-notifications` (diario).

## Temas, iconos y estructura

Temas en `themes/` (`theme.json`) → `php artisan gofio:themes:sync`. Icon packs en `icon-packs/` → `php artisan gofio:icon-packs:sync`. Ver `themes/README.md` e `icon-packs/README.md`. FA Gemas: `pnpm run import:fa-gemas`.

```
app/Http/Controllers/  app/Services/  app/Jobs/
resources/js/            routes/        themes/  icon-packs/
deploy/                  scripts/       docs/SCALABILITY.md
```

## Licencia

MIT — ver [LICENSE](LICENSE).
