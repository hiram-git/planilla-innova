# 📋 Guía Completa: Scripts Cron de Asistencias

## 🎯 Resumen General

El sistema de asistencias utiliza **3 scripts cron** que trabajan en secuencia para:
1. **Sincronizar** marcaciones desde API externa → `attendance_records`
2. **Procesar** marcaciones brutas → `attendance_detail` (consolidación)
3. **Calcular** métricas (horas, tardanzas, extras) → `attendance_calculations`

## 📊 Flujo de Datos

```
API Externa (Base44)
        ↓
┌─────────────────────────────────────────────────┐
│ 1. sync_attendance.php (cada 15 min)           │
│    - Obtiene marcaciones desde API              │
│    - Guarda en attendance_records               │
│    - Marca como is_processed = 0                │
└─────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────┐
│ 2. process_attendance_records.php (cada 20 min)│
│    - Lee attendance_records no procesados       │
│    - Agrupa por empleado + fecha                │
│    - Clasifica entrada/salida/almuerzo          │
│    - Crea/actualiza attendance_detail           │
│    - Marca records como is_processed = 1        │
└─────────────────────────────────────────────────┘
        ↓
┌─────────────────────────────────────────────────┐
│ 3. process_attendance_pipeline.php (cada 25min)│
│    - Lee attendance_detail                      │
│    - Calcula horas trabajadas, tardanzas        │
│    - Calcula horas extras (25%, 50%)            │
│    - Detecta ausencias automáticas              │
│    - Guarda en attendance_calculations          │
└─────────────────────────────────────────────────┘
        ↓
  attendance_calculations
  (listo para planilla)
```

---

## 1️⃣ sync_attendance.php

### 📝 Descripción
Script que sincroniza marcaciones desde una API externa (Base44) hacia la tabla `attendance_records`.

### 📍 Ubicación
`scripts/cron/sync_attendance.php`

### ⏰ Frecuencia Recomendada
**Cada 15 minutos**

### 🔧 Funcionalidad

**Proceso**:
1. Obtiene lista de tenants activos desde `planilla_master.tenants`
2. Para cada tenant:
   - Cambia conexión al tenant (`switchTenant()`)
   - Busca configuración activa en `attendance_api_config`
   - Verifica si sync está habilitado (`sync_enabled = 1`)
   - Verifica si debe sincronizar según intervalo configurado
   - Llama a `AttendanceSyncService->syncSince()` para obtener registros nuevos
   - Guarda resultados en `attendance_sync_log`

**Componentes utilizados**:
- `App\Core\MasterDatabase` - Conexión a base master
- `App\Core\TenantResolver` - Resolución de tenant
- `App\Models\AttendanceApiConfig` - Configuración API
- `App\Services\Attendance\AttendanceSyncService` - Servicio de sincronización

**Tablas afectadas**:
- ✅ **Inserta en**: `attendance_records`
- ✅ **Registra en**: `attendance_sync_log`
- ✅ **Lee de**: `attendance_api_config`

### 📊 Estadísticas Retornadas
```php
[
    'fetched' => int,      // Registros obtenidos de API
    'inserted' => int,     // Registros nuevos insertados
    'updated' => int,      // Registros actualizados (duplicados)
    'skipped' => int,      // Registros omitidos
    'errors' => int        // Errores encontrados
]
```

### ⚙️ Configuración Crontab (Linux)
```bash
# Cada 15 minutos
*/15 * * * * php /var/www/html/planilla-innova/scripts/cron/sync_attendance.php >> /var/log/cron_attendance.log 2>&1
```

### ⚙️ Configuración Task Scheduler (Windows)
```
Acción: Iniciar programa
Programa: C:\laragon60\bin\php\php.exe
Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\sync_attendance.php
Repetir cada: 15 minutos
```

### 🔍 Logs
El script genera salida estándar con formato:
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Sincronización de Asistencias desde API       ║
║   Fecha: 2025-12-26 10:15:00                              ║
╚════════════════════════════════════════════════════════════╝

══════════════════════════════════════════════════════
▶️  Tenant: PINN49411848
══════════════════════════════════════════════════════
✓ Configuración encontrada:
  - Proveedor: base44
  - Sincronización habilitada: Sí
  - Intervalo: 15 minutos

🚀 Iniciando sincronización...

