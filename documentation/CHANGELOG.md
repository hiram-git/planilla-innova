# ðŸ“‹ CHANGELOG - Sistema de Planillas MVC

## ðŸ“– **Ãndice de Versiones**

Este archivo sirve como Ã­ndice principal para el historial de cambios del sistema. Cada versiÃ³n tiene su propio archivo detallado en el directorio `changelog/`.

---

## ðŸ†• **Ãšltimas Versiones**

### **[v3.5.21]** - 2026-02-25 - *GSAP Animations Expansion + Innova Export Fixes*
**Tipo**: FEATURE + BUGFIX - UI/UX Enhancement + Module Fixes
**Fase**: Frontend Animations Expansion + Production Fixes
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Animaciones GSAP - 4 Vistas Adicionales**:
  - Vista Liquidaciones Estimadas (7 funciones, ~250 lÃ­neas)
  - Vista Planillas Estimadas (10 funciones, ~390 lÃ­neas)
  - Vista Innova Export (7 funciones, ~245 lÃ­neas)
  - Botones Formulario Crear Planilla (4 funciones, ~130 lÃ­neas)
- âœ… **Correcciones CrÃ­ticas MÃ³dulo Innova Export**:
  - Session Key Error: `$_SESSION['admin_id']` â†' `$_SESSION['admin']`
  - View Path Error: path correcto `admin/innova_export/index`
  - Parse Error: eliminaciÃ³n etiqueta `<?php` duplicada
  - View Rendering Method: `View::render()` â†' `$this->view()`
- â�Œ **Animaciones Revertidas - Employee Manual Concepts**:
  - DataTable quedaba en estado "Procesando" permanentemente
  - Conflicto entre animaciones GSAP y renderizado DataTable
  - Restaurada configuraciÃ³n bÃ¡sica funcional

**CaracterÃ­sticas GSAP**:
- Animaciones entrada: callouts, info-boxes, cards, tabla
- Hover effects: rotaciÃ³n 360Â° + escala 1.15-1.2
- DataTable integration: drawCallback para re-animar paginaciÃ³n
- Efectos especÃ­ficos: badges con bounce, nÃºmeros financieros
- Timing coordinado y event delegation

**ðŸ› Bugs Resueltos**:
- Session key redirect loop infinito
- Vista no encontrada (path incorrecto)
- Parse error (duplicate PHP tag)
- Variable `$shadowColor` interpretada como PHP
- Quotes en comentarios JavaScript

**ðŸ"ˆ EstadÃ­sticas**:
- 5 vistas modificadas | 2 controllers corregidos | 1 core file (Controller.php)
- ~1,015 lÃ­neas agregadas | ~350 lÃ­neas revertidas | ~665 lÃ­neas netas
- 28 funciones GSAP | 4 bugs crÃ­ticos resueltos
- Deployment: 10-15 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.21.md)**

---

### **[v3.5.20]** - 2026-02-24 - *GSAP Animations + Innova Export System*
**Tipo**: FEATURE - UI/UX Enhancement + ERP Integration
**Fase**: Frontend Animations + External Integrations
**Criticidad**: Media

**Componentes Principales**:
- ✅ **GSAP Animation Pattern**: Sistema de animaciones profesionales para DataTables con GSAP v3.12.5
- ✅ **Innova Export System**: Exportación planillas a formato fixed-width text para ERP INNOVA
- ✅ **Documentación Técnica**: GSAP_ANIMATION_PATTERN.md completo (482 líneas)
- ✅ **Implementación Empleados**: Animaciones en index + terminated views
- ✅ **Export Service**: InnovaExportService (433 líneas) + Controller + UI completa

**Características GSAP**:
- Animaciones fade-in + slide-up en tablas (carga inicial)
- Hover effects en botones (scale 1.15 + rotation 360°)
- Badges con animación scale desde 0
- Diferenciación animación inicial vs filtros/paginación
- clearProps automático para performance

**Características Innova Export**:
- Formato fixed-width text (347 caracteres por línea)
- 3 tipos registros: Movimientos (1), Neto empleado (2), Totales (3)
- Agrupación por departamento + totales automáticos
- Solo planillas PROCESADAS/CERRADAS
- Archivos con timestamp para auditoría

**ðŸ"ˆ Estadísticas**:
- 4 archivos nuevos | 4 modificados | ~1,625 líneas | 1 documento técnico (482 líneas)
- 5 tipos animaciones | 15 métodos PHP | 3 rutas REST | 2 queries SQL optimizadas
- Deployment: 15-20 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.20.md)**

---

### **[v3.5.19]** - 2026-01-16 - *Módulo Campos Adicionales Personalizados*
**Tipo**: FEATURE - Employee Custom Fields System
**Fase**: Core System Enhancement - Dynamic Employee Data
**Criticidad**: Media

**Componentes Principales**:
- ✅ **Sistema Catálogo Campos**: 4 tipos datos (TEXTO, NUMERO, FECHA, BOOLEAN)
- ✅ **Integración Formularios**: Sección dinámica en create/edit empleados
- ✅ **CRUD Completo**: Controller + Models + Views AdminLTE
- ✅ **DataTables Server-Side**: Paginación eficiente con búsqueda
- ✅ **7 Bugs Resueltos**: Parse errors, PDO, permisos, routing, migration

**📈 Estadísticas**:
- 11 archivos nuevos | 3 modificados | ~2,150 líneas | 2 tablas BD | 7 bugs fixed
- Deployment: 10-15 minutos

**[📄 Ver detalles completos →](./changelog/v3.5.19.md)**

---

### **[v3.5.18]** - 2026-01-15 - *Fix TypeError insertConceptDetail*
**Tipo**: BUGFIX CRÍTICO - Parameter Type Correction
**Fase**: Core System Maintenance (100%)
**Criticidad**: Crítica

**Componentes Principales**:
- âœ… **PayrollController Fix** (3 líneas modificadas):
  - Firma método insertConceptDetail(): \PDO → Database
  - Import agregado: use App\Core\Database
  - PHPDoc actualizado: @param Database $db
- âœ… **Regeneración Planillas Restaurada**:
  - Error TypeError fatal resuelto
  - Regeneración empleados con préstamos funcional
  - Flujo CUOTAPRESTAMO operativo

**ðŸ"§ Problema Resuelto**:
```
Fatal error: TypeError: insertConceptDetail(): Argument #1 ($db)
must be of type PDO, App\Core\Database given
```

**ðŸ"ˆ Estadísticas**:
- 1 archivo modificado | 3 líneas cambiadas | 1 import agregado | 1 bug crítico resuelto
- Deployment: < 1 minuto | Sin cambios BD | Sin breaking changes

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.18.md)**

---

### **[v3.5.17]** - 2025-12-29 - *Bug Fixes + UX Improvements*
**Tipo**: MANTENIMIENTO + UX - DataTables State Persistence + Loan System Fixes
**Fase**: Core System Maintenance (100%)
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **DataTables Persistencia Estado** (concepts/index.php):
  - stateSave: true para recordar página/filtros
  - Soporte Enter key en modal eliminación
- âœ… **Préstamos - Fix Status ENUM**:
  - Migración 3 pasos: 12 bases tenant actualizadas
  - ENUM('generada','pagada','cancelada','anulado') → ENUM('pendiente','pagada','anulada')
