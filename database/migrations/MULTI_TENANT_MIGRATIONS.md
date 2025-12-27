# 🏢 Multi-Tenant Migration System

Sistema automatizado para ejecutar migraciones en múltiples tenants del sistema Planilla Innova.

## 📋 Descripción

`migrate_all_tenants.php` es un script que ejecuta automáticamente las migraciones de la carpeta `/tenant` en **todos los tenants activos** registrados en `tenant_master.tenants`.

## 🎯 Características

- ✅ **Detección automática** de todos los tenants activos
- ✅ **Ejecución paralela** de migraciones por tenant
- ✅ **Logging detallado** con estadísticas por tenant
- ✅ **Manejo robusto** de errores (continúa si un tenant falla)
- ✅ **Modo dry-run** para simular sin ejecutar
- ✅ **Filtrado por tenant** específico
- ✅ **Tabla migrations_history** individual por tenant
- ✅ **Resumen consolidado** al final

## 🚀 Uso

### Ejecutar en todos los tenants

```bash
php database/migrations/migrate_all_tenants.php
```

### Simular ejecución (dry-run)

```bash
php database/migrations/migrate_all_tenants.php --dry-run
```

### Ejecutar solo en un tenant específico

```bash
php database/migrations/migrate_all_tenants.php --tenant=innova
```

### Ver status de migraciones por tenant

```bash
php database/migrations/migrate_all_tenants.php --status
```

### Ver ayuda

```bash
php database/migrations/migrate_all_tenants.php --help
```

## 📊 Salida del Script

### Ejemplo de Ejecución

```
╔═══════════════════════════════════════════════════════════════════════════╗
║          MULTI-TENANT MIGRATION RUNNER - PLANILLA INNOVA                 ║
╚═══════════════════════════════════════════════════════════════════════════╝

Modo: ⚡ EJECUCIÓN REAL
Directorio migraciones: /var/www/html/planilla/database/migrations/tenant
✅ Conectado a base de datos master: planilla_master

📋 Tenants encontrados: 3

================================================================================
🏢 TENANT: innova (DB: tenant_innova)
================================================================================
   ✅ Conectado exitosamente

   📊 Estado:
      Total: 25 | Ejecutadas: ✅ 20 | Pendientes: ⏳ 5

   🔄 Ejecutando 5 migraciones:

      ✅ 2025_12_01_add_vacation_fields.sql (45ms)
      ✅ 2025_12_15_improve_attendance_calculations.sql (89ms)
      ✅ 2025_12_20_add_lunch_tolerances.sql (32ms)
      ✅ 2025_12_26_improve_migrations_tracking.sql (112ms)
      ✅ 2025_12_27_add_overtime_eligibility.sql (56ms)

   ✅ Tenant completado exitosamente (5 migraciones)

================================================================================
🏢 TENANT: acme (DB: tenant_acme)
================================================================================
   ✅ Conectado exitosamente

   📊 Estado:
      Total: 25 | Ejecutadas: ✅ 25 | Pendientes: ⏳ 0

   ✅ No hay migraciones pendientes

================================================================================
🏢 TENANT: techcorp (DB: tenant_techcorp)
================================================================================
   ✅ Conectado exitosamente

   📊 Estado:
      Total: 25 | Ejecutadas: ✅ 18 | Pendientes: ⏳ 7

   🔄 Ejecutando 7 migraciones:

      ✅ 2025_11_20_create_attendance_tables.sql (156ms)
      ✅ 2025_12_01_add_vacation_fields.sql (42ms)
      ❌ 2025_12_10_add_unique_constraint.sql - ERROR: Duplicate entry '123'
      ✅ 2025_12_15_improve_attendance_calculations.sql (78ms)
      ✅ 2025_12_20_add_lunch_tolerances.sql (31ms)
      ✅ 2025_12_26_improve_migrations_tracking.sql (105ms)
      ✅ 2025_12_27_add_overtime_eligibility.sql (54ms)

   ⚠️  Tenant completado con errores (6 exitosas, 1 fallida)


╔═══════════════════════════════════════════════════════════════════════════╗
║                           RESUMEN FINAL                                   ║
╚═══════════════════════════════════════════════════════════════════════════╝

📊 ESTADÍSTICAS GENERALES:
   Tenants procesados:        3
   ✅ Exitosos:               1
   ❌ Con errores:            1
   ⏭️  Omitidos (sin cambios): 1
   📝 Total migraciones:      11

📋 DETALLE POR TENANT:
────────────────────────────────────────────────────────────────────────────────
TENANT                         STATUS          EJECUTADAS      FALLIDAS
────────────────────────────────────────────────────────────────────────────────
innova                         ✅ Exitoso      5               0
acme                           ⏭️  Omitido      0               -
techcorp                       ⚠️  Parcial      6               1
────────────────────────────────────────────────────────────────────────────────

✅ Proceso completado
```

## 🗂️ Estructura del Sistema

