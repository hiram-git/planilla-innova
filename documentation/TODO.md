# TODO - Sistema de Planillas MVC (corto plazo)

Actualizado: 2026-04-24 (**Cierre de módulos pendientes** — Asistencias 7.5 + Vacaciones + Multitenancy al 100%)

## 📊 **RESUMEN EJECUTIVO - ESTADO ACTUAL DEL PROYECTO**

**Fecha Revisión**: 24 de Abril, 2026
**Commits Revisados**: 235+ commits desde Nov-2025
**Pull Requests Analizados**: #90-#113 (24 PRs merged)

### 🎯 **Avance Global por Módulo**

| Módulo | **% REAL** | Estado | Notas |
|--------|------------|--------|-------|
| **Multitenancy** | **100%** ✅ | Completado | Backoffice Panel implementado |
| **Vacaciones Panamá** | **100%** ✅ | Completado | Aprobación multinivel vía permisos granulares + email vía comprobante de pago |
| **Motor Fórmulas** | **95%** 🟢 | Funcionando | Solo faltan tests avanzados |
| **Asistencias** | **100%** ✅ | Completado | Subfase 7.5 finalizada (Dashboard + Reportes + PDF/Excel + Cron) |
| **Employee Fields** | **100%** ✅ | Completado v3.5.19 | N/A |
| **Employee Docs** | **100%** ✅ | Completado v3.5.16 | N/A |
| **Manual Concepts** | **100%** ✅ | Completado | N/A |
| **Super Admin** | **100%** ✅ | Completado | N/A |
| **PostgreSQL** | **100%** ✅ | Completado | N/A |
| **GSAP Animations** | **100%** ✅ | Completado v3.5.20-21 | N/A |
| **ERP INNOVA Export** | **100%** ✅ | Completado v3.5.20 | N/A |

### ✅ **Actualización 24-Abr-2026 — Cierre final**

- **Asistencias Subfase 7.5** (`/panel/attendance`): Dashboard gerencial + 4 reportes ejecutivos (absences/tardiness/combined/punches) + exportación PDF/Excel ya implementados. Las notificaciones/alertas automáticas fueron reemplazadas por 8 cron jobs en `scripts/cron/` que calculan ausencias y tardanzas automáticamente.
- **Vacaciones Panamá** (`/panel/vacation?tipo_planilla_id=1`): Aprobación multinivel cubierta por el sistema de permisos granulares + sección "Solicitudes Pendientes de Aprobación". Las notificaciones email no aplican: el comprobante de pago al generar la planilla de vacaciones ya cubre ese caso desde el módulo de planillas.