✅ Sincronización completada para PINN49411848:
  - Registros obtenidos: 125
  - Registros insertados: 120
  - Registros actualizados: 5
  - Registros omitidos: 0
  - Errores: 0

⏱️  Tiempo de ejecución total: 3.45 segundos (2 tenants)
✓ Finalizado con código: 0
```

### 🚨 Condiciones de Salto
- No hay configuración activa → Salta tenant
- `sync_enabled = 0` → Salta tenant
- No es tiempo de sincronizar (basado en `sync_interval_minutes`) → Salta tenant

### 💡 Multitenancy
- ✅ Soporta múltiples tenants
- ✅ Cambia conexión automáticamente
- ✅ Procesa `planilla_prod` + todos los tenants activos
- ✅ Elimina duplicados automáticamente

---

## 2️⃣ process_attendance_records.php

### 📝 Descripción
Script que consolida marcaciones brutas (`attendance_records`) en registros de asistencia diaria (`attendance_detail`).

### 📍 Ubicación
`scripts/cron/process_attendance_records.php`

### ⏰ Frecuencia Recomendada
**Cada 20 minutos** (ejecutar DESPUÉS de sync_attendance.php)

### 🔧 Funcionalidad

**Proceso**:
1. Cuenta registros pendientes de procesar (`is_processed = 0`)
2. Si no hay registros pendientes → Sale
3. Obtiene rango de fechas con registros pendientes
4. Llama a `RecordsProcessor->processToDetails()` para:
   - Agrupar marcaciones por empleado + fecha
   - Clasificar como entrada/salida/almuerzo según horario
   - Crear o actualizar registros en `attendance_detail`
   - Marcar `attendance_records` como `is_processed = 1`

**Componentes utilizados**:
- `App\Services\Attendance\RecordsProcessor` - Procesador de marcaciones
- `App\Models\AttendanceRecord` - Modelo de marcaciones brutas

**Tablas afectadas**:
- ✅ **Lee de**: `attendance_records` (WHERE is_processed = 0)
- ✅ **Inserta/Actualiza**: `attendance_detail`
- ✅ **Actualiza**: `attendance_records.is_processed = 1`

### 📊 Estadísticas Retornadas
```php
[
    'total_records' => int,        // Total registros procesados
    'groups_processed' => int,     // Grupos (empleado+fecha) procesados
    'details_created' => int,      // Detalles nuevos creados
    'details_updated' => int,      // Detalles actualizados
    'details_skipped' => int,      // Detalles omitidos
    'records_marked' => int,       // Records marcados como procesados
    'errors' => int,               // Errores encontrados
    'errors_detail' => array       // Lista de errores
]
```

### ⚙️ Configuración Crontab (Linux)
```bash
# Cada 20 minutos
*/20 * * * * php /var/www/html/planilla-innova/scripts/cron/process_attendance_records.php >> /var/log/cron_process_records.log 2>&1
```

### ⚙️ Configuración Task Scheduler (Windows)
```
Acción: Iniciar programa
Programa: C:\laragon60\bin\php\php.exe
Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_records.php
Repetir cada: 20 minutos
```

### 🔍 Logs
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Procesamiento de Marcaciones                  ║
║   Fecha: 2025-12-26 10:20:00                              ║
╚════════════════════════════════════════════════════════════╝

📊 Registros pendientes: 350

📅 Rango de fechas a procesar:
  - Desde: 2025-12-25
  - Hasta: 2025-12-26

🚀 Iniciando procesamiento...

✅ Procesamiento completado:
  - Registros procesados: 350
  - Grupos procesados: 85
  - Details creados: 75
  - Details actualizados: 10
  - Details omitidos: 0
  - Records marcados: 350
  - Errores: 0

⏱️  Tiempo de ejecución: 5.67 segundos
✓ Finalizado con código: 0
```

### 🔑 Lógica de Clasificación
El script clasifica cada marcación según el horario del empleado:
- **Entrada**: Primera marcación del día (cerca de hora entrada)
- **Salida almuerzo**: Marcación cercana a hora salida almuerzo
- **Entrada almuerzo**: Marcación cercana a hora entrada almuerzo
- **Salida**: Última marcación del día (cerca de hora salida)

### 💡 Single Tenant
- ⚠️ **NO soporta multitenancy**
- ⚠️ Procesa solo el tenant actual de la sesión
- ⚠️ Debe ejecutarse en contexto del tenant correcto

---

## 3️⃣ process_attendance_pipeline.php

