#!/usr/bin/env bash
# ============================================================================
# check_links.sh — validación de enlaces internos y externos del manual.
#
# Requiere: lychee (https://github.com/lycheeverse/lychee)
#   Instalar: cargo install lychee   (o descargar release)
#
# El script sale con código != 0 si encuentra enlaces rotos, útil para CI.
# ============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"

if ! command -v lychee >/dev/null 2>&1; then
  echo "ERROR: lychee no está instalado."
  echo "       Instalar con: cargo install lychee"
  exit 2
fi

echo "=== Validando enlaces en ${ROOT} ==="
cd "${ROOT}"

lychee \
  --no-progress \
  --verbose \
  --base . \
  --max-concurrency 4 \
  --timeout 15 \
  chapters/*.md appendices/*.md README.md

echo
echo "=== Enlaces OK ==="
