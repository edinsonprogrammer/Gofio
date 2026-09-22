/**

 * Normalización de clases Font Awesome para garantizar el estilo correcto

 * (solid, regular o brands) según el nombre del icono.

 */



/** Iconos que deben renderizarse en variante regular cuando no se indica estilo. */

const REGULAR_PREFERRED = new Set([

    'fa-eye',

    'fa-comment',

    'fa-comment-slash',

    'fa-thumbs-up',

    'fa-flag',

    'fa-bookmark',

    'fa-newspaper',

    'fa-face-smile',

]);



/** Iconos del panel admin y navegación que siempre usan variante sólida. */

const SOLID_ONLY = new Set([

    'fa-tv',

    'fa-microchip',

    'fa-futbol',

    'fa-gamepad',

    'fa-coins',

    'fa-user-group',

    'fa-gauge-high',

    'fa-shield-halved',

    'fa-palette',

    'fa-folder',

    'fa-folder-open',

    'fa-ban',

    'fa-life-ring',

    'fa-id-card',

    'fa-users',

    'fa-newspaper',

    'fa-gear',

    'fa-award',

    'fa-medal',

    'fa-gem',

    'fa-seedling',

    'fa-star',

    'fa-star-half-stroke',

    'fa-crown',

    'fa-face-laugh',

    'fa-book',

    'fa-heart',

    'fa-music',

    'fa-fire',

    'fa-bolt',

    'fa-user-plus',

    'fa-user-check',

    'fa-user-pen',

    'fa-trophy',

    'fa-pen-nib',

    'fa-comments',

    'fa-hand-sparkles',

    'fa-handshake',

    'fa-ranking-star',

    'fa-house',

    'fa-magnifying-glass',

    'fa-comment-dots',

    'fa-right-from-bracket',

    'fa-triangle-exclamation',

    'fa-lightbulb',

    'fa-minus',

    'fa-xmark',

    'fa-paper-plane',

    'fa-plus',

    'fa-arrow-left',

    'fa-thumbs-up',

    'fa-thumbs-down',

    'fa-icons',

    'fa-rectangle-ad',

    'fa-image',

    'fa-film',

    'fa-sliders',

    'fa-cloud-arrow-up',

    'fa-spinner',

    'fa-eye-slash',

    'fa-trash',

    'fa-circle-play',

    'fa-circle-pause',

    'fa-play',

    'fa-pause',

    'fa-floppy-disk',

    'fa-circle-check',

    'fa-circle-xmark',

    'fa-rotate-left',

    'fa-rotate',

    'fa-chevron-up',

    'fa-chevron-down',

    'fa-chevron-left',

    'fa-chevron-right',

]);



/**

 * Convierte un identificador de icono en un array de clases FA completas.

 * @param {string} icon - Nombre o clases del icono.

 * @param {'auto'|'solid'|'regular'} variant - Variante forzada o detección automática.

 * @returns {string[]} Clases Font Awesome listas para aplicar.

 */

export function normalizeFaIcon(icon, variant = 'auto') {

    if (! icon) {

        return ['fa-solid', 'fa-circle'];

    }



    const parts = icon.trim().split(/\s+/).filter(Boolean);



    // Respeta clases FA ya completas (fa-solid fa-star, fa-brands fa-github…)

    if (parts.some((part) => ['fa-solid', 'fa-regular', 'fa-brands', 'fa-light', 'fa-thin'].includes(part))) {

        return parts;

    }



    // Compatibilidad con sintaxis antigua "fa fa-star" → fa-solid fa-star

    const normalizedParts = parts[0] === 'fa' && parts[1]?.startsWith('fa-')

        ? [parts[1]]

        : parts;



    const name = normalizedParts[0].startsWith('fa-')

        ? normalizedParts[0]

        : `fa-${normalizedParts[0]}`;



    if (variant === 'solid') {

        return ['fa-solid', name];

    }



    if (variant === 'regular') {

        return ['fa-regular', name];

    }



    if (REGULAR_PREFERRED.has(name)) {

        return ['fa-regular', name];

    }



    if (SOLID_ONLY.has(name)) {

        return ['fa-solid', name];

    }



    // Font Awesome 6 free: la mayoría de iconos solo existen en sólido

    return ['fa-solid', name];

}



/**

 * Genera la cadena de clases CSS para un elemento <i> de Font Awesome.

 * @param {string} icon - Identificador del icono.

 * @param {'auto'|'solid'|'regular'} variant - Variante de estilo.

 * @param {string} extra - Clases adicionales opcionales.

 * @returns {string} Clases concatenadas.

 */

export function faIconClass(icon, variant = 'auto', extra = '') {

    return [...normalizeFaIcon(icon, variant), extra].filter(Boolean).join(' ');

}