### 📝 Descripción
Script **maestro** que ejecuta el pipeline completo de procesamiento de asistencias: consolidación + cálculos + detección de ausencias.

### 📍 Ubicación
`scripts/cron/process_attendance_pipeline.php`

### ⏰ Frecuencia Recomendada
**Cada 25 minutos** con delay de 5 minutos después de sync

### 🔧 Funcionalidad

**PASO 1: Procesamiento de Marcaciones (Records → Details)**
1. Cuenta registros pendientes (`is_processed = 0`)
2. Obtiene rango de fechas pendientes
3. Llama a `RecordsProcessor->processToDetails()`
4. Consolida marcaciones en `attendance_detail`

**PASO 2: Cálculo de Horas y Tardanzas**
1. Obtiene días laborables de últimos 7 días (`BusinessCalendar`)
2. Para cada día laborable:
   - Obtiene detalles de asistencia del día
   - Para cada detalle:
     - Calcula horas trabajadas, tardanzas, salidas anticipadas
     - Calcula horas extras (25%, 50%)
     - Calcula horas nocturnas
     - Calcula score de puntualidad
     - Actualiza `attendance_detail` con métricas
     - Guarda en `attendance_calculations`

**Componentes utilizados**:
- `App\Core\MasterDatabase` - Conexión a base master
- `App\Core\TenantResolver` - Resolución de tenant
- `App\Services\Attendance\RecordsProcessor` - Procesador de records
- `App\Services\Attendance\Calculators\AttendanceCalculator` - Calculadora de métricas
- `App\Services\Attendance\Calculators\AbsenceDetector` - Detector de ausencias
- `App\Models\AttendanceRecord` - Modelo records
- `App\Models\AttendanceDetail` - Modelo detalles
- `App\Models\BusinessCalendar` - Calendario laboral

**Tablas afectadas**:
- ✅ **Lee de**: `attendance_records`, `attendance_detail`, `business_calendar`
- ✅ **Inserta/Actualiza**: `attendance_detail`, `attendance_calculations`
- ✅ **Actualiza**: `attendance_records.is_processed = 1`

### 📊 Estadísticas Retornadas

**PASO 1 (Records)**:
```php
[
    'groups_processed' => int,
    'details_created' => int,
    'details_updated' => int,
    'errors' => int
]
```

**PASO 2 (Cálculos)**:
```php
[
    'days_processed' => int,
    'days_skipped' => int,
    'total_details' => int,
    'calculations_saved' => int,
    'calculations_errors' => int,
    'absences_detected' => int,
    'omissions_marked' => int
]
```

### ⚙️ Configuración Crontab (Linux)
```bash
# Cada 25 minutos, iniciando en minuto 5
5,30,55 * * * * php /var/www/html/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /var/log/cron_pipeline.log 2>&1
```

### ⚙️ Configuración Task Scheduler (Windows)
```
Acción: Iniciar programa
Programa: C:\laragon60\bin\php\php.exe
Argumentos: C:\laragon60\www\planilla-innova\scripts\cron\process_attendance_pipeline.php
Repetir cada: 25 minutos
Retrasar: 5 minutos (ejecutar a las 00:05, 00:30, 00:55, etc.)
```

### 🔍 Logs
```
╔════════════════════════════════════════════════════════════╗
║   CRON JOB: Pipeline Completo de Procesamiento            ║
║   Fecha: 2025-12-26 10:25:00                              ║
╚════════════════════════════════════════════════════════════╝

══════════════════════════════════════════════════════
▶️  Tenant: PINN49411848
══════════════════════════════════════════════════════

🚀 PASO 1: Procesamiento de Marcaciones (Records → Details)
────────────────────────────────────────────────────────────
📊 Registros pendientes: 280
📅 Rango: 2025-12-25 → 2025-12-26

✅ Resultados:
  - Grupos procesados: 70
  - Details creados: 65
  - Details actualizados: 5
  - Errores: 0

⚡️  PASO 2: Cálculo de Horas y Tardanzas
────────────────────────────────────────────────────────────
📅 Analizando días laborables: 2025-12-19 → 2025-12-26
📊 Días laborables encontrados: 6

✓  2025-12-19: Procesando 35 marcaciones...
  ✅ 35 cálculos guardados
✓  2025-12-20: Procesando 40 marcaciones...
  ✅ 40 cálculos guardados
⏭️  2025-12-21: Sin marcaciones
✓  2025-12-23: Procesando 38 marcaciones...
  ✅ 38 cálculos guardados
✓  2025-12-24: Procesando 42 marcaciones...
  ✅ 42 cálculos guardados
✓  2025-12-26: Procesando 33 marcaciones...
  ✅ 33 cálculos guardados

📊 Resumen de Cálculos:
  - Días procesados: 5
  - Días sin datos: 1
  - Total marcaciones: 188
  - Cálculos guardados: 188
  - Errores: 0

══════════════════════════════════════════════════════
║   PIPELINE COMPLETADO                              ║
══════════════════════════════════════════════════════
⏱️  Tiempo total de ejecución: 12.34 segundos (2 tenants)
✓ Proceso finalizado con código 0
```