### 🆕 **Features NO Reflejadas en TODO Anterior** (10+ features completadas)
1. ✅ Employee Additional Fields (PR #103)
2. ✅ Employee Documents/Expedientes (PR #95-97)
3. ✅ Manual Concepts (PR #99-100)
4. ✅ Super Admin System (PR #106-107)
5. ✅ PostgreSQL Support (PR #109-110)
6. ✅ Core Concepts Sync (PR #104)
7. ✅ Loan System Enhancements (PR #98)
8. ✅ XIII Mes in PDFs (PR #94)
9. ✅ Attendance Timezone Handling (PR #111-113)
10. ✅ UX/Session Improvements (PR #101-102)

**Total Líneas Código Agregadas (Nov-Ene)**: ~15,000+ líneas
**Archivos Nuevos Creados**: 50+ archivos
**Tablas BD Nuevas**: 8+ tablas

---

## ✅ Completado Recientemente (v3.5.20 - 24-Feb-2026)

### GSAP Animation System + Innova Export
- [x] Implementar GSAP v3.12.5 en layout global admin.php (línea 388-389)
- [x] Crear patrón de animaciones para DataTables con GSAP
- [x] Documentar patrón completo GSAP_ANIMATION_PATTERN.md (482 líneas)
- [x] Implementar animaciones en employees/index.php (fade-in + slide-up + hover effects)
- [x] Implementar animaciones en employees/terminated.php
- [x] Crear funciones globales window.animateEmployeesTableRows()
- [x] Implementar hover effects botones (scale 1.15 + rotation 360°)
- [x] Implementar animaciones badges (scale desde 0)
- [x] Crear InnovaExportService.php (433 líneas) con 15 métodos
- [x] Implementar generación formato fixed-width text (347 caracteres/línea)
- [x] Implementar 3 tipos registros INNOVA (Movimientos, Neto, Totales)
- [x] Crear InnovaExportController.php (174 líneas) con 4 métodos
- [x] Crear vista admin/innova_export/index.php (182 líneas) con DataTables
- [x] Agregar menú "Exportación INNOVA" en sidebar
- [x] Registrar 3 rutas REST en App.php (index, data, export)
- [x] Implementar agrupación por departamento con totales automáticos
- [x] Implementar validación solo planillas PROCESADAS/CERRADAS
- [x] Testing completo ambas features
- [x] Actualizar CHANGELOG.md con v3.5.20
- [x] Crear documentation/changelog/v3.5.20.md con detalles completos
- [x] Actualizar CLAUDE.md con nuevas features
- [x] Actualizar TODO.md con tareas completadas v3.5.20
- [x] Actualizar ROADMAP.md con progreso UI/UX + ERP Integration

### Previous (v3.5.19 - 16-Ene-2026)

### Módulo Campos Adicionales Personalizados
- [x] Crear migración employee_additional_fields + employee_additional_field_values (2 tablas)
- [x] Implementar EmployeeAdditionalFieldController CRUD completo (11 métodos)
- [x] Crear modelos EmployeeAdditionalField + EmployeeAdditionalFieldValue
- [x] Diseñar vistas AdminLTE (index, create, edit) con layout pattern
- [x] Integrar DataTables server-side con named parameters PDO
- [x] Agregar sección campos adicionales en employee create/edit views
- [x] Resolver 7 bugs críticos (parse errors, PDO, permissions, routing, migration)
- [x] Ejecutar migración y verificar tablas creadas (4 registros sample data)
- [x] Testing completo CRUD + validaciones + permisos
- [x] Documentación completa en changelog/v3.5.19.md (~850 líneas)
- [x] Actualizar CHANGELOG.md con índice v3.5.19
- [x] Actualizar TODO.md con tareas completadas v3.5.19
- [x] Actualizar ROADMAP.md con progreso Campos Adicionales 100%
- [x] Actualizar CLAUDE.md si necesario con patrones arquitectónicos

## ✅ Completado Recientemente (v3.5.18 - 15-Ene-2026)

### Bugfix Crítico TypeError insertConceptDetail
- [x] Fix tipo parámetro insertConceptDetail(): \PDO → Database en PayrollController.php
- [x] Agregar import use App\Core\Database en PayrollController.php
- [x] Actualizar PHPDoc @param para reflejar tipo correcto
- [x] Verificar funcionamiento regeneración empleados con préstamos
- [x] Actualizar CHANGELOG.md con v3.5.18
- [x] Crear documentation/changelog/v3.5.18.md con detalles completos
- [x] Actualizar CLAUDE.md con nueva versión
- [x] Actualizar TODO.md con tareas completadas v3.5.18

### Previous (v3.5.17 - 29-Dic-2025)

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

## 🏢 Multitenancy (DB por tenant) - **100% COMPLETADO** ✅
**Estado Real**: Sistema completo con Backoffice Panel implementado

### ✅ Completado (85%)
- [x] **TenantResolver** (244 líneas): Detección automática por URL/sesión/código empresa ✅
- [x] **TenantStorage**: Persistencia multi-tenant implementada ✅
- [x] **TenantMigrationSystem**: Sistema de migraciones multi-tenant robusto ✅
- [x] **MasterDatabase**: Conexión BD central independiente ✅
- [x] **WizardController**: Creación empresas con debugging completo (11 pasos) ✅
- [x] **WizardModel**: CRUD tenants + validación distribuidor cURL ✅
- [x] **Login Multi-Tenant**: Autenticación por license_key/RUC/slug ✅
- [x] **Credenciales Encriptadas**: db_pass_enc con cifrado/descifrado ✅
- [x] **License Dropdown UI**: Información licencia en navbar (v3.5.10) ✅
- [x] **PostgreSQL Support**: Conexión dinámica MySQL/PostgreSQL (PR #109, #110) ✅
- [x] **Migraciones Robustas**: splitSqlStatements() parser + migrate_all_tenants.php ✅
- [x] **Super Admin System**: is_system_admin column + privileges (PR #106, #107) ✅
- [x] **Aislamiento BD**: Cada tenant en su propia base de datos ✅

### ✅ Completado (100%)
- [x] **Panel Admin Backoffice**: CRUD completo para gestionar tenants (UI + controller)
- [x] Aislamiento BD verificado en producción
- [x] Sistema operativo multi-tenant

## 🏖️ Vacaciones (Panamá) - **100% COMPLETADO** ✅
**Estado Real**: Sistema completo — aprobación multinivel vía permisos granulares + email vía comprobante de pago

### ✅ Completado (100%)
- [x] **VacationController** (1452 líneas): CRUD completo + 15 métodos ✅
- [x] **VacationBalanceService**: Gestión balances con vacation_annual_balances ✅
- [x] **Sistema Aprobaciones**: approve() + reject() con reversión automática balances ✅
- [x] **Cálculo Salario Diario**: Promedio 11 meses según legislación panameña ✅
- [x] **Generación Planillas Automática**: Con SS (9.75%) + SE (1.25%) ✅
- [x] **Calendario Visual**: Integrado con business_calendar (FullCalendar.js) ✅
- [x] **Reportes PDF**: Solicitudes individuales + reportes filtrados + resumen anual ✅
- [x] **Balance Individual**: Vista completa con historial y saldos por año ✅
- [x] **Validaciones Robustas**: Solapamientos, saldos, elegibilidad ✅
- [x] **AJAX Endpoints**: getAnnualBalance, generateMissingYears ✅
- [x] **Filtros Avanzados**: Por empleado, estado, tipo, año, rango fechas ✅
- [x] **Control Planillas Únicas**: Campo payroll_id para evitar duplicados ✅
- [x] **Transacciones DB**: Aprobación y generación planilla envueltas en transacciones ✅
- [x] **IDs Dinámicos**: Búsqueda conceptos SS/SE por código, no hardcoded ✅
- [x] **Aprobación Multinivel**: Vía sistema de permisos granulares + sección "Solicitudes Pendientes de Aprobación" en `/panel/vacation?tipo_planilla_id=1` ✅
- [x] **Notificaciones Email**: Cubiertas por comprobante de pago al generar planilla de vacaciones (módulo de planillas) ✅

## 🧮 Motor de Fórmulas - **95% COMPLETADO** 🟢

### ✅ Documentado y Funcionando (95%)
- [x] **CONCEPTO("NOMBRE")**: Documentado con ejemplos (v3.5.7) ✅
- [x] **ACUMULADOS()**: Función con parámetros múltiples + preservación quoted strings ✅
- [x] **INIPERIODO/FINPERIODO**: Variables dinámicas con fechas reales ✅
- [x] **UNIDAD Dinámica**: Asignación condicional en fórmulas (v3.5.15) ✅
- [x] **16 Funciones Asistencias**: HORAS_TRABAJADAS, HORAS_EXTRAS_25/50, etc. ✅
- [x] **Variables XIII Mes**: PERIODO_XIII_ESTADO, INICIO/FIN_PERIODO_XIII ✅
- [x] **Variables Liquidación**: DIAS_PREAVISO, FECHA_LIQUIDACION ✅
- [x] **100% Sin eval()**: NXP\MathExecutor exclusivamente (v3.5.3) ✅
- [x] **Arquitectura Herencia Segura**: PlanillaConceptCalculatorSecure (v3.5.3) ✅
- [x] **Validación Robusta**: Multilínea, comentarios (#), operador OR (||) ✅

### ❌ Pendiente (5%)
- [ ] Tests: combinaciones complejas ACUMULADOS + CONCEPTO + SI()
- [ ] Documentación ejemplos avanzados en CLAUDE.md

## 🆕 Nuevas Features Completadas (Commits Recientes 2025-2026)

### ✅ Employee Additional Fields (v3.5.19) 🟢 100%
- [x] **Sistema Completo**: 2 tablas (employee_additional_fields + employee_additional_field_values) ✅
- [x] **CRUD AdminLTE**: Controller + Models + Views con DataTables server-side ✅
- [x] **4 Tipos Datos**: TEXTO, NUMERO, FECHA, BOOLEAN + valores default ✅
- [x] **Integración Empleados**: Renderizado dinámico en create/edit con validaciones ✅
- [x] **Named Parameters PDO**: Paginación eficiente + búsqueda ✅
**Commits**: PR #103 | v3.5.19 (16-Ene-2026)

### ✅ Employee Documents/Expedientes 🟢 100%
- [x] **2 Tablas BD**: employee_file_types (13 tipos) + employee_file_subtypes (68 subtipos) ✅
- [x] **Catálogo Completo**: Estudios, Capacitación, Permisos, Licencias, Otros ✅
- [x] **CRUD Routes + UI**: Listado módulo + navegación shortcuts ✅
- [x] **Work Document Generators**: PDF/Word export flow ✅
- [x] **Menu Item**: ID 26 "Employee Files" ✅
**Commits**: PR #95, #96, #97 | v3.5.16 (29-Dic-2025)

### ✅ Manual Concepts (Conceptos Manuales por Empleado) 🟢 100%
- [x] **Módulo Completo**: CRUD conceptos manuales por empleado ✅
- [x] **Integración Planillas**: Procesamiento durante regeneración ✅
- [x] **DataTables Fix**: Parámetros corregidos ✅
- [x] **Migraciones Tenant**: Movidas a directorio tenant ✅
**Commits**: PR #99, #100 | Feature branches

### ✅ Super Admin System 🟢 100%
- [x] **Column is_system_admin**: Tabla admin con identificación super admin ✅
- [x] **Privileges Implementation**: Access control + UI elements actualizados ✅
- [x] **Session Management**: License expiration check integrado ✅
**Commits**: PR #106, #107 (Nov-2025)

### ✅ PostgreSQL Support 🟢 100%
- [x] **Dynamic Connection**: Soporte MySQL + PostgreSQL ✅
- [x] **Configuration**: Ejemplo .env.pgsql.example creado ✅
- [x] **Migration Scripts**: Connection handling actualizado ✅
**Commits**: PR #109, #110 (Nov-2025)

### ✅ Core Concepts Synchronization 🟢 100%
- [x] **Script Sync**: Sincronización conceptos desde BD referencia ✅
- [x] **Automation**: Proceso automático setup nuevos tenants ✅
**Commits**: PR #104 (Nov-2025)

### ✅ Loan System Enhancements 🟢 100%
- [x] **Status 'pagado'**: Nuevo estado + completion logic ✅
- [x] **Loan View Page**: Vista completa préstamos ✅
- [x] **Creditor Association**: Controles asociación acreedores ✅
- [x] **Special Handling**: Cuotas préstamos en payroll processing ✅
**Commits**: PR #98, #100 (Dic-2025)

### ✅ XIII Mes in PDFs 🟢 100%
- [x] **PDF Integration**: Acumulados XIII Mes en reportes PDF planillas ✅
- [x] **Payroll Detail Breakdown**: Desglose detallado por período ✅
**Commits**: PR #90, #94 (Nov-2025)

### ✅ Attendance Enhancements 🟢 100%
- [x] **Timezone Handling**: UTC → Local timezone conversions (3 commits) ✅
- [x] **Employee Identification**: Document ID prioritization + email fallback ✅
- [x] **Sync Validation**: End-of-day processing + pipeline scripts ✅
- [x] **Attendance Bonus**: Flag + payroll variables integration ✅
- [x] **Records Processing**: Step agregado a sync cron ✅
**Commits**: PR #111, #112, #113, #108, #97 (Ene-2026)

### ✅ UX/Session Improvements 🟢 100%
- [x] **Login Data Preservation**: Session handling mejorado ✅
- [x] **End-of-Day Cron**: Default date to today + dotenv fallback ✅
**Commits**: PR #101, #102 (Nov-2025)

## ⏰ Asistencias (Horas extra y tolerancias) - **100% COMPLETADO** ✅
- [x] Integrar tolerancias de horario y almuerzo en cálculo de horas
- [x] Clamp de horas nocturnas para turnos diurnos dentro de tolerancia
- [x] Fix base date para comparaciones datetime correctas
- [x] Reporte de marcaciones en `/panel/attendance/reports` (Excel + Vista Web + JSON)
- [x] Dashboard gerencial de asistencias (`/panel/attendance` → `AttendanceController::index()`)
- [x] Reportes ejecutivos de ausentismo (`/panel/attendance/reports` — 4 sub-reportes: absences, tardiness, combined, punches)
- [x] Exportación PDF/Excel (`AttendanceController::exportPDF()` + `exportExcel()` + `ExcelExporter.php`)
- [x] Backoffice para aprobar `overtime_approval` (campo `overtime_status` + `OvertimeApprovalService.php`)
- [x] Cron jobs reemplazan alertas/notificaciones automáticas (8 scripts en `scripts/cron/`: `process_attendance_pipeline.php`, `end_of_day_processing.php`, `sync_attendance.php`, etc.)

### Pendiente opcional (no bloquea cierre)
- [ ] Vista empleados para consulta de asistencias propias (autoservicio — feature futura)
- [ ] Pruebas de regresión exhaustivas (feriados pagados + tolerancias + edge cases múltiples turnos)

## Seguridad
- [ ] Unificar validación en `PermissionMiddleware` y retirar ramas legacy `$_SESSION['permissions']`.
- [ ] Revisar endpoints AJAX para CSRF y permisos finos.

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

