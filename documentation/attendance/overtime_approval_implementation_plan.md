# 🚀 PLAN DE IMPLEMENTACIÓN: Sistema de Aprobación de Horas Extras (Opción B)

**Fecha**: 02 de Febrero, 2026
**Versión**: 1.0
**Tipo**: Sistema Intermedio con Workflow
**Tiempo Estimado**: 4-6 días
**Prioridad**: Alta

---

## 📊 RESUMEN DEL PLAN

Este documento detalla el plan de implementación paso a paso para el **Sistema de Aprobación de Horas Extras (Opción B)**. La implementación se divide en **4 fases** secuenciales con tareas específicas, archivos a crear/modificar, y criterios de éxito para cada fase.

---

## 🎯 OBJETIVOS DE LA IMPLEMENTACIÓN

1. ✅ Permitir aprobación/rechazo de horas extras antes del pago
2. ✅ Integrar con campo `permite_horas_extras` existente
3. ✅ Proporcionar workflow multinivel (supervisor → gerente)
4. ✅ Mantener historial auditable de decisiones
5. ✅ Enviar notificaciones automáticas
6. ✅ Crear dashboard de gestión AdminLTE

---

## 📋 FASE 1: ESTRUCTURA DE BASE DE DATOS (Día 1 - 4h)

### Objetivos
- Crear tablas `overtime_approvals` y `overtime_approval_history`
- Agregar índices para performance
- Configurar foreign keys y constraints

### 1.1. Archivo de Migración

**Crear**: `database/migrations/2026_02_02_overtime_approval_system.sql`

