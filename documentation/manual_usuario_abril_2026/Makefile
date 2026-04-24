# ============================================================================
# Manual de Usuario INNOVA Planillas - Build Pipeline
# ----------------------------------------------------------------------------
# Targets:
#   make pdf          Genera PDF principal con Pandoc + xelatex (eisvogel)
#   make html         Genera HTML navegable + PDF alternativo (WeasyPrint)
#   make watch        Regenera el PDF al cambiar cualquier .md
#   make check-links  Valida enlaces internos y externos con lychee
#   make clean        Elimina build/ y dist/
#   make all          pdf + html
# ============================================================================

# --- Configuración --------------------------------------------------------
PANDOC       ?= pandoc
LATEX_ENGINE ?= xelatex
WEASYPRINT   ?= weasyprint
LYCHEE       ?= lychee

VERSION      := 3.5.22
OUT_PDF      := dist/manual_innova_v$(VERSION).pdf
OUT_PDF_ALT  := dist/manual_innova_v$(VERSION)_alt.pdf
OUT_HTML     := dist/manual_innova_v$(VERSION).html

# Orden de compilación (portada y prefacio primero, luego capítulos, luego apéndices)
SOURCES := \
	chapters/00_portada.md \
	chapters/00_prefacio.md \
	chapters/01_introduccion.md \
	chapters/02_gestion_personal.md \
	chapters/03_control_asistencia.md \
	chapters/04_conceptos_formulas.md \
	chapters/05_procesamiento_planillas.md \
	chapters/06_vacaciones.md \
	chapters/07_acreedores.md \
	chapters/08_reportes.md \
	chapters/09_administracion.md \
	appendices/A_legislacion_panama.md \
	appendices/B_referencia_formulas.md \
	appendices/C_matriz_permisos.md \
	appendices/D_glosario.md \
	appendices/E_faq_problemas.md \
	appendices/F_changelog_resumido.md

PANDOC_COMMON := \
	--from markdown+yaml_metadata_block+smart+fenced_divs+bracketed_spans \
	--toc --toc-depth=3 \
	--number-sections \
	--top-level-division=chapter \
	--lua-filter filters/badges.lua \
	--lua-filter filters/crossref.lua \
	--resource-path=.:assets:assets/img

# --- Targets --------------------------------------------------------------
.PHONY: all pdf html watch check-links clean dirs

all: pdf html

dirs:
	@mkdir -p build dist

pdf: dirs $(OUT_PDF)

$(OUT_PDF): metadata.yaml $(SOURCES) templates/eisvogel.latex filters/badges.lua filters/crossref.lua
	@echo "==> Generando PDF principal (xelatex)..."
	$(PANDOC) metadata.yaml $(SOURCES) \
		$(PANDOC_COMMON) \
		--pdf-engine=$(LATEX_ENGINE) \
		--template=templates/eisvogel.latex \
		--output=$@
	@echo "==> PDF listo: $@"

html: dirs $(OUT_HTML) $(OUT_PDF_ALT)

$(OUT_HTML): metadata.yaml $(SOURCES) templates/html_print.html styles/print.css
	@echo "==> Generando HTML imprimible..."
	$(PANDOC) metadata.yaml $(SOURCES) \
		$(PANDOC_COMMON) \
		--standalone \
		--template=templates/html_print.html \
		--css=styles/print.css \
		--self-contained \
		--output=$@
	@echo "==> HTML listo: $@"

$(OUT_PDF_ALT): $(OUT_HTML)
	@echo "==> Generando PDF alternativo (WeasyPrint)..."
	$(WEASYPRINT) $< $@
	@echo "==> PDF alternativo listo: $@"

watch:
	@echo "==> Modo watch (Ctrl+C para salir)..."
	@while true; do \
		$(MAKE) --no-print-directory pdf; \
		inotifywait -q -e modify,create,delete -r chapters appendices metadata.yaml 2>/dev/null || sleep 5; \
	done

check-links:
	@echo "==> Validando enlaces..."
	$(LYCHEE) --no-progress --verbose chapters/*.md appendices/*.md

clean:
	@echo "==> Limpiando artefactos..."
	@rm -rf build dist
	@echo "==> Limpio."
