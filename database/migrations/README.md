# Migraciones de Base de Datos

## Nomenclatura de Archivos de Migración

### ✅ Formato Recomendado (Con Timestamp Completo)

```
YYYY_MM_DD_HHMMSS_descripcion_descriptiva.sql
```

**Ejemplo:**
```
2025_12_23_143025_add_creditor_to_loans.sql
2025_12_23_143026_create_loan_payments_table.sql
2025_12_23_150000_add_indexes_to_loans.sql
```

**Ventajas:**
- ✅ **Orden preciso**: Permite múltiples migraciones el mismo día sin conflictos
- ✅ **Trazabilidad**: Timestamp exacto de cuándo se creó la migración
- ✅ **Sin ambigüedad**: Orden de ejecución 100% determinista

### 📋 Formatos Soportados (Compatibilidad Legacy)

| Formato | Ejemplo | Uso |
|---------|---------|-----|
| `YYYY_MM_DD_HHMMSS_*` | `2025_12_23_143025_migration.sql` | ✅ **RECOMENDADO** |
| `YYYY_MM_DD_HHMM_*` | `2025_12_23_1430_migration.sql` | ⚠️ Aceptable (sin segundos) |
| `YYYY_MM_DD_*` | `2025_12_23_migration.sql` | ⚠️ Legacy (solo fecha) |
| `YYYYMMDDHHMMSS_*` | `20251223143025_migration.sql` | ✅ Compacto válido |
| `YYYYMMDD_*` | `20251223_migration.sql` | ⚠️ Legacy compacto |

### ❌ Formatos NO Soportados

- ❌ `YYYY-MM-DD_*` (guiones en fecha): `2025-12-23_migration.sql`
- ❌ `DD_MM_YYYY_*` (orden incorrecto): `23_12_2025_migration.sql`
- ❌ Sin prefijo de fecha: `add_column_to_table.sql`

## Cómo Crear una Nueva Migración

### Opción 1: Manualmente con Timestamp

**Bash/Linux:**
```bash
touch "$(date +%Y_%m_%d_%H%M%S)_descripcion_migracion.sql"
```

**PowerShell/Windows:**
```powershell
New-Item -Path "database\migrations\$(Get-Date -Format 'yyyy_MM_dd_HHmmss')_descripcion_migracion.sql"
```

**Git Bash (Windows):**
```bash
touch "$(date +%Y_%m_%d_%H%M%S)_descripcion_migracion.sql"
```

### Opción 2: Script Helper (Ver scripts/create-migration.sh)

```bash
./scripts/create-migration.sh "add_column_to_users"
# Crea: 2025_12_23_143025_add_column_to_users.sql
```

## Orden de Ejecución

Las migraciones se ejecutan en **orden cronológico** basado en el timestamp extraído del nombre del archivo:

1. **Extracción de timestamp**: Se parsea el nombre del archivo
2. **Normalización**: Todos los formatos se convierten a `YYYYMMDDHHmmss`
3. **Ordenamiento**: Se ordena numéricamente (oldest → newest)
4. **Ejecución secuencial**: Se ejecuta una por una en orden

**Ejemplo de orden de ejecución:**

```
2025_12_20_120000_create_loans.sql          → 20251220120000
2025_12_20_150000_add_indexes_loans.sql     → 20251220150000
2025_12_23_090000_alter_loans.sql           → 20251223090000
2025_12_23_143025_add_creditor.sql          → 20251223143025
2025_12_23_143026_add_payments.sql          → 20251223143026
```

## Buenas Prácticas

### ✅ DO (Hacer)

- ✅ Usar timestamps completos (`YYYY_MM_DD_HHMMSS`)
- ✅ Nombres descriptivos en inglés o español consistente
- ✅ Una responsabilidad por migración (single responsibility)
- ✅ Incluir rollback comments cuando sea posible
- ✅ Usar `IF NOT EXISTS` / `IF EXISTS` para idempotencia
- ✅ Probar la migración antes de commit

**Ejemplo de migración bien estructurada:**
```sql
-- Migración: Agregar campo 'creditor' a tabla loans
-- Fecha: 2025-12-23 14:30:25
-- Autor: Sistema
-- Descripción: Campo para identificar el acreedor del préstamo

-- ==== UP Migration ====
ALTER TABLE loans
ADD COLUMN IF NOT EXISTS creditor VARCHAR(100) NULL
COMMENT 'Nombre del acreedor (banco, institución, etc.)';

-- Crear índice para búsquedas rápidas
CREATE INDEX IF NOT EXISTS idx_loans_creditor
ON loans(creditor);

-- ==== DOWN Migration (Rollback) ====
-- Para revertir manualmente si es necesario:
-- ALTER TABLE loans DROP COLUMN IF EXISTS creditor;
-- DROP INDEX IF EXISTS idx_loans_creditor ON loans;
```