- âœ… **Conceptos Préstamos Mejorados** (LoanController.php):
  - Acumulado "Por concepto" (ID 22) asignado automáticamente
  - creditor_id asignación automática para nuevos conceptos
- âœ… **Organigrama Fix Estructura** (migración SQL):
  - Renombrar nivel → nivel_jerarquico
  - Eliminar columnas legacy cargo_id/funcion_id + FK
  - Migración idempotente con verificaciones condicionales
- âœ… **Main Database Migration Runner**:
  - Script independiente para planilla_prod desde .env
  - SQL parser robusto + dry-run mode + migrations_history

**ðŸ"ˆ Estadísticas**:
- 5 archivos modificados | 1 script nuevo (500+ líneas) | 1 migración SQL | 5 bugs corregidos
- 12 bases tenant migradas | 1 concepto reparado | Deployment: 15-20 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.17.md)**

---

### **[v3.5.16]** - 2025-12-29 - *Expedientes Empleados + Migraciones Multi-Tenant Robustas*
**Tipo**: FEATURE + INFRASTRUCTURE - Employee Files System + Migration Runner Enhancement
**Fase**: Core System Infrastructure (100%)
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **Sistema Expedientes Empleados**:
  - 2 tablas nuevas: `employee_file_types` (13 registros), `employee_file_subtypes` (68 registros)
  - Catálogo completo: Estudios (13), Capacitación (5), Permisos (10), Licencias (13), otros (27)
  - Menu item ID 26 "Employee Files" agregado
  - INSERT...ON DUPLICATE KEY UPDATE para idempotencia
- âœ… **Mejoras Runner Migraciones Multi-Tenant**:
  - Método `splitSqlStatements()` (60 líneas): parser robusto SQL
  - Cambio exec() → query() para correcta liberación resultados
  - Manejo errores mejorado: try-catch por statement individual
  - Elimina error PDO "Cannot execute queries while pending result sets"
- âœ… **SQL Simplificado**: UNION ALL → VALUES simples en inserts

**ðŸ"ˆ Estadísticas**:
- 1 migración | 3 archivos | +195 líneas | 2 tablas nuevas | 81 registros | 1 método parser
- Deployment: 10 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.16.md)**

---

### **[v3.5.15]** - 2025-12-28 - *UNIDAD Dinámica en Fórmulas*
**Tipo**: FEATURE - Formula Engine Enhancement
**Fase**: Motor Fórmulas - Asignación Dinámica Variables
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Nueva Sintaxis Fórmulas**:
  - Asignación dinámica UNIDAD basada en condiciones
  - Sintaxis: `UNIDAD = expresión_condicional` + `resultado_monto`
  - Ejemplo: `UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)`
- âœ… **Implementación**:
  - Método `obtenerUnidadCalculada()` en PlanillaConceptCalculatorSecure.php (líneas 634-645)
  - Integración PayrollController.php (líneas 1574-1583)
  - Captura automática después de evaluar fórmula
  - Almacenamiento en `planilla_detalle.unidad`
- âœ… **Casos de Uso**: Conceptos con cálculo condicional, integración asistencias

**ðŸ"ˆ Estadísticas**:
- 2 archivos | +45 líneas | 1 método nuevo | 0 cambios BD
- Deployment: 5 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.15.md)**

---

### **[v3.5.14]** - 2025-12-28 - *Campo UNIDAD en Planilla Detalle*
**Tipo**: FEATURE + MIGRATION - Database Field + Formula Integration
**Fase**: Payroll System - Unit Tracking Enhancement
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Migración BD**: `planilla_detalle.referencia_valor` → `unidad` (VARCHAR 50)
- âœ… **Actualización PHP** (7 archivos):
  - PayrollController, ExcelReportController, AsientosContablesPDFGenerator
  - PlanillaContableExcelGenerator, AttendanceConceptMapper, PayrollDetail
- âœ… **Motor Fórmulas**: Variable UNIDAD agregada a whitelist (PlanillaConceptCalculatorSecure.php)
- âœ… **Vistas Actualizadas**:
  - edit-details.php: Headers conceptos muestran unidad
  - show_detail.php: Columna "Unidad" con badges coloreados

**ðŸ"ˆ Estadísticas**:
- 1 migración SQL | 7 archivos PHP | ~180 líneas modificadas | 1 variable nueva
- Deployment: 15-20 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.14.md)**

---

### **[v3.5.13]** - 2025-12-02 - *Sistema Permisos Granulares + Liquidaciones Dinámicas*
**Tipo**: FEATURE + REFACTOR - Permission System + Liquidation Portability
**Fase**: Security + Liquidation Module
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **Sistema Permisos Granular en Sidebar** (sidebar.php +95 líneas):
  - Método canAccessRoute() con verificación permisos lectura
  - Pre-verificación secciones (7 variables)
  - 23 módulos filtrados por permisos
- âœ… **Fix FK Constraint role_permissions** (Role.php +55 líneas):
  - 8 módulos insertados (IDs 18-25)
  - Método getValidMenuIds() + validación automática
- âœ… **Liquidaciones Dinámicas** (LiquidationController.php +95 líneas):
  - Método getLiquidationFrequencyId() con lookup dinámico
  - 8 queries refactorizadas eliminando hardcoded IDs

**ðŸ"ˆ Estadísticas**:
- 3 archivos | +245 líneas código | 2 métodos nuevos | 8 hardcodes eliminados | 8 registros BD insertados
- Deployment: 10-15 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.13.md)**
- âœ… **CorrecciÃ³n Orden Scripts** (index.php):
  - Eliminado uso de `$scriptFiles` array
  - Scripts construidos manualmente en orden correcto con `$scripts`
  - Orden: DataTables â†' APP_CONFIG base â†' config payroll â†' mÃ³dulos
  - Merge configuraciÃ³n con Object.assign()
- âœ… **Fix TenantStorageManager**:
  - Archivo copiado de `/public/js/` a `/js/` (ubicaciÃ³n correcta)
- âœ… **Errores Resueltos**:
  - `APP_CONFIG is not defined` (causa: acceso en definiciÃ³n objeto)
  - `TenantStorageManager is not defined` (causa: ruta incorrecta)

**ðŸ"ˆ EstadÃ­sticas**:
- 2 archivos modificados | 1 archivo copiado | ~95 lÃ­neas modificadas
- 2 errores crÃ­ticos corregidos | 1 mÃ©todo refactorizado | 0 cambios BD
- Deployment: 5 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.14.md)**

---

### **[v3.5.12]** - 2025-12-01 - *Acumulados Excel Export + Bug Fixes*
**Tipo**: MEJORA + BUGFIX
**Fase**: Módulo Acumulados - Export + Motor Fórmulas Fixes
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Exportación Excel Acumulados** (AcumuladoController.php):
  - Método `exportExcel()` con lógica SQL idéntica a CSV
  - 12 columnas de datos con styling profesional PhpSpreadsheet
  - Soporte completo filtros: concepto_id='all', tipo_planilla, month, group_by
  - Botón UI + función JavaScript `exportToExcel()`
- âœ… **Mejora Formato Columna DataTable**:
  - Columna "Concepto" ahora muestra: `descripcion concepto | tipo acumulado`
  - Ejemplo: "Salario Base | SALARIO_BASE"
- âœ… **Fix Variable Indefinida** (PlanillaConceptCalculatorSecure.php):
  - Línea 957: `$campo` → `$agregacion` en queryAggregation()
  - Elimina warning "Undefined variable $campo"
