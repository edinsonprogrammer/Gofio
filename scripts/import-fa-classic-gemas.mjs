#!/usr/bin/env node
/**
 * Importa iconos Font Awesome Classic (solid + regular) a icon-packs/gemas/
 * Licencia: Font Awesome Free (CC BY 4.0 / OFL / MIT) — https://fontawesome.com
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FA_ROOT = path.join(ROOT, 'node_modules/@fortawesome/fontawesome-free');
const OUT_ROOT = path.join(ROOT, 'icon-packs/gemas/classic');
const TOKEN_ES_PATH = path.join(ROOT, 'scripts/fa-token-es.json');
const INDEX_PATH = path.join(ROOT, 'icon-packs/gemas/indice-classic.json');

const STYLES = ['solid', 'regular'];

function slugify(value) {
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

function translateIconName(englishName, tokenEs) {
    const parts = englishName.split('-');
    const translated = parts.map((part) => tokenEs[part] ?? part);

    return slugify(translated.join('-')) || englishName;
}

function ensureDir(dir) {
    fs.mkdirSync(dir, { recursive: true });
}

function main() {
    if (!fs.existsSync(FA_ROOT)) {
        console.error('Instala @fortawesome/fontawesome-free: npm install @fortawesome/fontawesome-free --save-dev');
        process.exit(1);
    }

    if (!fs.existsSync(TOKEN_ES_PATH)) {
        console.error('Falta scripts/fa-token-es.json — ejecuta: node scripts/build-fa-token-es.mjs');
        process.exit(1);
    }

    const tokenEs = JSON.parse(fs.readFileSync(TOKEN_ES_PATH, 'utf8'));
    const index = { pack: 'gemas', styles: {}, generated_at: new Date().toISOString() };
    let copied = 0;

    for (const style of STYLES) {
        const srcDir = path.join(FA_ROOT, 'svgs', style);
        const destDir = path.join(OUT_ROOT, style);

        if (!fs.existsSync(srcDir)) {
            continue;
        }

        ensureDir(destDir);
        index.styles[style] = {};

        const usedNames = new Set();

        for (const file of fs.readdirSync(srcDir).filter((f) => f.endsWith('.svg'))) {
            const englishName = file.replace(/\.svg$/, '');
            let spanishBase = translateIconName(englishName, tokenEs);
            let spanishName = spanishBase;
            let suffix = 2;

            while (usedNames.has(spanishName)) {
                spanishName = `${spanishBase}-${suffix++}`;
            }

            usedNames.add(spanishName);

            const src = path.join(srcDir, file);
            const dest = path.join(destDir, `${spanishName}.svg`);
            fs.copyFileSync(src, dest);

            index.styles[style][spanishName] = {
                fa: `fa-${style} fa-${englishName}`,
                english: englishName,
                file: `classic/${style}/${spanishName}.svg`,
                pack_value: `pack:gemas/classic/${style}/${spanishName}.svg`,
            };

            copied++;
        }
    }

    fs.writeFileSync(INDEX_PATH, JSON.stringify(index, null, 2));

    const manifestPath = path.join(ROOT, 'icon-packs/gemas/icon-pack.json');
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    manifest.description = 'Set premium de iconos Classic (Font Awesome Free): SVG con nombres en español en classic/solid y classic/regular.';
    manifest.classic_icons = {
        solid: Object.keys(index.styles.solid ?? {}).length,
        regular: Object.keys(index.styles.regular ?? {}).length,
        index: 'indice-classic.json',
    };
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));

    fs.writeFileSync(
        path.join(ROOT, 'icon-packs/gemas/ATTRIBUTION.md'),
        `# Atribución Font Awesome\n\nLos SVG en \`classic/\` provienen de [Font Awesome Free](https://fontawesome.com/icons/packs/classic) v7 (CC BY 4.0 / OFL-1.1 / MIT).\n\nGenerado el ${new Date().toISOString().slice(0, 10)}.\n`,
    );

    console.log(`Importados ${copied} iconos Classic a icon-packs/gemas/classic/`);
}

main();
