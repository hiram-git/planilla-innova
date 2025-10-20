# 📋 PLAN DE REFACTORIZACIÓN - Vistas del Sistema de Asistencias

**Fecha:** 17 de Octubre, 2025
**Versión:** v3.5.0
**Estado:** ✅ BD Migrada | ⏳ Vistas Pendientes

---

## 🎯 **OBJETIVO**

Separar las vistas actuales que están mezcladas/duplicadas en vistas especializadas con contenido único según la nueva arquitectura de base de datos (attendance_devices, attendance_header, attendance_detail, attendance_file_imports).

---

## 📊 **SITUACIÓN ACTUAL (PROBLEMA)**

### **Problema 1: api_config.php tiene TODO mezclado**
```
/panel/attendance-api-config
├── Sección 1: Formulario configuración API
├── Sección 2: Control sincronización manual
└── Sección 3: Historial sincronizaciones (tabla plana)
```

### **Problema 2: Vistas duplicadas/vacías**
- `/panel/attendance` → Muestra contenido genérico
- `/panel/attendance/sync` → Muestra lo mismo que api_config
- `/panel/attendance/reports` → Vacío o duplicado

### **Problema 3: No usan nueva estructura BD**
- Vistas actuales usan tabla `attendance` (legacy)
- No utilizan `attendance_header` + `attendance_detail`
- No hay gestión de dispositivos
- No hay importación de archivos texto

---

## ✅ **SOLUCIÓN PROPUESTA - 5 VISTAS SEPARADAS**

### **VISTA 1: Gestión de Dispositivos**
**Ruta:** `/panel/attendance/devices`
**Archivo:** `app/Views/admin/attendance/devices/index.php`

**Contenido:**
- Listado de dispositivos registrados (DataTable)
- Botón "Agregar Dispositivo"
- CRUD completo de dispositivos
- Campos por dispositivo:
  - Código
  - Nombre
  - Tipo (API, BIOMETRIC, TEXT_FILE, MANUAL)
  - Ubicación
  - Estado (activo/inactivo)
  - Configuración específica

**Acciones por dispositivo:**
- Editar
- Test conexión (solo para API)
- Activar/Desactivar
- Eliminar

---

### **VISTA 2: Control de Sincronización**
**Ruta:** `/panel/attendance/sync`
**Archivo:** `app/Views/admin/attendance/sync.php`

**Contenido:**
- **Tab 1: Sincronización API**
  - Selector de dispositivo (solo dispositivos tipo API activos)
  - Botón "Sincronizar Todo"
  - Botón "Sincronizar Día Actual"
  - Formulario "Sincronizar por Rango de Fechas"

- **Tab 2: Importar Archivo Texto**
  - Selector de dispositivo (solo dispositivos tipo TEXT_FILE activos)
  - Upload de archivo (.txt, .csv, .dat)
  - Preview de registros (primeras 10 líneas)
  - Mapeo de columnas
  - Botón "Importar"

- **Tab 3: Registro Manual**
  - Formulario entrada manual individual
  - Selector empleado
  - Fecha
  - Hora entrada/salida
  - Botón "Guardar"

**Panel de Estado (sidebar):**
- Última sincronización
- Estado actual
- Registros procesados hoy

---

### **VISTA 3: Historial de Sincronizaciones**
**Ruta:** `/panel/attendance/sync-history`
**Archivo:** `app/Views/admin/attendance/sync-history/index.php`

**Contenido:**

**Tabla Cabecera (attendance_sync_log):**
- ID Sync
- Tipo (FULL, TODAY, DATERANGE, FILE_IMPORT)
- Dispositivo
- Fecha/Hora Inicio
- Duración
- Estado (SUCCESS, FAILED, PARTIAL)
- Registros Totales
- Acciones: Ver Detalle

**Modal/Vista Detalle (al hacer click en "Ver Detalle"):**
- Información completa del sync
- Estadísticas:
  - Registros obtenidos
  - Registros insertados
  - Registros actualizados
  - Registros omitidos
  - Errores
- Log de errores (si existen)
- **Tabla de Detalle:**
  - Empleados afectados
  - Marcaciones creadas/actualizadas
  - Fechas procesadas

---

### **VISTA 4: Marcaciones por Día (Lista)**
**Ruta:** `/panel/attendance`
**Archivo:** `app/Views/admin/attendance/list.php`

**Contenido:**

**Filtros:**
- Año (dropdown)
- Mes (dropdown)
- Rango de fechas
- Dispositivo (dropdown)

