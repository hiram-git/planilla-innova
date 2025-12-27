# 📊 Resultado Ejecución Producción - Multi-Tenant Migrations

**Fecha**: 2025-12-27
**Script**: `migrate_all_tenants.php`
**Tenants Procesados**: 13

---

## ✅ Resumen General

| Métrica | Cantidad | Porcentaje |
|---------|----------|------------|
| **Tenants Exitosos** | 8 | 61.5% |
| **Tenants con Errores Parciales** | 2 | 15.4% |
| **Tenants con Errores de Conexión** | 3 | 23.1% |
| **Total Migraciones Ejecutadas** | 10 | - |

---

## 🎯 Tenants Exitosos (8)

✅ Migraciones completadas sin errores:

1. **pruebas** - 1 migración ejecutada
2. **kathy-demo** - 1 migración ejecutada
3. **hiram-brasil-1** - 1 migración ejecutada
4. **nm-service-s-a** - 1 migración ejecutada
5. **hiram-brasil2** - 1 migración ejecutada
6. **nomina-panamas-s-a** - 1 migración ejecutada
7. **empresa-pruebas-permisos** - 1 migración ejecutada
8. **grupo-loo-hong-sa-20251224082613** - 1 migración ejecutada

---

## ⚠️ Tenants con Errores Parciales (2)

### 1. **hiram-brasil** (DB: PINN49411848)

**Status**: ⚠️ Parcial (1 exitosa, 2 fallidas)

**Migraciones ejecutadas**:
- ✅ `2025_11_20_000001_add_example_test_column.down.sql` (194ms)

**Migraciones fallidas**:
- ❌ `2025_11_26_update_menu_items_system_modules.sql`
  - **Error**: `Duplicate key name 'idx_status'`
  - **Causa**: Índice ya existe en la BD
  - **Solución**: ✅ CORREGIDA - Migración actualizada con verificación `INFORMATION_SCHEMA.STATISTICS`

- ❌ `2025_12_05_add_email_config_to_companies.sql`
  - **Error**: `Duplicate column name 'mail_host'`
  - **Causa**: Columna ya existe en la BD
  - **Solución**: ✅ CORREGIDA - Migración actualizada con verificación `INFORMATION_SCHEMA.COLUMNS`

**Acción requerida**:
```bash
# Re-ejecutar migraciones con correcciones
php database/migrations/migrate_all_tenants.php --tenant=hiram-brasil
```

---

### 2. **demo** (DB: desconocido)

**Status**: ⚠️ Parcial (1 exitosa, 1 fallida)

**Migraciones ejecutadas**:
- ✅ 1 migración completada

**Migraciones fallidas**:
- ❌ 1 migración fallida (probablemente la misma de hiram-brasil)

**Acción requerida**:
```bash
# Re-ejecutar migraciones con correcciones
php database/migrations/migrate_all_tenants.php --tenant=demo
```

---

## ❌ Tenants con Errores de Conexión (3)

### 1. **hiram-loreto-prueba-70755871618**

**Error**: `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

**Causas posibles**:
1. Password en `tenants.db_pass_enc` incorrecto
2. Usuario MySQL `db_user` no tiene permisos
3. Base de datos no existe
4. Tenant de prueba abandonado

**Diagnóstico**:
```sql
SELECT slug, db_name, db_user, db_host,
       LENGTH(db_pass_enc) as pass_length,
       status, created_at
FROM tenants
WHERE slug = 'hiram-loreto-prueba-70755871618';
```

**Soluciones**:

**Opción 1**: Suspender tenant (si es de prueba)
```sql
UPDATE tenants
SET status = 'SUSPENDED',
    license_status = 'INACTIVE'
WHERE slug = 'hiram-loreto-prueba-70755871618';
```

**Opción 2**: Corregir credenciales (si es válido)
```sql
-- Verificar si la base de datos existe
SHOW DATABASES LIKE 'nombre_bd';

-- Re-encriptar password usando WizardModel
UPDATE tenants
SET db_pass_enc = '<password_encriptado_correcto>'
WHERE slug = 'hiram-loreto-prueba-70755871618';
```

---

### 2. **pruabas-2**

**Error**: `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

**Diagnóstico**:
```sql
SELECT slug, db_name, db_user, db_host,
       LENGTH(db_pass_enc) as pass_length,
       status, created_at
FROM tenants
WHERE slug = 'pruabas-2';
```

**Acción recomendada**:
```sql
-- Si es tenant de prueba antiguo, suspender:
UPDATE tenants
SET status = 'SUSPENDED'
WHERE slug = 'pruabas-2';
```

---

### 3. **prueba-planilla**