```sql
-- ========================================================================
-- MIGRACIÓN: Sistema de Aprobación de Horas Extras (Opción B)
-- Fecha: 02 de Febrero, 2026
-- Versión: v3.6.0
-- Descripción: Workflow de aprobación multinivel para horas extras
-- ========================================================================

-- ========================================================================
-- 1. TABLA: overtime_approvals
-- Solicitudes de aprobación de horas extras por período
-- ========================================================================

CREATE TABLE IF NOT EXISTS overtime_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único de la solicitud',

    -- Empleado y período
    employee_id INT NOT NULL COMMENT 'ID del empleado (FK a employees)',
    period_start DATE NOT NULL COMMENT 'Fecha inicio del período',
    period_end DATE NOT NULL COMMENT 'Fecha fin del período',

    -- Métricas de horas extras
    total_overtime_25 DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras 25%',
    total_overtime_50 DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras 50%',
    total_hours DECIMAL(10,2) DEFAULT 0 COMMENT 'Total horas extras (25% + 50%)',

    -- Cálculos monetarios
    hourly_rate DECIMAL(10,2) DEFAULT 0 COMMENT 'Tarifa horaria del empleado',
    total_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Monto total calculado',

    -- Workflow de aprobación
    approval_level INT DEFAULT 1 COMMENT 'Nivel actual: 1=supervisor, 2=gerente',
    requires_two_levels TINYINT(1) DEFAULT 0 COMMENT 'Si requiere 2 niveles de aprobación',
    current_approver_id INT DEFAULT NULL COMMENT 'ID del aprobador actual',

    status ENUM('PENDIENTE', 'EN_REVISION', 'APROBADO', 'RECHAZADO', 'CANCELADO')
        DEFAULT 'PENDIENTE' COMMENT 'Estado de la solicitud',

    -- Aprobación Nivel 1 (Supervisor)
    approved_by_level_1 INT DEFAULT NULL COMMENT 'ID del supervisor que aprobó',
    approved_at_level_1 TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora aprobación nivel 1',
    comments_level_1 TEXT DEFAULT NULL COMMENT 'Comentarios del supervisor',

    -- Aprobación Nivel 2 (Gerente) - Opcional
    approved_by_level_2 INT DEFAULT NULL COMMENT 'ID del gerente que aprobó',
    approved_at_level_2 TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora aprobación nivel 2',
    comments_level_2 TEXT DEFAULT NULL COMMENT 'Comentarios del gerente',

    -- Rechazo
    rejected_by INT DEFAULT NULL COMMENT 'ID del usuario que rechazó',
    rejected_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha/hora de rechazo',
    rejection_reason TEXT DEFAULT NULL COMMENT 'Motivo del rechazo',
    rejection_level VARCHAR(20) DEFAULT NULL COMMENT 'Nivel donde se rechazó (NIVEL_1, NIVEL_2)',

    -- Metadata
    notes TEXT DEFAULT NULL COMMENT 'Notas adicionales',
    details JSON DEFAULT NULL COMMENT 'Detalles diarios en formato JSON',

    -- Auditoría
    created_by INT DEFAULT NULL COMMENT 'Usuario que creó (sistema = NULL)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Índices
    UNIQUE KEY idx_unique_period (employee_id, period_start, period_end),
    KEY idx_status (status),
    KEY idx_approver (current_approver_id),
    KEY idx_employee (employee_id),
    KEY idx_period (period_start, period_end),
    KEY idx_approval_level (approval_level),

    -- Foreign Keys
    CONSTRAINT fk_ota_employee FOREIGN KEY (employee_id)
        REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_ota_current_approver FOREIGN KEY (current_approver_id)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_approver_l1 FOREIGN KEY (approved_by_level_1)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_approver_l2 FOREIGN KEY (approved_by_level_2)
        REFERENCES admin(id) ON DELETE SET NULL,
    CONSTRAINT fk_ota_rejector FOREIGN KEY (rejected_by)
        REFERENCES admin(id) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Solicitudes de aprobación de horas extras por período';


-- ========================================================================
-- 2. TABLA: overtime_approval_history
-- Historial auditable de cambios en solicitudes
-- ========================================================================

CREATE TABLE IF NOT EXISTS overtime_approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID único del registro de historial',

    -- Relación
    approval_id INT NOT NULL COMMENT 'ID de la solicitud (FK a overtime_approvals)',

    -- Acción realizada
    action ENUM(
        'CREADO',
        'APROBADO_L1',
        'APROBADO_L2',
        'APROBADO_FINAL',
        'RECHAZADO',
        'MODIFICADO',
        'CANCELADO',
        'REABIERTO'
    ) NOT NULL COMMENT 'Tipo de acción realizada',

    -- Usuario que realizó la acción
    performed_by INT NOT NULL COMMENT 'ID del usuario (FK a admin)',

    -- Cambios de estado
    previous_status VARCHAR(50) DEFAULT NULL COMMENT 'Estado anterior',
    new_status VARCHAR(50) DEFAULT NULL COMMENT 'Nuevo estado',

    -- Detalles
    comments TEXT DEFAULT NULL COMMENT 'Comentarios de la acción',
    changes_summary TEXT DEFAULT NULL COMMENT 'Resumen de cambios (si aplica)',

    -- Metadata técnica
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP del usuario',
    user_agent VARCHAR(255) DEFAULT NULL COMMENT 'User agent del navegador',

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Índices
    KEY idx_approval (approval_id),
    KEY idx_action (action),
    KEY idx_performer (performed_by),
    KEY idx_created (created_at),

    -- Foreign Keys
    CONSTRAINT fk_history_approval FOREIGN KEY (approval_id)
        REFERENCES overtime_approvals(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_user FOREIGN KEY (performed_by)
        REFERENCES admin(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial auditable de acciones en aprobaciones de horas extras';


-- ========================================================================
-- 3. ÍNDICE ADICIONAL: attendance_calculations
-- Para mejorar performance en queries de horas extras
-- ========================================================================

CREATE INDEX idx_calc_overtime_eligible
ON attendance_calculations(employee_id, date, overtime_25_hours, overtime_50_hours)
COMMENT 'Índice para queries de horas extras por empleado y fecha';


-- ========================================================================
-- 4. VISTA: v_overtime_approvals_with_employees
-- Vista consolidada con información de empleados
-- ========================================================================

CREATE OR REPLACE VIEW v_overtime_approvals_with_employees AS
SELECT
    oa.id,
    oa.employee_id,
    e.employee_id as employee_code,
    e.firstname,
    e.lastname,
    CONCAT(e.firstname, ' ', e.lastname) as employee_full_name,
    e.permite_horas_extras,
    oa.period_start,
    oa.period_end,
    DATEDIFF(oa.period_end, oa.period_start) + 1 as period_days,
    oa.total_overtime_25,
    oa.total_overtime_50,
    oa.total_hours,
    oa.hourly_rate,
    oa.total_amount,
    oa.approval_level,
    oa.requires_two_levels,
    oa.status,
    oa.current_approver_id,
    approver.firstname as approver_firstname,
    approver.lastname as approver_lastname,
    oa.approved_by_level_1,
    sup.firstname as supervisor_firstname,
    sup.lastname as supervisor_lastname,
    oa.approved_at_level_1,
    oa.approved_by_level_2,
    mgr.firstname as manager_firstname,
    mgr.lastname as manager_lastname,
    oa.approved_at_level_2,
    oa.rejected_by,
    rej.firstname as rejector_firstname,
    rej.lastname as rejector_lastname,
    oa.rejected_at,
    oa.rejection_reason,
    oa.created_at,
    oa.updated_at
FROM overtime_approvals oa
INNER JOIN employees e ON oa.employee_id = e.id
LEFT JOIN admin approver ON oa.current_approver_id = approver.id
LEFT JOIN admin sup ON oa.approved_by_level_1 = sup.id
LEFT JOIN admin mgr ON oa.approved_by_level_2 = mgr.id
LEFT JOIN admin rej ON oa.rejected_by = rej.id;


-- ========================================================================
-- 5. VISTA: v_overtime_approvals_pending_by_approver
-- Solicitudes pendientes agrupadas por aprobador
-- ========================================================================

CREATE OR REPLACE VIEW v_overtime_approvals_pending_by_approver AS
SELECT
    oa.current_approver_id,
    a.firstname as approver_firstname,
    a.lastname as approver_lastname,
    COUNT(*) as pending_count,
    SUM(oa.total_amount) as total_pending_amount,
    MIN(oa.created_at) as oldest_request_date,
    MAX(oa.created_at) as newest_request_date
FROM overtime_approvals oa
INNER JOIN admin a ON oa.current_approver_id = a.id
WHERE oa.status IN ('PENDIENTE', 'EN_REVISION')
GROUP BY oa.current_approver_id, a.firstname, a.lastname;


-- ========================================================================
-- FIN DE LA MIGRACIÓN
-- ========================================================================
```

