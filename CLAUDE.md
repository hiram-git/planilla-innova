# 🤖 CLAUDE MEMORY - Sistema de Planillas MVC

## 📝 **Estado Actual - V3.4.1 Preparación Cálculos Asistencias**
- **Fecha**: 10 de Octubre, 2025
- **Estado**: ✅ **SISTEMA EMPRESARIAL 100% + CALENDARIO + API ASISTENCIAS + BD CÁLCULOS**
- **Versión**: 3.4.1 - Migraciones BD Subfase 7.2 completadas (attendance_calculations + attendance_absence_log + employee_payroll_salaries)

## 🎯 **Sistema**
Plataforma empresarial de planillas con legislación panameña, acumulados automáticos XIII Mes, reportes PDF profesionales con firmas, y estructura organizacional completa.

## ✅ **Completado (100%)**
- ✅ **Core MVC**: Router + Database + Middleware + CSRF + Roles
- ✅ **Planillas**: Procesamiento + PDF + Estados + Acumulados automáticos
- ✅ **XIII Mes Panamá**: (Salario Anual ÷ 3) - Descuentos legislación + Períodos Trimestrales
- ✅ **Reportes PDF**: Planillas + Comprobantes + Logos empresariales + Firmas
- ✅ **Módulo Organizacional**: CRUD completo + jerarquías + integración empleados
- ✅ **Sistema Logos**: Dropzone.js + triple logo + reportes PDF
- ✅ **JavaScript Modular**: BaseModule + ES6 + JavaScriptHelper
- ✅ **Motor Fórmulas V2**: INIPERIODO/FINPERIODO dinámico + tipo_acumulado + regex optimizado
- ✅ **Custom Query Builder**: Interfaz fluente + adaptadores multi-BD + 24% mejora rendimiento
- ✅ **Sistema Liquidaciones**: PayrollLiquidationController + cálculos legislación panameña completos
- ✅ **Planillas de Liquidación**: Generación automática + vistas separadas + periodo 11 meses
- ✅ **Correcciones Cálculos**: Fix ASIGNACION vs DEDUCCION + totales corregidos + JOIN mejorado
- ✅ **Sistema Separación Empleados**: /panel/employees activos + /terminated dados de baja + JavaScript modular + URLs dinámicas
- ✅ **AJAX DataTables**: Server-side paginación + búsqueda + performance optimizada
- ✅ **Duplicación Conceptos**: Modal AJAX + validación CSRF + redirect automático + event handling robusto
- ✅ **Modal Refresh**: Actualización sin recargar página + auto-refresh inteligente
- ✅ **Modal UX Optimizada**: Eliminación botones innecesarios + información contextual planilla (ID + descripción)
- ✅ **Cache-Busting**: Sistema SSIIHH automático para JavaScript + no más Ctrl+F5
- ✅ **Función CONCEPTO()**: Reutilización cálculos entre conceptos + protección recursión
- ✅ **Días Preaviso Editables**: Campo modificable desde interfaz + AJAX + validaciones + historial
- ✅ **Iconos Estado Planillas**: FontAwesome icons + tooltips + centrado perfecto
- ✅ **Responsive 1024px**: Breakpoint optimizado mini laptops + columnas esenciales
- ✅ **Vista Empleado Mejorada**: Información terminación completa + callouts AdminLTE + badges visuales
- ✅ **Validaciones Críticas**: Fechas contratos + períodos planillas + empleados activos durante procesamiento
- ✅ **Liquidaciones Mejoradas**: Período detallado (días, meses, años) + cálculos precisos días laborables + AJAX dinámico
- ✅ **Fixes Críticos CSRF**: Funciones csrf_token() y csrf_hash() agregadas + inclusión helpers.php + layout admin restaurado
- ✅ **Cálculos Legales Automáticos**: Prima antigüedad + indemnización actualizadas en tiempo real al cambiar fechas
- ✅ **Bugfixes Acumulados**: Corrección campos BD + undefined variables + enlaces menú
- ✅ **UI/UX Improvements**: Callouts AdminLTE + info boxes dinámicos + indicadores tiempo real
- ✅ **Filtros Avanzados Acumulados**: Tipo acumulado + año "todos" + auto-submit empleado + UX optimizada
- ✅ **Simplificación Lógica**: Campo `incluir_en_acumulado` eliminado + migración automática + código limpio
- ✅ **PHP 8+ Compatibility**: Deprecated warnings resueltos + cast explícito `(int) round()`
- ✅ **Variables Dinámicas**: DIAS_PREAVISO usa BD real + cálculo períodos liquidación corregido
- ✅ **Sistema Notificaciones Toastr**: Métodos Controller base + integración automática + categorización
- ✅ **Análisis Calendario Empresarial**: Marco legal panameño + requerimientos + estructura datos
- ✅ **XIII Mes Trimestral**: XIIIMesPeriodoTrimestralCalculator + períodos trimestrales + variables dinámicas + scripts testing/deploy + vista mejorada
- ✅ **Employee Import Fixes**: Foreign key constraints corregidos + cleanForeignKeyNulls() + PHP 8+ compatible + validaciones robustas
- ✅ **Dashboard Filtros V3.3.11**: Filtrado completo por tipo planilla + integración sessionStorage + sincronización navbar + tarjetas reordenadas
- ✅ **CSRF Security Fix V3.3.12**: Eliminación duplicación código CSRF + AuthMiddleware::validateCSRF() usa Security::validateToken() + código unificado
- ✅ **Reportes Dropdown V3.3.13**: Dropdown reportes en listado planillas + 5 reportes acceso rápido (PDF, Excel, Comprobantes, Acreedores, Informe 03) + iconos colores + nueva pestaña
- ✅ **Sidebar Toggle Fix V3.3.14**: Fix completo sidebar AdminLTE + manual toggle + stopImmediatePropagation() + desactivación plugin Treeview + expand/collapse perfecto
- ✅ **Módulo Acumulados Refactorizado V3.3.15**: byConcepto y byType completamente refactorizados + agrupación dinámica (empleado/mes/año/planilla) + filtros avanzados + cards visuales + Select2 + DataTables
- ✅ **Sidebar AdminLTE Nativo V3.3.16**: Refactorización completa sidebar + estructura multilevel nativa AdminLTE + detección rutas corregida (base path) + iconos originales restaurados + clases active/menu-open funcionando + JavaScript limpio sin interferencias + soporte subdirectorios
- ✅ **Acumulados ByEmployee Cards V3.3.17**: Vista byEmployee transformada a cards agrupados + opciones groupBy (tipo_acumulado/mes/planilla) + integración filtro tipo_planilla + colors dinámicos + tabla detalle colapsable + info-box total general + Select2 + DataTables + fixes PHP 8+ htmlspecialchars null values
- ✅ **Fixes Acumulados + Página 404 V3.3.18**: Dropdowns tipo_acumulado con descripciones (getTiposAcumulados refactorizado) + optgroups byConcepto corregidos (Asignaciones/Deducciones/Patronales) + badge PATRONAL color info + mes octubre corregido + Vista error 404 profesional AdminLTE completa
- ✅ **Roadmap Actualizado V3.3.19**: ISR PANAMÁ reemplazado por FASE 7 Integración API Marcaciones y Asistencias + 5 subfases planificadas (API Externa, Cálculos Avanzados, Legislación Panamá, Integración Planillas, Interfaz/Reportes) + documentación completa CLAUDE.md + ROADMAP.md actualizado + hitos Q1 2026
- ✅ **Múltiples Tipos de Planilla V3.3.20**: Empleados ahora pueden pertenecer a múltiples tipos de planilla + migración DB tipo_planilla_id INT→VARCHAR(255) + Select2 múltiple en create/edit + implode/explode conversión array↔string + FIND_IN_SET() queries en Employee/Acumulado/Attendance models + vista show.php muestra badges múltiples + filtros Dashboard/Acumulados actualizados + tabla backup rollback disponible
- ✅ **Calendario Empresarial V3.3.21**: FASE 4 Subfases 4.1-4.3 completadas (75% total) + tabla business_calendar con 411 registros (feriados Panamá 2024-2025 + fines semana + días laborables) + BusinessCalendar model completo (270 líneas, 14 métodos) + BusinessCalendarController CRUD + vistas index.php/calendar.php + FullCalendar.js 6.1.8 integration + sidebar link + rutas registradas + API AJAX getWorkingDays() + estadísticas visuales + modal agregar días especiales + DataTables listado
- ✅ **Inicialización Automática Calendario V3.3.22**: Fix Security namespace (App\Core\Security) + Script CLI fill_business_calendar_2025.php standalone + Método BusinessCalendar->initializeYear($year) con 85 líneas + Interfaz web botón "Inicializar Año" + Modal selector años (2024-2030+) + Ruta POST /initializeYear + Validaciones rango + CSRF + Testing script 2026 exitoso (365 días) + Mantiene feriados existentes + Genera días laborables/fines semana automáticamente + Mensajes detallados inserted/skipped/total
- ✅ **Integración API Asistencias Base44 V3.4.0**: Subfase 7.1 completada + Base44ApiClient (367 líneas) con retry logic + AttendanceSyncService (510 líneas) sincronización automática + 3 tablas BD (api_config, raw_data, sync_log) + AttendanceApiConfigController con interfaz AdminLTE completa + Cron job sincronización cada 15 min + Base44WebhookController para notificaciones tiempo real + Ruta /webhooks/base44/attendance + Logging exhaustivo + Estadísticas visuales + Endpoint test conexión

