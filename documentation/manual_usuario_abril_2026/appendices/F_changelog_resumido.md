# Changelog resumido {.unnumbered}

Historial compacto de versiones v3.4.x a v3.5.22. Para el detalle completo
consultar `documentation/CHANGELOG.md` y la carpeta
`documentation/changelog/` del repositorio.

## v3.5.22 (28-Feb-2026) — Liquidaciones Report Refactor + Vacation UX + Tolerancias

- Refactorización `LiquidationReportController` (1196 líneas, SRP aplicado).
- Métodos cálculo avanzado: `getAccumulatedTypesForLiquidation()`,
  `getMonthlyTotalsForLiquidation()`.
- UX Vacaciones: ocultar Valor Día y Monto Compensación en *crear*;
  singular/plural de años/días.
- Fix namespace `TenantStorage` en multi-tenant.

## v3.5.21 (25-Feb-2026) — Animaciones GSAP + Innova Export fixes

- 28 funciones GSAP en Liquidaciones Estimadas, Planillas Estimadas, Innova
  Export, Crear Planilla.
- Fix Innova Export: session key, view path, parse error, rendering.
- Fix redirect loop en `Controller.php` (sesión `admin_id` → `admin`).

## v3.5.20 — Export ERP INNOVA
## v3.5.19 — Campos Adicionales Personalizados
## v3.5.16 — Expedientes Empleados
## v3.5.15 — UNIDAD dinámica en fórmulas
## v3.5.13 — Permisos granulares (`canAccessRoute()`)
## v3.5.12 — Acumulados export Excel
## v3.5.10 — Panel Backoffice multi-tenant
## v3.5.9 — Employee Import v2
## v3.5.8 — Multitenancy core (TenantResolver)
## v3.5.7 — Vacaciones Panamá (VacationController)
## v3.5.6 — Calendario sincronización API
## v3.5.5 — Procesamiento batch asistencias
## v3.5.4 — Reportes marcaciones
## v3.5.3 — 19 funciones de asistencias integradas al motor
## v3.4.8 — Cálculo almuerzo avanzado
## v3.4.7 — Integración Planillas-Asistencias
## v3.4.6 — Consideraciones legales Panamá (AlertsSystem)
## v3.4.4 — Calculadores core asistencias
## v3.4.2 — Reprocesamiento planillas
## v3.4.0 — API externa Base44

> **TODO**: completar descripciones one-liner para las versiones listadas
> sólo con título.
