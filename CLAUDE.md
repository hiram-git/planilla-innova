# 🤖 CLAUDE MEMORY — Sistema de Planillas MVC

## 📍 Estado Actual — V3.5.22

- **Versión**: 3.5.22 | **Fecha**: 28-Feb-2026 | **PRs Merged**: #90–#113 (24 PRs)
- **Estado Global**: ✅ Sistema Empresarial 100% | Motor Fórmulas 95% | Todos los demás módulos al 100%
- **Changelog histórico**: [documentation/CHANGELOG.md](documentation/CHANGELOG.md) | **Roadmap**: [documentation/ROADMAP.md](documentation/ROADMAP.md)

## 🎯 Sistema

Plataforma empresarial de planillas con legislación panameña: acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, estructura organizacional y multitenancy completo.

## ✅ Módulos Completados (100%)

**Core & Planillas**: MVC + Router + CSRF + Roles | Planillas + Liquidaciones (11 meses) + PDF/Excel | XIII Mes Trimestral + acumulados | Reportes PDF con logos y firmas | Dashboard Ejecutivo con filtros tipo planilla

**Asistencias (100%)**: API Base44 + sync automática + webhook | Calculadores core + AbsenceDetector | Tolerancias completas (entrada/salida/almuerzo) | Aprobación horas extras (PENDING/APPROVED/REJECTED) | Dashboard gerencial `/panel/attendance` | Reportes ejecutivos `/panel/attendance/reports` (absences, tardiness, combined, punches) | Exportación PDF/Excel | Cron jobs en `scripts/cron/` (reemplazan alertas)

**Vacaciones Panamá (100%)**: VacationController (1452 líneas) + VacationBalanceService | 6 vistas + 3 reportes PDF | Aprobación multinivel vía permisos granulares | Sección "Solicitudes Pendientes" en `/panel/vacation?tipo_planilla_id=1` | Email cubierto por comprobante de pago al generar planilla

**Multitenancy (100%)**: TenantResolver + TenantMigrationSystem + Login multi-tenant | PostgreSQL dinámico (MySQL/PgSQL) | Super Admin (`is_system_admin`) + Wizard + Backoffice Panel

**Otros**: Calendario Empresarial Panamá (731 registros, 28 feriados) | Employee Import (Excel masivo) | Expedientes Empleados (13 tipos + 68 subtipos) | Campos Adicionales Personalizados (4 tipos) | Manual Concepts | Loan System | Employee Documents (PDF/Word) | Permisos Granulares | GSAP Animations | ERP INNOVA Export

## 🆕 Últimas Versiones

### V3.5.22 — Liquidaciones Refactor + Vacation UX (28-Feb-2026)
- LiquidationReportController separado (1196 líneas) aplicando SRP
- Métodos `getAccumulatedTypesForLiquidation()` + `getMonthlyTotalsForLiquidation()`
- Rutas: `/liquidation-reports/*` (reportes) vs `/liquidation/*` (CRUD)
- UX Vacaciones + fix TenantStorage namespace + singular/plural años/días
- [Detalles →](documentation/changelog/v3.5.22.md)

### V3.5.21 — GSAP Animations + Innova Export Fixes (25-Feb-2026)
- Expansión GSAP a 4 vistas (28 funciones totales) con DataTable integration
- 4 bugs Innova Export resueltos + fix session key `$_SESSION['admin']` en Controller.php
- [Detalles →](documentation/changelog/v3.5.21.md)

**Versiones anteriores** (v3.4.0 – v3.5.20): [Ver índice completo →](documentation/changelog/)

## ⏰ Módulo Asistencias — Resumen Técnico

| Subfase | Estado | Componentes Clave |
|---------|--------|-------------------|
| 7.1 API Externa | ✅ | Base44ApiClient (367) + AttendanceSyncService (510) + Webhook |
| 7.2 Cálculos | ✅ | AttendanceCalculator + AbsenceDetector + Lunch + Overtime |
| 7.3 Legal Panamá | ✅ | LegalComplianceChecker + OvertimeRateCalculator + AlertsSystem |
| 7.4 Integración Planillas | ✅ | PeriodAttendanceSummary + AttendanceConceptMapper + PayrollAttendanceIntegrator |
| 7.5 Interfaz y Reportes | ✅ | Dashboard + 4 reportes + PDF/Excel + 8 cron scripts |

**Sistema de Tolerancias** ✅: 8 campos en `schedules` + `permite_horas_extras` en `employees` + `overtime_status` en `attendance_calculations` + 3 funciones fórmulas aprobadas. **[Documentación técnica →](documentation/attendance/TOLERANCES_SYSTEM.md)**

