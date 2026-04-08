# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📍 **Estado Actual - V3.5.22 (Liquidaciones Report Refactor + Vacation UX Improvements + Attendance Tolerances)**
- **Fecha**: 28 de Febrero, 2026 (**Refactorización Liquidaciones + Mejoras UX Vacaciones + Sistema Tolerancias Asistencias**)
- **Versión**: 3.5.22 - Refactor LiquidationReportController + Métodos cálculo avanzado + UX vacaciones + Sistema tolerancias completo + Cálculo almuerzo avanzado
- **Commits Revisados**: 235+ commits (Nov-2025 a Feb-2026) | **PRs Merged**: #90-#113 (24 PRs)
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + VACACIONES PANAMÁ 90% (+45%) + MULTITENANCY 100% (+55%) + MOTOR FÓRMULAS 95% + API ASISTENCIAS 95% (+3%) + CALENDARIO 100% + LIQUIDACIONES 100% + SEGURIDAD 100% + EMPLOYEE IMPORT 100% + PERMISOS GRANULARES 100% + EXPEDIENTES 100% + CAMPOS ADICIONALES 100% + POSTGRESQL 100% + SUPER ADMIN 100% + MANUAL CONCEPTS 100% + LOAN SYSTEM 100% + ATTENDANCE TOLERANCES 100%**
- **Changelog**: [Ver historial completo →](documentation/CHANGELOG.md)

## 🎯 **Sistema**
Plataforma empresarial de planillas con legislación panameña, acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## ✅ **Componentes Principales Implementados**
- ✅ **Core Sistema**: MVC + Router + Database + CSRF + Roles + Middleware
- ✅ **Planillas & Liquidaciones**: Procesamiento completo + PDF + Acumulados automáticos + Legislación panameña
- ✅ **XIII Mes Panamá**: Cálculo trimestral (Salario Anual ÷ 3) + períodos automáticos + variables dinámicas
- ✅ **Reportes PDF**: Layout empresarial + logos + firmas + comprobantes individuales
- ✅ **Módulos**: Organizacional + Logos + Employee Import + Calendario Empresarial Panamá + Expedientes Empleados
- ✅ **Motor Fórmulas V3.5.3**: INIPERIODO/FINPERIODO + ACUMULADOS() + CONCEPTO() + arquitectura herencia segura + 100% sin eval() + nxp/math-executor
- ✅ **Custom Query Builder**: Interfaz fluente + adaptadores multi-BD + 24% mejora rendimiento
- ✅ **UI/UX**: AdminLTE nativo + AJAX DataTables + Responsive 1024px + Cache-busting + Modal refresh
- ✅ **Dashboard Ejecutivo**: Filtros tipo planilla + métricas en tiempo real + gráficas asistencia
- ✅ **Módulo Acumulados**: Vistas byEmployee/byConcepto/byType + cards agrupados + filtros avanzados + Excel Export
- ✅ **Múltiples Tipos Planilla**: Empleados en varios tipos + FIND_IN_SET() queries + Select2 múltiple
- ✅ **Calendario Empresarial**: BusinessCalendar model + feriados Panamá 2024-2025 + FullCalendar.js + API Sync
- ✅ **API Asistencias Base44**: Cliente API + sincronización automática + webhook + 3 tablas BD
- ✅ **Sistema Asistencias**: Migraciones BD + Calculadores Core + AlertsSystem + PayrollAttendanceIntegrator + Mapeo automático + Procesamiento batch día + Sistema Tolerancias Completo + Cálculo Almuerzo Avanzado (95% completado)
- ✅ **Expedientes Empleados**: 2 tablas (types + subtypes) + 81 registros catálogo completo
- ✅ **Campos Adicionales Personalizados**: 2 tablas + CRUD completo + 4 tipos datos + integración empleados
- ✅ **Vacaciones Panamá**: VacationController (1452 líneas) + VacationBalanceService + 15 métodos + 6 vistas + 3 reportes PDF + generación planillas automática (90% completado)
- ✅ **Multitenancy**: TenantResolver (244 líneas) + TenantMigrationSystem + Login multi-tenant + PostgreSQL support + Super Admin + Wizard completo + Backoffice Panel (100% completado)
- ✅ **PostgreSQL Support**: Conexión dinámica MySQL/PostgreSQL + .env.pgsql.example (100% completado)
- ✅ **Super Admin System**: is_system_admin column + privileges + access control (100% completado)
- ✅ **Manual Concepts**: CRUD conceptos manuales por empleado + integración planillas (100% completado)
- ✅ **Loan System**: Status 'pagado' + vista completa + creditor association + cuotas tracking (100% completado)
- ✅ **Employee Documents**: Work document generators PDF/Word + templates + navigation shortcuts (100% completado)

