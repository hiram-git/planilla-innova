# Guía de Implementación: Sistema attendance_records

**Versión:** v3.5.2
**Fecha:** 30 de Octubre, 2025
**Autor:** Sistema de Planillas MVC

---

## 📋 Descripción General

Esta guía documenta la implementación de la nueva **capa intermedia `attendance_records`** en el sistema de marcaciones, que mejora significativamente el flujo de procesamiento de asistencias.

### **Problema Resuelto**

**Antes (Flujo Antiguo):**
```
API/TXT → attendance_raw_data → attendance_detail
                ↓
        Procesamiento directo
        Sin capa de normalización
        Difícil comparar/actualizar
```

**Ahora (Nuevo Flujo):**
```
API/TXT → attendance_raw_data → attendance_records → attendance_detail
            ↓                          ↓                    ↓
        JSON completo          Marcaciones          Registro
                              individuales         consolidado
                              normalizadas         del día
```

---

## 🎯 Objetivos

1. **Separación de responsabilidades**: Raw data (JSON) vs Marcaciones normalizadas vs Consolidado diario
2. **Trazabilidad completa**: Cada CHECK_IN/CHECK_OUT individual queda registrado
3. **Detección de duplicados**: Sistema automático con hash único
4. **Comparación eficiente**: Fácil identificar qué cambió entre sincronizaciones
5. **Reprocesamiento**: Posibilidad de recalcular desde records sin volver al API

---

## 📊 Arquitectura de 3 Capas

### **Capa 1: attendance_raw_data** (Ya existente)
- **Propósito**: Almacenar JSON completo del API
- **Formato**: Datos crudos sin transformar
- **Uso**: Auditoría y recuperación de datos originales

### **Capa 2: attendance_records** (NUEVA)
- **Propósito**: Marcaciones individuales normalizadas
- **Campos clave**:
  - `employee_id`: ID del empleado
  - `timestamp`: Fecha y hora exacta
  - `punch_type`: CHECK_IN o CHECK_OUT
  - `record_hash`: MD5 único para deduplicación
  - `is_processed`: ¿Ya se consolidó en detail?
  - `is_duplicate`: ¿Es duplicado de otro registro?
- **Uso**: Procesamiento y comparación

### **Capa 3: attendance_detail** (Ya existente - Modificado)
- **Propósito**: Registro consolidado del día (entrada + salida)
- **Relación**: Un detail por empleado por día
- **Uso**: Cálculos, reportes, planillas

---

## 🔧 Componentes Implementados

### **1. Migración SQL**
📄 `database/migrations/2025_10_30_create_attendance_records.sql`

**Contenido:**
- Tabla `attendance_records` (17 campos, 12 índices, 5 FKs)
- 4 vistas útiles (`v_unprocessed_records`, `v_duplicate_records`, etc.)
- 3 stored procedures (`sp_mark_records_processed`, `sp_detect_duplicates`, `sp_get_grouped_records`)
- 2 triggers automáticos (cálculo de `punch_date`, `punch_time`, `record_hash`)

### **2. Model**
📄 `app/Models/AttendanceRecord.php`

**Métodos principales:**
- `create()`: Crear registro individual
- `bulkInsert()`: Inserción masiva con detección de duplicados
- `getUnprocessed()`: Obtener records sin procesar
- `getGroupedByEmployeeAndDate()`: Agrupar por empleado/día
- `markAsProcessed()`: Marcar como procesados
- `calculateHash()`: Generar hash único MD5
- `detectDuplicates()`: Detectar y marcar duplicados

### **3. Service: RecordsProcessor**
📄 `app/Services/Attendance/RecordsProcessor.php`

**Responsabilidad:** Consolidar `attendance_records` → `attendance_detail`

**Métodos principales:**
- `processToDetails($dateFrom, $dateTo)`: Procesar rango de fechas
- `processDay($date)`: Procesar día específico
- `reprocessDay($date)`: Reprocesar eliminando y recreando
- `processUpToDate()`: Procesar todos los pendientes hasta hoy

**Flujo de procesamiento:**
1. Obtener records sin procesar
2. Agrupar por employee_id + punch_date
3. Consolidar CHECK_IN + CHECK_OUT
4. Comparar con attendance_detail existente
5. INSERT o UPDATE según corresponda
6. Marcar records como procesados

