# ðŸ¤– CLAUDE MEMORY - Sistema de Planillas MVC

## 📍 **Estado Actual - V3.5.10 License Info UI + Wizard Debugging**
- **Fecha**: 25 de Noviembre, 2025
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + VACACIONES PANAMÁ 45% + CALENDARIO API SYNC + API ASISTENCIAS 92% + ALERTAS LEGALES + INTEGRACIÓN PLANILLAS + PROCESAMIENTO BATCH 95% + LIQUIDACIONES PROFESIONALES 100% + SEGURIDAD REFORZADA 100% + REPORTES ASISTENCIAS 35% + MULTITENANCY 35% + EMPLOYEE IMPORT 100% + LICENSE UI 100%**
- **Versión**: 3.5.10 - Dropdown información licencia en navbar + Sistema debugging completo WizardController
- **Versión Anterior**: 3.5.9 - Sistema importación Excel completo + Wizard UI simétrico

## ðŸŽ¯ **Sistema**
Plataforma empresarial de planillas con legislaciÃ³n panameÃ±a, acumulados automÃ¡ticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## âœ… **Componentes Principales Implementados**
- âœ… **Core Sistema**: MVC + Router + Database + CSRF + Roles + Middleware
- âœ… **Planillas & Liquidaciones**: Procesamiento completo + PDF + Acumulados automÃ¡ticos + LegislaciÃ³n panameÃ±a
- âœ… **XIII Mes PanamÃ¡**: CÃ¡lculo trimestral (Salario Anual Ã· 3) + perÃ­odos automÃ¡ticos + variables dinÃ¡micas
- âœ… **Reportes PDF**: Layout empresarial + logos + firmas + comprobantes individuales
- âœ… **MÃ³dulos**: Organizacional + Logos + Employee Import + Calendario Empresarial PanamÃ¡
- âœ… **Motor FÃ³rmulas V3.5.3**: INIPERIODO/FINPERIODO + ACUMULADOS() + CONCEPTO() + arquitectura herencia segura + 100% sin eval() + nxp/math-executor
- âœ… **Custom Query Builder**: Interfaz fluente + adaptadores multi-BD + 24% mejora rendimiento
- âœ… **UI/UX**: AdminLTE nativo + AJAX DataTables + Responsive 1024px + Cache-busting + Modal refresh
- âœ… **Dashboard Ejecutivo**: Filtros tipo planilla + mÃ©tricas en tiempo real + grÃ¡ficas asistencia
- âœ… **MÃ³dulo Acumulados**: Vistas byEmployee/byConcepto/byType + cards agrupados + filtros avanzados
- âœ… **MÃºltiples Tipos Planilla**: Empleados en varios tipos + FIND_IN_SET() queries + Select2 mÃºltiple
- âœ… **Calendario Empresarial**: BusinessCalendar model + feriados PanamÃ¡ 2024-2025 + FullCalendar.js
- âœ… **API Asistencias Base44 V3.4.0**: Cliente API + sincronizaciÃ³n automÃ¡tica + webhook + 3 tablas BD
- âœ… **Sistema Asistencias V3.4.1-3.5.5**: Migraciones BD + Vistas separadas + Calculadores Core + UI integraciÃ³n + AlertsSystem + PayrollAttendanceIntegrator + Mapeo automÃ¡tico + Procesamiento batch dÃ­a + Reprocess + Almuerzo (90% Subfases 7.1-7.4 completadas | 85% Subfase 7.2 | 35% Subfase 7.5)

### ðŸ†• V3.5.5 - Almuerzo en Asistencias + Proceso Unificado
- DB: `schedules` aÃ±ade `salida_almuerzo`/`entrada_almuerzo`; `attendance_detail` aÃ±ade campos de almuerzo + Ã­ndices
- Trigger: `trg_calculate_lunch_duration` para duraciÃ³n/exceso de almuerzo
- Processor: clasifica 4 marcaciones cuando el horario lo define; estado requiere 4 si aplica
- Calculator: descuenta almuerzo de horas trabajadas y aÃ±ade mÃ©tricas (`lunch_duration_minutes`, `lunch_exceeded_minutes`)
- UI: nuevas columnas y campos en el modal de ediciÃ³n en detalle de asistencias
- Process Day: consolida `attendance_records` â†’ `attendance_detail` (marca records como procesados) y respeta `employees.marca_asistencia = 1`
- Modal: checklist de opciones (procesar registros, detectar ausencias, marcar omisiones, recalcular mÃ©tricas)
- Ausencias: guardan `schedule_id`; sÃ³lo se detectan para empleados que marcan
- Fix infra: `AttendanceHeader::getById()` para soporte en reconstrucciÃ³n de DATETIME (almuerzos) durante actualizaciÃ³n
- âœ… **Hotfix V3.5.1**: Fix crÃ­tico synced_from ENUM + data cleanup + normalizaciÃ³n timestamp API Base44 + CSRF dispositivos + deployment scripts

## ðŸ“„ **Sistemas Auxiliares Implementados**

