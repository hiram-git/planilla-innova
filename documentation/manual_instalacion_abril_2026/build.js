#!/usr/bin/env node
/**
 * Build script para el Manual de Instalación INNOVA Planillas.
 * Uso:
 *   node build.js pdf     Genera PDF con Pandoc + xelatex
 *   node build.js clean   Elimina build/ y dist/
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const VERSION = '1.0';
const ROOT = __dirname;

const SOURCES = [
  'chapters/00_portada.md',
  'chapters/01_instalacion.md',
  'appendices/A_flujo_multitenant.md',
];

const PANDOC_COMMON = [
  '--from=markdown+yaml_metadata_block+smart+fenced_divs+bracketed_spans',
  '--toc',
  '--toc-depth=3',
  '--number-sections',
  '--top-level-division=chapter',
  '--lua-filter=filters/badges.lua',
  '--resource-path=.',
];

function ensureDirs() {
  ['dist'].forEach((d) => {
    const full = path.join(ROOT, d);
    if (!fs.existsSync(full)) fs.mkdirSync(full, { recursive: true });
  });
}

function run(cmd, args) {
  console.log(`==> ${cmd} ${args.join(' ')}`);
  const result = spawnSync(cmd, args, { cwd: ROOT, stdio: 'inherit', shell: true });
  if (result.status !== 0) {
    console.error(`ERROR: ${cmd} salió con código ${result.status}`);
    process.exit(result.status || 1);
  }
}

function buildPdf() {
  ensureDirs();
  const out = `dist/manual_instalacion_v${VERSION}.pdf`;
  console.log('==> Generando Manual de Instalación (xelatex)...');
  run('pandoc', [
    'metadata.yaml',
    ...SOURCES,
    ...PANDOC_COMMON,
    '--pdf-engine=xelatex',
    '--template=templates/eisvogel.latex',
    `--output=${out}`,
  ]);
  console.log(`==> PDF listo: ${out}`);
}

function clean() {
  ['dist'].forEach((d) => {
    const full = path.join(ROOT, d);
    if (fs.existsSync(full)) {
      fs.rmSync(full, { recursive: true, force: true });
      console.log(`==> Eliminado ${d}/`);
    }
  });
}

const cmd = process.argv[2];
switch (cmd) {
  case 'pdf':   buildPdf(); break;
  case 'clean': clean(); break;
  default:
    console.log('Uso: node build.js [pdf|clean]');
    process.exit(1);
}
