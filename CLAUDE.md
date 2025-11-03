# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📝 **Estado Actual - V3.5.4 Reportes Asistencias + Mejoras UI**
- **Fecha**: 1 de Noviembre, 2025
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + CALENDARIO + API ASISTENCIAS + ALERTAS LEGALES + INTEGRACIÓN PLANILLAS + PROCESAMIENTO BATCH 88% + LIQUIDACIONES PROFESIONALES 100% + SEGURIDAD REFORZADA 100% + REPORTES ASISTENCIAS 30%**
- **Versión**: 3.5.4 - Reporte marcaciones Excel + mejoras UI planillas (fix labels liquidaciones + comprobantes horizontales profesionales)
- **Versión Anterior**: 3.5.3 - Eliminación completa eval() + arquitectura segura con herencia (PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure)

## 🎯 **Sistema**
Plataforma empresarial de planillas con legislación panameña, acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## ✅ **Componentes Principales Implementados**
- ✅ **Core Sistema**: MVC + Router + Database + CSRF + Roles + Middleware
- ✅ **Planillas & Liquidaciones**: Procesamiento completo + PDF + Acumulados automáticos + Legislación panameña
- ✅ **XIII Mes Panamá**: Cálculo trimestral (Salario Anual ÷ 3) + períodos automáticos + variables dinámicas
- ✅ **Reportes PDF**: Layout empresarial + logos + firmas + comprobantes individuales
- ✅ **Módulos**: Organizacional + Logos + Employee Import + Calendario Empresarial Panamá
- ✅ **Motor Fórmulas V3.5.3**: INIPERIODO/FINPERIODO + ACUMULADOS() + CONCEPTO() + arquitectura herencia segura + 100% sin eval() + nxp/math-executor
- ✅ **Custom Query Builder**: Interfaz fluente + adaptadores multi-BD + 24% mejora rendimiento
- ✅ **UI/UX**: AdminLTE nativo + AJAX DataTables + Responsive 1024px + Cache-busting + Modal refresh
- ✅ **Dashboard Ejecutivo**: Filtros tipo planilla + métricas en tiempo real + gráficas asistencia
- ✅ **Módulo Acumulados**: Vistas byEmployee/byConcepto/byType + cards agrupados + filtros avanzados
- ✅ **Múltiples Tipos Planilla**: Empleados en varios tipos + FIND_IN_SET() queries + Select2 múltiple
- ✅ **Calendario Empresarial**: BusinessCalendar model + feriados Panamá 2024-2025 + FullCalendar.js
- ✅ **API Asistencias Base44 V3.4.0**: Cliente API + sincronización automática + webhook + 3 tablas BD
- ✅ **Sistema Asistencias V3.4.1-3.4.8**: Migraciones BD + Vistas separadas + Calculadores Core + UI integración + AlertsSystem + PayrollAttendanceIntegrator + Mapeo automático + Procesamiento batch día + Reprocess (85% Subfases 7.1-7.4 completadas | 80% Subfase 7.2)
- ✅ **Hotfix V3.5.1**: Fix crítico synced_from ENUM + data cleanup + normalización timestamp API Base44 + CSRF dispositivos + deployment scripts

## 📄 **Sistemas Auxiliares Implementados**

### **XIII Mes Trimestral** (V3.3.9)
XIIIMesPeriodoTrimestralCalculator con 3 períodos automáticos (P1-P3), variables dinámicas (INICIO/FIN_PERIODO_XIII), fórmula legislación panameña.

### **Employee Import** (V3.3.10)
Importación masiva Excel con validaciones robustas, PHP 8+ compatible, foreign key handling, error messages mejorados.

### **Planillas de Liquidación**
Generación automática período 11 meses según Código Trabajo Panamá, cálculos legislación completos, vistas separadas.

## 🔄 **Sistema Reprocesamiento Planillas** (V3.4.2)

**Checkbox Validación Situación**: Permite controlar validación situación empleado durante reproceso. Flujo Vista→JS→Controller→Model con parámetro `validate_situacion` opcional (default: true). Archivos: index.php + index.js + PayrollController + Payroll.php. Retrocompatible.

