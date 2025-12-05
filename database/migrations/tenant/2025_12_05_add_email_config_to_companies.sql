-- Migración: Agregar configuración de correo electrónico a tabla companies
-- Fecha: 05-Dic-2025
-- Descripción: Añade campos para configurar el servidor SMTP y credenciales de correo

ALTER TABLE companies ADD COLUMN mail_host VARCHAR(255) DEFAULT NULL COMMENT 'Servidor SMTP (ej: smtp.zeptomail.com)' AFTER email;
ALTER TABLE companies ADD COLUMN mail_port INT DEFAULT 587 COMMENT 'Puerto SMTP (587 TLS, 465 SSL, 25 sin cifrado)' AFTER mail_host;
ALTER TABLE companies ADD COLUMN mail_username VARCHAR(255) DEFAULT NULL COMMENT 'Usuario SMTP para autenticación' AFTER mail_port;
ALTER TABLE companies ADD COLUMN mail_password VARCHAR(255) DEFAULT NULL COMMENT 'Contraseña SMTP (almacenada cifrada)' AFTER mail_username;
ALTER TABLE companies ADD COLUMN mail_encryption VARCHAR(10) DEFAULT 'tls' COMMENT 'Tipo de cifrado: tls, ssl, o vacío' AFTER mail_password;
ALTER TABLE companies ADD COLUMN mail_from_address VARCHAR(255) DEFAULT NULL COMMENT 'Dirección de correo remitente' AFTER mail_encryption;
ALTER TABLE companies ADD COLUMN mail_from_name VARCHAR(255) DEFAULT NULL COMMENT 'Nombre del remitente' AFTER mail_from_address;

-- Comentario adicional en la tabla
ALTER TABLE companies COMMENT = 'Tabla de empresas con configuración de correo SMTP integrada';