### ❌ DON'T (No Hacer)

- ❌ Cambiar nombres de migraciones ya ejecutadas en producción
- ❌ Usar fechas futuras o pasadas ficticias
- ❌ Incluir múltiples cambios no relacionados en una migración
- ❌ Olvidar validar que la tabla/columna existe antes de DROP
- ❌ Usar formatos de fecha inconsistentes (guiones vs guiones bajos)

## Sistema de Tracking

### ¿Cómo sabe el sistema qué migraciones ya se ejecutaron?

Actualmente el sistema **NO tiene tabla de tracking** (`migrations` table). Esto significa:

- ⚠️ **Re-ejecutar migraciones**: Si vuelves a ejecutar `importTenantSchema()`, intentará ejecutar TODAS las migraciones de nuevo
- ✅ **Solución actual**: Usar `IF NOT EXISTS` / `IF EXISTS` en las migraciones para hacerlas **idempotentes**
- 🔮 **Mejora futura**: Implementar tabla `migrations` para tracking (ver TODO.md)

### Idempotencia Recomendada

Siempre escribe migraciones que se puedan ejecutar múltiples veces sin errores:

```sql
-- ✅ BIEN (Idempotente)
CREATE TABLE IF NOT EXISTS nueva_tabla (...);
ALTER TABLE tabla ADD COLUMN IF NOT EXISTS nueva_columna VARCHAR(50);
DROP TABLE IF EXISTS tabla_temporal;

-- ❌ MAL (Falla en segunda ejecución)
CREATE TABLE nueva_tabla (...);
ALTER TABLE tabla ADD COLUMN nueva_columna VARCHAR(50);
DROP TABLE tabla_temporal;
```

## Ejemplos Prácticos

### Crear Tabla Nueva
```sql
-- 2025_12_23_143025_create_loan_payments.sql
CREATE TABLE IF NOT EXISTS loan_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_loan_payments_loan
    FOREIGN KEY (loan_id) REFERENCES loans(id)
    ON DELETE CASCADE,

  INDEX idx_loan_payments_date (payment_date),
  INDEX idx_loan_payments_loan (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Agregar Columna
```sql
-- 2025_12_23_143026_add_interest_rate_to_loans.sql
ALTER TABLE loans
ADD COLUMN IF NOT EXISTS interest_rate DECIMAL(5,2) NULL DEFAULT 0.00
COMMENT 'Tasa de interés anual (%)';

-- Actualizar registros existentes si es necesario
UPDATE loans SET interest_rate = 5.00 WHERE interest_rate IS NULL;
```

### Modificar Columna Existente
```sql
-- 2025_12_23_143027_increase_loan_amount_precision.sql
-- Cambiar amount de DECIMAL(10,2) a DECIMAL(12,2)
ALTER TABLE loans
MODIFY COLUMN amount DECIMAL(12,2) NOT NULL
COMMENT 'Monto del préstamo (mayor precisión)';
```

### Renombrar Tabla
```sql
-- 2025_12_23_143028_rename_partidas_to_cuentas_contables.sql
-- Verificar que la tabla existe antes de renombrar
SET @table_exists = (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE()
  AND table_name = 'partidas'
);

-- Renombrar solo si existe
SET @sql = IF(@table_exists > 0,
  'RENAME TABLE partidas TO cuentas_contables',
  'SELECT "Table partidas does not exist, skipping rename" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

## Troubleshooting

### Problema: Migración no se ejecuta

**Síntomas**: La migración existe en `/database/migrations/` pero no se aplica al crear una empresa.

**Causas posibles:**
1. ❌ Formato de nombre incorrecto (guiones en fecha)
2. ❌ Fecha futura que la ordena al final
3. ❌ Error SQL que detiene la ejecución

**Solución:**
```bash
# Verificar formato del nombre
ls database/migrations/ | grep "tu_migracion"

# Renombrar si tiene formato incorrecto
mv 2025-12-23_migration.sql 2025_12_23_$(date +%H%M%S)_migration.sql

# Verificar logs de error
tail -f storage/logs/api_*.log
```

### Problema: Migración falla con error SQL

**Solución:**
1. Revisar logs del sistema (ver stack trace completo)
2. Probar la migración manualmente en una BD de prueba
3. Agregar `IF NOT EXISTS` / `IF EXISTS` para hacerla idempotente
4. Verificar dependencias (otras tablas, columnas que deben existir)

## Recursos Adicionales

- **Crear script helper**: `scripts/create-migration.sh` (próximamente)
- **Documentación Laravel Migrations**: https://laravel.com/docs/migrations
- **SQL Idempotency Patterns**: https://en.wikipedia.org/wiki/Idempotence

---

**Última actualización**: 2025-12-23
**Autor**: Sistema de Planillas Innova