## 📄 **Reportes PDF Empresariales**
- **Planillas**: Layout horizontal + logos empresariales + firmas profesionales
- **Comprobantes**: Individuales por empleado + conceptos detallados + logos
- **Triple Logo System**: Logo principal + logo izquierdo reportes + logo derecho reportes
- **Firmas**: Configurables desde BD companies (4 niveles de firma)
- **PDFReportController**: Controlador específico para generación reportes

## 💰 **Sistema XIII Mes Trimestral - V3.3.9**
- **XIIIMesPeriodoTrimestralCalculator**: Clase calculadora legislación panameña períodos trimestrales
- **Períodos Automáticos**: P1 (Dic16→Abr15), P2 (Abr16→Ago15), P3 (Ago16→Dic15)
- **Variables Dinámicas**: INICIO_PERIODO_XIII + FIN_PERIODO_XIII + PERIODO_XIII_NUMERO + PERIODO_XIII_ESTADO
- **Fórmula Corregida LIQ006**: `ACUMULADOS("SALARIO_BASE", FICHA, INICIO_PERIODO_XIII, FIN_PERIODO_XIII)/4`
- **Integración Automática**: PlanillaConceptCalculator procesa variables dinámicamente
- **Scripts Completos**: test_xiii_mes_trimestral.php (4 módulos testing) + deploy_xiii_mes_trimestral.php (backup automático)
- **Vista Mejorada**: Layout estilo cálculo liquidación + routing corregido + información empleado + marco legal panameño