- âœ… **Fix Validación Variables XIII Mes**:
  - Agregadas variables string: PERIODO_XIII_ESTADO, FECHA_LIQUIDACION
  - Permite estados: 'SIN_LIQUIDACION', 'ERROR', 'PENDIENTE', 'LIQUIDADO'

**ðŸ"ˆ Estadísticas**:
- 4 archivos modificados | +248 líneas código | 2 métodos nuevos | 2 bugs corregidos
- 1 función JavaScript | 1 ruta nueva | 0 cambios BD | Deployment: 5-10 min

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.12.md)**

---

### **[v3.5.10]** - 2025-11-25 - *License Info UI + Wizard Debugging*
**Tipo**: FEATURE + DEBUGGING
**Fase**: Multitenancy UX + Production Troubleshooting
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **Dropdown InformaciÃ³n de Licencia** (navbar.php):
  - InformaciÃ³n visible: Empresa, RUC, Licencia, DÃ­as restantes
  - Badges de color: Verde (â‰¥30 dÃ­as), Amarillo (7-29 dÃ­as), Rojo (<7 dÃ­as)
  - CÃ¡lculo automÃ¡tico desde `$_SESSION['license_expiration']`
  - Oculto para sistema principal (license='default')
  - DiseÃ±o responsivo con iconos FontAwesome
- âœ… **Sistema Debugging WizardController**:
  - 11 pasos con logs detallados (emojis identificadores)
  - Debug variables de entorno (TENANT_DB_*, DB_*)
  - Try-catch por paso crÃ­tico con stack trace completo
  - Manejo robusto de rollback en caso de error
- âœ… **Fix Error ProducciÃ³n**:
  - Eliminado llamada `inTransaction()` inexistente
  - Logs estructurados para diagnÃ³stico rÃ¡pido

**ðŸ"ˆ EstadÃ­sticas**:
- 2 archivos modificados | +332 lÃ­neas cÃ³digo | 2 mÃ©todos nuevos
- 1 error crÃ­tico corregido | 0 cambios BD | Deployment: 5-10 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.10.md)**

---

### **[v3.5.9]** - 2025-11-21 - *Employee Import System Overhaul + Wizard UI Improvements*
**Tipo**: FEATURE + UI/UX Enhancement
**Fase**: Employee Import System + Wizard UI/UX
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Sistema ImportaciÃ³n Excel Actualizado**:
  - 3 campos nuevos: `email` (requerido), `marca_asistencia`, `permite_horas_extras`
  - MÃ©todo `formatBoolean()` flexible (1/0, SI/NO, YES/NO)
  - Template Excel 30â†'33 columnas + shift completo
  - Instrucciones mejoradas con 3 secciones nuevas
- âœ… **IntegraciÃ³n employee_payroll_salaries**:
  - CreaciÃ³n automÃ¡tica registro salario por tipo de planilla
  - AuditorÃ­a completa (`notes`, `created_by`)
  - Empleados listos para procesamiento planillas
- âœ… **AsignaciÃ³n Foto Default**:
  - Ruta automÃ¡tica: `images/facebook-profile-image.jpeg`
  - Consistencia visual en listados
- âœ… **Wizard Crear Empresa UI**:
  - MÃ¡rgenes simÃ©tricos perfectos (izquierda/derecha)
  - Padding uniforme 40px contenido
  - Responsive optimizado (375px/1024px/1920px)
  - Botones con padding mejorado + separador visual

**ðŸ"ˆ EstadÃ­sticas**:
- 2 archivos modificados | +282 lÃ­neas cÃ³digo agregadas | ~85 lÃ­neas modificadas
- 1 mÃ©todo nuevo (`formatBoolean()`) | 8 validaciones nuevas
- 0 cambios BD | Deployment: 10-15 minutos

**[ðŸ"„ Ver detalles completos â†'](./changelog/v3.5.9.md)**

---

### **[v3.5.7]** - 2025-11-15 - *MÃ³dulo Vacaciones: CÃ¡lculo 11 Meses + Control Planillas Ãšnicas*
**Tipo**: Feature - MÃ³dulo Vacaciones PanamÃ¡
**Fase**: FASE 5 - MÃ³dulo Vacaciones (Subfase 5.1 40%)
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **CÃ¡lculo Salario Diario 11 Meses** (VacationController.php):
  - MÃ©todo `calculateVacationDailySalary()` nuevo (47 lÃ­neas)
  - FÃ³rmula: `ACUMULADOS("SALARIO_BASE", Ãºltimos 11 meses) Ã· 11 Ã· 30`
  - IntegraciÃ³n en mÃ©todos `store()`, `approve()`, `balance()`
  - Fallback a salario actual si no hay acumulados
  - Logging detallado para auditorÃ­a
- âœ… **Control Planillas Ãšnicas**:
  - Campo `payroll_id` en tabla `vacation_requests`
  - ValidaciÃ³n para evitar generaciÃ³n duplicada de planillas
  - ActualizaciÃ³n automÃ¡tica despuÃ©s de crear planilla
  - Foreign Key a `planilla_cabecera` con CASCADE
- âœ… **Mejoras UI Vista Balance** (balance.php):
  - ExplicaciÃ³n visible de fÃ³rmula de cÃ¡lculo
  - LÃ³gica condicional botÃ³n generar planilla
  - BotÃ³n gris para ver planilla existente
  - EliminaciÃ³n botÃ³n grande innecesario
- âœ… **Mejoras PDF Solicitud** (PDFReportController.php):
  - SecciÃ³n "CompensaciÃ³n / Monto a Pagar"
  - SecciÃ³n "Resumen de DÃ­as (Balance Actual)"
  - CÃ¡lculo balance resultante despuÃ©s de aprobaciÃ³n
  - Layout mejorado con columnas dinÃ¡micas
 - âœ… **Motor FÃ³rmulas Seguro** (PlanillaConceptCalculatorSecure.php):
   - Nueva funciÃ³n `CONCEPTO("NOMBRE")` para referenciar/evaluar otros conceptos con retorno 0 si no existe
 - âœ… **PreparaciÃ³n Horas Extra**:
   - Migraciones: `employees.overtime_eligible`, tolerancias en `schedules`, `attendance_calculations.overtime_approval`
   - Cableado inicial en controladores/modelos/calculadores

**ðŸ“ˆ EstadÃ­sticas**:
- 3 archivos modificados | 1 archivo nuevo | +203 lÃ­neas cÃ³digo agregadas | -63 lÃ­neas eliminadas
- 1 tabla modificada | 1 campo nuevo | 1 Ã­ndice nuevo | 1 foreign key nueva
- Deployment: 5-10 minutos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.7.md)**

---

### **[v3.5.6]** - 2025-11-13 - *SincronizaciÃ³n Calendario API + Feriados Pagados + UnificaciÃ³n*
**Tipo**: Feature - SincronizaciÃ³n automÃ¡tica calendario empresarial + UnificaciÃ³n vista vacaciones
**Fase**: Calendario Empresarial - IntegraciÃ³n API Base44
**Criticidad**: Media

**Componentes Principales**:
- âœ… **CalendarSyncService** (~500 lÃ­neas):
  - SincronizaciÃ³n manual calendario desde API Base44
  - Soporte sincronizaciÃ³n completa o por aÃ±o especÃ­fico
  - Modo `replace`: elimina registros existentes antes de importar
  - Modo `dry_run`: simulaciÃ³n sin modificar BD
  - Mapeo tipos de dÃ­a (LABORAL, FERIADO, DUELO_NACIONAL, ESPECIAL)
  - Logging detallado de operaciones