**Reproceso Histórico Propuesto**: Análisis completo en `documentation/ANALISIS_REPROCESO_HISTORICO.md` (400+ líneas). Sistema para reprocesar con empleados/salarios históricos vigentes en fecha original. 5 fases propuestas: detección ausencia empleados + queries históricos + modal confirmación. Estado: pendiente aprobación.

## 📊 **Características UI/UX Destacadas**

**Sidebar AdminLTE** (V3.3.16): Refactorización completa estructura multilevel nativa. getCurrentRoute() mejorado, eliminado JavaScript interferente, soporte subdirectorios. Archivos: sidebar.php + admin.php.

**Módulo Acumulados** (V3.3.15 + V3.3.17): 3 vistas refactorizadas (byEmployee/byConcepto/byType) con cards agrupados AdminLTE, filtros avanzados (Select2), 3 modos agrupación, DataTables español, integración sessionStorage.

**Dashboard Ejecutivo** (V3.3.11): Filtrado completo por tipo planilla con sessionStorage, evento `payrollTypeChanged`, métricas en tiempo real, gráficas asistencia, tabs alineados.

**Sistema Empleados**: Vista activos (/panel/employees) + vista terminados (/terminated), JavaScript modular, AJAX DataTables server-side, callouts AdminLTE, badges visuales.

**Función CONCEPTO()**: Sintaxis flexible, reutilización cálculos entre conceptos, protección recursión. Ejemplo: `CONCEPTO("LIQ005") * 0.0975`.

**UX/UI**: Iconos FontAwesome estados planillas, responsive 1024px, días preaviso editables AJAX, cache-busting SSIIHH, modal refresh inteligente, SweetAlert2.

## ⏰ **MÓDULO API MARCACIONES Y ASISTENCIAS**

**Objetivo**: Integración API marcaciones + control automatizado asistencias + generación conceptos planillas según legislación panameña.

**Estado Actual**: ✅ Subfase 7.4 COMPLETADA - 88% (4 de 5 subfases completadas | Subfase 7.2 mejorada al 80% | Subfase 7.5 iniciada al 30%)

### **Subfase 7.1: API Externa** ✅ (9-Oct-2025)
- **Base44ApiClient** (367 líneas): cURL + retry logic + timeout 30s
- **AttendanceSyncService** (510 líneas): Sync completa/incremental + duplicados
- **Cron Job**: Sincronización automática cada 15 min
- **Webhook**: /webhooks/base44/attendance + validación HMAC
- **3 Tablas BD**: api_config + raw_data + sync_log
- **Estadísticas**: ~2,417 líneas | 12 archivos nuevos

### **Subfase 7.2: Cálculos Avanzados** 🔵 80% (23-Oct-2025)

**Migraciones BD** (10-Oct-2025): 3 tablas (attendance_calculations, attendance_absence_log, employee_payroll_salaries) | 298 líneas SQL | 14 FKs | 22 índices

**Vistas Separadas** (16-Oct-2025): AttendanceController (135 líneas) + 3 vistas (list/detail/sync) + Attendance Model 5 métodos | ~968 líneas código

**AttendanceCalculator** (16-Oct-2025): 708 líneas totales. 9 métodos públicos (saveCalculation, calculateAndSave, calculateAndSaveBulk, getCalculation, deleteCalculation, getConfig). Persistencia BD completa. Integración WorkScheduleResolver + OvertimeCalculator + WorkingDayClassifier. Testing: 22 tests (90.9% éxito).

**AbsenceDetector** (16-Oct-2025): 693 líneas totales. 11 métodos públicos (saveAbsence, detectAndSaveAbsences, detectAndSaveBulk, justifyAbsence, rejectJustification, getPendingAbsences, getEmployeeAbsences). Workflow PENDING → JUSTIFIED/UNJUSTIFIED. Integración BusinessCalendar.

**UI Integración** (17-Oct-2025): 7 métodos AJAX AttendanceController + 6 rutas nuevas App.php + Vista detail.php badges puntualidad (verde≥80% / amarillo50-79% / rojo<50%) + Vista list.php modal detectar ausencias + Vista pending-absences.php (410 líneas) 4 cards estadísticas + 2 fixes críticos | ~850 líneas UI

