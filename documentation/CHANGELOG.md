# 📋 CHANGELOG - Sistema de Planillas MVC

## 📖 **Índice de Versiones**

Este archivo sirve como índice principal para el historial de cambios del sistema. Cada versión tiene su propio archivo detallado en el directorio `changelog/`.

---

## 🆕 **Últimas Versiones**

### **[v3.5.5]** - 2025-11-03 - *Almuerzo en Asistencias: marcaciones y cálculos*
**Tipo**: Feature - Cálculos de Asistencia + UI
**Fase**: Subfase 7.2 (Cálculos) mejorada + 7.5 (Interfaz)
**Criticidad**: Media

**Componentes Principales**:
- ✅ **Base de Datos**:
  - `schedules`: agrega `salida_almuerzo`, `entrada_almuerzo` (+ índice)
  - `attendance_detail`: agrega `lunch_out`, `lunch_in`, `scheduled_lunch_out`, `scheduled_lunch_in`, `lunch_duration_minutes`, `lunch_exceeded_minutes` (+ índices)
  - Trigger `trg_calculate_lunch_duration` para calcular duración/exceso de almuerzo
  - Vista `v_attendance_detail_with_lunch` y procedimiento `sp_validate_attendance_completeness`
- ✅ **Cálculos**:
  - `AttendanceCalculator`: resta tiempo de almuerzo de horas trabajadas, incluye métricas y horarios programados de almuerzo
- ✅ **Procesamiento**:
  - `RecordsProcessor`: clasifica marcaciones en entrada/salida y almuerzo (salida/entrada) cuando el horario lo define
  - Estado requiere 4 marcaciones si hay almuerzo programado; `getSchedule` incluye campos de almuerzo
- ✅ **Interfaz**:
  - `attendance/detail.php`: nuevas columnas “Salida Almuerzo / Entrada Almuerzo” y campos en el modal de edición
  - Modal de “Procesar día”: checklist para opciones (procesar registros, detectar ausencias, marcar omisiones, recalcular métricas)
  
- ✅ **Flujo de Procesamiento Unificado**:
  - `Process Day` delega consolidación a `RecordsProcessor->processDay(date)`
  - Persiste todas las marcaciones en `attendance_detail` y marca `attendance_records` como procesadas
  - Filtro global por `employees.marca_asistencia = 1` en procesamiento y ausencias
  - Ausencias almacenan `schedule_id`
  - Agrupado robusto: si `punch_date` es NULL se usa `DATE(timestamp)`
  - Fix: se agrega `AttendanceHeader::getById()` para reconstruir DATETIME de almuerzo al actualizar detalles

**📈 Estadísticas**:
- 9 archivos cambiados | +1200 inserciones | -80 eliminaciones | 3 migraciones nuevas

---

**[📄 Ver detalles completos →](./changelog/v3.5.5.md)**

---

### **[v3.5.4]** - 2025-11-01 - *Reportes Asistencias + Mejoras UI Planillas*
**Tipo**: Mejora - Funcionalidad + UX
**Fase**: Subfase 7.5 - Interfaz y Reportes (30%)
**Criticidad**: Media

**Componentes Principales**:
- ✅ **Reporte de Marcaciones Completo** (408 líneas):
  - ReportsGenerator->generateDetailedPunchesReport() (145 líneas)
  - ExcelExporter->exportPunchesReport() con formato profesional (188 líneas)
  - AttendanceController->punchesReport() endpoint completo (75 líneas)
  - Ruta /panel/attendance/reports/punches configurada
  - 8 estadísticas resumen + top 10 tardanzas + detalle por departamento (10 columnas)
- ✅ **Fix Label Liquidaciones** (2 líneas):
  - "Posición:" → "Cargo:" en PDF y Excel
  - LiquidationController.php (exportPayrollPdf, exportPayrollExcel)
- ✅ **Mejoras Comprobantes Horizontales** (~203 líneas):
  - Eliminación headers/footers TCPDF automáticos
  - Colores profesionales liquidaciones (naranja intenso, gris claro, azul profundo)
  - Sección firmas 3 columnas (Elaborado/Autorizado/Recibido por Colaborador)
  - ReportController.php (generateAllPayslipsHorizontalPDF, generateIndividualPaySlipPDF)
