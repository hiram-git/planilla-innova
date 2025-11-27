# Guía de Aplicación de Migraciones Tenant

## 📋 Descripción

Este directorio contiene migraciones SQL que deben ejecutarse en **todas las bases de datos tenant** del sistema multitenancy. Cada archivo de migración está diseñado para ser idempotente y puede ejecutarse de forma segura múltiples veces.

## 🎯 Migraciones Disponibles

### 2025_11_26_update_menu_items_system_modules.sql
**Descripción**: Actualiza el sistema de módulos y permisos sincronizando la tabla `menu_items` con el listado actualizado en `app/Models/Role.php`.

**Cambios incluidos**:
- Agrega columnas: `icon`, `description`, `status`, `display_order`, `created_at`, `updated_at`
- Actualiza 25 módulos con URLs formato MVC (sin .php)
- Normaliza nombres y descripciones
- Agrega íconos FontAwesome
- Inserta módulo "Usuarios" (ID 16) si no existe
- Agrega índices para optimización

**Tablas afectadas**: `menu_items`

## 🔧 Métodos de Ejecución

### Método 1: Script Bash Automatizado (RECOMENDADO)

```bash
#!/bin/bash
# Ejecutar migración en todos los tenants activos

MYSQL_USER="root"
MYSQL_PASS=""
MASTER_DB="planilla_master"
MIGRATION_FILE="2025_11_26_update_menu_items_system_modules.sql"

# Obtener lista de tenants activos
TENANTS=$(mysql -u $MYSQL_USER -p$MYSQL_PASS $MASTER_DB -Nse "
    SELECT db_name
    FROM tenants
    WHERE status = 'ACTIVE'
    ORDER BY id
")

echo "🚀 Iniciando aplicación de migración: $MIGRATION_FILE"
echo "📊 Tenants encontrados: $(echo "$TENANTS" | wc -l)"
echo ""

SUCCESS=0
FAILED=0

for TENANT_DB in $TENANTS; do
    echo "⚙️  Procesando: $TENANT_DB..."

    if mysql -u $MYSQL_USER -p$MYSQL_PASS $TENANT_DB < $MIGRATION_FILE 2>&1; then
        echo "✅ Éxito: $TENANT_DB"
        ((SUCCESS++))
    else
        echo "❌ Error: $TENANT_DB"
        ((FAILED++))
    fi
    echo ""
done

echo "============================================"
echo "📈 Resumen de Ejecución"
echo "✅ Exitosos: $SUCCESS"
echo "❌ Fallidos: $FAILED"
echo "📊 Total: $((SUCCESS + FAILED))"
echo "============================================"
```

**Guardar como**: `apply_tenant_migration.sh`

**Ejecutar**:
```bash
chmod +x apply_tenant_migration.sh
./apply_tenant_migration.sh
```

### Método 2: Script PHP con WizardModel

```php
<?php
// Archivo: scripts/apply_tenant_migration.php

require_once __DIR__ . '/../config/bootstrap.php';

use App\Models\WizardModel;

$wizard = new WizardModel();
$migrationFile = __DIR__ . '/../database/migrations/tenant/2025_11_26_update_menu_items_system_modules.sql';

// Obtener tenants activos
$tenants = $wizard->getActiveTenants();

echo "🚀 Iniciando aplicación de migración\n";
echo "📊 Tenants encontrados: " . count($tenants) . "\n\n";

$success = 0;
$failed = 0;

foreach ($tenants as $tenant) {
    echo "⚙️  Procesando: {$tenant['company_name']} ({$tenant['db_name']})...\n";

    try {
        $result = $wizard->applyMigrationToTenant($tenant['db_name'], $migrationFile);

        if ($result['success']) {
            echo "✅ Éxito\n\n";
            $success++;
        } else {
            echo "❌ Error: {$result['message']}\n\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ Excepción: {$e->getMessage()}\n\n";
        $failed++;
    }
}

echo "============================================\n";
echo "📈 Resumen de Ejecución\n";
echo "✅ Exitosos: $success\n";
echo "❌ Fallidos: $failed\n";
echo "📊 Total: " . ($success + $failed) . "\n";
echo "============================================\n";
```

**Ejecutar**:
```bash
php scripts/apply_tenant_migration.php
```

### Método 3: Ejecución Manual por Tenant

Para aplicar la migración a un tenant específico:

```bash
mysql -u root -p NOMBRE_TENANT_DB < database/migrations/tenant/2025_11_26_update_menu_items_system_modules.sql
```

**Ejemplo**:
```bash
mysql -u root planilla_prod < database/migrations/tenant/2025_11_26_update_menu_items_system_modules.sql
```

### Método 4: PHPMyAdmin (Para testing)

