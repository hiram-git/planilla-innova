# TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2025-11-21 (v3.5.9)

## ✅ Completado Recientemente (v3.5.9 - 21-Nov-2025)

### Employee Import System + Wizard UI
- [x] Actualizar template Excel con 3 campos nuevos (email*, marca_asistencia, permite_horas_extras)
- [x] Implementar validación email requerido con formato FILTER_VALIDATE_EMAIL
- [x] Crear método `formatBoolean()` para conversión flexible (1/0, SI/NO, YES/NO)
- [x] Integrar creación automática registros `employee_payroll_salaries` con auditoría
- [x] Asignar foto por defecto automáticamente (images/facebook-profile-image.jpeg)
- [x] Actualizar instrucciones Excel con 3 secciones nuevas (obligatorios/opcionales/automáticos)
- [x] Refactorizar CSS wizard `/crear-empresa` para márgenes simétricos perfectos
- [x] Implementar padding uniforme 40px + responsive (375px/1024px/1920px)
- [x] Mejorar botones (padding 12x32, min-height 48px) + separador visual
- [x] Testing: importación Excel + wizard responsive + integración salarios

---

## Multitenancy (DB por tenant)
- [x] Scaffolding: `MasterDatabase`, migración `tenants`, `WizardModel`, rutas wizard.
- [x] Validación de distribuidor por cURL configurable vía `.env`.
- [ ] `TenantResolver` (dominio/subdominio/slug) y `DatabaseManager::forTenant`.
- [ ] Middleware para fijar `TenantContext` en rutas de negocio.
- [ ] `importTenantSchema()` para ejecutar migraciones reales del tenant.
- [ ] Semillas iniciales (empresa/admin) y cifrado de credenciales con `APP_KEY`.
- [ ] Panel admin para gestionar tenants (CRUD completo).
- [ ] Testing completo aislamiento tenants.

## Vacaciones (Panamá)
- [x] Auto-filtro por `tipo_planilla_id` en `/panel/vacation` (sessionStorage + evento navbar).
- [x] Descripción de planillas de vacaciones en MAYÚSCULAS.
- [x] PDF horizontal con labels en español y mejor alineación.
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
- [x] Fix base date para comparaciones datetime correctas.
- [ ] Validar CSRF en `AttendanceController::processDay`.
- [ ] Completar implementación de tolerancias en UI y reportes.
- [ ] Backoffice para aprobar `overtime_approval` y reportes asociados.
- [ ] Pruebas de regresión: días feriados pagados + tolerancias.
- [ ] Edge cases: empleados con múltiples turnos, cambios de horario mid-período.

## Seguridad
- [ ] Unificar validación en `PermissionMiddleware` y retirar ramas legacy `$_SESSION['permissions']`.
- [ ] Revisar endpoints AJAX para CSRF y permisos finos.
- [ ] Validar CSRF en `AttendanceController::processDay`.

## Frontend
- [ ] Extraer JS inline a módulos (balance/show) y reutilizar helpers.
- [ ] Validar fallback jQuery en escenarios sin red/CDN.

## Frontend
- [x] Guards jQuery para evitar `$ is not defined`.
- [ ] Extraer JS inline a módulos (balance/show) y reutilizar helpers.
- [ ] Validar fallback jQuery en escenarios sin red/CDN.

## Documentación
- [x] Agregar nota v3.5.9 (employee import overhaul + wizard UI improvements)
- [x] Changelog detallado v3.5.9.md con ~650 líneas de documentación
- [x] Actualizar CHANGELOG.md con índice v3.5.9
- [x] Actualizar TODO.md con tareas completadas v3.5.9
- [x] Actualizar ROADMAP.md con progreso Employee Import 100%
- [x] Actualizar CLAUDE.md con detalles técnicos v3.5.9
- [x] Mantener `documentation/changelog/` por versión e indexar en `CHANGELOG.md`
- [ ] Revisar ROADMAP metas Q1 2026 para multitenancy completo