- âœ… **Campo is_paid_holiday**:
  - Nuevo campo en tabla `business_calendar`
  - IntegraciÃ³n con API Base44 (campo `paid`)
  - IdentificaciÃ³n de feriados pagados para procesamiento asistencias
  - Modal ediciÃ³n con checkbox "Feriado Pagado"
- âœ… **Endpoint SincronizaciÃ³n**:
  - POST `/panel/business-calendar/sync-api`
  - ValidaciÃ³n CSRF obligatoria
  - ParÃ¡metros: year, replace, dry_run
  - Respuesta JSON con estadÃ­sticas detalladas
- âœ… **UI SincronizaciÃ³n** (calendar.php):
  - BotÃ³n "Sincronizar desde API"
  - Modal confirmaciÃ³n con SweetAlert2
  - Tabla de estadÃ­sticas al completar
  - Recarga automÃ¡tica de pÃ¡gina
- âœ… **Tabla calendar_sync_log** (~40 lÃ­neas SQL):
  - Tracking completo de sincronizaciones
  - Campos: tipo, tiempos, duraciÃ³n, status, estadÃ­sticas
  - 4 Ã­ndices optimizados
- âœ… **UnificaciÃ³n Calendario Vacaciones**:
  - VacationController usa `business_calendar` (antes: `vacation_calendar`)
  - Query unificado con filtros por `day_type`
  - Vista vacation/calendar.php actualizada (~100 lÃ­neas)
  - Eventos con colores dinÃ¡micos por tipo de dÃ­a
  - Modal informaciÃ³n mejorado
- âœ… **Procesamiento Feriados Pagados**:
  - AttendanceController->processDay() genera 8 horas trabajadas
  - Aplicable a todos los empleados en feriados pagados
  - IntegraciÃ³n con campo `is_paid_holiday`
- âœ… **Fix DataTables**: CorrecciÃ³n sync-history cuando no hay registros

**ðŸ“ˆ EstadÃ­sticas**:
- 5 archivos modificados | 2 archivos nuevos | ~825 lÃ­neas cÃ³digo agregadas
- 1 tabla BD nueva (calendar_sync_log) | 1 campo nuevo (is_paid_holiday)
- 1 endpoint nuevo | 1 servicio nuevo (CalendarSyncService)
- Deployment: 5-10 minutos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.6.md)**

---

### **[v3.5.5]** - 2025-11-03 - *Almuerzo en Asistencias: marcaciones y cÃ¡lculos*
**Tipo**: Feature - CÃ¡lculos de Asistencia + UI
**Fase**: Subfase 7.2 (CÃ¡lculos) mejorada + 7.5 (Interfaz)
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Base de Datos**:
  - `schedules`: agrega `salida_almuerzo`, `entrada_almuerzo` (+ Ã­ndice)
  - `attendance_detail`: agrega `lunch_out`, `lunch_in`, `scheduled_lunch_out`, `scheduled_lunch_in`, `lunch_duration_minutes`, `lunch_exceeded_minutes` (+ Ã­ndices)
  - Trigger `trg_calculate_lunch_duration` para calcular duraciÃ³n/exceso de almuerzo
  - Vista `v_attendance_detail_with_lunch` y procedimiento `sp_validate_attendance_completeness`
- âœ… **CÃ¡lculos**:
  - `AttendanceCalculator`: resta tiempo de almuerzo de horas trabajadas, incluye mÃ©tricas y horarios programados de almuerzo
- âœ… **Procesamiento**:
  - `RecordsProcessor`: clasifica marcaciones en entrada/salida y almuerzo (salida/entrada) cuando el horario lo define
  - Estado requiere 4 marcaciones si hay almuerzo programado; `getSchedule` incluye campos de almuerzo
- âœ… **Interfaz**:
  - `attendance/detail.php`: nuevas columnas â€œSalida Almuerzo / Entrada Almuerzoâ€ y campos en el modal de ediciÃ³n
  - Modal de â€œProcesar dÃ­aâ€: checklist para opciones (procesar registros, detectar ausencias, marcar omisiones, recalcular mÃ©tricas)
  - Modal de mÃ©tricas ampliado: desglose (tipo de dÃ­a, marcaciones y horarios programados, horas totales/regulares/extras +25/+50, nocturnas, feriados, almuerzo y puntualidad)

- âœ… **Flujo de Procesamiento Unificado**:
  - `Process Day` delega consolidaciÃ³n a `RecordsProcessor->processDay(date)`
  - Persiste todas las marcaciones en `attendance_detail` y marca `attendance_records` como procesadas
  - Filtro global por `employees.marca_asistencia = 1` en procesamiento y ausencias
  - Ausencias almacenan `schedule_id`
  - Agrupado robusto: si `punch_date` es NULL se usa `DATE(timestamp)`
  - Fix: se agrega `AttendanceHeader::getById()` para reconstruir DATETIME de almuerzo al actualizar detalles

**ðŸ“ˆ EstadÃ­sticas**:
- 9 archivos cambiados | +1200 inserciones | -80 eliminaciones | 3 migraciones nuevas

---

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.5.md)**

---

### **[v3.5.4]** - 2025-11-01 - *Reportes Asistencias + Mejoras UI Planillas*
**Tipo**: Mejora - Funcionalidad + UX
**Fase**: Subfase 7.5 - Interfaz y Reportes (30%)
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Reporte de Marcaciones Completo** (408 lÃ­neas):
  - ReportsGenerator->generateDetailedPunchesReport() (145 lÃ­neas)
  - ExcelExporter->exportPunchesReport() con formato profesional (188 lÃ­neas)
  - AttendanceController->punchesReport() endpoint completo (75 lÃ­neas)
  - Ruta /panel/attendance/reports/punches configurada
  - 8 estadÃ­sticas resumen + top 10 tardanzas + detalle por departamento (10 columnas)
- âœ… **Fix Label Liquidaciones** (2 lÃ­neas):
  - "PosiciÃ³n:" â†’ "Cargo:" en PDF y Excel
  - LiquidationController.php (exportPayrollPdf, exportPayrollExcel)
- âœ… **Mejoras Comprobantes Horizontales** (~203 lÃ­neas):
  - EliminaciÃ³n headers/footers TCPDF automÃ¡ticos
  - Colores profesionales liquidaciones (naranja intenso, gris claro, azul profundo)
  - SecciÃ³n firmas 3 columnas (Elaborado/Autorizado/Recibido por Colaborador)
  - ReportController.php (generateAllPayslipsHorizontalPDF, generateIndividualPaySlipPDF)
- ðŸ“ˆ **EstadÃ­sticas**:
  - 6 archivos modificados | ~620 lÃ­neas cÃ³digo agregadas
  - 3 mÃ©todos nuevos | 1 ruta nueva | 0 cambios BD
  - Deployment: 10-15 minutos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.4.md)**

---

