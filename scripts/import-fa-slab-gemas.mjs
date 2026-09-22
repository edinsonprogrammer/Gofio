#!/usr/bin/env node
/**
 * Importa iconos Font Awesome Slab a icon-packs/gemas/
 * Requiere licencia Font Awesome Pro+ — https://fontawesome.com/icons/packs/slab
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
    ensureDir,
    resolveSlabStyleSources,
    translateIconName,
} from './fa-import-utils.mjs';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT_ROOT = path.join(ROOT, 'icon-packs/gemas');
const TOKEN_ES_PATH = path.join(ROOT, 'scripts/fa-token-es.json');
const INDEX_PATH = path.join(ROOT, 'icon-packs/gemas/indice-slab.json');

function printSetupHelp() {
    console.error(`
No se encontraron SVG del pack Slab.

Slab es Pro+ de Font Awesome: https://fontawesome.com/icons/packs/slab

Opción A — Kit npm (recomendado si tienes Pro+):
  1. En fontawesome.com/kits activa Slab (regular, press, duo…) + "Instalar Kit como paquete npm"
  2. En .env añade:
       FONTAWESOME_PACKAGE_TOKEN=tu-token-npm
       FONTAWESOME_KIT_ID=tu-kit-id
  3. npm run install:fa-kit
  4. npm run import:fa-slab-gemas

Opción B — Carpeta manual:
  Copia los SVG a una de estas rutas:
    icon-packs/gemas/_sources/slab/regular/
    icon-packs/gemas/_sources/slab-press/regular/
    icon-packs/gemas/_sources/slab-duo/regular/
    icon-packs/gemas/_sources/slab-press-duo/regular/
  Luego: npm run import:fa-slab-gemas

Opción C — Rutas personalizadas en .env:
    FA_SLAB_REGULAR_SOURCE=C:/ruta/a/svgs/slab/regular
`);
}

function updateAttribution(counts) {
    const attributionPath = path.join(ROOT, 'icon-packs/gemas/ATTRIBUTION.md');
    let content = fs.existsSync(attributionPath)
        ? fs.readFileSync(attributionPath, 'utf8')
        : '# Atribución Font Awesome\n\n';

    const details = Object.entries(counts)
        .map(([style, count]) => `- ${style}: ${count}`)
        .join('\n');

    const slabBlock = [
        '',
        '## Slab (Pro+)',
        '',
        'Los SVG en `slab/`, `slab-press/`, `slab-duo/` y `slab-press-duo/` provienen del pack [Font Awesome Slab](https://fontawesome.com/icons/packs/slab) (Pro+). Requieren licencia Font Awesome Pro+ válida.',
        '',
        details,
        '',
        `Generado el ${new Date().toISOString().slice(0, 10)}.`,
        '',
    ].join('\n');

    if (content.includes('## Slab (Pro+)')) {
        content = content.replace(
            /## Slab \(Pro\+\)[\s\S]*?(?=\n## |\n*$)/,
            slabBlock.trim(),
        );
    } else {
        content = content.trimEnd() + slabBlock;
    }

    fs.writeFileSync(attributionPath, content.endsWith('\n') ? content : content + '\n');
}

function importStyle(source, tokenEs, index) {
    const outDir = path.join(OUT_ROOT, source.outPath);
    ensureDir(outDir);

    index.styles[source.id] = {};
    const usedNames = new Set();
    let copied = 0;

    for (const file of fs.readdirSync(source.dir).filter((f) => f.endsWith('.svg'))) {
        const englishName = file.replace(/\.svg$/, '');
        let spanishBase = translateIconName(englishName, tokenEs);
        let spanishName = spanishBase;
        let suffix = 2;

        while (usedNames.has(spanishName)) {
            spanishName = `${spanishBase}-${suffix++}`;
        }

        usedNames.add(spanishName);

        fs.copyFileSync(path.join(source.dir, file), path.join(outDir, `${spanishName}.svg`));

        index.styles[source.id][spanishName] = {
            fa: source.faClass(englishName),
            english: englishName,
            file: `${source.outPath}/${spanishName}.svg`,
            pack_value: `pack:gemas/${source.outPath}/${spanishName}.svg`,
        };

        copied++;
    }

    return copied;
}

function main() {
    const sources = resolveSlabStyleSources(ROOT);

    if (sources.length === 0) {
        printSetupHelp();
        process.exit(1);
    }

    if (! fs.existsSync(TOKEN_ES_PATH)) {
        console.error('Falta scripts/fa-token-es.json — ejecuta: npm run import:fa-gemas');
        process.exit(1);
    }

    const tokenEs = JSON.parse(fs.readFileSync(TOKEN_ES_PATH, 'utf8'));
    const index = {
        pack: 'gemas',
        family: 'slab',
        generated_at: new Date().toISOString(),
        styles: {},
    };

    const counts = {};
    let total = 0;

    for (const source of sources) {
        const copied = importStyle(source, tokenEs, index);
        counts[source.label] = copied;
        total += copied;
        console.log(`  ${source.label}: ${copied} iconos ← ${source.dir}`);
    }

    if (total === 0) {
        printSetupHelp();
        process.exit(1);
    }

    fs.writeFileSync(INDEX_PATH, JSON.stringify(index, null, 2));

    const manifestPath = path.join(ROOT, 'icon-packs/gemas/icon-pack.json');
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    manifest.slab_icons = {
        styles: Object.fromEntries(
            Object.entries(index.styles).map(([key, icons]) => [key, Object.keys(icons).length]),
        ),
        total,
        index: 'indice-slab.json',
        license: 'Font Awesome Pro+',
        url: 'https://fontawesome.com/icons/packs/slab',
    };

    if (! manifest.description?.includes('Slab')) {
        manifest.description = `${manifest.description ?? 'Set premium Gemas.'} Incluye Slab Pro+ cuando está importado.`.trim();
    }

    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
    updateAttribution(counts);

    console.log(`Importados ${total} iconos Slab a icon-packs/gemas/`);
}

main();
