# Multitenancy DB‑per‑Tenant con BD Maestra

Fecha: 2025‑11‑15  
Estado: Propuesta técnica accionable para el proyecto “planilla‑innova”.

## 1) Objetivo

- Aislar datos por cliente usando una base de datos por tenant (DB‑per‑tenant) y una base de datos maestra para registrar metadatos y credenciales de conexión.  
- Minimizar cambios disruptivos, manteniendo la arquitectura MVC actual y la compatibilidad con módulos de Vacaciones, Planillas y Asistencias.

## 2) Modelo de Tenancy

- BD Maestra: centraliza la tabla `tenants` con credenciales, licencia y estado.  
- BD por Tenant: cada cliente tiene su propia BD con el mismo esquema y migraciones.

Beneficios:
- Aislamiento fuerte (riesgo bajo de fuga de datos).  
- Backups y restauración por cliente.  
- Escalado y mantenimiento por tenant.

## 3) Esquema BD Maestra (propuesta)

Tabla `tenants` (campos principales):
- `id` (PK), `slug` (UNIQUE), `domain` (UNIQUE, NULL), `subdomain` (UNIQUE, NULL)  
- `status` ENUM('ACTIVE','SUSPENDED'), `license_key`, `license_status`, `license_expires_at`  
- `db_host`, `db_port`, `db_name`, `db_user`, `db_pass_enc` (cifrado), `db_charset`  
- `created_at`, `updated_at`, `last_healthcheck_at`

Índices sugeridos: `(slug)`, `(domain)`, `(subdomain)`, `(status)`.

Notas:
- `db_pass_enc` debe cifrarse (APP_KEY/KMS).  
- Opcional `tenant_settings` para flags (e.g., usa_overtime, usa_paid_holidays).

## 4) Resolución de Tenant

Componente TenantResolver (orden de resolución):
1. Dominio completo (mapeo `tenants.domain`).  
2. Subdominio (mapeo `tenants.subdomain`).  
3. Prefijo en ruta `/t/{slug}` (si se habilita).  
4. Sesión post‑login (usuario pertenece a un `tenant_id`).

Flujo:
- Consulta BD Maestra → valida status/licencia → construye `TenantContext` (id/slug/host/credenciales).  
- Cache en memoria (APCu/static) 60–120s para reducir latencia.

## 5) Gestión de Conexiones

`DatabaseManager` (nuevo):
- `forMaster()`: PDO a la BD maestra.  
- `forTenant(TenantContext $ctx)`: devuelve (y recicla por request) PDO hacia la BD del tenant.  
- Pool por `tenant_key` (host+db+user).  
- Manejo de fallos: si conexión falla → página de mantenimiento “tenant unavailable”.

Base `Model`:
- Dejar de usar un singleton global rígido.  
- Inyectar siempre la conexión vía `DatabaseManager::forTenant(TenantContext::current())` en módulos de negocio.

## 6) Cambios en Código (mínimos y progresivos)

Nuevos archivos (esqueleto):
- `app/Core/TenantContext.php` — contexto inmutable por request (id, slug, credenciales).  
- `app/Core/TenantResolver.php` — resuelve tenant desde host/ruta/sesión (usa BD maestra).  
- `app/Core/DatabaseManager.php` — gestiona conexiones master/tenant (pool simple).  
- `config/master_database.php` — credenciales BD maestra.

Modificaciones:
- `App::run()`: invocar TenantResolver al inicio de rutas de negocio y fijar `TenantContext`.  
- `app/Core/Model.php`: usar `DatabaseManager::forTenant(...)` para obtener conexión.  
- Servicios y controladores que usan `Database::getInstance()` directo (e.g., PDFReportController, servicios de vacaciones/asistencias) → migrarlos a `DatabaseManager`.

## 7) Migraciones y Aprovisionamiento

- BD Maestra: migración `create_tenants`.  
- CLI/Panel Admin para alta de tenant:
  - `tenants:create --slug acme --domain acme.empresa.com --db_name planilla_acme --db_user ...`  
  - Crea registro en master → crea BD del tenant → corre migraciones/seeds en la BD del tenant.  
- Migraciones de negocio: correr sobre cada tenant con `tenants:migrate --all`.

## 8) Sesión, Seguridad y CSRF

- Nombre de sesión por tenant: `planilla_{slug}` (evita colisiones).  
- CSRF por sesión se mantiene.  
- `PermissionMiddleware` sigue vigente; todo debe usar la conexión del tenant.  
- Licencia: validar en BD maestra (con TTL de cache). Suspender tenants vencidos (403/402).

