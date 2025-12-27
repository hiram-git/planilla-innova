# 🚀 Sistema de Migraciones Mejorado - Planilla Innova

## 📋 **Resumen de Mejoras**

El sistema de migraciones ha sido completamente mejorado para ser **robusto, resiliente y con tracking completo**.

### **✅ Problemas Resueltos:**

1. ❌ **Antes:** Las migraciones se detenían al encontrar un error (ej: "Duplicate column name")
2. ✅ **Ahora:** Las migraciones continúan ejecutándose, registrando errores pero NO deteniendo el proceso

3. ❌ **Antes:** No había auditoría de quién ejecutó las migraciones ni cuándo
4. ✅ **Ahora:** Tracking completo con batch, tiempo de ejecución, usuario, tipo de migración

5. ❌ **Antes:** Difícil identificar migraciones fallidas
6. ✅ **Ahora:** Status detallado con estadísticas y listado de migraciones fallidas

---

## 🏗️ **Arquitectura del Sistema**

### **1. Tabla `migrations_history` Mejorada**

```sql
CREATE TABLE migrations_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch INT UNSIGNED NOT NULL DEFAULT 1,              -- Agrupa migraciones ejecutadas juntas
    filename VARCHAR(255) NOT NULL UNIQUE,              -- Nombre del archivo de migración
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,    -- Fecha/hora de ejecución
    version VARCHAR(20) NULL,                           -- Versión del sistema (ej: 3.5.13)
    checksum VARCHAR(32) NULL,                          -- MD5 del contenido SQL
    execution_time_ms INT UNSIGNED NULL,                -- Tiempo de ejecución en milisegundos
    status ENUM('success', 'failed', 'rolled_back', 'skipped') NOT NULL DEFAULT 'success',
    error_message TEXT NULL,                            -- Mensaje de error si falló
    executed_by VARCHAR(100) NULL,                      -- Usuario que ejecutó (SO user o www-data)
    migration_type ENUM('master', 'tenant', 'seed', 'default') NOT NULL DEFAULT 'default',

    INDEX idx_batch (batch),
    INDEX idx_status (status, executed_at),
    INDEX idx_migration_type (migration_type, executed_at)
) ENGINE=InnoDB;
```

### **Campos Clave:**

| Campo | Tipo | Descripción | Uso |
|-------|------|-------------|-----|
| `batch` | INT | Número de lote de ejecución | Permite rollback por lotes |
| `execution_time_ms` | INT | Tiempo en milisegundos | Optimización y monitoreo de performance |
| `status` | ENUM | Estado de la migración | Diferencia entre exitosas, fallidas, omitidas |
| `error_message` | TEXT | Mensaje de error | Debugging de migraciones fallidas |
| `executed_by` | VARCHAR | Usuario del SO | Auditoría de quién ejecutó |
| `migration_type` | ENUM | Tipo de migración | Separar migraciones de master/tenant/seed |

---

## 🔧 **Flujo de Ejecución Mejorado**

### **Antes (Sistema Antiguo):**
```
1. Cargar migraciones ejecutadas
2. Filtrar pendientes
3. Ejecutar migración
   └─❌ ERROR → DETENER TODO EL PROCESO
4. Registrar en migrations_history
```

**Problema:** Un error detenía TODAS las migraciones posteriores.

---

### **Ahora (Sistema Mejorado):**
```
1. Cargar migraciones ejecutadas (solo status = 'success' o 'skipped')
2. Filtrar pendientes
3. Obtener siguiente batch number
4. Para cada migración:
   a. Iniciar timer
   b. Ejecutar SQL
      ├─✅ SUCCESS → status = 'success'
      ├─⚠️  Duplicate column/index → status = 'skipped' (CONTINUAR)
      └─❌ ERROR → status = 'failed', registrar error (CONTINUAR)
   c. Calcular execution_time_ms
   d. Registrar en migrations_history con TODOS los detalles
   e. Mostrar resultado y CONTINUAR con siguiente migración
5. Mostrar resumen final
```

**Ventaja:** Las migraciones continúan aunque alguna falle. Se registra TODO para auditoría.

---

## 📊 **Estados de Migraciones**

| Estado | Icono | Descripción | Se Vuelve a Ejecutar? |
|--------|-------|-------------|----------------------|
| `success` | ✅ | Ejecutada exitosamente | ❌ No |
| `skipped` | ⏭️ | Omitida (ej: columna ya existe) | ❌ No |
| `failed` | ❌ | Falló con error | ✅ Sí (al corregir y re-ejecutar) |
| `rolled_back` | ↩️ | Revertida mediante rollback | ✅ Sí |

