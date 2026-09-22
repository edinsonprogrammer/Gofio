/**
 * Sanitización y métricas de contenido enriquecido para publicaciones y comentarios.
 * Permite formato inline seguro, listas, alineación y estilos tipográficos controlados.
 */

/** Etiquetas inline clásicas permitidas. */
const INLINE_TAG_MAP = {
    B: 'b',
    STRONG: 'b',
    I: 'i',
    EM: 'i',
    U: 'u',
    S: 's',
    STRIKE: 's',
    DEL: 's',
};

/** Propiedades CSS permitidas en span/div/p. */
const ALLOWED_STYLE_PROPS = {
    color: /^#([0-9a-f]{3}|[0-9a-f]{6})$/i,
    'background-color': /^#([0-9a-f]{3}|[0-9a-f]{6})$/i,
    'font-family': /^[a-z0-9 ,'"\-]+$/i,
    'font-size': /^\d+(?:\.\d+)?(px|em|rem|%)$/i,
    'font-weight': /^(normal|bold|[1-9]00)$/i,
    'font-style': /^(normal|italic)$/i,
    'text-decoration': /^(none|underline|line-through)$/i,
    'text-align': /^(left|center|right|justify)$/i,
    'text-transform': /^(none|uppercase|lowercase|capitalize)$/i,
};

/** Fuentes seguras para el selector del compositor. */
export const COMPOSER_FONT_FAMILIES = [
    { label: 'Predeterminada', value: 'inherit' },
    { label: 'Inter / Sans', value: 'Inter, system-ui, sans-serif' },
    { label: 'Arial', value: 'Arial, Helvetica, sans-serif' },
    { label: 'Georgia', value: 'Georgia, serif' },
    { label: 'Times New Roman', value: '"Times New Roman", Times, serif' },
    { label: 'Courier New', value: '"Courier New", Courier, monospace' },
    { label: 'Comic Sans MS', value: '"Comic Sans MS", cursive, sans-serif' },
];

/** Tamaños tipográficos del compositor. */
export const COMPOSER_FONT_SIZES = [
    { label: 'Pequeño', value: '13px' },
    { label: 'Normal', value: '15px' },
    { label: 'Grande', value: '18px' },
    { label: 'Extra grande', value: '22px' },
];

/** Colores rápidos para texto y resalto. */
export const COMPOSER_PALETTE = [
    '#0F172A', '#64748B', '#DC2626', '#D97706', '#16A34A',
    '#0D9488', '#2563EB', '#7C3AED', '#DB2777', '#FFFFFF',
];

/**
 * Escapa caracteres HTML especiales en texto plano.
 */
function escapeHtml(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

/**
 * Filtra un atributo style dejando solo propiedades seguras.
 */
function sanitizeStyleAttribute(styleAttr) {
    if (! styleAttr) {
        return '';
    }

    const safe = [];

    styleAttr.split(';').forEach((chunk) => {
        const [rawKey, ...rawVal] = chunk.split(':');
        const key = rawKey?.trim().toLowerCase();
        const value = rawVal.join(':').trim();

        if (! key || ! value || ! ALLOWED_STYLE_PROPS[key]) {
            return;
        }

        if (ALLOWED_STYLE_PROPS[key].test(value)) {
            safe.push(`${key}:${value}`);
        }
    });

    return safe.join(';');
}

/**
 * Extrae etiquetas b/i/u/s a partir del style de un span legacy.
 */
function tagsFromSpanStyle(styleAttr) {
    const style = (styleAttr || '').toLowerCase();
    const tags = [];

    if (/font-weight:\s*(bold|bolder|[6-9]00)/.test(style)) {
        tags.push('b');
    }
    if (/font-style:\s*italic/.test(style)) {
        tags.push('i');
    }
    if (/text-decoration(?:-line)?:\s*[^;]*underline/.test(style)) {
        tags.push('u');
    }
    if (/text-decoration(?:-line)?:\s*[^;]*line-through/.test(style)) {
        tags.push('s');
    }

    return tags;
}

/**
 * Convierte etiqueta FONT legacy a span con estilos seguros.
 */
function fontTagToSpan(node) {
    const styles = [];
    const face = node.getAttribute('face');
    const color = node.getAttribute('color');
    const size = node.getAttribute('size');

    if (face) {
        styles.push(`font-family:${face}`);
    }
    if (color && /^#?[0-9a-f]{3,6}$/i.test(color)) {
        const hex = color.startsWith('#') ? color : `#${color}`;
        styles.push(`color:${hex}`);
    }

    const sizeMap = {
        1: '12px', 2: '13px', 3: '15px', 4: '18px', 5: '22px', 6: '26px', 7: '32px',
    };
    if (size && sizeMap[size]) {
        styles.push(`font-size:${sizeMap[size]}`);
    }

    return styles.join(';');
}

/**
 * Recorre el DOM y devuelve HTML sanitizado con formato enriquecido permitido.
 */
export function sanitizeRichHtml(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = html || '';

    const parts = [];

    const walk = (node) => {
        if (node.nodeType === Node.TEXT_NODE) {
            parts.push(escapeHtml(node.textContent));
            return;
        }

        if (node.nodeType !== Node.ELEMENT_NODE) {
            return;
        }

        const tag = node.tagName;

        if (tag === 'BR') {
            parts.push('<br>');
            return;
        }

        if (tag === 'FONT') {
            const style = sanitizeStyleAttribute(fontTagToSpan(node));
            if (style) {
                parts.push(`<span style="${style}">`);
            }
            node.childNodes.forEach(walk);
            if (style) {
                parts.push('</span>');
            }
            return;
        }

        if (tag === 'SPAN') {
            const style = sanitizeStyleAttribute(node.getAttribute('style'));
            const legacyTags = style ? [] : tagsFromSpanStyle(node.getAttribute('style'));
            legacyTags.forEach((t) => parts.push(`<${t}>`));
            if (style) {
                parts.push(`<span style="${style}">`);
            }
            node.childNodes.forEach(walk);
            if (style) {
                parts.push('</span>');
            }
            [...legacyTags].reverse().forEach((t) => parts.push(`</${t}>`));
            return;
        }

        if (tag === 'UL' || tag === 'OL') {
            parts.push(`<${tag.toLowerCase()}>`);
            node.childNodes.forEach(walk);
            parts.push(`</${tag.toLowerCase()}>`);
            return;
        }

        if (tag === 'LI') {
            parts.push('<li>');
            node.childNodes.forEach(walk);
            parts.push('</li>');
            return;
        }

        if (tag === 'SUP' || tag === 'SUB') {
            const lower = tag.toLowerCase();
            parts.push(`<${lower}>`);
            node.childNodes.forEach(walk);
            parts.push(`</${lower}>`);
            return;
        }

        const inline = INLINE_TAG_MAP[tag];
        const isBlock = tag === 'DIV' || tag === 'P';

        if (isBlock) {
            const alignStyle = sanitizeStyleAttribute(node.getAttribute('style'));
            const textAlign = (alignStyle.match(/text-align:(left|center|right|justify)/i) || [])[0];
            if (textAlign) {
                parts.push(`<div style="${textAlign}">`);
            } else {
                parts.push('<div>');
            }
            node.childNodes.forEach(walk);
            parts.push('</div>');
            return;
        }

        if (inline) {
            parts.push(`<${inline}>`);
        }

        node.childNodes.forEach(walk);

        if (inline) {
            parts.push(`</${inline}>`);
        }
    };

    wrapper.childNodes.forEach(walk);

    return parts
        .join('')
        .replace(/(<br>\s*){3,}/g, '<br><br>')
        .replace(/^(<br>\s*)+/, '')
        .replace(/(<br>\s*)+$/, '')
        .trim();
}

/**
 * Cuenta los caracteres de texto visible ignorando etiquetas HTML.
 */
export function plainTextLength(html) {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = (html || '').replace(/<br\s*\/?>/gi, '\n');

    return (wrapper.textContent || '').trim().length;
}

/**
 * Determina si el contenido enriquecido no contiene texto visible.
 */
export function isRichTextEmpty(html) {
    return plainTextLength(html) === 0;
}

/** Comandos de bloque que fallan si styleWithCSS está activo. */
const BLOCK_EDITOR_COMMANDS = new Set([
    'insertUnorderedList',
    'insertOrderedList',
    'justifyLeft',
    'justifyCenter',
    'justifyRight',
    'formatBlock',
]);

/**
 * Coloca el cursor al final de un nodo del editor.
 */
function placeCursorAtEnd(node) {
    if (! node) {
        return;
    }

    const selection = window.getSelection();
    if (! selection) {
        return;
    }

    const range = document.createRange();
    range.selectNodeContents(node);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
}

/**
 * Ejecuta un comando de formato sobre el editor contenteditable.
 */
export function execEditorCommand(editorEl, command, value = null) {
    if (! editorEl) {
        return false;
    }

    editorEl.focus();
    document.execCommand('styleWithCSS', false, ! BLOCK_EDITOR_COMMANDS.has(command));
    return document.execCommand(command, false, value);
}

/**
 * Inserta o alterna viñetas / numeración en el editor contenteditable.
 */
export function toggleList(editorEl, ordered = false) {
    if (! editorEl) {
        return;
    }

    editorEl.focus();
    document.execCommand('styleWithCSS', false, false);

    const tag = ordered ? 'ol' : 'ul';
    const command = ordered ? 'insertOrderedList' : 'insertUnorderedList';
    const hasLists = editorEl.querySelector('ul, ol');
    const visibleText = (editorEl.textContent || '').replace(/\u200B/g, '').trim();
    const isEffectivelyEmpty = ! visibleText && ! hasLists;

    // Editor vacío: crea la primera lista manualmente (execCommand suele fallar).
    if (isEffectivelyEmpty) {
        editorEl.innerHTML = `<${tag}><li><br></li></${tag}>`;
        placeCursorAtEnd(editorEl.querySelector(`${tag} li`));
        return;
    }

    const applied = document.execCommand(command, false, null);

    // Respaldo si el navegador no aplica el comando sobre la selección actual.
    if (! applied) {
        const selection = window.getSelection();
        if (! selection || selection.rangeCount === 0) {
            return;
        }

        const range = selection.getRangeAt(0);
        const selectedText = range.toString().trim() || 'Elemento';
        const listHtml = `<${tag}><li>${escapeHtml(selectedText)}</li></${tag}>`;

        if (range.collapsed) {
            document.execCommand('insertHTML', false, listHtml);
        } else {
            range.deleteContents();
            range.insertNode(range.createContextualFragment(listHtml));
        }
    }
}

/**
 * Aplica una propiedad CSS inline sobre la selección actual.
 */
export function applyInlineStyle(editorEl, property, value) {
    if (! editorEl || ! property || ! value) {
        return;
    }

    editorEl.focus();
    const selection = window.getSelection();
    if (! selection || selection.rangeCount === 0) {
        return;
    }

    const range = selection.getRangeAt(0);
    if (! editorEl.contains(range.commonAncestorContainer)) {
        return;
    }

    if (range.collapsed) {
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('insertHTML', false, `<span style="${property}:${value}">&#8203;</span>`);
        return;
    }

    const span = document.createElement('span');
    span.setAttribute('style', `${property}:${value}`);

    try {
        range.surroundContents(span);
    } catch {
        const fragment = range.extractContents();
        span.appendChild(fragment);
        range.insertNode(span);
    }

    selection.removeAllRanges();
    const next = document.createRange();
    next.selectNodeContents(span);
    next.collapse(false);
    selection.addRange(next);
}
