# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📍 **Estado Actual - V3.5.19 + 230 Commits Nuevos (REVISIÓN COMPLETA)**
- **Fecha**: 29 de Enero, 2026 (**Sincronización con Estado Real del Proyecto**)
- **Versión**: 3.5.19 - Sistema completo campos adicionales personalizados empleados
- **Commits Revisados**: 230+ commits (Nov-2025 a Ene-2026) | **PRs Merged**: #90-#113 (24 PRs)
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + VACACIONES PANAMÁ 90% (+45%) + MULTITENANCY 85% (+40%) + MOTOR FÓRMULAS 95% + API ASISTENCIAS 92% + CALENDARIO 100% + LIQUIDACIONES 100% + SEGURIDAD 100% + EMPLOYEE IMPORT 100% + PERMISOS GRANULARES 100% + EXPEDIENTES 100% + CAMPOS ADICIONALES 100% + POSTGRESQL 100% + SUPER ADMIN 100% + MANUAL CONCEPTS 100% + LOAN SYSTEM 100%**
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
- ✅ **Sistema Asistencias**: Migraciones BD + Calculadores Core + AlertsSystem + PayrollAttendanceIntegrator + Mapeo automático + Procesamiento batch día + Almuerzo (92% completado)
- ✅ **Expedientes Empleados**: 2 tablas (types + subtypes) + 81 registros catálogo completo
- ✅ **Campos Adicionales Personalizados**: 2 tablas + CRUD completo + 4 tipos datos + integración empleados
- ✅ **Vacaciones Panamá**: VacationController (1452 líneas) + VacationBalanceService + 15 métodos + 6 vistas + 3 reportes PDF + generación planillas automática (90% completado)
- ✅ **Multitenancy**: TenantResolver (244 líneas) + TenantMigrationSystem + Login multi-tenant + PostgreSQL support + Super Admin + Wizard completo (85% completado)
- ✅ **PostgreSQL Support**: Conexión dinámica MySQL/PostgreSQL + .env.pgsql.example (100% completado)
- ✅ **Super Admin System**: is_system_admin column + privileges + access control (100% completado)
- ✅ **Manual Concepts**: CRUD conceptos manuales por empleado + integración planillas (100% completado)
- ✅ **Loan System**: Status 'pagado' + vista completa + creditor association + cuotas tracking (100% completado)
- ✅ **Employee Documents**: Work document generators PDF/Word + templates + navigation shortcuts (100% completado)

## 🆕 **Últimas Versiones (Ver changelog para detalles)**

### V3.5.19 - Módulo Campos Adicionales Personalizados (16-Ene-2026)
- Sistema completo campos personalizados: employee_additional_fields + employee_additional_field_values
- 4 tipos datos soportados: TEXTO, NUMERO, FECHA, BOOLEAN + valores por defecto
- CRUD completo AdminLTE + DataTables server-side + named parameters PDO
- Integración formularios empleados: renderizado dinámico create/edit con validaciones
- 7 bugs resueltos: Parse errors PHP + PDO pagination + permisos + routing + migration + edit button
- [Ver detalles →](documentation/changelog/v3.5.19.md)

### V3.5.18 - Fix TypeError insertConceptDetail (15-Ene-2026)
- Fix crítico: PayrollController::insertConceptDetail() tipo parámetro \PDO → Database
- Import agregado: use App\Core\Database
- Regeneración empleados con préstamos restaurada
- [Ver detalles →](documentation/changelog/v3.5.18.md)

### V3.5.17 - Bug Fixes + UX Improvements (29-Dic-2025)
- DataTables persistencia estado: stateSave + Enter key modal eliminación
- Préstamos fixes: ENUM status migración 12 bases + creditor_id + cuotas tracking
- Organigrama: migración idempotente nivel→nivel_jerarquico + limpieza columnas legacy
- Main DB migration runner: script independiente planilla_prod desde .env + SQL parser robusto
- [Ver detalles →](documentation/changelog/v3.5.17.md)

### V3.5.16 - Expedientes Empleados + Migraciones Multi-Tenant (29-Dic-2025)
- Sistema completo expedientes: employee_file_types + employee_file_subtypes (81 registros)
- Runner migraciones robusto: splitSqlStatements() parser + exec()→query() + manejo errores mejorado
- [Ver detalles →](documentation/changelog/v3.5.16.md)

### V3.5.15 - UNIDAD Dinámica en Fórmulas (28-Dic-2025)
- Asignación dinámica UNIDAD en fórmulas: `UNIDAD = SI(MARCA_ASISTENCIA, HORAS_REGULARES(), 15)`
- Captura automática después de evaluar fórmula + almacenamiento en planilla_detalle
- [Ver detalles →](documentation/changelog/v3.5.15.md)