### **XIII Mes Trimestral** (V3.3.9)
XIIIMesPeriodoTrimestralCalculator con 3 perÃ­odos automÃ¡ticos (P1-P3), variables dinÃ¡micas (INICIO/FIN_PERIODO_XIII), fÃ³rmula legislaciÃ³n panameÃ±a.

### **Employee Import** (V3.3.10)
ImportaciÃ³n masiva Excel con validaciones robustas, PHP 8+ compatible, foreign key handling, error messages mejorados.

### **Planillas de LiquidaciÃ³n**
GeneraciÃ³n automÃ¡tica perÃ­odo 11 meses segÃºn CÃ³digo Trabajo PanamÃ¡, cÃ¡lculos legislaciÃ³n completos, vistas separadas.

## ðŸ”„ **Sistema Reprocesamiento Planillas** (V3.4.2)

**Checkbox ValidaciÃ³n SituaciÃ³n**: Permite controlar validaciÃ³n situaciÃ³n empleado durante reproceso. Flujo Vistaâ†’JSâ†’Controllerâ†’Model con parÃ¡metro `validate_situacion` opcional (default: true). Archivos: index.php + index.js + PayrollController + Payroll.php. Retrocompatible.

**Reproceso HistÃ³rico Propuesto**: AnÃ¡lisis completo en `documentation/ANALISIS_REPROCESO_HISTORICO.md` (400+ lÃ­neas). Sistema para reprocesar con empleados/salarios histÃ³ricos vigentes en fecha original. 5 fases propuestas: detecciÃ³n ausencia empleados + queries histÃ³ricos + modal confirmaciÃ³n. Estado: pendiente aprobaciÃ³n.

## ðŸ“Š **CaracterÃ­sticas UI/UX Destacadas**

**Sidebar AdminLTE** (V3.3.16): RefactorizaciÃ³n completa estructura multilevel nativa. getCurrentRoute() mejorado, eliminado JavaScript interferente, soporte subdirectorios. Archivos: sidebar.php + admin.php.

**MÃ³dulo Acumulados** (V3.3.15 + V3.3.17): 3 vistas refactorizadas (byEmployee/byConcepto/byType) con cards agrupados AdminLTE, filtros avanzados (Select2), 3 modos agrupaciÃ³n, DataTables espaÃ±ol, integraciÃ³n sessionStorage.

**Dashboard Ejecutivo** (V3.3.11): Filtrado completo por tipo planilla con sessionStorage, evento `payrollTypeChanged`, mÃ©tricas en tiempo real, grÃ¡ficas asistencia, tabs alineados.

**Sistema Empleados**: Vista activos (/panel/employees) + vista terminados (/terminated), JavaScript modular, AJAX DataTables server-side, callouts AdminLTE, badges visuales.

**FunciÃ³n CONCEPTO()**: Sintaxis flexible, reutilizaciÃ³n cÃ¡lculos entre conceptos, protecciÃ³n recursiÃ³n. Ejemplo: `CONCEPTO("LIQ005") * 0.0975`.

**UX/UI**: Iconos FontAwesome estados planillas, responsive 1024px, dÃ­as preaviso editables AJAX, cache-busting SSIIHH, modal refresh inteligente, SweetAlert2.

## â° **MÃ“DULO API MARCACIONES Y ASISTENCIAS**

**Objetivo**: IntegraciÃ³n API marcaciones + control automatizado asistencias + generaciÃ³n conceptos planillas segÃºn legislaciÃ³n panameÃ±a.

**Estado Actual**: âœ… Subfase 7.4 COMPLETADA - 88% (4 de 5 subfases completadas | Subfase 7.2 mejorada al 80% | Subfase 7.5 iniciada al 30%)

### **Subfase 7.1: API Externa** âœ… (9-Oct-2025)
- **Base44ApiClient** (367 lÃ­neas): cURL + retry logic + timeout 30s
- **AttendanceSyncService** (510 lÃ­neas): Sync completa/incremental + duplicados
- **Cron Job**: SincronizaciÃ³n automÃ¡tica cada 15 min
- **Webhook**: /webhooks/base44/attendance + validaciÃ³n HMAC
- **3 Tablas BD**: api_config + raw_data + sync_log
- **EstadÃ­sticas**: ~2,417 lÃ­neas | 12 archivos nuevos

### **Subfase 7.2: CÃ¡lculos Avanzados** ðŸ”µ 80% (23-Oct-2025)

**Migraciones BD** (10-Oct-2025): 3 tablas (attendance_calculations, attendance_absence_log, employee_payroll_salaries) | 298 lÃ­neas SQL | 14 FKs | 22 Ã­ndices

**Vistas Separadas** (16-Oct-2025): AttendanceController (135 lÃ­neas) + 3 vistas (list/detail/sync) + Attendance Model 5 mÃ©todos | ~968 lÃ­neas cÃ³digo

**AttendanceCalculator** (16-Oct-2025): 708 lÃ­neas totales. 9 mÃ©todos pÃºblicos (saveCalculation, calculateAndSave, calculateAndSaveBulk, getCalculation, deleteCalculation, getConfig). Persistencia BD completa. IntegraciÃ³n WorkScheduleResolver + OvertimeCalculator + WorkingDayClassifier. Testing: 22 tests (90.9% Ã©xito).