---

## 💻 **Comandos Disponibles**

### **1. Ejecutar Migraciones Pendientes**

```bash
# Ejecutar todas las migraciones pendientes
php database/migrations/migration_runner.php

# Dry run (simular sin ejecutar)
php database/migrations/migration_runner.php --dry-run

# Ejecutar hasta una versión específica
php database/migrations/migration_runner.php --version=3.5.13
```

**Output Mejorado:**
```
=== SISTEMA MIGRACIÓN PLANILLA INNOVA ===
Modo: EJECUCIÓN REAL
Base de datos: planilla_master (tenant_master)
Directorio: /var/www/html/planilla/database/migrations/master

✅ Tabla migrations_history verificada

📋 Migraciones encontradas: 15
⏳ Migraciones pendientes: 3
✅ Migraciones ejecutadas: 12

🔄 Ejecutando: 2025_12_26_improve_migrations_tracking.sql
   Fecha: 2025-12-26 10:30
   ✅ Completada exitosamente (245ms)

🔄 Ejecutando: 2025_12_26_add_new_feature.sql
   Fecha: 2025-12-26 11:00
   ❌ ERROR: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'
   ⏭️  Continuando con siguiente migración...

🔄 Ejecutando: 2025_12_26_fix_data.sql
   Fecha: 2025-12-26 11:15
   ✅ Completada exitosamente (89ms)

✅ Proceso de migración completado
```

**Nota:** Aunque la segunda migración falló, el proceso continuó y ejecutó la tercera.

---

### **2. Ver Status de Migraciones**

```bash
php database/migrations/migration_runner.php --status
```

**Output Mejorado:**
```
╔════════════════════════════════════════════════════════════════╗
║            STATUS MIGRACIONES - PLANILLA INNOVA                ║
╚════════════════════════════════════════════════════════════════╝

Base de datos: planilla_master (tenant_master)
Directorio: /var/www/html/planilla/database/migrations/master

📊 ESTADÍSTICAS:
   Total: 15 | Ejecutadas: ✅ 14 | Pendientes: ⏳ 1

📋 DETALLE DE MIGRACIONES:
────────────────────────────────────────────────────────────────────────────────
ARCHIVO                                           FECHA                STATUS        BATCH      TIEMPO (ms)      EJECUTADO POR
────────────────────────────────────────────────────────────────────────────────
2025_11_21_add_company_info_to_tenants.sql       2025-11-21 09:00     ✅ Exitosa    1          156              root
2025_11_24_add_license_sync_fields.sql           2025-11-24 14:30     ✅ Exitosa    1          89               root
2025_12_26_improve_migrations_tracking.sql       2025-12-26 10:30     ✅ Exitosa    2          245              root
2025_12_26_add_new_feature.sql                   2025-12-26 11:00     ❌ Fallida    2          12               root
   ❌ Error: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'
2025_12_26_fix_data.sql                          2025-12-26 11:15     ✅ Exitosa    2          89               root
2025_12_27_pending_migration.sql                 2025-12-27 08:00     ⏳ Pendiente  -          -                -
────────────────────────────────────────────────────────────────────────────────

⚠️  MIGRACIONES FALLIDAS:

   ❌ 2025_12_26_add_new_feature.sql
      Fecha: 2025-12-26 11:00:34
      Batch: 2
      Error: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'

💡 Para re-ejecutar migraciones fallidas, corrige el error y ejecuta nuevamente el runner.
```

---

## 🎯 **Casos de Uso**

### **Caso 1: Migración con Columna Duplicada (Ya Existe)**

**Escenario:** La migración intenta crear una columna que ya existe en la BD.

**Antes (Sistema Antiguo):**
```
❌ ERROR: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name'
🛑 Proceso DETENIDO - migraciones posteriores NO se ejecutan
```

**Ahora (Sistema Mejorado):**
```
⚠️  Warning: SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'company_name' (skipped)
⏭️  Omitida (ya existente) (12ms)
🔄 Continuando con siguiente migración...
✅ Siguiente migración se ejecuta normalmente
```