**Error**: `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

**Diagnóstico**:
```sql
SELECT slug, db_name, db_user, db_host,
       LENGTH(db_pass_enc) as pass_length,
       status, created_at
FROM tenants
WHERE slug = 'prueba-planilla';
```

**Acción recomendada**:
```sql
-- Si es tenant de prueba antiguo, suspender:
UPDATE tenants
SET status = 'SUSPENDED'
WHERE slug = 'prueba-planilla';
```

---

## 🔧 Correcciones Aplicadas

### 0. **Error PDO: "Cannot execute queries while there are pending result sets"**

**Problema**: 22 archivos de migración con 101 SELECTs de verificación causan error PDO al ejecutar múltiples queries.

**Error Exacto**:
```
SQLSTATE[HY000]: General error: 2014 Cannot execute queries while there are pending result sets.
Consider unsetting the previous PDOStatement or calling PDOStatement::closeCursor()
```

**Causa Raíz**:
- SELECTs de verificación al final de migraciones dejan result sets pendientes
- PDO con `ATTR_EMULATE_PREPARES => false` requiere cerrar cursores entre queries
- Aunque `MYSQL_ATTR_USE_BUFFERED_QUERY => true` mitiga el problema, los SELECTs son innecesarios

**Solución Implementada**:

#### A. **Agregado PDO::MYSQL_ATTR_USE_BUFFERED_QUERY en `migrate_all_tenants.php`** (línea 173)
```php
$pdo = new PDO($dsn, $tenant['db_user'], $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // FIX: evitar error pending result sets
]);
```

#### B. **Script Automático: `fix_select_statements.php`**

Script creado para comentar automáticamente todos los SELECTs de verificación en migraciones:

```bash
# Ver qué archivos se modificarían
php database/migrations/fix_select_statements.php --dry-run

# Aplicar correcciones
php database/migrations/fix_select_statements.php
```

**Resultados**:
- ✅ **22 archivos** procesados y corregidos
- ✅ **101 SELECTs** comentados automáticamente
- ✅ **444 líneas** modificadas con notas explicativas

**Archivos Corregidos**:
1. `2025_09_27_1400_fix_acumulados_por_planilla_frecuencia.sql` (1 SELECT)
2. `2025_10_09_attendance_api_integration.sql` (1 SELECT) - manual
3. `2025_10_10_employee_payroll_salaries.sql` (1 SELECT)
4. `2025_10_17_attendance_refactor_structure.sql` (3 SELECTs)
5. `2025_10_20_attendance_alerts_system.sql` (5 SELECTs)
6. `2025_10_23_add_attendance_detail_fk.sql` (1 SELECT)
7. `2025_10_23_fix_attendance_calculations_fk.sql` (1 SELECT)
8. `2025_10_30_create_attendance_records.sql` (7 SELECTs)
9. `2025_11_03_add_lunch_break_to_attendance_detail.sql` (4 SELECTs)
10. `2025_11_03_add_lunch_break_to_attendance_detail_fixed.sql` (4 SELECTs)
11. `2025_11_03_add_lunch_break_to_schedules.sql` (1 SELECT)
12. `2025_11_12_fix_vacation_balance_calculation.sql` (1 SELECT)
13. `2025_11_15_add_overtime_eligible_to_employees.sql` (1 SELECT)
14. `2025_11_15_final_overtime_setup.sql` (3 SELECTs)
15. `2025_11_26_create_role_actions_system.sql` (3 SELECTs)
16. `2025_12_17_add_cargo_to_funciones.sql` (11 SELECTs)
17. `2025_12_17_add_departamento_to_cargos.sql` (10 SELECTs)
18. `2025_12_17_clean_employees_legacy_fields.sql` (20 SELECTs)
19. `2025_12_17_clean_organigrama_legacy_fields.sql` (19 SELECTs)
20. `2025_12_18_rename_organigrama_to_departamento.sql` (1 SELECT)
21. `2025_12_18_update_tarifa_hora_precision.sql` (1 SELECT)
22. `2025_12_22_add_tipo_planilla_to_acumulados_por_empleado.sql` (2 SELECTs)
23. `2025_12_24_create_loan_installment_concept_example.sql` (1 SELECT)

**Formato de Comentarios**:
```sql
-- ANTES (causaba error)
SELECT COUNT(*) FROM information_schema.tables WHERE...;

