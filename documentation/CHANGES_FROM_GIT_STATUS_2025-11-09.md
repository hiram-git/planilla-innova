# Cambios locales detectados por Git (WIP)

Fecha: 09-Nov-2025

Este documento resume los cambios locales detectados con `git status` que aún no han sido versionados, para facilitar su documentación y posterior commit.

---

## Modificados

- `.claude/settings.local.json`
- `CLAUDE.md`

---

## No rastreados (untracked)

### Scripts y utilidades
- `check_init_logs.bat`
- `database/scripts/insert_holidays_panama_2025.sql` (script de feriados Panamá 2025)

### Tests y diagnósticos
- `test_calendar_init.php`
- `test_diagnose_init.php`
- `test_saturday_update.php`
- `test_working_days_saturday.php`

### Activos/Recursos (imágenes)
- `images/1759866106_ChatGPT_Image11_12_05.png`
- `images/logos/logo_derecho_reportes_*.png|jpg`
- `images/logos/logo_empresa_*.jpg|png`
- `images/logos/logo_izquierdo_reportes_*.jpg|png`

### Logs (no versionables)
- `storage/logs/attendance_sync_2025-*.log`
- `storage/logs/base44_api_2025-*.log`

### Misceláneos
- `nul_`

---

## Sugerencias de documentación

- CHANGELOG:
  - Agregar entrada WIP para v3.5.6 destacando:
    - Importación de feriados 2025 (`database/scripts/insert_holidays_panama_2025.sql`).
    - Actualización de recursos gráficos (logos) para reportes.
    - Nuevos scripts utilitarios y pruebas de calendario.
- Calendario Empresarial/BusinessCalendar:
  - Documentar la disponibilidad del script 2025 y el procedimiento de ejecución en entornos (QA/Prod).
- README/Índice de documentación:
  - Verificar y actualizar la versión mostrada (v3.5.5 actual).

---

## Próximos pasos sugeridos

1) Añadir `.log` a `.gitignore` si no existe, y excluir la carpeta `storage/logs/`.
2) Confirmar si los assets en `images/` deben versionarse; si sí, normalizar nombres y formatos.
3) Crear entrada `documentation/changelog/v3.5.6.md` (o `WIP.md`) con los puntos anteriores.
4) Ejecutar y documentar el script de feriados 2025 en QA y Prod.

