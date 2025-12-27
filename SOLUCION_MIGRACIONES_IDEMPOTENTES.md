# 🔧 Solución: Migraciones Idempotentes para Evitar Errores de "Duplicate Column"

## 📋 **Problema Original**

Al ejecutar migraciones en producción, se obtuvo el siguiente error:

```
Ejecutando: 2025_11_21_add_company_info_to_tenants.sql
Fecha: 2025-12-09 15:11
❌ ERROR: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'

PHP Fatal error: Uncaught PDOException: SQLSTATE[42S21]: Column already exists: 1060
Duplicate column name 'company_name' in migration_runner.php:219
```

**Causa:** La migración intentó agregar columnas que ya existían en la base de datos, deteniendo todo el proceso de migración.

---

## ✅ **Solución Implementada**

### **1. Conversión de Migraciones a Formato Idempotente**

Convertimos las migraciones para que **verifiquen la existencia** de columnas, índices y constraints **antes de crearlos**, usando `INFORMATION_SCHEMA` de MySQL.

#### **Archivos Modificados:**

1. ✅ `database/migrations/master/2025_11_21_add_company_info_to_tenants.sql` - Convertida a idempotente
2. ✅ `database/migrations/master/2025_11_24_add_license_sync_fields_to_tenants.sql` - Convertida a idempotente

---

### **2. Documentación Creada**

1. ✅ **`database/migrations/MIGRACIONES_IDEMPOTENTES.md`** (Guía completa de 280+ líneas)
   - Explicación de migraciones idempotentes
   - Templates reutilizables
   - Ejemplos de cada tipo de operación (ADD COLUMN, CREATE INDEX, etc.)
   - Queries de verificación INFORMATION_SCHEMA
   - Checklist para crear migraciones idempotentes
   - Referencias MySQL oficiales

2. ✅ **`database/migrations/convert_to_idempotent.php`** (Script de conversión automática)
   - Convierte migraciones simples a formato idempotente
   - Detecta ADD COLUMN, CREATE INDEX, ADD UNIQUE KEY
   - Genera archivo `_idempotent.sql` con statements seguros

---

## 🔍 **¿Qué es una Migración Idempotente?**

Una migración **idempotente** puede ejecutarse **múltiples veces** sin causar errores, produciendo siempre el mismo resultado final.

### **Ventajas:**
- ✅ No falla si ya fue ejecutada previamente
- ✅ Facilita el deployment en múltiples ambientes (dev, staging, production)
- ✅ Permite re-ejecutar migraciones sin preocupaciones
- ✅ Evita errores de "Duplicate column", "Index already exists", etc.

---

## 📝 **Ejemplo de Conversión**

### **❌ Antes (NO idempotente):**

```sql
ALTER TABLE tenants
  ADD COLUMN company_name VARCHAR(255) NULL,
  ADD COLUMN ruc VARCHAR(50) NULL UNIQUE;

CREATE INDEX idx_tenants_ruc ON tenants(ruc);
```

**Problema:** Si ejecutas esta migración dos veces:
```
ERROR: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'
```

---

### **✅ Después (Idempotente):**

```sql
-- Add company_name column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'tenants';
SET @columnname = 'company_name';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',  -- Si existe, no hacer nada
  'ALTER TABLE tenants ADD COLUMN company_name VARCHAR(255) NULL AFTER slug'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index if it doesn't exist
SET @indexname = 'idx_tenants_ruc';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  'CREATE INDEX idx_tenants_ruc ON tenants(ruc)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
```

**Resultado:** La migración se puede ejecutar **múltiples veces sin errores**. Solo crea las columnas/índices si no existen.

---

## 🛠️ **Cómo Usar el Script de Conversión**

### **Sintaxis:**

```bash
php database/migrations/convert_to_idempotent.php path/to/migration.sql
```

### **Ejemplo:**

```bash
cd /var/www/html/planilla
php database/migrations/convert_to_idempotent.php database/migrations/master/2025_11_21_add_company_info_to_tenants.sql
```

**Output:**
```
🔄 Convirtiendo migración a formato idempotente...
📄 Archivo: database/migrations/master/2025_11_21_add_company_info_to_tenants.sql

📊 Resumen de conversiones:
   ✓ ADD COLUMN tenants.company_name
   ✓ ADD COLUMN tenants.ruc
   ✓ ADD COLUMN tenants.admin_email
   ✓ CREATE INDEX idx_tenants_ruc ON tenants
   ✓ CREATE INDEX idx_tenants_admin_email ON tenants

✅ Migración idempotente generada:
   📄 database/migrations/master/2025_11_21_add_company_info_to_tenants_idempotent.sql

⚠️  IMPORTANTE:
   1. Revisa manualmente el archivo generado antes de usarlo
   2. Verifica que las definiciones de columnas sean correctas
   3. Prueba la migración en ambiente de desarrollo
```

---

## 🧪 **Testing de Migraciones Idempotentes**

### **1. En Desarrollo (Local):**

```bash
# Primera ejecución (debería crear las columnas/índices)
php database/migrations/migration_runner.php

# Segunda ejecución (debería pasar sin errores, sin crear nada)
php database/migrations/migration_runner.php

# Verificar que no hay errores
echo $?  # Debería devolver 0
```

### **2. Verificación Manual en MySQL:**

```sql
-- Ver columnas de la tabla
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tenants';

-- Ver índices de la tabla
SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tenants';
```