## 📥 **Sistema Employee Import - V3.3.10**
- **EmployeeImportController**: Importación masiva empleados desde Excel + validaciones robustas
- **Foreign Key Constraint Fix**: SQLSTATE[23000] error resuelto + cleanForeignKeyNulls() method
- **PHP 8+ Compatibility**: safeTrim() + null coalescing operator (??) + deprecated warnings corregidos
- **Output Buffering**: ob_start()/ob_end_clean() previene "headers already sent" errors
- **Enhanced Date Formatting**: formatDate() múltiples formatos (YYYY-MM-DD, DD/MM/YYYY, Excel serial)
- **Conditional Validation**: position_id opcional empresas privadas + foreign keys solo validados si proporcionados
- **Excel Template**: Headers "(Ver Ref)" + hoja Referencias con IDs válidos + ejemplos datos
- **Error Messages Enhanced**: Mensajes específicos con columna exacta + referencia hoja (ej: "Columna J - Ver hoja Referencias")
- **Callouts AdminLTE**: UI mejorada con callout-success/danger/warning + integración visual completa
- **Debug Logging**: Logs detallados print_r() datos extraídos + validación errors tracking
- **Testing Suite**: test_employee_import_fix.php + test_empleados_simple.xlsx con IDs válidos BD

## 🏢 **Módulo Organizacional Completo**
- **OrganizationalController**: CRUD completo con create/edit/delete
- **Vistas Completas**: Index con organigrama visual + formularios create/edit
- **JavaScript Modular**: organizational/index.js, create.js, edit.js
- **Jerarquías Dinámicas**: Paths automáticos + validación ciclos
- **Integración Empleados**: Campo organigrama_id + foreign key + formularios

## 🎨 **Sistema Logos Empresariales**
- **Dropzone.js Integration**: Upload arrastrando archivos + preview dinámico
- **company/logos.js**: Módulo completo gestión logos con CSRF
- **Dynamic URLs**: Detección automática paths para upload/delete/preview
- **Security**: CSRF tokens + validaciones + preview en tiempo real

## 🏢 **Sistema Planillas de Liquidación**
- **Generación Automática**: Periodo 11 meses según Código de Trabajo Panamá
- **Integración Existente**: Reutiliza planilla_cabecera + planilla_detalle con frecuencia específica
- **Vistas Separadas**: /panel/liquidation/payrolls filtrada + vista detallada por empleado
- **Cálculos Corregidos**: Fix ASIGNACION vs DEDUCCION + totales netos correctos
- **Dashboard Completo**: Estadísticas + export CSV + PDF + navegación integrada
- **Flujo Completo**: Calcular → Generar Planilla → Ver Planillas → Detalle

## 🎨 **Sidebar AdminLTE Nativo - V3.3.16**
- **Problema Resuelto**: Sidebar con navegación multilevel que perdía estado y clases `active`
- **Causa Raíz**:
  - JavaScript manual interferente que desactivaba plugin AdminLTE Treeview
  - Detección de rutas fallaba en subdirectorios (no eliminaba `/planilla-innova`)
  - Event handlers conflictivos con comportamiento nativo

- **Refactorización Completa**:
  - **Estructura HTML Nativa**: HTML directo siguiendo patrón oficial AdminLTE multilevel
  - **Detección Rutas Corregida**: `getCurrentRoute()` detecta y elimina base path automáticamente
  - **Iconos Específicos**: Restaurados todos los iconos originales (fas fa-list, fas fa-user-times, etc.)
  - **JavaScript Limpio**: Eliminado código que desactivaba AdminLTE + event handlers manuales removidos

- **Características**:
  - ✅ `data-widget="treeview"` en `<ul class="nav nav-sidebar">`
  - ✅ `data-accordion="false"` para múltiples menús abiertos simultáneamente
  - ✅ Clase `menu-open` aplicada automáticamente por PHP según ruta activa
  - ✅ Clase `active` en enlaces según `isActive()`
  - ✅ Soporte subdirectorios y root transparente
  - ✅ Comportamiento 100% AdminLTE nativo

- **Archivos Modificados**:
  - `app/Views/components/sidebar.php`: Refactorización completa + getCurrentRoute() mejorado
  - `app/Views/components/sidebar_anterior.php`: Respaldo sidebar anterior
  - `app/Views/layouts/admin.php`: Limpieza JavaScript interferente (líneas 497-500)

## 📊 **Módulo Acumulados Refactorizado - V3.3.15 + V3.3.17**

