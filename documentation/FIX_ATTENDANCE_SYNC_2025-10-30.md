# Fix: Sincronización de Asistencias - 30 de Octubre, 2025

## 🐛 Problema Reportado
La sincronización de asistencias estaba omitiendo **todos** los registros:
```
Sincronización completa: 36 obtenidos, 0 insertados, 0 actualizados, 36 omitidos, 0 errores
```

Los registros no se insertaban en `attendance_records` ni en `attendance_detail`.

## 🔍 Diagnóstico

### Scripts de Diagnóstico Creados
1. **`test_sync_complete_flow.php`**: Test completo del flujo de sincronización
2. **`debug_sync_skipped.php`**: Análisis de por qué se omiten registros
3. **`test_single_record_insert.php`**: Prueba de inserción individual
4. **`test_punch_type_mapping.php`**: Verificación del mapeo de tipos
5. **`check_db_config.php`**: Verificación de configuración BD

### Investigación Realizada
- ✅ API funcionando correctamente (36 registros obtenidos)
- ✅ 144 registros en `attendance_raw_data` (todos sin procesar)
- ✅ 0 registros en `attendance_records` (problema principal)
- ✅ Empleados existentes en BD (33 de 36)
- ✅ Sin duplicados en attendance_records

### Causas Raíz Identificadas

#### **Problema #1: Mapeo Incorrecto de `punch_type`** ❌
**Archivo**: `app/Services/Attendance/AttendanceSyncService.php` línea 230

**Código Original**:
```php
$punchType = strtoupper($rawData['type'] ?? 'CHECK_IN');
```

**Problema**:
- La columna `punch_type` es un ENUM que solo acepta: `'CHECK_IN'`, `'CHECK_OUT'`
- API Base44 envía: `'entrada'`, `'salida'`, `'entrada_almuerzo'`, `'salida_almuerzo'`
- El código convertía a: `'ENTRADA'`, `'SALIDA'`, `'ENTRADA_ALMUERZO'`, `'SALIDA_ALMUERZO'`
- MySQL **rechazaba** estos valores por no coincidir con el ENUM

#### **Problema #2: Formato de Timestamp Incompatible** ❌
**Archivo**: `app/Services/Attendance/AttendanceSyncService.php` línea 236

**Código Original**:
```php
'timestamp' => $rawData['timestamp']
```

**Problema**:
- API envía: `2025-10-02T13:03:48.144Z` (ISO 8601 con timezone)
- MySQL espera: `2025-10-02 13:03:48` (datetime format)
- Error: `SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value`

## ✅ Solución Aplicada

### Fix #1: Método `mapPunchType()` para Conversión ENUM
**Archivo**: `app/Services/Attendance/AttendanceSyncService.php` líneas 310-332

```php
/**
 * Mapear tipo de marcación del API a ENUM punch_type
 * @param string $apiType Tipo desde API (entrada, salida, etc.)
 * @return string Valor ENUM (CHECK_IN o CHECK_OUT)
 */
private function mapPunchType($apiType)
{
    $apiType = strtolower(trim($apiType));

    // Mapeo de tipos Base44 a ENUM punch_type
    $mapping = [
        'entrada' => 'CHECK_IN',
        'entrada_almuerzo' => 'CHECK_IN',
        'check_in' => 'CHECK_IN',
        'in' => 'CHECK_IN',
        'salida' => 'CHECK_OUT',
        'salida_almuerzo' => 'CHECK_OUT',
        'check_out' => 'CHECK_OUT',
        'out' => 'CHECK_OUT'
    ];

    return $mapping[$apiType] ?? 'CHECK_IN';
}
```

**Cambio en `saveToRecords()`** línea 368:
```php
$punchType = $this->mapPunchType($rawData['type'] ?? 'entrada');
```

### Fix #2: Conversión de Timestamp ISO 8601 a MySQL Datetime
**Archivo**: `app/Services/Attendance/AttendanceSyncService.php` líneas 370-372

```php
// Convertir timestamp ISO 8601 a formato MySQL
$timestampObj = new \DateTime($timestamp);
$mysqlTimestamp = $timestampObj->format('Y-m-d H:i:s');
```

**Uso en `recordData`** líneas 374-386:
```php
$recordData = [
    'raw_data_id' => $rawDataId,
    'external_id' => $rawData['id'] ?? null,
    'employee_id' => $employee['id'],
    'timestamp' => $mysqlTimestamp,                    // ← Fix aplicado
    'punch_date' => $timestampObj->format('Y-m-d'),    // ← Usar objeto DateTime
    'punch_time' => $timestampObj->format('H:i:s'),    // ← Usar objeto DateTime
    'punch_type' => $punchType,                        // ← Mapeo aplicado
    // ...
];
```