**Procesamiento Completo Día + Reprocess** (23-Oct-2025): Método processDay() (220 líneas) con pipeline 3 pasos (ausencias → omisiones → cálculos) + Detección automática empleados sin marcación → ABSENT + Single punch → INCOMPLETE + Filtrado tipo planilla sessionStorage + Reprocesamiento: deleteByHeader() + recarga desde tabla attendance + Columna Horas Extras +25%/+50% + 4 fixes críticos (jQuery loading + dynamic updates + undefined keys) | ~500 líneas código

**Cálculos Implementados**: Marcaciones perfectas ✅ | Horas trabajadas ✅ | Ausencias ✅ | Justificaciones ✅ | Tardanzas ✅ | Horas extras 25%/50% ✅ | Salidas anticipadas ✅ | Almuerzo ✅ | Score puntualidad 0-100 ✅ | Horas nocturnas ✅ | Horas feriados ✅

### **Subfase 7.3: Consideraciones Legales Panamá** ✅ (20-Oct-2025)

**LegalComplianceChecker** (604 líneas): Validación cumplimiento normativa laboral panameña. Métodos: validateDailyHours(), validateWeeklyHours(), validateLunchBreak(), validateNightHours(), performFullCompliance(). Niveles riesgo: NINGUNO, BAJO, MEDIO, ALTO, CRÍTICO.

**OvertimeRateCalculator** (408 líneas): Cálculo tarifas según tipo hora extra. Recargos: +25% primeras 3h, +50% adicionales, +50% nocturnas, +100% feriados. Métodos: calculateHourlyRate(), calculateOvertimePay(), calculateOvertimeRates().

**WorkingDayClassifier** (472 líneas): Clasificación días ordinarios/festivos/descanso. Tipos: LABORAL, FERIADO, DUELO_NACIONAL, FIN_SEMANA, ESPECIAL. Integración completa BusinessCalendar. Métodos: classifyDate(), getWorkingDaysBetween(), getDayClassificationForPeriod().

**AlertsSystem** (675 líneas): Sistema completo alertas automáticas. 14 métodos públicos. Workflow: PENDING → ACKNOWLEDGED → RESOLVED/DISMISSED. 10+ tipos alertas (exceso jornada, ausencias graves, tardanzas). 3 niveles severidad (INFO, WARNING, CRITICAL). Prevención duplicados + metadata JSON flexible + referencias legales (Art. 31, 35, 38, 39, 48, 213).

**Migración BD** (342 líneas): Tabla attendance_alerts (20 campos + 11 índices + 4 Foreign Keys). 4 vistas útiles: v_active_alerts, v_critical_alerts, v_employee_alert_stats, v_alerts_by_type. 2 triggers + 3 stored procedures.

**Integración AttendanceCalculator** (+180 líneas): 4 métodos nuevos: checkLegalComplianceAndAlert(), calculateSaveAndAlert(), calculateSaveAndAlertBulk(), getEmployeeAlerts(). Flujo automático: calcular → guardar → verificar → alertar.

**Testing Suite** (481 líneas, 33 tests): 95.5% éxito (21/22 tests). Cobertura: LegalComplianceChecker (87.5%), OvertimeRateCalculator (100%), WorkingDayClassifier (100%), AlertsSystem, Integración Completa.

### **Subfase 7.4: Integración Planillas-Asistencias** ✅ (20-Oct-2025)

**Migración BD** (342 líneas): 3 tablas nuevas (payroll_attendance_summary, attendance_concepts_mapping, payroll_attendance_details). 10 Foreign Keys + 15 índices optimizados. 3 vistas útiles. 10 tipos mapeo soportados (regular hours, overtime 25/50%, night, holidays, Sunday, tardiness/absence deductions, bonuses).

**PeriodAttendanceSummary** (349 líneas): Generación resúmenes consolidados por período. Métodos: generateSummary(), aggregateMetrics(), checkLegalCompliance(). Integración LegalComplianceChecker. Resumen incluye: horas trabajadas, overtime, tardanzas, ausencias, puntualidad, cálculos monetarios, compliance legal.

**AttendanceConceptMapper** (636 líneas): Mapeo asistencias → conceptos planilla. 10 métodos especializados (regular, overtime25, overtime50, night, holidays, Sunday, tardiness, absences, bonuses). Soporte fórmulas dinámicas: {SUELDO}, {TARIFA_HORA}, {CANTIDAD}. Evaluación segura MathExecutor (sin eval()). Mapeo configurable por tipo_planilla_id + situacion_id.

