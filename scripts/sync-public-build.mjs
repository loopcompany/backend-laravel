import { copyFileSync, cpSync, existsSync, mkdirSync, readdirSync, statSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(projectRoot, 'public', 'build');
const manifest = join(source, 'manifest.json');
const configuredPublicHtml = process.env.PUBLIC_HTML_DIR;
const publicHtml = resolve(projectRoot, configuredPublicHtml || '../public_html');

if (!existsSync(manifest)) {
    throw new Error(`Vite manifest is missing: ${manifest}`);
}

if (!existsSync(publicHtml)) {
    if (configuredPublicHtml) {
        throw new Error(`PUBLIC_HTML_DIR does not exist: ${publicHtml}`);
    }

    console.log(`Skipping public_html sync; directory not found: ${publicHtml}`);
    process.exit(0);
}

if (!statSync(publicHtml).isDirectory()) {
    throw new Error(`PUBLIC_HTML_DIR is not a directory: ${publicHtml}`);
}

const target = join(publicHtml, 'build');

if (target === source) {
    console.log('Vite build is already in the public web directory.');
    process.exit(0);
}

mkdirSync(target, { recursive: true });

// Publish assets before the manifest so it never points to files still being copied.
for (const entry of readdirSync(source)) {
    if (entry !== 'manifest.json') {
        cpSync(join(source, entry), join(target, entry), { recursive: true, force: true });
    }
}

copyFileSync(manifest, join(target, 'manifest.json'));
console.log(`Synced Vite build to ${target}`);