**AbsenceDetector** (16-Oct-2025): 693 lÃ­neas totales. 11 mÃ©todos pÃºblicos (saveAbsence, detectAndSaveAbsences, detectAndSaveBulk, justifyAbsence, rejectJustification, getPendingAbsences, getEmployeeAbsences). Workflow PENDING â†’ JUSTIFIED/UNJUSTIFIED. IntegraciÃ³n BusinessCalendar.

**UI IntegraciÃ³n** (17-Oct-2025): 7 mÃ©todos AJAX AttendanceController + 6 rutas nuevas App.php + Vista detail.php badges puntualidad (verdeâ‰¥80% / amarillo50-79% / rojo<50%) + Vista list.php modal detectar ausencias + Vista pending-absences.php (410 lÃ­neas) 4 cards estadÃ­sticas + 2 fixes crÃ­ticos | ~850 lÃ­neas UI

**Procesamiento Completo DÃ­a + Reprocess** (23-Oct-2025): MÃ©todo processDay() (220 lÃ­neas) con pipeline 3 pasos (ausencias â†’ omisiones â†’ cÃ¡lculos) + DetecciÃ³n automÃ¡tica empleados sin marcaciÃ³n â†’ ABSENT + Single punch â†’ INCOMPLETE + Filtrado tipo planilla sessionStorage + Reprocesamiento: deleteByHeader() + recarga desde tabla attendance + Columna Horas Extras +25%/+50% + 4 fixes crÃ­ticos (jQuery loading + dynamic updates + undefined keys) | ~500 lÃ­neas cÃ³digo

**CÃ¡lculos Implementados**: Marcaciones perfectas âœ… | Horas trabajadas âœ… | Ausencias âœ… | Justificaciones âœ… | Tardanzas âœ… | Horas extras 25%/50% âœ… | Salidas anticipadas âœ… | Almuerzo âœ… | Score puntualidad 0-100 âœ… | Horas nocturnas âœ… | Horas feriados âœ…

### **Subfase 7.3: Consideraciones Legales PanamÃ¡** âœ… (20-Oct-2025)

**LegalComplianceChecker** (604 lÃ­neas): ValidaciÃ³n cumplimiento normativa laboral panameÃ±a. MÃ©todos: validateDailyHours(), validateWeeklyHours(), validateLunchBreak(), validateNightHours(), performFullCompliance(). Niveles riesgo: NINGUNO, BAJO, MEDIO, ALTO, CRÃTICO.

**OvertimeRateCalculator** (408 lÃ­neas): CÃ¡lculo tarifas segÃºn tipo hora extra. Recargos: +25% primeras 3h, +50% adicionales, +50% nocturnas, +100% feriados. MÃ©todos: calculateHourlyRate(), calculateOvertimePay(), calculateOvertimeRates().

**WorkingDayClassifier** (472 lÃ­neas): ClasificaciÃ³n dÃ­as ordinarios/festivos/descanso. Tipos: LABORAL, FERIADO, DUELO_NACIONAL, FIN_SEMANA, ESPECIAL. IntegraciÃ³n completa BusinessCalendar. MÃ©todos: classifyDate(), getWorkingDaysBetween(), getDayClassificationForPeriod().

**AlertsSystem** (675 lÃ­neas): Sistema completo alertas automÃ¡ticas. 14 mÃ©todos pÃºblicos. Workflow: PENDING â†’ ACKNOWLEDGED â†’ RESOLVED/DISMISSED. 10+ tipos alertas (exceso jornada, ausencias graves, tardanzas). 3 niveles severidad (INFO, WARNING, CRITICAL). PrevenciÃ³n duplicados + metadata JSON flexible + referencias legales (Art. 31, 35, 38, 39, 48, 213).

**MigraciÃ³n BD** (342 lÃ­neas): Tabla attendance_alerts (20 campos + 11 Ã­ndices + 4 Foreign Keys). 4 vistas Ãºtiles: v_active_alerts, v_critical_alerts, v_employee_alert_stats, v_alerts_by_type. 2 triggers + 3 stored procedures.

**IntegraciÃ³n AttendanceCalculator** (+180 lÃ­neas): 4 mÃ©todos nuevos: checkLegalComplianceAndAlert(), calculateSaveAndAlert(), calculateSaveAndAlertBulk(), getEmployeeAlerts(). Flujo automÃ¡tico: calcular â†’ guardar â†’ verificar â†’ alertar.

**Testing Suite** (481 lÃ­neas, 33 tests): 95.5% Ã©xito (21/22 tests). Cobertura: LegalComplianceChecker (87.5%), OvertimeRateCalculator (100%), WorkingDayClassifier (100%), AlertsSystem, IntegraciÃ³n Completa.

### **Subfase 7.4: IntegraciÃ³n Planillas-Asistencias** âœ… (20-Oct-2025)

**MigraciÃ³n BD** (342 lÃ­neas): 3 tablas nuevas (payroll_attendance_summary, attendance_concepts_mapping, payroll_attendance_details). 10 Foreign Keys + 15 Ã­ndices optimizados. 3 vistas Ãºtiles. 10 tipos mapeo soportados (regular hours, overtime 25/50%, night, holidays, Sunday, tardiness/absence deductions, bonuses).