**PayrollAttendanceIntegrator** (415 líneas): Coordinador principal integración. Métodos: processPayrollAttendance() (batch), processEmployeeAttendance() (individual), saveSummary(), saveDetails(), getPayrollAttendanceSummary(), getEmployeeAttendanceDetails(), deletePayrollAttendanceData(). Transacciones BD. Estadísticas completas: processed, summaries_created, concepts_generated, errors.

**Integración PayrollController** (+217 líneas): 4 endpoints AJAX nuevos: /process-attendance, /attendance-summary, /attendance-details/{employeeId}, /delete-attendance-data. Método getEmployeesByPayroll() agregado a PayrollDetail. Rutas configuradas en App.php.

**Script Testing** (481 líneas): 5 fases (preparación, summary, mapper, integrator, batch). Color-coded output. Auto-detección datos test. Verbose mode + success rate.

**Fixes**: AttendanceDeviceController layout (render() vs view() en 3 métodos). Sync-history view (removido botón navegación a /sync).

### **Legislación Panamá Aplicada**
Jornada ordinaria 8h/48h semanales (Art.31) | Jornada nocturna 6PM-6AM +50% (Art.38) | Horas extras +25%/+50% (Art.39) | Domingos/feriados +50% (Art.48) | Almuerzo 30min mínimo (Art.35) | 3+ ausencias injustificadas/mes = despido (Art.213)

### **Subfase 7.5: Interfaz y Reportes** 🔵 30% (01-Nov-2025)

**Reporte de Marcaciones** ✅ (01-Nov-2025): ReportsGenerator->generateDetailedPunchesReport() (145 líneas) + ExcelExporter->exportPunchesReport() (188 líneas) + AttendanceController->punchesReport() (75 líneas). Ruta /panel/attendance/reports/punches. 8 estadísticas resumen + top 10 tardanzas + detalle por departamento (10 columnas). Formatos: Excel + Vista Web + JSON.

**Pendiente Implementar**:
- [ ] Vista empleados: consulta asistencias propias en tiempo real
- [ ] Vista gerencial: dashboard asistencias por departamento
- [ ] Reportes ejecutivos: ausentismo, horas extras por período
- [ ] Alertas automáticas: ausencias injustificadas, excesos jornada
- [ ] Exportación PDF: reportes asistencias con logos empresariales
- [ ] Dashboards visuales: gráficos asistencia por período
- [ ] Notificaciones: alertas automáticas supervisores/RRHH

### **Versión V3.5.4: Reportes Asistencias + Mejoras UI** ✅ (01-Nov-2025)

**Componentes Implementados**:
1. **Reporte Marcaciones Completo** (408 líneas):
   - ReportsGenerator->generateDetailedPunchesReport(): SQL optimizado + 8 estadísticas + top 10 tardanzas
   - ExcelExporter->exportPunchesReport(): Formato profesional Excel con agrupación por departamento
   - AttendanceController->punchesReport(): Endpoint formatos view/json/excel
   - Ruta /panel/attendance/reports/punches configurada en App.php
2. **Fix Label Liquidaciones** (LiquidationController.php):
   - "Posición:" → "Cargo:" en PDF y Excel (2 métodos)
3. **Mejoras Comprobantes Horizontales** (ReportController.php):
   - Eliminación headers/footers TCPDF automáticos
   - Colores profesionales liquidaciones (naranja intenso RGB 255,140,0 + azul profundo RGB 25,118,210)
   - Sección firmas 3 columnas (Elaborado/Autorizado/Recibido por Colaborador)

**Archivos Modificados**: App.php, AttendanceController.php, ReportsGenerator.php, ExcelExporter.php, LiquidationController.php, ReportController.php

**Estadísticas**: 6 archivos | ~620 líneas código agregadas | 0 cambios BD | Deployment: 10-15 min

### **Versión V3.5.2: Mejora Reportes Liquidaciones** ✅ (30-Oct-2025)

**Mejoras Implementadas**:
1. **Campos Adicionales Reportes PDF/Excel** (LiquidationController.php):
   - Fecha Fin de Contrato (desde employee_terminations.termination_date)
   - Posición (desde tabla cargos vía posiciones)
   - Tiempo en Empresa (calculado automáticamente: "X años, Y meses, Z días")
   - Salario (desde employees.sueldo_individual con formato $X,XXX.XX)
