#!/usr/bin/env bash
# ============================================================================
# audit_modules.sh — reporte de migración HTML → Markdown
#
# Compara el contenido del manual HTML anterior (documentation/manual_usuario/)
# con los capítulos actuales (documentation/manual_usuario_abril_2026/chapters/)
# y muestra qué secciones quedan con marca "TODO migración" pendiente.
# ============================================================================

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OLD_DIR="${ROOT}/../manual_usuario"
NEW_DIR="${ROOT}/chapters"
APX_DIR="${ROOT}/appendices"

echo "=== Auditoría de migración del Manual de Usuario ==="
echo
echo "Fuente anterior: ${OLD_DIR}"
echo "Fuente nueva:    ${NEW_DIR}"
echo

# 1) Listar módulos HTML originales
echo "--- Módulos HTML originales ---"
if [ -d "${OLD_DIR}" ]; then
  ls -1 "${OLD_DIR}"/modulo_*.html 2>/dev/null | sed 's|.*/||' | sort
else
  echo "(no encontrado)"
fi
echo

# 2) Contar TODO migración pendientes por capítulo
echo "--- TODOs de migración pendientes por archivo ---"
for f in "${NEW_DIR}"/*.md "${APX_DIR}"/*.md; do
  [ -f "$f" ] || continue
  count=$(grep -c "TODO migración\|TODO:" "$f" 2>/dev/null || echo 0)
  if [ "$count" -gt 0 ]; then
    printf "  %-45s %s TODO(s)\n" "$(basename "$f")" "$count"
  fi
done
echo

# 3) Resumen numérico
total_todos=$(grep -c "TODO migración\|TODO:" "${NEW_DIR}"/*.md "${APX_DIR}"/*.md 2>/dev/null | awk -F: '{s+=$2} END {print s+0}')
echo "--- Resumen ---"
echo "TODOs totales pendientes: ${total_todos}"
echo
echo "Para generar el PDF actual aún con placeholders:"
echo "  make pdf"
