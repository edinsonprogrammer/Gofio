#!/usr/bin/env node
/**
 * Importa iconos Font Awesome Graphite (thin) a icon-packs/gemas/graphite/thin/
 * Requiere licencia Font Awesome Pro+ — https://fontawesome.com/icons/packs/graphite
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
    ensureDir,
    resolveGraphiteThinSource,
    translateIconName,
} from './fa-import-utils.mjs';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT_DIR = path.join(ROOT, 'icon-packs/gemas/graphite/thin');
const TOKEN_ES_PATH = path.join(ROOT, 'scripts/fa-token-es.json');
const INDEX_PATH = path.join(ROOT, 'icon-packs/gemas/indice-graphite.json');

function printSetupHelp() {
    console.error(`
No se encontraron SVG del pack Graphite (thin).

Graphite es Pro+ de Font Awesome: https://fontawesome.com/icons/packs/graphite

Opción A — Kit npm (recomendado si tienes Pro+):
  1. En fontawesome.com/kits activa Graphite + "Instalar Kit como paquete npm"
  2. En .env añade:
       FONTAWESOME_PACKAGE_TOKEN=tu-token-npm
       FONTAWESOME_KIT_ID=tu-kit-id
  3. npm run install:fa-kit
  4. npm run import:fa-graphite-gemas

Opción B — Carpeta manual:
  1. Descarga tu Kit (Desktop) con Graphite habilitado
  2. Copia los SVG thin de Graphite a:
       icon-packs/gemas/_sources/graphite/thin/
  3. npm run import:fa-graphite-gemas

Opción C — Ruta personalizada en .env:
       FA_GRAPHITE_SOURCE=C:/ruta/a/svgs/graphite/thin
`);
}

function updateAttribution(count) {
    const attributionPath = path.join(ROOT, 'icon-packs/gemas/ATTRIBUTION.md');
    let content = fs.existsSync(attributionPath)
        ? fs.readFileSync(attributionPath, 'utf8')
        : '# Atribución Font Awesome\n\n';

    const graphiteBlock = [
        '',
        '## Graphite (Pro+)',
        '',
        `Los SVG en \`graphite/thin/\` provienen del pack [Font Awesome Graphite](https://fontawesome.com/icons/packs/graphite) (Pro+, thin). Requieren licencia Font Awesome Pro+ válida.`,
        '',
        `Iconos importados: ${count}. Generado el ${new Date().toISOString().slice(0, 10)}.`,
        '',
    ].join('\n');

    if (content.includes('## Graphite (Pro+)')) {
        content = content.replace(
            /## Graphite \(Pro\+\)[\s\S]*?(?=\n## |\n*$)/,
            graphiteBlock.trim(),
        );
    } else {
        content = content.trimEnd() + graphiteBlock;
    }

    fs.writeFileSync(attributionPath, content.endsWith('\n') ? content : content + '\n');
}

function main() {
    const source = resolveGraphiteThinSource(ROOT);

    if (! source) {
        printSetupHelp();
        process.exit(1);
    }

    if (! fs.existsSync(TOKEN_ES_PATH)) {
        console.error('Falta scripts/fa-token-es.json — ejecuta: npm run import:fa-gemas');
        process.exit(1);
    }

    const tokenEs = JSON.parse(fs.readFileSync(TOKEN_ES_PATH, 'utf8'));
    ensureDir(OUT_DIR);

    const index = {
        pack: 'gemas',
        family: 'graphite',
        style: 'thin',
        source: source.origin,
        generated_at: new Date().toISOString(),
        icons: {},
    };

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

        fs.copyFileSync(path.join(source.dir, file), path.join(OUT_DIR, `${spanishName}.svg`));

        index.icons[spanishName] = {
            fa: `fa-graphite fa-thin fa-${englishName}`,
            english: englishName,
            file: `graphite/thin/${spanishName}.svg`,
            pack_value: `pack:gemas/graphite/thin/${spanishName}.svg`,
        };

        copied++;
    }

    if (copied === 0) {
        console.error(`La carpeta ${source.dir} no contiene SVG.`);
        printSetupHelp();
        process.exit(1);
    }

    fs.writeFileSync(INDEX_PATH, JSON.stringify(index, null, 2));

    const manifestPath = path.join(ROOT, 'icon-packs/gemas/icon-pack.json');
    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    manifest.description = 'Set premium Gemas: Classic Free (solid/regular) + Graphite Pro+ (thin), con nombres en español.';
    manifest.graphite_icons = {
        thin: copied,
        index: 'indice-graphite.json',
        license: 'Font Awesome Pro+',
        url: 'https://fontawesome.com/icons/packs/graphite',
    };
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
    updateAttribution(copied);

    console.log(`Origen: ${source.dir}`);
    console.log(`Importados ${copied} iconos Graphite (thin) a icon-packs/gemas/graphite/thin/`);
}

main();
