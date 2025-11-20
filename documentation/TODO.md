# TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2025-11-18 (v3.5.8)

## Vacaciones (Panamá)
- [x] Auto-filtro por `tipo_planilla_id` en `/panel/vacation` (sessionStorage + evento navbar).
- [x] Descripción de planillas de vacaciones en MAYÚSCULAS.
- [ ] Preservar `tipo_planilla_id` en enlaces internos del módulo vacaciones.
- [ ] Envolver aprobación y generación de planilla en transacciones DB.
- [ ] Parametrizar IDs de conceptos (SS/SE) por código/config.
- [ ] Mostrar desglose por año en PDF (opcional) y validar cifras contra UI.
- [ ] Tests unitarios: `calculateVacationDailySalary` (11m completos, parciales, sin acumulados).

## Motor de Fórmulas
- [ ] Documentar `CONCEPTO("NOMBRE")` con ejemplos.
- [ ] Tests: combinaciones ACUMULADOS + CONCEPTO + SI().

## Asistencias (Horas extra y tolerancias)
- [x] Integrar tolerancias de horario y almuerzo en cálculo de horas.
- [x] Clamp de horas nocturnas para turnos diurnos dentro de tolerancia.
- [ ] Completar implementación de tolerancias en UI y reportes.
- [ ] Backoffice para aprobar `overtime_approval` y reportes asociados.
- [ ] Pruebas de regresión: días feriados pagados + tolerancias.

## Seguridad
- [ ] Unificar validación en `PermissionMiddleware` y retirar ramas legacy `$_SESSION['permissions']`.
- [ ] Revisar endpoints AJAX para CSRF y permisos finos.
- [ ] Validar CSRF en `AttendanceController::processDay`.

## Frontend
- [ ] Extraer JS inline a módulos (balance/show) y reutilizar helpers.
- [ ] Validar fallback jQuery en escenarios sin red/CDN.

## Documentación
- [x] Agregar nota v3.5.8 (multitenancy, filtros vacaciones, tolerancias asistencia).
- [ ] Mantener `documentation/changelog/` por versión e indexar en `CHANGELOG.md`.
- [ ] Revisar ROADMAP metas Q4 2025 tras horas extra.

## Multitenancy (DB por tenant)
- [x] Scaffolding: `MasterDatabase`, migración `tenants`, `WizardModel`, rutas wizard.
- [x] Validación de distribuidor por cURL configurable vía `.env`.
- [ ] `TenantResolver` (dominio/subdominio/slug) y `DatabaseManager::forTenant`.
- [ ] Middleware para fijar `TenantContext` en rutas de negocio.
- [ ] `importTenantSchema()` para ejecutar migraciones reales del tenant.
- [ ] Semillas iniciales (empresa/admin) y cifrado de credenciales con `APP_KEY`.

