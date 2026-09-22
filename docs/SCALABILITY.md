# Escalabilidad Gofio — 8M registrados / 1–3M concurrentes

## Respuesta directa

| Escenario | ¿Lo soporta este código solo? |
|-----------|-------------------------------|
| **8 millones de usuarios registrados** en MySQL | **Sí**, con índices, paginación y contadores denormalizados (ya aplicados). |
| **1–3 millones conectados a la vez** en un solo nodo PHP | **No.** Eso es escala de red social global. |
| **1–3 millones concurrentes** con cluster + Redis + colas + balanceador | **Posible** como plataforma distribuida, no como “un script en un VPS”. |

**8M en base de datos ≠ 3M en simultáneo.** La base aguanta filas; el cuello de botella es **peticiones por segundo** (likes, feed, chat, posts).

---

## Qué optimizamos en código (fase 2)

### Escrituras rápidas (respuesta HTTP inmediata)
- **Posts:** gamificación en cola; notificaciones a seguidores en `NotifyFollowersJob` (chunks de 500).
- **Comentarios:** notificación + medallas/rangos en cola tras guardar el comentario.
- **Reacciones/likes:** reacción síncrona (UI instantánea); notificación y karma de popularidad en cola.
- **Chat:** mensaje guardado al instante; notificación push en cola; WebSocket en cola (`ShouldBroadcast`).
- **Propinas:** transferencia de monedas **síncrona** (integridad); notificación en cola.

### Lecturas rápidas
- **Feed:** no carga `content` (longText) — solo metadatos + preview desde título.
- **Visitas:** buffer en cache (`PostViewBufferService`), flush cada minuto — evita un UPDATE por vista.
- **Medallas automáticas:** lista de medallas activas cacheada 5 min.
- **Config del sitio:** cache 5 min.
- **Badge notificaciones:** cache 30 s.
- **Seguidores:** `followers_count` / `following_count` en `users` (sin COUNT en caliente).

### Límites defensivos
- Notificaciones panel: 50
- Comentarios: 50 hilos + 30 respuestas
- Chat contactos: 150; conversaciones: 80
- Mensajes por conversación: 50

### Índices DB
- Feed, follows, comments, votes, messages, posts por usuario/fecha

### Infraestructura requerida (producción)

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
APP_DEBUG=false
```

Procesos:

```bash
php artisan migrate
php artisan queue:work --tries=3 --max-jobs=1000
php artisan schedule:work   # flush visitas, prune notificaciones
php artisan reverb:start    # chat tiempo real
```

Para **cientos de miles+ concurrentes** además necesitas:
- 4–20+ servidores PHP (Octane o PHP-FPM) detrás de load balancer
- MySQL primary + réplicas de lectura
- Redis Cluster
- CDN (Cloudflare) para JS/CSS
- Meilisearch/Elasticsearch para búsqueda (el `LIKE` actual no escala)
- Reverb/Pusher con scaling habilitado

---

## Capacidad orientativa

| Infraestructura | Concurrentes razonables |
|-----------------|-------------------------|
| XAMPP local | 10–50 |
| 1 VPS 4 GB sin Redis | 500–2.000 |
| 1 VPS 8 GB + Redis + 4 workers | 5.000–20.000 |
| Cluster 10 nodos + Redis + DB réplicas | 100.000–500.000+ |

**1–3M concurrentes** implica presupuesto y equipo de infraestructura de producto grande, no solo optimizar PHP.

---

## Verificación local

```bash
php artisan migrate
php artisan queue:work
php artisan schedule:work
```

Prueba: publicar post, comentar, reaccionar, chatear, enviar propina — la UI debe responder al instante; notificaciones/medallas llegan en segundos vía cola.
