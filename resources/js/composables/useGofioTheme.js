/**
 * Aplicación dinámica de temas visuales de Gofio mediante variables CSS,
 * hoja de estilos personalizada, variante de layout y logo de marca.
 * El tema se aplica al elemento raíz <html> para que el CSS pueda usar
 * selectores de atributo [data-gofio-layout] y [data-gofio-theme].
 */

/** Identificador del elemento <style> que almacena CSS personalizado del tema activo. */
const CUSTOM_STYLE_ID = 'gofio-theme-custom-css';

/** Variantes de layout reconocidas. */
const VALID_LAYOUTS = ['default', 'sidebar-nav', 'minimal', 'compact', 'panel'];

/**
 * Aplica variables CSS, layout variant, logo y estilos personalizados al documento.
 * @param {object} variables - Mapa de propiedades CSS (--*).
 * @param {string} slug - Identificador del tema para el atributo data-gofio-theme.
 * @param {string|null} customCss - Reglas CSS adicionales del tema.
 * @param {string} layoutVariant - Variante estructural: default | sidebar-nav | minimal | compact | panel.
 * @param {string|null} logoUrl - URL del logo personalizado del tema o null para usar el wordmark.
 */
export function applyGofioTheme(variables, slug, customCss, layoutVariant = 'default', logoUrl = null) {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    // Aplica variables CSS al elemento raíz
    if (variables) {
        Object.entries(variables).forEach(([key, value]) => {
            root.style.setProperty(key, value);
        });
    }

    // Marca el tema activo como atributo de datos en <html>
    if (slug) {
        root.dataset.gofioTheme = slug;
    }

    // Aplica la variante de layout como atributo en <html> para que el CSS pueda reaccionar
    const safeLayout = VALID_LAYOUTS.includes(layoutVariant) ? layoutVariant : 'default';
    root.dataset.gofioLayout = safeLayout;

    // Expone el logo del tema como variable CSS para que SiteBrand lo consuma sin props extra
    if (logoUrl) {
        root.style.setProperty('--theme-logo-url', `url("${logoUrl}")`);
        root.dataset.gofioLogo = logoUrl;
    } else {
        root.style.removeProperty('--theme-logo-url');
        delete root.dataset.gofioLogo;
    }

    applyCustomCss(customCss);
}

/**
 * Inserta o elimina la hoja de estilos personalizada del tema en <head>.
 * @param {string|null} customCss - CSS del tema o null para quitar estilos previos.
 */
function applyCustomCss(customCss) {
    let styleTag = document.getElementById(CUSTOM_STYLE_ID);

    if (!customCss) {
        styleTag?.remove();
        return;
    }

    if (!styleTag) {
        styleTag = document.createElement('style');
        styleTag.id = CUSTOM_STYLE_ID;
        document.head.appendChild(styleTag);
    }

    styleTag.textContent = customCss;
}
