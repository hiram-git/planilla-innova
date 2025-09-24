# 🗃️ Guía de Migraciones - Planilla Innova

## 📋 Nomenclatura de Archivos

### Formato Obligatorio
```
YYYY_MM_DD_HHII_nombre_descriptivo.sql
```

**Componentes:**
- `YYYY`: Año (4 dígitos)
- `MM`: Mes (2 dígitos, 01-12)
- `DD`: Día (2 dígitos, 01-31)
- `HHII`: Hora y minuto (4 dígitos, 24h format)
- `nombre_descriptivo`: Descripción en snake_case

### Ejemplos
```
2025_09_24_0930_add_employee_photos.sql
2025_09_24_1445_create_payroll_taxes_table.sql
2025_09_24_1630_update_user_permissions.sql
2025_09_25_0800_fix_salary_calculations.sql
```

## 🎯 Categorías de Migraciones

### 1. **CREATE** - Crear nuevas estructuras
```sql
-- 2025_09_25_0900_create_vacation_requests_table.sql
CREATE TABLE vacation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB;
```

### 2. **ADD** - Agregar columnas/índices
```sql
-- 2025_09_25_1000_add_phone_to_employees.sql
ALTER TABLE employees
ADD COLUMN phone VARCHAR(20) NULL AFTER email,
ADD COLUMN emergency_contact VARCHAR(100) NULL AFTER phone;

-- Crear índice si es necesario
CREATE INDEX idx_employees_phone ON employees(phone);
```

### 3. **MODIFY** - Modificar estructuras existentes
```sql
-- 2025_09_25_1100_modify_salary_precision.sql
ALTER TABLE employees
MODIFY COLUMN salary DECIMAL(12,2) NOT NULL;

ALTER TABLE payroll_details
MODIFY COLUMN amount DECIMAL(12,2) NOT NULL;
```

### 4. **FIX** - Correcciones y bugfixes
```sql
-- 2025_09_25_1200_fix_duplicate_employee_ids.sql
-- Eliminar duplicados
DELETE e1 FROM employees e1
INNER JOIN employees e2
WHERE e1.id > e2.id
AND e1.employee_id = e2.employee_id;

-- Agregar constraint único
ALTER TABLE employees
ADD CONSTRAINT uk_employees_employee_id UNIQUE (employee_id);
```

### 5. **DATA** - Migraciones de datos
```sql
-- 2025_09_25_1300_migrate_legacy_payroll_data.sql
-- Migrar datos de tabla legacy a nueva estructura
INSERT INTO payroll_details (employee_id, concept_id, amount, period)
SELECT
    employee_id,
    (SELECT id FROM concepts WHERE code = legacy_concept_code),
    amount,
    payroll_period
FROM legacy_payroll_data
WHERE migrated = 0;

-- Marcar como migrados
UPDATE legacy_payroll_data SET migrated = 1;
```

### 6. **DROP** - Eliminar estructuras
```sql
-- 2025_09_25_1400_drop_legacy_tables.sql
-- CUIDADO: Solo después de confirmar que no se usan
DROP TABLE IF EXISTS legacy_payroll_data;
DROP TABLE IF EXISTS temp_migration_table;

-- Eliminar columnas obsoletas
ALTER TABLE employees DROP COLUMN old_employee_code;
```

## 🚀 Comandos de Migración

### Ver estado actual
```bash
php database/migrations/migration_runner.php --status
```

### Dry run (simular sin ejecutar)
```bash
php database/migrations/migration_runner.php --dry-run
```

### Ejecutar todas las migraciones pendientes
```bash
php database/migrations/migration_runner.php
```

### Ejecutar hasta versión específica
```bash
php database/migrations/migration_runner.php --version=3.4.0
```

## 📁 Estructura de Directorios

```
database/
├── migrations/                    ← Migraciones nuevas (usar este)
│   ├── migration_runner.php      ← Script executor
│   └── YYYY_MM_DD_HHII_*.sql     ← Migraciones ordenadas
├── migrations_consolidated/       ← Migraciones legacy consolidadas
├── backups/                      ← Respaldos BD
├── install/                      ← Scripts instalación inicial
├── schema.sql                    ← Schema base actual
└── MIGRATION_GUIDE.md           ← Esta guía
```

