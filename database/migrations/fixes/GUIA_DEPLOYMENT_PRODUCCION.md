# 🚀 GUÍA DE DEPLOYMENT - Fixes de Asistencias a Producción

**Versión:** v3.5.1
**Fecha:** 28 de Octubre, 2025
**Responsable:** Equipo Técnico
**Tiempo Estimado:** 15-30 minutos
**Ventana de Mantenimiento:** Requerida

---

## 📋 PRE-REQUISITOS

### 1. Verificaciones Previas
- [ ] Servidor de producción accesible
- [ ] MySQL 8.0+ instalado y corriendo
- [ ] Acceso root o privilegios de administrador en BD
- [ ] Espacio en disco suficiente (mínimo 500MB libre)
- [ ] Usuarios informados del mantenimiento

### 2. Archivos Necesarios
```
database/migrations/fixes/
├── 2025_10_28_fix_attendance_data_cleanup.sql    (Script principal)
├── GUIA_DEPLOYMENT_PRODUCCION.md                  (Este archivo)
```

### 3. Archivos de Código Actualizados
```
app/Services/Attendance/AttendanceSyncService.php  (línea 436 + líneas 268-275)
app/Controllers/AttendanceController.php           (línea 992)
```

---

## 🔒 PASO 1: BACKUP COMPLETO

### Opción A: Backup Completo de la Base de Datos

```bash
# En servidor de producción
cd /backup/mysql/

# Crear backup completo con fecha y hora
mysqldump -u root -p planilla_prod \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  > planilla_prod_backup_$(date +%Y%m%d_%H%M%S).sql

# Comprimir backup
gzip planilla_prod_backup_*.sql

# Verificar tamaño del archivo
ls -lh planilla_prod_backup_*.sql.gz
```

### Opción B: Backup Solo de Tablas Afectadas

```bash
mysqldump -u root -p planilla_prod \
  employees \
  attendance_header \
  attendance_detail \
  attendance_raw_data \
  > attendance_tables_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Verificar Backup

```bash
# Verificar que el archivo no esté vacío
wc -l attendance_tables_backup_*.sql

# Debería mostrar miles de líneas
```

---

## 🛑 PASO 2: DETENER SINCRONIZACIONES

### Opción A: Deshabilitar Cron Job Temporalmente

```bash
# Comentar el cron job de attendance
crontab -e

# Buscar la línea similar a:
# */15 * * * * /usr/bin/php /path/to/cron/attendance_sync.php

# Comentarla con #:
# # */15 * * * * /usr/bin/php /path/to/cron/attendance_sync.php

# Guardar y salir
```

### Opción B: Deshabilitar en Base de Datos

```sql
-- Conectar a MySQL
mysql -u root -p planilla_prod

-- Deshabilitar sincronización
UPDATE attendance_api_config
SET sync_enabled = 0
WHERE provider = 'base44';

-- Verificar
SELECT id, provider, sync_enabled FROM attendance_api_config;
```

---

## 🔧 PASO 3: ACTUALIZAR CÓDIGO DE APLICACIÓN

### 3.1. Actualizar AttendanceSyncService.php

**Archivo:** `app/Services/Attendance/AttendanceSyncService.php`

**Cambio 1 - Línea 436:**
```php
// ❌ ANTES (INCORRECTO)
'synced_from' => 'API_SYNC',