2. **Sección Firmas Profesionales**:
   - 3 columnas: Autorizado por (Gerencia) | Elaborado por (RRHH) | Recibido por (Colaborador)
   - Líneas para firma física en PDF con espaciado optimizado
   - Formato profesional en Excel con alineación centrada
3. **Mejoras SQL Queries**:
   - JOINs adicionales: posiciones, cargos, employee_terminations
   - Query optimizado para obtener información completa en una consulta
4. **Cálculo Inteligente Tiempo Empresa**:
   - Usa DateTime::diff() para precisión
   - Formato humanizado ("X años, Y meses, Z días")
   - Maneja casos especiales (0 días, menos de 1 mes, etc.)

**Archivos Modificados**:
- LiquidationController.php: exportPayrollPdf() (~300 líneas), exportPayrollExcel() (~280 líneas)

**Estadísticas**: 1 archivo | 2 métodos | ~250 líneas agregadas | 4 campos nuevos | 1 sección firmas | 3 JOINs SQL | 0 cambios BD

### **Refactorización V3.5.3: Eliminación eval() + Arquitectura Segura** ✅ (01-Nov-2025)

**Cambios Críticos de Seguridad**:
1. **Eliminación Total eval()** (PlanillaConceptCalculator.php):
   - 862 líneas de código corrupto/duplicado eliminadas (líneas 166-1030)
   - Archivo reducido de 2058 a 1196 líneas
   - Arquitectura de herencia: `class PlanillaConceptCalculator extends PlanillaConceptCalculatorSecure`
   - 100% evaluación fórmulas mediante NXP\MathExecutor (sin eval())
2. **Refactorización Visibilidad** (PlanillaConceptCalculatorSecure.php):
   - 9 propiedades `private`→`protected` (incluye $db, $executor, $conceptos, etc.)
   - 18 métodos `private`→`protected` para permitir herencia completa
   - Fix crítico acceso $db que causaba error null pointer
3. **Validación Variables Extendida** (líneas 54-70):
   - Agregadas variables string permitidas: EMPLEADO, CLAVE_SS, CLAVE_SEGURO_SOCIAL
   - Mejora en configurarValidacionesEstritas() para identificadores de empleados
4. **Testing Runtime Completo**:
   - 3 errores resueltos durante testing: null $db, private methods, variable validation
   - Validación con planillas reales (ID 85, tipo planilla 2, empleado 1)

**Arquitectura Final**:
```
PlanillaConceptCalculatorSecure (Padre)
├── MathExecutor $executor (seguro, sin eval())
├── Evaluación segura de fórmulas
├── Funciones personalizadas (ACUMULADOS, CONCEPTO, etc.)
└── Validaciones estrictas de variables

    ↓ extends

PlanillaConceptCalculator (Hijo)
├── XIIIMesPeriodoTrimestralCalculator
├── Override setVariablesColaborador() (múltiples tipos planilla)
├── Funciones liquidaciones (LIQUIDACION_INDEMNIZACION, etc.)
├── Funciones vacaciones (VACATION_DAYS_EARNED, etc.)
└── Funciones XIII mes trimestral
```

**Archivos Modificados**:
- PlanillaConceptCalculator.php: Eliminadas 862 líneas, estructura limpia 1196 líneas
- PlanillaConceptCalculatorSecure.php: 9 propiedades + 18 métodos refactorizados

**Estadísticas**: 2 archivos | -1085 líneas inseguras | +321 líneas seguras | 0 eval() restante | 0 cambios BD

**Mejoras de Seguridad**:
- ✅ Eliminación de vectores de inyección de código
- ✅ Sandbox aislado para evaluación de fórmulas (MathExecutor)
- ✅ Validación estricta de variables antes de evaluación
- ✅ Código más limpio y mantenible
- ✅ Mejor separación de responsabilidades

### **Hotfix V3.5.1: Data Cleanup & Fixes Críticos** ✅ (28-Oct-2025)

**Problema Crítico**: Error "Data truncated for column 'synced_from'" bloqueaba sincronización asistencias.

**Correcciones Aplicadas**:
1. **Fix synced_from ENUM** (AttendanceSyncService.php línea 436, AttendanceController.php línea 992):
   - 'API_SYNC' → 'API'
   - 'MANUAL_PROCESSING' → 'MANUAL'
