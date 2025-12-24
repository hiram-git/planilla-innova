# Script para crear archivos de migración con timestamp correcto (PowerShell)
# Uso: .\scripts\create-migration.ps1 "descripcion_de_la_migracion"

param(
    [Parameter(Mandatory=$true)]
    [string]$Description
)

# Limpiar descripción (remover espacios, convertir a lowercase, solo alfanuméricos y guiones bajos)
$CleanDescription = $Description -replace '[^a-zA-Z0-9_]', '_' `
    -replace '__+', '_' `
    -replace '^_|_$', ''
$CleanDescription = $CleanDescription.ToLower()

# Generar timestamp en formato YYYY_MM_DD_HHMMSS
$Timestamp = Get-Date -Format "yyyy_MM_dd_HHmmss"

# Nombre del archivo
$Filename = "${Timestamp}_${CleanDescription}.sql"
$Filepath = "database\migrations\$Filename"

# Crear directorio si no existe
$MigrationsDir = "database\migrations"
if (-not (Test-Path $MigrationsDir)) {
    New-Item -ItemType Directory -Path $MigrationsDir -Force | Out-Null
}

# Obtener nombre de usuario de Git o sistema
$Author = "Sistema"
try {
    $GitUser = git config user.name 2>$null
    if ($GitUser) { $Author = $GitUser }
} catch {}

# Template de la migración
$DateString = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$Template = @"
-- Migración: $CleanDescription
-- Fecha: $DateString
-- Autor: $Author
-- Descripción: [Agregar descripción detallada aquí]

-- ==== UP Migration ====

-- Tu código SQL aquí
-- Ejemplo:
-- CREATE TABLE IF NOT EXISTS nueva_tabla (
--   id INT AUTO_INCREMENT PRIMARY KEY,
--   nombre VARCHAR(100) NOT NULL,
--   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ALTER TABLE tabla_existente
-- ADD COLUMN IF NOT EXISTS nueva_columna VARCHAR(50) NULL
-- COMMENT 'Descripción de la columna';


-- ==== DOWN Migration (Rollback) ====
-- Para revertir manualmente si es necesario:

-- DROP TABLE IF EXISTS nueva_tabla;
-- ALTER TABLE tabla_existente DROP COLUMN IF EXISTS nueva_columna;
"@

# Crear archivo
Set-Content -Path $Filepath -Value $Template -Encoding UTF8

# Confirmación con colores
Write-Host ""
Write-Host "✓ Migración creada exitosamente:" -ForegroundColor Green
Write-Host "  $Filepath" -ForegroundColor Blue
Write-Host ""
Write-Host "Siguiente paso:" -ForegroundColor Yellow
Write-Host "  1. Editar el archivo y agregar tu código SQL"
Write-Host "  2. Probar la migración en una BD de prueba"
Write-Host "  3. Commit y push cuando esté listo"
Write-Host ""
Write-Host "Abrir en editor:"
Write-Host "  code $Filepath  # VS Code" -ForegroundColor Cyan
Write-Host "  notepad $Filepath  # Notepad" -ForegroundColor Cyan
Write-Host ""