- 📈 **Estadísticas**:
  - 6 archivos modificados | ~620 líneas código agregadas
  - 3 métodos nuevos | 1 ruta nueva | 0 cambios BD
  - Deployment: 10-15 minutos

**[📄 Ver detalles completos →](./changelog/v3.5.4.md)**

---

### **[v3.5.3]** - 2025-11-01 - *Eliminación Completa eval() + Arquitectura Segura*
**Tipo**: Refactorización - Seguridad
**Fase**: Core System - Mejoras de Seguridad
**Criticidad**: Alta

**Componentes Principales**:
- ✅ **Eliminación Total de eval()**:
  - 862 líneas de código corrupto/duplicado eliminadas (líneas 166-1030)
  - Archivo PlanillaConceptCalculator.php reducido de 2058 a 1196 líneas
  - 100% evaluación de fórmulas mediante NXP\MathExecutor (sin eval())
  - Arquitectura de herencia: `class PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure`
- ✅ **Refactorización Visibilidad**:
  - 9 propiedades `private`→`protected` en clase padre (incluye $db, $executor, etc.)
  - 18 métodos `private`→`protected` para permitir herencia completa
  - Fix crítico acceso $db que causaba error null pointer
- ✅ **Validación Variables Extendida**:
  - Variables string permitidas: EMPLEADO, CLAVE_SS, CLAVE_SEGURO_SOCIAL
  - Mejora validación en configurarValidacionesEstritas() (líneas 54-70)
- ✅ **Testing Runtime Completo**:
  - 3 errores resueltos: null $db, private methods, variable validation
  - Validación con planillas reales (ID 85, tipo planilla 2, empleado 1)
- ✅ **Mejoras de Seguridad**:
  - Eliminación de vectores de inyección de código
  - Sandbox aislado para evaluación de fórmulas
  - Validación estricta de variables antes de evaluación
- 📈 **Estadísticas**:
  - 2 archivos modificados | -1085 líneas inseguras | +321 líneas seguras
  - 9 propiedades refactorizadas | 18 métodos refactorizados
  - 0 cambios BD | 3 errores runtime resueltos | 0 uso eval() restante
  - Deployment: 10-15 minutos

**[📄 Ver detalles completos →](./changelog/v3.5.3.md)**

---

### **[v3.5.2]** - 2025-10-30 - *Mejora Reportes PDF/Excel Liquidaciones*
**Tipo**: Mejora - Reportes de Liquidación
**Fase**: Mejora de UX - Documentación Profesional
**Criticidad**: Media

**Componentes Principales**:
- ✅ **Campos Adicionales en Reportes** (4 nuevos campos):
  - Fecha Fin de Contrato (desde employee_terminations)
  - Posición (desde tabla cargos)
  - Tiempo en la Empresa (calculado automáticamente: "X años, Y meses, Z días")
  - Salario (desde employees.sueldo_individual con formato $X,XXX.XX)
- ✅ **Sección de Firmas Profesionales**:
  - 3 columnas: Autorizado por (Gerencia), Elaborado por (RRHH), Recibido por (Colaborador)
  - Líneas para firma física en PDF
  - Formato profesional en Excel
  - Espaciado optimizado para impresión
- ✅ **Mejoras SQL Queries**:
  - JOINs adicionales a posiciones, cargos, employee_terminations
  - Query optimizado para obtener toda la información en una sola consulta
- ✅ **Implementación Dual**:
  - Método exportPayrollPdf() actualizado (~300 líneas modificadas)
  - Método exportPayrollExcel() actualizado (~280 líneas modificadas)
  - Cálculo de tiempo en empresa reutilizable
- 📈 **Estadísticas**:
  - 1 archivo modificado | 2 métodos actualizados | ~250 líneas código agregadas
  - 4 campos nuevos | 1 sección nueva (firmas) | 3 JOINs SQL adicionales
  - 0 cambios en BD | Deployment: 5-10 minutos

**[📄 Ver detalles completos →](./changelog/v3.5.2.md)**

---

### **[v3.5.1]** - 2025-10-28 - *Data Cleanup y Fixes Críticos Sistema Asistencias*
**Tipo**: Hotfix / Data Cleanup
**Fase**: Mantenimiento - Correcciones Críticas Sistema Asistencias
**Criticidad**: Alta