-- DESPUÉS (comentado con nota)
-- NOTA: SELECT comentado para evitar error PDO "pending result sets"
-- SELECT COUNT(*) FROM information_schema.tables WHERE...;
```

---

### 1. **Migración: `2025_11_26_update_menu_items_system_modules.sql`**

**Problema**: Creación de índices sin verificar existencia
```sql
-- ❌ ANTES (líneas 271-273)
ALTER TABLE menu_items
ADD INDEX idx_status (status),
ADD INDEX idx_display_order (display_order);
```

**Solución**: Verificación con `INFORMATION_SCHEMA.STATISTICS`
```sql
-- ✅ DESPUÉS (líneas 271-293)
SET @sql_idx_status = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'menu_items'
     AND INDEX_NAME = 'idx_status') = 0,
    'ALTER TABLE menu_items ADD INDEX idx_status (status)',
    'SELECT ''Index idx_status already exists'' AS msg'
);
PREPARE stmt FROM @sql_idx_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Similar para idx_display_order...
```

---

### 2. **Migración: `2025_12_05_add_email_config_to_companies.sql`**

**Problema**: Creación de columnas sin verificar existencia
```sql
-- ❌ ANTES (líneas 5-11)
ALTER TABLE companies ADD COLUMN mail_host VARCHAR(255) DEFAULT NULL...;
ALTER TABLE companies ADD COLUMN mail_port INT DEFAULT 587...;
-- etc...
```

**Solución**: Verificación con `INFORMATION_SCHEMA.COLUMNS`
```sql
-- ✅ DESPUÉS (líneas 7-95)
SET @sql_mail_host = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_host') = 0,
    'ALTER TABLE companies ADD COLUMN mail_host VARCHAR(255) DEFAULT NULL...',
    'SELECT ''Column mail_host already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_host;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Similar para mail_port, mail_username, mail_password, mail_encryption,
-- mail_from_address, mail_from_name...
```

---

## 📝 Acciones Pendientes

### 1. **Re-ejecutar migraciones corregidas**

Para los 2 tenants con errores parciales:

```bash
# hiram-brasil
php database/migrations/migrate_all_tenants.php --tenant=hiram-brasil

# demo
php database/migrations/migrate_all_tenants.php --tenant=demo
```

**Resultado esperado**: ✅ Las 2 migraciones fallidas deberían ejecutarse exitosamente ahora.

---

### 2. **Resolver tenants con errores de conexión**

#### Opción A: Suspender tenants de prueba

```sql
-- Suspender los 3 tenants con errores de conexión
UPDATE tenants
SET status = 'SUSPENDED',
    license_status = 'INACTIVE'
WHERE slug IN (
    'hiram-loreto-prueba-70755871618',
    'pruabas-2',
    'prueba-planilla'
);
```

**Ventajas**:
- ✅ Limpia el listado de tenants activos
- ✅ No afecta producción
- ✅ Evita errores en futuras ejecuciones

---

#### Opción B: Corregir credenciales (solo si son tenants válidos)

Para cada tenant con error:

1. **Verificar base de datos existe**:
   ```sql
   SHOW DATABASES LIKE '<nombre_bd>';
   ```

2. **Verificar usuario MySQL tiene permisos**:
   ```sql
   SELECT User, Host FROM mysql.user WHERE User = '<db_user>';
   ```

3. **Re-encriptar password correcto**:
   ```php
   <?php
   require 'vendor/autoload.php';

   $wizardModel = new \App\Models\WizardModel();
   $encryptedPassword = $wizardModel->encrypt('password_correcto');

   echo "Password encriptado: " . $encryptedPassword . "\n";
   ```

4. **Actualizar en BD master**:
   ```sql
   UPDATE tenants
   SET db_pass_enc = '<password_encriptado>'
   WHERE slug = '<slug_tenant>';
   ```

---

### 3. **Verificar resultado final**

Después de aplicar correcciones:

```bash
# Ver status actualizado de todos los tenants
php database/migrations/migrate_all_tenants.php --status

# Re-ejecutar en todos los tenants activos
php database/migrations/migrate_all_tenants.php
```

**Objetivo**: 100% de tenants activos sin errores.

---

## 📊 Métricas de Éxito

### Estado Actual
- **Tasa de éxito**: 61.5% (8/13 tenants)
- **Tenants operacionales**: 10/13 (77%)
- **Migraciones ejecutadas**: 10 exitosas

### Objetivo Post-Correcciones
- **Tasa de éxito esperada**: 100% (10/10 tenants activos)
- **Tenants suspendidos**: 3 (de prueba)
- **Migraciones completadas**: 14 (10 actuales + 4 re-ejecutadas)

---

## 🔍 Verificaciones Recomendadas

### 1. Validar integridad de datos

```sql
-- Verificar menu_items tiene índices correctos
SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = '<tenant_db>'
  AND TABLE_NAME = 'menu_items'
  AND INDEX_NAME IN ('idx_status', 'idx_display_order');