### **Vista byEmployee() V3.3.17 - Cards Agrupados**
- **Problema Resuelto**: Vista mostraba solo tabla simple sin agrupaciones visuales claras
- **Transformación Completa**: Diseño cards agrupados estilo byConcepto con opciones flexibles
- **Características**:
  - **Filtros Mejorados**: Empleado (Select2) + Año + Mes + Tipo Acumulado + Tipo Planilla + Agrupar por
  - **Opciones Agrupación**: 3 modos dinámicos (tipo_acumulado, mes, planilla)
  - **Cards Visuales AdminLTE**:
    - Small-box con colores dinámicos (success/danger para tipo_acumulado según tipo_concepto, info para otros)
    - Iconos FontAwesome específicos (fas fa-coins, fas fa-calendar-alt, fas fa-file-invoice-dollar)
    - Porcentaje visual del total general
    - Indicadores total planillas y conceptos incluidos
    - Fechas período para agrupación por planilla
  - **Info-Box Total General**: Progress bar + contador grupos
  - **Tabla Detalle Colapsable**: DataTables con todos los registros + ordenamiento año/mes desc + paginación 25
  - **Integración Filtro Tipo Planilla**: JavaScript común lee sessionStorage y filtra empleados automáticamente
- **Métodos Controller**:
  - `getAcumuladosAgrupadosByEmployee()`: SQL dinámico para agrupación (líneas 917-1008)
  - `getAcumuladosDetalleByEmployee()`: Obtiene registros detallados para tabla

### **Vista byConcepto() V3.3.15 - Mejorada**
- **Filtros**: Concepto (required) + Año + Mes + Agrupar por (empleado/planilla/año)
- **Cards Visuales**: Small-box con totales agrupados + porcentajes + color por tipo_concepto
- **Total General**: Info-box con resumen completo + contadores
- **Tabla Detallada**: DataTables colapsada con todos los registros + exportar CSV
- **Select2**: Dropdown conceptos con optgroups (Asignaciones/Deducciones)

### **Vista byType() V3.3.15 - Mejorada**
- **Filtros**: Tipo acumulado (required) + Año + Mes + Agrupar por (empleado/mes/año)
- **Cards Visuales**: Small-box bg-info con totales agrupados + porcentajes
- **Total General**: Info-box con resumen completo + contadores
- **Tabla Detallada**: DataTables colapsada con todos los registros + exportar CSV
- **Select2**: Dropdown tipos de acumulados activos

### **Métodos Controller Acumulados**
- `getTiposAcumuladosForFilter()`: Obtiene tipos disponibles desde BD
  - `getAcumuladosByTipoAcumulado()`: Filtra acumulados por tipo + año + mes
  - `getAcumuladosAgrupadosByTipo()`: Agrupa por empleado/mes/año con totales
  - `getConceptosForFilter()`: Obtiene conceptos con acumulados (fix campo activo removido)
  - `getAcumuladosAgrupadosByConcepto()`: Agrupa por empleado/planilla/año con totales

- **Fixes Aplicados**:
  - ✅ Eliminado `WHERE c.activo = 1` en getConceptosForFilter() (columna no existe)
  - ✅ Filtros expandidos por defecto (removed collapsed-card class)
  - ✅ Rutas corregidas en formularios (byType vs byConcepto separados)
  - ✅ Integración completa Select2 + DataTables Spanish

## 📊 **Dashboard Ejecutivo con Filtros - V3.3.11**
- **Filtrado por Tipo Planilla**: Sistema completo filtrado todas las métricas del dashboard
- **Integración SessionStorage**: Lee automáticamente tipo planilla seleccionado desde navbar
- **Sincronización Navbar**: Evento `payrollTypeChanged` actualiza dashboard en tiempo real
- **Tarjetas Reordenadas**: Total Empleados → Colaboradores Activos → Puntualidad Mensual → Presentes Hoy
- **Indicadores Visuales**: Badge "Filtrado" en tarjetas + alerta informativa con tipo seleccionado
- **Modelos Actualizados**:
  - `Acumulado.php`: Nuevo modelo con métodos `getAcumuladosByTipoAndYear()`, `getEmployeesWithAcumulados()`, `getAvailableYears()`
  - `Employee.php`: Método `getEmployeesByTipoPlanilla($tipoPlanillaId)`
  - `Attendance.php`: Parámetro `$tipoPlanillaId` en `getAttendanceByDateRange()`
- **Métricas Filtradas**:
  - Total empleados por tipo planilla
  - Colaboradores activos últimos 30 días
  - Asistencia hoy (presentes/ausentes)
  - Puntualidad mensual
  - Gráfica asistencia (últimos 30 días)
  - Acumulados por tipo
- **JavaScript Limpio**: EventListeners + URLSearchParams API + sin formularios redundantes
- **Tabs Alineados**: CSS `justify-content: flex-end` para tabs a la derecha
- **Compatible URLs Directas**: `/panel/dashboard?tipo_planilla=1` funciona correctamente

## 👥 **Sistema Separación Empleados**
- **Vista Activos**: /panel/employees muestra únicamente empleados situacion_id = 1
- **Vista Terminados**: /panel/employees/terminated para empleados dados de baja
- **Controller Methods**: terminated() + terminated_datatables_ajax() + filtro datatablesAjax()
- **Navegación Cruzada**: Enlaces alternar entre vistas + breadcrumbs contextuales
- **Export Completo**: Botones Excel/PDF configurados para ambas vistas
- **JavaScript Modular**: assets/javascript/modules/employees/terminated.js con URLs dinámicas
- **Router Config**: App.php con rutas terminated + terminated-datatables-ajax registradas
- **SQL Optimizado**: getFilteredEmployeesCount() con JOIN employee_terminations consistente
- **Arquitectura Limpia**: Sin hardcode URLs + compatible producción + modular + responsive

