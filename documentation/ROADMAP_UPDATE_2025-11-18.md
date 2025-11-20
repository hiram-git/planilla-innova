# ROADMAP Update — 2025-11-18 (v3.5.8)

- Multitenancy (DB por tenant) — scaffolding:
  - Conexión master (`config/master_database.php`, `App\Core\MasterDatabase`).
  - Migración `tenants` (master) para conexiones por empresa.
  - `App\Models\WizardModel` (provisión tenant) y rutas wizard en `App\Core\App` + `App\Controllers\WizardController` (namespace).
  - Validación de distribuidor por cURL con `.env` (`DISTRIBUTOR_VALIDATION_URL`).
- Vacaciones: filtrado por `tipo_planilla_id` desde sessionStorage y evento navbar; descripción de planillas en MAYUSCULAS.
- PDF Vacaciones: orientación horizontal; labels en español; sin “Dias Habiles”; mejor alineación; “Resumen de Dias” igual a “Balance Actual”.
- Asistencias: tolerancias aplicadas a entrada/salida y almuerzo; horas nocturnas se anulan dentro de tolerancia para turnos diurnos.

Proximos pasos clave
- TenantResolver + DatabaseManager.forTenant + middleware `TenantContext`.
- `importTenantSchema()` real (migraciones + semillas iniciales).
- Enforce CSRF en `/panel/attendance/process-day`.
- Preservar `tipo_planilla_id` dentro de enlaces del módulo vacaciones.