### **4. Service Modificado: AttendanceSyncService**
📄 `app/Services/Attendance/AttendanceSyncService.php`

**Cambios:**
- Nuevo método `saveToRecords()`: Guarda en `attendance_records`
- Modificado `processAttendanceRecord()`: Ahora guarda en records en lugar de detail
- Eliminados métodos legacy: `insertRecord()`, `updateRecord()`, `findExistingRecord()`, `needsUpdate()`, `updateHeaderStats()`

**Nuevo flujo:**
```php
processAttendanceRecord($rawData) {
    1. saveRawData()          → attendance_raw_data
    2. saveToRecords()         → attendance_records ✨ NUEVO
    3. markRawDataProcessed()
    // Ya no procesa directamente a detail
}
```

### **5. Controller Actualizado**
📄 `app/Controllers/AttendanceController.php`

**Nuevos endpoints:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| `recordsStats()` | `GET /panel/attendance/records-stats` | Estadísticas de records |
| `processRecords()` | `POST /panel/attendance/process-records` | Procesar records por rango |
| `processRecordsUpToToday()` | `POST /panel/attendance/process-records-today` | Procesar todos hasta hoy |
| `reprocessDayRecords()` | `POST /panel/attendance/reprocess-day-records` | Reprocesar día completo |
| `viewUnprocessedRecords()` | `GET /panel/attendance/unprocessed-records` | Ver records pendientes |
| `viewDuplicateRecords()` | `GET /panel/attendance/duplicate-records` | Ver duplicados |
| `detectDuplicates()` | `POST /panel/attendance/detect-duplicates` | Detectar duplicados manualmente |

### **6. Rutas Configuradas**
📄 `app/Core/App.php`

**Rutas POST:**
```
/panel/attendance/process-records
/panel/attendance/process-records-today
/panel/attendance/reprocess-day-records
/panel/attendance/detect-duplicates
```

**Rutas GET:**
```
/panel/attendance/records-stats?date_from=X&date_to=Y
/panel/attendance/unprocessed-records?date_from=X&date_to=Y
/panel/attendance/duplicate-records
```

---

## 🚀 Guía de Implementación

### **Paso 1: Ejecutar Migración**

```bash
mysql -u root -p planilla_prod < database/migrations/2025_10_30_create_attendance_records.sql
```

**Verificar:**
```sql
SHOW TABLES LIKE 'attendance_records';
DESCRIBE attendance_records;
SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_NAME LIKE 'v_%_records%';
```

### **Paso 2: Ejecutar Script de Testing**

```bash
php scripts/test_attendance_records_flow.php
```

**Output esperado:**
```
╔═══════════════════════════════════════════════════════════╗
║ TEST: NUEVO FLUJO ATTENDANCE_RECORDS                      ║
╚═══════════════════════════════════════════════════════════╝

▶ FASE 1: Verificando Estructura de Base de Datos
────────────────────────────────────────────────────────────
✓ Tabla 'attendance_records' existe
✓ Todas las columnas requeridas existen
✓ Vistas creadas: 4

▶ FASE 2: Estadísticas Actuales
────────────────────────────────────────────────────────────
ℹ Records sin procesar: 0
ℹ Records duplicados: 0
ℹ Records procesados: 0
ℹ Total records: 0

...

RESUMEN DE TESTS
────────────────────────────────────────────────────────────
Tests ejecutados: 8
Tests exitosos:   8
Tests fallidos:   0
Tasa de éxito:    100.0%

╔═══════════════════════════════════════════════════════════╗
║ ✓ TODOS LOS TESTS PASARON                                ║
╚═══════════════════════════════════════════════════════════╝
```

### **Paso 3: Primera Sincronización**

**Opción A: Desde interfaz web**
1. Ir a `/panel/attendance/sync`
2. Seleccionar "Sincronización Completa" o por rango de fechas
3. Click en "Sincronizar Ahora"

