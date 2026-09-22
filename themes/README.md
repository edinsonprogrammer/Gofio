# Sistema de Temas de Gofio

Este directorio es el motor de temas de Gofio. Cualquier persona puede cambiar
**toda la apariencia y estructura de la aplicación** copiando una carpeta aquí,
sin tocar código Vue ni PHP y sin compilar nada.

Los temas controlan:

- **Colores** — paleta completa (marca, superficie, bordes, fondo, estados)
- **Tipografía** — familia de fuente, tamaño base, interlineado
- **Forma** — radios de borde en escala (sm, md, lg, xl)
- **Sombras** — tarjeta, elevado, topbar
- **Estructura de página** — 5 variantes de layout (ver abajo)
- **Logo de marca** — reemplaza el wordmark "Gofio!" con una imagen
- **Iconos de rangos y medallas** — vía `icon_pack` ligado a un paquete

---

## Instalar un tema

1. Copia la carpeta del tema dentro de `themes/` (junto a este README).
2. Entra al panel de administración → **Temas / Apariencias**.
3. Pulsa **«Sincronizar carpetas»** (o espera: se sincroniza automáticamente al abrir esa página).
4. El tema aparece en la lista y en *Configuración → Apariencia* para que los usuarios lo seleccionen.

También puedes sincronizar por consola (útil en despliegues):

```bash
php artisan gofio:themes:sync
```

---

## Estructura de una carpeta de tema

```
themes/
  mi-tema/
    theme.json      (obligatorio)
    custom.css      (opcional — CSS adicional)
    preview.png     (opcional — captura para el diseñador)
    assets/
      logo.png      (opcional — logo personalizado)
      bg.jpg        (opcional — imagen de fondo)
```

---

## `theme.json` — referencia completa

```json
{
    "name": "Mi Tema",
    "slug": "mi-tema",
    "description": "Una descripción corta.",
    "author": "EdsonDev",
    "version": "1.0.0",
    "is_default": false,
    "requires_creator_plus": false,
    "layout_variant": "default",
    "logo_url": "/themes/mi-tema/assets/logo.png",
    "icon_pack": "gemas",
    "variables": {
        ...
    }
}
```

### Campos de metadatos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `name` | string | Nombre visible en el panel admin |
| `slug` | string | ID único (solo minúsculas, números y guiones) |
| `description` | string | Descripción corta |
| `author` | string | Autor del tema |
| `version` | string | Versión (ej. `"1.0.0"`) |
| `is_default` | bool | Tema activo para nuevos usuarios (solo aplica al crear, no al resincronizar) |
| `requires_creator_plus` | bool | Restringir a usuarios Creator Plus |
| `layout_variant` | string | Variante de estructura de página (ver abajo) |
| `logo_url` | string | URL del logo; puede ser ruta relativa o absoluta |
| `icon_pack` | string | Slug de un paquete instalado en `icon-packs/` |

---

## Variantes de layout (`layout_variant`)

Esta es la característica más potente. Controla la **estructura completa de la página**:

| Valor | Descripción | Similar a |
|-------|-------------|-----------|
| `"default"` | Topbar horizontal + sidebar izquierdo + contenido + sidebar derecho | Facebook, Taringa |
| `"sidebar-nav"` | Navegación vertical fija a la izquierda, contenido a la derecha | Twitter/X, Discord |
| `"minimal"` | Solo topbar y columna de contenido centrada, sin sidebars | Medium, Substack |
| `"compact"` | Espaciado reducido, alta densidad de información | Reddit, Hacker News |
| `"panel"` | Panel lateral fijo izquierdo + área de contenido principal | WhatsApp, Slack |

### Ejemplo: tema tipo Twitter/X

```json
{
    "name": "Mi Red Oscura",
    "slug": "mi-red-oscura",
    "layout_variant": "sidebar-nav",
    "variables": {
        "--bg-principal": "#15202B",
        "--color-brand":  "#1D9BF0",
        ...
    }
}
```

Al sincronizar, la aplicación cambia su estructura de página automáticamente.

### Personalizar más con `custom.css`

Cada variante aplica estilos base, pero puedes ajustar cualquier detalle con `custom.css`:

```css
/* Ejemplo: sidebar-nav más estrecho */
[data-gofio-layout="sidebar-nav"] .gofio-app-shell {
    padding-left: 200px;
}

/* Ejemplo: fuente personalizada via Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
body { font-family: 'Poppins', sans-serif; }
```