### 💡 Multitenancy
- ✅ Soporta múltiples tenants
- ✅ Cambia conexión automáticamente
- ✅ Procesa `planilla_prod` + todos los tenants activos
- ✅ Maneja errores por tenant sin detener el proceso completo

### 🎯 Ventajas del Pipeline
- ✅ **Todo-en-uno**: Un solo script ejecuta todo el flujo
- ✅ **Multitenancy**: Procesa todos los tenants automáticamente
- ✅ **Detección inteligente**: Solo procesa si hay datos pendientes
- ✅ **Robusto**: Maneja errores sin detener el proceso
- ✅ **Informativo**: Logs detallados por paso y tenant

---

## 📅 Recomendación de Programación

### Opción 1: Usar los 3 Scripts (Más Control)
```
Minuto 00: sync_attendance.php          (Sincroniza API)
Minuto 15: sync_attendance.php          (Sincroniza API)
Minuto 20: process_attendance_records.php (Procesa records)
Minuto 30: sync_attendance.php          (Sincroniza API)
Minuto 40: process_attendance_records.php (Procesa records)
Minuto 45: sync_attendance.php          (Sincroniza API)
```

**Crontab Linux**:
```bash
# Sincronización cada 15 minutos
*/15 * * * * php /var/www/html/planilla-innova/scripts/cron/sync_attendance.php >> /var/log/cron_attendance.log 2>&1

# Procesamiento cada 20 minutos
*/20 * * * * php /var/www/html/planilla-innova/scripts/cron/process_attendance_records.php >> /var/log/cron_process_records.log 2>&1
```

### Opción 2: Usar Pipeline + Sync (Recomendado)
```
Minuto 00: sync_attendance.php          (Sincroniza API)
Minuto 05: process_attendance_pipeline.php (Todo el pipeline)
Minuto 15: sync_attendance.php          (Sincroniza API)
Minuto 20: process_attendance_pipeline.php (Todo el pipeline) - OPCIONAL
Minuto 30: sync_attendance.php          (Sincroniza API)
Minuto 35: process_attendance_pipeline.php (Todo el pipeline)
Minuto 45: sync_attendance.php          (Sincroniza API)
```

**Crontab Linux**:
```bash
# Sincronización cada 15 minutos
*/15 * * * * php /var/www/html/planilla-innova/scripts/cron/sync_attendance.php >> /var/log/cron_attendance.log 2>&1

# Pipeline completo cada 30 minutos (con delay de 5 min)
5,35 * * * * php /var/www/html/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /var/log/cron_pipeline.log 2>&1
```

**Task Scheduler Windows**:
```
Tarea 1: sync_attendance.php
  - Inicio: 00:00
  - Repetir cada: 15 minutos
  - Duración: Indefinidamente

Tarea 2: process_attendance_pipeline.php
  - Inicio: 00:05
  - Repetir cada: 30 minutos
  - Duración: Indefinidamente
```

### Opción 3: Solo Pipeline (Más Simple)
Si no necesitas sincronización tan frecuente:

```bash
# Pipeline completo cada 30 minutos (incluye paso 1 de procesamiento)
*/30 * * * * php /var/www/html/planilla-innova/scripts/cron/process_attendance_pipeline.php >> /var/log/cron_pipeline.log 2>&1

# Sincronización manual cuando sea necesario
```

---

## 🔍 Verificación y Monitoreo

### Verificar que los scripts se ejecutaron
```bash
# Ver logs de cron (Linux)
tail -f /var/log/cron_attendance.log
tail -f /var/log/cron_process_records.log
tail -f /var/log/cron_pipeline.log

# Ver últimas sincronizaciones (SQL)
SELECT * FROM attendance_sync_log
ORDER BY sync_started_at DESC
LIMIT 10;
```

