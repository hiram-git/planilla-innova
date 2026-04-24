# Manual de Usuario - INNOVA Planillas v3.5.22 (Abril 2026)

Fuente única en Markdown para generar el manual en formato PDF y HTML imprimible.

## Estructura

```
manual_usuario_abril_2026/
├── metadata.yaml          Variables del documento (título, versión, colores)
├── Makefile               Targets: pdf / html / watch / clean
├── build.js               Alternativa en Node si no hay make
├── chapters/              9 capítulos + portada + prefacio
├── appendices/            6 apéndices (A-F)
├── assets/                Imágenes, diagramas, logos
├── templates/             eisvogel.latex (LaTeX), html_print.html (WeasyPrint)
├── styles/                print.css para la ruta WeasyPrint
├── filters/               Filtros Lua para Pandoc (badges, cross-refs)
├── scripts/               Utilidades de auditoría y QA
├── build/                 Artefactos intermedios (gitignored)
└── dist/                  Salida final (PDF + HTML)
```

## Requisitos

### Ruta principal (Pandoc + LaTeX)

- **Pandoc** >= 3.0 — https://pandoc.org
- **TeX Live** completo o **MiKTeX** con `xelatex` y paquetes: `eisvogel`, `fontawesome5`, `hyperref`, `lastpage`, `longtable`
- Fuentes: `Source Serif Pro`, `Source Sans Pro`, `Fira Code` instaladas en el sistema

### Ruta alternativa (Pandoc + WeasyPrint)

- **Pandoc** >= 3.0
- **WeasyPrint** >= 60 — `pip install weasyprint`

### Validación de enlaces (opcional)

- **lychee** — https://github.com/lycheeverse/lychee

## Comandos

```bash
# Generar PDF principal (xelatex)
make pdf

# Generar HTML + PDF alternativo (WeasyPrint)
make html

# Limpiar artefactos intermedios y salida
make clean

# Modo watch: regenera el PDF al guardar cualquier .md
make watch

# Validar enlaces internos y externos
make check-links
```

Sin `make`, usar la alternativa Node:

```bash
node build.js pdf
node build.js html
node build.js clean
```

## Flujo de edición

1. Los archivos fuente son los `.md` en `chapters/` y `appendices/`.
2. Nunca editar directamente la salida en `dist/` — se regenera en cada build.
3. Para agregar una captura de pantalla: colocarla en `assets/img/capXX/` y referenciarla con ruta relativa desde el `.md`.
4. Los números de sección (`3.1.2`) los genera Pandoc automáticamente — en el Markdown sólo se usan los niveles `#`, `##`, `###`.

## Mantenimiento por versión

Para publicar una nueva versión del manual (ej. 3.5.23 → 3.6.0):

1. Actualizar `metadata.yaml` → `version` y `date`.
2. Actualizar `appendices/F_changelog_resumido.md` con las novedades.
3. Agregar/ajustar secciones en los capítulos correspondientes.
4. Ejecutar `make pdf` y revisar el resultado.
5. Commit del `.pdf` en `dist/` + tag git `manual-v3.5.22`.

## Migración desde el manual anterior

El manual HTML previo vive en `documentation/manual_usuario/`. El script de
auditoría genera un reporte de qué secciones HTML ya se migraron a `.md` y
cuántos `TODO migración` quedan en cada archivo.

**Windows (PowerShell — recomendado)**:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\audit_modules.ps1
```

**Linux/macOS/Git Bash**:

```bash
bash scripts/audit_modules.sh
```

> Si en Windows intentas `bash scripts/audit_modules.sh` y ves el mensaje
> *"Subsistema de Windows para Linux no tiene distribuciones instaladas"*,
> usa la variante PowerShell (`.ps1`) o ejecuta desde **Git Bash** (viene con
> Laragon/Git for Windows).

## Referencia

- Plan de refactorización completo: conversación original con el equipo técnico (Abr-2026).
- Guía de estilo editorial: `appendices/D_glosario.md` define la terminología oficial.
