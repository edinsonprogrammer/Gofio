import fs from 'fs';
import path from 'path';

export function slugify(value) {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/gi, '')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
}

export function translateIconName(englishName, tokenEs) {
    const parts = englishName.split('-');
    const translated = parts.map((part) => tokenEs[part] ?? part);

    return slugify(translated.join('-')) || englishName;
}

export function ensureDir(dir) {
    fs.mkdirSync(dir, { recursive: true });
}

export function readEnvFile(root) {
    const envPath = path.join(root, '.env');
    const values = {};

    if (! fs.existsSync(envPath)) {
        return values;
    }

    for (const line of fs.readFileSync(envPath, 'utf8').split('\n')) {
        const trimmed = line.trim();

        if (trimmed === '' || trimmed.startsWith('#') || ! trimmed.includes('=')) {
            continue;
        }

        const [key, ...rest] = trimmed.split('=');
        values[key.trim()] = rest.join('=').trim().replace(/^["']|["']$/g, '');
    }

    return values;
}

export function countSvgFiles(dir) {
    if (! fs.existsSync(dir)) {
        return 0;
    }

    return fs.readdirSync(dir).filter((file) => file.endsWith('.svg')).length;
}

/**
 * @param {string} root
 * @param {string[]} candidates
 */
export function firstExistingDirWithSvgs(root, candidates) {
    for (const relative of candidates) {
        const dir = path.isAbsolute(relative) ? relative : path.join(root, relative);
        if (countSvgFiles(dir) > 0) {
            return dir;
        }
    }

    return null;
}

/**
 * @param {string} dir
 * @param {string[]} suffixes
 * @param {number} depth
 * @returns {string[]}
 */
export function findDirsMatchingSuffixes(dir, suffixes, depth = 0) {
    if (depth > 6 || ! fs.existsSync(dir)) {
        return [];
    }

    const matches = [];
    const normalized = dir.replace(/\\/g, '/').toLowerCase();

    if (
        suffixes.some((suffix) => normalized.endsWith(suffix.toLowerCase()) || normalized.includes(`/${suffix.toLowerCase()}`))
        && countSvgFiles(dir) > 0
    ) {
        matches.push(dir);
    }

    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        if (! entry.isDirectory()) {
            continue;
        }

        if (['.git', 'node_modules', '.cache'].includes(entry.name)) {
            continue;
        }

        matches.push(...findDirsMatchingSuffixes(path.join(dir, entry.name), suffixes, depth + 1));
    }

    return matches;
}

/**
 * @param {string} root
 * @param {{
 *   envKey?: string,
 *   manualRelativePaths: string[],
 *   npmRelativePaths: string[],
 *   pathSuffixes: string[],
 * }} options
 */
export function resolveProPlusSvgSource(root, options) {
    const env = readEnvFile(root);

    if (options.envKey && env[options.envKey] && countSvgFiles(env[options.envKey]) > 0) {
        return { dir: env[options.envKey], origin: `${options.envKey} (.env)` };
    }

    const explicit = firstExistingDirWithSvgs(root, options.manualRelativePaths);
    if (explicit) {
        return { dir: explicit, origin: 'icon-packs/gemas/_sources/' };
    }

    const npmCandidates = [];

    for (const rel of options.npmRelativePaths) {
        npmCandidates.push(`node_modules/@fortawesome/fontawesome-pro/${rel}`);

        if (rel.startsWith('svgs/')) {
            npmCandidates.push(`node_modules/@fortawesome/fontawesome-pro/svgs-full/${rel.slice(5)}`);
        }
    }

    const kitRoot = path.join(root, 'node_modules/@awesome.me');
    if (fs.existsSync(kitRoot)) {
        for (const kitDir of fs.readdirSync(kitRoot)) {
            for (const rel of options.npmRelativePaths) {
                npmCandidates.push(`node_modules/@awesome.me/${kitDir}/${rel}`);

                if (rel.startsWith('svgs/')) {
                    npmCandidates.push(`node_modules/@awesome.me/${kitDir}/svgs-full/${rel.slice(5)}`);
                }
            }
        }
    }

    const npmMatch = firstExistingDirWithSvgs(root, npmCandidates);
    if (npmMatch) {
        return { dir: npmMatch, origin: 'paquete npm Font Awesome' };
    }

    const searchRoots = [
        path.join(root, 'node_modules/@fortawesome'),
        path.join(root, 'node_modules/@awesome.me'),
        path.join(root, 'icon-packs/gemas/_sources'),
    ];

    for (const searchRoot of searchRoots) {
        const found = findDirsMatchingSuffixes(searchRoot, options.pathSuffixes);
        if (found.length > 0) {
            return { dir: found[0], origin: searchRoot };
        }
    }

    return null;
}

/**
 * @param {string} root
 */
export function resolveGraphiteThinSource(root) {
    return resolveProPlusSvgSource(root, {
        envKey: 'FA_GRAPHITE_SOURCE',
        manualRelativePaths: [
            'icon-packs/gemas/_sources/graphite/thin',
            'icon-packs/gemas/_sources/graphite-thin',
        ],
        npmRelativePaths: [
            'svgs/graphite/thin',
            'svgs-full/graphite/thin',
            'svgs/graphite-thin',
            'svgs-full/graphite-thin',
        ],
        pathSuffixes: ['graphite/thin', 'graphite-thin'],
    });
}

export const SLAB_STYLES = [
    {
        id: 'regular',
        outPath: 'slab/regular',
        group: 'slab/regular',
        label: 'Slab · Regular',
        envKey: 'FA_SLAB_REGULAR_SOURCE',
        manualRelativePaths: ['icon-packs/gemas/_sources/slab/regular'],
        npmRelativePaths: ['svgs/slab/regular', 'svgs-full/slab/regular', 'svgs/slab-regular'],
        pathSuffixes: ['slab/regular', 'slab-regular'],
        faClass: (englishName) => `fa-slab fa-regular fa-${englishName}`,
    },
    {
        id: 'press',
        outPath: 'slab-press/regular',
        group: 'slab-press/regular',
        label: 'Slab Press · Regular',
        envKey: 'FA_SLAB_PRESS_SOURCE',
        manualRelativePaths: ['icon-packs/gemas/_sources/slab-press/regular'],
        npmRelativePaths: ['svgs/slab-press/regular', 'svgs-full/slab-press/regular', 'svgs/slab-press-regular'],
        pathSuffixes: ['slab-press/regular', 'slab-press-regular'],
        faClass: (englishName) => `fa-slab-press fa-regular fa-${englishName}`,
    },
    {
        id: 'duo',
        outPath: 'slab-duo/regular',
        group: 'slab-duo/regular',
        label: 'Slab Duo · Regular',
        envKey: 'FA_SLAB_DUO_SOURCE',
        manualRelativePaths: ['icon-packs/gemas/_sources/slab-duo/regular'],
        npmRelativePaths: ['svgs/slab-duo/regular', 'svgs-full/slab-duo/regular', 'svgs/slab-duo-regular'],
        pathSuffixes: ['slab-duo/regular', 'slab-duo-regular'],
        faClass: (englishName) => `fa-slab-duo fa-regular fa-${englishName}`,
    },
    {
        id: 'press-duo',
        outPath: 'slab-press-duo/regular',
        group: 'slab-press-duo/regular',
        label: 'Slab Press Duo · Regular',
        envKey: 'FA_SLAB_PRESS_DUO_SOURCE',
        manualRelativePaths: ['icon-packs/gemas/_sources/slab-press-duo/regular'],
        npmRelativePaths: ['svgs/slab-press-duo/regular', 'svgs-full/slab-press-duo/regular', 'svgs/slab-press-duo-regular'],
        pathSuffixes: ['slab-press-duo/regular', 'slab-press-duo-regular'],
        faClass: (englishName) => `fa-slab-press-duo fa-regular fa-${englishName}`,
    },
];

/**
 * @param {string} root
 */
export function resolveSlabStyleSources(root) {
    return SLAB_STYLES
        .map((style) => {
            const source = resolveProPlusSvgSource(root, style);

            if (! source) {
                return null;
            }

            return { ...style, ...source };
        })
        .filter(Boolean);
}

/**
 * @param {string} root
 */
export function writeFaNpmrc(root) {
    const env = readEnvFile(root);
    const token = env.FONTAWESOME_PACKAGE_TOKEN ?? process.env.FONTAWESOME_PACKAGE_TOKEN;

    if (! token) {
        return false;
    }

    const npmrc = [
        '@fortawesome:registry=https://npm.fontawesome.com/',
        '@awesome.me:registry=https://npm.fontawesome.com/',
        '//npm.fontawesome.com/:_authToken=' + token,
        '',
    ].join('\n');

    fs.writeFileSync(path.join(root, '.npmrc'), npmrc);

    return true;
}
