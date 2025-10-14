# 📊 ANÁLISIS Y ARQUITECTURA - MÓDULO DE ASISTENCIAS
## Sistema de Control de Asistencias con Integración API Base44

**Fecha**: 9 de Octubre, 2025
**Versión**: 1.0 - Análisis y Planificación Inicial
**Estado**: 🟡 En Planificación

---

## 📋 ÍNDICE

1. [Estado Actual del Sistema](#estado-actual-del-sistema)
2. [Análisis de la API Externa Base44](#análisis-de-la-api-externa-base44)
3. [Arquitectura Propuesta](#arquitectura-propuesta)
4. [Subfase 7.1: Integración API Externa](#subfase-71-integración-api-externa)
5. [Subfase 7.2: Cálculos Avanzados](#subfase-72-cálculos-avanzados)
6. [Subfase 7.3: Legislación Panamá + BusinessCalendar](#subfase-73-legislación-panamá--businesscalendar)
7. [Subfase 7.4: Integración con Planillas](#subfase-74-integración-con-planillas)
8. [Subfase 7.5: Interfaz y Reportes](#subfase-75-interfaz-y-reportes)
9. [Plan de Implementación](#plan-de-implementación)
10. [Estimación de Tiempo](#estimación-de-tiempo)

---

## 🔍 ESTADO ACTUAL DEL SISTEMA

### ✅ Recursos Existentes

#### **1. Tabla `attendance` (BD)**
```sql
CREATE TABLE attendance (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    num_hr DOUBLE NOT NULL,
    status INT(11) NOT NULL
);
```

**Campos Actuales**:
- `id`: PK autoincremental
- `employee_id`: FK a tabla employees
- `date`: Fecha del registro
- `time_in`: Hora de entrada
- `time_out`: Hora de salida
- `num_hr`: Horas trabajadas calculadas
- `status`: Estado (0 = tarde, 1 = a tiempo)

**Limitaciones**:
- ❌ No tiene campos para sincronización con API externa
- ❌ No almacena datos de geolocalización (lat/lng)
- ❌ No registra tipo de marcación (CHECK_IN/CHECK_OUT)
- ❌ No tiene campo para notas o justificaciones
- ❌ No almacena datos crudos de la API (para auditoría)
- ❌ No tiene timestamps de creación/actualización

#### **2. Tabla `schedules` (Horarios)**
```sql
CREATE TABLE schedules (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(500),
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Estado**: ✅ Tabla adecuada para horarios estándar

#### **3. Modelo `Attendance.php`**
**Ubicación**: `app/Models/Attendance.php` (194 líneas)

**Métodos Principales**:
- `timeIn($employeeId)`: Registra entrada manual
- `timeOut($employeeId)`: Registra salida manual
- `getTodayAttendance($employeeId)`: Obtiene registro del día
- `calculateWorkedHours()`: Calcula horas con descuento de almuerzo
- `getAttendanceByDateRange()`: Consulta por rango de fechas (soporte tipo_planilla)
- `getMonthlyAttendance()`: Consulta mensual

**Características**:
- ✅ Validación duplicados (un registro por día por empleado)
- ✅ Cálculo automático de horas trabajadas
- ✅ Descuento automático de hora de almuerzo (si > 4 horas)
- ✅ Detección de tardanzas comparando con schedule
- ✅ Soporte para múltiples tipos de planilla (FIND_IN_SET)

**Limitaciones**:
- ❌ No tiene integración con API externa
- ❌ Lógica de cálculo básica (no considera horas extras, nocturnas, etc.)
- ❌ No detecta ausencias automáticamente
- ❌ No calcula horas extras según legislación panameña

#### **4. Controlador `Attendance.php`**
**Ubicación**: `app/Controllers/Attendance.php` (252 líneas)

**Endpoints**:
- `GET /panel/attendance`: Lista de asistencias (últimos 30 días)
- `POST /panel/attendance/store`: Crear registro manual
- `POST /panel/attendance/update/{id}`: Actualizar registro
- `POST /panel/attendance/delete/{id}`: Eliminar registro
- `POST /panel/attendance/getRow`: Obtener datos para edición (AJAX)
- `GET /panel/attendance/reports`: Reportes con filtros de fecha/empleado

**Características**:
- ✅ CRUD completo
- ✅ Validaciones CSRF
- ✅ Sanitización de datos
- ✅ Integración con modelo Employee
- ✅ Cálculo automático de horas y status

**Limitaciones**:
- ❌ Solo entrada manual de datos (no automática desde API)
- ❌ No tiene endpoints para webhooks o sincronización
- ❌ Reportes básicos sin analytics avanzados

---

## 🌐 ANÁLISIS DE LA API EXTERNA BASE44

### **Información de la API**
- **Proveedor**: Base44.com
- **App ID**: `68dd9181444436f4bd157e1d`
- **API Key**: `40162908d71941b98636b38106be556e`
- **Base URL**: `https://app.base44.com/api/apps/{app_id}`

### **Entidades Disponibles**

#### **1. Entity: Employee**
**Endpoint**: `/entities/Employee`

**Campos Disponibles** (Filterable):
- `user_email`: Email del usuario
- `full_name`: Nombre completo
- `employee_id`: ID de empleado
- `department`: Departamento
- `position`: Posición/cargo
- `phone`: Teléfono
- `profile_photo_url`: URL foto de perfil
- `assigned_location_ids`: IDs de ubicaciones asignadas
- `work_schedules`: Horarios de trabajo
- `is_active`: Estado activo/inactivo

**Operaciones**:
- `GET /entities/Employee`: Listar todos los empleados
- `GET /entities/Employee/{id}`: Obtener empleado específico
- `PUT /entities/Employee/{id}`: Actualizar empleado
- `POST /entities/Employee`: Crear empleado (presumiblemente)
- `DELETE /entities/Employee/{id}`: Eliminar empleado (presumiblemente)

#### **2. Entity: Attendance**
**Endpoint**: `/entities/Attendance`

**Campos Disponibles** (Filterable):
- `employee_email`: Email del empleado
- `employee_name`: Nombre del empleado
- `type`: Tipo de marcación (CHECK_IN/CHECK_OUT)
- `photo_url`: URL de foto de la marcación
- `latitude`: Latitud GPS
- `longitude`: Longitud GPS
- `location_name`: Nombre de la ubicación
- `timestamp`: Fecha y hora de la marcación
- `hours_worked`: Horas trabajadas calculadas
- `is_late`: Indicador de tardanza (boolean)
- `is_early_exit`: Indicador de salida anticipada (boolean)
- `notes`: Notas adicionales

**Operaciones**:
- `GET /entities/Attendance`: Listar todas las marcaciones
- `GET /entities/Attendance/{id}`: Obtener marcación específica
- `PUT /entities/Attendance/{id}`: Actualizar marcación
- `POST /entities/Attendance`: Crear marcación (presumiblemente)
- `DELETE /entities/Attendance/{id}`: Eliminar marcación (presumiblemente)

### **Características de la API**
- ✅ **Autenticación**: API Key simple en headers
- ✅ **Formato**: JSON request/response
- ✅ **Filtros**: Soporte de filtros por campos específicos
- ✅ **Geolocalización**: Campos lat/lng para tracking de ubicación
- ✅ **Evidencia Fotográfica**: URLs de fotos de marcaciones
- ✅ **Cálculos Automáticos**: Campos pre-calculados (hours_worked, is_late, is_early_exit)
- ❓ **Webhooks**: No documentado (a investigar)
- ❓ **Paginación**: No documentado (a investigar)
- ❓ **Rate Limiting**: No documentado (a investigar)

---

## 🏗️ ARQUITECTURA PROPUESTA

### **Principios de Diseño**

1. **Separación de Responsabilidades**:
   - Capa API independiente del modelo de datos local
   - Servicios dedicados para cada funcionalidad (sync, cálculos, reportes)

2. **Doble Sistema de Datos**:
   - **Datos Crudos API** (`attendance_raw_data`): Backup completo de respuestas API
   - **Datos Procesados** (`attendance_records`): Datos normalizados + cálculos locales
   - Beneficio: Auditoría completa + capacidad de reprocesar

3. **Sincronización Híbrida**:
   - **Push (Webhooks)**: Notificaciones en tiempo real (si disponible)
   - **Pull (Cron Jobs)**: Sincronización programada cada X minutos
   - **Manual**: Opción de sincronización bajo demanda

4. **Cálculos en Dos Etapas**:
   - **Etapa 1**: Cálculos básicos (horas trabajadas, tardanzas)
   - **Etapa 2**: Cálculos legales avanzados (horas extras, nocturnas, dominicales)

5. **Integración con BusinessCalendar**:
   - Validación de días laborables para ausencias
   - Clasificación de días (ordinario, festivo, domingo)
   - Cálculo preciso de períodos para liquidaciones

### **Stack Tecnológico**

**Backend**:
- PHP 8.3+
- Guzzle HTTP Client (para API calls)
- Cron Jobs (para sincronización)

**Frontend**:
- AdminLTE 3+ (UI components)
- DataTables server-side (listados grandes)
- FullCalendar.js (vista calendario)
- Chart.js (gráficos y analytics)

**Base de Datos**:
- MySQL 8.0+
- 7 nuevas tablas adicionales
- Índices optimizados para queries frecuentes

---

## 🔧 SUBFASE 7.1: INTEGRACIÓN API EXTERNA

### **Objetivo**
Establecer conexión robusta con API Base44 y sincronización bidireccional de datos.

### **Componentes a Crear**

#### **1. Clase `Base44ApiClient`**
**Ubicación**: `app/Services/Attendance/Base44ApiClient.php`

**Responsabilidades**:
- Gestión de autenticación (API key)
- Métodos CRUD para entidades Employee y Attendance
- Manejo de errores HTTP y timeouts
- Logging de todas las transacciones
- Retry logic con backoff exponencial

**Métodos Principales**:
```php
class Base44ApiClient {
    public function __construct($apiKey, $appId)
    public function getEmployees($filters = [])
    public function getEmployee($entityId)
    public function updateEmployee($entityId, $data)

    public function getAttendances($filters = [])
    public function getAttendance($entityId)
    public function updateAttendance($entityId, $data)

    public function testConnection()
    private function makeRequest($method, $endpoint, $data = null)
    private function handleError($response)
}
```

**Configuración**:
```php
// .env
BASE44_API_KEY=40162908d71941b98636b38106be556e
BASE44_APP_ID=68dd9181444436f4bd157e1d
BASE44_API_URL=https://app.base44.com/api
BASE44_SYNC_ENABLED=true
BASE44_SYNC_INTERVAL=15 // minutos
```

#### **2. Clase `AttendanceSyncService`**
**Ubicación**: `app/Services/Attendance/AttendanceSyncService.php`

**Responsabilidades**:
- Sincronización periódica de marcaciones
- Detección de registros nuevos/modificados
- Inserción en `attendance_raw_data` y `attendance_records`
- Manejo de conflictos (duplicados, datos inconsistentes)
- Logging de operaciones de sync

**Métodos Principales**:
```php
class AttendanceSyncService {
    public function syncAll()
    public function syncByDateRange($startDate, $endDate)
    public function syncEmployee($employeeId)
    public function syncSince($lastSyncTimestamp)

    private function processAttendanceRecord($rawData)
    private function detectDuplicates($data)
    private function resolveConflict($existingRecord, $newData)
}
```

#### **3. Clase `WebhookReceiverController`**
**Ubicación**: `app/Controllers/Webhooks/Base44WebhookController.php`

**Responsabilidades**:
- Endpoint para recibir notificaciones en tiempo real de Base44
- Validación de firma/token de webhook
- Procesamiento asíncrono de eventos
- Respuesta inmediata (status 200) para no bloquear API externa

**Métodos Principales**:
```php
class Base44WebhookController extends Controller {
    public function receive()
    public function validateSignature($payload, $signature)
    public function processEvent($eventType, $data)
}
```

**Endpoint**: `POST /webhooks/base44/attendance`

#### **4. Cron Job de Sincronización**
**Ubicación**: `scripts/cron/sync_attendance.php`

**Funcionalidad**:
- Ejecutar cada 15 minutos (configurable)
- Sincronizar marcaciones desde última ejecución
- Enviar notificaciones si hay errores
- Logging detallado en `attendance_sync_log`

**Comando**:
```bash
# Crontab entry
*/15 * * * * php /path/to/planilla-innova/scripts/cron/sync_attendance.php
```

### **Nuevas Tablas BD**

#### **Tabla 1: `attendance_api_config`**
```sql
CREATE TABLE attendance_api_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_provider VARCHAR(50) NOT NULL DEFAULT 'base44',
    api_key VARCHAR(255) NOT NULL,
    app_id VARCHAR(255) NOT NULL,
    api_url VARCHAR(500) NOT NULL,
    sync_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sync_interval_minutes INT NOT NULL DEFAULT 15,
    last_sync_at TIMESTAMP NULL,
    webhook_url VARCHAR(500),
    webhook_secret VARCHAR(255),
    config_json TEXT COMMENT 'Configuraciones adicionales en JSON',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_provider (api_provider)
);
```

**Propósito**: Almacenar configuración de conexión a API externa (multi-tenant ready).

#### **Tabla 2: `attendance_raw_data`**
```sql
CREATE TABLE attendance_raw_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    external_id VARCHAR(100) COMMENT 'ID desde API externa',
    api_provider VARCHAR(50) NOT NULL DEFAULT 'base44',
    entity_type VARCHAR(50) NOT NULL COMMENT 'Employee o Attendance',
    raw_json TEXT NOT NULL COMMENT 'Payload completo de la API',
    processed TINYINT(1) NOT NULL DEFAULT 0,
    processed_at TIMESTAMP NULL,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_external_id (external_id),
    INDEX idx_provider (api_provider),
    INDEX idx_processed (processed)
);
```

**Propósito**: Backup completo de todas las respuestas de la API para auditoría y reprocesamiento.

#### **Tabla 3: `attendance_sync_log`**
```sql
CREATE TABLE attendance_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sync_type VARCHAR(50) NOT NULL COMMENT 'FULL, INCREMENTAL, MANUAL, WEBHOOK',
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    records_fetched INT DEFAULT 0,
    records_inserted INT DEFAULT 0,
    records_updated INT DEFAULT 0,
    records_skipped INT DEFAULT 0,
    errors_count INT DEFAULT 0,
    error_details TEXT,
    status VARCHAR(20) NOT NULL COMMENT 'SUCCESS, FAILED, PARTIAL',
    triggered_by VARCHAR(100) COMMENT 'CRON, USER_ID, WEBHOOK',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time)
);
```

**Propósito**: Historial completo de todas las sincronizaciones para debugging y monitoreo.

### **Interfaz de Configuración**

**Ubicación Vista**: `app/Views/admin/attendance/api_config.php`

**Funcionalidades**:
- Formulario configuración API (key, app_id, interval)
- Botón "Probar Conexión" con feedback inmediato
- Mostrar último sync exitoso
- Botón "Sincronizar Ahora" (manual)
- Logs de sincronización (tabla con últimas 20 ejecuciones)
- Activar/Desactivar sincronización automática

---

## 📊 SUBFASE 7.2: CÁLCULOS AVANZADOS

### **Objetivo**
Procesar marcaciones y calcular métricas avanzadas de asistencia según reglas empresariales.

### **Componentes a Crear**

#### **1. Clase `AttendanceCalculator`**
**Ubicación**: `app/Services/Attendance/AttendanceCalculator.php`

**Responsabilidades**:
- Cálculo de horas trabajadas exactas
- Detección de tardanzas con tolerancia configurable
- Detección de salidas anticipadas
- Cálculo de horas extras (normales y nocturnas)
- Identificación de marcaciones perfectas

**Métodos Principales**:
```php
class AttendanceCalculator {
    public function calculateWorkedHours($timeIn, $timeOut, $schedule)
    public function isLate($timeIn, $schedule, $toleranceMinutes = 5)
    public function isEarlyExit($timeOut, $schedule, $toleranceMinutes = 5)
    public function calculateRegularHours($timeIn, $timeOut, $schedule)
    public function calculateOvertimeHours($timeIn, $timeOut, $schedule)
    public function calculateNighttimeHours($timeIn, $timeOut)
    public function isPerfectAttendance($timeIn, $timeOut, $schedule)
    public function calculateLunchBreak($timeIn, $timeOut, $hours)
}
```

**Configuración**:
```php
// Tolerancia tardanzas (minutos)
ATTENDANCE_LATE_TOLERANCE=5

// Hora inicio jornada nocturna
ATTENDANCE_NIGHT_START=18:00

// Hora fin jornada nocturna
ATTENDANCE_NIGHT_END=06:00

// Descuento automático almuerzo (minutos)
ATTENDANCE_LUNCH_BREAK=60

// Umbral para aplicar descuento almuerzo (horas)
ATTENDANCE_LUNCH_THRESHOLD=4
```

#### **2. Clase `OvertimeCalculator`**
**Ubicación**: `app/Services/Attendance/OvertimeCalculator.php`

**Responsabilidades**:
- Calcular horas extras según legislación panameña
- Diferenciar entre horas extras normales (+25%) y adicionales (+50%)
- Calcular horas nocturnas (+50%)
- Calcular horas dominicales/festivos (+50%)
- Integración con `BusinessCalendar` para detectar días especiales

**Métodos Principales**:
```php
class OvertimeCalculator {
    public function calculateOvertimeByType($timeIn, $timeOut, $schedule, $date)
    public function getNormalOvertimeHours($totalHours, $schedule) // Primeras 3 horas
    public function getExtraOvertimeHours($totalHours, $schedule) // Más de 3 horas
    public function getNighttimeHours($timeIn, $timeOut)
    public function isSundayOrHoliday($date)
    public function getOvertimeRates()
}
```

**Tasas Legislación Panamá**:
```php
const OVERTIME_RATE_NORMAL = 1.25;  // +25% (primeras 3 horas)
const OVERTIME_RATE_EXTRA = 1.50;   // +50% (adicionales)
const NIGHTTIME_RATE = 1.50;        // +50% (6PM-6AM)
const SUNDAY_HOLIDAY_RATE = 1.50;   // +50% (domingos/feriados)
```

#### **3. Clase `AbsenceDetector`**
**Ubicación**: `app/Services/Attendance/AbsenceDetector.php`

**Responsabilidades**:
- Detectar ausencias automáticamente (días sin marcación)
- Clasificar ausencias (justificadas/injustificadas)
- Consultar permisos, vacaciones, incapacidades
- Generar alertas de ausencias injustificadas

**Métodos Principales**:
```php
class AbsenceDetector {
    public function detectAbsences($employeeId, $startDate, $endDate)
    public function classifyAbsence($employeeId, $date)
    public function hasJustification($employeeId, $date)
    public function getConsecutiveAbsences($employeeId)
    public function triggerAlertIfNeeded($employeeId, $absences)
}
```

#### **4. Clase `WorkScheduleResolver`**
**Ubicación**: `app/Services/Attendance/WorkScheduleResolver.php`

**Responsabilidades**:
- Determinar horario aplicable para un empleado en una fecha específica
- Soporte para turnos rotativos
- Soporte para horarios especiales (días festivos, eventos)
- Cache de horarios para mejorar performance

**Métodos Principales**:
```php
class WorkScheduleResolver {
    public function getScheduleForDate($employeeId, $date)
    public function hasRotatingSchedule($employeeId)
    public function getSpecialSchedule($employeeId, $date)
    public function getDefaultSchedule($employeeId)
}
```

### **Nuevas Tablas BD**

#### **Tabla 4: `attendance_records`**
```sql
CREATE TABLE attendance_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME,
    time_out DATETIME,
    schedule_id INT,

    -- Horas trabajadas
    worked_hours DECIMAL(5,2) DEFAULT 0.00,
    regular_hours DECIMAL(5,2) DEFAULT 0.00,
    overtime_hours_normal DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Primeras 3 horas extras +25%',
    overtime_hours_extra DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Adicionales +50%',
    nighttime_hours DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Horas nocturnas 6PM-6AM',

    -- Indicadores
    is_late TINYINT(1) DEFAULT 0,
    late_minutes INT DEFAULT 0,
    is_early_exit TINYINT(1) DEFAULT 0,
    early_exit_minutes INT DEFAULT 0,
    is_perfect_attendance TINYINT(1) DEFAULT 0,
    is_sunday_or_holiday TINYINT(1) DEFAULT 0,

    -- Datos adicionales
    status VARCHAR(20) DEFAULT 'PRESENT' COMMENT 'PRESENT, ABSENT, LATE, JUSTIFIED',
    notes TEXT,
    justification_type VARCHAR(50) COMMENT 'PERMISO, INCAPACIDAD, VACACION',
    justification_id INT COMMENT 'ID del registro de justificación',

    -- Auditoría
    synced_from_api TINYINT(1) DEFAULT 0,
    raw_data_id INT COMMENT 'FK a attendance_raw_data',
    calculated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_employee_date (employee_id, date),
    INDEX idx_employee (employee_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_late (is_late),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
);
```

**Propósito**: Registros procesados de asistencias con todos los cálculos realizados.

#### **Tabla 5: `attendance_calculations`**
```sql
CREATE TABLE attendance_calculations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attendance_record_id INT NOT NULL,
    calculation_type VARCHAR(50) NOT NULL COMMENT 'WORKED_HOURS, OVERTIME, NIGHTTIME, etc.',
    value DECIMAL(10,2) NOT NULL,
    details TEXT COMMENT 'JSON con detalles del cálculo',
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_record (attendance_record_id),
    INDEX idx_type (calculation_type),
    FOREIGN KEY (attendance_record_id) REFERENCES attendance_records(id) ON DELETE CASCADE
);
```

**Propósito**: Detalle granular de cálculos individuales para auditoría.

#### **Tabla 6: `attendance_exceptions`**
```sql
CREATE TABLE attendance_exceptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    exception_type VARCHAR(50) NOT NULL COMMENT 'PERMISO, INCAPACIDAD, VACACION, LICENCIA',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    approval_status VARCHAR(20) DEFAULT 'PENDING' COMMENT 'PENDING, APPROVED, REJECTED',
    approved_by INT COMMENT 'user_id del aprobador',
    approved_at TIMESTAMP NULL,
    reason TEXT,
    document_url VARCHAR(500) COMMENT 'URL del documento justificativo',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee (employee_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (exception_type),
    INDEX idx_status (approval_status),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

**Propósito**: Justificaciones de ausencias (permisos, incapacidades, vacaciones).

---

## 🇵🇦 SUBFASE 7.3: LEGISLACIÓN PANAMÁ + BUSINESSCALENDAR

### **Objetivo**
Aplicar normativa laboral panameña y integrar con BusinessCalendar para cálculos legales precisos.

### **Componentes a Crear**

#### **1. Clase `LegalComplianceChecker`**
**Ubicación**: `app/Services/Attendance/LegalComplianceChecker.php`

**Responsabilidades**:
- Validar cumplimiento de jornadas máximas legales
- Detectar excesos de jornada diaria (>8h) y semanal (>48h)
- Validar descansos obligatorios
- Generar alertas de incumplimientos

**Métodos Principales**:
```php
class LegalComplianceChecker {
    public function validateDailyHours($workedHours)
    public function validateWeeklyHours($employeeId, $weekStartDate)
    public function validateMandatoryRest($employeeId, $date)
    public function checkConsecutiveWorkDays($employeeId)
    public function generateComplianceReport($employeeId, $period)
}
```

**Límites Legales Panamá**:
```php
const MAX_DAILY_HOURS = 8;           // Art. 31 Código de Trabajo
const MAX_WEEKLY_HOURS = 48;         // Art. 31 Código de Trabajo
const MAX_NIGHTTIME_HOURS = 7;       // Art. 38 Código de Trabajo
const MANDATORY_LUNCH_MINUTES = 30;  // Art. 35 Código de Trabajo
const MAX_CONSECUTIVE_WORK_DAYS = 6; // Art. 48 Código de Trabajo
```

#### **2. Integración BusinessCalendar**

**Modificaciones en `BusinessCalendar.php`**:

Agregar métodos para clasificación de días:
```php
// Nuevos métodos en BusinessCalendar
public function isHoliday($date)
public function isSunday($date)
public function isSpecialDay($date)
public function getDayClassification($date) // LABORAL, FERIADO, DOMINGO, ESPECIAL
public function getWorkingDaysInPeriod($startDate, $endDate)
```

**Uso en Asistencias**:
```php
// En OvertimeCalculator
$calendar = new BusinessCalendar();
if ($calendar->isHoliday($date) || $calendar->isSunday($date)) {
    $rate = self::SUNDAY_HOLIDAY_RATE; // +50%
}
```

**Uso en Liquidaciones** (actualización):
```php
// En LiquidationCalculator
$calendar = new BusinessCalendar();
$workingDays = $calendar->getWorkingDaysInPeriod($startDate, $endDate);
$preaviso = $workingDays >= 30 ? $salary : ($salary / 30 * $workingDays);
```

#### **3. Clase `AttendanceReportGenerator`**
**Ubicación**: `app/Services/Attendance/AttendanceReportGenerator.php`

**Responsabilidades**:
- Generar reportes diarios, semanales, mensuales
- Reportes de puntualidad
- Reportes de ausentismo
- Reportes de horas extras
- Exportar a PDF, Excel, CSV

**Métodos Principales**:
```php
class AttendanceReportGenerator {
    public function generateDailyReport($date, $departmentId = null)
    public function generateWeeklyReport($weekStartDate, $employeeId = null)
    public function generateMonthlyReport($year, $month)
    public function generatePunctualityReport($startDate, $endDate)
    public function generateAbsenteeismReport($startDate, $endDate)
    public function generateOvertimeReport($startDate, $endDate)
    public function exportToPDF($data, $reportType)
    public function exportToExcel($data, $reportType)
}
```

---

## 🔗 SUBFASE 7.4: INTEGRACIÓN CON PLANILLAS

### **Objetivo**
Automatizar inclusión de conceptos de asistencia en cálculo de planillas.

### **Componentes a Crear**

#### **1. Clase `PayrollAttendanceIntegrator`**
**Ubicación**: `app/Services/Attendance/PayrollAttendanceIntegrator.php`

**Responsabilidades**:
- Integrar asistencias en generación de planillas
- Calcular conceptos automáticos por empleado
- Validar períodos de asistencias vs. planillas
- Generar resumen de asistencias por planilla

**Métodos Principales**:
```php
class PayrollAttendanceIntegrator {
    public function processAttendanceForPayroll($payrollId)
    public function getEmployeeAttendanceSummary($employeeId, $startDate, $endDate)
    public function calculateAttendanceConcepts($employeeId, $attendances)
    public function validateAttendanceData($employeeId, $period)
    public function attachAttendanceReport($payrollId)
}
```

#### **2. Clase `AttendanceConceptMapper`**
**Ubicación**: `app/Services/Attendance/AttendanceConceptMapper.php`

**Responsabilidades**:
- Mapear cálculos de asistencias a conceptos de planilla
- Crear registros en `planilla_detalle` automáticamente
- Configuración de códigos de conceptos por tipo

**Métodos Principales**:
```php
class AttendanceConceptMapper {
    public function mapToPayrollConcepts($employeeAttendanceSummary)
    public function getConceptCode($conceptType)
    public function createPayrollDetailRecords($payrollId, $mappedConcepts)
}
```

**Configuración de Conceptos**:
```php
// .env o tabla attendance_concepts_mapping
CONCEPT_HORAS_TRABAJADAS=HRS001
CONCEPT_HORAS_EXTRAS_25=HEX025
CONCEPT_HORAS_EXTRAS_50=HEX050
CONCEPT_HORAS_NOCTURNAS=HNOC01
CONCEPT_HORAS_DOMINICALES=HDOM01
CONCEPT_DESCUENTO_TARDANZAS=DTARD1
CONCEPT_DESCUENTO_AUSENCIAS=DAUSE1
CONCEPT_BONO_PUNTUALIDAD=BPUNT1
```

#### **3. Nuevos Conceptos en Tabla `concepto`**

**Script de Migración**: `database/migrations/2025_10_09_attendance_concepts.sql`

```sql
-- Conceptos de Asignación
INSERT INTO concepto (concepto, descripcion, tipo, formula, tipo_acumulado, activo) VALUES
('HRS001', 'Horas Trabajadas', 'ASIGNACION', 'ATTENDANCE_WORKED_HOURS(FICHA, INIPERIODO, FINPERIODO)', NULL, 1),
('HEX025', 'Horas Extras 25%', 'ASIGNACION', 'ATTENDANCE_OVERTIME_NORMAL(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 240) * 1.25', NULL, 1),
('HEX050', 'Horas Extras 50%', 'ASIGNACION', 'ATTENDANCE_OVERTIME_EXTRA(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 240) * 1.50', NULL, 1),
('HNOC01', 'Horas Nocturnas', 'ASIGNACION', 'ATTENDANCE_NIGHTTIME(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 240) * 1.50', NULL, 1),
('HDOM01', 'Horas Dominicales/Feriados', 'ASIGNACION', 'ATTENDANCE_SUNDAY_HOURS(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 240) * 1.50', NULL, 1),
('BPUNT1', 'Bono Puntualidad', 'ASIGNACION', 'ATTENDANCE_PERFECT_DAYS(FICHA, INIPERIODO, FINPERIODO) >= 20 ? 50.00 : 0', NULL, 1);

-- Conceptos de Deducción
INSERT INTO concepto (concepto, descripcion, tipo, formula, tipo_acumulado, activo) VALUES
('DTARD1', 'Descuento por Tardanzas', 'DEDUCCION', 'ATTENDANCE_LATE_MINUTES(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 240 / 60)', NULL, 1),
('DAUSE1', 'Descuento por Ausencias', 'DEDUCCION', 'ATTENDANCE_ABSENT_DAYS(FICHA, INIPERIODO, FINPERIODO) * (SALARIO_BASE / 30)', NULL, 1);
```

#### **4. Nuevas Funciones en `PlanillaConceptCalculator.php`**

Agregar soporte para funciones ATTENDANCE_*():

```php
// En PlanillaConceptCalculator.php

private function procesarFuncionesAsistencia($formula, $ficha, $iniPeriodo, $finPeriodo) {
    // ATTENDANCE_WORKED_HOURS(FICHA, INICIO, FIN)
    if (preg_match('/ATTENDANCE_WORKED_HOURS\s*\(([^)]+)\)/', $formula, $matches)) {
        $hours = $this->getWorkedHours($ficha, $iniPeriodo, $finPeriodo);
        $formula = str_replace($matches[0], $hours, $formula);
    }

    // ATTENDANCE_OVERTIME_NORMAL(FICHA, INICIO, FIN)
    if (preg_match('/ATTENDANCE_OVERTIME_NORMAL\s*\(([^)]+)\)/', $formula, $matches)) {
        $hours = $this->getOvertimeNormalHours($ficha, $iniPeriodo, $finPeriodo);
        $formula = str_replace($matches[0], $hours, $formula);
    }

    // Similar para otras funciones...

    return $formula;
}

private function getWorkedHours($ficha, $startDate, $endDate) {
    $attendanceRecords = new AttendanceRecord();
    $summary = $attendanceRecords->getSummaryByPeriod($ficha, $startDate, $endDate);
    return $summary['worked_hours'] ?? 0;
}
```

### **Nuevas Tablas BD**

#### **Tabla 7: `payroll_attendance_summary`**
```sql
CREATE TABLE payroll_attendance_summary (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payroll_id INT NOT NULL,
    employee_id INT NOT NULL,

    -- Resumen del período
    total_days INT DEFAULT 0,
    present_days INT DEFAULT 0,
    absent_days INT DEFAULT 0,
    late_days INT DEFAULT 0,
    perfect_days INT DEFAULT 0,

    -- Horas totales
    worked_hours DECIMAL(8,2) DEFAULT 0.00,
    regular_hours DECIMAL(8,2) DEFAULT 0.00,
    overtime_hours_normal DECIMAL(8,2) DEFAULT 0.00,
    overtime_hours_extra DECIMAL(8,2) DEFAULT 0.00,
    nighttime_hours DECIMAL(8,2) DEFAULT 0.00,
    sunday_hours DECIMAL(8,2) DEFAULT 0.00,

    -- Minutos tardanzas
    total_late_minutes INT DEFAULT 0,

    -- Montos calculados
    amount_worked_hours DECIMAL(10,2) DEFAULT 0.00,
    amount_overtime_normal DECIMAL(10,2) DEFAULT 0.00,
    amount_overtime_extra DECIMAL(10,2) DEFAULT 0.00,
    amount_nighttime DECIMAL(10,2) DEFAULT 0.00,
    amount_sunday DECIMAL(10,2) DEFAULT 0.00,
    amount_late_deduction DECIMAL(10,2) DEFAULT 0.00,
    amount_absence_deduction DECIMAL(10,2) DEFAULT 0.00,
    amount_punctuality_bonus DECIMAL(10,2) DEFAULT 0.00,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_payroll_employee (payroll_id, employee_id),
    INDEX idx_payroll (payroll_id),
    INDEX idx_employee (employee_id),
    FOREIGN KEY (payroll_id) REFERENCES planilla_cabecera(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
```

**Propósito**: Resumen de asistencias por empleado para cada planilla generada.

#### **Tabla 8: `attendance_concepts_mapping`**
```sql
CREATE TABLE attendance_concepts_mapping (
    id INT PRIMARY KEY AUTO_INCREMENT,
    attendance_type VARCHAR(50) NOT NULL COMMENT 'WORKED_HOURS, OVERTIME_NORMAL, etc.',
    concept_code VARCHAR(20) NOT NULL COMMENT 'Código del concepto en tabla concepto',
    concept_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_type (attendance_type),
    INDEX idx_concept (concept_id),
    FOREIGN KEY (concept_id) REFERENCES concepto(id) ON DELETE SET NULL
);
```

**Propósito**: Mapeo configurable entre tipos de asistencia y conceptos de planilla.

---

## 🖥️ SUBFASE 7.5: INTERFAZ Y REPORTES

### **Componentes a Crear**

#### **1. Dashboard de Asistencias**
**Vista**: `app/Views/admin/attendance/dashboard.php`

**Componentes**:
- **Filtros**: Fecha, empleado, departamento
- **Cards Resumen**:
  - Total Presentes Hoy
  - Total Tardanzas del Mes
  - Total Ausencias del Mes
  - Puntualidad Promedio (%)
- **Gráfico de Asistencia**: Últimos 30 días (Chart.js)
- **Tabla Top Puntualidad**: Mejores empleados del mes
- **Tabla Alertas**: Ausencias injustificadas, excesos de jornada

#### **2. Vista Empleado - Mis Asistencias**
**Vista**: `app/Views/employee/my_attendance.php`

**Componentes**:
- Calendario con marcaciones del mes (FullCalendar.js)
- Resumen personal (horas trabajadas, tardanzas, ausencias)
- Botón "Justificar Ausencia"
- Historial de justificaciones

#### **3. Vista Gerencial - Asistencias por Departamento**
**Vista**: `app/Views/admin/attendance/by_department.php`

**Componentes**:
- Filtros: Departamento, período
- Tabla DataTables con todos los empleados
- Indicadores visuales (badges) para cada día
- Export Excel/PDF
- Gráficos comparativos por departamento

#### **4. Vista Reportes Ejecutivos**
**Vista**: `app/Views/admin/attendance/executive_reports.php`

**Tipos de Reportes**:
- Reporte de Puntualidad
- Reporte de Ausentismo
- Reporte de Horas Extras
- Reporte de Costo de Horas Extras
- Reporte de Cumplimiento Legal

---

## 📅 PLAN DE IMPLEMENTACIÓN

### **Sprint 1: Subfase 7.1 - Integración API (2 semanas)**
**Semana 1**:
- Día 1-2: Crear clase `Base44ApiClient` + tests de conexión
- Día 3-4: Crear tablas BD (`attendance_api_config`, `attendance_raw_data`, `attendance_sync_log`)
- Día 5: Crear interfaz de configuración API

**Semana 2**:
- Día 1-2: Crear `AttendanceSyncService`
- Día 3: Crear cron job de sincronización
- Día 4: Crear `Base44WebhookController`
- Día 5: Testing completo + ajustes

**Entregables**:
- ✅ Conexión API Base44 funcional
- ✅ Sincronización automática cada 15 minutos
- ✅ Datos crudos guardados en BD
- ✅ Interfaz de configuración

---

### **Sprint 2: Subfase 7.2 - Cálculos Avanzados (2 semanas)**
**Semana 1**:
- Día 1-2: Crear tablas BD (`attendance_records`, `attendance_calculations`, `attendance_exceptions`)
- Día 3-4: Crear `AttendanceCalculator` + tests unitarios
- Día 5: Crear `OvertimeCalculator`

**Semana 2**:
- Día 1-2: Crear `AbsenceDetector`
- Día 3: Crear `WorkScheduleResolver`
- Día 4-5: Integrar calculadoras con `AttendanceSyncService`

**Entregables**:
- ✅ Cálculos automáticos de horas trabajadas
- ✅ Detección de tardanzas/salidas anticipadas
- ✅ Cálculo de horas extras por tipo
- ✅ Detección automática de ausencias

---

### **Sprint 3: Subfase 7.3 - Legislación + BusinessCalendar (1-2 semanas)**
**Semana 1**:
- Día 1-2: Crear `LegalComplianceChecker`
- Día 3: Agregar métodos a `BusinessCalendar` (isHoliday, isSunday, etc.)
- Día 4: Integrar BusinessCalendar en `OvertimeCalculator`
- Día 5: Testing cumplimiento legislación panameña

**Semana 2** (opcional si se requiere):
- Día 1-2: Actualizar `LiquidationCalculator` con BusinessCalendar
- Día 3-4: Actualizar cálculos de vacaciones con BusinessCalendar
- Día 5: Testing integral

**Entregables**:
- ✅ Validación cumplimiento jornadas legales
- ✅ Clasificación días laborables/festivos/domingos
- ✅ Cálculo preciso de preaviso con días laborables
- ✅ Alertas de incumplimientos

---

### **Sprint 4: Subfase 7.4 - Integración Planillas (1-2 semanas)**
**Semana 1**:
- Día 1: Crear tablas BD (`payroll_attendance_summary`, `attendance_concepts_mapping`)
- Día 2-3: Crear `PayrollAttendanceIntegrator`
- Día 4: Crear `AttendanceConceptMapper`
- Día 5: Agregar conceptos de asistencia a BD

**Semana 2** (opcional si se requiere):
- Día 1-2: Agregar funciones ATTENDANCE_*() a `PlanillaConceptCalculator`
- Día 3-4: Integrar en flujo de generación de planillas
- Día 5: Testing completo + validación con planillas reales

**Entregables**:
- ✅ Conceptos de asistencia en planillas
- ✅ Cálculo automático de montos
- ✅ Resumen de asistencias por planilla
- ✅ Integración completa con motor de fórmulas

---

### **Sprint 5: Subfase 7.5 - Interfaz y Reportes (1 semana)**
**Día 1-2**: Dashboard de asistencias + gráficos
**Día 3**: Vista empleado "Mis Asistencias"
**Día 4**: Vista gerencial por departamento
**Día 5**: Reportes ejecutivos + exports PDF/Excel

**Entregables**:
- ✅ Dashboard completo
- ✅ Vista empleado
- ✅ Reportes gerenciales
- ✅ Exports múltiples formatos

---

## ⏱️ ESTIMACIÓN DE TIEMPO

### **Desglose por Subfase**
| Subfase | Descripción | Tiempo Estimado | Complejidad |
|---------|-------------|-----------------|-------------|
| 7.1 | Integración API Externa | 2 semanas | Alta |
| 7.2 | Cálculos Avanzados | 2 semanas | Alta |
| 7.3 | Legislación + BusinessCalendar | 1-2 semanas | Media |
| 7.4 | Integración con Planillas | 1-2 semanas | Alta |
| 7.5 | Interfaz y Reportes | 1 semana | Media |
| **TOTAL** | | **7-9 semanas** | |

### **Factores de Riesgo**
- 🔴 **Alto**: API Base44 sin webhooks (requiere polling intensivo)
- 🟡 **Medio**: Complejidad de legislación panameña (múltiples tasas)
- 🟢 **Bajo**: BusinessCalendar ya implementado y funcional

### **Recursos Requeridos**
- 1 Desarrollador Full-Stack (PHP + JavaScript)
- Acceso a API Base44 con datos de prueba
- Documentación completa API Base44
- Asesoría legal panameña (opcional, para validación de cálculos)

---

## 🎯 CRITERIOS DE ÉXITO

### **Funcionales**
- ✅ Sincronización automática exitosa cada 15 minutos
- ✅ 100% de marcaciones sincronizadas sin pérdidas
- ✅ Cálculos de horas extras conformes legislación panameña
- ✅ Detección automática de ausencias con 99% precisión
- ✅ Conceptos de asistencia incluidos automáticamente en planillas

### **Técnicos**
- ✅ Tiempo de sincronización < 30 segundos (100 empleados)
- ✅ Logs completos de todas las operaciones
- ✅ Error rate < 0.1% en procesamiento de marcaciones
- ✅ Cobertura de tests > 80%

### **UX/UI**
- ✅ Dashboard carga en < 2 segundos
- ✅ Exportación de reportes en < 5 segundos
- ✅ Interfaz responsive (móvil + desktop)
- ✅ Feedback visual en todas las operaciones

---

## 📚 REFERENCIAS

### **Legislación Panameña**
- Código de Trabajo de Panamá - Libro Primero (Título II - Contrato Individual)
- Art. 31: Jornada Ordinaria (8h/día, 48h/semana)
- Art. 35: Tiempo de Comida (mínimo 30 minutos)
- Art. 38: Jornada Nocturna (6PM-6AM, +50%)
- Art. 39: Horas Extras (primeras 3h +25%, adicionales +50%)
- Art. 48: Trabajo Domingos/Feriados (+50%)
- Art. 213: Causales Despido (3+ ausencias injustificadas)

### **API Base44**
- Documentación: `documentation/attendance/examples.md`
- Endpoint Employees: `https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Employee`
- Endpoint Attendance: `https://app.base44.com/api/apps/68dd9181444436f4bd157e1d/entities/Attendance`

### **Código Existente**
- Modelo Attendance: `app/Models/Attendance.php`
- Controlador Attendance: `app/Controllers/Attendance.php`
- Modelo BusinessCalendar: `app/Models/BusinessCalendar.php`

---

**Estado del Documento**: 🟢 Completo
**Última Actualización**: 9 de Octubre, 2025
**Próxima Revisión**: Al iniciar cada sprint