### **[v3.5.3]** - 2025-11-01 - *EliminaciÃ³n Completa eval() + Arquitectura Segura*
**Tipo**: RefactorizaciÃ³n - Seguridad
**Fase**: Core System - Mejoras de Seguridad
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **EliminaciÃ³n Total de eval()**:
  - 862 lÃ­neas de cÃ³digo corrupto/duplicado eliminadas (lÃ­neas 166-1030)
  - Archivo PlanillaConceptCalculator.php reducido de 2058 a 1196 lÃ­neas
  - 100% evaluaciÃ³n de fÃ³rmulas mediante NXP\MathExecutor (sin eval())
  - Arquitectura de herencia: `class PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure`
- âœ… **RefactorizaciÃ³n Visibilidad**:
  - 9 propiedades `private`â†’`protected` en clase padre (incluye $db, $executor, etc.)
  - 18 mÃ©todos `private`â†’`protected` para permitir herencia completa
  - Fix crÃ­tico acceso $db que causaba error null pointer
- âœ… **ValidaciÃ³n Variables Extendida**:
  - Variables string permitidas: EMPLEADO, CLAVE_SS, CLAVE_SEGURO_SOCIAL
  - Mejora validaciÃ³n en configurarValidacionesEstritas() (lÃ­neas 54-70)
- âœ… **Testing Runtime Completo**:
  - 3 errores resueltos: null $db, private methods, variable validation
  - ValidaciÃ³n con planillas reales (ID 85, tipo planilla 2, empleado 1)
- âœ… **Mejoras de Seguridad**:
  - EliminaciÃ³n de vectores de inyecciÃ³n de cÃ³digo
  - Sandbox aislado para evaluaciÃ³n de fÃ³rmulas
  - ValidaciÃ³n estricta de variables antes de evaluaciÃ³n
- ðŸ“ˆ **EstadÃ­sticas**:
  - 2 archivos modificados | -1085 lÃ­neas inseguras | +321 lÃ­neas seguras
  - 9 propiedades refactorizadas | 18 mÃ©todos refactorizados
  - 0 cambios BD | 3 errores runtime resueltos | 0 uso eval() restante
  - Deployment: 10-15 minutos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.3.md)**

---

### **[v3.5.2]** - 2025-10-30 - *Mejora Reportes PDF/Excel Liquidaciones*
**Tipo**: Mejora - Reportes de LiquidaciÃ³n
**Fase**: Mejora de UX - DocumentaciÃ³n Profesional
**Criticidad**: Media

**Componentes Principales**:
- âœ… **Campos Adicionales en Reportes** (4 nuevos campos):
  - Fecha Fin de Contrato (desde employee_terminations)
  - PosiciÃ³n (desde tabla cargos)
  - Tiempo en la Empresa (calculado automÃ¡ticamente: "X aÃ±os, Y meses, Z dÃ­as")
  - Salario (desde employees.sueldo_individual con formato $X,XXX.XX)
- âœ… **SecciÃ³n de Firmas Profesionales**:
  - 3 columnas: Autorizado por (Gerencia), Elaborado por (RRHH), Recibido por (Colaborador)
  - LÃ­neas para firma fÃ­sica en PDF
  - Formato profesional en Excel
  - Espaciado optimizado para impresiÃ³n
- âœ… **Mejoras SQL Queries**:
  - JOINs adicionales a posiciones, cargos, employee_terminations
  - Query optimizado para obtener toda la informaciÃ³n en una sola consulta
- âœ… **ImplementaciÃ³n Dual**:
  - MÃ©todo exportPayrollPdf() actualizado (~300 lÃ­neas modificadas)
  - MÃ©todo exportPayrollExcel() actualizado (~280 lÃ­neas modificadas)
  - CÃ¡lculo de tiempo en empresa reutilizable
- ðŸ“ˆ **EstadÃ­sticas**:
  - 1 archivo modificado | 2 mÃ©todos actualizados | ~250 lÃ­neas cÃ³digo agregadas
  - 4 campos nuevos | 1 secciÃ³n nueva (firmas) | 3 JOINs SQL adicionales
  - 0 cambios en BD | Deployment: 5-10 minutos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.2.md)**

---

### **[v3.5.1]** - 2025-10-28 - *Data Cleanup y Fixes CrÃ­ticos Sistema Asistencias*
**Tipo**: Hotfix / Data Cleanup
**Fase**: Mantenimiento - Correcciones CrÃ­ticas Sistema Asistencias
**Criticidad**: Alta

**Componentes Principales**:
- âœ… **Fix Error "Data truncated for column 'synced_from'"**:
  - Valores incorrectos corregidos: 'API_SYNC' â†’ 'API', 'MANUAL_PROCESSING' â†’ 'MANUAL'
  - Archivos modificados: AttendanceSyncService.php lÃ­nea 436, AttendanceController.php lÃ­nea 992
  - Script SQL limpieza datos existentes
- âœ… **NormalizaciÃ³n Campo Timestamp**:
  - Soporte para `actual_timestamp`, `registered_timestamp` ademÃ¡s de `timestamp`
  - Mejora compatibilidad con API Base44
  - AttendanceSyncService.php lÃ­neas 268-275
- âœ… **CorrecciÃ³n Emails Empleados**:
  - 3 empleados actualizados para coincidir con API Base44
  - ID 2 (KATHY GONZALEZ), ID 3 (NESTOR MOLINA), ID 5 (DILSA QUINTANA)
- âœ… **Limpieza Registros InvÃ¡lidos**:
  - 10 registros attendance_detail con time_in/time_out NULL eliminados
  - 179 registros raw duplicados marcados como procesados
- âœ… **Fix CSRF Dispositivos de Asistencia**:
  - Token CSRF agregado a vista index dispositivos (lÃ­nea 32)
  - ValidaciÃ³n CSRF agregada a mÃ©todos delete(), testConnection(), toggle()
  - Funciones edit, test conexiÃ³n, desactivar y eliminar ahora funcionan correctamente
- âœ… **Script Deployment ProducciÃ³n**:
  - MigraciÃ³n SQL completa con backups automÃ¡ticos (2025_10_28_fix_attendance_data_cleanup.sql)
  - GuÃ­a deployment paso a paso (GUIA_DEPLOYMENT_PRODUCCION.md)
  - Script verificaciÃ³n pre/post deployment (verify_attendance_system.sql)
  - Plan rollback incluido (5-10 minutos)
- ðŸ“ˆ **EstadÃ­sticas**:
  - Mejora tasa Ã©xito sincronizaciÃ³n: 50% â†’ 93% (+86%)
  - Registros procesados: 30 â†’ 209 (+597%)
  - 5 archivos modificados | 4 archivos nuevos | ~1,500 lÃ­neas documentaciÃ³n

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.5.1.md)**

---

### **[v3.4.8]** - 2025-10-23 - *Procesamiento Completo DÃ­a Asistencias*
**Tipo**: Mejora - Procesamiento Batch + Reprocess + Filtros
**Fase**: Subfase 7.2 - CÃ¡lculos Avanzados de Asistencias (80%)

**Componentes Principales**:
- âœ… **Procesamiento Completo DÃ­a** (220+ lÃ­neas):
  - Pipeline 3 pasos: ausencias â†’ omisiones â†’ cÃ¡lculos completos
  - BotÃ³n "Procesar Marcaciones" con feedback SweetAlert2
  - DetecciÃ³n automÃ¡tica empleados sin marcaciÃ³n â†’ registro ABSENT
  - DetecciÃ³n single punch (solo entrada o salida) â†’ estado INCOMPLETE/OMISIÃ“N
  - CÃ¡lculo completo mÃ©tricas (horas trabajadas, extras, tardanzas)