**Registro en BD:**
```sql
SELECT * FROM migrations_history WHERE filename = '2025_12_26_add_new_feature.sql';

| id | batch | filename                          | status   | error_message                  | executed_by |
|----|-------|-----------------------------------|----------|--------------------------------|-------------|
| 14 | 2     | 2025_12_26_add_new_feature.sql   | skipped  | Column already exists: 1060... | root        |
```

---

### **Caso 2: Migración con Error Real (Sintaxis SQL Incorrecta)**

**Escenario:** La migración tiene un error de sintaxis SQL.

**Sistema Mejorado:**
```
❌ ERROR: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax
⏭️  Continuando con siguiente migración...
```

**Registro en BD:**
```sql
| id | batch | filename                    | status  | error_message            | execution_time_ms |
|----|-------|-----------------------------|---------|--------------------------|-------------------|
| 15 | 2     | 2025_12_26_bad_syntax.sql  | failed  | You have an error in...  | 5                 |
```

**Solución:**
1. Corregir el archivo SQL
2. Re-ejecutar `php migration_runner.php`
3. El runner detecta que tiene `status = 'failed'` → NO está en `executedMigrations`
4. Se vuelve a intentar
5. Si ahora es exitosa → se actualiza a `status = 'success'` (nuevo registro)

---

### **Caso 3: Re-ejecutar Todas las Migraciones en Nuevo Ambiente**

**Escenario:** Deployment en nuevo servidor donde NO hay `migrations_history`.

**Comportamiento:**
1. Se crea tabla `migrations_history` automáticamente
2. Se ejecutan TODAS las migraciones (15 en total)
3. Las que son idempotentes (verifican existencia) → `status = 'success'`
4. Las que no son idempotentes pero ya existen → `status = 'skipped'`
5. Las que tienen errores reales → `status = 'failed'`

**Resultado:** El sistema queda funcional aunque algunas migraciones fallen.

---

## 🔍 **Queries Útiles para Auditoría**

### **Ver Todas las Migraciones Ejecutadas:**
```sql
SELECT
    filename,
    executed_at,
    status,
    execution_time_ms,
    executed_by,
    batch
FROM migrations_history
ORDER BY executed_at DESC;
```

### **Ver Migraciones por Batch:**
```sql
SELECT batch, COUNT(*) as total, SUM(execution_time_ms) as total_time_ms
FROM migrations_history
GROUP BY batch
ORDER BY batch DESC;
```

### **Ver Migraciones Fallidas:**
```sql
SELECT
    filename,
    executed_at,
    error_message
FROM migrations_history
WHERE status = 'failed'
ORDER BY executed_at DESC;
```

### **Ver Performance de Migraciones (Más Lentas):**
```sql
SELECT
    filename,
    execution_time_ms,
    executed_at
FROM migrations_history
WHERE status = 'success'
ORDER BY execution_time_ms DESC
LIMIT 10;
```

### **Ver Migraciones por Tipo:**
```sql
SELECT
    migration_type,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as exitosas,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as fallidas
FROM migrations_history
GROUP BY migration_type;
```

---

## 🚀 **Deployment a Producción**

### **Paso 1: Backup de Base de Datos**

```bash
# Backup de planilla_master
mysqldump -u root planilla_master > backup_master_$(date +%Y%m%d_%H%M%S).sql

# Backup de planilla_prod (si aplica)
mysqldump -u root planilla_prod > backup_prod_$(date +%Y%m%d_%H%M%S).sql
```

### **Paso 2: Subir Archivos al Servidor**

```bash
# Subir migration_runner.php mejorado
scp database/migrations/migration_runner.php root@servidor:/var/www/html/planilla/database/migrations/

# Subir nueva migración para mejorar tabla migrations_history
scp database/migrations/master/2025_12_26_improve_migrations_tracking.sql root@servidor:/var/www/html/planilla/database/migrations/master/

# Subir migraciones idempotentes
scp database/migrations/master/2025_11_21_add_company_info_to_tenants.sql root@servidor:/var/www/html/planilla/database/migrations/master/
scp database/migrations/master/2025_11_24_add_license_sync_fields_to_tenants.sql root@servidor:/var/www/html/planilla/database/migrations/master/
```

### **Paso 3: Ejecutar Migraciones**

```bash
cd /var/www/html/planilla

# Ver status actual
php database/migrations/migration_runner.php --status

# Ejecutar migraciones pendientes
php database/migrations/migration_runner.php

# Verificar nuevamente status
php database/migrations/migration_runner.php --status
```

### **Paso 4: Verificar Resultados**

