/**
 * Resolución de iconos personalizados del sistema de paquetes de Gofio
 * y generación de etiquetas legibles para la interfaz.
 */

/** Prefijo que identifica un icono proveniente de un paquete subido. */
const PACK_PREFIX = 'pack:';

/**
 * Indica si el valor representa un icono de paquete (formato pack:ruta).
 * @param {string} value - Identificador del icono.
 * @returns {boolean} true si es un icono de paquete.
 */
export function isPackIcon(value) {
    return typeof value === 'string' && value.startsWith(PACK_PREFIX);
}

/**
 * Construye la URL pública del asset de un icono de paquete validando la ruta.
 * @param {string} value - Identificador pack:paquete/archivo.svg.
 * @returns {string|null} URL del asset o null si la ruta es inválida.
 */
export function packIconUrl(value) {
    if (! isPackIcon(value)) {
        return null;
    }

    const path = value.slice(PACK_PREFIX.length).replace(/^\/+/, '');

    if (! path || path.includes('..')) {
        return null;
    }

    const slash = path.indexOf('/');

    if (slash <= 0) {
        return null;
    }

    const pack = path.slice(0, slash);
    const file = path.slice(slash + 1);

    if (! pack || ! file) {
        return null;
    }

    return `/icon-packs/assets/${pack}/${file.split('/').map((part) => encodeURIComponent(part)).join('/')}`;
}

/**
 * Obtiene una etiqueta legible para mostrar en selectores y listados de iconos.
 * @param {string} value - Identificador Font Awesome o de paquete.
 * @returns {string} Nombre amigable del icono.
 */
export function iconLabel(value) {
    if (! value) {
        return '';
    }

    if (isPackIcon(value)) {
        const file = value.split('/').pop() ?? value;

        return file.replace(/\.[^.]+$/, '');
    }

    const parts = value.trim().split(/\s+/);
    const name = parts[parts.length - 1] ?? value;

    return name.replace(/^fa-/, '').replace(/-/g, ' ');
}

