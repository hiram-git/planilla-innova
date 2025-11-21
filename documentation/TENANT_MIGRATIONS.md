# 🔄 Sistema de Migraciones Tenant

**Sistema de Planillas MVC - Versión 3.5.8**
Última actualización: 20 de Noviembre, 2025

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura](#arquitectura)
3. [Instalación](#instalación)
4. [Guía de Uso](#guía-de-uso)
5. [Formato de Migraciones](#formato-de-migraciones)
6. [Comandos CLI](#comandos-cli)
7. [Mejores Prácticas](#mejores-prácticas)
8. [Troubleshooting](#troubleshooting)
9. [FAQ](#faq)

---

## Introducción

El Sistema de Migraciones Tenant permite gestionar cambios de esquema de base de datos de forma segura y consistente across múltiples bases de datos tenant en un entorno multi-tenancy.

### Características Principales

- ✅ **Parser SQL Robusto**: Maneja statements multi-línea y comentarios
- ✅ **Tracking de Versiones**: Tabla `schema_migrations` por tenant
- ✅ **Transacciones Atómicas**: Rollback automático en errores
- ✅ **Dry-Run Mode**: Preview sin ejecutar cambios
- ✅ **Rollback Support**: Archivos `.down.sql` para revertir
- ✅ **Checksum Validation**: Detecta cambios en migraciones ejecutadas
- ✅ **Batch Execution**: Aplica migraciones a múltiples tenants simultáneamente
- ✅ **Logging Detallado**: Archivos de log por fecha

---

## Arquitectura

### Componentes del Sistema

```
app/Core/
├─ SqlImporter.php              # Parser y ejecutor SQL robusto (333 líneas)
└─ TenantMigrationSystem.php    # Coordinador principal (438 líneas)

bin/
├─ migrate-tenants.php          # CLI principal (160 líneas)
└─ test-migration-system.php    # Script de testing (277 líneas)

database/migrations/tenant/
├─ 2025_11_20_000001_add_column.sql       # Migración UP
└─ 2025_11_20_000001_add_column.down.sql  # Migración DOWN (rollback)
```

### Flujo de Datos

```
┌─────────────────┐
│   CLI Command   │
│  php bin/...    │
└────────┬────────┘
         │
         v
┌────────────────────────┐
│ TenantMigrationSystem  │
│ - Discovery migraciones│
│ - Iteración tenants    │
│ - Status tracking      │
└────────┬───────────────┘
         │
         v
┌─────────────────┐      ┌──────────────────┐
│   SqlImporter   │─────▶│  Tenant Database │
│ - Parse SQL     │      │  schema_migrations│
│ - Execute       │      │  + user tables    │
│ - Transacciones │      └──────────────────┘
└─────────────────┘
```

---

## Instalación

### Requisitos

- PHP 8.0+
- MySQL 5.7+ / 8.0+
- PDO extension habilitada
- Permisos de escritura en `database/migrations/tenant/` y `storage/logs/`

### Setup Inicial

1. **Verificar directorios**:
```bash
mkdir -p database/migrations/tenant
mkdir -p storage/logs
chmod 755 database/migrations/tenant storage/logs
```

2. **Probar sistema**:
```bash
php bin/test-migration-system.php
```

Expected output: `✅ TODOS LOS TESTS PASARON`

---

## Guía de Uso

### 1. Crear una Migración

**Archivo**: `database/migrations/tenant/2025_11_20_000002_add_overtime_column.sql`

```sql
-- version: 2025_11_20_000002
-- description: Add overtime_eligible column to employees

ALTER TABLE employees
ADD COLUMN overtime_eligible TINYINT(1) DEFAULT 1
COMMENT 'Empleado elegible para horas extras';

ALTER TABLE employees
ADD INDEX idx_employees_overtime (overtime_eligible);
```

**Archivo Rollback**: `database/migrations/tenant/2025_11_20_000002_add_overtime_column.down.sql`

```sql
-- rollback for: 2025_11_20_000002
-- description: Remove overtime_eligible column

ALTER TABLE employees DROP INDEX idx_employees_overtime;
ALTER TABLE employees DROP COLUMN overtime_eligible;
```

### 2. Preview de Migraciones (Dry-Run)

```bash
php bin/migrate-tenants.php migrate --dry-run
```

Output:
```
=== TENANT MIGRATION SYSTEM ===
Tenants: 3 active
Migrations: 1 discovered
Mode: DRY-RUN (preview only)

Processing tenant: Empresa ABC (planilla_tenant_abc123)...
  🔍 DRY-RUN: Would execute 1 migrations:
    - 2025_11_20_000002: Add overtime_eligible column to employees

Processing tenant: Empresa XYZ (planilla_tenant_xyz456)...
  ⏭️  No pending migrations
```

### 3. Ejecutar Migraciones

```bash
php bin/migrate-tenants.php migrate
```

Output:
```
Processing tenant: Empresa ABC (planilla_tenant_abc123)...
    ✓ 2025_11_20_000002: Add overtime_eligible column to employees
      (2 statements, 156.32ms)
  ✅ SUCCESS: 1 migrations executed

=== SUMMARY ===
Success: 1 tenants
Failed: 0 tenants
Skipped: 2 tenants (no pending migrations)
Total time: 1.23s
```

### 4. Verificar Estado

**Todos los tenants**:
```bash
php bin/migrate-tenants.php status
```

Output:
```
=== MIGRATION STATUS (All Tenants) ===
Total available migrations: 2

✅ UP-TO-DATE | Empresa ABC
  Database: planilla_tenant_abc123
  Executed: 2 | Pending: 0

⚠️  PENDING | Empresa XYZ
  Database: planilla_tenant_xyz456
  Executed: 1 | Pending: 1
```

**Tenant específico**:
```bash
php bin/migrate-tenants.php status planilla_tenant_abc123
```

Output:
```
=== MIGRATION STATUS: planilla_tenant_abc123 ===

✓ 2025_11_20_000002: Add overtime_eligible column to employees
  Executed: 2025-11-20 14:32:15 (156ms)

✓ 2025_11_20_000001: Add test column for migration system validation
  Executed: 2025-11-20 12:10:05 (225ms)

Total: 2 migrations executed
```

### 5. Rollback de Migraciones

```bash
php bin/migrate-tenants.php rollback planilla_tenant_abc123 1
```

Output:
```
=== ROLLBACK: planilla_tenant_abc123 ===
Steps to rollback: 1

Rolling back: 2025_11_20_000002 - Add overtime_eligible column to employees
  ✅ Rolled back successfully (2 statements)

=== ROLLBACK COMPLETED ===
```

---

## Formato de Migraciones

### Convenciones de Nombres

**Formato**: `YYYY_MM_DD_NNNNNN_description.sql`

- `YYYY_MM_DD`: Fecha de creación
- `NNNNNN`: Número secuencial (000001, 000002, etc.)
- `description`: Descripción corta en snake_case

**Ejemplos**:
- ✅ `2025_11_20_000001_add_overtime_column.sql`
- ✅ `2025_11_21_000001_create_audit_log_table.sql`
- ❌ `add-column.sql` (sin fecha ni número)
- ❌ `2025-11-20-overtime.sql` (guiones en lugar de underscores)

### Metadata en Comentarios

**Obligatorio** al inicio del archivo:

```sql
-- version: 2025_11_20_000001
-- description: Brief description of what this migration does
```

**Opcional**:
```sql
-- author: John Doe
-- date: 2025-11-20
-- ticket: JIRA-123
-- rollback-safe: yes
```

### Consideraciones Especiales MySQL

#### ⚠️ DDL y Commit Implícito

MySQL hace **commit implícito** en statements DDL (`ALTER TABLE`, `CREATE TABLE`, `DROP TABLE`, etc.). El sistema maneja esto automáticamente.

**Implicaciones**:
- No se puede hacer rollback transaccional de DDL
- Cada statement DDL se commitea inmediatamente
- Por eso son críticos los archivos `.down.sql`

#### ✅ Sintaxis Soportada

```sql
-- ✅ CORRECTO
ALTER TABLE employees ADD COLUMN name VARCHAR(100);
ALTER TABLE employees ADD INDEX idx_name (name);

-- ❌ INCORRECTO (no soportado en MySQL)
ALTER TABLE employees ADD COLUMN IF NOT EXISTS name VARCHAR(100);
CREATE INDEX IF NOT EXISTS idx_name ON employees(name);
```

#### 💡 Alternativas para Idempotencia

El sistema de tracking `schema_migrations` previene ejecuciones duplicadas, por lo que **no necesitas** `IF NOT EXISTS` en tus migraciones.

---

## Comandos CLI

### migrate [--dry-run]

Ejecuta migraciones pendientes en todos los tenants activos.

**Uso**:
```bash
php bin/migrate-tenants.php migrate [--dry-run]
```

**Opciones**:
- `--dry-run`: Preview sin ejecutar (altamente recomendado antes de producción)

**Exit Codes**:
- `0`: Éxito (todos los tenants migrados o skipped)
- `1`: Error (al menos un tenant falló)

### status [database_name]

Muestra el estado de migraciones.

**Uso**:
```bash
# Todos los tenants
php bin/migrate-tenants.php status

# Tenant específico
php bin/migrate-tenants.php status planilla_tenant_abc123
```

### rollback <database_name> [steps]

Revierte las últimas N migraciones de un tenant.

**Uso**:
```bash
# Rollback 1 migración
php bin/migrate-tenants.php rollback planilla_tenant_abc123

# Rollback 3 migraciones
php bin/migrate-tenants.php rollback planilla_tenant_abc123 3
```

**⚠️ Precaución**: El rollback es destructivo. Verifica con `status` antes de ejecutar.

### help

Muestra ayuda detallada.

**Uso**:
```bash
php bin/migrate-tenants.php help
```

---

## Mejores Prácticas

### 1. Desarrollo

✅ **Hacer**:
- Crear migración UP y DOWN juntos
- Usar dry-run antes de ejecutar
- Probar migraciones en ambiente de desarrollo primero
- Usar nombres descriptivos
- Incluir metadata completa en comentarios
- Hacer migraciones pequeñas e incrementales

❌ **Evitar**:
- Modificar migraciones ya ejecutadas en producción
- Migraciones muy grandes (>100 statements)
- Dependencias circulares entre migraciones
- Cambios destructivos sin respaldo

### 2. Producción

**Checklist Pre-Deploy**:

1. ✅ Dry-run en staging
2. ✅ Verificar status de todos los tenants
3. ✅ Backup de todas las bases de datos
4. ✅ Ventana de mantenimiento comunicada
5. ✅ Plan de rollback documentado

**Workflow Recomendado**:

```bash
# 1. Status inicial
php bin/migrate-tenants.php status > pre-migration-status.txt

# 2. Dry-run
php bin/migrate-tenants.php migrate --dry-run

# 3. Backup (fuera del sistema)
./scripts/backup-all-tenants.sh

# 4. Ejecutar migraciones
php bin/migrate-tenants.php migrate 2>&1 | tee migration-log.txt

# 5. Verificar resultado
php bin/migrate-tenants.php status > post-migration-status.txt
diff pre-migration-status.txt post-migration-status.txt

# 6. En caso de error, rollback específico
php bin/migrate-tenants.php rollback planilla_tenant_FAILED 1
```

### 3. Versionado

Usar Git para trackear migraciones:

```bash
# Agregar nueva migración al repo
git add database/migrations/tenant/2025_11_20_000001_*.sql
git commit -m "Migration: Add overtime_eligible column"
git push
```

### 4. Documentación

Cada migración compleja debe tener:

- **Ticket/Issue** de referencia
- **Descripción** del problema que resuelve
- **Testing steps** para validar
- **Rollback plan** si algo sale mal

Ejemplo en comentarios:

```sql
-- version: 2025_11_20_000003
-- description: Add attendance_calculations table for new payroll integration
-- ticket: JIRA-456
-- testing: Verify table exists with: SHOW CREATE TABLE attendance_calculations;
-- rollback-plan: Safe to rollback, no data loss (new table)
-- estimated-time: 5 seconds per tenant
```

---

## Troubleshooting

### Error: "There is no active transaction"

**Causa**: Statements DDL (`ALTER TABLE`, etc.) hacen commit implícito en MySQL.

**Solución**: Ya está manejado automáticamente en `SqlImporter.php`. Si persiste, verificar versión de PHP/PDO.

### Error: "Duplicate column name 'X'"

**Causa**: La migración ya fue ejecutada pero no fue registrada en `schema_migrations`.

**Solución**:
```bash
# Verificar si la columna existe
mysql planilla_tenant_abc123 -e "SHOW COLUMNS FROM employees LIKE 'column_name';"

# Si existe, marcar migración como ejecutada manualmente
mysql planilla_tenant_abc123 -e "
INSERT INTO schema_migrations (version, description, file_path, checksum)
VALUES ('2025_11_20_000001', 'Description', '/path/to/file.sql', MD5('content'));
"
```

### Error: "Migration file not found"

**Causa**: Ruta del archivo incorrecta o permisos insuficientes.

**Solución**:
```bash
# Verificar que el archivo existe
ls -la database/migrations/tenant/

# Verificar permisos
chmod 644 database/migrations/tenant/*.sql
```

### Error: "Can't DROP 'index_name'; check that column/key exists"

**Causa**: Archivo `.down.sql` intenta eliminar algo que ya no existe.

**Solución**: Modificar `.down.sql` para usar sintaxis condicional o verificar primero:

```sql
-- Opción 1: Verificar primero (requiere procedimiento almacenado)
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics
               WHERE table_schema = DATABASE() AND table_name = 'employees'
               AND index_name = 'idx_name');
SET @sqlstmt := IF(@exist>0, 'ALTER TABLE employees DROP INDEX idx_name', 'SELECT ''Index does not exist''');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Opción 2: Ignorar error (menos seguro)
-- Ejecutar desde CLI con || true
```

### Logs de Errores

Los errores se registran automáticamente en:

```
storage/logs/tenant_migrations_YYYY-MM-DD.log
```

Formato:
```
[2025-11-20 14:32:15] FAILED: Empresa ABC (planilla_tenant_abc123)
Error: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'overtime_eligible'
Trace: ...
```

---

## FAQ

### ¿Puedo ejecutar migraciones en un solo tenant?

No directamente con el CLI principal. Opciones:

**Opción 1**: Usar SQL directo

```bash
mysql planilla_tenant_abc123 < database/migrations/tenant/2025_11_20_000001_file.sql
```

**Opción 2**: Crear script personalizado

```php
<?php
require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/master_database.php';

$db = new PDO("mysql:host=localhost;dbname=planilla_tenant_abc123", 'user', 'pass');
$importer = new \App\Core\SqlImporter($db);
$importer->importFile('database/migrations/tenant/2025_11_20_000001_file.sql');
```

### ¿Qué pasa si una migración falla a medianoche de los tenants?

El sistema continúa con los tenants restantes. Los tenants fallidos quedan en su estado anterior gracias a las transacciones.

**Recovery**:
1. Revisar log en `storage/logs/tenant_migrations_YYYY-MM-DD.log`
2. Corregir el problema específico del tenant
3. Re-ejecutar migraciones (solo procesará los pendientes)

### ¿Puedo tener migraciones específicas para ciertos tenants?

No de forma nativa. Todas las migraciones se aplican a todos los tenants activos.

**Workarounds**:
1. Crear tabla de configuración por tenant
2. Usar migraciones condicionales con checks de configuración
3. Ejecutar migraciones manuales para casos específicos

### ¿Cómo migro datos además del esquema?

Crear migraciones de datos (DML) separadas:

```sql
-- version: 2025_11_20_000004
-- description: Seed default roles

INSERT INTO roles (name, description) VALUES
('admin', 'Administrator'),
('user', 'Regular User'),
('guest', 'Guest User');
```

**⚠️ Precaución**: Verifica que los datos no existan ya (usa `INSERT IGNORE` o checks previos).

### ¿Puedo modificar una migración ya ejecutada?

**En desarrollo**: Sí, si solo tú la has ejecutado.
**En producción**: **NO**. Crea una nueva migración.

**Si absolutamente debes hacerlo**:
1. Rollback en todos los tenants afectados
2. Modificar archivo
3. Re-ejecutar

### ¿Cómo manejo migraciones de varios developers?

1. **Git**: Cada developer crea su rama con su migración
2. **Merge conflicts**: El último en merge renumera su migración
3. **Comunicación**: Anunciar migraciones grandes en Slack/Teams
4. **Convention**: Usar prefijo de iniciales (`jd_2025_11_20_000001`)

---

## Soporte y Contribuciones

**Documentación**: `documentation/TENANT_MIGRATIONS.md` (este archivo)

**Código Fuente**:
- `app/Core/SqlImporter.php`
- `app/Core/TenantMigrationSystem.php`
- `bin/migrate-tenants.php`

**Testing**: `bin/test-migration-system.php`

**Reportar Issues**: Contactar al equipo de desarrollo

---

## Changelog

### v1.0.0 (2025-11-20)
- ✨ Release inicial
- ✅ SqlImporter con parser robusto
- ✅ TenantMigrationSystem completo
- ✅ CLI con comandos migrate/status/rollback
- ✅ Dry-run mode
- ✅ Checksum validation
- ✅ Logging detallado
- ✅ Testing suite completo
- ✅ Documentación completa

---

**Última actualización**: 20 de Noviembre, 2025
**Versión del Sistema**: 3.5.8
**Autor**: Sistema Planillas MVC Team