**Componentes Principales**:
- ✅ **Fix Error "Data truncated for column 'synced_from'"**:
  - Valores incorrectos corregidos: 'API_SYNC' → 'API', 'MANUAL_PROCESSING' → 'MANUAL'
  - Archivos modificados: AttendanceSyncService.php línea 436, AttendanceController.php línea 992
  - Script SQL limpieza datos existentes
- ✅ **Normalización Campo Timestamp**:
  - Soporte para `actual_timestamp`, `registered_timestamp` además de `timestamp`
  - Mejora compatibilidad con API Base44
  - AttendanceSyncService.php líneas 268-275
- ✅ **Corrección Emails Empleados**:
  - 3 empleados actualizados para coincidir con API Base44
  - ID 2 (KATHY GONZALEZ), ID 3 (NESTOR MOLINA), ID 5 (DILSA QUINTANA)
- ✅ **Limpieza Registros Inválidos**:
  - 10 registros attendance_detail con time_in/time_out NULL eliminados
  - 179 registros raw duplicados marcados como procesados
- ✅ **Fix CSRF Dispositivos de Asistencia**:
  - Token CSRF agregado a vista index dispositivos (línea 32)
  - Validación CSRF agregada a métodos delete(), testConnection(), toggle()
  - Funciones edit, test conexión, desactivar y eliminar ahora funcionan correctamente
- ✅ **Script Deployment Producción**:
  - Migración SQL completa con backups automáticos (2025_10_28_fix_attendance_data_cleanup.sql)
  - Guía deployment paso a paso (GUIA_DEPLOYMENT_PRODUCCION.md)
  - Script verificación pre/post deployment (verify_attendance_system.sql)
  - Plan rollback incluido (5-10 minutos)
- 📈 **Estadísticas**:
  - Mejora tasa éxito sincronización: 50% → 93% (+86%)
  - Registros procesados: 30 → 209 (+597%)
  - 5 archivos modificados | 4 archivos nuevos | ~1,500 líneas documentación

**[📄 Ver detalles completos →](./changelog/v3.5.1.md)**

---

### **[v3.4.8]** - 2025-10-23 - *Procesamiento Completo Día Asistencias*
**Tipo**: Mejora - Procesamiento Batch + Reprocess + Filtros
**Fase**: Subfase 7.2 - Cálculos Avanzados de Asistencias (80%)

**Componentes Principales**:
- ✅ **Procesamiento Completo Día** (220+ líneas):
  - Pipeline 3 pasos: ausencias → omisiones → cálculos completos
  - Botón "Procesar Marcaciones" con feedback SweetAlert2
  - Detección automática empleados sin marcación → registro ABSENT
  - Detección single punch (solo entrada o salida) → estado INCOMPLETE/OMISIÓN
  - Cálculo completo métricas (horas trabajadas, extras, tardanzas)
- ✅ **Reprocesamiento con Recarga** (90 líneas):
  - Método AttendanceDetail->deleteByHeader() para limpiar detalles
  - Recarga desde tabla attendance (marcaciones originales)
  - Recreación completa + pipeline detección + cálculos
- ✅ **Filtrado Tipo Planilla SessionStorage** (50 líneas):
  - Lectura desde sessionStorage (navbar selection)
  - Validación frontend + backend con FIND_IN_SET()
  - Sin duplicación de selectores (reutiliza infraestructura existente)
- ✅ **Columna Horas Extras** (40 líneas):
  - Badge azul con total horas extras
  - Desglose +25% y +50% en tooltip
  - Formato decimal 2 decimales
- ✅ **Fixes jQuery Loading** (30 líneas):
  - Output buffering (ob_start/ob_get_clean) en sync-history y list views
  - Scripts renderizados al final de página
  - Variable baseUrl para URLs relativas
- ✅ **Fixes Errores Críticos**:
  - Undefined array key "date" en AbsenceDetector
  - Undefined array key "attendance_date" en AttendanceHeader
  - Refactor AttendanceHeader->update() para campos dinámicos
- 📈 **Estadísticas**: 7 archivos modificados | ~500 líneas código | 2 métodos nuevos | 1 ruta nueva | 4 bugs fixed

**[📄 Ver detalles completos →](./changelog/v3.4.8.md)**

---

