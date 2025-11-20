-- Master DB migration: tenants table for multitenancy (DB-per-tenant)

CREATE TABLE IF NOT EXISTS tenants (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL UNIQUE,
  domain VARCHAR(190) NULL UNIQUE,
  subdomain VARCHAR(120) NULL UNIQUE,
  status ENUM('ACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  license_key VARCHAR(190) NULL,
  license_status VARCHAR(40) NULL,
  license_expires_at DATETIME NULL,
  db_host VARCHAR(190) NULL,
  db_port INT NULL,
  db_name VARCHAR(190) NULL,
  db_user VARCHAR(190) NULL,
  db_pass_enc VARBINARY(2048) NULL,
  db_charset VARCHAR(40) NULL,
  last_healthcheck_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tenants_status ON tenants(status);

