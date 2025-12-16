# 📅 Configuración de Cronjobs - Sistema de Asistencias

**Versión**: 3.5.20
**Fecha**: 15 de Diciembre, 2025
**Estado**: ✅ Completado

---

## 📋 Descripción General

Este documento describe cómo configurar y ejecutar los **cronjobs automáticos** para el sistema de asistencias, que permite la sincronización y procesamiento completo end-to-end sin intervención manual.

---

## 🔄 Pipeline Completo de Asistencias

```
┌─────────────────────────────────────────────────────────────────┐
│                    PIPELINE AUTOMÁTICO                          │
│                    (Cada 15-20 minutos)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1️⃣ sync_attendance.php (Cada 15 min)                          │
│     📥 API Base44 → attendance_raw_data                         │
│     📥 API Base44 → attendance_records                          │
│     🔍 Detectar ausencias automáticas                           │
│                                                                  │
│  2️⃣ process_attendance_pipeline.php (Cada 25 min, +5min delay) │
│     📊 attendance_records → attendance_detail                   │
│     ⏱️  Calcular horas trabajadas, tardanzas, extras           │
│     💾 Guardar en attendance_calculations                       │
│     🔍 Detectar omisiones (marcaciones incompletas)            │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 📦 Scripts Disponibles

### 1. `sync_attendance.php` ✅ (YA EXISTÍA)
**Ruta**: `scripts/cron/sync_attendance.php`
**Frecuencia recomendada**: Cada 15 minutos
**Función**:
- Sincronizar marcaciones desde API Base44
- Guardar en `attendance_raw_data` y `attendance_records`
- Detectar ausencias automáticamente
- Actualizar `attendance_sync_log`

**Dependencias**:
- Configuración activa en `attendance_api_config`
- API Base44 accesible

---

### 2. `process_attendance_records.php` 🆕 (NUEVO)
**Ruta**: `scripts/cron/process_attendance_records.php`
**Frecuencia recomendada**: Cada 20 minutos
**Función**:
- Consolidar `attendance_records` → `attendance_detail`
- Agrupar marcaciones por empleado y fecha
- Clasificar entrada/salida/almuerzo
- Marcar records como procesados

**Dependencias**:
- Registros sin procesar en `attendance_records`
- Script #1 debe ejecutarse primero

---

### 3. `process_attendance_pipeline.php` 🆕 ⭐ (RECOMENDADO - TODO EN UNO)
**Ruta**: `scripts/cron/process_attendance_pipeline.php`
**Frecuencia recomendada**: Cada 25 minutos (con delay de 5 minutos después de sync)
**Función**:
- **Pipeline completo en un solo script**
- Paso 1: Procesar records → details
- Paso 2: Calcular horas, tardanzas, extras
- Paso 3: Detectar omisiones
- Paso 4: Guardar en attendance_calculations

**Dependencias**:
- Script #1 debe ejecutarse primero
- Empleados con `schedule_id` asignado

---

## 🖥️ Configuración en Windows (Programador de Tareas)

### Opción A: Configuración Automática (Recomendado)

Ejecutar el siguiente script PowerShell **como Administrador**:

```powershell
# ============================================
# Script: crear_tareas_asistencias.ps1
# Ubicación: scripts/cron/
# ============================================

# Variables de configuración
$PHPPath = "C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe"
$ProjectPath = "C:\laragon60\www\planilla-innova"
$LogPath = "C:\laragon60\www\planilla-innova\storage\logs"

# Tarea 1: Sincronización API (cada 15 minutos)
$Action1 = New-ScheduledTaskAction -Execute $PHPPath `
    -Argument "$ProjectPath\scripts\cron\sync_attendance.php" `
    -WorkingDirectory $ProjectPath

$Trigger1 = New-ScheduledTaskTrigger -Once -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 15) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

Register-ScheduledTask -TaskName "Planilla - Sincronizar Asistencias" `
    -Action $Action1 `
    -Trigger $Trigger1 `
    -Description "Sincronizar marcaciones desde API Base44 cada 15 minutos" `
    -User "SYSTEM" `
    -RunLevel Highest

# Tarea 2: Procesamiento Pipeline (cada 25 minutos, delay 5 min)
$Action2 = New-ScheduledTaskAction -Execute $PHPPath `
    -Argument "$ProjectPath\scripts\cron\process_attendance_pipeline.php" `
    -WorkingDirectory $ProjectPath

$Trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(5) `
    -RepetitionInterval (New-TimeSpan -Minutes 25) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