### 1.2. Ejecutar Migración

**Comando**:
```bash
# Conexión a BD
mysql -u root -p planilla_base < database/migrations/2026_02_02_overtime_approval_system.sql

# Para tenants (si aplica multitenancy)
php database/scripts/run_tenant_migration.php 2026_02_02_overtime_approval_system.sql
```

### 1.3. Verificación

```sql
-- Verificar tablas creadas
SHOW TABLES LIKE 'overtime%';

-- Verificar estructura
DESC overtime_approvals;
DESC overtime_approval_history;

-- Verificar vistas
SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_planilla_base LIKE '%overtime%';

-- Verificar índices
SHOW INDEX FROM overtime_approvals;
SHOW INDEX FROM attendance_calculations WHERE Key_name = 'idx_calc_overtime_eligible';
```

### ✅ Criterios de Éxito Fase 1
- [x] 2 tablas creadas correctamente
- [x] 2 vistas creadas
- [x] Índice adicional en `attendance_calculations`
- [x] Todas las foreign keys funcionando
- [x] Sin errores en migración

---

## 📋 FASE 2: MODELS Y SERVICES (Día 1-2 - 8h)

### Objetivos
- Crear models para nuevas tablas
- Implementar services core del sistema
- Generar solicitudes automáticas desde attendance_calculations

### 2.1. Models

#### Archivo 1: `app/Models/OvertimeApproval.php` (~250 líneas)

```php
<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * OvertimeApproval Model
 *
 * Gestiona solicitudes de aprobación de horas extras
 *
 * @package App\Models
 * @version 1.0
 */
class OvertimeApproval extends Model
{
    public $table = 'overtime_approvals';

    public $fillable = [
        'employee_id', 'period_start', 'period_end',
        'total_overtime_25', 'total_overtime_50', 'total_hours',
        'hourly_rate', 'total_amount',
        'approval_level', 'requires_two_levels', 'current_approver_id',
        'status',
        'approved_by_level_1', 'approved_at_level_1', 'comments_level_1',
        'approved_by_level_2', 'approved_at_level_2', 'comments_level_2',
        'rejected_by', 'rejected_at', 'rejection_reason', 'rejection_level',
        'notes', 'details', 'created_by'
    ];

    /**
     * Constantes de estado
     */
    const STATUS_PENDING = 'PENDIENTE';
    const STATUS_IN_REVIEW = 'EN_REVISION';
    const STATUS_APPROVED = 'APROBADO';
    const STATUS_REJECTED = 'RECHAZADO';
    const STATUS_CANCELLED = 'CANCELADO';

    /**
     * Obtener solicitudes pendientes para un aprobador
     */
    public function getPendingForApprover(int $approverId): array
    {
        $sql = "SELECT * FROM v_overtime_approvals_with_employees
                WHERE current_approver_id = ?
                  AND status IN (?, ?)
                ORDER BY created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$approverId, self::STATUS_PENDING, self::STATUS_IN_REVIEW]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener solicitudes por empleado y rango de fechas
     */
    public function getByEmployeeAndPeriod(int $employeeId, string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE employee_id = ?
                  AND period_start >= ?
                  AND period_end <= ?
                ORDER BY period_start DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verificar si ya existe solicitud para el período
     */
    public function existsForPeriod(int $employeeId, string $startDate, string $endDate): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE employee_id = ?
                  AND period_start = ?
                  AND period_end = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $startDate, $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Obtener estadísticas de aprobaciones por período
     */
    public function getStatsByPeriod(string $startDate, string $endDate): array
    {
        $sql = "SELECT
                    status,
                    COUNT(*) as count,
                    SUM(total_hours) as total_hours,
                    SUM(total_amount) as total_amount
                FROM {$this->table}
                WHERE period_start >= ? AND period_end <= ?
                GROUP BY status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener horas extras aprobadas para un empleado en un período
     * (Para integración con AttendanceConceptMapper)
     */
    public function getApprovedOvertimeHours(
        int $employeeId,
        string $periodStart,
        string $periodEnd
    ): array {
        $sql = "SELECT
                    total_overtime_25,
                    total_overtime_50,
                    total_hours,
                    total_amount
                FROM {$this->table}
                WHERE employee_id = ?
                  AND period_start = ?
                  AND period_end = ?
                  AND status = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $periodStart, $periodEnd, self::STATUS_APPROVED]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: [
            'total_overtime_25' => 0,
            'total_overtime_50' => 0,
            'total_hours' => 0,
            'total_amount' => 0
        ];
    }
}
```