### **[v3.4.7]** - 2025-10-20 - *Integración Completa Planillas-Asistencias*
**Tipo**: Feature - Subfase 7.4 Integración Planillas-Asistencias
**Fase**: Subfase 7.4 - Mapeo Automático Asistencias → Conceptos (100%)

**Componentes Principales**:
- ✅ **Migración BD Integración** (342 líneas SQL):
  - Tabla `payroll_attendance_summary` (38 campos) - resumen por empleado/planilla
  - Tabla `attendance_concepts_mapping` (22 campos) - configuración mapeos
  - Tabla `payroll_attendance_details` (16 campos) - detalles día por día
  - 3 vistas útiles + 10 Foreign Keys + 15 índices optimizados
- ✅ **PeriodAttendanceSummary Service** (349 líneas):
  - Generación resúmenes: horas trabajadas, overtime, tardanzas, ausencias, puntualidad
  - Integración LegalComplianceChecker para compliance
  - Cálculos monetarios: regular_pay, overtime_pay, night_pay, holiday_pay
- ✅ **AttendanceConceptMapper Service** (636 líneas):
  - 10 métodos especializados mapeo (regular, overtime25/50, night, holidays, etc.)
  - Soporte fórmulas dinámicas: {SUELDO}, {TARIFA_HORA}, {CANTIDAD}
  - Evaluación segura con MathExecutor (sin eval())
  - Mapeo configurable por tipo_planilla_id + situacion_id
- ✅ **PayrollAttendanceIntegrator Service** (415 líneas):
  - Procesamiento batch con transacciones
  - Métodos: processPayrollAttendance(), processEmployeeAttendance()
  - Persistencia completa: summary + details
  - Estadísticas: processed, summaries_created, concepts_generated, errors
- ✅ **Integración PayrollController** (+217 líneas):
  - 4 endpoints AJAX: process-attendance, attendance-summary, attendance-details, delete-attendance-data
  - Método getEmployeesByPayroll() agregado a PayrollDetail
  - Rutas configuradas en App.php
- ✅ **Script Testing Completo** (481 líneas):
  - 5 fases testing (preparación, summary, mapper, integrator, batch)
  - Color-coded output + auto-detección datos test
  - Verbose mode + success rate calculation
- ✅ **Fixes**: AttendanceDeviceController layout (render() vs view()) + sync-history navigation
- 📈 **Estadísticas**: 3 servicios nuevos | 3 tablas BD | ~2,600 líneas código | 80% módulo asistencias completado

**[📄 Ver detalles completos →](./changelog/v3.4.7.md)**

---

### **[v3.4.6]** - 2025-10-20 - *Sistema de Alertas Legales Automáticas*
**Tipo**: Feature - Subfase 7.3 Consideraciones Legales Panamá
**Fase**: Subfase 7.3 - Sistema de Alertas (100%)

**Componentes Principales**:
- ✅ **AlertsSystem** (675 líneas):
  - 14 métodos públicos gestión completa de alertas
  - Generación automática desde LegalComplianceChecker
  - Workflow: PENDING → ACKNOWLEDGED → RESOLVED/DISMISSED
  - 10+ tipos de alertas (excesos jornada, ausencias graves, tardanzas)
  - 3 niveles severidad: INFO, WARNING, CRITICAL
  - Metadata JSON flexible + referencias legales (Art. 31, 35, 38, 39, 48, 213)
- ✅ **Migración BD attendance_alerts** (342 líneas):
  - Tabla completa 20 campos + metadata JSON
  - 11 índices optimizados + 4 Foreign Keys
  - 4 vistas útiles + 2 triggers + 3 stored procedures
- ✅ **Integración AttendanceCalculator** (+180 líneas):
  - Métodos: checkLegalComplianceAndAlert(), calculateSaveAndAlert(), etc.
  - Flujo completo: calcular → guardar → verificar → alertar automático
- ✅ **Script Testing Completo** (481 líneas, 33 tests):
  - Tests componentes legales: LegalComplianceChecker, OvertimeRateCalculator, WorkingDayClassifier
  - Tests AlertsSystem: CRUD, workflow, estadísticas
  - Tests Integración completa
  - **Resultado**: 21/22 tests funcionales (95.5%)
- 📈 **Estadísticas**: 3 archivos creados | 1 modificado | ~1,678 líneas código | 1 tabla BD