**PeriodAttendanceSummary** (349 lÃ­neas): GeneraciÃ³n resÃºmenes consolidados por perÃ­odo. MÃ©todos: generateSummary(), aggregateMetrics(), checkLegalCompliance(). IntegraciÃ³n LegalComplianceChecker. Resumen incluye: horas trabajadas, overtime, tardanzas, ausencias, puntualidad, cÃ¡lculos monetarios, compliance legal.

**AttendanceConceptMapper** (636 lÃ­neas): Mapeo asistencias â†’ conceptos planilla. 10 mÃ©todos especializados (regular, overtime25, overtime50, night, holidays, Sunday, tardiness, absences, bonuses). Soporte fÃ³rmulas dinÃ¡micas: {SUELDO}, {TARIFA_HORA}, {CANTIDAD}. EvaluaciÃ³n segura MathExecutor (sin eval()). Mapeo configurable por tipo_planilla_id + situacion_id.

**PayrollAttendanceIntegrator** (415 lÃ­neas): Coordinador principal integraciÃ³n. MÃ©todos: processPayrollAttendance() (batch), processEmployeeAttendance() (individual), saveSummary(), saveDetails(), getPayrollAttendanceSummary(), getEmployeeAttendanceDetails(), deletePayrollAttendanceData(). Transacciones BD. EstadÃ­sticas completas: processed, summaries_created, concepts_generated, errors.

**IntegraciÃ³n PayrollController** (+217 lÃ­neas): 4 endpoints AJAX nuevos: /process-attendance, /attendance-summary, /attendance-details/{employeeId}, /delete-attendance-data. MÃ©todo getEmployeesByPayroll() agregado a PayrollDetail. Rutas configuradas en App.php.

**Script Testing** (481 lÃ­neas): 5 fases (preparaciÃ³n, summary, mapper, integrator, batch). Color-coded output. Auto-detecciÃ³n datos test. Verbose mode + success rate.

**Fixes**: AttendanceDeviceController layout (render() vs view() en 3 mÃ©todos). Sync-history view (removido botÃ³n navegaciÃ³n a /sync).

### **LegislaciÃ³n PanamÃ¡ Aplicada**
Jornada ordinaria 8h/48h semanales (Art.31) | Jornada nocturna 6PM-6AM +50% (Art.38) | Horas extras +25%/+50% (Art.39) | Domingos/feriados +50% (Art.48) | Almuerzo 30min mÃ­nimo (Art.35) | 3+ ausencias injustificadas/mes = despido (Art.213)

### **Subfase 7.5: Interfaz y Reportes** ðŸ”µ 30% (01-Nov-2025)

**Reporte de Marcaciones** âœ… (01-Nov-2025): ReportsGenerator->generateDetailedPunchesReport() (145 lÃ­neas) + ExcelExporter->exportPunchesReport() (188 lÃ­neas) + AttendanceController->punchesReport() (75 lÃ­neas). Ruta /panel/attendance/reports/punches. 8 estadÃ­sticas resumen + top 10 tardanzas + detalle por departamento (10 columnas). Formatos: Excel + Vista Web + JSON.

**Pendiente Implementar**:
- [ ] Vista empleados: consulta asistencias propias en tiempo real
- [ ] Vista gerencial: dashboard asistencias por departamento
- [ ] Reportes ejecutivos: ausentismo, horas extras por perÃ­odo
- [ ] Alertas automÃ¡ticas: ausencias injustificadas, excesos jornada
- [ ] ExportaciÃ³n PDF: reportes asistencias con logos empresariales
- [ ] Dashboards visuales: grÃ¡ficos asistencia por perÃ­odo
- [ ] Notificaciones: alertas automÃ¡ticas supervisores/RRHH

### **VersiÃ³n V3.5.4: Reportes Asistencias + Mejoras UI** âœ… (01-Nov-2025)

**Componentes Implementados**:
1. **Reporte Marcaciones Completo** (408 lÃ­neas):
   - ReportsGenerator->generateDetailedPunchesReport(): SQL optimizado + 8 estadÃ­sticas + top 10 tardanzas
   - ExcelExporter->exportPunchesReport(): Formato profesional Excel con agrupaciÃ³n por departamento
   - AttendanceController->punchesReport(): Endpoint formatos view/json/excel
   - Ruta /panel/attendance/reports/punches configurada en App.php
2. **Fix Label Liquidaciones** (LiquidationController.php):
   - "PosiciÃ³n:" â†’ "Cargo:" en PDF y Excel (2 mÃ©todos)
3. **Mejoras Comprobantes Horizontales** (ReportController.php):
   - EliminaciÃ³n headers/footers TCPDF automÃ¡ticos
   - Colores profesionales liquidaciones (naranja intenso RGB 255,140,0 + azul profundo RGB 25,118,210)
   - SecciÃ³n firmas 3 columnas (Elaborado/Autorizado/Recibido por Colaborador)

**Archivos Modificados**: App.php, AttendanceController.php, ReportsGenerator.php, ExcelExporter.php, LiquidationController.php, ReportController.php