```
database/migrations/
├── master/                          # Migraciones para tenant_master
│   ├── 2025_11_18_create_tenants.sql
│   └── 2025_12_26_improve_migrations_tracking.sql
│
├── tenant/                          # Migraciones para cada tenant
│   ├── 2025_10_10_create_attendance_calculations.sql
│   ├── 2025_12_26_improve_migrations_tracking.sql
│   └── ...
│
├── migration_runner.php             # Runner individual (1 BD a la vez)
├── migrate_all_tenants.php          # Runner multi-tenant (TODOS)
└── MULTI_TENANT_MIGRATIONS.md       # Esta documentación
```

## 🔧 Funcionamiento Interno

### 1. Conexión a Master

El script se conecta a `tenant_master` usando `/config/master_database.php`:

```php
$masterConfig = require __DIR__ . '/../../config/master_database.php';
// Usa: MASTER_DB_HOST, MASTER_DB_NAME, MASTER_DB_USER, MASTER_DB_PASS
```

### 2. Detección de Tenants

Consulta la tabla `tenants` para obtener todos los tenants activos:

```sql
SELECT id, slug, db_host, db_port, db_name, db_user, db_pass_enc, db_charset
FROM tenants
WHERE status = 'ACTIVE'
```

### 3. Conexión por Tenant

Para cada tenant, crea una conexión usando las credenciales almacenadas:

```php
$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name}";
$pdo = new PDO($dsn, $db_user, $decrypted_password);
```

### 4. Ejecución de Migraciones

Por cada tenant:

1. **Verifica** tabla `migrations_history`
2. **Obtiene** migraciones ya ejecutadas
3. **Filtra** migraciones pendientes
4. **Ejecuta** cada migración pendiente
5. **Registra** resultado en `migrations_history`

### 5. Tabla migrations_history (por tenant)

Cada tenant tiene su propia tabla con este esquema:

```sql
CREATE TABLE migrations_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version VARCHAR(20) NULL,
    checksum VARCHAR(32) NULL,
    batch INT NULL,
    execution_time_ms INT NULL,
    status ENUM('success', 'failed', 'skipped', 'rolled_back'),
    error_message TEXT NULL,
    executed_by VARCHAR(100) NULL,
    migration_type ENUM('master', 'tenant', 'seed', 'default')
);
```

## ⚙️ Configuración Requerida

### Variables de Entorno (.env)

```env
# Master Database (tenant_master)
MASTER_DB_HOST=localhost
MASTER_DB_PORT=3306
MASTER_DB_NAME=planilla_master
MASTER_DB_USER=root
MASTER_DB_PASS=your_password
MASTER_DB_CHARSET=utf8mb4
```

### Tabla tenants

Cada tenant debe tener:

```sql
INSERT INTO tenants (slug, db_name, db_host, db_port, db_user, db_pass_enc, status)
VALUES ('innova', 'tenant_innova', 'localhost', 3306, 'root', '', 'ACTIVE');
```

**Campos obligatorios**:
- `slug`: Identificador único del tenant
- `db_name`: Nombre de la base de datos
- `db_host`: Host de la base de datos
- `db_user`: Usuario de la base de datos
- `status`: 'ACTIVE' para ejecutar migraciones

## 🔐 Seguridad

### Contraseñas Encriptadas

El script soporta passwords encriptados en `db_pass_enc`:

```php
// TODO: Implementar método de desencriptación según tu sistema
private function decryptPassword($encryptedPassword)
{
    // Actualmente soporta:
    // - NULL/vacío → password vacío
    // - Base64 → decodifica automáticamente
    // - Texto plano → usa directamente
}
```

### Permisos de Usuario

El usuario que ejecuta el script debe tener:

- ✅ Acceso a `tenant_master` (lectura en `tenants`)
- ✅ Acceso a **todas las bases de datos** de tenants activos
- ✅ Permisos `CREATE TABLE`, `ALTER TABLE`, `INSERT`, `UPDATE`

## 🛠️ Troubleshooting

### Error: "Duplicate entry '2-737-176' for key 'tenants.ruc'"

**Causa**: Migración `2025_11_21_add_company_info_to_tenants.sql` intenta insertar datos duplicados.

**Solución**: Editar migración para usar `INSERT IGNORE` o `ON DUPLICATE KEY UPDATE`.

### Error: "Error conectando a tenant X"

**Verificar**:
1. Base de datos existe: `SHOW DATABASES LIKE 'tenant_X';`
2. Credenciales correctas en tabla `tenants`
3. Usuario tiene permisos en esa BD

### Error: "No hay tenants activos para procesar"

**Verificar**:
```sql
SELECT slug, status FROM tenants WHERE status = 'ACTIVE';
```

Si no hay registros, crear tenant:
```sql
INSERT INTO tenants (slug, db_name, db_host, db_user, status)
VALUES ('innova', 'tenant_innova', 'localhost', 'root', 'ACTIVE');
```

### Migraciones no se ejecutan