**[📄 Ver detalles completos →](./changelog/v3.4.6.md)**

---

### **[v3.4.5]** - 2025-10-17 - *Integración UI Calculadores Asistencias*
**Tipo**: Mejora - Integración UI + Endpoints AJAX
**Fase**: Subfase 7.2 - Cálculos Avanzados de Asistencias (75%)

**Componentes Principales**:
- ✅ **AttendanceController Integración** (+370 líneas):
  - 7 métodos AJAX: calculateAttendance(), detectAbsences(), processCalculations(), etc.
  - Integración completa calculadores con interfaz visual
  - Validación CSRF + manejo errores robusto
- ✅ **Vista detail.php Mejorada** (+120 líneas):
  - Botón "Procesar Cálculos Día" para batch processing
  - Nueva columna "Puntualidad" con badges coloreados (verde ≥80%, amarillo 50-79%, rojo <50%)
  - Icono estrella dorada para asistencia perfecta
  - Modal detalles cálculo completo (horas, tardanzas, extras, score)
- ✅ **Vista list.php Mejorada** (+100 líneas):
  - Botón "Detectar Ausencias" con modal completo
  - Validaciones JavaScript + confirmación SweetAlert2
  - Checkbox "Guardar en BD" + estadísticas resultados
- ✅ **Vista pending-absences.php NUEVA** (410 líneas):
  - 4 estadísticas cards (total pendientes, injustificadas, por revisar, empleados afectados)
  - Filtros avanzados con Select2 (empleado, fecha inicio/fin)
  - DataTable con listado completo + ordenamiento español
  - Modal justificación con 6 tipos (MEDICAL, PERMISSION, VACATION, BEREAVEMENT, MATERNITY, OTHER)
- ✅ **Routing + Fixes Críticos**:
  - 6 rutas nuevas en App.php (calculate, detect-absences, justify, etc.)
  - Fix controller mapping: 'Attendance' → 'AttendanceController' (línea 60)
  - Fix jQuery/DataTables en sync-history/index.php
- 📈 **Estadísticas**: 4 archivos modificados | 1 vista nueva | ~850 líneas código UI | 7 endpoints AJAX | 2 fixes críticos

**[📄 Ver detalles completos →](./changelog/v3.4.5.md)**

---

### **[v3.4.4]** - 2025-10-16 - *AttendanceCalculator + AbsenceDetector con Persistencia BD*
**Tipo**: Mejora - Implementación Core Calculators
**Fase**: Subfase 7.2 - Cálculos Avanzados de Asistencias (60%)

**Componentes Principales**:
- ✅ **AttendanceCalculator Mejorado** (+280 líneas, total 708):
  - Método `saveCalculation()` - Guarda en attendance_calculations (INSERT/UPDATE automático)
  - Método `calculateAndSave()` - All-in-one (calcula + guarda + retorna con ID)
  - Método `calculateAndSaveBulk()` - Procesamiento batch con estadísticas
  - Métodos CRUD: `getCalculation()`, `deleteCalculation()`, `getConfig()`
  - Integración completa con WorkScheduleResolver, OvertimeCalculator, WorkingDayClassifier
- ✅ **AbsenceDetector Mejorado** (+385 líneas, total 693):
  - Método `saveAbsence()` - Guarda en attendance_absence_log (INSERT/UPDATE automático)
  - Método `detectAndSaveAbsences()` - Detecta y guarda con estadísticas por empleado
  - Método `detectAndSaveBulk()` - Procesamiento batch múltiples empleados
  - Workflow justificaciones: `justifyAbsence()`, `rejectJustification()`
  - Consultas: `getPendingAbsences()`, `getEmployeeAbsences()`, `getAbsenceStatistics()`
  - Estados: JUSTIFIED, UNJUSTIFIED, PENDING con resolución tracking
- ✅ **Suite Testing Completa** (370+ líneas):
  - 22 tests organizados en 6 módulos temáticos
  - 90.9% tasa de éxito (20/22 tests pasaron)
  - Módulos: Cálculos Básicos, Tardanzas, Asistencia Perfecta, Jornadas Especiales, BD, Batch
- 📈 **Estadísticas**: 3 archivos (2 modificados + 1 creado) | ~1,035 líneas código