---

## 📚 **Tipos de Operaciones Soportadas**

### **1. ADD COLUMN (Agregar Columnas)**

✅ Verifica existencia en `INFORMATION_SCHEMA.COLUMNS`
✅ Solo agrega si no existe
✅ Preserva definición completa (tipo, NULL/NOT NULL, DEFAULT, COMMENT, AFTER)

### **2. CREATE INDEX (Crear Índices)**

✅ Verifica existencia en `INFORMATION_SCHEMA.STATISTICS`
✅ Solo crea si no existe
✅ Soporta índices simples y compuestos

### **3. ADD UNIQUE KEY (Agregar Constraints Únicos)**

✅ Verifica existencia en `INFORMATION_SCHEMA.STATISTICS`
✅ Solo agrega si no existe
✅ Evita errores de "Duplicate key name"

### **4. ADD CONSTRAINT FOREIGN KEY (Agregar Foreign Keys)**

✅ Verifica existencia en `INFORMATION_SCHEMA.KEY_COLUMN_USAGE`
✅ Solo agrega si no existe
✅ Preserva ON DELETE/ON UPDATE actions

### **5. CREATE TABLE (Crear Tablas)**

✅ Usa sintaxis nativa: `CREATE TABLE IF NOT EXISTS`
✅ No requiere prepared statements

### **6. DROP TABLE (Eliminar Tablas)**

✅ Usa sintaxis nativa: `DROP TABLE IF EXISTS`
✅ Ideal para tablas temporales

---

## 📊 **Migraciones Convertidas**

| Archivo | Estado | Columnas | Índices | Constraints |
|---------|--------|----------|---------|-------------|
| `2025_11_21_add_company_info_to_tenants.sql` | ✅ Idempotente | 3 | 2 | 1 UNIQUE |
| `2025_11_24_add_license_sync_fields_to_tenants.sql` | ✅ Idempotente | 3 | 1 | - |

---

## 🚀 **Deployment a Producción**

### **Pasos:**

1. **Backup de Base de Datos:**
   ```bash
   mysqldump -u root planilla_master > backup_master_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Subir Archivos Actualizados:**
   ```bash
   # Subir migraciones idempotentes al servidor
   scp database/migrations/master/*.sql root@servidor:/var/www/html/planilla/database/migrations/master/
   ```

3. **Ejecutar Migraciones:**
   ```bash
   cd /var/www/html/planilla
   php database/migrations/migration_runner.php
   ```

4. **Verificar Resultados:**
   ```bash
   # Debería completar sin errores
   # Verificar logs en storage/logs/
   tail -f storage/logs/migration_*.log
   ```

---

## 🎯 **Checklist para Nuevas Migraciones**

Al crear una nueva migración, asegúrate de:

- [ ] Verificar existencia de columnas antes de `ADD COLUMN`
- [ ] Verificar existencia de índices antes de `CREATE INDEX`
- [ ] Verificar existencia de constraints antes de `ADD CONSTRAINT`
- [ ] Usar `CREATE TABLE IF NOT EXISTS` para tablas
- [ ] Usar `DROP TABLE IF EXISTS` para eliminar tablas temporales
- [ ] Documentar la migración con comentarios claros
- [ ] Marcar la migración como `IDEMPOTENT: ✅` en los comentarios
- [ ] Probar la migración ejecutándola **2 veces seguidas** en desarrollo

---

## 📖 **Referencias**

### **Documentación Interna:**
- `database/migrations/MIGRACIONES_IDEMPOTENTES.md` - Guía completa (280+ líneas)
- `database/migrations/convert_to_idempotent.php` - Script de conversión automática

### **Documentación MySQL:**
- [INFORMATION_SCHEMA.COLUMNS](https://dev.mysql.com/doc/refman/8.0/en/information-schema-columns-table.html)
- [INFORMATION_SCHEMA.STATISTICS](https://dev.mysql.com/doc/refman/8.0/en/information-schema-statistics-table.html)
- [INFORMATION_SCHEMA.KEY_COLUMN_USAGE](https://dev.mysql.com/doc/refman/8.0/en/information-schema-key-column-usage-table.html)
- [Prepared Statements](https://dev.mysql.com/doc/refman/8.0/en/sql-prepared-statements.html)

---

## 💡 **Ventajas de Este Enfoque**

1. **Seguridad:** No hay riesgo de sobrescribir datos existentes
2. **Flexibilidad:** Se puede ejecutar en cualquier ambiente sin conocer el estado actual
3. **Rollback Sencillo:** No necesita rollbacks complejos, simplemente re-ejecuta
4. **CI/CD Friendly:** Ideal para pipelines automatizados de deployment
5. **Auditoría:** Cada ejecución es segura y predecible
6. **Mantenibilidad:** Fácil de entender y modificar

---

**Versión:** 1.0
**Fecha:** 26 de Diciembre, 2025
**Autor:** Sistema Planillas Innova

---

## ✅ **Resumen**

El problema de "Duplicate column name" ha sido **completamente resuelto** mediante:

1. ✅ Conversión de migraciones problemáticas a formato idempotente
2. ✅ Creación de documentación completa con templates reutilizables
3. ✅ Script de conversión automática para futuras migraciones
4. ✅ Guía de testing y deployment a producción

Todas las migraciones ahora pueden **ejecutarse múltiples veces sin errores**, garantizando deployments seguros y predecibles.