- âœ… **Reprocesamiento con Recarga** (90 lÃ­neas):
  - MÃ©todo AttendanceDetail->deleteByHeader() para limpiar detalles
  - Recarga desde tabla attendance (marcaciones originales)
  - RecreaciÃ³n completa + pipeline detecciÃ³n + cÃ¡lculos
- âœ… **Filtrado Tipo Planilla SessionStorage** (50 lÃ­neas):
  - Lectura desde sessionStorage (navbar selection)
  - ValidaciÃ³n frontend + backend con FIND_IN_SET()
  - Sin duplicaciÃ³n de selectores (reutiliza infraestructura existente)
- âœ… **Columna Horas Extras** (40 lÃ­neas):
  - Badge azul con total horas extras
  - Desglose +25% y +50% en tooltip
  - Formato decimal 2 decimales
- âœ… **Fixes jQuery Loading** (30 lÃ­neas):
  - Output buffering (ob_start/ob_get_clean) en sync-history y list views
  - Scripts renderizados al final de pÃ¡gina
  - Variable baseUrl para URLs relativas
- âœ… **Fixes Errores CrÃ­ticos**:
  - Undefined array key "date" en AbsenceDetector
  - Undefined array key "attendance_date" en AttendanceHeader
  - Refactor AttendanceHeader->update() para campos dinÃ¡micos
- ðŸ“ˆ **EstadÃ­sticas**: 7 archivos modificados | ~500 lÃ­neas cÃ³digo | 2 mÃ©todos nuevos | 1 ruta nueva | 4 bugs fixed

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.8.md)**

---

### **[v3.4.7]** - 2025-10-20 - *IntegraciÃ³n Completa Planillas-Asistencias*
**Tipo**: Feature - Subfase 7.4 IntegraciÃ³n Planillas-Asistencias
**Fase**: Subfase 7.4 - Mapeo AutomÃ¡tico Asistencias â†’ Conceptos (100%)

**Componentes Principales**:
- âœ… **MigraciÃ³n BD IntegraciÃ³n** (342 lÃ­neas SQL):
  - Tabla `payroll_attendance_summary` (38 campos) - resumen por empleado/planilla
  - Tabla `attendance_concepts_mapping` (22 campos) - configuraciÃ³n mapeos
  - Tabla `payroll_attendance_details` (16 campos) - detalles dÃ­a por dÃ­a
  - 3 vistas Ãºtiles + 10 Foreign Keys + 15 Ã­ndices optimizados
- âœ… **PeriodAttendanceSummary Service** (349 lÃ­neas):
  - GeneraciÃ³n resÃºmenes: horas trabajadas, overtime, tardanzas, ausencias, puntualidad
  - IntegraciÃ³n LegalComplianceChecker para compliance
  - CÃ¡lculos monetarios: regular_pay, overtime_pay, night_pay, holiday_pay
- âœ… **AttendanceConceptMapper Service** (636 lÃ­neas):
  - 10 mÃ©todos especializados mapeo (regular, overtime25/50, night, holidays, etc.)
  - Soporte fÃ³rmulas dinÃ¡micas: {SUELDO}, {TARIFA_HORA}, {CANTIDAD}
  - EvaluaciÃ³n segura con MathExecutor (sin eval())
  - Mapeo configurable por tipo_planilla_id + situacion_id
- âœ… **PayrollAttendanceIntegrator Service** (415 lÃ­neas):
  - Procesamiento batch con transacciones
  - MÃ©todos: processPayrollAttendance(), processEmployeeAttendance()
  - Persistencia completa: summary + details
  - EstadÃ­sticas: processed, summaries_created, concepts_generated, errors
- âœ… **IntegraciÃ³n PayrollController** (+217 lÃ­neas):
  - 4 endpoints AJAX: process-attendance, attendance-summary, attendance-details, delete-attendance-data
  - MÃ©todo getEmployeesByPayroll() agregado a PayrollDetail
  - Rutas configuradas en App.php
- âœ… **Script Testing Completo** (481 lÃ­neas):
  - 5 fases testing (preparaciÃ³n, summary, mapper, integrator, batch)
  - Color-coded output + auto-detecciÃ³n datos test
  - Verbose mode + success rate calculation
- âœ… **Fixes**: AttendanceDeviceController layout (render() vs view()) + sync-history navigation
- ðŸ“ˆ **EstadÃ­sticas**: 3 servicios nuevos | 3 tablas BD | ~2,600 lÃ­neas cÃ³digo | 80% mÃ³dulo asistencias completado

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.7.md)**

---

### **[v3.4.6]** - 2025-10-20 - *Sistema de Alertas Legales AutomÃ¡ticas*
**Tipo**: Feature - Subfase 7.3 Consideraciones Legales PanamÃ¡
**Fase**: Subfase 7.3 - Sistema de Alertas (100%)

**Componentes Principales**:
- âœ… **AlertsSystem** (675 lÃ­neas):
  - 14 mÃ©todos pÃºblicos gestiÃ³n completa de alertas
  - GeneraciÃ³n automÃ¡tica desde LegalComplianceChecker
  - Workflow: PENDING â†’ ACKNOWLEDGED â†’ RESOLVED/DISMISSED
  - 10+ tipos de alertas (excesos jornada, ausencias graves, tardanzas)
  - 3 niveles severidad: INFO, WARNING, CRITICAL
  - Metadata JSON flexible + referencias legales (Art. 31, 35, 38, 39, 48, 213)
- âœ… **MigraciÃ³n BD attendance_alerts** (342 lÃ­neas):
  - Tabla completa 20 campos + metadata JSON
  - 11 Ã­ndices optimizados + 4 Foreign Keys
  - 4 vistas Ãºtiles + 2 triggers + 3 stored procedures
- âœ… **IntegraciÃ³n AttendanceCalculator** (+180 lÃ­neas):
  - MÃ©todos: checkLegalComplianceAndAlert(), calculateSaveAndAlert(), etc.
  - Flujo completo: calcular â†’ guardar â†’ verificar â†’ alertar automÃ¡tico
- âœ… **Script Testing Completo** (481 lÃ­neas, 33 tests):
  - Tests componentes legales: LegalComplianceChecker, OvertimeRateCalculator, WorkingDayClassifier
  - Tests AlertsSystem: CRUD, workflow, estadÃ­sticas
  - Tests IntegraciÃ³n completa
  - **Resultado**: 21/22 tests funcionales (95.5%)
- ðŸ“ˆ **EstadÃ­sticas**: 3 archivos creados | 1 modificado | ~1,678 lÃ­neas cÃ³digo | 1 tabla BD

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.6.md)**

---

### **[v3.4.5]** - 2025-10-17 - *IntegraciÃ³n UI Calculadores Asistencias*
**Tipo**: Mejora - IntegraciÃ³n UI + Endpoints AJAX
**Fase**: Subfase 7.2 - CÃ¡lculos Avanzados de Asistencias (75%)

**Componentes Principales**:
- âœ… **AttendanceController IntegraciÃ³n** (+370 lÃ­neas):
  - 7 mÃ©todos AJAX: calculateAttendance(), detectAbsences(), processCalculations(), etc.
  - IntegraciÃ³n completa calculadores con interfaz visual
  - ValidaciÃ³n CSRF + manejo errores robusto
