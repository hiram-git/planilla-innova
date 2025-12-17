# ============================================
# Script: crear_tareas_asistencias.ps1
# Descripción: Crear tareas programadas de Windows para sistema de asistencias
# Requisitos: Ejecutar como Administrador
# Versión: 1.0
# Fecha: 15-Dic-2025
# ============================================

# Verificar si se está ejecutando como Administrador
$currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
$adminRole = [Security.Principal.WindowsBuiltInRole]::Administrator

if (-not $principal.IsInRole($adminRole)) {
    Write-Host "❌ ERROR: Este script debe ejecutarse como Administrador." -ForegroundColor Red
    Write-Host "   Clic derecho en PowerShell → 'Ejecutar como administrador'" -ForegroundColor Yellow
    pause
    exit 1
}

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   CONFIGURACIÓN AUTOMÁTICA DE TAREAS - ASISTENCIAS            ║" -ForegroundColor Cyan
Write-Host "║   Sistema de Planillas Innova v3.5.20                         ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# ============================================
# CONFIGURACIÓN - AJUSTAR SEGÚN TU ENTORNO
# ============================================

$PHPPath = "C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe"
$ProjectPath = "C:\laragon60\www\planilla-innova"
$LogPath = "$ProjectPath\storage\logs"

Write-Host "📝 Configuración detectada:" -ForegroundColor Yellow
Write-Host "   PHP: $PHPPath"
Write-Host "   Proyecto: $ProjectPath"
Write-Host "   Logs: $LogPath"
Write-Host ""

# Verificar que PHP existe
if (-not (Test-Path $PHPPath)) {
    Write-Host "❌ ERROR: No se encontró PHP en $PHPPath" -ForegroundColor Red
    Write-Host "   Por favor, edita el script y ajusta la ruta de PHP." -ForegroundColor Yellow
    pause
    exit 1
}

# Verificar que el proyecto existe
if (-not (Test-Path $ProjectPath)) {
    Write-Host "❌ ERROR: No se encontró el proyecto en $ProjectPath" -ForegroundColor Red
    Write-Host "   Por favor, edita el script y ajusta la ruta del proyecto." -ForegroundColor Yellow
    pause
    exit 1
}

# Crear directorio de logs si no existe
if (-not (Test-Path $LogPath)) {
    New-Item -ItemType Directory -Path $LogPath -Force | Out-Null
    Write-Host "✅ Directorio de logs creado: $LogPath" -ForegroundColor Green
}

Write-Host "🔍 Verificando tareas existentes..." -ForegroundColor Yellow

# Eliminar tareas existentes si las hay
$existingTasks = @(
    "Planilla - Sincronizar Asistencias",
    "Planilla - Procesar Asistencias Pipeline"
)

foreach ($taskName in $existingTasks) {
    $task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($task) {
        Write-Host "⚠️  Tarea existente encontrada: $taskName" -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
        Write-Host "   ✓ Tarea eliminada" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "📅 Creando tareas programadas..." -ForegroundColor Cyan
Write-Host ""

# ============================================
# TAREA 1: Sincronización API (cada 15 min)
# ============================================

Write-Host "1️⃣  Creando tarea: Sincronizar Asistencias" -ForegroundColor White

$Action1 = New-ScheduledTaskAction `
    -Execute $PHPPath `
    -Argument "$ProjectPath\scripts\cron\sync_attendance.php" `
    -WorkingDirectory $ProjectPath

$Trigger1 = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 15) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$Settings1 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 10)

$Principal1 = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName "Planilla - Sincronizar Asistencias" `
    -Action $Action1 `
    -Trigger $Trigger1 `
    -Settings $Settings1 `
    -Principal $Principal1 `
    -Description "Sincronizar marcaciones desde API Base44 cada 15 minutos. Sistema de Planillas Innova." | Out-Null

Write-Host "   ✅ Tarea creada exitosamente" -ForegroundColor Green
Write-Host "   📅 Frecuencia: Cada 15 minutos" -ForegroundColor Gray
Write-Host "   📂 Script: scripts\cron\sync_attendance.php" -ForegroundColor Gray
Write-Host ""

# ============================================
# TAREA 2: Pipeline Completo (cada 25 min, +5 min delay)
# ============================================

Write-Host "2️⃣  Creando tarea: Procesar Asistencias Pipeline" -ForegroundColor White

$Action2 = New-ScheduledTaskAction `
    -Execute $PHPPath `
    -Argument "$ProjectPath\scripts\cron\process_attendance_pipeline.php" `
    -WorkingDirectory $ProjectPath

# Crear trigger con delay de 5 minutos
$Trigger2 = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(5) `
    -RepetitionInterval (New-TimeSpan -Minutes 25) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$Settings2 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 15)

$Principal2 = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

Register-ScheduledTask `
    -TaskName "Planilla - Procesar Asistencias Pipeline" `
    -Action $Action2 `
    -Trigger $Trigger2 `
    -Settings $Settings2 `
    -Principal $Principal2 `
    -Description "Procesar marcaciones completo: records → details → calculations. Cada 25 minutos con delay de 5 min. Sistema de Planillas Innova." | Out-Null

Write-Host "   ✅ Tarea creada exitosamente" -ForegroundColor Green
Write-Host "   📅 Frecuencia: Cada 25 minutos (delay 5 min)" -ForegroundColor Gray
Write-Host "   📂 Script: scripts\cron\process_attendance_pipeline.php" -ForegroundColor Gray
Write-Host ""

# ============================================
# RESUMEN Y VERIFICACIÓN
# ============================================

Write-Host ""
Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   ✅ TAREAS CREADAS EXITOSAMENTE                               ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📋 Resumen de tareas programadas:" -ForegroundColor Cyan
Write-Host ""

$tasks = Get-ScheduledTask | Where-Object {$_.TaskName -like "Planilla*"}

foreach ($task in $tasks) {
    $info = Get-ScheduledTaskInfo -TaskName $task.TaskName
    Write-Host "   • $($task.TaskName)" -ForegroundColor White
    Write-Host "     Estado: $($task.State)" -ForegroundColor Gray
    Write-Host "     Última ejecución: $($info.LastRunTime)" -ForegroundColor Gray
    Write-Host "     Próxima ejecución: $($info.NextRunTime)" -ForegroundColor Gray
    Write-Host ""
}

Write-Host "🔍 Verificación en Programador de Tareas:" -ForegroundColor Yellow
Write-Host "   1. Presiona Win + R" -ForegroundColor Gray
Write-Host "   2. Escribe: taskschd.msc" -ForegroundColor Gray
Write-Host "   3. Busca las tareas que inician con 'Planilla'" -ForegroundColor Gray
Write-Host ""

Write-Host "📊 Monitoreo de logs:" -ForegroundColor Yellow
Write-Host "   Logs de sincronización: $LogPath\attendance_sync_*.log" -ForegroundColor Gray
Write-Host "   Logs de cron: Revisa el Programador de Tareas → Historial" -ForegroundColor Gray
Write-Host ""

Write-Host "🧪 Prueba manual (Opcional):" -ForegroundColor Yellow
Write-Host "   Ejecuta desde CMD/PowerShell:" -ForegroundColor Gray
Write-Host "   $PHPPath $ProjectPath\scripts\cron\sync_attendance.php" -ForegroundColor White
Write-Host "   $PHPPath $ProjectPath\scripts\cron\process_attendance_pipeline.php" -ForegroundColor White
Write-Host ""

Write-Host "✅ Configuración completada. Las tareas se ejecutarán automáticamente." -ForegroundColor Green
Write-Host ""

pause
