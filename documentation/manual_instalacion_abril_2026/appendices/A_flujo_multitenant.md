# Apéndice A — Flujo Multi-Tenant

Este apéndice describe la arquitectura *multi-tenant* del Sistema de
Planillas INNOVA desde el punto de vista de instalación y operación.
La gestión funcional desde la UI (panel Backoffice) se documenta en el
Manual de Usuario §9.4.

## Visión general

INNOVA Planillas opera bajo un modelo **"una base de datos por tenant"**
(*database per tenant*). Cada empresa cliente tiene:

- Su propia **base de datos** MySQL o PostgreSQL aislada.
- Su propia **clave de licencia** (`license_key`).
- Sus propios **usuarios administradores**.
- Su propio **almacenamiento de archivos** bajo `storage/tenants/{db_name}/`.

Existe además una **base de datos maestra** (`planilla_master` o la que
se configure como `DB_DATABASE` en `.env` principal) que contiene la
tabla central `tenants` con el registro de todas las empresas.

## Diagrama de componentes

```
                       +------------------------+
                       |  Request del usuario   |
                       |  (navegador web)       |
                       +-----------+------------+
                                   |
                                   v
                       +------------------------+
                       |  Nginx + PHP-FPM       |
                       |  planilla-sistema      |
                       +-----------+------------+
                                   |
                                   v
                       +------------------------+
                       |  TenantResolver        |
                       |  (app/Core)            |
                       +-----------+------------+
                                   |
                   +---------------+---------------+
                   |                               |
                   v                               v
      +------------------------+    +----------------------------+
      |  BD Master             |    |  BD Tenant (por empresa)   |
      |  - tabla `tenants`     |    |  - empleados               |
      |  - tabla `admins`      |    |  - planillas               |
      |    (super admins)      |    |  - conceptos               |
      |  - licencias           |    |  - acumulados              |
      +------------------------+    +----------------------------+
                                   ^
                                   |
                       +------------------------+
                       |  storage/tenants/      |
                       |    {db_name}/          |
                       |      uploads/          |
                       |      logs/             |
                       +------------------------+
```

## Tabla central `tenants`

La tabla `tenants` en la BD maestra guarda la configuración de cada
empresa. Campos principales:

| Campo                 | Tipo        | Propósito                                            |
|-----------------------|-------------|------------------------------------------------------|
| `id`                  | INT PK      | Identificador interno                                |
| `company_name`        | VARCHAR     | Razón social visible                                 |
| `ruc`                 | VARCHAR     | Identificación fiscal (login multi-tenant)           |
| `slug`                | VARCHAR     | Alias URL-safe                                       |
| `license_key`         | VARCHAR     | Clave de licencia (prioridad en el login)            |
| `license_expires_at`  | DATE        | Fecha expiración de licencia                         |
| `db_name`             | VARCHAR     | Nombre de la BD del tenant                           |
| `db_host`             | VARCHAR     | Host (default: mismo servidor que master)            |
| `db_port`             | INT         | Puerto                                               |
| `db_user`             | VARCHAR     | Usuario MySQL del tenant                             |
| `db_pass_enc`         | TEXT        | Contraseña **encriptada** con `APP_KEY`              |
| `db_charset`          | VARCHAR     | Default `utf8mb4`                                    |
| `status`              | ENUM        | `ACTIVE`, `SUSPENDED`, `EXPIRED`                     |

> La contraseña de la BD del tenant nunca se guarda en texto plano.
> Usa cifrado simétrico con la llave del entorno (`APP_KEY`). Si rota
> `APP_KEY` debe re-encriptar `db_pass_enc` de todos los tenants antes.

## Ciclo de vida de un tenant

### 1. Creación (desde Panel Backoffice)

El super administrador del sistema (`is_system_admin = 1`) accede al
panel Backoffice y ejecuta el wizard de creación de empresa:

1. **Datos de la empresa**: nombre, RUC, slug.
2. **Conexión BD**: MySQL o PostgreSQL, credenciales.
3. **Usuario admin inicial**: email + contraseña.
4. **Confirmación**: el sistema prueba la conexión.
5. **Migración automática**: se ejecutan todas las migraciones
   pendientes sobre la nueva BD.
6. **Seed inicial**: se insertan conceptos base, roles, menú.

### 2. Acceso (login multi-tenant)

En la pantalla de login (`/panel`):

1. Usuario ingresa **código de empresa** (license_key, RUC, o slug).
2. `TenantResolver::resolveByCompanyCode()` busca el tenant en master.
3. Si existe y está `ACTIVE`, conecta a su BD.
4. Valida credenciales del usuario contra la BD del tenant.
5. Valida licencia (si `license_expires_at < hoy`, bloquea acceso
   excepto super admin del sistema).