**Tabla Cabeceras (attendance_header):**
| Fecha | Dispositivo | Total Empleados | Presentes | Tarde | Ausentes | Estado | Acciones |
|-------|-------------|-----------------|-----------|-------|----------|--------|----------|
| 2025-10-17 | API Base44 | 25 | 20 | 3 | 2 | Procesado | Ver Detalle |

**Cards de Estadísticas (arriba):**
- Total días con marcaciones
- Total empleados únicos
- Promedio puntualidad
- Marcaciones sin procesar

**Acciones por día:**
- Ver Detalle → `/panel/attendance/detail/{date}`
- Exportar Excel
- Reprocesar

---

### **VISTA 5: Detalle de Marcaciones del Día**
**Ruta:** `/panel/attendance/detail/{date}`
**Archivo:** `app/Views/admin/attendance/detail.php`

**Contenido:**

**Cabecera (Info del Día):**
- Fecha
- Dispositivo utilizado
- Estadísticas del día:
  - Total empleados
  - Presentes (con hora)
  - Tarde
  - Ausentes
  - Incompletos (sin hora salida)

**Tabla Detalles (attendance_detail):**
| Empleado | Horario | Hora Entrada | Hora Salida | Tardanza | Horas Trabajadas | Estado | Acciones |
|----------|---------|--------------|-------------|----------|------------------|--------|----------|
| Juan Pérez | 08:00-17:00 | 08:15 | 17:05 | 15 min | 8.5 h | LATE | Editar / Justificar |

**Filtros:**
- Por estado (Todos, Presentes, Tarde, Ausentes, Incompletos)
- Por departamento
- Búsqueda por nombre

**Acciones Masivas:**
- Exportar a Excel
- Generar reporte PDF
- Marcar ausencias como justificadas (selección múltiple)

**Acciones Individuales:**
- Editar marcación
- Justificar ausencia
- Ver historial del empleado
- Eliminar

---

## 🔧 **CAMBIOS TÉCNICOS REQUERIDOS**

### **1. Controladores**

#### **AttendanceDeviceController.php** (NUEVO)
```php
- index()              // Lista de dispositivos
- create()             // Formulario crear dispositivo
- store()              // Guardar nuevo dispositivo
- edit($id)            // Formulario editar
- update($id)          // Actualizar dispositivo
- delete($id)          // Eliminar dispositivo
- testConnection($id)  // Test API (AJAX)
- toggle($id)          // Activar/Desactivar (AJAX)
```

#### **AttendanceController.php** (REFACTORIZAR)
```php
// VISTAS
- index()                    // Lista cabeceras (REFACTORIZAR para usar attendance_header)
- detail($date)              // Detalle del día (REFACTORIZAR para usar attendance_detail)
- sync()                     // Vista sincronización con tabs (REFACTORIZAR)
- syncHistory()              // Historial sincronizaciones (NUEVO)
- syncHistoryDetail($syncId) // Detalle de un sync (NUEVO - AJAX)

// ACCIONES SYNC
- syncNow()                  // Ejecutar sincronización (AJAX)
- importFile()               // Importar archivo texto (AJAX)
- manualEntry()              // Registro manual (POST)

// ACCIONES DETALLES
- updateDetail($id)          // Editar marcación individual (AJAX)
- deleteDetail($id)          // Eliminar marcación (AJAX)
- justifyAbsence($id)        // Justificar ausencia (AJAX)
- exportExcel($date)         // Exportar día a Excel
- exportPDF($date)           // Generar PDF del día
```

### **2. Modelos**

#### **AttendanceDevice.php** (NUEVO)
```php
- getAll()
- getById($id)
- getActive()
- getByType($type)
- create($data)
- update($id, $data)
- delete($id)
- toggleStatus($id)
```

#### **AttendanceHeader.php** (NUEVO)
```php
- getAll($filters)              // Con paginación
- getByDate($date)
- getByDateRange($start, $end)
- getStatistics($filters)
- create($data)
- update($id, $data)
- delete($id)
- reprocess($id)                // Recalcular estadísticas
```

#### **AttendanceDetail.php** (NUEVO)
```php
- getByHeader($headerId)
- getByEmployee($employeeId)
- getByDateAndEmployee($date, $employeeId)
- create($data)
- update($id, $data)
- delete($id)
- bulkInsert($records)
```

#### **AttendanceSyncLog.php** (REFACTORIZAR existente)
```php
- getAll($filters)
- getById($id)
- getDetailBySyncId($syncId)   // Obtener detalle del sync
- create($data)
- updateStatus($id, $status)
```

### **3. Rutas (App.php)**

