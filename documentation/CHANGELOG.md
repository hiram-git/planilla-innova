# 📋 CHANGELOG - Sistema de Planillas MVC

## 📖 **Índice de Versiones**

Este archivo sirve como índice principal para el historial de cambios del sistema. Cada versión tiene su propio archivo detallado en el directorio `changelog/`.

---

## 🆕 **Últimas Versiones**

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

**Última Actualización**: 10 de Octubre, 2025
**Sistema**: Planillas MVC v3.4.1
**Progreso Global**: Core 100% | Calendario 100% | API Asistencias 25%