**Verificar** que no estén ya registradas:
```sql
USE tenant_innova;
SELECT filename FROM migrations_history WHERE status = 'success';
```

**Eliminar registro** para re-ejecutar (⚠️ solo en desarrollo):
```sql
DELETE FROM migrations_history WHERE filename = '2025_12_27_nombre_migracion.sql';
```

## 📝 Best Practices

### 1. Siempre usar --dry-run primero

```bash
# Simular primero
php migrate_all_tenants.php --dry-run

# Si todo OK, ejecutar real
php migrate_all_tenants.php
```

### 2. Probar en un tenant específico

```bash
# Probar primero en tenant de desarrollo
php migrate_all_tenants.php --tenant=dev_innova

# Si funciona, ejecutar en todos
php migrate_all_tenants.php
```

### 3. Verificar status antes de ejecutar

```bash
# Ver estado actual
php migrate_all_tenants.php --status

# Ejecutar migraciones
php migrate_all_tenants.php
```

### 4. Backup antes de migraciones grandes

```bash
# Backup de todos los tenants
mysqldump tenant_innova > backup_innova_$(date +%Y%m%d).sql
mysqldump tenant_acme > backup_acme_$(date +%Y%m%d).sql

# Ejecutar migraciones
php migrate_all_tenants.php
```

### 5. Migraciones idempotentes

Todas las migraciones deben ser idempotentes (ejecutables múltiples veces sin error):

```sql
-- ✅ CORRECTO
CREATE TABLE IF NOT EXISTS nueva_tabla (...);
ALTER TABLE tabla ADD COLUMN IF NOT EXISTS nueva_columna VARCHAR(255);

-- ❌ INCORRECTO
CREATE TABLE nueva_tabla (...);  -- Falla si ya existe
ALTER TABLE tabla ADD COLUMN nueva_columna VARCHAR(255);  -- Falla si ya existe
```

## 🔄 Workflow Recomendado

### Desarrollo

1. Crear migración en `/database/migrations/tenant/`
2. Probar en tenant de desarrollo:
   ```bash
   php migrate_all_tenants.php --tenant=dev_innova
   ```
3. Verificar resultado:
   ```bash
   php migrate_all_tenants.php --status --tenant=dev_innova
   ```

### Staging

1. Simular en staging:
   ```bash
   php migrate_all_tenants.php --dry-run
   ```
2. Ejecutar en staging:
   ```bash
   php migrate_all_tenants.php
   ```
3. Verificar todos los tenants:
   ```bash
   php migrate_all_tenants.php --status
   ```

### Producción

1. **Backup** de todas las bases de datos
2. **Dry-run** para verificar:
   ```bash
   php migrate_all_tenants.php --dry-run
   ```
3. **Ejecutar** en producción:
   ```bash
   php migrate_all_tenants.php
   ```
4. **Verificar** status:
   ```bash
   php migrate_all_tenants.php --status
   ```
5. **Monitorear** logs y errores

## 📊 Estadísticas y Métricas

El script registra para cada migración:

- ✅ **Tiempo de ejecución** (ms)
- ✅ **Status** (success/failed/skipped)
- ✅ **Batch** (número de ejecución)
- ✅ **Checksum** (MD5 del archivo)
- ✅ **Usuario** que ejecutó
- ✅ **Mensajes de error** (si falló)

### Consultar historial

```sql
-- Ver últimas migraciones ejecutadas
SELECT filename, executed_at, execution_time_ms, status
FROM migrations_history
ORDER BY executed_at DESC
LIMIT 10;

-- Ver migraciones fallidas
SELECT filename, error_message, executed_at
FROM migrations_history
WHERE status = 'failed';

-- Estadísticas por batch
SELECT batch, COUNT(*) as migrations, AVG(execution_time_ms) as avg_time
FROM migrations_history
GROUP BY batch;
```

## 🆘 Soporte

### Logs

El script muestra logs detallados en tiempo real. Para guardar logs:

```bash
php migrate_all_tenants.php | tee migration_log_$(date +%Y%m%d_%H%M%S).txt
```

### Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `Connection refused` | BD no accesible | Verificar host/puerto |
| `Access denied` | Credenciales inválidas | Verificar user/password |
| `Database doesn't exist` | BD no creada | Crear BD del tenant |
| `Duplicate entry` | Migración no idempotente | Usar `IF NOT EXISTS` |
| `Table already exists` | Migración ya ejecutada | Usar `CREATE TABLE IF NOT EXISTS` |

## 📚 Referencias

- [migration_runner.php](./migration_runner.php) - Runner individual
- [CLAUDE.md](../../CLAUDE.md) - Documentación general del proyecto
- [SISTEMA_MIGRACIONES_MEJORADO.md](./SISTEMA_MIGRACIONES_MEJORADO.md) - Sistema mejorado de tracking

---

**Versión**: 1.0.0
**Fecha**: 2025-12-27
**Autor**: Sistema Planilla Innova