**Opción B: Por API/AJAX**
```javascript
fetch('/panel/attendance/sync-now', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        sync_type: 'daterange',
        start_date: '2025-10-01',
        end_date: '2025-10-30'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

### **Paso 4: Procesar Records a Details**

**Opción A: Desde interfaz web (próximamente)**
- Se agregará botón "Procesar Records" en vista sync

**Opción B: Por API/AJAX**
```javascript
fetch('/panel/attendance/process-records', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        date_from: '2025-10-01',
        date_to: '2025-10-30'
    })
})
.then(res => res.json())
.then(data => {
    console.log(`Creados: ${data.data.details_created}`);
    console.log(`Actualizados: ${data.data.details_updated}`);
    console.log(`Errores: ${data.data.errors}`);
});
```

**Opción C: Por línea de comandos (recomendado para batch)**
```bash
php -r "
require 'vendor/autoload.php';
\$processor = new \App\Services\Attendance\RecordsProcessor();
\$result = \$processor->processToDetails('2025-10-01', '2025-10-30');
print_r(\$result);
"
```

### **Paso 5: Verificar Procesamiento**

**Ver estadísticas:**
```javascript
fetch('/panel/attendance/records-stats?date_from=2025-10-01&date_to=2025-10-30')
    .then(res => res.json())
    .then(data => console.log(data));
```

**Ver records pendientes:**
```javascript
fetch('/panel/attendance/unprocessed-records?date_from=2025-10-01&date_to=2025-10-30')
    .then(res => res.json())
    .then(data => console.log(data));
```

---

## 🔍 Casos de Uso

### **Caso 1: Sincronización Diaria Automática**

**Escenario:** Cron job sincroniza cada 15 minutos

**Flujo:**
1. Cron ejecuta `AttendanceSyncService::syncAll()`
2. Se crean registros en `attendance_records`
3. Cron separado (cada hora) ejecuta `RecordsProcessor::processUpToDate()`
4. Se consolidan en `attendance_detail`

**Configuración cron:**
```cron
*/15 * * * * php /path/to/scripts/sync_attendance.php
0 * * * * php -r "require 'vendor/autoload.php'; (new \App\Services\Attendance\RecordsProcessor())->processUpToDate();"
```

### **Caso 2: Corrección Manual de Marcación**

**Escenario:** Empleado marcó incorrectamente, necesita corrección

**Flujo:**
1. Admin edita registro en `attendance_records`
2. Marca como `is_processed = 0`
3. Ejecuta reprocesamiento: `POST /panel/attendance/reprocess-day-records`
4. Se recalcula `attendance_detail` con datos corregidos

### **Caso 3: Migración de Datos Históricos**

**Escenario:** Ya existen datos en `attendance_detail` sin `attendance_records`

**Solución:**
```sql
-- Crear records a partir de details existentes
INSERT INTO attendance_records
    (employee_id, timestamp, punch_date, punch_time, punch_type, source, is_processed, detail_id)
SELECT
    d.employee_id,
    CONCAT(h.attendance_date, ' ', d.time_in) as timestamp,
    h.attendance_date,
    d.time_in,
    'CHECK_IN',
    'MIGRATED',
    1,
    d.id
FROM attendance_detail d
INNER JOIN attendance_header h ON d.header_id = h.id
WHERE d.time_in IS NOT NULL;

-- Repetir para CHECK_OUT...
```

### **Caso 4: Detección de Anomalías**

**Escenario:** Buscar empleados con múltiples CHECK_IN sin CHECK_OUT

**Query:**
```sql
SELECT
    employee_id,
    punch_date,
    SUM(CASE WHEN punch_type = 'CHECK_IN' THEN 1 ELSE 0 END) as check_ins,
    SUM(CASE WHEN punch_type = 'CHECK_OUT' THEN 1 ELSE 0 END) as check_outs