2. **Normalización Timestamp** (AttendanceSyncService.php líneas 268-275):
   - Soporte `actual_timestamp`, `registered_timestamp` además de `timestamp`
   - Mejora compatibilidad API Base44
3. **Corrección Emails Empleados**:
   - 3 empleados actualizados (ID 2, 3, 5) para coincidir con API Base44
4. **Limpieza Datos**:
   - 10 registros attendance_detail con NULL/NULL eliminados
   - 179 registros raw duplicados marcados como procesados
5. **Fix CSRF Dispositivos** (AttendanceDeviceController.php):
   - Token CSRF agregado a vista index (línea 32)
   - Validación CSRF en delete/testConnection/toggle (líneas 172, 213, 287)

**Scripts Deployment**:
- Migración SQL: `2025_10_28_fix_attendance_data_cleanup.sql`
- Guía deployment: `GUIA_DEPLOYMENT_PRODUCCION.md` (24-32 min estimado)
- Script verificación: `verify_attendance_system.sql`

**Resultados**:
- Tasa éxito sincronización: **50% → 93%** (+86%)
- Registros procesados: **30 → 209** (+597%)
- Todas funciones dispositivos (edit, delete, toggle, test) **ahora funcionan correctamente**

## 📅 **Calendario Empresarial Panamá** ✅ (V3.3.21-22)

**BD**: Tabla business_calendar con 731 registros (2024-2025), 28 feriados nacionales. Tipos: LABORAL/NO_LABORAL/FERIADO/DUELO/ESPECIAL.

**Model**: BusinessCalendar.php (355+ líneas). Métodos: getWorkingDaysBetween(), isWorkingDay(), getNextWorkingDay(), initializeYear(), getMonthCalendar(), getHolidaysByYear().

**UI**: BusinessCalendarController CRUD + 2 vistas (index + calendar) + FullCalendar.js 6.1.8 + API AJAX getWorkingDays() + Script CLI inicialización automática años.

**Nota**: Subfase 4.4 (Integración Cálculos Legales) CANCELADA - se implementará en módulo asistencias.

## 🔑 **Próximas Fases**
1. **⏰ ASISTENCIAS**: Subfase 7.5 (Interfaz y Reportes - dashboard, vistas empleados, exportación)
2. **🏖️ VACACIONES PANAMÁ**: VacationCalculator + CRUD + Aprobaciones + Integración
3. **🏢 MULTITENANCY**: Wizard empresas + BD automática

## 🔧 **Stack Tecnológico**
**Backend**: PHP 8.3 + MVC + MySQL | **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 | **Reportes**: TCPDF | **Estado**: Producción estable

## 🧮 **Motor Fórmulas & Query Builder**

**Motor Fórmulas V3.2.1**: INIPERIODO/FINPERIODO dinámico + ACUMULADOS() + CONCEPTO() + tipo_acumulado + regex optimizado + nxp/math-executor (seguridad). Fix frecuencia ENUM→INT.

**Custom Query Builder V3.2.2**: Interfaz fluente + CRUD optimizado + adaptadores MySQL/PostgreSQL + 24% mejora rendimiento + 82% reducción código SQL + escalabilidad 5-1000+ empleados.

### **⏰ Funciones de Asistencias V3.5.3** ✅ (31-Oct-2025)

**16 funciones** integradas al motor de fórmulas para consultar datos del módulo de marcaciones. Retornan 0 si no hay datos (opcional). Consultan `payroll_attendance_summary` automáticamente.

**Funciones Horas (Asignaciones)**:
- `HORAS_TRABAJADAS()` - Total horas trabajadas del período
- `HORAS_REGULARES()` - Horas regulares sin extras
- `HORAS_EXTRAS()` - Total horas extras (25% + 50%)
- `HORAS_EXTRAS_25()` - Horas extras al 25% (primeras 3h)
- `HORAS_EXTRAS_50()` - Horas extras al 50% (adicionales)
- `HORAS_NOCTURNAS()` - Horas nocturnas 6PM-6AM
- `HORAS_FERIADOS()` - Horas trabajadas en feriados
- `HORAS_DOMINICALES()` - Horas trabajadas en domingos

