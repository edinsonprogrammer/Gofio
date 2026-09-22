#!/usr/bin/env node
/**
 * Instala un Kit de Font Awesome Pro/Pro+ vía npm (requiere FONTAWESOME_PACKAGE_TOKEN en .env).
 */
import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { readEnvFile, writeFaNpmrc } from './fa-import-utils.mjs';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function main() {
    const env = readEnvFile(ROOT);
    const kitId = env.FONTAWESOME_KIT_ID ?? process.env.FONTAWESOME_KIT_ID;

    if (! kitId) {
        console.error('Define FONTAWESOME_KIT_ID en .env (ID del Kit en fontawesome.com/kits).');
        process.exit(1);
    }

    if (! writeFaNpmrc(ROOT)) {
        console.error('Define FONTAWESOME_PACKAGE_TOKEN en .env (token npm de tu cuenta Font Awesome Pro+).');
        process.exit(1);
    }

    const pkg = `@awesome.me/kit-${kitId}`;

    console.log(`Instalando ${pkg}...`);

    try {
        execSync(`npm install --save-dev "${pkg}"`, {
            cwd: ROOT,
            stdio: 'inherit',
            env: { ...process.env },
        });
    } catch {
        process.exit(1);
    }

    console.log(`Kit ${pkg} instalado. Ejecuta: npm run import:fa-graphite-gemas`);
}

main();
