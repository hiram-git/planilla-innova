# TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2025-12-29 (v3.5.17)

## ✅ Completado Recientemente (v3.5.17 - 29-Dic-2025)

### Bug Fixes + UX Improvements
- [x] Agregar stateSave: true en DataTables de concepts/index.php para persistir estado
- [x] Implementar soporte Enter key en modal eliminación conceptos
- [x] Asignar acumulado "Por concepto" (ID 22) automáticamente a conceptos de préstamos
- [x] Fix status ENUM préstamos: migración 3 pasos en 12 bases tenant
- [x] Fix cuotas préstamos no se marcan como pagadas (creditor_id requerido)
- [x] Reparar concepto 62 con creditor_id = 1 en pinn49411848
- [x] Marcar cuota ID 89 como pagada manualmente
- [x] Verificar conceptos sin creditor_id en todas las bases tenant (0 encontrados)
- [x] Crear migrate_main_database.php para ejecutar migraciones en planilla_prod
- [x] Script lee configuración desde .env (DB_DATABASE, DB_HOST, etc.)
- [x] SQL parser robusto + dry-run mode + migrations_history tracking
- [x] Fix error "jerarquía del departamento" en producción (estructura organigrama legacy)
- [x] Crear migración 2025_12_29_fix_organigrama_column_names.sql idempotente
- [x] Renombrar nivel → nivel_jerarquico con verificaciones condicionales
- [x] Eliminar columnas/FK legacy (cargo_id, funcion_id, fk_organigrama_cargo, fk_organigrama_funcion)
- [x] Actualizar CHANGELOG.md con índice v3.5.17
- [x] Crear documentation/changelog/v3.5.17.md con detalles completos
- [x] Actualizar TODO.md con tareas completadas v3.5.17

### Previous (v3.5.16 - 29-Dic-2025)

### Sistema Expedientes Empleados + Migraciones Multi-Tenant Robustas
- [x] Crear migración employee_files_catalogs.sql con tablas types/subtypes
- [x] Insertar 13 tipos de expedientes (Estudios, Capacitación, Permisos, etc.)
- [x] Insertar 68 subtipos categorizados con FK a types
- [x] Agregar menu item ID 26 "Employee Files" a menu_items
- [x] Implementar idempotencia con INSERT...ON DUPLICATE KEY UPDATE
- [x] Fix error PDO "Cannot execute queries while pending result sets"
- [x] Implementar método splitSqlStatements() (60 líneas) en migration runners
- [x] Cambiar exec() → query() para correcta liberación de resultados
- [x] Agregar try-catch por statement individual con logging detallado
- [x] Simplificar SQL migration: UNION ALL → VALUES simples
- [x] Actualizar migrate_all_tenants.php y migration_runner.php
- [x] Testing exitoso migración en todos los tenants (0 errores PDO)
- [x] Actualizar CLAUDE.md con v3.5.14, v3.5.15, v3.5.16
- [x] Actualizar CHANGELOG.md con índices v3.5.14-16
- [x] Actualizar TODO.md con tareas completadas

### Previous (v3.5.15 - 28-Dic-2025)

### UNIDAD Dinámica en Fórmulas
- [x] Implementar sintaxis asignación dinámica UNIDAD en fórmulas
- [x] Crear método obtenerUnidadCalculada() en PlanillaConceptCalculatorSecure
- [x] Integrar captura automática UNIDAD en PayrollController (líneas 1574-1583)
- [x] Documentar sintaxis: UNIDAD = expresión_condicional + resultado_monto
- [x] Ejemplo real: UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
- [x] Testing con fórmulas complejas condicionales
- [x] Crear FORMULA_UNIDAD_DINAMICA.md con casos de uso

### Previous (v3.5.14 - 28-Dic-2025)

### Campo UNIDAD en Planilla Detalle
- [x] Crear migración rename referencia_valor → unidad en planilla_detalle
- [x] Actualizar 7 archivos PHP (PayrollController, ExcelReportController, etc.)
- [x] Agregar variable UNIDAD a whitelist PlanillaConceptCalculatorSecure
- [x] Cargar campo unidad desde tabla conceptos en evaluación fórmulas
- [x] Actualizar vista edit-details.php (headers conceptos muestran unidad)
- [x] Actualizar vista show_detail.php (columna Unidad con badges)
- [x] Ejecutar migración exitosamente en todos los tenants
- [x] Crear IMPLEMENTACION_CAMPO_UNIDAD.md (385 líneas) con documentación completa

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