## 👤 **Vista Empleado Mejorada**
- **Información Terminación**: Sección completa datos terminación con badges visuales
- **JOIN Employee Terminations**: Modelo Employee con campos termination_date/type/reason/status
- **Callouts AdminLTE**: Uso callout-warning en lugar de alerts básicos + estilos personalizados
- **Display Condicional**: Solo mostrar información terminación si empleado tiene termination_date
- **Badges Estado**: Colores específicos para cada tipo terminación (Despido, Renuncia, Mutuo Acuerdo)
- **Fix Contratos**: Validación robusta campos fecha vacíos con trim() + empty() + null fallback
- **Fix Parse Error**: Reestructuración código PHP vista show.php sin syntax errors

## ⚡ **AJAX DataTables + Performance**
- **Server-Side DataTables**: Paginación eficiente con carga bajo demanda + búsqueda optimizada
- **PayrollController@datatablesAjax()**: Endpoint completo con filtros, ordenamiento y conteo total
- **Modelo Optimizado**: getTotalCount() + getFilteredCount() + getAllWithStats() mejorado para paginación
- **Modal Refresh Inteligente**: Actualización automática sin recargar página después procesar/reprocesar
- **Cache-Busting SSIIHH**: Sistema automático formato Segundos-Minutos-Horas para JavaScript
- **Headers AJAX**: X-Requested-With + error handling + redirección automática sesión expirada
- **URLs Dinámicas**: Sin hardcode para compatibilidad producción + debugging completo

## 🧮 **Función CONCEPTO() + Motor Fórmulas Avanzado**
- **Sintaxis Flexible**: `CONCEPTO("LIQ005")` o `CONCEPTO(LIQ005)` - ambas válidas
- **Reutilización Cálculos**: Evita duplicar fórmulas largas entre conceptos relacionados
- **Protección Recursión**: Detección automática bucles infinitos + logging de advertencias
- **Casos Reales**: LIQ008 (`CONCEPTO("LIQ005") * 0.0975`) para ISR sobre vacaciones
- **37 Conceptos Cargados**: Sistema completo con referencias anidadas funcionales
- **Zero Values Handling**: Retorna 0 para conceptos no encontrados + logging inteligente

## 📱 **UX/UI Optimizada + Responsive 1024px**
- **Iconos Estado Planillas**: FontAwesome en lugar de texto truncado (PEND → ⏰, PROC → ✅, CERR → 🔒, ANUL → ❌)
- **Tooltips Informativos**: Hover muestra nombre completo estado + centrado perfecto
- **Breakpoint 1024px**: d-xl-table-cell para mini laptops + columnas esenciales siempre visibles
- **Días Preaviso Editables**: Campo input numérico en /panel/liquidation/preview/{id}
- **AJAX Validado**: Endpoint update-notice-days + validaciones rango 0-365 + estados permitidos
- **Historial Completo**: Registro liquidation_history + flag needs_recalculation automático
- **SweetAlert2 UX**: Confirmaciones + opción recálculo inmediato + restauración en errores
- **Auto-Refresh**: Delay inteligente 2 segundos después completar procesamiento + botones inteligentes

## ✅ **Validaciones Críticas**
- **Validación Períodos Planillas**: Solo procesar empleados activos durante período planilla específico
- **SQL Mejorada**: WHERE con validaciones fecha_ingreso <= período_fin AND termination_date >= período_inicio
- **Mensajes Error Descriptivos**: Incluyen período específico + sugerencias verificación fechas
- **Logging Trazabilidad**: Conteo empleados antes/después validación + debugging información

## ⏰ **MÓDULO API MARCACIONES Y ASISTENCIAS - EN DESARROLLO**

### **Objetivo General**
Integración completa con API externa de marcaciones para control automatizado de asistencias, cálculo de horas trabajadas, y generación automática de conceptos en planillas según legislación panameña.

### **Estado Actual**: 🔵 Subfase 7.2 EN PROGRESO - 25% (Migraciones BD completadas)

### **Subfase 7.1: Integración API Externa** ✅ **COMPLETADA (9-Oct-2025)**
**Objetivo**: Establecer conexión robusta con API de marcaciones y sincronización bidireccional de datos.

**Componentes Implementados**:
- ✅ **Base44ApiClient** (367 líneas): Cliente HTTP completo con cURL + retry logic (3 intentos) + backoff exponencial + timeout 30s
- ✅ **AttendanceSyncService** (510 líneas): Sincronización completa/incremental/por empleado + detección duplicados + manejo conflictos
- ✅ **AttendanceApiConfig Model** (240 líneas): CRUD configuración + validaciones + estadísticas sync + shouldSync() logic
- ✅ **AttendanceApiConfigController** (300+ líneas): 9 endpoints (save, test, sync-now, enable/disable, logs, clean)
- ✅ **Vista AdminLTE Completa** (500+ líneas): Formulario config + estadísticas cards + panel control + tabla logs + modales
- ✅ **Cron Job** (130 líneas): Script CLI sincronización automática cada 15 minutos + validaciones + estadísticas
- ✅ **Base44WebhookController** (220 líneas): Endpoint público /webhooks/base44/attendance + validación firma HMAC + logging
- ✅ **Sidebar Integration**: Enlace "Configuración API" en menú Asistencia + icono fas fa-plug