- âœ… **Vista detail.php Mejorada** (+120 lÃ­neas):
  - BotÃ³n "Procesar CÃ¡lculos DÃ­a" para batch processing
  - Nueva columna "Puntualidad" con badges coloreados (verde â‰¥80%, amarillo 50-79%, rojo <50%)
  - Icono estrella dorada para asistencia perfecta
  - Modal detalles cÃ¡lculo completo (horas, tardanzas, extras, score)
- âœ… **Vista list.php Mejorada** (+100 lÃ­neas):
  - BotÃ³n "Detectar Ausencias" con modal completo
  - Validaciones JavaScript + confirmaciÃ³n SweetAlert2
  - Checkbox "Guardar en BD" + estadÃ­sticas resultados
- âœ… **Vista pending-absences.php NUEVA** (410 lÃ­neas):
  - 4 estadÃ­sticas cards (total pendientes, injustificadas, por revisar, empleados afectados)
  - Filtros avanzados con Select2 (empleado, fecha inicio/fin)
  - DataTable con listado completo + ordenamiento espaÃ±ol
  - Modal justificaciÃ³n con 6 tipos (MEDICAL, PERMISSION, VACATION, BEREAVEMENT, MATERNITY, OTHER)
- âœ… **Routing + Fixes CrÃ­ticos**:
  - 6 rutas nuevas en App.php (calculate, detect-absences, justify, etc.)
  - Fix controller mapping: 'Attendance' â†’ 'AttendanceController' (lÃ­nea 60)
  - Fix jQuery/DataTables en sync-history/index.php
- ðŸ“ˆ **EstadÃ­sticas**: 4 archivos modificados | 1 vista nueva | ~850 lÃ­neas cÃ³digo UI | 7 endpoints AJAX | 2 fixes crÃ­ticos

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.5.md)**

---

### **[v3.4.4]** - 2025-10-16 - *AttendanceCalculator + AbsenceDetector con Persistencia BD*
**Tipo**: Mejora - ImplementaciÃ³n Core Calculators
**Fase**: Subfase 7.2 - CÃ¡lculos Avanzados de Asistencias (60%)

**Componentes Principales**:
- âœ… **AttendanceCalculator Mejorado** (+280 lÃ­neas, total 708):
  - MÃ©todo `saveCalculation()` - Guarda en attendance_calculations (INSERT/UPDATE automÃ¡tico)
  - MÃ©todo `calculateAndSave()` - All-in-one (calcula + guarda + retorna con ID)
  - MÃ©todo `calculateAndSaveBulk()` - Procesamiento batch con estadÃ­sticas
  - MÃ©todos CRUD: `getCalculation()`, `deleteCalculation()`, `getConfig()`
  - IntegraciÃ³n completa con WorkScheduleResolver, OvertimeCalculator, WorkingDayClassifier
- âœ… **AbsenceDetector Mejorado** (+385 lÃ­neas, total 693):
  - MÃ©todo `saveAbsence()` - Guarda en attendance_absence_log (INSERT/UPDATE automÃ¡tico)
  - MÃ©todo `detectAndSaveAbsences()` - Detecta y guarda con estadÃ­sticas por empleado
  - MÃ©todo `detectAndSaveBulk()` - Procesamiento batch mÃºltiples empleados
  - Workflow justificaciones: `justifyAbsence()`, `rejectJustification()`
  - Consultas: `getPendingAbsences()`, `getEmployeeAbsences()`, `getAbsenceStatistics()`
  - Estados: JUSTIFIED, UNJUSTIFIED, PENDING con resoluciÃ³n tracking
- âœ… **Suite Testing Completa** (370+ lÃ­neas):
  - 22 tests organizados en 6 mÃ³dulos temÃ¡ticos
  - 90.9% tasa de Ã©xito (20/22 tests pasaron)
  - MÃ³dulos: CÃ¡lculos BÃ¡sicos, Tardanzas, Asistencia Perfecta, Jornadas Especiales, BD, Batch
- ðŸ“ˆ **EstadÃ­sticas**: 3 archivos (2 modificados + 1 creado) | ~1,035 lÃ­neas cÃ³digo

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.4.md)**

---

### **[v3.4.3]** - 2025-10-16 - *Vistas Separadas Sistema Asistencias*
**Tipo**: Mejora - RefactorizaciÃ³n Arquitectura
**Fase**: Subfase 7.2 - CÃ¡lculos Avanzados de Asistencias (35%)

**Componentes Principales**:
- âœ… **AttendanceController Completo** (135 lÃ­neas): Controlador dedicado con 4 mÃ©todos (index, detail, sync, export)
- âœ… **3 Vistas Separadas**:
  - `list.php` (230 lÃ­neas): Listado marcaciones agrupadas por dÃ­a + filtros aÃ±o/mes/rango
  - `detail.php` (260 lÃ­neas): Detalle completo dÃ­a especÃ­fico + estadÃ­sticas + tabla empleados
  - `sync.php` (180 lÃ­neas): Panel sincronizaciÃ³n manual (Full/Hoy/Rango)
- âœ… **Attendance Model Extendido**: 5 mÃ©todos nuevos para estadÃ­sticas
  - getAttendanceSummaryByMonth(), getAttendanceSummaryByDateRange()
  - getAttendancesByDate(), getDayStatistics(), getAvailableYears()
- âœ… **Routing Mejorado**: App.php con rutas especÃ­ficas attendance (lÃ­neas 130-163)
- âœ… **Sidebar Reorganizado**: 5 opciones separadas (Marcaciones, Sincronizar, Reportes, Config API, Sistema Marcaciones)
- ðŸ“ˆ **EstadÃ­sticas**: 4 vistas nuevas | 1 controller | 5 mÃ©todos modelo | ~968 lÃ­neas cÃ³digo

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.3.md)**

---

### **[v3.4.2]** - 2025-10-10 - *Checkbox ValidaciÃ³n SituaciÃ³n + AnÃ¡lisis Reproceso HistÃ³rico*
**Tipo**: Mejora + AnÃ¡lisis
**Fase**: Sistema Reprocesamiento Planillas

**Componentes Principales**:
- âœ… **Checkbox ValidaciÃ³n SituaciÃ³n Empleado** (COMPLETADO)
  - Checkbox condicional en modal reprocesar planilla
  - ParÃ¡metro `validate_situacion` flujo completo (Vistaâ†’JSâ†’Controllerâ†’Model)
  - ValidaciÃ³n condicional `validateConceptConditions()` en Payroll.php
  - Default checked + logging detallado
- ðŸ“‹ **AnÃ¡lisis Reprocesamiento HistÃ³rico** (PROPUESTO)
  - Documento tÃ©cnico `ANALISIS_REPROCESO_HISTORICO.md` (400+ lÃ­neas)
  - 5 fases planificadas: DetecciÃ³n + Queries HistÃ³ricas + Modal + JavaScript + Testing
  - Query empleados histÃ³ricos con cÃ¡lculo situaciÃ³n por fechas
  - Query salarios histÃ³ricos con validaciÃ³n vigencias
  - Modal 3 opciones: HistÃ³rico/Actual/Cancelar
- ðŸ“ˆ **EstadÃ­sticas**: 4 archivos modificados | 57 lÃ­neas cÃ³digo agregadas

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.2.md)**

---