// ✅ DESPUÉS (CORRECTO)
'synced_from' => 'API',
```

**Cambio 2 - Líneas 268-275 (agregar normalización timestamp):**
```php
private function processAttendanceRecord($rawData)
{
    try {
        // AGREGAR ESTE BLOQUE AL INICIO
        // Normalizar timestamp (soportar timestamp, actual_timestamp, registered_timestamp)
        if (!isset($rawData['timestamp'])) {
            if (isset($rawData['actual_timestamp'])) {
                $rawData['timestamp'] = $rawData['actual_timestamp'];
            } elseif (isset($rawData['registered_timestamp'])) {
                $rawData['timestamp'] = $rawData['registered_timestamp'];
            }
        }

        // Validar que tenga los campos mínimos requeridos
        if (!isset($rawData['employee_email']) || !isset($rawData['timestamp'])) {
            // ... resto del código
```

### 3.2. Actualizar AttendanceController.php

**Archivo:** `app/Controllers/AttendanceController.php`

**Cambio - Línea 992:**
```php
// ❌ ANTES (INCORRECTO)
'synced_from' => 'MANUAL_PROCESSING',

// ✅ DESPUÉS (CORRECTO)
'synced_from' => 'MANUAL',
```

### 3.3. Verificar Cambios Localmente

```bash
# En tu entorno de desarrollo
cd /path/to/planilla-innova

# Verificar que los archivos fueron modificados
grep -n "synced_from.*API" app/Services/Attendance/AttendanceSyncService.php
grep -n "synced_from.*MANUAL" app/Controllers/AttendanceController.php
grep -n "actual_timestamp" app/Services/Attendance/AttendanceSyncService.php
```

### 3.4. Subir Cambios a Producción

```bash
# Opción A: Git (recomendado)
git add app/Services/Attendance/AttendanceSyncService.php
git add app/Controllers/AttendanceController.php
git commit -m "fix: Corregir valores synced_from y agregar soporte actual_timestamp"
git push origin main

# En producción
cd /path/to/planilla-innova
git pull origin main

# Opción B: FTP/SFTP
# Subir manualmente los 2 archivos modificados
```

---

## 📊 PASO 4: EJECUTAR SCRIPT DE MIGRACIÓN

### 4.1. Conectar a MySQL en Producción

```bash
mysql -u root -p planilla_prod
```

### 4.2. Cargar Script de Migración

```sql
-- Opción A: Desde consola MySQL
SOURCE /path/to/database/migrations/fixes/2025_10_28_fix_attendance_data_cleanup.sql;

-- Opción B: Desde línea de comandos
mysql -u root -p planilla_prod < database/migrations/fixes/2025_10_28_fix_attendance_data_cleanup.sql
```

### 4.3. Monitorear Ejecución

El script mostrará mensajes de progreso. **ESPERAR** a que termine completamente:

```
✓ Verificando versión MySQL...
✓ Verificando tablas requeridas...
✓ Backup creado - employees_backup_20251028
✓ Backup creado - attendance_header_backup_20251028
✓ Backup creado - attendance_detail_backup_20251028
✓ Emails actualizados
✓ synced_from corregidos
✓ Registros inválidos eliminados
✓ Registros raw marcados
✓ Estadísticas recalculadas
✓ Validaciones finales (todas deben dar 0 o el valor esperado)
✓ MIGRACIÓN COMPLETADA EXITOSAMENTE
```

---

## ✅ PASO 5: VALIDACIONES POST-MIGRACIÓN

### 5.1. Validaciones Automáticas en Script

El script incluye 4 validaciones automáticas. **Verificar que todas pasen:**

```sql
-- Ejecutar manualmente si es necesario
USE planilla_prod;

-- Validación 1: synced_from debe tener solo valores válidos (debe dar 0)
SELECT COUNT(*) as registros_incorrectos
FROM attendance_header
WHERE synced_from NOT IN ('API', 'FILE', 'MANUAL');

-- Validación 2: No debe haber registros inválidos (debe dar 0)
SELECT COUNT(*) as registros_invalidos
FROM attendance_detail
WHERE time_in IS NULL AND time_out IS NULL;

-- Validación 3: Emails actualizados (debe dar 3)
SELECT COUNT(*) as emails_correctos
FROM employees
WHERE (id = 2 AND email = 'soporte4nms@gmail.com')
   OR (id = 3 AND email = 'nmolina@nmspanama.com')
   OR (id = 5 AND email = 'dilsaquintana@gmail.com');

-- Validación 4: Integridad referencial (debe dar 0)
SELECT COUNT(*) as registros_huerfanos
FROM attendance_detail d
LEFT JOIN attendance_header h ON d.header_id = h.id
WHERE h.id IS NULL;
```

### 5.2. Verificar Resumen Final

```sql
-- Ver estadísticas finales
SELECT
    'Cabeceras totales' as metrica,
    COUNT(*) as valor
FROM attendance_header
UNION ALL
SELECT 'Detalles totales', COUNT(*) FROM attendance_detail
UNION ALL
SELECT 'Empleados con marcaciones', COUNT(DISTINCT employee_id) FROM attendance_detail
UNION ALL
SELECT 'Registros raw procesados', COUNT(*) FROM attendance_raw_data WHERE processed = 1
UNION ALL
SELECT 'Registros raw pendientes', COUNT(*) FROM attendance_raw_data WHERE processed = 0;
```

### 5.3. Verificar Backups Creados

```sql
-- Verificar que las tablas de backup existen
SHOW TABLES LIKE '%backup_20251028';

-- Deben aparecer 3 tablas:
-- employees_backup_20251028
-- attendance_header_backup_20251028
-- attendance_detail_backup_20251028
```

---

## 🔄 PASO 6: REACTIVAR SINCRONIZACIONES

### Opción A: Reactivar Cron Job

```bash
# Editar crontab
crontab -e

# Descomentar la línea del cron job
*/15 * * * * /usr/bin/php /path/to/cron/attendance_sync.php

# Guardar y salir
```

### Opción B: Reactivar en Base de Datos

```sql
UPDATE attendance_api_config
SET sync_enabled = 1
WHERE provider = 'base44';

-- Verificar
SELECT id, provider, sync_enabled, last_sync_at FROM attendance_api_config;
```

---

## 🧪 PASO 7: PRUEBAS POST-DEPLOYMENT

### 7.1. Ejecutar Sincronización Manual

```bash
# En servidor de producción
cd /path/to/planilla-innova
php database/scripts/sync_attendance_now.php
```

**Resultado esperado:**
```
========================================
SINCRONIZACIÓN DE ATTENDANCE
========================================

🔄 Iniciando sincronización...

========================================
RESULTADO DE SINCRONIZACIÓN
========================================
Registros obtenidos: X
Insertados: X
Actualizados: 0
Omitidos: X
Errores: 0 (o mínimo)
========================================

✅ Sincronización completada
```

### 7.2. Verificar Nuevas Cabeceras

```sql
-- Ver últimas cabeceras creadas
SELECT *
FROM attendance_header
ORDER BY created_at DESC
LIMIT 5;

-- Verificar que synced_from sea 'API'
SELECT synced_from, COUNT(*) as total
FROM attendance_header
GROUP BY synced_from;
```

### 7.3. Verificar Logs de Aplicación

```bash
# Revisar logs de sincronización
tail -f storage/logs/attendance_sync_$(date +%Y-%m-%d).log

# No debe haber errores de "Data truncated for column 'synced_from'"
```

---

## 🚨 ROLLBACK (En Caso de Problemas)

### Cuándo Hacer Rollback

- Si las validaciones post-migración fallan
- Si aparecen errores inesperados en producción
- Si los usuarios reportan problemas con asistencias

### Procedimiento de Rollback

```sql
USE planilla_prod;

-- PASO 1: Restaurar emails de empleados
UPDATE employees e
INNER JOIN employees_backup_20251028 b ON e.id = b.id
SET e.email = b.email;

-- PASO 2: Restaurar synced_from en attendance_header
UPDATE attendance_header h
INNER JOIN attendance_header_backup_20251028 b ON h.id = b.id
SET h.synced_from = b.synced_from;

-- PASO 3: Restaurar attendance_detail eliminados
INSERT INTO attendance_detail
SELECT * FROM attendance_detail_backup_20251028;

-- PASO 4: Restaurar attendance_raw_data
UPDATE attendance_raw_data
SET processed = 0,
    processed_at = NULL
WHERE processed = 1
  AND processed_at >= '2025-10-28 00:00:00';

-- Verificar rollback
SELECT 'Rollback completado' as estado, NOW() as fecha;
```

### Restaurar Código

```bash
# Si se hizo rollback de BD, también revertir código
git revert HEAD
git push origin main

# En producción
git pull origin main
```

---

## 📝 CHECKLIST FINAL

### Pre-Deployment
- [ ] Backup completo creado y verificado
- [ ] Usuarios notificados del mantenimiento
- [ ] Sincronizaciones detenidas
- [ ] Scripts y archivos listos

### Deployment
- [ ] Código actualizado en producción
- [ ] Script SQL ejecutado sin errores
- [ ] Todas las validaciones pasaron (4/4)
- [ ] Backups internos creados (3 tablas)

### Post-Deployment
- [ ] Sincronizaciones reactivadas
- [ ] Prueba de sincronización manual exitosa
- [ ] Logs sin errores
- [ ] Sistema funcionando normalmente
- [ ] Usuarios notificados del fin del mantenimiento

### Documentación
- [ ] Deployment registrado en bitácora
- [ ] Fecha y hora de ejecución documentada
- [ ] Resultados de validaciones guardados
- [ ] Backups etiquetados con fecha

---

## 📞 CONTACTOS DE EMERGENCIA

**Desarrollador Principal:** [Nombre]
**DBA:** [Nombre]
**Gerente de TI:** [Nombre]

---

## 📚 REFERENCIAS

- **Script SQL:** `database/migrations/fixes/2025_10_28_fix_attendance_data_cleanup.sql`
- **Documentación:** `documentation/changelog/v3.5.1.md`
- **Issue Tracker:** [Link al issue relacionado]

---

## ⏱️ TIEMPO ESTIMADO POR PASO

| Paso | Tiempo Estimado |
|------|-----------------|
| 1. Backup | 5-10 min |
| 2. Detener sync | 2 min |
| 3. Actualizar código | 5 min |
| 4. Ejecutar script | 2-5 min |
| 5. Validaciones | 3 min |
| 6. Reactivar sync | 2 min |
| 7. Pruebas | 5 min |
| **TOTAL** | **24-32 min** |

---

**✅ FIN DE LA GUÍA DE DEPLOYMENT**

_Última actualización: 28 de Octubre, 2025_