**EstadÃ­sticas**: 6 archivos | ~620 lÃ­neas cÃ³digo agregadas | 0 cambios BD | Deployment: 10-15 min

### **VersiÃ³n V3.5.2: Mejora Reportes Liquidaciones** âœ… (30-Oct-2025)

**Mejoras Implementadas**:
1. **Campos Adicionales Reportes PDF/Excel** (LiquidationController.php):
   - Fecha Fin de Contrato (desde employee_terminations.termination_date)
   - PosiciÃ³n (desde tabla cargos vÃ­a posiciones)
   - Tiempo en Empresa (calculado automÃ¡ticamente: "X aÃ±os, Y meses, Z dÃ­as")
   - Salario (desde employees.sueldo_individual con formato $X,XXX.XX)
2. **SecciÃ³n Firmas Profesionales**:
   - 3 columnas: Autorizado por (Gerencia) | Elaborado por (RRHH) | Recibido por (Colaborador)
   - LÃ­neas para firma fÃ­sica en PDF con espaciado optimizado
   - Formato profesional en Excel con alineaciÃ³n centrada
3. **Mejoras SQL Queries**:
   - JOINs adicionales: posiciones, cargos, employee_terminations
   - Query optimizado para obtener informaciÃ³n completa en una consulta
4. **CÃ¡lculo Inteligente Tiempo Empresa**:
   - Usa DateTime::diff() para precisiÃ³n
   - Formato humanizado ("X aÃ±os, Y meses, Z dÃ­as")
   - Maneja casos especiales (0 dÃ­as, menos de 1 mes, etc.)

**Archivos Modificados**:
- LiquidationController.php: exportPayrollPdf() (~300 lÃ­neas), exportPayrollExcel() (~280 lÃ­neas)

**EstadÃ­sticas**: 1 archivo | 2 mÃ©todos | ~250 lÃ­neas agregadas | 4 campos nuevos | 1 secciÃ³n firmas | 3 JOINs SQL | 0 cambios BD

### **RefactorizaciÃ³n V3.5.3: EliminaciÃ³n eval() + Arquitectura Segura** âœ… (01-Nov-2025)

**Cambios CrÃ­ticos de Seguridad**:
1. **EliminaciÃ³n Total eval()** (PlanillaConceptCalculator.php):
   - 862 lÃ­neas de cÃ³digo corrupto/duplicado eliminadas (lÃ­neas 166-1030)
   - Archivo reducido de 2058 a 1196 lÃ­neas
   - Arquitectura de herencia: `class PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure`
   - 100% evaluaciÃ³n fÃ³rmulas mediante NXP\MathExecutor (sin eval())
2. **RefactorizaciÃ³n Visibilidad** (PlanillaConceptCalculatorSecure.php):
   - 9 propiedades `private`â†’`protected` (incluye $db, $executor, $conceptos, etc.)
   - 18 mÃ©todos `private`â†’`protected` para permitir herencia completa
   - Fix crÃ­tico acceso $db que causaba error null pointer
3. **ValidaciÃ³n Variables Extendida** (lÃ­neas 54-70):
   - Agregadas variables string permitidas: EMPLEADO, CLAVE_SS, CLAVE_SEGURO_SOCIAL
   - Mejora en configurarValidacionesEstritas() para identificadores de empleados
4. **Testing Runtime Completo**:
   - 3 errores resueltos durante testing: null $db, private methods, variable validation
   - ValidaciÃ³n con planillas reales (ID 85, tipo planilla 2, empleado 1)

**Arquitectura Final**:
```
PlanillaConceptCalculatorSecure (Padre)
â”œâ”€â”€ MathExecutor $executor (seguro, sin eval())
â”œâ”€â”€ EvaluaciÃ³n segura de fÃ³rmulas
â”œâ”€â”€ Funciones personalizadas (ACUMULADOS, CONCEPTO, etc.)
â””â”€â”€ Validaciones estrictas de variables

    â†“ extends

PlanillaConceptCalculator (Hijo)
â”œâ”€â”€ XIIIMesPeriodoTrimestralCalculator
â”œâ”€â”€ Override setVariablesColaborador() (mÃºltiples tipos planilla)
â”œâ”€â”€ Funciones liquidaciones (LIQUIDACION_INDEMNIZACION, etc.)
â”œâ”€â”€ Funciones vacaciones (VACATION_DAYS_EARNED, etc.)
â””â”€â”€ Funciones XIII mes trimestral
```

**Archivos Modificados**:
- PlanillaConceptCalculator.php: Eliminadas 862 lÃ­neas, estructura limpia 1196 lÃ­neas
- PlanillaConceptCalculatorSecure.php: 9 propiedades + 18 mÃ©todos refactorizados

**EstadÃ­sticas**: 2 archivos | -1085 lÃ­neas inseguras | +321 lÃ­neas seguras | 0 eval() restante | 0 cambios BD

**Mejoras de Seguridad**:
- âœ… EliminaciÃ³n de vectores de inyecciÃ³n de cÃ³digo
- âœ… Sandbox aislado para evaluaciÃ³n de fÃ³rmulas (MathExecutor)
- âœ… ValidaciÃ³n estricta de variables antes de evaluaciÃ³n
- âœ… CÃ³digo mÃ¡s limpio y mantenible
- âœ… Mejor separaciÃ³n de responsabilidades

