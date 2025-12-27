-- Migración: Agregar configuración de correo electrónico a tabla companies
-- Fecha: 05-Dic-2025
-- Descripción: Añade campos para configurar el servidor SMTP y credenciales de correo
-- IDEMPOTENTE: Verifica si cada columna existe antes de crearla

-- mail_host
SET @sql_mail_host = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_host') = 0,
    'ALTER TABLE companies ADD COLUMN mail_host VARCHAR(255) DEFAULT NULL COMMENT ''Servidor SMTP (ej: smtp.zeptomail.com)'' AFTER email',
    'SELECT ''Column mail_host already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_host;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_port
SET @sql_mail_port = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_port') = 0,
    'ALTER TABLE companies ADD COLUMN mail_port INT DEFAULT 587 COMMENT ''Puerto SMTP (587 TLS, 465 SSL, 25 sin cifrado)'' AFTER mail_host',
    'SELECT ''Column mail_port already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_port;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_username
SET @sql_mail_username = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_username') = 0,
    'ALTER TABLE companies ADD COLUMN mail_username VARCHAR(255) DEFAULT NULL COMMENT ''Usuario SMTP para autenticación'' AFTER mail_port',
    'SELECT ''Column mail_username already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_username;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_password
SET @sql_mail_password = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_password') = 0,
    'ALTER TABLE companies ADD COLUMN mail_password VARCHAR(255) DEFAULT NULL COMMENT ''Contraseña SMTP (almacenada cifrada)'' AFTER mail_username',
    'SELECT ''Column mail_password already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_password;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_encryption
SET @sql_mail_encryption = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_encryption') = 0,
    'ALTER TABLE companies ADD COLUMN mail_encryption VARCHAR(10) DEFAULT ''tls'' COMMENT ''Tipo de cifrado: tls, ssl, o vacío'' AFTER mail_password',
    'SELECT ''Column mail_encryption already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_encryption;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_from_address
SET @sql_mail_from_address = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_from_address') = 0,
    'ALTER TABLE companies ADD COLUMN mail_from_address VARCHAR(255) DEFAULT NULL COMMENT ''Dirección de correo remitente'' AFTER mail_encryption',
    'SELECT ''Column mail_from_address already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_from_address;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- mail_from_name
SET @sql_mail_from_name = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
     AND TABLE_NAME = 'companies'
     AND COLUMN_NAME = 'mail_from_name') = 0,
    'ALTER TABLE companies ADD COLUMN mail_from_name VARCHAR(255) DEFAULT NULL COMMENT ''Nombre del remitente'' AFTER mail_from_address',
    'SELECT ''Column mail_from_name already exists'' AS msg'
);
PREPARE stmt FROM @sql_mail_from_name;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Comentario adicional en la tabla
ALTER TABLE companies COMMENT = 'Tabla de empresas con configuración de correo SMTP integrada';
