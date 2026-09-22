# Paquetes de iconos para rangos y medallas

Igual que `themes/`, esta carpeta es "instala y listo": cualquier persona
puede crear un set alternativo de iconos para los rangos y medallas de
Gofio simplemente copiando una carpeta aquí, sin tocar código.

## Instalar un paquete

1. Copia la carpeta del paquete dentro de `icon-packs/`.
2. Entra a **Admin → Paquetes de iconos** y pulsa **«Sincronizar carpetas»**
   (o solo espera: se sincroniza también al abrir esa página).
3. Opcional: en **Admin → Temas / Skins**, edita un tema y selecciónalo en
   el campo **«Paquete de iconos»**. Mientras ese tema esté activo, todos
   los rangos y medallas usarán los iconos del paquete en vez de los
   iconos por defecto guardados en la base de datos.

También puedes sincronizar por consola:

```bash
php artisan gofio:icon-packs:sync
```

## Estructura de un paquete

```
icon-packs/
  mi-paquete/
    icon-pack.json   (obligatorio)
    classic/         (opcional — SVG con nombres en español)
      solid/
      regular/
```

### Paquete Gemas (Classic)

El paquete `gemas/` incluye **2274 iconos SVG** del pack [Font Awesome Classic Free](https://fontawesome.com/icons/packs/classic) con nombres en español (`casa.svg`, `corazon.svg`, `usuario.svg`, etc.) en:

- `icon-packs/gemas/classic/solid/` — 2001 iconos
- `icon-packs/gemas/classic/regular/` — 273 iconos

Para regenerarlos tras actualizar Font Awesome:

```bash
npm run import:fa-gemas
```

Ver `icon-packs/gemas/ATTRIBUTION.md` para la licencia (CC BY 4.0 / OFL / MIT).

### Paquete Gemas (Graphite Pro+)

El pack [Font Awesome Graphite](https://fontawesome.com/icons/packs/graphite) (**thin**) requiere licencia **Pro+**. Tras obtener los SVG (Kit npm o descarga manual a `_sources/graphite/thin/`):

```bash
# Opcional: instalar Kit npm (FONTAWESOME_PACKAGE_TOKEN + FONTAWESOME_KIT_ID en .env)
npm run install:fa-kit

# Importar a icon-packs/gemas/graphite/thin/
npm run import:fa-graphite-gemas
```

Aparecen en el selector admin bajo **Graphite · Thin**.

### Paquete Gemas (Slab Pro+)

El pack [Font Awesome Slab](https://fontawesome.com/icons/packs/slab) (**regular**, press, duo…) también requiere licencia **Pro+**:

```bash
npm run install:fa-kit          # si usas Kit npm
npm run import:fa-slab-gemas    # importa variantes disponibles
# o ambos Pro+ de una vez:
npm run import:fa-proplus-gemas
```

Aparecen en el selector bajo **Slab · Regular**, **Slab Press · Regular**, etc.


### `icon-pack.json`

```json
{
    "name": "Mi Paquete",
    "slug": "mi-paquete",
    "description": "Una descripción corta.",
    "author": "EdsonDev",
    "version": "1.0.0",
    "ranks": {
        "administrador": "fa-solid fa-chess-king",
        "usuario": "fa-solid fa-star"
    },
    "medals": {
        "primer-paso": "fa-solid fa-shoe-prints"
    }
}
```

- Las claves de `ranks` y `medals` son los **slugs** de cada rango/medalla
  (visibles en `/admin/rangos` y `/admin/medallas`, junto al nombre).
- No es necesario mapear todos los rangos o medallas — los que no
  aparezcan en el paquete simplemente usan su icono por defecto.
- Los valores son clases completas de Font Awesome (ej.
  `fa-solid fa-crown`), igual que en los formularios de rangos/medallas.

## Por qué así

Un icono por rango/medalla ya vive en la base de datos (campo `icon`), así
que un paquete de iconos no lo reemplaza permanentemente: solo lo
**sobrescribe en el navegador** mientras el tema activo lo tenga vinculado.
Esto permite tener varios "estilos visuales" de iconos (uno por tema) sin
duplicar rangos ni medallas, y cambiar de uno a otro con un simple cambio
de tema.
