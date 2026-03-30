<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AttendanceDetail
{
    private $db;
    private $table = 'attendance_detail';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene detalles por header_id
     */
    public function getByHeader($headerId, $filters = [])
    {
        $sql = "SELECT d.*,
                       e.firstname, e.lastname,
                       e.employee_id as employee_number,
                       COALESCE(d.scheduled_time_in, s.time_in) as scheduled_time_in,
                       COALESCE(d.scheduled_time_out, s.time_out) as scheduled_time_out,
                       dev.device_name
                FROM {$this->table} d
                INNER JOIN employees e ON d.employee_id = e.id
                LEFT JOIN schedules s ON d.schedule_id = s.id
                LEFT JOIN attendance_devices dev ON d.device_id = dev.id
                WHERE d.header_id = ?";

        $params = [$headerId];

        // Filtro por estado
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        // Filtro por empleado
        if (!empty($filters['employee_id'])) {
            $sql .= " AND d.employee_id = ?";
            $params[] = $filters['employee_id'];
        }

        // Filtro por tardanza
        if (isset($filters['is_late'])) {
            $sql .= " AND d.is_late = ?";
            $params[] = $filters['is_late'];
        }

        $sql .= " ORDER BY e.lastname, e.firstname";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene detalles por empleado
     */
    public function getByEmployee($employeeId, $filters = [])
    {
        $sql = "SELECT d.*, h.attendance_date, dev.device_name
                FROM {$this->table} d
                INNER JOIN attendance_header h ON d.header_id = h.id
                LEFT JOIN attendance_devices dev ON d.device_id = dev.id
                WHERE d.employee_id = ?";

        $params = [$employeeId];

        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $sql .= " AND h.attendance_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND h.attendance_date <= ?";
            $params[] = $filters['date_to'];
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = ?";
            $params[] = $filters['status'];
        }

        $sql .= " ORDER BY h.attendance_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los detalles de asistencia para una fecha específica
     */
    public function getByDate($date)
    {
        $sql = "SELECT d.*, h.attendance_date,
                       e.firstname, e.lastname, e.employee_id as employee_number
                FROM {$this->table} d
                INNER JOIN attendance_header h ON d.header_id = h.id
                INNER JOIN employees e ON d.employee_id = e.id
                WHERE h.attendance_date = ?
                ORDER BY e.lastname, e.firstname";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un detalle específico por fecha y empleado
     */
    public function getByDateAndEmployee($date, $employeeId, $deviceId = null)
    {
        $sql = "SELECT d.*, h.attendance_date
                FROM {$this->table} d
                INNER JOIN attendance_header h ON d.header_id = h.id
                WHERE h.attendance_date = ? AND d.employee_id = ?";

        $params = [$date, $employeeId];

        if ($deviceId) {
            $sql .= " AND d.device_id = ?";
            $params[] = $deviceId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo registro de detalle
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    header_id, employee_id, schedule_id,
                    time_in, time_out,
                    scheduled_time_in, scheduled_time_out,
                    device_id, external_id,
                    tardiness_minutes, is_late, early_departure_minutes, hours_worked,
                    status, justification_type, justification_notes, justification_document,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $data['header_id'],
            $data['employee_id'],
            $data['schedule_id'] ?? null,
            $data['time_in'] ?? null,
            $data['time_out'] ?? null,
            $data['scheduled_time_in'] ?? null,
            $data['scheduled_time_out'] ?? null,
            $data['device_id'] ?? null,
            $data['external_id'] ?? null,
            $data['tardiness_minutes'] ?? 0,
            $data['is_late'] ?? 0,
            $data['early_departure_minutes'] ?? 0,
            $data['hours_worked'] ?? 0,
            $data['status'] ?? 'PRESENT',
            $data['justification_type'] ?? null,
            $data['justification_notes'] ?? null,
            $data['justification_document'] ?? null,
            $data['notes'] ?? null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Actualiza un registro de detalle
     * Solo actualiza los campos que se proporcionen en $data
     */
    public function update($id, $data)
    {
        // Campos permitidos para actualización
        $allowedFields = [
            'time_in', 'time_out',
            'lunch_out', 'lunch_in',
            'scheduled_time_in', 'scheduled_time_out',
            'scheduled_lunch_out', 'scheduled_lunch_in',
            // Métricas que pueden ser ajustadas por trigger/servicios
            'lunch_duration_minutes', 'lunch_exceeded_minutes',
            'tardiness_minutes', 'is_late', 'early_departure_minutes', 'hours_worked',
            'status', 'justification_type', 'justification_notes', 'justification_document',
            'notes', 'schedule_id', 'device_id', 'external_id'
        ];

        // Filtrar solo los campos que están en $data y son permitidos
        $updateFields = [];
        $values = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "$field = ?";
                $values[] = $value;
            }
        }

        // Si no hay campos para actualizar, retornar false
        if (empty($updateFields)) {
            return false;
        }

        // Construir la consulta SQL dinámicamente
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = ?";

        // Agregar el ID al final de los valores
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Elimina un registro de detalle
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Elimina todos los registros de detalle de una cabecera
     * Útil para reprocesar un día completo
     */
    public function deleteByHeader($headerId)
    {
        $sql = "DELETE FROM {$this->table} WHERE header_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$headerId]);
    }

    /**
     * Inserta múltiples registros en batch
     */
    public function bulkInsert($records)
    {
        if (empty($records)) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO {$this->table} (
                        header_id, employee_id, schedule_id,
                        time_in, time_out,
                        scheduled_time_in, scheduled_time_out,
                        device_id, external_id,
                        tardiness_minutes, is_late, early_departure_minutes, hours_worked,
                        status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        time_in = VALUES(time_in),
                        time_out = VALUES(time_out),
                        hours_worked = VALUES(hours_worked),
                        status = VALUES(status)";

            $stmt = $this->db->prepare($sql);

            $inserted = 0;
            foreach ($records as $record) {
                $result = $stmt->execute([
                    $record['header_id'],
                    $record['employee_id'],
                    $record['schedule_id'] ?? null,
                    $record['time_in'] ?? null,
                    $record['time_out'] ?? null,
                    $record['scheduled_time_in'] ?? null,
                    $record['scheduled_time_out'] ?? null,
                    $record['device_id'] ?? null,
                    $record['external_id'] ?? null,
                    $record['tardiness_minutes'] ?? 0,
                    $record['is_late'] ?? 0,
                    $record['early_departure_minutes'] ?? 0,
                    $record['hours_worked'] ?? 0,
                    $record['status'] ?? 'PRESENT',
                    $record['notes'] ?? null
                ]);

                if ($result) {
                    $inserted++;
                }
            }

            $this->db->commit();
            return $inserted;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in bulkInsert: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Justifica una ausencia
     * Sincroniza automáticamente con attendance_absence_log
     */
    public function justifyAbsence($id, $data)
    {
        try {
            // Iniciar transacción para sincronización atómica
            $this->db->beginTransaction();

            // 1. Obtener información del registro de detalle (employee_id y fecha)
            $detail = $this->getById($id);

            if (!$detail) {
                $this->db->rollBack();
                return false;
            }

            // 2. Actualizar attendance_detail
            $sql = "UPDATE {$this->table} SET
                        status = 'JUSTIFIED',
                        justification_type = ?,
                        justification_notes = ?,
                        justification_document = ?
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['justification_type'],
                $data['justification_notes'] ?? null,
                $data['justification_document'] ?? null,
                $id
            ]);

            if (!$result) {
                $this->db->rollBack();
                return false;
            }

            // 3. Sincronizar con attendance_absence_log si existe un registro de ausencia
            $sqlAbsence = "UPDATE attendance_absence_log SET
                            justified = 1,
                            absence_type = 'JUSTIFIED',
                            justification_type = ?,
                            justification_notes = ?,
                            justification_document = ?,
                            resolved = 1,
                            resolved_at = NOW(),
                            updated_at = NOW()
                        WHERE employee_id = ?
                            AND absence_date = ?
                            AND justified = 0";

            $stmtAbsence = $this->db->prepare($sqlAbsence);
            $stmtAbsence->execute([
                $data['justification_type'],
                $data['justification_notes'] ?? null,
                $data['justification_document'] ?? null,
                $detail['employee_id'],
                $detail['date']
            ]);

            // Log de sincronización
            $affectedRows = $stmtAbsence->rowCount();
            error_log("Justificación sincronizada: Detail ID=$id, Employee={$detail['employee_id']}, Date={$detail['date']}, Absence log updated=$affectedRows");

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error justifying absence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadísticas por empleado
     */
    public function getEmployeeStats($employeeId, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as late_days,
                    SUM(CASE WHEN status = 'JUSTIFIED' THEN 1 ELSE 0 END) as justified_days,
                    SUM(hours_worked) as total_hours_worked,
                    AVG(hours_worked) as avg_hours_worked,
                    SUM(tardiness_minutes) as total_tardiness_minutes
                FROM {$this->table} d
                INNER JOIN attendance_header h ON d.header_id = h.id
                WHERE d.employee_id = ?";

        $params = [$employeeId];

        if ($dateFrom) {
            $sql .= " AND h.attendance_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND h.attendance_date <= ?";
            $params[] = $dateTo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si existe un registro para employee y header
     */
    public function exists($headerId, $employeeId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE header_id = ? AND employee_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$headerId, $employeeId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene un registro de detalle por ID
     * Incluye información de la cabecera (fecha) y empleado
     */
    public function getById($id)
    {
        $sql = "SELECT d.*, h.attendance_date as date,
                       e.firstname, e.lastname,
                       e.employee_id as employee_code,
                       e.employee_id as employee_number,
                       COALESCE(d.scheduled_time_in, s.time_in) as scheduled_time_in,
                       COALESCE(d.scheduled_time_out, s.time_out) as scheduled_time_out
                FROM {$this->table} d
                INNER JOIN attendance_header h ON d.header_id = h.id
                INNER JOIN employees e ON d.employee_id = e.id
                LEFT JOIN schedules s ON d.schedule_id = s.id
                WHERE d.id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