6. Establece sesión con `tenant_id`, `tenant_license`, `tenant_db`.

### 3. Operación diaria

Todas las operaciones del usuario impactan exclusivamente en **su BD**.
El sistema no tiene queries que crucen múltiples tenants (excepto el
panel Backoffice del super admin).

Los archivos subidos (documentos de empleados, PDFs, exportaciones)
se guardan en `storage/tenants/{db_name}/` — aislados físicamente.

### 4. Suspensión / Expiración

Al marcar `status = 'SUSPENDED'` o al expirar la licencia, el login del
tenant queda bloqueado. La BD sigue existiendo y puede reactivarse
desde el panel Backoffice. Los datos **no** se eliminan automáticamente.

### 5. Backup por tenant

Cada BD de tenant puede respaldarse independientemente. Recomendado un
cron que itere la tabla `tenants` y genere un `mysqldump` por cada una:

```bash
#!/bin/bash
# backup-all-tenants.sh
MASTER_DB="planilla_master"
BACKUP_DIR="/var/backups/planilla/tenants"
mkdir -p "$BACKUP_DIR"

TENANTS=$(mysql -N -e "SELECT db_name FROM ${MASTER_DB}.tenants WHERE status='ACTIVE'")
for db in $TENANTS; do
  ts=$(date +%Y%m%d_%H%M%S)
  mysqldump --single-transaction "$db" | gzip > "$BACKUP_DIR/${db}_${ts}.sql.gz"
done
```

## Variables `.env` relevantes

En el servidor de producción:

```ini
# BD maestra (conexión por defecto cuando no hay tenant)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=planilla_master
DB_USERNAME=planilla_master_user
DB_PASSWORD=********

# BD de tenants (credenciales comunes usadas al provisionar nuevos tenants)
TENANT_DB_HOST=localhost
TENANT_DB_PORT=3306
TENANT_DB_USER=planilla_tenant_admin
TENANT_DB_PASS=********
TENANT_DB_CHARSET=utf8mb4

# Clave maestra para cifrar db_pass_enc y otros datos sensibles
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=

# Validación de licencia contra distribuidor (opcional)
DISTRIBUTOR_VALIDATION_URL=https://licencias.innova.com/api/validate
DISTRIBUTOR_VALIDATION_TIMEOUT=10
LICENSING_ALLOW_OFFLINE=true
```

## Migraciones multi-tenant

Las migraciones están en `database/migrations/tenant/` y se ejecutan
individualmente por cada BD de tenant. El runner es
`database/migrations/tenant/migrate_all_tenants.php`:

```bash
cd /var/www/planilla-sistema
php database/migrations/tenant/migrate_all_tenants.php
```

El script:

1. Lee la tabla `tenants` de master.
2. Por cada tenant con `status = 'ACTIVE'`:
   - Conecta a su BD.
   - Consulta `migrations_history` del tenant.
   - Ejecuta las migraciones faltantes en orden.
   - Registra cada ejecución.

## Permisos y roles

Cada BD tenant tiene **su propio juego de roles y permisos**:

- Roles (admin empresa, operador, sólo lectura, etc.) con 25 módulos
  (`menu_items`) y permisos granulares (`role_permissions`).
- Usuarios (`admins`) con `role_id`.

**Super administradores del sistema** (los que pueden usar el panel
Backoffice) se identifican por un campo adicional:

```sql
ALTER TABLE admins ADD COLUMN is_system_admin TINYINT(1) DEFAULT 0;
```

Este campo existe tanto en master como en cada tenant. Los super
administradores pueden alternar entre tenants y ejecutar operaciones
globales.

## Checklist de validación post-instalación

Para confirmar que el sistema multi-tenant funciona correctamente:

- [ ] Tabla `tenants` existe en BD maestra con al menos 1 registro `ACTIVE`.
- [ ] `APP_KEY` definida en `.env` y `db_pass_enc` encriptada.
- [ ] Login con `license_key` del tenant funciona.
- [ ] Al cambiar de tenant desde Backoffice, la UI refleja los datos
      correctos (empleados, planillas).
- [ ] Subir un archivo en un tenant no es accesible desde otro (revisar
      `storage/tenants/`).
- [ ] Migraciones pendientes ejecutan correctamente en todos los tenants.
- [ ] Backup por tenant genera un archivo `.sql.gz` por BD activa.
- [ ] Licencia próxima a vencer muestra aviso en la UI.
- [ ] Licencia vencida bloquea el acceso (excepto super admin del sistema).