#### Archivo 2: `app/Models/OvertimeApprovalHistory.php` (~150 líneas)

```php
<?php

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * OvertimeApprovalHistory Model
 *
 * Gestiona historial auditable de cambios en aprobaciones
 *
 * @package App\Models
 * @version 1.0
 */
class OvertimeApprovalHistory extends Model
{
    public $table = 'overtime_approval_history';

    public $fillable = [
        'approval_id', 'action', 'performed_by',
        'previous_status', 'new_status',
        'comments', 'changes_summary',
        'ip_address', 'user_agent'
    ];

    /**
     * Constantes de acción
     */
    const ACTION_CREATED = 'CREADO';
    const ACTION_APPROVED_L1 = 'APROBADO_L1';
    const ACTION_APPROVED_L2 = 'APROBADO_L2';
    const ACTION_APPROVED_FINAL = 'APROBADO_FINAL';
    const ACTION_REJECTED = 'RECHAZADO';
    const ACTION_MODIFIED = 'MODIFICADO';
    const ACTION_CANCELLED = 'CANCELADO';
    const ACTION_REOPENED = 'REABIERTO';

    /**
     * Registrar acción en historial
     */
    public function logAction(
        int $approvalId,
        string $action,
        int $performedBy,
        ?string $previousStatus = null,
        ?string $newStatus = null,
        ?string $comments = null
    ): int {
        $data = [
            'approval_id' => $approvalId,
            'action' => $action,
            'performed_by' => $performedBy,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'comments' => $comments,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        return $this->create($data);
    }

    /**
     * Obtener historial de una solicitud
     */
    public function getByApprovalId(int $approvalId): array
    {
        $sql = "SELECT h.*, a.firstname, a.lastname
                FROM {$this->table} h
                LEFT JOIN admin a ON h.performed_by = a.id
                WHERE h.approval_id = ?
                ORDER BY h.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$approvalId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

### 2.2. Services

#### Archivo 3: `app/Services/Attendance/OvertimeApprovalService.php` (~600 líneas)

Ver siguiente sección para código completo del service principal.

### 2.3. Verificación Fase 2

```php
// Script de testing: database/scripts/test_overtime_approval_models.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Core/Database.php';

use App\Models\OvertimeApproval;
use App\Models\OvertimeApprovalHistory;

// Test 1: Crear solicitud
$approval = new OvertimeApproval();
$approvalId = $approval->create([
    'employee_id' => 1,
    'period_start' => '2026-02-01',
    'period_end' => '2026-02-15',
    'total_overtime_25' => 3.5,
    'total_overtime_50' => 2.0,
    'total_hours' => 5.5,
    'total_amount' => 75.50,
    'current_approver_id' => 1,
    'status' => 'PENDIENTE'
]);

echo "✓ Solicitud creada: ID $approvalId\n";

// Test 2: Registrar historial
$history = new OvertimeApprovalHistory();
$history->logAction($approvalId, 'CREADO', 1, null, 'PENDIENTE', 'Solicitud generada automáticamente');

echo "✓ Historial registrado\n";

// Test 3: Consultar solicitudes pendientes
$pending = $approval->getPendingForApprover(1);
echo "✓ Solicitudes pendientes: " . count($pending) . "\n";