Register-ScheduledTask -TaskName "Planilla - Procesar Asistencias Pipeline" `
    -Action $Action2 `
    -Trigger $Trigger2 `
    -Description "Procesar marcaciones, calcular horas y tardanzas cada 25 minutos" `
    -User "SYSTEM" `
    -RunLevel Highest

Write-Host "✅ Tareas programadas creadas exitosamente!" -ForegroundColor Green
Write-Host "📝 Revisa el Programador de Tareas de Windows para verificar." -ForegroundColor Yellow
```

### Opción B: Configuración Manual (Paso a Paso)

#### Tarea 1: Sincronización API

1. Abrir **Programador de Tareas** (Task Scheduler)
2. Clic derecho en "Biblioteca del Programador de Tareas" → **Crear tarea básica...**
3. **Nombre**: `Planilla - Sincronizar Asistencias`
4. **Desencadenador**: Diariamente, repetir cada 15 minutos indefinidamente
5. **Acción**: Iniciar un programa
   - **Programa**: `C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe`
   - **Argumentos**: `C:\laragon60\www\planilla-innova\scripts\cron\sync_attendance.php`
   - **Iniciar en**: `C:\laragon60\www\planilla-innova`
6. **Configuraciones avanzadas**:
   - ✅ Ejecutar aunque el usuario no haya iniciado sesión
   - ✅ Ejecutar con los privilegios más altos
   - ✅ Configurado para: Windows 10/11

#### Tarea 2: Procesamiento Pipeline

1. Repetir pasos 1-3
2. **Nombre**: `Planilla - Procesar Asistencias Pipeline`
3. **Desencadenador**: Diariamente, repetir cada 25 minutos con **retraso de 5 minutos**
4. **Acción**: Iniciar un programa
   - **Programa**: `C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe`
   - **Argumentos**: `C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_pipeline.php`
   - **Iniciar en**: `C:\laragon60\www\planilla-innova`
5. Aplicar mismas configuraciones avanzadas

---

## 🐧 Configuración en Linux (Crontab)

### Editar Crontab

```bash
crontab -e
```

### Agregar las siguientes líneas:

```bash
# ============================================
# Sistema de Asistencias - Planilla Innova
# ============================================

# 1. Sincronizar marcaciones desde API cada 15 minutos
*/15 * * * * /usr/bin/php /var/www/planilla-innova/scripts/cron/sync_attendance.php >> /var/log/planilla/cron_sync.log 2>&1

# 2. Procesar pipeline completo cada 25 minutos (delay 5 min)
5,30,55 * * * * /usr/bin/php /var/www/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /var/log/planilla/cron_pipeline.log 2>&1
```

### Verificar Crontab

```bash
crontab -l
```

### Crear directorio de logs

```bash
sudo mkdir -p /var/log/planilla
sudo chown www-data:www-data /var/log/planilla
sudo chmod 755 /var/log/planilla
```

---

## 📊 Monitoreo y Logs

### Ubicación de Logs

**Windows**:
- Manual: `C:\laragon60\www\planilla-innova\storage\logs\cron_*.log`
- Output scripts: Configurar redirección en PowerShell

**Linux**:
- `/var/log/planilla/cron_sync.log`
- `/var/log/planilla/cron_pipeline.log`

### Verificar ejecución

**Windows** (PowerShell):
```powershell
Get-ScheduledTask | Where-Object {$_.TaskName -like "*Planilla*"}
Get-ScheduledTask "Planilla - Sincronizar Asistencias" | Get-ScheduledTaskInfo
```

**Linux**:
```bash
grep CRON /var/log/syslog | grep attendance
tail -f /var/log/planilla/cron_sync.log
tail -f /var/log/planilla/cron_pipeline.log
```

### Logs en Base de Datos

**Tabla**: `attendance_sync_log`
```sql
SELECT *
FROM attendance_sync_log
ORDER BY start_time DESC
LIMIT 10;
```

---

## 🧪 Prueba Manual de Scripts

Antes de programar los cronjobs, probar manualmente:

### Windows (CMD/PowerShell):
```cmd
cd C:\laragon60\www\planilla-innova
C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe scripts\cron\sync_attendance.php
C:\laragon60\bin\php\php-8.3.11-Win32-vs16-x64\php.exe scripts\cron\process_attendance_pipeline.php
```

### Linux (Bash):
```bash
cd /var/www/planilla-innova
/usr/bin/php scripts/cron/sync_attendance.php
/usr/bin/php scripts/cron/process_attendance_pipeline.php
```

**Salida esperada**:
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Pipeline Completo de Procesamiento...         ║
║   Fecha: 2025-12-15 14:30:00                              ║
╚════════════════════════════════════════════════════════════╝

