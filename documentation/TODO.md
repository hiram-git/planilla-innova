# ✅ TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2025-11-15 (v3.5.7)

## Vacaciones (Panamá)
- [ ] Envolver aprobación y generación de planilla en transacciones DB
- [ ] Parametrizar IDs de conceptos (SS/SE) por código/config
- [ ] Mostrar desglose por año en PDF (opcional) y validar cifras contra UI
- [ ] Tests unitarios: `calculateVacationDailySalary` (11m completos, parciales, sin acumulados)

## Motor de Fórmulas
- [ ] Documentar `CONCEPTO("NOMBRE")` en manual de fórmulas con ejemplos
- [ ] Tests de fórmula: combinaciones ACUMULADOS + CONCEPTO + SI()

## Asistencias (Horas extra y tolerancias)
- [ ] Completar implementación de tolerancias en calculadores y UI
- [ ] Backoffice para aprobar `overtime_approval` y reportes asociados
- [ ] Pruebas de regresión: días feriados pagados + tolerancias

## Permisos y Seguridad
- [ ] Unificar validación en `PermissionMiddleware` y retirar ramas legacy `$_SESSION['permissions']`
- [ ] Revisar endpoints AJAX para CSRF y permisos finos

## Frontend
- [ ] Extraer JS inline a módulos (balance/show) y reutilizar helpers
- [ ] Validar fallback jQuery en escenarios sin red/CDN

## Documentación
- [ ] Mantener `documentation/changelog/` por versión e indexar en `CHANGELOG.md`
- [ ] Revisar ROADMAP metas Q4 2025 tras horas extra

