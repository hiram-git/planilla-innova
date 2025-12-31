-- =====================================================
-- Migración: Agregar configuración de correo a tabla companies
-- Fecha: 31-Dic-2025 14:00
-- Descripción: Agregar columnas para configuración SMTP
-- =====================================================

-- Verificar si existe mail_host
SET @mail_host_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_host'
);

SET @sql = IF(@mail_host_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_host VARCHAR(255) NULL COMMENT ''Servidor SMTP'' AFTER email',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_port
SET @mail_port_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_port'
);

SET @sql = IF(@mail_port_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_port INT DEFAULT 587 COMMENT ''Puerto SMTP'' AFTER mail_host',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_username
SET @mail_username_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_username'
);

SET @sql = IF(@mail_username_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_username VARCHAR(255) NULL COMMENT ''Usuario SMTP'' AFTER mail_port',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_password
SET @mail_password_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_password'
);

SET @sql = IF(@mail_password_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_password VARCHAR(255) NULL COMMENT ''Contraseña SMTP (encriptada)'' AFTER mail_username',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_encryption
SET @mail_encryption_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_encryption'
);

SET @sql = IF(@mail_encryption_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_encryption VARCHAR(10) DEFAULT ''tls'' COMMENT ''Tipo encriptación: tls, ssl, none'' AFTER mail_password',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_from_address
SET @mail_from_address_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_from_address'
);

SET @sql = IF(@mail_from_address_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_from_address VARCHAR(255) NULL COMMENT ''Email remitente'' AFTER mail_encryption',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si existe mail_from_name
SET @mail_from_name_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'companies'
    AND COLUMN_NAME = 'mail_from_name'
);

SET @sql = IF(@mail_from_name_exists = 0,
    'ALTER TABLE companies ADD COLUMN mail_from_name VARCHAR(255) NULL COMMENT ''Nombre remitente'' AFTER mail_from_address',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