### **[v3.4.1]** - 2025-10-10 - *PreparaciÃ³n BD CÃ¡lculos Asistencias*
**Tipo**: Infraestructura Base de Datos
**Fase**: Subfase 7.2 - CÃ¡lculos Avanzados de Asistencias (25%)

**Componentes Principales**:
- ðŸ“Š Migraciones BD para cÃ¡lculos de asistencias
  - Tabla `attendance_calculations` (horas, tardanzas, mÃ©tricas)
  - Tabla `attendance_absence_log` (ausencias con justificaciones)
  - Tabla `employee_payroll_salaries` (salarios mÃºltiples por tipo planilla)
- ðŸ“ˆ **EstadÃ­sticas**: 298 lÃ­neas SQL | 3 tablas | 14 Foreign Keys | 22 Ãndices

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.1.md)**

---

### **[v3.4.0]** - 2025-10-09 - *IntegraciÃ³n API Base44*
**Tipo**: Nueva Funcionalidad - IntegraciÃ³n Externa
**Fase**: Subfase 7.1 - IntegraciÃ³n API Asistencias Base44 (COMPLETADA)

**Componentes Principales**:
- ðŸ”Œ Base44ApiClient (367 lÃ­neas) con retry logic
- ðŸ”„ AttendanceSyncService (510 lÃ­neas) sincronizaciÃ³n automÃ¡tica
- ðŸ“¡ Webhook Receiver para notificaciones tiempo real
- âš™ï¸ Interfaz AdminLTE configuraciÃ³n completa
- â° Cron job sincronizaciÃ³n cada 15 minutos
- ðŸ“ˆ **EstadÃ­sticas**: ~2,417 lÃ­neas cÃ³digo | 12 archivos nuevos | 3 tablas BD

**[ðŸ“„ Ver detalles completos â†’](./changelog/v3.4.0.md)**

---

### **[v3.3.22]** - 2025-10-06 - *InicializaciÃ³n AutomÃ¡tica Calendario*
**Tipo**: Mejora + Bugfix
**Fase**: Calendario Empresarial PanamÃ¡

**Componentes Principales**:
- âœ… Script CLI `fill_business_calendar_2025.php`
- âœ… MÃ©todo `BusinessCalendar->initializeYear($year)`
- âœ… Interfaz web con botÃ³n "Inicializar AÃ±o"
- âœ… Fix namespace Security (`App\Core\Security`)

---

### **[v3.3.21]** - 2025-10-06 - *Calendario Empresarial PanamÃ¡*
**Tipo**: Nueva Funcionalidad
**Fase**: FASE 4 Subfases 4.1-4.3 (75%)

**Componentes Principales**:
- ðŸ“… Tabla `business_calendar` (731 registros 2024-2025)
- ðŸ“Š BusinessCalendar Model (355+ lÃ­neas, 14 mÃ©todos)
- ðŸ–¥ï¸ Interfaz AdminLTE completa + FullCalendar.js 6.1.8
- ðŸ”§ CRUD completo + API AJAX + DataTables

---

## ðŸ“š **Versiones Anteriores**

Para consultar versiones anteriores (v3.3.20 y previas), consulte el archivo histÃ³rico:
**[ðŸ“„ CHANGELOG_LEGACY.md â†’](./CHANGELOG_LEGACY.md)**

*(PrÃ³ximamente: migraciÃ³n de versiones legacy a archivos individuales)*

---

## ðŸ“ **Estructura de Archivos**

```
documentation/
â”œâ”€â”€ CHANGELOG.md                    # Este archivo (Ã­ndice principal)
â”œâ”€â”€ CHANGELOG_LEGACY.md             # Versiones 3.3.20 y anteriores
â””â”€â”€ changelog/                      # Directorio de versiones individuales
    â”œâ”€â”€ v3.4.1.md                  # Migraciones BD CÃ¡lculos (10-Oct-2025)
    â”œâ”€â”€ v3.4.0.md                  # IntegraciÃ³n API Base44 (9-Oct-2025)
    â””â”€â”€ [versiones futuras...]
```

---

## ðŸ” **CÃ³mo Usar Este Ãndice**

1. **Ver Ãšltimas Versiones**: Las versiones mÃ¡s recientes estÃ¡n listadas arriba con resumen ejecutivo
2. **Detalles Completos**: Click en el enlace "Ver detalles completos â†’" para abrir el archivo especÃ­fico de la versiÃ³n
3. **Versiones Legacy**: Versiones anteriores a v3.4.0 estÃ¡n en `CHANGELOG_LEGACY.md`
4. **BÃºsqueda RÃ¡pida**: Usa Ctrl+F para buscar por nÃºmero de versiÃ³n, fecha o componente

---

## ðŸ“Š **Convenciones**

### **Tipos de Versiones**:
- **Major** (vX.0.0): Cambios arquitectÃ³nicos significativos
- **Minor** (v3.X.0): Nuevas funcionalidades o mÃ³dulos completos
- **Patch** (v3.4.X): Bugfixes, mejoras menores, migraciones BD

### **Tipos de Releases**:
- ðŸš€ **Nueva Funcionalidad**: Nuevos mÃ³dulos o caracterÃ­sticas importantes
- ðŸ”§ **Mejora**: Optimizaciones o ampliaciones de funcionalidad existente
- ðŸ› **Bugfix**: CorrecciÃ³n de errores
- ðŸ“Š **Infraestructura**: Migraciones BD, configuraciÃ³n, estructura
- ðŸ”’ **Seguridad**: Parches de seguridad y validaciones

### **Fases del Proyecto**:
- **FASE 1-3**: Core System completado
- **FASE 4**: Calendario Empresarial (completado)
- **FASE 5**: MÃ³dulo Vacaciones (pendiente)
- **FASE 6**: Multitenancy (pendiente)
- **FASE 7**: IntegraciÃ³n API Asistencias (en progreso 25%)
- **FASE 8-9**: ReporterÃ­a + Integraciones (pendiente)

---

## ðŸ“ **GuÃ­a para Nuevas Versiones**

Al crear una nueva versiÃ³n:

1. **Crear archivo individual**: `documentation/changelog/vX.Y.Z.md`
2. **Usar template**: Copiar estructura de `v3.4.1.md` o `v3.4.0.md`
3. **Actualizar este Ã­ndice**: Agregar entrada en secciÃ³n "Ãšltimas Versiones"
4. **Mantener orden**: Versiones mÃ¡s recientes primero
5. **Incluir estadÃ­sticas**: LÃ­neas de cÃ³digo, archivos, tablas BD
6. **Referencias cruzadas**: Enlazar versiones relacionadas

---

Última Actualización: 28 de Febrero, 2026
Sistema: Planillas MVC v3.5.22
Progreso Global: Core 100% | Calendario 100% | API Asistencias 92% | Liquidaciones 100% | Seguridad 100% | Multitenancy 85% | Employee Import 100% | Acumulados Export 100% | Permisos Granular 100% | Employee Files 100% | Migraciones Multi-Tenant 100% | Campos Adicionales 100% | UI/UX Animations (GSAP) 100% | ERP Integration (INNOVA) 100% | Vacaciones 90%

---

### **VERSIÓN v3.5.22 AGREGADA** - 2026-02-28
Ver archivo completo de changelog en: [documentation/changelog/v3.5.22.md](./changelog/v3.5.22.md)
**Resumen**: Refactor LiquidationReportController (1196 líneas) + Métodos cálculo avanzado LIQ007 + UX vacaciones mejorada