**Funciones Ausencias/Tardanzas (Deducciones)**:
- `TARDANZAS()` - Minutos de tardanza total
- `CANTIDAD_TARDANZAS()` - Número de tardanzas
- `AUSENCIAS()` - Días ausencias injustificadas
- `TOTAL_AUSENCIAS()` - Total ausencias (justificadas + injustificadas)
- `AUSENCIAS_JUSTIFICADAS()` - Días ausencias justificadas

**Funciones Estadísticas**:
- `SCORE_PUNTUALIDAD()` - Score 0-100 de puntualidad
- `DIAS_ASISTENCIA_PERFECTA()` - Días con asistencia perfecta
- `DIAS_TRABAJADOS()` - Total días trabajados

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

**Implementación** (PlanillaConceptCalculatorSecure.php:79-211):
- Método helper `obtenerDatoAsistencia()` con cache automático
- Query optimizado con tolerancia de fechas
- Limpieza cache automática al cambiar empleado
- Sin eval(), 100% seguro con MathExecutor

**Ventajas**:
- ✅ Opcional - No requiere módulo marcaciones activo
- ✅ Depurable - Fácil testing de fórmulas
- ✅ Flexible - Conceptos configurables desde UI
- ✅ Consistente - Integrado con ACUMULADOS(), CONCEPTO()

**🛡️ SEGURIDAD CRÍTICA**:
- 🚨 NUNCA eliminar librería nxp/math-executor
- ⚠️ PROHIBIDO eval() - usar MathExecutor exclusivamente
- 🔒 Validación obligatoria fórmulas + preservar multilínea/ACUMULADOS/fechas dinámicas

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.

## 🚨 **FLUJO OBLIGATORIO PARA ANÁLISIS**
**MANDATORY ANALYSIS WORKFLOW - NO EXCEPTIONS**

Cuando el usuario solicite cualquier tipo de análisis (usando palabras como "analiza", "analyze", "evalúa", "estudia", etc.):

1. **ANÁLISIS**: Realizar investigación y análisis completo
2. **PRESENTACIÓN**: Presentar opciones, pros/contras, recomendaciones
3. **ESPERAR APROBACIÓN**: NO proceder hasta recibir confirmación explícita del usuario
4. **IMPLEMENTACIÓN**: Solo si se solicita específicamente

**PROHIBIDO**: Implementar automáticamente después de análisis sin aprobación explícita.
**OBLIGATORIO**: Siempre preguntar "¿Proceder con la implementación de [opción recomendada]?" antes de cualquier implementación.

## 📁 **Estructura de Documentación**
- **CLAUDE.md**: Memoria principal del proyecto (raíz)
- **documentation/**: Directorio para archivos de documentación del proyecto
  - **ROADMAP.md**: Hoja de ruta y planificación
  - **CHANGELOG.md**: Índice principal de versiones
  - **changelog/**: Directorio de changelogs individuales por versión
    - **v3.4.1.md**: Migraciones BD Cálculos Asistencias (10-Oct-2025)
    - **v3.4.0.md**: Integración API Base44 (9-Oct-2025)
    - **README.md**: Guía de estructura y convenciones
  - **TODO.md**: Lista de tareas pendientes
- **docs/**: Directorio de AdminLTE (NO MODIFICAR)

IMPORTANTE: Todos los archivos de documentación del proyecto deben guardarse en `/documentation` para no confundirlos con `/docs` que pertenece a la plantilla AdminLTE.

### **Sistema de Changelogs Modularizados (V3.4.1+)**
A partir de la versión 3.4.1, cada versión tiene su propio archivo en `documentation/changelog/`:
- **Propósito**: Evitar que CHANGELOG.md se vuelva demasiado extenso
- **Formato**: `vX.Y.Z.md` (ejemplo: `v3.4.1.md`)
- **Índice**: `CHANGELOG.md` sirve como índice con enlaces a archivos individuales
- **Template**: Copiar estructura de versiones existentes para nuevas versiones
- **Convenciones**: Incluir fecha, tipo, componentes, estadísticas y referencias cruzadas
- **Última Versión**: v3.5.4 (01-Nov-2025) - Reporte marcaciones Excel + mejoras UI planillas

      
      IMPORTANT: this context may or may not be relevant to your tasks. You should not respond to this context unless it is highly relevant to your task.