```php
// DISPOSITIVOS
$router->get('/panel/attendance/devices', 'AttendanceDeviceController@index');
$router->get('/panel/attendance/devices/create', 'AttendanceDeviceController@create');
$router->post('/panel/attendance/devices/store', 'AttendanceDeviceController@store');
$router->get('/panel/attendance/devices/{id}/edit', 'AttendanceDeviceController@edit');
$router->post('/panel/attendance/devices/{id}/update', 'AttendanceDeviceController@update');
$router->post('/panel/attendance/devices/{id}/delete', 'AttendanceDeviceController@delete');
$router->post('/panel/attendance/devices/{id}/test', 'AttendanceDeviceController@testConnection');

// SINCRONIZACIÓN
$router->get('/panel/attendance/sync', 'AttendanceController@sync');
$router->post('/panel/attendance/sync-now', 'AttendanceController@syncNow');
$router->post('/panel/attendance/import-file', 'AttendanceController@importFile');
$router->post('/panel/attendance/manual-entry', 'AttendanceController@manualEntry');

// HISTORIAL SINCRONIZACIONES
$router->get('/panel/attendance/sync-history', 'AttendanceController@syncHistory');
$router->get('/panel/attendance/sync-history/{id}', 'AttendanceController@syncHistoryDetail');

// MARCACIONES
$router->get('/panel/attendance', 'AttendanceController@index');
$router->get('/panel/attendance/detail/{date}', 'AttendanceController@detail');
$router->post('/panel/attendance/detail/{id}/update', 'AttendanceController@updateDetail');
$router->post('/panel/attendance/detail/{id}/delete', 'AttendanceController@deleteDetail');
$router->post('/panel/attendance/detail/{id}/justify', 'AttendanceController@justifyAbsence');

// ELIMINAR/DEPRECAR
// $router->get('/panel/attendance-api-config', ...) → ELIMINAR, reemplazar por /devices
```

### **4. Sidebar**

```php
<!-- CONTROL DE ASISTENCIA -->
<li class="nav-header">CONTROL DE ASISTENCIA</li>

<li class="nav-item menu-open">
    <a href="#" class="nav-link active">
        <i class="nav-icon fas fa-clock"></i>
        <p>Asistencia <i class="fas fa-angle-left right"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <!-- CAMBIO 1: Gestión Dispositivos -->
        <li class="nav-item">
            <a href="/panel/attendance/devices" class="nav-link">
                <i class="fas fa-desktop nav-icon"></i>
                <p>Dispositivos</p>
            </a>
        </li>

        <!-- CAMBIO 2: Control Sincronización (con tabs) -->
        <li class="nav-item">
            <a href="/panel/attendance/sync" class="nav-link">
                <i class="fas fa-sync-alt nav-icon"></i>
                <p>Sincronizar</p>
            </a>
        </li>

        <!-- CAMBIO 3: Historial Sincronizaciones (separado) -->
        <li class="nav-item">
            <a href="/panel/attendance/sync-history" class="nav-link">
                <i class="fas fa-history nav-icon"></i>
                <p>Historial Sync</p>
            </a>
        </li>

        <li class="nav-item">
            <a href="/panel/attendance" class="nav-link">
                <i class="fas fa-calendar-check nav-icon"></i>
                <p>Marcaciones por Día</p>
            </a>
        </li>

        <!-- ELIMINAR: Sistema de Marcaciones externo (timeclock) -->
    </ul>
</li>
```

---

## 📅 **ORDEN DE IMPLEMENTACIÓN**

### **Fase 1: Modelos** (30 min)
1. AttendanceDevice.php
2. AttendanceHeader.php
3. AttendanceDetail.php
4. Refactorizar AttendanceSyncLog.php

### **Fase 2: Controladores** (45 min)
1. AttendanceDeviceController.php (completo)
2. Refactorizar AttendanceController.php

### **Fase 3: Vistas** (60 min)
1. devices/index.php
2. devices/form.php
3. sync.php (con 3 tabs)
4. sync-history/index.php
5. Refactorizar list.php
6. Refactorizar detail.php

### **Fase 4: Rutas + Sidebar** (15 min)
1. Actualizar App.php
2. Actualizar sidebar.php

### **Fase 5: Testing** (30 min)
1. Verificar CRUD dispositivos
2. Verificar sincronización
3. Verificar historial
4. Verificar listado marcaciones
5. Verificar detalle del día

**TOTAL ESTIMADO: 3 horas**

---

## ✅ **APROBACIÓN**

¿Proceder con esta implementación completa?

- [ ] SÍ, implementar todo según este plan
- [ ] NO, necesito modificaciones (especificar)