```bash
# Verificar logs (si hay migraciones fallidas)
tail -f storage/logs/migration_*.log

# Conectar a MySQL y verificar
mysql -u root planilla_master

# Verificar campos nuevos en migrations_history
DESCRIBE migrations_history;

# Ver últimas migraciones
SELECT * FROM migrations_history ORDER BY executed_at DESC LIMIT 10;
```

---

## 📚 **Mejores Prácticas**

### **1. Siempre Crear Migraciones Idempotentes**

✅ **Correcto:**
```sql
-- Verificar existencia antes de crear
SET @columnname = 'company_name';
SET @preparedStatement = (SELECT IF(...));
```

❌ **Incorrecto:**
```sql
-- Asumir que no existe
ALTER TABLE tenants ADD COLUMN company_name VARCHAR(255);
```

### **2. Nomenclatura de Archivos**

```
YYYY_MM_DD_HHII_descripcion_clara.sql
```

Ejemplos:
- `2025_12_26_1030_add_company_info_to_tenants.sql`
- `2025_12_26_1115_improve_migrations_tracking.sql`

### **3. Incluir Comentarios Descriptivos**

```sql
-- ============================================================================
-- Migration: Improve Migrations History Tracking
-- Date: 2025-12-26
-- Purpose: Agregar campos de auditoría robustos a migrations_history
-- IDEMPOTENT: ✅ Safe to run multiple times
-- ============================================================================
```

### **4. Testing en Desarrollo**

```bash
# Siempre probar PRIMERO en local
php migration_runner.php --dry-run

# Ejecutar 2 veces para verificar idempotencia
php migration_runner.php
php migration_runner.php  # No debería dar errores
```

### **5. Monitoreo de Performance**

```sql
-- Ver migraciones que tardan más de 1 segundo
SELECT filename, execution_time_ms
FROM migrations_history
WHERE execution_time_ms > 1000
ORDER BY execution_time_ms DESC;
```

---

## 🔧 **Troubleshooting**

### **Problema: Migración Marcada como Fallida Pero el Error Ya Se Corrigió**

**Solución:**
```sql
-- Eliminar el registro fallido para permitir re-ejecución
DELETE FROM migrations_history WHERE filename = '2025_12_26_problematic.sql' AND status = 'failed';

-- O actualizar a rolled_back
UPDATE migrations_history SET status = 'rolled_back' WHERE filename = '2025_12_26_problematic.sql';
```

Luego re-ejecutar:
```bash
php migration_runner.php
```

### **Problema: Tabla `migrations_history` No Tiene Columnas Nuevas**

**Solución:**
```bash
# Ejecutar la migración que mejora la tabla
php migration_runner.php
```

La migración `2025_12_26_improve_migrations_tracking.sql` agregará las columnas faltantes de forma idempotente.

---

## 📊 **Comparación: Antes vs Ahora**

| Característica | Sistema Antiguo | Sistema Mejorado |
|----------------|-----------------|------------------|
| **Manejo de errores** | ❌ Detiene TODO el proceso | ✅ Continúa registrando error |
| **Tracking de batch** | ❌ No existe | ✅ Batch number automático |
| **Tiempo de ejecución** | ❌ No se mide | ✅ Milisegundos registrados |
| **Status detallado** | ❌ Solo "ejecutada" | ✅ success/failed/skipped/rolled_back |
| **Auditoría de usuario** | ❌ No se registra | ✅ Registra usuario del SO |
| **Tipo de migración** | ❌ No se diferencia | ✅ master/tenant/seed/default |
| **Comando status** | ⚠️  Básico (lista simple) | ✅ Tabla detallada + estadísticas |
| **Re-ejecución segura** | ❌ Causa errores | ✅ Idempotente + skip duplicados |
| **Debugging** | ❌ Difícil identificar errores | ✅ error_message completo |

---

## 🎉 **Resumen**

El sistema de migraciones ahora es:

1. ✅ **Robusto:** No se detiene por errores de duplicados
2. ✅ **Auditable:** Registro completo de quién, cuándo, cuánto tardó
3. ✅ **Monitoreable:** Estadísticas y performance tracking
4. ✅ **Resiliente:** Continúa ejecutando aunque algunas fallen
5. ✅ **Profesional:** Similar a Laravel Migrations, Doctrine, Flyway

**Versión:** 2.0
**Fecha:** 26 de Diciembre, 2025
**Autor:** Sistema Planillas Innova
