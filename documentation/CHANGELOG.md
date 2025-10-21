# 📋 CHANGELOG - Sistema de Planillas MVC

## 📖 **Índice de Versiones**

Este archivo sirve como índice principal para el historial de cambios del sistema. Cada versión tiene su propio archivo detallado en el directorio `changelog/`.

---

## 🆕 **Últimas Versiones**

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

**Última Actualización**: 16 de Octubre, 2025
**Sistema**: Planillas MVC v3.4.4
**Progreso Global**: Core 100% | Calendario 100% | API Asistencias 60%