**Tablas BD Creadas**:
- ✅ `attendance_api_config`: Configuración API (provider, key, app_id, url, sync_enabled, interval, webhook)
- ✅ `attendance_raw_data`: Backup datos crudos API (external_id, entity_type, raw_json, processed, sync_batch_id)
- ✅ `attendance_sync_log`: Historial sincronizaciones (type, stats, duration, status, errors, triggered_by)

**Rutas Implementadas**:
- ✅ `GET /panel/attendance-api-config`: Vista configuración
- ✅ `POST /panel/attendance-api-config/save`: Guardar config
- ✅ `POST /panel/attendance-api-config/test-connection`: Test API (AJAX)
- ✅ `POST /panel/attendance-api-config/sync-now`: Sincronizar manual
- ✅ `POST /webhooks/base44/attendance`: Webhook receiver (público)

**Estadísticas**: ~2,417 líneas código | 12 archivos nuevos | 2 archivos modificados

---

### **Subfase 7.2: Cálculos Avanzados de Asistencias** 🔵 **EN PROGRESO (25%)**
**Objetivo**: Procesar marcaciones y calcular métricas de asistencia según reglas empresariales.

#### ✅ **Migraciones BD COMPLETADAS (10-Oct-2025)**

**Tabla `attendance_calculations`** (Migración: `2025_10_10_attendance_calculations.sql` - 145 líneas):
- **Referencias**: attendance_id, employee_id, schedule_id (FKs)
- **Marcaciones**: time_in, time_out, scheduled_time_in, scheduled_time_out
- **Horas Trabajadas**: total_hours, regular_hours, overtime_hours, overtime_25_hours, overtime_50_hours, night_hours, holiday_hours
- **Tardanzas**: tardiness_minutes, is_late, early_departure_minutes
- **Ausencias**: is_absent, absence_type (JUSTIFIED/UNJUSTIFIED/UNKNOWN)
- **Tipo Día**: is_working_day, is_holiday, is_weekend, day_type
- **Métricas**: is_perfect_attendance, punctuality_score (0-100)
- **Almuerzo**: lunch_time_minutes (descuento configurable)
- **Auditoría**: calculation_version, calculated_at, recalculated_at
- **Detalles**: notes, calculation_details (JSON)
- **Índices**: unique_attendance_calc + 7 índices optimizados

**Tabla `attendance_absence_log`** (Migración: `2025_10_10_attendance_calculations.sql`):
- **Ausencia**: absence_date, absence_type (JUSTIFIED/UNJUSTIFIED/PENDING)
- **Justificación**: justified, justification_type (MEDICAL/PERMISSION/VACATION/OTHER)
- **Documentación**: justification_document (ruta), justification_notes
- **Día**: is_working_day, day_type
- **Detección**: detected_at, detection_method (AUTO/MANUAL/SYNC)
- **Resolución**: resolved, resolved_at, resolved_by (FK users)
- **Índices**: unique_employee_absence + 5 índices

**Tabla `employee_payroll_salaries`** (Migración: `2025_10_10_employee_payroll_salaries.sql` - 153 líneas):
- **Objetivo**: Salarios diferenciados por tipo de planilla con histórico de vigencias
- **Campos**: employee_id, tipo_planilla_id, sueldo_base, gastos_representacion
- **Vigencia**: fecha_inicio, fecha_fin (NULL = indefinido), is_active
- **Auditoría**: created_by, updated_by, notes
- **Script Migración Automática**: Migra datos desde `employees.sueldo_individual` usando COALESCE
- **Soporta**: Hasta 10 tipos de planilla por empleado
- **Estadísticas**: Reporte automático salarios por tipo (promedio/mínimo/máximo)
- **Índices**: unique_employee_payroll_active + 4 índices

**Estadísticas Migraciones**: 298 líneas SQL | 3 tablas | 14 Foreign Keys | 22 Índices

#### 🔲 **Componentes PHP Pendientes**:
- [ ] **AttendanceCalculator**: Clase principal de cálculos
- [ ] **WorkScheduleResolver**: Determina horario aplicable por empleado/día
- [ ] **OvertimeCalculator**: Cálculo horas extras (normales/nocturnas/feriados)
- [ ] **AbsenceDetector**: Identifica ausencias y clasifica tipos
- [ ] **Reports Generator**: Reportes diarios/semanales/mensuales de asistencias

#### 🔲 **Cálculos a Implementar**:
- **Marcaciones Perfectas**: Entrada/salida dentro de horario sin tardanzas
- **Horas Trabajadas**: Cálculo exacto entre entrada/salida con redondeos configurables
- **Ausencias**: Detección automática días sin marcación (justificadas/injustificadas)
- **Tardanzas**: Minutos de retraso con tolerancia configurable (ej: 5 min gratis)
- **Horas Extras**: Cálculo automático tiempo adicional después de jornada normal
- **Salidas Anticipadas**: Detección de salidas antes de hora programada
- **Tiempo de Almuerzo**: Descuento automático según políticas empresa

### **Fase 3: Consideraciones Legales Panamá**
**Objetivo**: Aplicar normativa laboral panameña al control de asistencias.

