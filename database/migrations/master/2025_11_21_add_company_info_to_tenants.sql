-- Add company information to tenants table
-- This allows validation of duplicate RUCs at the master database level

ALTER TABLE tenants
  ADD COLUMN company_name VARCHAR(255) NULL AFTER slug,
  ADD COLUMN ruc VARCHAR(50) NULL UNIQUE AFTER company_name,
  ADD COLUMN admin_email VARCHAR(190) NULL AFTER ruc;

-- Add index for RUC lookups
CREATE INDEX idx_tenants_ruc ON tenants(ruc);

-- Add index for email lookups
CREATE INDEX idx_tenants_admin_email ON tenants(admin_email);