### **Hotfix V3.5.1: Data Cleanup & Fixes CrÃ­ticos** âœ… (28-Oct-2025)

**Problema CrÃ­tico**: Error "Data truncated for column 'synced_from'" bloqueaba sincronizaciÃ³n asistencias.

**Correcciones Aplicadas**:
1. **Fix synced_from ENUM** (AttendanceSyncService.php lÃ­nea 436, AttendanceController.php lÃ­nea 992):
   - 'API_SYNC' â†’ 'API'
   - 'MANUAL_PROCESSING' â†’ 'MANUAL'
2. **NormalizaciÃ³n Timestamp** (AttendanceSyncService.php lÃ­neas 268-275):
   - Soporte `actual_timestamp`, `registered_timestamp` ademÃ¡s de `timestamp`
   - Mejora compatibilidad API Base44
3. **CorrecciÃ³n Emails Empleados**:
   - 3 empleados actualizados (ID 2, 3, 5) para coincidir con API Base44
4. **Limpieza Datos**:
   - 10 registros attendance_detail con NULL/NULL eliminados
   - 179 registros raw duplicados marcados como procesados
5. **Fix CSRF Dispositivos** (AttendanceDeviceController.php):
   - Token CSRF agregado a vista index (lÃ­nea 32)
   - ValidaciÃ³n CSRF en delete/testConnection/toggle (lÃ­neas 172, 213, 287)

**Scripts Deployment**:
- MigraciÃ³n SQL: `2025_10_28_fix_attendance_data_cleanup.sql`
- GuÃ­a deployment: `GUIA_DEPLOYMENT_PRODUCCION.md` (24-32 min estimado)
- Script verificaciÃ³n: `verify_attendance_system.sql`

**Resultados**:
- Tasa Ã©xito sincronizaciÃ³n: **50% â†’ 93%** (+86%)
- Registros procesados: **30 â†’ 209** (+597%)
- Todas funciones dispositivos (edit, delete, toggle, test) **ahora funcionan correctamente**

## ðŸ“… **Calendario Empresarial PanamÃ¡** âœ… (V3.3.21-22)

**BD**: Tabla business_calendar con 731 registros (2024-2025), 28 feriados nacionales. Tipos: LABORAL/NO_LABORAL/FERIADO/DUELO/ESPECIAL.

**Model**: BusinessCalendar.php (355+ lÃ­neas). MÃ©todos: getWorkingDaysBetween(), isWorkingDay(), getNextWorkingDay(), initializeYear(), getMonthCalendar(), getHolidaysByYear().

**UI**: BusinessCalendarController CRUD + 2 vistas (index + calendar) + FullCalendar.js 6.1.8 + API AJAX getWorkingDays() + Script CLI inicializaciÃ³n automÃ¡tica aÃ±os.

**Feriados Pagados** âœ… (13-Nov-2025): Campo `is_paid_holiday` en business_calendar + Modal ediciÃ³n checkbox "Feriado Pagado" + IntegraciÃ³n CalendarSyncService con API Base44 (campo `paid`) + GeneraciÃ³n automÃ¡tica 8 horas trabajadas en processDay() (AttendanceController.php:1400-1488). Fix DataTables sync-history cuando no hay registros.
- **Flujo Manual Actual**: Ejecutar `processDay()` desde UI para fechas especÃ­ficas con feriado pagado
- **Pendiente AutomatizaciÃ³n**: Cron job diario / Trigger CalendarSyncService / Procesamiento anticipado (Ver TODO.md)

**Nota**: Subfase 4.4 (IntegraciÃ³n CÃ¡lculos Legales) CANCELADA - se implementarÃ¡ en mÃ³dulo asistencias.

## ðŸ”‘ **PrÃ³ximas Fases**
1. **â° ASISTENCIAS**: Subfase 7.5 (Interfaz y Reportes - dashboard, vistas empleados, exportaciÃ³n)
2. **ðŸ–ï¸ VACACIONES PANAMÃ**: VacationCalculator + CRUD + Aprobaciones + IntegraciÃ³n
3. **ðŸ¢ MULTITENANCY**: Wizard empresas + BD automÃ¡tica

## ðŸ”§ **Stack TecnolÃ³gico**
**Backend**: PHP 8.3 + MVC + MySQL | **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 | **Reportes**: TCPDF | **Estado**: ProducciÃ³n estable

## ðŸ§® **Motor FÃ³rmulas & Query Builder**

**Motor FÃ³rmulas V3.2.1**: INIPERIODO/FINPERIODO dinÃ¡mico + ACUMULADOS() + CONCEPTO() + tipo_acumulado + regex optimizado + nxp/math-executor (seguridad). Fix frecuencia ENUMâ†’INT.

**Custom Query Builder V3.2.2**: Interfaz fluente + CRUD optimizado + adaptadores MySQL/PostgreSQL + 24% mejora rendimiento + 82% reducciÃ³n cÃ³digo SQL + escalabilidad 5-1000+ empleados.

### **â° Funciones de Asistencias V3.5.3** âœ… (31-Oct-2025)

