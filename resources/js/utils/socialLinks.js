/**
 * Construcción de URLs y etiquetas de visualización para redes sociales
 * del perfil de usuario (WhatsApp, Instagram, Facebook, X).
 */

/**
 * Genera el enlace de WhatsApp a partir de un número telefónico.
 * @param {string|null} phone - Número con o sin formato.
 * @returns {string|null} URL wa.me o null si no hay dígitos válidos.
 */
export function whatsappUrl(phone) {
    if (!phone) {
        return null;
    }

    const digits = String(phone).replace(/\D/g, '');
    return digits ? `https://wa.me/${digits}` : null;
}

/**
 * Genera la URL del perfil de Instagram eliminando el prefijo @.
 * @param {string|null} handle - Usuario de Instagram.
 * @returns {string|null} URL del perfil o null si está vacío.
 */
export function instagramUrl(handle) {
    if (!handle) {
        return null;
    }

    const clean = String(handle).replace(/^@/, '').trim();
    return clean ? `https://instagram.com/${clean}` : null;
}

/**
 * Genera la URL del perfil de Facebook a partir del identificador o usuario.
 * @param {string|null} handle - Usuario o slug de Facebook.
 * @returns {string|null} URL del perfil o null si está vacío.
 */
export function facebookUrl(handle) {
    if (!handle) {
        return null;
    }

    const clean = String(handle).replace(/^@/, '').trim();
    return clean ? `https://facebook.com/${clean}` : null;
}

/**
 * Genera la URL del perfil de X (Twitter) a partir del handle.
 * @param {string|null} handle - Usuario de X.
 * @returns {string|null} URL del perfil o null si está vacío.
 */
export function xUrl(handle) {
    if (!handle) {
        return null;
    }

    const clean = String(handle).replace(/^@/, '').trim();
    return clean ? `https://x.com/${clean}` : null;
}

/**
 * Normaliza un handle para mostrarlo siempre con prefijo @.
 * @param {string|null} handle - Nombre de usuario sin formato.
 * @returns {string} Handle con @ o cadena vacía.
 */
export function displayHandle(handle) {
    if (!handle) {
        return '';
    }

    return handle.startsWith('@') ? handle : `@${handle}`;
}

