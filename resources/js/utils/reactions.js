/**
 * Catálogo de reacciones disponibles en publicaciones y utilidades
 * para consultar y ordenar el resumen de conteos por tipo.
 */

/** Definición de las cinco reacciones con clave, etiqueta e icono Font Awesome. */
export const REACTIONS = [
    { key: 'like', label: 'Me gusta', icon: 'fa-solid fa-thumbs-up' },
    { key: 'excelente', label: 'Excelente', icon: 'fa-solid fa-trophy' },
    { key: 'lindo', label: 'Lindo', icon: 'fa-solid fa-heart' },
    { key: 'desacuerdo', label: 'Desacuerdo', icon: 'fa-solid fa-thumbs-down' },
    { key: 'asombroso', label: 'Asombroso', icon: 'fa-solid fa-bolt' },
];

/**
 * Busca la definición completa de una reacción por su clave.
 * @param {string} key - Identificador de la reacción.
 * @returns {object|null} Objeto de reacción o null si no existe.
 */
export function reactionByKey(key) {
    return REACTIONS.find((r) => r.key === key) ?? null;
}

/**
 * Devuelve las reacciones con mayor conteo para mostrar en la barra resumida.
 * @param {object} summary - Mapa clave → cantidad de reacciones.
 * @param {number} limit - Máximo de reacciones a incluir.
 * @returns {object[]} Reacciones ordenadas por popularidad.
 */
export function topReactions(summary, limit = 3) {
    return REACTIONS
        .filter((r) => (summary?.[r.key] ?? 0) > 0)
        .sort((a, b) => (summary[b.key] ?? 0) - (summary[a.key] ?? 0))
        .slice(0, limit);
}