---

## Variables CSS disponibles

### Colores principales

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--bg-principal` | `#F1F5F9` | Fondo de página |
| `--text-principal` | `#0F172A` | Texto base |
| `--color-brand` | `#0D9488` | Color de marca (botones, links) |
| `--color-brand-hover` | `#0F766E` | Hover del color de marca |
| `--color-surface` | `#FFFFFF` | Fondo de tarjetas |
| `--color-border` | `#CBD5E1` | Bordes |
| `--color-muted` | `#64748B` | Texto secundario |
| `--color-link` | `#0F766E` | Color de links |
| `--color-panel` | `#F0FDFA` | Cabeceras de cajas |
| `--color-accent` | `#2DD4BF` | Color de acento |
| `--color-accent-soft` | `#CCFBF1` | Versión suave del acento |

### Topbar / barra superior

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--color-topbar-dark` | `#115E59` | Fondo topbar (modo solid) |
| `--color-topbar-gradient-from` | `#0F766E` | Gradiente topbar — inicio |
| `--color-topbar-gradient-to` | `#14B8A6` | Gradiente topbar — fin |

### Botón secundario

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--color-btn-secondary-bg` | `#F0FDFA` | Fondo |
| `--color-btn-secondary-border` | `#99F6E4` | Borde |
| `--color-btn-secondary-text` | `#134E4A` | Texto |

### Colores de estado

| Variable | Por defecto |
|----------|-------------|
| `--color-success` | `#16A34A` |
| `--color-success-soft` | `#DCFCE7` |
| `--color-error` | `#DC2626` |
| `--color-error-soft` | `#FEE2E2` |
| `--color-warning` | `#D97706` |
| `--color-warning-soft` | `#FEF3C7` |

### Tipografía

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--font-sans` | `'Inter', system-ui, sans-serif` | Fuente global |
| `--font-size-base` | `15px` | Tamaño base de texto |
| `--font-weight-normal` | `400` | Peso normal |
| `--font-weight-semibold` | `600` | Peso semibold |
| `--line-height-base` | `1.55` | Interlineado |

### Forma y radio

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--radius-sm` | `4px` | Radio pequeño |
| `--radius-md` | `8px` | Radio medio |
| `--radius-lg` | `12px` | Radio grande |
| `--radius-xl` | `16px` | Radio extra grande |
| `--radius-full` | `9999px` | Círculo/píldora |

### Sombras

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--shadow-card` | `0 1px 3px rgba(0,0,0,0.08)` | Tarjetas |
| `--shadow-elevated` | `0 4px 14px rgba(0,0,0,0.10)` | Modales, popovers |
| `--shadow-topbar` | `0 2px 8px rgba(0,0,0,0.15)` | Barra superior |

### Estructura

| Variable | Por defecto | Uso |
|----------|-------------|-----|
| `--topbar-height` | `52px` | Altura de la barra superior |
| `--sidebar-left-width` | `200px` | Ancho sidebar izquierdo |
| `--sidebar-right-width` | `240px` | Ancho sidebar derecho |
| `--sidenav-width` | `240px` | Ancho nav lateral (sidebar-nav / panel) |
| `--content-max-width` | `1152px` | Ancho máximo del contenido |
| `--content-gap` | `1rem` | Espaciado entre columnas |

---

## Temas incluidos

| Carpeta | Layout | Descripción |
|---------|--------|-------------|
| `indigo-corporativo` | `default` | Azul índigo profesional |
| `twitter-dark` | `sidebar-nav` | Oscuro con nav lateral tipo Twitter/X |
| `minimal-clean` | `minimal` | Limpio tipo blog/Medium, serif |
| `compact-pro` | `compact` | Alta densidad tipo Hacker News |

---

## Notas importantes

- **Diseño vs. administración**: Solo se actualizan `name`, `description`, `author`, `version`,
  `variables`, `custom_css`, `layout_variant` y `logo_url` en cada sincronización.
  Los interruptores administrativos (activo / predeterminado / requiere Creator Plus)
  se controlan desde el panel y **no se sobreescriben** al resincronizar.

- **Para desinstalar un tema**: bórralo desde el panel admin. Borrar la carpeta sin
  eliminarlo del panel no lo quita de la base de datos.

- **Crear un tema completamente nuevo**: basta con crear una carpeta nueva, editar
  `theme.json` con el nuevo `slug`, y sincronizar desde el panel.
