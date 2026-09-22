/**
 * Utilidades para mezclar colores hex y generar una paleta coherente
 * a partir de tres bases: marca, fondo y acento.
 */

/** Convierte un color hex (#RGB o #RRGGBB) a componentes RGB. */
export function parseHex(hex) {
    const normalized = String(hex || '').trim().replace(/^#/, '');
    if (normalized.length === 3) {
        const [r, g, b] = normalized.split('');
        return {
            r: parseInt(r + r, 16),
            g: parseInt(g + g, 16),
            b: parseInt(b + b, 16),
        };
    }
    if (normalized.length !== 6) {
        return { r: 13, g: 148, b: 136 };
    }
    return {
        r: parseInt(normalized.slice(0, 2), 16),
        g: parseInt(normalized.slice(2, 4), 16),
        b: parseInt(normalized.slice(4, 6), 16),
    };
}

/** Normaliza un valor de color a hex #RRGGBB válido para inputs type=color. */
export function normalizeHex(hex, fallback = '#0D9488') {
    const raw = String(hex || '').trim();
    if (! raw) {
        return fallback;
    }

    const withHash = raw.startsWith('#') ? raw : `#${raw}`;
    const { r, g, b } = parseHex(withHash);
    return toHex(r, g, b);
}

/** Empaqueta RGB en cadena hex de seis dígitos. */
export function toHex(r, g, b) {
    const clamp = (v) => Math.max(0, Math.min(255, Math.round(v)));
    return `#${[clamp(r), clamp(g), clamp(b)]
        .map((v) => v.toString(16).padStart(2, '0'))
        .join('')}`;
}

/** Luminancia relativa aproximada para elegir texto legible sobre un fondo. */
export function luminance(hex) {
    const { r, g, b } = parseHex(hex);
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255;
}

/** Mezcla dos colores hex; ratio 0 = color A, 1 = color B. */
export function mixHex(colorA, colorB, ratio = 0.5) {
    const a = parseHex(colorA);
    const b = parseHex(colorB);
    const t = Math.max(0, Math.min(1, ratio));
    return toHex(
        a.r + (b.r - a.r) * t,
        a.g + (b.g - a.g) * t,
        a.b + (b.b - a.b) * t,
    );
}

/** Oscurece un color hex (amount entre 0 y 1). */
export function darken(hex, amount = 0.12) {
    return mixHex(hex, '#000000', amount);
}

/** Aclara un color hex mezclándolo con blanco. */
export function lighten(hex, amount = 0.15) {
    return mixHex(hex, '#FFFFFF', amount);
}

/**
 * Genera variables CSS derivadas a partir de tres colores base y un ratio de mezcla.
 * No altera tipografía, sombras ni dimensiones estructurales.
 */
export function buildPaletteFromBases({ brand, background, accent }, blendRatio = 0.5) {
    const surface = luminance(background) > 0.55 ? '#FFFFFF' : lighten(background, 0.08);
    const text = luminance(background) > 0.55 ? '#0F172A' : '#F8FAFC';
    const panel = mixHex(background, accent, 0.08 + blendRatio * 0.12);
    const border = mixHex(background, '#64748B', 0.28 + blendRatio * 0.1);
    const muted = mixHex(text, background, 0.45);
    const brandHover = darken(brand, 0.1 + blendRatio * 0.06);
    const topbarFrom = mixHex(brand, accent, blendRatio * 0.25);
    const topbarTo = mixHex(brand, accent, 0.45 + blendRatio * 0.35);

    return {
        '--bg-principal': background,
        '--text-principal': text,
        '--color-brand': brand,
        '--color-brand-hover': brandHover,
        '--color-link': darken(brand, 0.06),
        '--color-surface': surface,
        '--color-border': border,
        '--color-muted': muted,
        '--color-panel': panel,
        '--color-accent': accent,
        '--color-accent-soft': mixHex('#FFFFFF', accent, 0.22 + blendRatio * 0.18),
        '--color-topbar-dark': darken(brand, 0.18),
        '--color-input-border': mixHex(background, '#64748B', 0.38),
        '--color-btn-secondary-bg': mixHex(background, accent, 0.1),
        '--color-btn-secondary-border': mixHex(accent, '#FFFFFF', 0.55),
        '--color-btn-secondary-text': darken(brand, 0.22),
        '--color-topbar-gradient-from': topbarFrom,
        '--color-topbar-gradient-to': topbarTo,
    };
}