## 🆕 **Últimas Versiones (Ver changelog para detalles)**

### V3.5.22 - Liquidaciones Report Refactor + Vacation UX Improvements (28-Feb-2026)
- Refactorización completa: LiquidationReportController (1196 líneas) separado de LiquidationController
- Principios SOLID aplicados: Single Responsibility (CRUD vs Reportes)
- Métodos cálculo avanzado: getAccumulatedTypesForLiquidation() + getMonthlyTotalsForLiquidation() (181 líneas)
- Mejoras cálculo LIQ007: Ajustes rangos fechas + logging detallado + totales mensuales precisos
- Rutas refactorizadas: /liquidation-reports/* (reportes) vs /liquidation/* (CRUD)
- UX Vacaciones: Ocultar campos redundantes (Valor Día + Monto Compensación) en vista create
- Fix namespace: TenantStorage import correcto para multi-tenant
- Mejoras visualización: Singular/plural años/días empleado ("1 año" vs "2 años")
- Estadísticas: 5 archivos | 1 controller nuevo | ~1,403 líneas agregadas | ~1,234 eliminadas | 3 bugs corregidos
- [Ver detalles →](documentation/changelog/v3.5.22.md)

### V3.5.21 - GSAP Animations Expansion + Innova Export Fixes (25-Feb-2026)
- Expansión animaciones GSAP a 4 vistas adicionales: Liquidaciones Estimadas (7 funciones) + Planillas Estimadas (10 funciones) + Innova Export (7 funciones) + Crear Planilla Botones (4 funciones)
- 28 funciones GSAP implementadas totales con patrón consolidado + timing coordinado + event delegation
- Correcciones críticas Innova Export: 4 bugs resueltos (session key, view path, parse error, rendering method)
- Bug crítico Controller.php: Fix session key `$_SESSION['admin_id']` → `$_SESSION['admin']` eliminando redirect loop
- Animaciones revertidas employee-manual-concepts: DataTable stuck "Procesando" + lecciones aprendidas documentadas
- Características GSAP: fade-in + slide-up + hover effects (rotation 360° + scale 1.15-1.2) + DataTable drawCallback integration
- Estadísticas: 5 vistas modificadas | 2 controllers corregidos | 1 core file | ~1,015 líneas agregadas | ~350 revertidas | ~665 netas
- [Ver detalles →](documentation/changelog/v3.5.21.md)

**Versiones Anteriores**: [Ver changelog completo →](documentation/CHANGELOG.md)
- V3.5.20: GSAP Animations + Innova Export System
- V3.5.19: Módulo Campos Adicionales Personalizados
- V3.5.18: Fix TypeError insertConceptDetail
- V3.5.17: Bug Fixes + UX Improvements
- V3.5.16: Expedientes Empleados + Migraciones Multi-Tenant
- V3.5.15: UNIDAD Dinámica en Fórmulas
- V3.5.14: Campo UNIDAD en Planilla Detalle
- V3.4.x - V3.5.13: [Ver archivos individuales →](documentation/changelog/)

## ⏰ **MÓDULO API MARCACIONES Y ASISTENCIAS**

**Objetivo**: Integración API marcaciones + control automatizado asistencias + generación conceptos planillas según legislación panameña.

**Estado Actual**: ✅ Subfase 7.4 COMPLETADA - 92% (4 de 5 subfases completadas | Subfase 7.5 al 35%)

### **Subfase 7.1: API Externa** ✅ (9-Oct-2025)
- Base44ApiClient (367 líneas): cURL + retry logic + timeout 30s
- AttendanceSyncService (510 líneas): Sync completa/incremental + duplicados
- Cron Job + Webhook + 3 Tablas BD
- [Ver v3.4.0 →](documentation/changelog/v3.4.0.md)

### **Subfase 7.2: Cálculos Avanzados** 🔵 85% (Oct-Nov-2025)
- AttendanceCalculator + AbsenceDetector con persistencia BD
- Procesamiento completo día + Reprocess + Almuerzo
- Cálculos: Marcaciones perfectas | Horas trabajadas | Ausencias | Tardanzas | Horas extras 25%/50% | Almuerzo | Score puntualidad | Horas nocturnas/feriados
- [Ver v3.4.4 →](documentation/changelog/v3.4.4.md) | [Ver v3.4.8 →](documentation/changelog/v3.4.8.md) | [Ver v3.5.5 →](documentation/changelog/v3.5.5.md)

### **Subfase 7.3: Consideraciones Legales Panamá** ✅ (20-Oct-2025)
- LegalComplianceChecker (604 líneas): Validación normativa laboral panameña
- OvertimeRateCalculator (408 líneas): +25% primeras 3h, +50% adicionales
- WorkingDayClassifier (472 líneas): LABORAL/FERIADO/DUELO/FIN_SEMANA
- AlertsSystem (675 líneas): 10+ tipos alertas + 3 niveles severidad
- [Ver v3.4.6 →](documentation/changelog/v3.4.6.md)

### **Subfase 7.4: Integración Planillas-Asistencias** ✅ (20-Oct-2025)
- PeriodAttendanceSummary (349 líneas): Resúmenes consolidados por período
- AttendanceConceptMapper (636 líneas): Mapeo asistencias → conceptos planilla
- PayrollAttendanceIntegrator (415 líneas): Coordinador principal integración
- 3 tablas BD + 10 Foreign Keys + 15 índices optimizados
- [Ver v3.4.7 →](documentation/changelog/v3.4.7.md)

### **Subfase 7.5: Interfaz y Reportes** 🔵 35% (01-Nov-2025)
- Reporte de Marcaciones: Excel + Vista Web + JSON (8 estadísticas + top 10 tardanzas)
- **Pendiente**: Dashboard gerencial, reportes ejecutivos, alertas automáticas, exportación PDF
- [Ver v3.5.4 →](documentation/changelog/v3.5.4.md)

### **Sistema de Tolerancias y Cálculo Avanzado de Almuerzo** ✅ (18-20-Nov-2025)

**Estado**: 100% COMPLETADO | **Archivos**: 20 modificados | **Código**: +2,413 líneas

Sistema completo de tolerancias para entrada/salida/almuerzo + elegibilidad horas extras + aprobación horas extras + corrección horas nocturnas turnos diurnos.

**Componentes Implementados**:
- 8 campos tolerancias en `schedules` (entrada/salida/almuerzo before/after)
- Campo `permite_horas_extras` en `employees` (elegibilidad por empleado)
- Campo `overtime_status` en `attendance_calculations` (PENDING/APPROVED/REJECTED)
- 6 métodos nuevos: calculateLunchWithTolerance + calculateTardinessWithTolerance + otros
- 3 funciones fórmulas: HORAS_EXTRAS_APROBADAS() + HORAS_EXTRAS_APROBADAS_25/50()
- UI completa: Formularios empleados + horarios con tolerancias

**[Ver documentación técnica completa →](documentation/attendance/TOLERANCES_SYSTEM.md)**

### **Legislación Panamá Aplicada**
Jornada ordinaria 8h/48h semanales (Art.31) | Jornada nocturna 6PM-6AM +50% (Art.38) | Horas extras +25%/+50% (Art.39) | Domingos/feriados +50% (Art.48) | Almuerzo 30min mínimo (Art.35) | 3+ ausencias injustificadas/mes = despido (Art.213)

## 📅 **CALENDARIO EMPRESARIAL PANAMÁ** ✅

**BD**: Tabla business_calendar con 731 registros (2024-2025), 28 feriados nacionales. Tipos: LABORAL/NO_LABORAL/FERIADO/DUELO/ESPECIAL.

**Model**: BusinessCalendar.php (355+ líneas). Métodos: getWorkingDaysBetween(), isWorkingDay(), getNextWorkingDay(), initializeYear()

**UI**: BusinessCalendarController CRUD + 2 vistas + FullCalendar.js 6.1.8 + API AJAX + Script CLI inicialización

**Sincronización API**: CalendarSyncService (~500 líneas) con integración Base44 + campo is_paid_holiday + procesamiento feriados pagados automático

[Ver v3.3.21-22 →](documentation/CHANGELOG.md) | [Ver v3.5.6 →](documentation/changelog/v3.5.6.md)

## 🔐 **PRÓXIMAS FASES** (Actualizado 20-Mar-2026)
1. **⏰ ASISTENCIAS**: Subfase 7.5 (Dashboard gerencial, reportes ejecutivos, notificaciones - 8% restante)
2. **🏖️ VACACIONES PANAMÁ**: Notificaciones email + aprobación multinivel (10% restante) - **Sistema funcional al 90%**

## 🔧 **STACK TECNOLÓGICO**
**Backend**: PHP 8.3 + MVC + MySQL | **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 | **Reportes**: TCPDF | **Estado**: Producción estable

## 🧮 **MOTOR FÓRMULAS & QUERY BUILDER**

**Motor Fórmulas V3.5.15**:
- INIPERIODO/FINPERIODO dinámico + ACUMULADOS() + CONCEPTO() + UNIDAD dinámica
- Arquitectura herencia segura + 100% sin eval() + nxp/math-executor
- 19 funciones asistencias integradas (HORAS_TRABAJADAS, HORAS_EXTRAS_25/50, HORAS_EXTRAS_APROBADAS_25/50, TARDANZAS, etc.)
- Variable UNIDAD con asignación condicional en fórmulas

**Custom Query Builder V3.2.2**:
- Interfaz fluente + CRUD optimizado + adaptadores MySQL/PostgreSQL
- 24% mejora rendimiento + 82% reducción código SQL
- Escalabilidad 5-1000+ empleados

### **⏰ Funciones de Asistencias V3.5.3** ✅

**19 funciones** integradas al motor de fórmulas. Retornan 0 si no hay datos. Consultan `attendance_calculations` automáticamente.

**Funciones Horas**: HORAS_TRABAJADAS(), HORAS_REGULARES(), HORAS_EXTRAS(), HORAS_EXTRAS_25(), HORAS_EXTRAS_50(), HORAS_NOCTURNAS(), HORAS_FERIADOS(), HORAS_DOMINICALES()

**Funciones Horas Extras Aprobadas**: HORAS_EXTRAS_APROBADAS(), HORAS_EXTRAS_APROBADAS_25(), HORAS_EXTRAS_APROBADAS_50() - Consultan solo registros con `overtime_status = 'APPROVED'`

**Funciones Ausencias/Tardanzas**: TARDANZAS(), CANTIDAD_TARDANZAS(), AUSENCIAS(), TOTAL_AUSENCIAS(), AUSENCIAS_JUSTIFICADAS()

**Funciones Estadísticas**: SCORE_PUNTUALIDAD(), DIAS_ASISTENCIA_PERFECTA(), DIAS_TRABAJADOS()

**Ejemplos de Uso**:
```php
// Horas Extras 25% (todas, pendientes + aprobadas)
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25

// Horas Extras APROBADAS 25% (solo aprobadas desde módulo)
HORAS_EXTRAS_APROBADAS_25() * (SUELDO / 220) * 1.25

// Total Horas Extras APROBADAS (25% + 50%)
HORAS_EXTRAS_APROBADAS() * (SUELDO / 220) * 1.25

// Descuento Tardanzas
TARDANZAS() / 60 * (SUELDO / 220)

// Bono Puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)

// UNIDAD Dinámica (V3.5.15)
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)
SI(MARCA_ASISTENCIA, HORAS_REGULARES() * TARIFA_HORA, SUELDO * 0.5)
```

**🛡️ SEGURIDAD CRÍTICA**:
- 🚨 NUNCA eliminar librería nxp/math-executor
- ⚠️ PROHIBIDO eval() - usar MathExecutor exclusivamente
- 🔒 Validación obligatoria fórmulas + preservar multilínea/ACUMULADOS/fechas dinámicas

## 📄 **SISTEMAS AUXILIARES IMPLEMENTADOS**

| Sistema | Estado | Descripción | Ver Más |
|---------|--------|-------------|---------|
| **XIII Mes Trimestral** | 100% | 3 períodos automáticos + variables dinámicas + legislación PA | - |
| **Employee Import** | 100% | Importación masiva Excel + validaciones + PHP 8+ | [v3.5.9](documentation/changelog/v3.5.9.md) |
| **Liquidaciones** | 100% | Período 11 meses + cálculos legislación + PDF/Excel | [v3.5.2](documentation/changelog/v3.5.2.md) |
| **Reprocesamiento Planillas** | 100% | Checkbox validación situación + flujo Vista→JS→Controller | [v3.4.2](documentation/changelog/v3.4.2.md) |
| **Expedientes Empleados** | 100% | 2 tablas + 81 registros catálogo + 13 tipos + 68 subtipos | [v3.5.16](documentation/changelog/v3.5.16.md) |
| **Campos Adicionales** | 100% | 2 tablas + CRUD + 4 tipos datos + integración empleados | [v3.5.19](documentation/changelog/v3.5.19.md) |
| **Vacaciones Panamá** | 90% | VacationController (1452 líneas) + aprobaciones + 3 reportes PDF | [v3.5.7](documentation/changelog/v3.5.7.md) |
| **Multitenancy** | 100% | TenantResolver + PostgreSQL + Super Admin + Wizard + Backoffice Panel | [v3.5.8-10](documentation/CHANGELOG.md) |
| **Manual Concepts** | 100% | CRUD conceptos manuales + integración planillas | PRs #99-100 |
| **Super Admin System** | 100% | is_system_admin column + privileges + access control | PRs #106-107 |
| **PostgreSQL Support** | 100% | Conexión dinámica MySQL/PostgreSQL + .env.pgsql | PRs #109-110 |
| **Loan System** | 100% | Status 'pagado' + creditor association + cuotas tracking | PR #98 |
| **Employee Documents** | 100% | Generadores PDF/Word + templates + navigation | PRs #95-97 |
| **XIII Mes in PDFs** | 100% | Acumulados XIII Mes integrados en reportes planillas | PRs #90-94 |
| **Attendance Enhancements** | 100% | Timezone UTC→Local + document ID + bonus flag | PRs #108-113 |

## 📊 **CARACTERÍSTICAS UI/UX DESTACADAS**

**Sidebar AdminLTE** (V3.3.16 + V3.5.13):
- Refactorización completa estructura multilevel nativa
- Sistema permisos granular con canAccessRoute() + pre-verificación secciones
- 23 módulos filtrados por permisos de lectura
[Ver v3.5.13 →](documentation/changelog/v3.5.13.md)

**Módulo Acumulados** (V3.3.15 + V3.5.12):
- 3 vistas refactorizadas con cards agrupados AdminLTE
- Exportación Excel profesional con PhpSpreadsheet
- Filtros avanzados + 3 modos agrupación + DataTables español
[Ver v3.5.12 →](documentation/changelog/v3.5.12.md)

**Dashboard Ejecutivo** (V3.3.11):
- Filtrado completo por tipo planilla con sessionStorage
- Evento `payrollTypeChanged` + métricas en tiempo real
- Gráficas asistencia + tabs alineados

**Función CONCEPTO()**: Sintaxis flexible, reutilización cálculos entre conceptos, protección recursión. Ejemplo: `CONCEPTO("LIQ005") * 0.0975`

**UX/UI**: Iconos FontAwesome estados planillas, responsive 1024px, días preaviso editables AJAX, cache-busting, modal refresh inteligente, SweetAlert2

## ⚙️ **REGLAS DE DESARROLLO**
**[Ver reglas completas →](documentation/DEVELOPMENT_RULES.md)**

**Principio**: Mínima intervención - hacer SOLO lo solicitado sin tocar sistemas funcionando
**Política Documentación**: NO crear archivos .md automáticamente, solo cuando usuario lo solicite explícitamente
**Flujo Análisis**: SIEMPRE presentar opciones y esperar aprobación antes de implementar

## 📁 **ESTRUCTURA DE DOCUMENTACIÓN**
- **CLAUDE.md**: Memoria principal (raíz) - Info crítica + enlaces
- **documentation/**: CHANGELOG.md + ROADMAP.md + TODO.md + DEVELOPMENT_RULES.md + PATRON_DESARROLLO_MVC.md
  - **changelog/**: Changelogs individuales por versión (v3.4.0 - v3.5.22)
  - **attendance/**: TOLERANCES_SYSTEM.md
- **docs/**: AdminLTE (NO MODIFICAR)

**Convención**: Changelogs modularizados desde V3.4.1+ | Formato: `vX.Y.Z.md` | CLAUDE.md solo menciona últimas 2 versiones

## 📐 **PATRÓN DE DESARROLLO MVC**
**[Ver patrón completo →](documentation/PATRON_DESARROLLO_MVC.md)**

**Estructura**: Controller + Model + Service (opcional) + Views
**Reglas Clave**: ob_start/ob_get_clean para scripts | PDO prepared statements | CSRF tokens | Validación permisos | NO eval() | NO concatenar strings
**Tiempo Estimado**: 30-45 min por módulo CRUD completo

---

**Última Actualización**: 20 de Marzo, 2026 (**Multitenancy Backoffice Panel COMPLETADO + Liquidaciones Report Refactor + Vacation UX Improvements**)
**Sistema**: Planillas MVC v3.5.22
**Progreso Global**: Core 100% | Calendario 100% | **Vacaciones 90%** | **Multitenancy 100%** ✅ | **Motor Fórmulas 95%** | **API Asistencias 95%** (+3%) | **Attendance Tolerances 100%** | Liquidaciones 100% | Seguridad 100% | Employee Import 100% | Acumulados Export 100% | Permisos Granular 100% | Employee Files 100% | Campos Adicionales 100% | **PostgreSQL 100%** | **Super Admin 100%** | **Manual Concepts 100%** | **Loan System 100%** | **UI/UX Animations (GSAP) 100%** | **ERP Integration (INNOVA) 100%** | **Backoffice Panel 100%** ✅
