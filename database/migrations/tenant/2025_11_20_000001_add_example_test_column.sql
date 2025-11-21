-- version: 2025_11_20_000001
-- description: Add test column for migration system validation

ALTER TABLE employees ADD COLUMN migration_test_column VARCHAR(50) DEFAULT 'test_value' COMMENT 'Columna de prueba para validar sistema de migraciones tenant';

ALTER TABLE employees ADD INDEX idx_employees_migration_test (migration_test_column);