**[📄 Ver detalles completos →](./changelog/v3.4.4.md)**

---

### **[v3.4.3]** - 2025-10-16 - *Vistas Separadas Sistema Asistencias*
**Tipo**: Mejora - Refactorización Arquitectura
**Fase**: Subfase 7.2 - Cálculos Avanzados de Asistencias (35%)

**Componentes Principales**:
- ✅ **AttendanceController Completo** (135 líneas): Controlador dedicado con 4 métodos (index, detail, sync, export)
- ✅ **3 Vistas Separadas**:
  - `list.php` (230 líneas): Listado marcaciones agrupadas por día + filtros año/mes/rango
  - `detail.php` (260 líneas): Detalle completo día específico + estadísticas + tabla empleados
  - `sync.php` (180 líneas): Panel sincronización manual (Full/Hoy/Rango)
- ✅ **Attendance Model Extendido**: 5 métodos nuevos para estadísticas
  - getAttendanceSummaryByMonth(), getAttendanceSummaryByDateRange()
  - getAttendancesByDate(), getDayStatistics(), getAvailableYears()
- ✅ **Routing Mejorado**: App.php con rutas específicas attendance (líneas 130-163)
- ✅ **Sidebar Reorganizado**: 5 opciones separadas (Marcaciones, Sincronizar, Reportes, Config API, Sistema Marcaciones)
- 📈 **Estadísticas**: 4 vistas nuevas | 1 controller | 5 métodos modelo | ~968 líneas código

**[📄 Ver detalles completos →](./changelog/v3.4.3.md)**

---

### **[v3.4.2]** - 2025-10-10 - *Checkbox Validación Situación + Análisis Reproceso Histórico*
**Tipo**: Mejora + Análisis
**Fase**: Sistema Reprocesamiento Planillas

**Componentes Principales**:
- ✅ **Checkbox Validación Situación Empleado** (COMPLETADO)
  - Checkbox condicional en modal reprocesar planilla
  - Parámetro `validate_situacion` flujo completo (Vista→JS→Controller→Model)
  - Validación condicional `validateConceptConditions()` en Payroll.php
  - Default checked + logging detallado
- 📋 **Análisis Reprocesamiento Histórico** (PROPUESTO)
  - Documento técnico `ANALISIS_REPROCESO_HISTORICO.md` (400+ líneas)
  - 5 fases planificadas: Detección + Queries Históricas + Modal + JavaScript + Testing
  - Query empleados históricos con cálculo situación por fechas
  - Query salarios históricos con validación vigencias
  - Modal 3 opciones: Histórico/Actual/Cancelar
- 📈 **Estadísticas**: 4 archivos modificados | 57 líneas código agregadas

**[📄 Ver detalles completos →](./changelog/v3.4.2.md)**

---

### **[v3.4.1]** - 2025-10-10 - *Preparación BD Cálculos Asistencias*
**Tipo**: Infraestructura Base de Datos
**Fase**: Subfase 7.2 - Cálculos Avanzados de Asistencias (25%)

**Componentes Principales**:
- 📊 Migraciones BD para cálculos de asistencias
  - Tabla `attendance_calculations` (horas, tardanzas, métricas)
  - Tabla `attendance_absence_log` (ausencias con justificaciones)
  - Tabla `employee_payroll_salaries` (salarios múltiples por tipo planilla)
- 📈 **Estadísticas**: 298 líneas SQL | 3 tablas | 14 Foreign Keys | 22 Índices

**[📄 Ver detalles completos →](./changelog/v3.4.1.md)**

---

### **[v3.4.0]** - 2025-10-09 - *Integración API Base44*
**Tipo**: Nueva Funcionalidad - Integración Externa
**Fase**: Subfase 7.1 - Integración API Asistencias Base44 (COMPLETADA)

**Componentes Principales**:
- 🔌 Base44ApiClient (367 líneas) con retry logic
- 🔄 AttendanceSyncService (510 líneas) sincronización automática
- 📡 Webhook Receiver para notificaciones tiempo real
- ⚙️ Interfaz AdminLTE configuración completa
- ⏰ Cron job sincronización cada 15 minutos
- 📈 **Estadísticas**: ~2,417 líneas código | 12 archivos nuevos | 3 tablas BD

**[📄 Ver detalles completos →](./changelog/v3.4.0.md)**