## ✅ Mejores Prácticas

### 1. **Antes de crear una migración**
- [ ] Verificar que la funcionalidad requiere cambios en BD
- [ ] Revisar si existe migración similar pendiente
- [ ] Hacer backup de BD en desarrollo
- [ ] Probar cambios manualmente primero

### 2. **Al escribir la migración**
- [ ] Usar nomenclatura correcta YYYY_MM_DD_HHII_
- [ ] Incluir comentarios explicativos
- [ ] Usar transacciones para cambios críticos
- [ ] Incluir rollback plan en comentarios
- [ ] Verificar sintaxis SQL

### 3. **Contenido de archivo**
```sql
-- Migración: Agregar campos de contacto a empleados
-- Versión: 3.4.0
-- Fecha: 2025-09-25
-- Autor: Sistema Automático
--
-- Descripción:
-- Agrega campos phone y emergency_contact a tabla employees
-- para mejorar gestión de información de contacto
--
-- Rollback:
-- ALTER TABLE employees DROP COLUMN phone, DROP COLUMN emergency_contact;

START TRANSACTION;

ALTER TABLE employees
ADD COLUMN phone VARCHAR(20) NULL AFTER email,
ADD COLUMN emergency_contact VARCHAR(100) NULL AFTER phone;

CREATE INDEX idx_employees_phone ON employees(phone);

COMMIT;
```

### 4. **Testing y validación**
- [ ] Ejecutar dry-run primero
- [ ] Verificar en BD de desarrollo
- [ ] Probar rollback si es posible
- [ ] Confirmar que aplicación sigue funcionando
- [ ] Documentar cambios en CHANGELOG.md

### 5. **Para producción**
- [ ] Coordinar ventana de mantenimiento
- [ ] Hacer backup completo de BD
- [ ] Ejecutar en staging primero
- [ ] Monitorear logs durante ejecución
- [ ] Verificar funcionalidad post-migración

## ⚠️ Casos Especiales

### Migraciones grandes (>5 min)
```sql
-- Para migraciones que toman mucho tiempo
-- Usar en horarios de bajo tráfico
-- Ejemplo: migración masiva de datos

-- Configurar timeout más largo
SET SESSION max_execution_time = 3600;
SET SESSION innodb_lock_wait_timeout = 3600;

-- Procesar en lotes
DELIMITER //
CREATE PROCEDURE migrate_large_dataset()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE batch_size INT DEFAULT 1000;
    DECLARE current_id INT DEFAULT 0;

    migration_loop: LOOP
        -- Procesar lote
        UPDATE large_table
        SET new_field = calculated_value
        WHERE id BETWEEN current_id AND current_id + batch_size
        AND new_field IS NULL;

        -- Verificar si quedan registros
        IF ROW_COUNT() = 0 THEN
            LEAVE migration_loop;
        END IF;

        SET current_id = current_id + batch_size;
    END LOOP;
END //
DELIMITER ;

CALL migrate_large_dataset();
DROP PROCEDURE migrate_large_dataset;
```

### Migraciones con dependencias externas
```sql
-- Verificar que tablas/datos requeridos existan
SELECT COUNT(*) INTO @table_exists
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'required_table';

IF @table_exists = 0 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Tabla requerida no existe. Ejecutar migración previa.';
END IF;
```

## 🔄 Sistema de Control de Versiones

### Tabla de tracking
```sql
-- Creada automáticamente por migration_runner.php
CREATE TABLE migrations_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    version VARCHAR(20) NULL,
    checksum VARCHAR(32) NULL
) ENGINE=InnoDB;
```

### Verificación de integridad
El sistema automáticamente:
- ✅ Verifica que no se re-ejecuten migraciones
- ✅ Calcula checksum para detectar modificaciones
- ✅ Registra fecha/hora de ejecución
- ✅ Asocia migraciones con versiones del sistema

## 📞 Soporte

Para dudas sobre migraciones:
1. Revisar esta guía completa
2. Verificar logs en `migrations_history`
3. Consultar `migration_consolidation_plan.md`
4. Usar dry-run antes de ejecutar en producción

---

**✅ SISTEMA DE MIGRACIONES PLANILLA INNOVA v3.3.3**
*Ordenado, versionado y listo para producción* 🚀