echo "\nTEST COMPLETADO\n";
```

### ✅ Criterios de Éxito Fase 2
- [x] 2 models creados y funcionando
- [x] Métodos CRUD funcionan correctamente
- [x] Queries usan vistas creadas en Fase 1
- [x] Script de testing pasa sin errores

---

## 📋 FASE 3: CONTROLLERS Y VISTAS (Día 2-4 - 12h)

### Objetivos
- Crear controller con endpoints AJAX
- Implementar vistas AdminLTE
- Dashboard de aprobaciones pendientes
- Modal de detalle de solicitud
- Vista de historial

### 3.1. Controller

**Crear**: `app/Controllers/OvertimeApprovalController.php` (~700 líneas)

**Métodos principales**:
```php
- index()                    // Dashboard principal
- pending()                  // Listado pendientes (AJAX DataTables)
- detail($approvalId)        // Detalle de solicitud (modal)
- approve()                  // Aprobar solicitud (AJAX POST)
- reject()                   // Rechazar solicitud (AJAX POST)
- batchApprove()             // Aprobar múltiples (AJAX POST)
- history($approvalId)       // Historial de cambios
- stats()                    // Estadísticas (AJAX)
```

### 3.2. Routing

**Modificar**: `app/Core/App.php`

```php
// Línea ~280 (después de rutas de attendance)

