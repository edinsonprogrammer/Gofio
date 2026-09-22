# Fuente Graphite (Font Awesome Pro+)

Coloca aquí los SVG **thin** del pack [Graphite](https://fontawesome.com/icons/packs/graphite) antes de importar.

## Cómo obtener los archivos

1. Entra en [fontawesome.com/kits](https://fontawesome.com/kits) con una cuenta **Pro+**.
2. Crea o edita un Kit y activa el pack **Graphite · Thin**.
3. Descarga el Kit para escritorio (**Download Kit**) o instálalo vía npm:
   - En `.env`: `FONTAWESOME_PACKAGE_TOKEN` y `FONTAWESOME_KIT_ID`
   - `npm run install:fa-kit`
4. Copia los `.svg` de Graphite thin a esta carpeta **o** ejecuta directamente:
   ```bash
   npm run import:fa-graphite-gemas
   ```

Tras importar, los iconos quedarán en `icon-packs/gemas/graphite/thin/` con nombres en español y aparecerán en el admin bajo **Graphite · Thin**.
