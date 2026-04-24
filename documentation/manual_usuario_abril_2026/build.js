#!/usr/bin/env node
/**
 * Alternativa al Makefile para entornos sin GNU Make (Windows sin WSL, etc.)
 * Uso:
 *   node build.js pdf     Genera PDF principal con Pandoc + xelatex
 *   node build.js html    Genera HTML + PDF alternativo con WeasyPrint
 *   node build.js clean   Elimina build/ y dist/
 *   node build.js all     pdf + html
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const VERSION = '3.5.22';
const ROOT = __dirname;

const SOURCES = [
  'chapters/00_portada.md',
  'chapters/00_prefacio.md',
  'chapters/01_introduccion.md',
  'chapters/02_gestion_personal.md',
  'chapters/03_control_asistencia.md',
  'chapters/04_conceptos_formulas.md',
  'chapters/05_procesamiento_planillas.md',
  'chapters/06_vacaciones.md',
  'chapters/07_acreedores.md',
  'chapters/08_reportes.md',
  'chapters/09_administracion.md',
  'appendices/A_legislacion_panama.md',
  'appendices/B_referencia_formulas.md',
  'appendices/C_matriz_permisos.md',
  'appendices/D_glosario.md',
  'appendices/E_faq_problemas.md',
  'appendices/F_changelog_resumido.md',
];

const PANDOC_COMMON = [
  '--from=markdown+yaml_metadata_block+smart+fenced_divs+bracketed_spans',
  '--toc',
  '--toc-depth=3',
  '--number-sections',
  '--top-level-division=chapter',
  '--lua-filter=filters/badges.lua',
  '--lua-filter=filters/crossref.lua',
  '--resource-path=.:assets:assets/img',
];

function ensureDirs() {
  ['build', 'dist'].forEach((d) => {
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
  const out = `dist/manual_innova_v${VERSION}.pdf`;
  console.log('==> Generando PDF principal (xelatex)...');
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

function buildHtml() {
  ensureDirs();
  const outHtml = `dist/manual_innova_v${VERSION}.html`;
  const outPdfAlt = `dist/manual_innova_v${VERSION}_alt.pdf`;

  console.log('==> Generando HTML imprimible...');
  run('pandoc', [
    'metadata.yaml',
    ...SOURCES,
    ...PANDOC_COMMON,
    '--standalone',
    '--template=templates/html_print.html',
    '--css=styles/print.css',
    '--self-contained',
    `--output=${outHtml}`,
  ]);
  console.log(`==> HTML listo: ${outHtml}`);

  console.log('==> Generando PDF alternativo (WeasyPrint)...');
  run('weasyprint', [outHtml, outPdfAlt]);
  console.log(`==> PDF alternativo listo: ${outPdfAlt}`);
}

function clean() {
  ['build', 'dist'].forEach((d) => {
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
  case 'html':  buildHtml(); break;
  case 'all':   buildPdf(); buildHtml(); break;
  case 'clean': clean(); break;
  default:
    console.log('Uso: node build.js [pdf|html|all|clean]');
    process.exit(1);
}