**Normativa Implementada**:
- **Jornada Ordinaria**: Máximo 8 horas diarias / 48 horas semanales (Código de Trabajo Art. 31)
- **Jornada Nocturna**: 6:00 PM - 6:00 AM con recargo 50% (Art. 38)
- **Horas Extras**: Primeras 3 horas +25%, siguientes +50% (Art. 39)
- **Día de Descanso**: Trabajo domingos/feriados con recargo 50% (Art. 48)
- **Tiempo de Comida**: Mínimo 30 minutos, no computable como trabajo (Art. 35)
- **Tolerancia Tardanzas**: Configuración por empresa según políticas internas
- **Faltas Graves**: 3+ ausencias injustificadas en mes = causal despido (Art. 213)

**Componentes**:
- **LegalComplianceChecker**: Validación cumplimiento normativa
- **OvertimeRateCalculator**: Cálculo tarifas según tipo de hora extra
- **WorkingDayClassifier**: Clasificación días (ordinarios, festivos, descanso)
- **Alerts System**: Notificaciones excesos jornada o incumplimientos

### **Fase 4: Integración con Generación de Planillas**
**Objetivo**: Automatizar inclusión de conceptos de asistencia en cálculo de planillas.

**Integración Implementada**:
- **Conceptos Automáticos**:
  - `HORAS_TRABAJADAS`: Horas normales trabajadas en el período
  - `HORAS_EXTRAS_25`: Primeras horas extras con recargo 25%
  - `HORAS_EXTRAS_50`: Horas extras adicionales con recargo 50%
  - `HORAS_NOCTURNAS`: Horas trabajadas en jornada nocturna +50%
  - `HORAS_DOMINICALES`: Horas trabajadas domingos/feriados +50%
  - `DESCUENTO_TARDANZAS`: Deducciones por tardanzas según política
  - `DESCUENTO_AUSENCIAS`: Descuentos por ausencias injustificadas
  - `BONO_PUNTUALIDAD`: Incentivo por asistencia perfecta

**Componentes**:
- **PayrollAttendanceIntegrator**: Integración asistencias → planillas
- **AttendanceConceptMapper**: Mapeo cálculos asistencias → conceptos planilla
- **PeriodAttendanceSummary**: Resumen asistencias por período de planilla
- **ValidationRules**: Validaciones cruce de datos asistencias/planillas

**Flujo de Trabajo**:
1. Al crear planilla, sistema consulta período de asistencias
2. Calcula automáticamente todos los conceptos relacionados
3. Genera registros en `planilla_detalle` con conceptos de asistencia
4. Permite revisión manual antes de procesar planilla
5. Genera reporte adjunto de asistencias incluidas

**Tablas BD**:
- `payroll_attendance_summary`: Resumen asistencias por empleado/período
- `attendance_concepts_mapping`: Mapeo tipos asistencia → conceptos planilla
- `payroll_attendance_details`: Detalle asistencias incluidas en cada planilla

### **Beneficios del Sistema**
✅ **Automatización Total**: Elimina carga manual de asistencias
✅ **Cumplimiento Legal**: Garantiza aplicación correcta legislación panameña
✅ **Transparencia**: Empleados pueden consultar sus asistencias en tiempo real
✅ **Auditoría Completa**: Registro detallado de todas las marcaciones y cálculos
✅ **Flexibilidad**: Reglas configurables por empresa y tipo de empleado
✅ **Precisión**: Cálculos exactos eliminan errores humanos
✅ **Reportes**: Dashboards y reportes ejecutivos de asistencias

## 🔧 **Stack Tecnológico**
- **Backend**: PHP 8.3 + MVC + MySQL (planilla_innova)
- **Frontend**: AdminLTE + Bootstrap 4 + JavaScript ES6 modular
- **Reportes**: TCPDF + diseño empresarial profesional
- **Estado**: Producción estable + arquitectura escalable

## 📅 **Calendario Empresarial Panamá - V3.3.22 (COMPLETADO)**

### ✅ **Subfases Completadas**:
- **Subfase 4.1 - Base de Datos** ✅ 100%
  - Tabla `business_calendar` con 731 registros (años 2024-2025 completos)
  - 13 feriados nacionales panameños 2024 + 15 feriados 2025
  - Tipos: LABORAL, NO_LABORAL, FERIADO, DUELO_NACIONAL, ESPECIAL
  - Estados: NORMAL, RECUPERABLE, MEDIO_DIA, HORARIO_ESPECIAL
  - Migración: `2025_09_22_1193_panama_business_calendar.sql`

- **Subfase 4.2 - BusinessCalendar Model** ✅ 100%
  - Modelo completo: `app/Models/BusinessCalendar.php` (355+ líneas)
  - Métodos core: getWorkingDaysBetween(), isWorkingDay(), getNextWorkingDay(), getPreviousWorkingDay()
  - Métodos avanzados: getMonthCalendar(), getHolidaysByYear(), addSpecialDay(), getCalendarStats()
  - **Nuevo**: initializeYear($year) - Inicialización automática años completos (85 líneas)
  - Fallback automático sin BD
  - Helper functions: getDayTypeColors(), getDayTypeIcons()

- **Subfase 4.3 - Interfaz Gestión** ✅ 100%
  - BusinessCalendarController completo con CRUD
  - Vista `index.php`: Listado feriados + estadísticas (4 small-boxes) + DataTables + modal agregar días
  - Vista `calendar.php`: FullCalendar.js 6.1.8 + modal detalles + leyenda colores
  - API AJAX: getWorkingDays() para consultas dinámicas
  - Rutas registradas en App.php: `panel/business-calendar`
  - Sidebar: Ubicado en **CONFIGURACIÓN → Administración** (después de Roles y Permisos)
  - Icono: `fas fa-calendar-check`