### V3.5.14 - Campo UNIDAD en Planilla Detalle (28-Dic-2025)
- Migración: planilla_detalle.referencia_valor → unidad (VARCHAR 50)
- Variable UNIDAD agregada al motor de fórmulas + actualización 7 archivos PHP
- [Ver detalles →](documentation/changelog/v3.5.14.md)

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

### **Legislación Panamá Aplicada**
Jornada ordinaria 8h/48h semanales (Art.31) | Jornada nocturna 6PM-6AM +50% (Art.38) | Horas extras +25%/+50% (Art.39) | Domingos/feriados +50% (Art.48) | Almuerzo 30min mínimo (Art.35) | 3+ ausencias injustificadas/mes = despido (Art.213)

## 📅 **CALENDARIO EMPRESARIAL PANAMÁ** ✅

**BD**: Tabla business_calendar con 731 registros (2024-2025), 28 feriados nacionales. Tipos: LABORAL/NO_LABORAL/FERIADO/DUELO/ESPECIAL.

**Model**: BusinessCalendar.php (355+ líneas). Métodos: getWorkingDaysBetween(), isWorkingDay(), getNextWorkingDay(), initializeYear()

**UI**: BusinessCalendarController CRUD + 2 vistas + FullCalendar.js 6.1.8 + API AJAX + Script CLI inicialización

**Sincronización API**: CalendarSyncService (~500 líneas) con integración Base44 + campo is_paid_holiday + procesamiento feriados pagados automático

[Ver v3.3.21-22 →](documentation/CHANGELOG.md) | [Ver v3.5.6 →](documentation/changelog/v3.5.6.md)

## 🔐 **PRÓXIMAS FASES** (Actualizado 29-Ene-2026)
1. **⏰ ASISTENCIAS**: Subfase 7.5 (Dashboard gerencial, reportes ejecutivos, notificaciones - 8% restante)
2. **🏖️ VACACIONES PANAMÁ**: Notificaciones email + aprobación multinivel (10% restante) - **Sistema funcional al 90%**
3. **🏢 MULTITENANCY**: Panel admin backoffice CRUD tenants (15% restante) - **Sistema funcional al 85%**

## 🔧 **STACK TECNOLÓGICO**
**Backend**: PHP 8.3 + MVC + MySQL | **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 | **Reportes**: TCPDF | **Estado**: Producción estable

## 🧮 **MOTOR FÓRMULAS & QUERY BUILDER**

**Motor Fórmulas V3.5.15**:
- INIPERIODO/FINPERIODO dinámico + ACUMULADOS() + CONCEPTO() + UNIDAD dinámica
- Arquitectura herencia segura + 100% sin eval() + nxp/math-executor
- 16 funciones asistencias integradas (HORAS_TRABAJADAS, HORAS_EXTRAS_25/50, TARDANZAS, etc.)
- Variable UNIDAD con asignación condicional en fórmulas

**Custom Query Builder V3.2.2**:
- Interfaz fluente + CRUD optimizado + adaptadores MySQL/PostgreSQL
- 24% mejora rendimiento + 82% reducción código SQL
- Escalabilidad 5-1000+ empleados

### **⏰ Funciones de Asistencias V3.5.3** ✅

**16 funciones** integradas al motor de fórmulas. Retornan 0 si no hay datos. Consultan `payroll_attendance_summary` automáticamente.

**Funciones Horas**: HORAS_TRABAJADAS(), HORAS_REGULARES(), HORAS_EXTRAS(), HORAS_EXTRAS_25(), HORAS_EXTRAS_50(), HORAS_NOCTURNAS(), HORAS_FERIADOS(), HORAS_DOMINICALES()

**Funciones Ausencias/Tardanzas**: TARDANZAS(), CANTIDAD_TARDANZAS(), AUSENCIAS(), TOTAL_AUSENCIAS(), AUSENCIAS_JUSTIFICADAS()

**Funciones Estadísticas**: SCORE_PUNTUALIDAD(), DIAS_ASISTENCIA_PERFECTA(), DIAS_TRABAJADOS()