---

### **[v3.3.22]** - 2025-10-06 - *Inicialización Automática Calendario*
**Tipo**: Mejora + Bugfix
**Fase**: Calendario Empresarial Panamá

**Componentes Principales**:
- ✅ Script CLI `fill_business_calendar_2025.php`
- ✅ Método `BusinessCalendar->initializeYear($year)`
- ✅ Interfaz web con botón "Inicializar Año"
- ✅ Fix namespace Security (`App\Core\Security`)

---

### **[v3.3.21]** - 2025-10-06 - *Calendario Empresarial Panamá*
**Tipo**: Nueva Funcionalidad
**Fase**: FASE 4 Subfases 4.1-4.3 (75%)

**Componentes Principales**:
- 📅 Tabla `business_calendar` (731 registros 2024-2025)
- 📊 BusinessCalendar Model (355+ líneas, 14 métodos)
- 🖥️ Interfaz AdminLTE completa + FullCalendar.js 6.1.8
- 🔧 CRUD completo + API AJAX + DataTables

---

## 📚 **Versiones Anteriores**

Para consultar versiones anteriores (v3.3.20 y previas), consulte el archivo histórico:
**[📄 CHANGELOG_LEGACY.md →](./CHANGELOG_LEGACY.md)**

*(Próximamente: migración de versiones legacy a archivos individuales)*

---

## 📁 **Estructura de Archivos**

```
documentation/
├── CHANGELOG.md                    # Este archivo (índice principal)
├── CHANGELOG_LEGACY.md             # Versiones 3.3.20 y anteriores
└── changelog/                      # Directorio de versiones individuales
    ├── v3.4.1.md                  # Migraciones BD Cálculos (10-Oct-2025)
    ├── v3.4.0.md                  # Integración API Base44 (9-Oct-2025)
    └── [versiones futuras...]
```

---

## 🔍 **Cómo Usar Este Índice**

1. **Ver Últimas Versiones**: Las versiones más recientes están listadas arriba con resumen ejecutivo
2. **Detalles Completos**: Click en el enlace "Ver detalles completos →" para abrir el archivo específico de la versión
3. **Versiones Legacy**: Versiones anteriores a v3.4.0 están en `CHANGELOG_LEGACY.md`
4. **Búsqueda Rápida**: Usa Ctrl+F para buscar por número de versión, fecha o componente

---

## 📊 **Convenciones**

### **Tipos de Versiones**:
- **Major** (vX.0.0): Cambios arquitectónicos significativos
- **Minor** (v3.X.0): Nuevas funcionalidades o módulos completos
- **Patch** (v3.4.X): Bugfixes, mejoras menores, migraciones BD

### **Tipos de Releases**:
- 🚀 **Nueva Funcionalidad**: Nuevos módulos o características importantes
- 🔧 **Mejora**: Optimizaciones o ampliaciones de funcionalidad existente
- 🐛 **Bugfix**: Corrección de errores
- 📊 **Infraestructura**: Migraciones BD, configuración, estructura
- 🔒 **Seguridad**: Parches de seguridad y validaciones

### **Fases del Proyecto**:
- **FASE 1-3**: Core System completado
- **FASE 4**: Calendario Empresarial (completado)
- **FASE 5**: Módulo Vacaciones (pendiente)
- **FASE 6**: Multitenancy (pendiente)
- **FASE 7**: Integración API Asistencias (en progreso 25%)
- **FASE 8-9**: Reportería + Integraciones (pendiente)

---

## 📝 **Guía para Nuevas Versiones**

Al crear una nueva versión:

1. **Crear archivo individual**: `documentation/changelog/vX.Y.Z.md`
2. **Usar template**: Copiar estructura de `v3.4.1.md` o `v3.4.0.md`
3. **Actualizar este índice**: Agregar entrada en sección "Últimas Versiones"
4. **Mantener orden**: Versiones más recientes primero
5. **Incluir estadísticas**: Líneas de código, archivos, tablas BD
6. **Referencias cruzadas**: Enlazar versiones relacionadas

---

**Última Actualización**: 3 de Noviembre, 2025
**Sistema**: Planillas MVC v3.5.5
**Progreso Global**: Core 100% | Calendario 100% | API Asistencias 90% | Liquidaciones 100% | Seguridad 100%