**16 funciones** integradas al motor de fÃ³rmulas para consultar datos del mÃ³dulo de marcaciones. Retornan 0 si no hay datos (opcional). Consultan `payroll_attendance_summary` automÃ¡ticamente.

**Funciones Horas (Asignaciones)**:
- `HORAS_TRABAJADAS()` - Total horas trabajadas del perÃ­odo
- `HORAS_REGULARES()` - Horas regulares sin extras
- `HORAS_EXTRAS()` - Total horas extras (25% + 50%)
- `HORAS_EXTRAS_25()` - Horas extras al 25% (primeras 3h)
- `HORAS_EXTRAS_50()` - Horas extras al 50% (adicionales)
- `HORAS_NOCTURNAS()` - Horas nocturnas 6PM-6AM
- `HORAS_FERIADOS()` - Horas trabajadas en feriados
- `HORAS_DOMINICALES()` - Horas trabajadas en domingos

**Funciones Ausencias/Tardanzas (Deducciones)**:
- `TARDANZAS()` - Minutos de tardanza total
- `CANTIDAD_TARDANZAS()` - NÃºmero de tardanzas
- `AUSENCIAS()` - DÃ­as ausencias injustificadas
- `TOTAL_AUSENCIAS()` - Total ausencias (justificadas + injustificadas)
- `AUSENCIAS_JUSTIFICADAS()` - DÃ­as ausencias justificadas

**Funciones EstadÃ­sticas**:
- `SCORE_PUNTUALIDAD()` - Score 0-100 de puntualidad
- `DIAS_ASISTENCIA_PERFECTA()` - DÃ­as con asistencia perfecta
- `DIAS_TRABAJADOS()` - Total dÃ­as trabajados

**Ejemplos de Uso en Conceptos**:
```php
// Concepto: Horas Extras 25%
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25

// Concepto: Descuento Tardanzas
TARDANZAS() / 60 * (SUELDO / 220)

// Concepto: Descuento Ausencias
AUSENCIAS() * (SUELDO / 30)

// Concepto: Bono Puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)

// Concepto: Horas Nocturnas con recargo
HORAS_NOCTURNAS() * (SUELDO / 220) * 1.5
```

**ImplementaciÃ³n** (PlanillaConceptCalculatorSecure.php:79-211):
- MÃ©todo helper `obtenerDatoAsistencia()` con cache automÃ¡tico
- Query optimizado con tolerancia de fechas
- Limpieza cache automÃ¡tica al cambiar empleado
- Sin eval(), 100% seguro con MathExecutor

**Ventajas**:
- âœ… Opcional - No requiere mÃ³dulo marcaciones activo
- âœ… Depurable - FÃ¡cil testing de fÃ³rmulas
- âœ… Flexible - Conceptos configurables desde UI
- âœ… Consistente - Integrado con ACUMULADOS(), CONCEPTO()

**ðŸ›¡ï¸ SEGURIDAD CRÃTICA**:
- ðŸš¨ NUNCA eliminar librerÃ­a nxp/math-executor
- âš ï¸ PROHIBIDO eval() - usar MathExecutor exclusivamente
- ðŸ”’ ValidaciÃ³n obligatoria fÃ³rmulas + preservar multilÃ­nea/ACUMULADOS/fechas dinÃ¡micas

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.

## ðŸš¨ **FLUJO OBLIGATORIO PARA ANÃLISIS**
**MANDATORY ANALYSIS WORKFLOW - NO EXCEPTIONS**

Cuando el usuario solicite cualquier tipo de anÃ¡lisis (usando palabras como "analiza", "analyze", "evalÃºa", "estudia", etc.):

1. **ANÃLISIS**: Realizar investigaciÃ³n y anÃ¡lisis completo
2. **PRESENTACIÃ“N**: Presentar opciones, pros/contras, recomendaciones
3. **ESPERAR APROBACIÃ“N**: NO proceder hasta recibir confirmaciÃ³n explÃ­cita del usuario
4. **IMPLEMENTACIÃ“N**: Solo si se solicita especÃ­ficamente

**PROHIBIDO**: Implementar automÃ¡ticamente despuÃ©s de anÃ¡lisis sin aprobaciÃ³n explÃ­cita.
**OBLIGATORIO**: Siempre preguntar "Â¿Proceder con la implementaciÃ³n de [opciÃ³n recomendada]?" antes de cualquier implementaciÃ³n.

