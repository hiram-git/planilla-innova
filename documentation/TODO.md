# TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2025-12-04 (v3.5.14)

## ✅ Completado Recientemente (v3.5.14 - 04-Dic-2025)

### Fix JavaScript Module Loading (Critical)
- [x] Refactorizar PayrollModule para lazy initialization de URLs
- [x] Mover acceso APP_CONFIG desde definición objeto a método init()
- [x] Agregar verificaciones typeof !== 'undefined' para APP_CONFIG
- [x] Implementar fallback dinámico si APP_CONFIG no disponible
- [x] Corregir orden de carga scripts en index.php (usar $scripts único)
- [x] Copiar tenant-storage-manager.js a ubicación correcta (/js/)
- [x] Testing completo en navegador (consola limpia, funcionalidad operativa)
- [x] Actualizar CHANGELOG.md con índice v3.5.14
- [x] Crear documentation/changelog/v3.5.14.md con análisis completo
- [x] Actualizar CLAUDE.md con resumen compacto del fix

### Previous (v3.5.13 - 02-Dic-2025)

### Sistema Permisos Granular + Liquidaciones Dinámicas
- [x] Fix error FK role_permissions al actualizar roles
- [x] Insertar 8 módulos faltantes en menu_items (IDs 18-25)
- [x] Agregar método getValidMenuIds() en Role.php para validación FK
- [x] Sistema permisos granular en sidebar.php con filtrado dinámico
- [x] Método canAccessRoute() para verificación permisos lectura
- [x] Pre-verificación de secciones con variables $hasXxxSection
- [x] Headers condicionales (oculta secciones sin módulos accesibles)
- [x] Fix conceptos LIQ no aparecían (frecuencia_id hardcoded incorrecta)
- [x] Corregir asignación frecuencia_id=5 a todos conceptos LIQ
- [x] Asignar tipo_planilla_id=1 a conceptos LIQ sin asignación
- [x] Implementar método getLiquidationFrequencyId() dinámico con caché
- [x] Usar UPPER() en búsquedas para case-insensitive (código y nombre)
- [x] Reemplazar 8 hardcodes frecuencia_id por búsqueda dinámica
- [x] Actualizar CHANGELOG.md con índice v3.5.13
- [x] Crear documentation/changelog/v3.5.13.md con detalles completos

### Previous (v3.5.12 - 01-Dic-2025)

### Acumulados Excel Export + Bug Fixes Motor Fórmulas
- [x] Implementar exportación Excel para módulo acumulados con filtros completos
- [x] Método exportExcel() con lógica SQL idéntica a CSV export
- [x] Styling profesional PhpSpreadsheet (12 columnas, headers, borders, colors)
- [x] Botón UI "Exportar Excel" + función JavaScript exportToExcel()
- [x] Agregar ruta 'panel/acumulados/exportExcel' con permisos admin/manager
- [x] Mejorar formato columna "Concepto" en DataTable (descripcion | tipo_acumulado)
- [x] Fix variable indefinida $campo → $agregacion en queryAggregation()
- [x] Fix validación variables XIII mes (PERIODO_XIII_ESTADO, FECHA_LIQUIDACION)
- [x] Actualizar CHANGELOG.md con índice v3.5.12
- [x] Crear documentation/changelog/v3.5.12.md con detalles completos

### Previous (v3.5.11 - 27-Nov-2025)

### Permission System Restructuring + Wizard UI + Formula Validation
- [x] Implementar página acceso dedicada para permisos denegados
- [x] Diferenciar entre expiración sesión y fallo autorización
- [x] Mantener sesión activa cuando se deniega acceso
- [x] Mostrar grid módulos permitidos con acceso rápido
- [x] Agregar imagen fondo wizard con opacidad configurable
- [x] Cambiar color subtítulo confirmación a orange-accent-4
- [x] Fix validación fórmulas: soporte caracteres acentuados español
- [x] Fix validación fórmulas: soporte comentarios (#) y operador OR (||)
- [x] Fix validación fórmulas: soporte saltos de línea multiline

### Previous (v3.5.10 - 25-Nov-2025) - License Info UI + Wizard Debugging
- [x] Implementar dropdown información de licencia en navbar (RUC, empresa, licencia, días restantes)
- [x] Calcular días restantes automáticamente desde `$_SESSION['license_expiration']`
- [x] Badges de color según días restantes (verde ≥30, amarillo 7-29, rojo <7)
- [x] Ocultar dropdown para sistema principal (license='default')
- [x] Agregar mensajes de depuración detallados en `WizardController::createCompany()`
- [x] Debug de variables de entorno (TENANT_DB_*, DB_*)
- [x] Logging por pasos (11 pasos con emojis identificadores)
- [x] Fix error `inTransaction()` inexistente en clase Database
- [x] Stack trace completo en catch blocks con manejo robusto de rollback

### Previous (v3.5.9 - 21-Nov-2025) - Employee Import + Wizard UI
- [x] Actualizar template Excel con 3 campos nuevos (email*, marca_asistencia, permite_horas_extras)
- [x] Implementar validación email requerido con formato FILTER_VALIDATE_EMAIL
- [x] Crear método `formatBoolean()` para conversión flexible (1/0, SI/NO, YES/NO)
- [x] Integrar creación automática registros `employee_payroll_salaries` con auditoría
- [x] Refactorizar CSS wizard `/crear-empresa` para márgenes simétricos perfectos

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