**Ejemplos de Uso**:
```php
// Horas Extras 25%
HORAS_EXTRAS_25() * (SUELDO / 220) * 1.25

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

### **XIII Mes Trimestral** (V3.3.9)
XIIIMesPeriodoTrimestralCalculator con 3 períodos automáticos (P1-P3), variables dinámicas (INICIO/FIN_PERIODO_XIII), fórmula legislación panameña.

### **Employee Import** (V3.3.10 + V3.5.9)
Importación masiva Excel con validaciones robustas, PHP 8+ compatible, foreign key handling, 3 campos nuevos (email, marca_asistencia, permite_horas_extras), integración employee_payroll_salaries automática.
[Ver v3.5.9 →](documentation/changelog/v3.5.9.md)

### **Planillas de Liquidación** (V3.5.2 + V3.5.13)
Generación automática período 11 meses según Código Trabajo Panamá, cálculos legislación completos, vistas separadas, reportes PDF/Excel profesionales con firmas, 100% portable sin hardcoded IDs.
[Ver v3.5.2 →](documentation/changelog/v3.5.2.md) | [Ver v3.5.13 →](documentation/changelog/v3.5.13.md)

### **Sistema Reprocesamiento Planillas** (V3.4.2)
Checkbox validación situación empleado durante reproceso. Flujo Vista→JS→Controller→Model con parámetro `validate_situacion` opcional (default: true). Retrocompatible.
[Ver v3.4.2 →](documentation/changelog/v3.4.2.md)

### **Expedientes Empleados** (V3.5.16)
2 tablas nuevas: employee_file_types (13 tipos) + employee_file_subtypes (68 subtipos). Catálogo completo: Estudios Académicos, Capacitación, Permisos, Licencias, Otros. Menú ID 26.
[Ver v3.5.16 →](documentation/changelog/v3.5.16.md)

### **Campos Adicionales Personalizados** (V3.5.19)
Sistema completo gestión campos dinámicos empleados. 2 tablas BD (employee_additional_fields + employee_additional_field_values). CRUD AdminLTE con DataTables server-side. 4 tipos datos: TEXTO, NUMERO, FECHA, BOOLEAN. Integración automática formularios create/edit empleados con renderizado condicional. Named parameters PDO + validaciones robustas.
[Ver v3.5.19 →](documentation/changelog/v3.5.19.md)

### **Vacaciones Panamá** (V3.5.7 + commits recientes) - **90% COMPLETADO**
VacationController (1452 líneas) con 15 métodos completos. VacationBalanceService con vacation_annual_balances. Sistema aprobaciones + reversión automática balances. Generación planillas automática (SS 9.75% + SE 1.25%). Calendario visual FullCalendar.js integrado con business_calendar. 3 tipos reportes PDF (solicitudes + filtrados + resumen anual). Cálculo salario diario promedio 11 meses (legislación). AJAX endpoints completos. Control planillas únicas (payroll_id). 6 vistas AdminLTE. Validaciones robustas solapamientos/saldos/elegibilidad.

### **Multitenancy Empresarial** (V3.5.8-10 + PRs #104-110) - **85% COMPLETADO**
TenantResolver (244 líneas) con detección automática por URL/sesión/código. TenantStorage + TenantMigrationSystem robustos. Login multi-tenant por license_key/RUC/ID/slug. PostgreSQL support (MySQL/PostgreSQL dinámico). Super Admin System (is_system_admin + privileges). Wizard creación empresas funcional (11 pasos debugging). License dropdown navbar tiempo real. Credenciales encriptadas (db_pass_enc). Aislamiento BD completo. Core Concepts Synchronization automática. Migraciones multi-tenant robustas (splitSqlStatements parser). **Pendiente**: Panel admin backoffice CRUD tenants.

### **Manual Concepts** (PRs #99-100) - **100% COMPLETADO**
Módulo completo CRUD conceptos manuales por empleado. Integración planillas durante regeneración. DataTables parametrizado. Migraciones tenant directory.

### **Super Admin System** (PRs #106-107) - **100% COMPLETADO**
Column is_system_admin en tabla admin. Privileges implementation + access control + UI elements. License expiration check integrado.

### **PostgreSQL Support** (PRs #109-110) - **100% COMPLETADO**
Conexión dinámica MySQL/PostgreSQL. Configuración .env.pgsql.example. Migration scripts con connection handling actualizado.

### **Loan System Enhancements** (PR #98 + commits) - **100% COMPLETADO**
Status 'pagado' nuevo + completion logic. Vista completa préstamos. Creditor association controls. Special handling cuotas préstamos en payroll processing.

### **Employee Documents/Work Documents** (PRs #95-97) - **100% COMPLETADO**
Work document generators PDF/Word export flow. Listado módulo + templates + navigation shortcuts. Employee file correlative preview.

### **XIII Mes in PDFs** (PRs #90-94) - **100% COMPLETADO**
Acumulados XIII Mes integrados en reportes PDF planillas. Payroll detail breakdown por período completo.

### **Attendance Enhancements** (PRs #108-113) - **100% COMPLETADO**
Timezone handling UTC → Local (3 commits). Employee identification document ID + email fallback. Sync validation end-of-day processing. Attendance bonus flag + payroll variables. Records processing step en sync cron.

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

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.

## ⚙️ **REGLAS DE DESARROLLO Y MODIFICACIÓN DE CÓDIGO**
**CRÍTICO - SEGUIR ESTRICTAMENTE PARA EVITAR ROMPER FUNCIONALIDADES**

### **Principio Fundamental: Mínima Intervención**
**"Hacer SOLO lo solicitado, sin tocar sistemas que ya funcionan"**

### **Reglas Obligatorias:**

1. **NO MODIFICAR archivos core/helpers sin necesidad absoluta**
   - ❌ PROHIBIDO: Cambiar `JavaScriptHelper`, `UrlHelper`, `Database`, `Router`, etc. sin pedirlo explícitamente
   - ❌ PROHIBIDO: Modificar sistemas de rutas, configuraciones globales, o arquitectura base
   - ✅ PERMITIDO: Solo si el usuario explícitamente solicita "modifica el helper X"

2. **RESPETAR convenciones y estándares del proyecto**
   - ✅ Usar `window.APP_CONFIG` (ya establecido en el proyecto) - NUNCA cambiar a `appConfig`
   - ✅ Seguir patrones de nombres de variables y métodos existentes
   - ✅ Mantener estructura de carpetas y archivos actual
   - ⚠️ Si necesitas cambiar una convención, **PREGUNTAR PRIMERO**

3. **ALCANCE LIMITADO de modificaciones**
   - ✅ Si piden "agrega datepicker al formulario X" → Solo agregar datepicker
   - ❌ NO agregar validaciones, rutas AJAX, o refactorizar lógica sin pedirlo
   - ❌ NO crear archivos JavaScript complejos si el formulario ya funciona con lógica existente
   - ✅ Reutilizar lógica existente en lugar de crear nueva

4. **VERIFICAR antes de modificar**
   - ✅ Usar `Grep` para buscar cómo se usa actualmente un patrón (ej: `window.APP_CONFIG`)
   - ✅ Leer archivos relacionados antes de hacer cambios
   - ❌ NO asumir que tu implementación es mejor que la existente
   - ⚠️ Si tienes duda, **PREGUNTAR AL USUARIO**

5. **PREFERIR ediciones mínimas sobre refactorizaciones**
   - ✅ Agregar `autocomplete="off"` en campos específicos
   - ❌ NO refactorizar todo el formulario "para mejorarlo"
   - ❌ NO cambiar arquitectura de routing/helpers "para hacerlo más limpio"

### **Casos de Error Comunes (APRENDER DE ESTOS):**

**❌ ERROR - Modificación Excesiva:**
```
Usuario: "Agrega datepicker y quita autocomplete"
Claude: [Modifica JavaScriptHelper + crea sistema de rutas + refactoriza form.php]
Resultado: Sistema de rutas roto, error 404
```

**✅ CORRECTO - Modificación Precisa:**
```
Usuario: "Agrega datepicker y quita autocomplete"
Claude: [Agrega autocomplete="off" + crea JS simple con datepicker]
Resultado: Funciona perfectamente, nada roto
```

### **Preguntas Obligatorias Antes de Modificar:**

1. **¿El usuario pidió modificar este archivo específicamente?** → Si NO, no lo modifiques
2. **¿Este cambio puede romper funcionalidad existente?** → Si SÍ, pregunta primero
3. **¿Estoy cambiando una convención del proyecto?** → Si SÍ, pregunta primero
4. **¿Puedo lograr el objetivo sin tocar archivos core?** → Si SÍ, hazlo así

### **En caso de duda: PREGUNTAR > ASUMIR**

Si no estás 100% seguro de que una modificación es necesaria o segura, **pregunta al usuario** antes de proceder.

## 📝 **POLÍTICA DE DOCUMENTACIÓN**
**CRÍTICO - SEGUIR ESTRICTAMENTE**:

1. **NUNCA crear archivos de documentación (.md) automáticamente** cuando se implementa un cambio
2. **SOLO crear documentación cuando el usuario lo solicite explícitamente** con frases como:
   - "crea un documento de..."
   - "documenta esto en..."
   - "genera documentación..."
   - "escribe un .md con..."
3. **IMPLEMENTAR CAMBIOS DIRECTAMENTE** sin documentación adicional a menos que se pida
4. **ACTUALIZAR CLAUDE.md** solo cuando sea un cambio de versión mayor o característica significativa

**Ejemplos**:
- ❌ Usuario: "cambia la función X" → NO crear documento de cambios automáticamente
- ✅ Usuario: "cambia la función X" → Solo implementar el cambio
- ✅ Usuario: "cambia la función X y documenta el cambio" → Implementar + crear documento

**Razón**: Evitar saturación de archivos .md innecesarios en el proyecto.

## 🚨 **FLUJO OBLIGATORIO PARA ANÁLISIS**
**MANDATORY ANALYSIS WORKFLOW - NO EXCEPTIONS**

Cuando el usuario solicite cualquier tipo de análisis (usando palabras como "analiza", "analyze", "evalúa", "estudia", etc.):

1. **ANÁLISIS**: Realizar investigación y análisis completo
2. **PRESENTACIÓN**: Presentar opciones, pros/contras, recomendaciones
3. **ESPERAR APROBACIÓN**: NO proceder hasta recibir confirmación explícita del usuario
4. **IMPLEMENTACIÓN**: Solo si se solicita específicamente

**PROHIBIDO**: Implementar automáticamente después de análisis sin aprobación explícita.
**OBLIGATORIO**: Siempre preguntar "¿Proceder con la implementación de [opción recomendada]?" antes de cualquier implementación.

## 📁 **ESTRUCTURA DE DOCUMENTACIÓN**
- **CLAUDE.md**: Memoria principal del proyecto (raíz) - Solo info crítica + enlaces a changelogs
- **documentation/**: Directorio para archivos de documentación del proyecto
  - **ROADMAP.md**: Hoja de ruta y planificación
  - **CHANGELOG.md**: Índice principal de versiones con enlaces
  - **changelog/**: Directorio de changelogs individuales por versión
    - **v3.5.19.md**: Módulo Campos Adicionales Personalizados (16-Ene-2026)
    - **v3.5.18.md**: Fix TypeError insertConceptDetail (15-Ene-2026)
    - **v3.5.17.md**: Bug Fixes + UX Improvements (29-Dic-2025)
    - **v3.5.16.md**: Expedientes Empleados + Migraciones Multi-Tenant (29-Dic-2025)
    - **v3.5.15.md**: UNIDAD Dinámica en Fórmulas (28-Dic-2025)
    - **v3.5.14.md**: Campo UNIDAD en Planilla Detalle (28-Dic-2025)
    - **v3.5.13.md**: Sistema Permisos Granulares + Liquidaciones Dinámicas (02-Dic-2025)
    - **v3.5.12.md**: Acumulados Excel Export + Bug Fixes (01-Dic-2025)
    - **v3.5.1-v3.5.11.md**: Versiones anteriores con detalles completos
    - **v3.4.0-v3.4.8.md**: API Asistencias + Calculadores (Oct-2025)
    - **README.md**: Guía de estructura y convenciones
  - **TODO.md**: Lista de tareas pendientes
- **docs/**: Directorio de AdminLTE (NO MODIFICAR)

IMPORTANTE: Todos los archivos de documentación del proyecto deben guardarse en `/documentation` para no confundirlos con `/docs` que pertenece a la plantilla AdminLTE.

### **Sistema de Changelogs Modularizados (V3.4.1+)**
A partir de la versión 3.4.1, cada versión tiene su propio archivo en `documentation/changelog/`:
- **Propósito**: Evitar que CLAUDE.md y CHANGELOG.md se vuelvan demasiado extensos
- **Formato**: `vX.Y.Z.md` (ejemplo: `v3.5.16.md`)
- **Índice**: `CHANGELOG.md` sirve como índice con resumen + enlaces a archivos individuales
- **CLAUDE.md**: Solo menciona últimas 5 versiones con link a changelog detallado
- **Template**: Copiar estructura de versiones existentes para nuevas versiones
- **Convenciones**: Incluir fecha, tipo, componentes, estadísticas y referencias cruzadas

---

**Última Actualización**: 29 de Enero, 2026 (**REVISIÓN COMPLETA - 230+ commits**)
**Sistema**: Planillas MVC v3.5.19
**Progreso Global**: Core 100% | Calendario 100% | **Vacaciones 90% (+45%)** | **Multitenancy 85% (+40%)** | **Motor Fórmulas 95%** | API Asistencias 92% | Liquidaciones 100% | Seguridad 100% | Employee Import 100% | Acumulados Export 100% | Permisos Granular 100% | Employee Files 100% | Campos Adicionales 100% | **PostgreSQL 100%** | **Super Admin 100%** | **Manual Concepts 100%** | **Loan System 100%**