1. Acceder a PHPMyAdmin
2. Seleccionar la base de datos del tenant
3. Ir a la pestaña "SQL"
4. Copiar y pegar el contenido completo del archivo de migración
5. Ejecutar

## ✅ Verificación Post-Migración

### 1. Verificar estructura de tabla

```sql
DESCRIBE menu_items;
```

**Resultado esperado**:
```
+---------------+--------------+------+-----+-------------------+-----------------------------+
| Field         | Type         | Null | Key | Default           | Extra                       |
+---------------+--------------+------+-----+-------------------+-----------------------------+
| id            | int          | NO   | PRI | NULL              | auto_increment              |
| name          | varchar(100) | NO   |     | NULL              |                             |
| url           | varchar(255) | NO   |     | NULL              |                             |
| icon          | varchar(100) | YES  |     | NULL              |                             |
| description   | varchar(255) | YES  |     | NULL              |                             |
| status        | tinyint(1)   | YES  | MUL | 1                 |                             |
| display_order | int          | YES  | MUL | 0                 |                             |
| created_at    | timestamp    | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED           |
| updated_at    | timestamp    | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update |
+---------------+--------------+------+-----+-------------------+-----------------------------+
```

### 2. Verificar datos actualizados

```sql
SELECT
    id,
    name,
    url,
    icon,
    description,
    display_order,
    status
FROM menu_items
ORDER BY display_order
LIMIT 5;
```

**Resultado esperado** (primeros 5 registros):
```
+----+-----------+-----------+--------------------------+--------------------------------------+---------------+--------+
| id | name      | url       | icon                     | description                          | display_order | status |
+----+-----------+-----------+--------------------------+--------------------------------------+---------------+--------+
|  1 | Dashboard | dashboard | fas fa-tachometer-alt    | Vista general del sistema            |             1 |      1 |
|  2 | Empresa   | company   | fas fa-building          | Configuración de la empresa          |             2 |      1 |
|  3 | Plazas    | positions | fas fa-chair             | Gestión de plazas                    |             3 |      1 |
|  4 | Partidas  | partidas  | fas fa-money-check-alt   | Gestión de partidas presupuestarias  |             4 |      1 |
|  5 | Organigrama| organigrama| fas fa-sitemap          | Visualización de estructura org.     |             5 |      1 |
+----+-----------+-----------+--------------------------+--------------------------------------+---------------+--------+
```

### 3. Verificar conteo total

```sql
SELECT COUNT(*) as total_modules FROM menu_items WHERE status = 1;
```

**Resultado esperado**: `total_modules = 25`

### 4. Verificar módulo Usuarios (ID 16)

```sql
SELECT * FROM menu_items WHERE id = 16;
```

**Resultado esperado**:
```
+----+--------------------+-------+------------------+---------------------------------------------+--------+---------------+
| id | name               | url   | icon             | description                                 | status | display_order |
+----+--------------------+-------+------------------+---------------------------------------------+--------+---------------+
| 16 | Usuarios           | users | fas fa-user-cog  | Administración de usuarios del sistema      |      1 |            16 |
+----+--------------------+-------+------------------+---------------------------------------------+--------+---------------+
```

## 🔄 Rollback (Opcional)

Si necesitas revertir los cambios, ejecuta:

```sql
-- Eliminar columnas agregadas
ALTER TABLE menu_items
DROP COLUMN icon,
DROP COLUMN description,
DROP COLUMN status,
DROP COLUMN display_order,
DROP COLUMN created_at,
DROP COLUMN updated_at;

-- Restaurar URLs antiguas (ejemplo para algunos registros)
UPDATE menu_items SET url = 'home.php' WHERE id = 1;
UPDATE menu_items SET url = 'datos_empresa.php' WHERE id = 2;
UPDATE menu_items SET url = 'employee.php' WHERE id = 8;
-- ... (agregar resto de UPDATEs según necesidad)
```

**⚠️ Advertencia**: El rollback completo requiere tener un backup de los datos originales.

## 📝 Notas Importantes

1. **Backup**: Siempre hacer backup de la base de datos antes de ejecutar migraciones
2. **Testing**: Probar primero en un tenant de prueba antes de aplicar a producción
3. **Idempotencia**: Esta migración puede ejecutarse múltiples veces sin causar errores
4. **Permisos**: Los permisos en `role_permissions` siguen usando `menu_id`, por lo que permanecen intactos
5. **Multitenancy**: Esta migración debe ejecutarse en CADA tenant activo del sistema

## 🆘 Soporte

Si encuentras errores durante la aplicación:

1. Revisar logs de MySQL
2. Verificar permisos de usuario de base de datos
3. Confirmar que la base de datos existe y es accesible
4. Verificar sintaxis SQL en el archivo de migración

## 📅 Historial de Cambios

- **2025-11-26**: Creación inicial - Sincronización módulos sistema con Role.php