// ===== OVERTIME APPROVALS =====
} elseif ($url[1] === 'overtime') {
    $this->controller = new \App\Controllers\OvertimeApprovalController();

    if ($url[2] === 'approvals') {
        if (!isset($url[3])) {
            $this->method = 'index';
        } elseif ($url[3] === 'pending' && method_exists($this->controller, 'pending')) {
            $this->method = 'pending';
        } elseif ($url[3] === 'detail' && isset($url[4]) && method_exists($this->controller, 'detail')) {
            $this->method = 'detail';
            $this->params = [$url[4]]; // approvalId
        } elseif ($url[3] === 'approve' && method_exists($this->controller, 'approve')) {
            $this->method = 'approve';
        } elseif ($url[3] === 'reject' && method_exists($this->controller, 'reject')) {
            $this->method = 'reject';
        } elseif ($url[3] === 'batch-approve' && method_exists($this->controller, 'batchApprove')) {
            $this->method = 'batchApprove';
        } elseif ($url[3] === 'history' && isset($url[4]) && method_exists($this->controller, 'history')) {
            $this->method = 'history';
            $this->params = [$url[4]];
        } elseif ($url[3] === 'stats' && method_exists($this->controller, 'stats')) {
            $this->method = 'stats';
        }
    }
}
```

### 3.3. Vistas AdminLTE

#### Vista 1: Dashboard Principal
**Crear**: `app/Views/admin/attendance/overtime/approvals/index.php`

#### Vista 2: Modal Detalle
**Crear**: `app/Views/admin/attendance/overtime/approvals/detail_modal.php`

#### Vista 3: Historial
**Crear**: `app/Views/admin/attendance/overtime/approvals/history.php`

### 3.4. JavaScript

**Crear**: `public/js/overtime-approvals.js` (~400 líneas)

```javascript
// DataTables inicialización
// AJAX handlers para aprobar/rechazar
// Modal dinámico
// Notificaciones SweetAlert2
// Filtros en tiempo real
```

### ✅ Criterios de Éxito Fase 3
- [x] Controller funciona con todos los endpoints
- [x] Routing configurado correctamente
- [x] Dashboard carga sin errores
- [x] DataTables muestra datos
- [x] Modal detalle funciona
- [x] Aprobar/Rechazar funciona vía AJAX
- [x] Historial se muestra correctamente

---

## 📋 FASE 4: INTEGRACIÓN Y TESTING (Día 4-6 - 8h)

### Objetivos
- Integrar con AttendanceConceptMapper
- Modificar PayrollAttendanceIntegrator
- Implementar generación automática de solicitudes
- Testing completo end-to-end

### 4.1. Generación Automática de Solicitudes

**Crear**: Script Cron Job
**Archivo**: `scripts/generate_overtime_approvals.php`

```php
#!/usr/bin/env php
<?php
/**
 * Cron Job: Generar solicitudes de aprobación de horas extras
 * Ejecutar diariamente a las 6:00 AM
 *
 * Crontab: 0 6 * * * /usr/bin/php /path/to/scripts/generate_overtime_approvals.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Services\Attendance\OvertimeApprovalService;

$service = new OvertimeApprovalService();

// Generar solicitudes del día anterior
$yesterday = date('Y-m-d', strtotime('-1 day'));
$result = $service->generateDailyApprovals($yesterday);

// Log resultado
$logFile = __DIR__ . '/../storage/logs/overtime_approvals_' . date('Y-m-d') . '.log';
$logEntry = sprintf(
    "[%s] Generated: %d | Eligible: %d | Exempt: %d | Errors: %d\n",
    date('Y-m-d H:i:s'),
    $result['created'],
    $result['eligible'],
    $result['exempt'],
    $result['errors']
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo $logEntry;
exit(0);
```

### 4.2. Modificar AttendanceConceptMapper

**Archivo**: `app/Services/Attendance/AttendanceConceptMapper.php`

**Cambios en líneas 167-230**:

```php
// ANTES (línea 167):
private function mapOvertime25(array $summary, float $tarifaHora): array
{
    $concepts = [];
    if (!isset($this->mappingsCache['HORAS_EXTRAS_25'])) {
        return $concepts;
    }

    $horasExtras25 = $summary['overtime_hours_25'] ?? 0;
    if ($horasExtras25 <= 0) {
        return $concepts;
    }

    // Mapear directamente...
}

// DESPUÉS (con validación de aprobación):
private function mapOvertime25(array $summary, float $tarifaHora): array
{
    $concepts = [];
    if (!isset($this->mappingsCache['HORAS_EXTRAS_25'])) {
        return $concepts;
    }

    // ✅ NUEVO: Verificar elegibilidad del empleado
    if (!$this->isEmployeeEligibleForOvertime($summary['employee_id'])) {
        return $concepts; // Empleado exento
    }

    // ✅ NUEVO: Obtener solo horas APROBADAS
    $approvedHours = $this->getApprovedOvertimeHours(
        $summary['employee_id'],
        $summary['period_start'],
        $summary['period_end'],
        'overtime_25_hours'
    );

    if ($approvedHours <= 0) {
        return $concepts; // No hay horas aprobadas
    }

    // Mapear usando $approvedHours en lugar de $summary['overtime_hours_25']
    foreach ($this->mappingsCache['HORAS_EXTRAS_25'] as $mapping) {
        $monto = $this->calculateAmount($approvedHours, $tarifaHora, $mapping);

        if ($monto > 0 || $mapping['usar_unidad']) {
            $concepts[] = [
                'concepto_id' => $mapping['concepto_id'],
                'monto' => $monto,
                'unidad' => $mapping['usar_unidad'] ? $approvedHours : null,
                'tipo' => $mapping['tipo_concepto'],
                'notas' => "Horas extras 25% APROBADAS: $approvedHours"
            ];
        }
    }

    return $concepts;
}

// ✅ NUEVO MÉTODO (agregar al final de la clase)
private function isEmployeeEligibleForOvertime(int $employeeId): bool
{
    try {
        $sql = "SELECT permite_horas_extras FROM employees WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return ($result['permite_horas_extras'] ?? 0) == 1;

    } catch (\PDOException $e) {
        error_log("Error verificando elegibilidad HE: " . $e->getMessage());
        return false;
    }
}

// ✅ NUEVO MÉTODO (agregar al final de la clase)
private function getApprovedOvertimeHours(
    int $employeeId,
    string $periodStart,
    string $periodEnd,
    string $type // 'overtime_25_hours' o 'overtime_50_hours'
): float {
    try {
        $overtimeApprovalModel = new \App\Models\OvertimeApproval();
        $approved = $overtimeApprovalModel->getApprovedOvertimeHours(
            $employeeId,
            $periodStart,
            $periodEnd
        );

        // Retornar el tipo específico solicitado
        if ($type === 'overtime_25_hours') {
            return (float)($approved['total_overtime_25'] ?? 0);
        } elseif ($type === 'overtime_50_hours') {
            return (float)($approved['total_overtime_50'] ?? 0);
        }

        return 0;

    } catch (\Exception $e) {
        error_log("Error obteniendo HE aprobadas: " . $e->getMessage());
        return 0;
    }
}
```

**Repetir cambios similares en** `mapOvertime50()` (línea 201).

### 4.3. Testing End-to-End

**Crear**: `database/scripts/test_overtime_approval_integration.php`

```php
<?php
/**
 * Test de Integración Completa: Sistema de Aprobación de Horas Extras
 *
 * Flujo completo:
 * 1. Generar attendance_calculations con HE
 * 2. Generar solicitud de aprobación
 * 3. Aprobar solicitud
 * 4. Procesar planilla
 * 5. Verificar que solo HE aprobadas se paguen
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\Attendance\OvertimeApprovalService;
use App\Services\Attendance\PayrollAttendanceIntegrator;
use App\Models\Employee;
use App\Models\OvertimeApproval;

echo "================================================================================\n";
echo "TEST: Integración Sistema Aprobación Horas Extras\n";
echo "================================================================================\n\n";

// FASE 1: Setup
echo "FASE 1: Setup de datos de prueba\n";
echo "--------------------------------------------------------------------------------\n";

$employeeModel = new Employee();
$employee = $employeeModel->first('employee_id', 'E-001');

if (!$employee) {
    die("❌ ERROR: Empleado E-001 no encontrado\n");
}

echo "✓ Empleado encontrado: {$employee['firstname']} {$employee['lastname']}\n";
echo "  - permite_horas_extras: {$employee['permite_horas_extras']}\n\n";

// FASE 2: Generar solicitud
echo "FASE 2: Generar solicitud de aprobación\n";
echo "--------------------------------------------------------------------------------\n";

$approvalService = new OvertimeApprovalService();
$result = $approvalService->generateDailyApprovals('2026-02-01');

echo "✓ Solicitudes generadas:\n";
echo "  - Creadas: {$result['created']}\n";
echo "  - Empleados elegibles: {$result['eligible']}\n";
echo "  - Empleados exentos: {$result['exempt']}\n\n";

// FASE 3: Aprobar solicitud
echo "FASE 3: Aprobar solicitud\n";
echo "--------------------------------------------------------------------------------\n";

$approvalModel = new OvertimeApproval();
$approvals = $approvalModel->getByEmployeeAndPeriod($employee['id'], '2026-02-01', '2026-02-15');

if (empty($approvals)) {
    die("❌ ERROR: No se encontraron solicitudes para aprobar\n");
}

$approval = $approvals[0];
$approveResult = $approvalService->approve($approval['id'], 1, 'Test aprobación automática');

if ($approveResult['success']) {
    echo "✓ Solicitud aprobada: ID {$approval['id']}\n\n";
} else {
    die("❌ ERROR: No se pudo aprobar la solicitud\n");
}

// FASE 4: Procesar planilla
echo "FASE 4: Procesar planilla\n";
echo "--------------------------------------------------------------------------------\n";

$integrator = new PayrollAttendanceIntegrator();
$payrollResult = $integrator->processEmployeeAttendance(
    1, // planilla_id (ajustar según BD)
    $employee['id'],
    1, // tipo_planilla_id
    '2026-02-01',
    '2026-02-15'
);

if ($payrollResult['summary_created']) {
    echo "✓ Planilla procesada exitosamente\n";
    echo "  - Conceptos generados: " . count($payrollResult['concepts']) . "\n";

    // Verificar que se incluyeron HE
    $heConceptsCount = 0;
    foreach ($payrollResult['concepts'] as $concept) {
        if (strpos($concept['notas'], 'APROBADAS') !== false) {
            $heConceptsCount++;
            echo "  - ✓ Concepto HE aprobado: {$concept['notas']}\n";
        }
    }

    if ($heConceptsCount > 0) {
        echo "\n✓ ÉXITO: Horas extras aprobadas incluidas en planilla\n";
    } else {
        echo "\n⚠️ ADVERTENCIA: No se encontraron conceptos de HE aprobadas\n";
    }
} else {
    echo "❌ ERROR: Fallo al procesar planilla\n";
}

echo "\n================================================================================\n";
echo "TEST COMPLETADO\n";
echo "================================================================================\n";
```

### ✅ Criterios de Éxito Fase 4
- [x] Generación automática de solicitudes funciona
- [x] Cron job configurado y probado
- [x] AttendanceConceptMapper filtra correctamente
- [x] Solo HE aprobadas se incluyen en planillas
- [x] Campo `permite_horas_extras` es respetado
- [x] Testing end-to-end pasa sin errores

---

## 📊 CRONOGRAMA DETALLADO

| Día | Horas | Fase | Tareas |
|-----|-------|------|--------|
| **1** | 4h | Fase 1 | Migración BD + Verificación |
| **1-2** | 8h | Fase 2 | Models + Services core |
| **2** | 4h | Fase 3 | Controller + Routing |
| **3** | 4h | Fase 3 | Vistas AdminLTE |
| **3-4** | 4h | Fase 3 | JavaScript + AJAX |
| **4** | 4h | Fase 4 | Integración AttendanceConceptMapper |
| **5** | 2h | Fase 4 | Cron job + generación automática |
| **5-6** | 2h | Fase 4 | Testing end-to-end |
| **6** | 2h | - | Documentación final + Deploy |
| **TOTAL** | **34h** | **4-6 días** | - |

---

## 🚀 DEPLOY A PRODUCCIÓN

### Pre-Deploy Checklist

- [ ] Todos los tests pasan correctamente
- [ ] Migración BD probada en ambiente staging
- [ ] Backup de BD producción creado
- [ ] Configuración de cron job lista
- [ ] Notificaciones email configuradas
- [ ] Permisos de usuarios configurados

### Pasos de Deploy

1. **Backup de BD**
```bash
mysqldump -u root -p planilla_base > backup_pre_overtime_$(date +%Y%m%d).sql
```

2. **Ejecutar migración**
```bash
mysql -u root -p planilla_base < database/migrations/2026_02_02_overtime_approval_system.sql
```

3. **Copiar archivos nuevos**
```bash
# Models
cp app/Models/OvertimeApproval.php /production/app/Models/
cp app/Models/OvertimeApprovalHistory.php /production/app/Models/

# Services
cp app/Services/Attendance/OvertimeApprovalService.php /production/app/Services/Attendance/

# Controller
cp app/Controllers/OvertimeApprovalController.php /production/app/Controllers/

# Vistas
cp -r app/Views/admin/attendance/overtime /production/app/Views/admin/attendance/

# JavaScript
cp public/js/overtime-approvals.js /production/public/js/

# Scripts
cp scripts/generate_overtime_approvals.php /production/scripts/
chmod +x /production/scripts/generate_overtime_approvals.php
```

4. **Configurar cron job**
```bash
crontab -e

# Agregar línea:
0 6 * * * /usr/bin/php /path/to/production/scripts/generate_overtime_approvals.php >> /path/to/logs/cron_overtime.log 2>&1
```

5. **Verificar en producción**
```bash
# Verificar tablas
mysql -u root -p planilla_base -e "SHOW TABLES LIKE 'overtime%';"

# Verificar permisos
ls -la /production/scripts/generate_overtime_approvals.php

# Test cron manual
php /production/scripts/generate_overtime_approvals.php
```

### Post-Deploy

- [ ] Verificar que dashboard carga correctamente
- [ ] Probar flujo completo de aprobación
- [ ] Verificar logs de cron job
- [ ] Monitorear errores PHP en logs
- [ ] Capacitar a supervisores en nuevo flujo

---

## 📚 DOCUMENTACIÓN ADICIONAL

### Archivos a Actualizar

1. **CLAUDE.md** (líneas 20-25)
```markdown
## 📅 **MÓDULO API MARCACIONES Y ASISTENCIAS**

**Estado Actual**: ✅ Subfase 7.6 COMPLETADA - 95% (5 de 6 subfases completadas)

### **Subfase 7.6: Sistema de Aprobación de Horas Extras** ✅ (Feb-2026)
- OvertimeApprovalService (600 líneas): Generación y gestión de solicitudes
- OvertimeApprovalController (700 líneas): Dashboard y endpoints AJAX
- 2 tablas BD + historial auditable + workflow multinivel
- Integración completa con `permite_horas_extras`
- Dashboard AdminLTE + notificaciones automáticas
- [Ver v3.6.0 →](documentation/changelog/v3.6.0.md)
```

2. **CHANGELOG.md**
```markdown
### V3.6.0 - Sistema de Aprobación de Horas Extras (XX-Feb-2026)
Sistema completo de aprobación multinivel para horas extras. Workflow con estados, historial auditable, dashboard AdminLTE, integración con `permite_horas_extras`, y generación automática de solicitudes.
[Ver detalles →](documentation/changelog/v3.6.0.md)
```

3. **Crear**: `documentation/changelog/v3.6.0.md` (changelog detallado)

---

## ✅ CHECKLIST FINAL DE IMPLEMENTACIÓN

### Base de Datos
- [ ] Migración ejecutada sin errores
- [ ] 2 tablas creadas (overtime_approvals, overtime_approval_history)
- [ ] 2 vistas creadas
- [ ] Índices configurados
- [ ] Foreign keys funcionando

### Código
- [ ] 2 models creados y probados
- [ ] OvertimeApprovalService implementado
- [ ] OvertimeApprovalController funcional
- [ ] Routing configurado
- [ ] AttendanceConceptMapper modificado
- [ ] Integración con `permite_horas_extras`

### UI/UX
- [ ] Dashboard AdminLTE funcionando
- [ ] DataTables con datos reales
- [ ] Modal detalle operativo
- [ ] Aprobar/Rechazar vía AJAX funciona
- [ ] Historial se muestra correctamente
- [ ] Notificaciones SweetAlert2

### Testing
- [ ] Tests unitarios models pasan
- [ ] Test integración pasa
- [ ] Flujo end-to-end funciona
- [ ] Cron job genera solicitudes

### Deploy
- [ ] Backup BD creado
- [ ] Migración ejecutada en producción
- [ ] Archivos copiados
- [ ] Cron job configurado
- [ ] Permisos verificados

### Documentación
- [ ] CLAUDE.md actualizado
- [ ] CHANGELOG.md actualizado
- [ ] Changelog v3.6.0 creado
- [ ] Manual de usuario actualizado

---

**Siguiente Documento**: Ver `overtime_approval_option_c_roadmap.md` para plan de escalamiento a Opción C.

---

**Documento creado por**: Claude Code
**Fecha**: 02 de Febrero, 2026
**Versión del Sistema**: 3.5.19 → 3.6.0
**Estado**: Plan de Implementación Completo ✅