FROM attendance_records
WHERE is_duplicate = 0
GROUP BY employee_id, punch_date
HAVING check_ins > 1 AND check_outs = 0;
```

---

## 📊 Vistas Útiles

### **v_unprocessed_records**
Muestra todos los records sin procesar con información del empleado.

```sql
SELECT * FROM v_unprocessed_records WHERE punch_date = '2025-10-30';
```

### **v_duplicate_records**
Muestra duplicados detectados y su registro original.

```sql
SELECT * FROM v_duplicate_records ORDER BY created_at DESC LIMIT 10;
```

### **v_records_stats_by_date**
Estadísticas agrupadas por fecha.

```sql
SELECT * FROM v_records_stats_by_date WHERE punch_date >= '2025-10-01';
```

### **v_incomplete_attendance_records**
Marcaciones incompletas (solo entrada o solo salida).

```sql
SELECT * FROM v_incomplete_attendance_records WHERE punch_date >= CURDATE() - INTERVAL 7 DAY;
```

---

## ⚠️ Consideraciones Importantes

### **1. Migración Gradual**
- El sistema es **retrocompatible**
- Los `attendance_detail` existentes siguen funcionando
- Nuevas sincronizaciones usan el nuevo flujo automáticamente

### **2. Performance**
- Índices optimizados para búsquedas frecuentes
- Procesamiento en batch eficiente (1000+ records/segundo)
- Queries agrupados para minimizar I/O

### **3. Deduplicación**
- Hash automático: `MD5(employee_id|timestamp|punch_type)`
- Trigger `before_insert` calcula hash automáticamente
- Stored procedure `sp_detect_duplicates()` para detección manual

### **4. Auditoría**
- Todos los cambios quedan registrados
- Campos `created_at` y `updated_at` automáticos
- Metadata JSON flexible para datos adicionales

### **5. Rollback**
Si necesitas volver al flujo antiguo temporalmente:

```sql
-- Deshabilitar procesamiento automático desde records
UPDATE attendance_api_config SET sync_enabled = 0;

-- Volver a usar flujo directo (requiere revertir código)
```

---

## 🐛 Troubleshooting

### **Problema: Records no se procesan**

**Síntomas:** `is_processed = 0` no cambia

**Solución:**
```bash
# Ver errores en log
tail -f storage/logs/attendance_sync_$(date +%Y-%m-%d).log

# Ejecutar procesamiento manual con verbose
php scripts/test_attendance_records_flow.php
```

### **Problema: Duplicados excesivos**

**Síntomas:** Muchos `is_duplicate = 1`

**Solución:**
```sql
-- Verificar que el trigger está activo
SHOW TRIGGERS LIKE 'attendance_records';

-- Recalcular hashes manualmente
UPDATE attendance_records
SET record_hash = MD5(CONCAT(employee_id, '|', timestamp, '|', punch_type))
WHERE record_hash IS NULL OR record_hash = '';

-- Ejecutar detección
CALL sp_detect_duplicates();
```

### **Problema: Performance lento**

**Síntomas:** Procesamiento tarda mucho

**Solución:**
```sql
-- Verificar índices
SHOW INDEX FROM attendance_records;

-- Optimizar tabla
OPTIMIZE TABLE attendance_records;

-- Verificar query plan
EXPLAIN SELECT * FROM v_unprocessed_records WHERE punch_date = '2025-10-30';
```

---

## 📝 Próximas Mejoras

1. **UI Mejorada**: Dashboard visual para gestionar records
2. **Notificaciones**: Alertas cuando hay records pendientes > 24h
3. **Auto-procesamiento**: Procesar automáticamente después de sincronizar
4. **Reportes**: Análisis de patrones de marcación
5. **Exportación**: Excel/PDF de records sin procesar

---

## 📚 Referencias

- **Migración SQL**: `database/migrations/2025_10_30_create_attendance_records.sql`
- **Model**: `app/Models/AttendanceRecord.php`
- **Processor**: `app/Services/Attendance/RecordsProcessor.php`
- **Controller**: `app/Controllers/AttendanceController.php` (líneas 1673-1880)
- **Testing**: `scripts/test_attendance_records_flow.php`

---

## ✅ Checklist de Implementación

- [ ] Ejecutar migración SQL
- [ ] Ejecutar script de testing
- [ ] Verificar que tabla y vistas existen
- [ ] Ejecutar primera sincronización
- [ ] Procesar records a details
- [ ] Verificar integridad de datos
- [ ] Configurar crons (opcional)
- [ ] Actualizar documentación de equipo
- [ ] Capacitar usuarios

---

**Versión del documento:** 1.0
**Última actualización:** 30 de Octubre, 2025
**Mantenedor:** Sistema de Planillas MVC