- **🆕 Subfase 4.3.1 - Inicialización Automática de Años** ✅ 100%
  - **Script CLI**: `database/scripts/fill_business_calendar_2025.php`
    - Ejecución: `php database/scripts/fill_business_calendar_2025.php`
    - Carga automática `.env` + conexión PDO directa
    - Generación completa año: días laborables + fines de semana
    - Mantiene feriados existentes sin duplicar
    - Reporte estadísticas detalladas al finalizar
  - **Interfaz Web**: Botón "Inicializar Año" en `/panel/business-calendar/listado`
    - Modal con selector de año (2024 hasta año actual + 5)
    - Método `BusinessCalendar->initializeYear($year)` (líneas 247-325)
    - Ruta: `POST /panel/business-calendar/initializeYear`
    - Validaciones: rango años + CSRF + prevención duplicados
    - Mensajes detallados: días insertados + omitidos + total
  - **Testing**: Script `test_initialize_year_2026.php` con pruebas exitosas
  - **Resultados**: 2025 (365 días), 2026 (365 días) completados automáticamente
  - **Fix Bug**: Corregido namespace `App\Helpers\Security` → `App\Core\Security`

### 📝 **Nota Importante**:
- **Subfase 4.4 - Integración Cálculos Legales**: CANCELADA
  - La integración del calendario empresarial con los cálculos de liquidaciones, vacaciones y XIII Mes se implementará como parte del **Módulo de Integración API Marcaciones y Asistencias**
  - El modelo BusinessCalendar permanece disponible para uso futuro según necesidades del proyecto

## 🔑 **Próximas Fases**
1. **⏰ INTEGRACIÓN API MARCACIONES Y ASISTENCIAS** (PRIORIDAD ALTA): Sistema completo de control de asistencias con API externa
   - **Fase 1 - Integración API Externa**: Conexión con API de marcaciones + sincronización automática datos
   - **Fase 2 - Cálculos Avanzados**: Marcaciones perfectas + horas trabajadas + ausencias + tardanzas + horas extras
   - **Fase 3 - Consideraciones Legales**: Aplicación normativa panameña trabajo + jornadas laborales + límites legales + integración BusinessCalendar
   - **Fase 4 - Integración Planillas**: Cálculo horas trabajadas en generación de planillas + conceptos automáticos + validaciones
2. **🏖️ MÓDULO VACACIONES PANAMÁ**: 4 subfases planificadas (VacationCalculator + CRUD + Aprobaciones + Integración)
3. **🏢 MULTITENANCY**: Wizard empresas + BD automática

---

**✅ SISTEMA EMPRESARIAL COMPLETO + CUSTOM QUERY BUILDER OPTIMIZADO**

## 🧮 **Motor Fórmulas Conceptos - V3.2.1 Optimizado V2**
- **Fechas Dinámicas Avanzadas**: INIPERIODO/FINPERIODO con fechas reales planilla_cabecera optimizado
- **Función ACUMULADOS Robusta**: Manejo avanzado conceptos múltiples + preservación quoted strings
- **Regex Patterns Mejorados**: Procesamiento conceptos complejos con parámetros fecha
- **Categorización**: Campo tipo_acumulado para XIII_MES, VACACIONES, etc.
- **Integración Automática**: PayrollController → PlanillaConceptCalculator seamless
- **Validación Integridad**: Fórmulas multi-concepto + error handling robusto
- **Preservación Strings**: Variables no corrompen concept names en quoted strings
- **🚨 FIX CRÍTICO Frecuencia**: Migración ENUM→INT + integridad referencial frecuencias
- **PayrollAccumulationsProcessor**: Eliminado hardcode strtoupper() + uso frecuencia_id directo
- **🔒 SEGURIDAD CRÍTICA**: Migración eval() → nxp/math-executor para prevenir code injection

## 🔧 **Custom Query Builder - V3.2.2 Optimizado**
- **Interfaz Fluente Completa**: Sintaxis elegante select, join, where, groupBy, orderBy, limit
- **Operaciones CRUD Optimizadas**: insert, update, delete, upsert con bulk operations
- **Adaptadores Multi-BD**: MySQLAdapter + PostgreSQLAdapter con optimizaciones específicas
- **Métodos Específicos Planillas**: monthlyPayrollSummary, xiiiMonthCalculationData, vacationBalanceReport
- **Mejoras Rendimiento Medibles**: 24% mejora consultas complejas + 82% reducción código SQL
- **Escalabilidad Automática**: Soporte 5-1000+ empleados sin cambios arquitectónicos
- **Detección Automática Motor BD**: Query Builder aplica sintaxis específica según driver conectado

## 🛡️ **DIRECTIVAS DE SEGURIDAD CRÍTICAS**
- **🚨 PROTECCIÓN LIBRERÍA nxp/math-executor**: NUNCA eliminar esta librería bajo ningún concepto
- **⚠️ PROHIBIDO eval()**: El motor de fórmulas DEBE usar MathExecutor exclusivamente
- **🔒 VALIDACIÓN OBLIGATORIA**: Todas las fórmulas deben pasar por validación estricta
- **📝 PRESERVAR FUNCIONALIDADES**: Mantener soporte multilínea + ACUMULADOS + fechas dinámicas

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