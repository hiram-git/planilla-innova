# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📝 **Estado Actual - V3.4.7 Integración Completa Planillas-Asistencias**
- **Fecha**: 20 de Octubre, 2025
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + CALENDARIO + API ASISTENCIAS + ALERTAS LEGALES + INTEGRACIÓN PLANILLAS 80%**
- **Versión**: 3.4.7 - Mapeo automático asistencias → conceptos planilla + procesamiento batch + endpoints AJAX

## 🎯 **Sistema**
Plataforma empresarial de planillas con legislación panameña, acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## ✅ **Componentes Principales Implementados**
- ✅ **Core Sistema**: MVC + Router + Database + CSRF + Roles + Middleware
- ✅ **Planillas & Liquidaciones**: Procesamiento completo + PDF + Acumulados automáticos + Legislación panameña
- ✅ **XIII Mes Panamá**: Cálculo trimestral (Salario Anual ÷ 3) + períodos automáticos + variables dinámicas
- ✅ **Reportes PDF**: Layout empresarial + logos + firmas + comprobantes individuales
- ✅ **Módulos**: Organizacional + Logos + Employee Import + Calendario Empresarial Panamá
- ✅ **Motor Fórmulas V2**: INIPERIODO/FINPERIODO + ACUMULADOS() + CONCEPTO() + nxp/math-executor
- ✅ **Custom Query Builder**: Interfaz fluente + adaptadores multi-BD + 24% mejora rendimiento
- ✅ **UI/UX**: AdminLTE nativo + AJAX DataTables + Responsive 1024px + Cache-busting + Modal refresh
- ✅ **Dashboard Ejecutivo**: Filtros tipo planilla + métricas en tiempo real + gráficas asistencia
- ✅ **Módulo Acumulados**: Vistas byEmployee/byConcepto/byType + cards agrupados + filtros avanzados
- ✅ **Múltiples Tipos Planilla**: Empleados en varios tipos + FIND_IN_SET() queries + Select2 múltiple
- ✅ **Calendario Empresarial**: BusinessCalendar model + feriados Panamá 2024-2025 + FullCalendar.js
- ✅ **API Asistencias Base44 V3.4.0**: Cliente API + sincronización automática + webhook + 3 tablas BD
- ✅ **Sistema Asistencias V3.4.1-3.4.7**: Migraciones BD + Vistas separadas + Calculadores Core + UI integración + AlertsSystem + PayrollAttendanceIntegrator + Mapeo automático (80% Subfases 7.1-7.4 completadas)

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

**Estado Actual**: ✅ Subfase 7.4 COMPLETADA - 80% (4 de 5 subfases completadas)

### **Subfase 7.1: API Externa** ✅ (9-Oct-2025)
- **Base44ApiClient** (367 líneas): cURL + retry logic + timeout 30s
- **AttendanceSyncService** (510 líneas): Sync completa/incremental + duplicados
- **Cron Job**: Sincronización automática cada 15 min
- **Webhook**: /webhooks/base44/attendance + validación HMAC
- **3 Tablas BD**: api_config + raw_data + sync_log
- **Estadísticas**: ~2,417 líneas | 12 archivos nuevos

### **Subfase 7.2: Cálculos Avanzados** ✅ (17-Oct-2025)

**Migraciones BD** (10-Oct-2025): 3 tablas (attendance_calculations, attendance_absence_log, employee_payroll_salaries) | 298 líneas SQL | 14 FKs | 22 índices

**Vistas Separadas** (16-Oct-2025): AttendanceController (135 líneas) + 3 vistas (list/detail/sync) + Attendance Model 5 métodos | ~968 líneas código

**AttendanceCalculator** (16-Oct-2025): 708 líneas totales. 9 métodos públicos (saveCalculation, calculateAndSave, calculateAndSaveBulk, getCalculation, deleteCalculation, getConfig). Persistencia BD completa. Integración WorkScheduleResolver + OvertimeCalculator + WorkingDayClassifier. Testing: 22 tests (90.9% éxito).

**AbsenceDetector** (16-Oct-2025): 693 líneas totales. 11 métodos públicos (saveAbsence, detectAndSaveAbsences, detectAndSaveBulk, justifyAbsence, rejectJustification, getPendingAbsences, getEmployeeAbsences). Workflow PENDING → JUSTIFIED/UNJUSTIFIED. Integración BusinessCalendar.

**UI Integración** (17-Oct-2025): 7 métodos AJAX AttendanceController + 6 rutas nuevas App.php + Vista detail.php badges puntualidad (verde≥80% / amarillo50-79% / rojo<50%) + Vista list.php modal detectar ausencias + Vista pending-absences.php (410 líneas) 4 cards estadísticas + 2 fixes críticos | ~850 líneas UI

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

### **Pendiente Implementar (Subfase 7.5)**
- [ ] Reports Generator (reportes diarios/semanales/mensuales asistencias)
- [ ] Dashboard visual alertas + notificaciones por email
- [ ] Vista empleados: consulta asistencias propias
- [ ] Vista gerencial: dashboard asistencias por departamento
- [ ] Exportación: Excel, PDF, CSV reportes de asistencias

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

      
      IMPORTANT: this context may or may not be relevant to your tasks. You should not respond to this context unless it is highly relevant to your task.