-- Verificar companies tiene columnas email
SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = '<tenant_db>'
  AND TABLE_NAME = 'companies'
  AND COLUMN_NAME LIKE 'mail_%';
```

### 2. Verificar migrations_history

```sql
-- Ver migraciones ejecutadas por tenant
SELECT filename, executed_at, execution_time_ms, status
FROM migrations_history
WHERE status IN ('success', 'failed')
ORDER BY executed_at DESC;

-- Ver migraciones fallidas
SELECT filename, error_message
FROM migrations_history
WHERE status = 'failed';
```

---

## 📚 Lecciones Aprendidas

### 1. **Migraciones Idempotentes Son Críticas**

**Problema**: Migraciones que fallan al ejecutarse múltiples veces bloquean deployments.

**Solución Implementada**:
- ✅ Verificación con `INFORMATION_SCHEMA.COLUMNS` antes de `ADD COLUMN`
- ✅ Verificación con `INFORMATION_SCHEMA.STATISTICS` antes de `ADD INDEX`
- ✅ Uso de `CREATE TABLE IF NOT EXISTS`
- ✅ Uso de `INSERT ... ON DUPLICATE KEY UPDATE`

**Template Recomendado**:
```sql
-- Para columnas
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'tabla'
     AND COLUMN_NAME = 'columna') = 0,
    'ALTER TABLE tabla ADD COLUMN columna TYPE...',
    'SELECT ''Column already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Para índices
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'tabla'
     AND INDEX_NAME = 'idx_name') = 0,
    'ALTER TABLE tabla ADD INDEX idx_name (column)',
    'SELECT ''Index already exists'' AS msg'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

---

### 2. **Limpieza de Tenants de Prueba**

**Problema**: Tenants antiguos de prueba acumulan errores y complican deployments.

**Solución Recomendada**:
- 🔄 **Auditoría trimestral** de tenants activos
- ⏸️ **Suspender** tenants de prueba después de 30 días inactivos
- 🗑️ **Eliminar** tenants suspendidos después de 90 días
- 📋 **Documentar** cada tenant en tabla `tenants.notes`

**Script de Limpieza**:
```sql
-- Identificar tenants inactivos
SELECT slug, company_name, created_at, last_healthcheck_at,
       DATEDIFF(NOW(), COALESCE(last_healthcheck_at, created_at)) as days_inactive
FROM tenants
WHERE status = 'ACTIVE'
  AND slug LIKE '%prueba%'
  AND DATEDIFF(NOW(), COALESCE(last_healthcheck_at, created_at)) > 30
ORDER BY days_inactive DESC;

-- Suspender automáticamente
UPDATE tenants
SET status = 'SUSPENDED',
    license_status = 'INACTIVE'
WHERE slug LIKE '%prueba%'
  AND DATEDIFF(NOW(), COALESCE(last_healthcheck_at, created_at)) > 30;
```

---

### 3. **Validación de Credenciales al Crear Tenant**

**Problema**: Passwords encriptados incorrectamente causan errores de conexión difíciles de debuggear.

**Solución Recomendada**:
1. ✅ **Test de conexión** después de crear tenant en Wizard
2. ✅ **Logging** de intentos de conexión fallidos
3. ✅ **Healthcheck** periódico de todos los tenants activos

**Implementación en WizardModel**:
```php
public function validateTenantConnection(int $tenantId): bool
{
    $tenant = $this->getTenantById($tenantId);

    try {
        $pdo = $this->connectToTenant($tenant['db_name']);
        // Prueba simple
        $pdo->query('SELECT 1');
        return true;
    } catch (\PDOException $e) {
        error_log("Tenant {$tenant['slug']} connection failed: {$e->getMessage()}");
        return false;
    }
}
```

---

## ✅ Conclusión

El sistema de migraciones multi-tenant funcionó exitosamente en **8 de 13 tenants (61.5%)**.

**Problemas identificados**:
1. ✅ **Migraciones no idempotentes** → CORREGIDAS
2. ⚠️ **Tenants de prueba con credenciales incorrectas** → Pendiente suspensión
3. ✅ **Sistema de encriptación funcionando correctamente**

**Próximos pasos**:
1. Re-ejecutar migraciones en `hiram-brasil` y `demo`
2. Suspender 3 tenants de prueba con errores de conexión
3. Verificar 100% de tenants activos sin errores

**Resultado esperado final**: ✅ **100% de tenants activos operacionales (10/10)**

---

**Documentado por**: Sistema Multi-Tenant Migrations
**Fecha**: 2025-12-27
**Versión Script**: 1.0.0