### Verificar registros pendientes
```sql
-- Records sin procesar
SELECT COUNT(*) as pending_records
FROM attendance_records
WHERE is_processed = 0 AND is_duplicate = 0;

-- Detalles sin cálculos (últimos 7 días)
SELECT ad.work_date, COUNT(*) as details_without_calc
FROM attendance_detail ad
LEFT JOIN attendance_calculations ac
  ON ac.employee_id = ad.employee_id
  AND ac.work_date = ad.work_date
WHERE ac.id IS NULL
  AND ad.work_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY ad.work_date;
```

### Verificar errores en logs
```bash
# Buscar líneas con ERROR o ❌ en logs
grep -i "error\|❌" /var/log/cron_attendance.log
grep -i "error\|❌" /var/log/cron_pipeline.log
```

---

## ⚠️ Troubleshooting

### Problema: "No hay registros pendientes" pero deberían haber
**Causa**: Los records ya fueron marcados como procesados
**Solución**:
```sql
-- Ver si hay records marcados como procesados hoy
SELECT COUNT(*), is_processed
FROM attendance_records
WHERE punch_date = CURDATE()
GROUP BY is_processed;

-- Si están todos procesados pero no hay detalles:
-- Resetear para reprocesar
UPDATE attendance_records
SET is_processed = 0
WHERE punch_date >= '2025-12-26'
  AND is_duplicate = 0;
```

### Problema: Script se ejecuta pero no sincroniza
**Causa**: `sync_enabled = 0` o no es tiempo de sincronizar
**Solución**:
```sql
-- Verificar configuración
SELECT * FROM attendance_api_config WHERE is_active = 1;

-- Habilitar sincronización
UPDATE attendance_api_config
SET sync_enabled = 1
WHERE is_active = 1;

-- Forzar próxima sincronización
UPDATE attendance_api_config
SET last_sync_at = DATE_SUB(NOW(), INTERVAL 1 HOUR)
WHERE is_active = 1;
```

### Problema: Tenant no encontrado
**Causa**: Multitenancy no configurado correctamente
**Solución**:
```sql
-- Verificar tenants activos en master
SELECT * FROM planilla_master.tenants WHERE status = 'ACTIVE';

-- O usar base por defecto en .env
DB_NAME=PINN49411848
```

### Problema: Errores de permisos en Linux
**Solución**:
```bash
# Dar permisos de ejecución
chmod +x scripts/cron/*.php

# Verificar que PHP CLI esté disponible
which php
php -v

# Probar ejecución manual
php scripts/cron/sync_attendance.php
```

---

## 📊 Métricas Calculadas

El pipeline calcula y guarda las siguientes métricas en `attendance_calculations`:

| Métrica | Descripción |
|---------|-------------|
| `hours_worked` | Horas trabajadas (time_out - time_in - almuerzo) |
| `tardiness_minutes` | Minutos de tardanza (entrada después de hora programada) |
| `early_departure_minutes` | Minutos de salida anticipada |
| `regular_hours` | Horas regulares (sin extras) |
| `overtime_hours_25` | Horas extras al 25% (primeras 3h) |
| `overtime_hours_50` | Horas extras al 50% (adicionales a 3h) |
| `night_hours` | Horas nocturnas (6PM-6AM) |
| `holiday_hours` | Horas trabajadas en feriados |
| `sunday_hours` | Horas trabajadas en domingos |
| `punctuality_score` | Score de puntualidad 0-100 |
| `lunch_duration_minutes` | Duración del almuerzo |
| `lunch_exceeded_minutes` | Minutos excedidos de almuerzo |

---

## 🎯 Conclusión

Los 3 scripts trabajan en conjunto para automatizar completamente el sistema de asistencias:

1. **sync_attendance.php**: Trae datos de la API → `attendance_records`
2. **process_attendance_records.php**: Consolida marcaciones → `attendance_detail`
3. **process_attendance_pipeline.php**: Pipeline completo (consolida + calcula)

**Recomendación**: Usar **Opción 2** (Pipeline + Sync) para balance entre frecuencia y carga del servidor.

---

**Fecha de documentación**: 26-Dic-2025
**Versión del sistema**: 3.5.14
**Scripts analizados**: 3
