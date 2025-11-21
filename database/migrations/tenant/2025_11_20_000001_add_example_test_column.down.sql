-- rollback for: 2025_11_20_000001
-- description: Remove test column from employees table

ALTER TABLE employees DROP INDEX idx_employees_migration_test;

ALTER TABLE employees DROP COLUMN migration_test_column;
