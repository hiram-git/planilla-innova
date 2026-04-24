# ============================================================================
# audit_modules.ps1 — equivalente PowerShell de audit_modules.sh
#
# Uso (desde cualquier carpeta):
#   powershell -ExecutionPolicy Bypass -File documentation\manual_usuario_abril_2026\scripts\audit_modules.ps1
# o desde PowerShell Core:
#   pwsh documentation/manual_usuario_abril_2026/scripts/audit_modules.ps1
# ============================================================================

$ErrorActionPreference = 'Stop'
$OutputEncoding = [System.Text.Encoding]::UTF8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$Root      = (Resolve-Path (Join-Path $ScriptDir '..')).Path
$OldDir    = Join-Path $Root '..\manual_usuario'
$NewDir    = Join-Path $Root 'chapters'
$ApxDir    = Join-Path $Root 'appendices'

# Patrón sin acentos para evitar problemas de encoding del propio script.
# Matchea: "TODO migración" (codificación UTF-8 de ó = 2 bytes) y "TODO:".
$TodoPattern = 'TODO[: ]'

Write-Host '=== Auditoria de migracion del Manual de Usuario ===' -ForegroundColor Cyan
Write-Host ''
Write-Host "Fuente anterior: $OldDir"
Write-Host "Fuente nueva:    $NewDir"
Write-Host ''

# 1) Listar módulos HTML originales
Write-Host '--- Modulos HTML originales ---' -ForegroundColor Yellow
if (Test-Path $OldDir) {
  Get-ChildItem -Path $OldDir -Filter 'modulo_*.html' |
    Sort-Object Name |
    ForEach-Object { Write-Host "  $($_.Name)" }
} else {
  Write-Host '  (no encontrado)'
}
Write-Host ''

# 2) Contar TODOs pendientes por archivo (leyendo como UTF-8)
Write-Host '--- TODOs de migracion pendientes por archivo ---' -ForegroundColor Yellow

$files = @()
if (Test-Path $NewDir) { $files += Get-ChildItem -Path $NewDir -Filter '*.md' }
if (Test-Path $ApxDir) { $files += Get-ChildItem -Path $ApxDir -Filter '*.md' }

$totalTodos = 0
foreach ($f in $files) {
  $lines = Get-Content -Path $f.FullName -Encoding UTF8
  $count = ($lines | Where-Object { $_ -match $TodoPattern }).Count
  if ($count -gt 0) {
    '  {0,-45} {1} TODO(s)' -f $f.Name, $count | Write-Host
    $totalTodos += $count
  }
}

if ($totalTodos -eq 0) {
  Write-Host '  (sin pendientes)'
}
Write-Host ''

# 3) Resumen
Write-Host '--- Resumen ---' -ForegroundColor Yellow
Write-Host "TODOs totales pendientes: $totalTodos"
Write-Host ''
Write-Host 'Para generar el PDF actual aun con placeholders:'
Write-Host '  make pdf   (o)   node build.js pdf'