## 📊 Resultados del Fix

### Antes del Fix
```
Obtenidos: 36
Insertados: 0
Omitidos: 36
Errores: 0
```

### Después del Fix
```
Obtenidos: 36
Insertados: 34
Omitidos: 2
Errores: 0
```

**Errores esperados** (2 omitidos):
- `c.qmacuart@gmail.com` - Empleado no existe en BD ✓ Correcto

### Verificación
```sql
SELECT COUNT(*) FROM attendance_records;
-- Antes: 0
-- Después: 34

SELECT r.id, CONCAT(e.firstname, ' ', e.lastname) as employee,
       r.punch_date, r.punch_time, r.punch_type
FROM attendance_records r
INNER JOIN employees e ON r.employee_id = e.id
ORDER BY r.id DESC LIMIT 5;
```

**Resultado**:
```
ID 35: KATHY MARGORIE GONZALEZ QUIROZ | 2025-10-30 17:54:47 | CHECK_IN
ID 34: KATHY MARGORIE GONZALEZ QUIROZ | 2025-10-30 17:04:56 | CHECK_OUT
ID 33: KATHY MARGORIE GONZALEZ QUIROZ | 2025-10-30 13:09:29 | CHECK_IN
ID 32: KATHY MARGORIE GONZALEZ QUIROZ | 2025-10-29 12:56:06 | CHECK_IN
ID 31: KATHY MARGORIE GONZALEZ QUIROZ | 2025-10-29 12:55:32 | CHECK_IN
```

## 🎯 Próximos Pasos

### 1. Limpiar Datos de Prueba (Opcional)
Si deseas resincronizar desde cero:
```bash
# Limpiar attendance_records
mysql> DELETE FROM attendance_records;

# Marcar raw_data como no procesado
mysql> UPDATE attendance_raw_data SET processed = 0, processed_at = NULL;
```

### 2. Ejecutar Sincronización Completa
```bash
# Desde UI
http://localhost/panel/attendance/sync

# O desde CLI
php scripts/sync_attendance.php
```

### 3. Procesar Records → Details
Una vez sincronizados los registros en `attendance_records`, procesarlos a `attendance_detail`:
- UI: `/panel/attendance/sync` → Pestaña "Procesamiento"
- CLI: `php scripts/process_attendance_records.php`

### 4. Verificar Integridad de Datos
```bash
php scripts/test_sync_complete_flow.php
```

## 📝 Archivos Modificados

### Producción
- ✅ `app/Services/Attendance/AttendanceSyncService.php` (2 fixes críticos)

### Scripts de Diagnóstico (Mantener para futuras referencias)
- `scripts/test_sync_complete_flow.php`
- `scripts/debug_sync_skipped.php`
- `scripts/test_single_record_insert.php`
- `scripts/test_punch_type_mapping.php`
- `scripts/test_punch_type_fix.php`
- `scripts/test_real_sync.php`
- `scripts/check_db_config.php`
- `scripts/check_raw_data.php`

### Scripts SQL (Ya existentes)
- `scripts/check_duplicate_records.sql`
- `scripts/reset_attendance_sync.sql`
- `scripts/clean_duplicate_records_only.sql`

## 🔒 Consideraciones de Seguridad

- ✅ No se usó `eval()` - solo conversión nativa DateTime
- ✅ Mapeo controlado con array asociativo
- ✅ Validación de ENUM en base de datos
- ✅ Prepared statements en todas las queries

## 📚 Referencias

- **ISO 8601**: Formato de timestamp internacional (usado por Base44)
- **MySQL ENUM**: Tipo de dato que restringe valores permitidos
- **PHP DateTime**: Conversión segura de formatos de fecha/hora

## ✅ Estado Final

**RESUELTO** ✓

La sincronización ahora funciona correctamente:
- 94% de registros insertados exitosamente (34 de 36)
- 6% omitidos por empleados faltantes (esperado)
- 0% errores técnicos
- Timestamps convertidos correctamente
- Punch types mapeados a ENUM correctamente

---
**Fecha de Fix**: 30 de Octubre, 2025
**Versión**: 3.5.1-hotfix
**Impacto**: Crítico (bloqueaba toda la sincronización)
**Tiempo de Diagnóstico**: ~1 hora
**Tiempo de Fix**: ~15 minutos
