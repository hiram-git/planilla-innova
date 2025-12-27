# 🔄 Guía de Migraciones Idempotentes

## 📌 **¿Qué es una Migración Idempotente?**

Una migración **idempotente** es aquella que puede ejecutarse **múltiples veces sin causar errores**, produciendo siempre el mismo resultado final.

### **Ventajas:**
- ✅ No falla si ya fue ejecutada previamente
- ✅ Facilita el deployment en múltiples ambientes
- ✅ Permite re-ejecutar migraciones sin preocupaciones
- ✅ Evita errores como "Duplicate column name" o "Index already exists"

---

## 🛠️ **Técnicas para Migraciones Idempotentes**

### **1. Verificar Existencia de Columnas Antes de Agregar**

#### ❌ **Forma NO idempotente (mala práctica):**
```sql
ALTER TABLE tenants
  ADD COLUMN company_name VARCHAR(255) NULL,
  ADD COLUMN ruc VARCHAR(50) NULL UNIQUE;
```

**Problema:** Si ejecutas esta migración dos veces, obtendrás:
```
ERROR: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'
```

#### ✅ **Forma idempotente (buena práctica):**
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
  'ALTER TABLE tenants ADD COLUMN company_name VARCHAR(255) NULL AFTER slug'  -- Si no existe, agregar
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
```

**Explicación:**
1. `INFORMATION_SCHEMA.COLUMNS` contiene metadata de todas las columnas
2. `IF(condicion, 'SELECT 1', 'ALTER TABLE...')` ejecuta ALTER solo si la columna NO existe
3. `PREPARE` y `EXECUTE` permiten SQL dinámico

---

### **2. Verificar Existencia de Índices Antes de Crear**

#### ❌ **Forma NO idempotente:**
```sql
CREATE INDEX idx_tenants_ruc ON tenants(ruc);
```

**Problema:**
```
ERROR: SQLSTATE[42000]: Syntax error or access violation: 1061 Duplicate key name 'idx_tenants_ruc'
```

#### ✅ **Forma idempotente:**
```sql
-- Add index for RUC lookups if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'tenants';
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

---

### **3. Verificar Existencia de Constraints (UNIQUE, FOREIGN KEY)**

#### ✅ **UNIQUE Constraint:**
```sql
SET @dbname = DATABASE();
SET @tablename = 'tenants';
SET @constraintname = 'ruc';  -- Nombre del índice único
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @constraintname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE tenants ADD UNIQUE KEY ruc (ruc)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
```

#### ✅ **FOREIGN KEY Constraint:**
```sql
SET @dbname = DATABASE();
SET @tablename = 'loan_installments';
SET @constraintname = 'fk_loan_installments_loan_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (CONSTRAINT_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @constraintname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE loan_installments ADD CONSTRAINT fk_loan_installments_loan_id FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
```

---

### **4. Verificar Existencia de Tablas Antes de Crear**

#### ✅ **CREATE TABLE IF NOT EXISTS (MySQL 5.0+):**
```sql
CREATE TABLE IF NOT EXISTS tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  company_name VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Ventaja:** Sintaxis nativa de MySQL, más simple y limpia.

---

### **5. Verificar Existencia de Tablas para DROP TABLE**

#### ✅ **DROP TABLE IF EXISTS:**
```sql
DROP TABLE IF EXISTS temp_migration_data;
```

---

## 📝 **Template de Migración Idempotente Completa**

```sql
-- ============================================================================
-- Migration: [NOMBRE_MIGRACION]
-- Date: [FECHA]
-- Description: [DESCRIPCIÓN_DETALLADA]
-- IDEMPOTENT: ✅ Safe to run multiple times
-- ============================================================================

-- Add column if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'nombre_tabla';
SET @columnname = 'nombre_columna';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE nombre_tabla ADD COLUMN nombre_columna VARCHAR(255) NULL'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add index if it doesn't exist
SET @indexname = 'idx_nombre_tabla_columna';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  'SELECT 1',
  'CREATE INDEX idx_nombre_tabla_columna ON nombre_tabla(nombre_columna)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add unique constraint if it doesn't exist
SET @constraintname = 'nombre_columna_unique';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @constraintname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE nombre_tabla ADD UNIQUE KEY nombre_columna_unique (nombre_columna)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add foreign key if it doesn't exist
SET @constraintname = 'fk_nombre_tabla_columna_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE
      (CONSTRAINT_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (CONSTRAINT_NAME = @constraintname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE nombre_tabla ADD CONSTRAINT fk_nombre_tabla_columna_id FOREIGN KEY (columna_id) REFERENCES otra_tabla(id) ON DELETE CASCADE'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
```

---

## 🔍 **Queries de Verificación INFORMATION_SCHEMA**

### **Verificar columnas de una tabla:**
```sql
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tenants';
```

### **Verificar índices de una tabla:**
```sql
SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tenants';
```

### **Verificar foreign keys de una tabla:**
```sql
SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'loan_installments'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### **Verificar si una tabla existe:**
```sql
SELECT COUNT(*) as table_exists
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tenants';
```

---

## ✅ **Checklist para Crear Migraciones Idempotentes**

- [ ] Verificar existencia de columnas antes de `ADD COLUMN`
- [ ] Verificar existencia de índices antes de `CREATE INDEX`
- [ ] Verificar existencia de constraints antes de `ADD CONSTRAINT`
- [ ] Usar `CREATE TABLE IF NOT EXISTS` para tablas
- [ ] Usar `DROP TABLE IF EXISTS` para eliminar tablas temporales
- [ ] Documentar la migración con comentarios claros
- [ ] Marcar la migración como `IDEMPOTENT: ✅` en los comentarios
- [ ] Probar la migración ejecutándola **2 veces seguidas** en ambiente de desarrollo

---

## 🧪 **Ejemplo Real: Migración `2025_11_21_add_company_info_to_tenants.sql`**

Ver archivo `database/migrations/master/2025_11_21_add_company_info_to_tenants.sql` para un ejemplo completo de migración idempotente que:

1. Agrega 3 columnas (`company_name`, `ruc`, `admin_email`)
2. Agrega 1 constraint UNIQUE en `ruc`
3. Agrega 2 índices (`idx_tenants_ruc`, `idx_tenants_admin_email`)
4. **Se puede ejecutar múltiples veces sin errores**

---

## 📚 **Referencias MySQL**

- [INFORMATION_SCHEMA.COLUMNS](https://dev.mysql.com/doc/refman/8.0/en/information-schema-columns-table.html)
- [INFORMATION_SCHEMA.STATISTICS](https://dev.mysql.com/doc/refman/8.0/en/information-schema-statistics-table.html)
- [INFORMATION_SCHEMA.KEY_COLUMN_USAGE](https://dev.mysql.com/doc/refman/8.0/en/information-schema-key-column-usage-table.html)
- [Prepared Statements](https://dev.mysql.com/doc/refman/8.0/en/sql-prepared-statements.html)

---

**Versión:** 1.0
**Fecha:** 26 de Diciembre, 2025
**Autor:** Sistema Planillas Innova