**Legislación Panamá**: Jornada 8h/48h sem (Art.31) | Nocturna 6PM-6AM +50% (Art.38) | Horas extras +25%/+50% (Art.39) | Domingos/feriados +50% (Art.48) | Almuerzo 30min (Art.35) | 3+ ausencias injustificadas/mes = despido (Art.213)

## 🧮 Motor de Fórmulas V3.5.15

**Características**: INIPERIODO/FINPERIODO dinámico | ACUMULADOS() | CONCEPTO() | UNIDAD dinámica | Arquitectura herencia segura | 100% sin `eval()` | nxp/math-executor | 19 funciones asistencias integradas

**Funciones de Asistencias** (consultan `attendance_calculations`, retornan 0 si no hay datos):
- **Horas**: `HORAS_TRABAJADAS()`, `HORAS_REGULARES()`, `HORAS_EXTRAS()`, `HORAS_EXTRAS_25/50()`, `HORAS_NOCTURNAS()`, `HORAS_FERIADOS()`, `HORAS_DOMINICALES()`
- **Horas Extras Aprobadas**: `HORAS_EXTRAS_APROBADAS()`, `HORAS_EXTRAS_APROBADAS_25/50()` (solo `overtime_status = 'APPROVED'`)
- **Ausencias/Tardanzas**: `TARDANZAS()`, `CANTIDAD_TARDANZAS()`, `AUSENCIAS()`, `TOTAL_AUSENCIAS()`, `AUSENCIAS_JUSTIFICADAS()`
- **Estadísticas**: `SCORE_PUNTUALIDAD()`, `DIAS_ASISTENCIA_PERFECTA()`, `DIAS_TRABAJADOS()`

**Ejemplos**:
```php
// Horas Extras 25% (todas)
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25

// Solo aprobadas desde módulo
HORAS_EXTRAS_APROBADAS_25() * (SUELDO / 220) * 1.25

// Descuento por tardanzas
TARDANZAS() / 60 * (SUELDO / 220)

// Bono por puntualidad
SI(SCORE_PUNTUALIDAD() >= 95, 100, 0)

// UNIDAD dinámica (V3.5.15)
UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)

// Reutilización entre conceptos
CONCEPTO("LIQ005") * 0.0975
```

### 🛡️ Seguridad Crítica
- 🚨 **NUNCA** eliminar la librería `nxp/math-executor`
- ⚠️ **PROHIBIDO** `eval()` — usar MathExecutor exclusivamente
- 🔒 Validación obligatoria de fórmulas + preservar multilínea/ACUMULADOS/fechas dinámicas

### Custom Query Builder V3.2.2
Interfaz fluente + adaptadores MySQL/PostgreSQL + 24% mejora rendimiento + 82% reducción código SQL + escalabilidad 5–1000+ empleados

## 🔧 Stack Tecnológico

- **Backend**: PHP 8.3 + MVC + MySQL/PostgreSQL
- **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 + GSAP + FullCalendar 6.1.8
- **Reportes**: TCPDF + PhpSpreadsheet
- **Estado**: Producción estable

## ⚙️ Reglas de Desarrollo

**[Ver reglas completas →](documentation/DEVELOPMENT_RULES.md)** | **[Patrón MVC →](documentation/PATRON_DESARROLLO_MVC.md)**

- **Principio de mínima intervención**: hacer SOLO lo solicitado, sin tocar sistemas funcionando
- **Documentación**: NO crear archivos `.md` automáticamente, solo cuando se solicite explícitamente
- **Flujo**: SIEMPRE presentar opciones y esperar aprobación antes de implementar
- **Código**: PDO prepared statements | CSRF tokens | validación de permisos | NO `eval()` | NO concatenar strings SQL | `ob_start`/`ob_get_clean` para scripts

## 📁 Estructura de Documentación

```
CLAUDE.md                              ← Memoria principal (este archivo)
documentation/
├── CHANGELOG.md                       ← Historial completo
├── ROADMAP.md
├── TODO.md
├── DEVELOPMENT_RULES.md
├── PATRON_DESARROLLO_MVC.md
├── changelog/                         ← Changelogs por versión (v3.4.0–v3.5.22)
└── attendance/
    └── TOLERANCES_SYSTEM.md           ← Documentación técnica tolerancias
docs/                                  ← AdminLTE (NO MODIFICAR)
```

---

**Última Actualización**: 24 de Abril, 2026 — Compactación + verificación estado real (Asistencias 7.5 y Vacaciones al 100%)
**Sistema**: Planillas MVC v3.5.22 | **Progreso**: Core + todos los módulos 100% (Motor Fórmulas 95%)