## 9) Archivos y Assets

- Directorios por tenant: `images/{slug}/...`, `storage/logs/{slug}/...`  
- `UrlHelper::asset` puede aceptar base por tenant si se guardan logos por cliente.  
- PDFs/Exports siempre con datos del tenant (conexión correcta + rutas/host correctos).

## 10) Health & Monitoring

- `last_healthcheck_at` en `tenants`.  
- Tareas programadas: verificar reachability/latencia de cada BD de tenant, renovar caches.  
- Alertas si un tenant queda inaccesible.

## 11) Testing y Validación

- Unit tests: TenantResolver, DatabaseManager, Model scoping.  
- E2E: login simultáneo de dos tenants en subdominios distintos; validar aislamiento de datos.  
- Seguridad: detector de usos de `Database::getInstance()` fuera de master en rutas de negocio.

## 12) Roadmap de Implementación (propuesto)

1. Infraestructura base: tabla `tenants` en master + `TenantResolver`, `TenantContext`, `DatabaseManager`.  
2. Enrutador: resolver Tenant al inicio; romper en dev si falta TenantContext en rutas de negocio.  
3. Migrar módulos críticos a `DatabaseManager` (Vacaciones, Planillas, Asistencias).  
4. CLI de provisioning + `tenants:migrate --all`; crear 1–2 tenants de prueba.  
5. Mover almacenamiento de archivos/imagenes a carpetas por tenant.  
6. Healthchecks + alertas; documentación operativa (backups por tenant, rotación de claves).  
7. Endurecer permisos (unificar middleware) y limpiar ramas legacy.

## 13) Riesgos y Mitigaciones

- Olvidar migrar una consulta a conexión tenant:  
  - Guardas en `DatabaseManager` + grep/CI para `Database::getInstance()` en capas de negocio.  
- Caída de una BD de tenant:  
  - Página “tenant unavailable”, reintentos, healthcheck proactivo.  
- Rotación de credenciales:  
  - `db_pass_enc` cifrado; rotación controlada en BD maestra; recarga dinámica de pool.

## 14) Anexos

### 14.1) SQL sugerido (BD Maestra)

```sql
CREATE TABLE tenants (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  slug VARCHAR(80) NOT NULL UNIQUE,
  domain VARCHAR(190) NULL UNIQUE,
  subdomain VARCHAR(120) NULL UNIQUE,
  status ENUM('ACTIVE','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  license_key VARCHAR(190) NULL,
  license_status VARCHAR(40) NULL,
  license_expires_at DATETIME NULL,
  db_host VARCHAR(190) NOT NULL,
  db_port INT NOT NULL DEFAULT 3306,
  db_name VARCHAR(190) NOT NULL,
  db_user VARCHAR(190) NOT NULL,
  db_pass_enc VARBINARY(2048) NOT NULL,
  db_charset VARCHAR(40) NOT NULL DEFAULT 'utf8mb4',
  last_healthcheck_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE INDEX idx_tenants_status ON tenants(status);
```

### 14.2) Interfaces PHP (pseudocódigo)

```php
// app/Core/TenantContext.php
final class TenantContext {
  public function __construct(
    public readonly int $id,
    public readonly string $slug,
    public readonly string $dbHost,
    public readonly int $dbPort,
    public readonly string $dbName,
    public readonly string $dbUser,
    public readonly string $dbPass,
    public readonly string $dbCharset
  ) {}
  public static function current(): self { /* from request-scoped storage */ }
}

// app/Core/TenantResolver.php
final class TenantResolver {
  public function resolveFromRequest(): TenantContext { /* query master DB */ }
}

// app/Core/DatabaseManager.php
final class DatabaseManager {
  public static function forMaster(): PDO { /* singleton master */ }
  public static function forTenant(TenantContext $ctx): PDO { /* pool per tenant */ }
}
```

### 14.3) CLI de Provisioning (ideas)

- `php cli tenants:create --slug acme --domain acme.empresa.com --db_name planilla_acme --db_user ...`  
- `php cli tenants:migrate --tenant=acme` / `--all`  
- `php cli tenants:healthcheck --all`

---

Con este diseño DB‑per‑tenant con BD maestra, el sistema gana aislamiento, control operativo y escalabilidad, con cambios controlados en la base de código actual.