📋 PASO 1: Procesamiento de Marcaciones (Records → Details)
────────────────────────────────────────────────────────────
✓ Registros pendientes: 45
📅 Rango: 2025-12-10 → 2025-12-15
✅ Resultados:
  - Grupos procesados: 12
  - Details creados: 10
  - Details actualizados: 2
  - Errores: 0

⏱️  PASO 2: Cálculo de Horas y Tardanzas
────────────────────────────────────────────────────────────
...
```

---

## ⚠️ Troubleshooting

### Problema 1: Script no se ejecuta

**Causa**: Permisos insuficientes

**Solución Windows**:
- Ejecutar Task Scheduler como Administrador
- Verificar que la tarea esté configurada con "Ejecutar con los privilegios más altos"

**Solución Linux**:
```bash
sudo chmod +x /var/www/planilla-innova/scripts/cron/*.php
sudo chown www-data:www-data /var/www/planilla-innova/scripts/cron/*.php
```

### Problema 2: Error "Class not found"

**Causa**: Composer autoload no cargado

**Solución**:
```bash
cd /var/www/planilla-innova
composer dump-autoload
```

### Problema 3: No hay registros pendientes

**Causa**: Sincronización API no funciona

**Verificar**:
1. Configuración activa en `attendance_api_config`
2. API Key válida en `.env`
3. Conexión a API Base44 funcional

```sql
SELECT * FROM attendance_api_config WHERE is_active = 1;
SELECT * FROM attendance_sync_log ORDER BY start_time DESC LIMIT 5;
```

### Problema 4: Errores de cálculo

**Causa**: Empleados sin horario asignado

**Solución**:
```sql
-- Verificar empleados sin schedule_id
SELECT id, CONCAT(firstname, ' ', lastname) as nombre, schedule_id
FROM employees
WHERE marca_asistencia = 1 AND schedule_id IS NULL;

-- Asignar horario por defecto (ejemplo: schedule_id = 1)
UPDATE employees
SET schedule_id = 1
WHERE marca_asistencia = 1 AND schedule_id IS NULL;
```

---

## 🔐 Consideraciones de Seguridad

1. **Permisos de archivos**:
   - Scripts: 755 (rwxr-xr-x)
   - Logs: 644 (rw-r--r--)
   - Directorio logs: 755

2. **Usuario de ejecución**:
   - Windows: SYSTEM o usuario de servicio
   - Linux: www-data (mismo que Apache/Nginx)

3. **Validación CSRF**:
   - Los scripts CLI no requieren CSRF (ejecutan fuera de contexto HTTP)
   - Validar `php_sapi_name() === 'cli'` en cada script

4. **Logs sensibles**:
   - No registrar passwords o API keys
   - Rotar logs automáticamente (logrotate en Linux)

---

## 📈 Métricas y Estadísticas

### Estadísticas de Sincronización

```sql
SELECT
    DATE(start_time) as fecha,
    COUNT(*) as ejecuciones,
    SUM(records_fetched) as registros_obtenidos,
    SUM(records_inserted) as registros_insertados,
    SUM(errors_count) as errores_totales,
    AVG(duration_seconds) as promedio_duracion_seg
FROM attendance_sync_log
WHERE start_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(start_time)
ORDER BY fecha DESC;
```

### Empleados con Marcaciones Recientes

```sql
SELECT
    e.id,
    CONCAT(e.firstname, ' ', e.lastname) as nombre,
    COUNT(DISTINCT r.punch_date) as dias_con_marcaciones,
    MAX(r.timestamp) as ultima_marcacion
FROM employees e
INNER JOIN attendance_records r ON e.id = r.employee_id
WHERE r.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY e.id
ORDER BY dias_con_marcaciones DESC;
```

---

## 📝 Changelog

### V3.5.20 (15-Dic-2025)
- ✅ Creado `process_attendance_records.php`
- ✅ Creado `process_attendance_pipeline.php` (pipeline completo)
- ✅ Agregados métodos `countUnprocessed()` y `getUnprocessedDateRange()` a AttendanceRecord
- ✅ Documentación completa Windows y Linux
- ✅ Scripts PowerShell para automatización Windows

### Anterior
- ✅ Script `sync_attendance.php` existente desde V3.5.1

---

## 🆘 Soporte

Para asistencia técnica o reportar problemas:
- **Email**: soporte@planilla-innova.com
- **GitHub Issues**: https://github.com/planilla-innova/issues
- **Documentación**: `documentation/`

---

**Última actualización**: 15 de Diciembre, 2025
**Mantenido por**: Equipo de Desarrollo Planilla Innova