## ðŸ“ **Estructura de DocumentaciÃ³n**
- **CLAUDE.md**: Memoria principal del proyecto (raÃ­z)
- **documentation/**: Directorio para archivos de documentaciÃ³n del proyecto
  - **ROADMAP.md**: Hoja de ruta y planificaciÃ³n
  - **CHANGELOG.md**: Ãndice principal de versiones
  - **changelog/**: Directorio de changelogs individuales por versiÃ³n
    - **v3.4.1.md**: Migraciones BD CÃ¡lculos Asistencias (10-Oct-2025)
    - **v3.4.0.md**: IntegraciÃ³n API Base44 (9-Oct-2025)
    - **README.md**: GuÃ­a de estructura y convenciones
  - **TODO.md**: Lista de tareas pendientes
- **docs/**: Directorio de AdminLTE (NO MODIFICAR)

IMPORTANTE: Todos los archivos de documentaciÃ³n del proyecto deben guardarse en `/documentation` para no confundirlos con `/docs` que pertenece a la plantilla AdminLTE.

### **Sistema de Changelogs Modularizados (V3.4.1+)**
A partir de la versiÃ³n 3.4.1, cada versiÃ³n tiene su propio archivo en `documentation/changelog/`:
- **PropÃ³sito**: Evitar que CHANGELOG.md se vuelva demasiado extenso
- **Formato**: `vX.Y.Z.md` (ejemplo: `v3.4.1.md`)
- **Ãndice**: `CHANGELOG.md` sirve como Ã­ndice con enlaces a archivos individuales
- **Template**: Copiar estructura de versiones existentes para nuevas versiones
- **Convenciones**: Incluir fecha, tipo, componentes, estadÃ­sticas y referencias cruzadas
- **Ãšltima VersiÃ³n**: v3.5.6 (13-Nov-2025) - SincronizaciÃ³n Calendario API + Feriados Pagados + UnificaciÃ³n

      
      IMPORTANT: this context may or may not be relevant to your tasks. You should not respond to this context unless it is highly relevant to your task.
### ðŸ†• V3.5.7 - Vacaciones + CÃ¡lculo por Acumulados
- Vacaciones: salario diario = ACUMULADOS("SALARIO_BASE", Ãºltimos 11 meses) Ã· 11 Ã· 30
- Vacaciones: `vacation_requests.payroll_id` (FK) para controlar planillas Ãºnicas
- Vacaciones: PDF horizontal, etiquetas ES, columnas balanceadas, resumen de dÃ­as alineado con â€œBalance Actualâ€
- Evaluador Seguro: funciÃ³n `CONCEPTO("NOMBRE")` para referenciar/evaluar conceptos con retorno 0 si no existe

### ðŸ› ï¸ PreparaciÃ³n Horas Extra
- Migraciones para elegibilidad y tolerancias (employees/schedules/attendance_calculations)
- Ajustes en calculadores y resolvers para respetar tolerancias y elegibilidad

## Update v3.5.8 — Multitenancy + Vacations Filters + Attendance Tolerances (2025-11-18)
- Multitenancy scaffolding: config/master_database.php, App\\Core\\MasterDatabase, migration 	enants, App\\Models\\WizardModel, wizard routes in App\\Core\\App, App\\Controllers\\WizardController namespaced.
- Remote distributor validation via cURL using .env (DISTRIBUTOR_VALIDATION_URL), with SSL and timeouts.
- Vacations: filter employees/requests by 	ipo_planilla_id (sessionStorage + navbar event). Vacation payroll description forced to UPPERCASE.
- PDF Vacation request: landscape orientation, Spanish labels (Tipo/Estado), removed "Dias Habiles", improved alignment, "Resumen de Dias" matches "Balance Actual".
- Attendance: schedule and lunch tolerances applied in total hours; night hours clamped to 0 for daytime shifts within tolerance.

Next steps
- TenantResolver and DatabaseManager.forTenant + middleware to set TenantContext.
- Implement importTenantSchema to run tenant migrations and seed initial data.
- Enforce CSRF on /panel/attendance/process-day.
- Preserve 	ipo_planilla_id on internal links within vacations module.

### 🆕 V3.5.9 - Employee Import System Overhaul + Wizard UI Improvements (21-Nov-2025)

**Sistema Importación Excel Completo** (EmployeeImportController.php):
- **3 Campos Nuevos**: `email` (requerido, validación FILTER_VALIDATE_EMAIL), `marca_asistencia` (opcional, default 1), `permite_horas_extras` (opcional, default 1)
- **Template Actualizado**: 30→33 columnas (A-AG), shift completo todas las columnas después de EMAIL
- **Método formatBoolean()** (26 líneas): Conversión flexible 1/0, SI/NO, YES/NO, true/false (case insensitive + defaults inteligentes)
- **Integración employee_payroll_salaries**: Creación automática registro salario con `tipo_planilla_id`, `sueldo_base`, auditoría (`notes`, `created_by`)
- **Foto Default**: Asignación automática `images/facebook-profile-image.jpeg` para consistencia visual
- **Validaciones Mejoradas**: Email obligatorio + formato válido, mensajes error descriptivos con número columna
- **Instrucciones Template**: 3 secciones nuevas (Obligatorios/Opcionales/Automáticos) con documentación formatos

**Wizard UI Mejorado** (crear_empresa.php - CSS):
- **Márgenes Simétricos**: Container padding 40px uniforme, reset `.v-row` margins, columnas 12px padding L/R
- **Responsive Optimizado**: Desktop (40px), Tablet (32px 24px), Mobile (24px 16px)
- **Botones Mejorados**: Padding 12x32px, min-height 48px, separador visual border-top, class `.button-group`
- **Resultado**: Simetría perfecta izquierda/derecha + UX profesional multi-dispositivo

**Estadísticas**: 2 archivos | +282 líneas | 1 método nuevo | 8 validaciones | 0 cambios BD | Deployment 10-15 min
