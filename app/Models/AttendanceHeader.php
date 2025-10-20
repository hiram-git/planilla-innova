<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AttendanceHeader
{
    private $db;
    private $table = 'attendance_header';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los registros con filtros
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT h.*, d.device_name, d.device_type
                FROM {$this->table} h
                LEFT JOIN attendance_devices d ON h.device_id = d.id
                WHERE 1=1";

        $params = [];

        // Filtro por año
        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(h.attendance_date) = ?";
            $params[] = $filters['year'];
        }

        // Filtro por mes
        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(h.attendance_date) = ?";
            $params[] = $filters['month'];
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $sql .= " AND h.attendance_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND h.attendance_date <= ?";
            $params[] = $filters['date_to'];
        }

        // Filtro por dispositivo
        if (!empty($filters['device_id'])) {
            $sql .= " AND h.device_id = ?";
            $params[] = $filters['device_id'];
        }

        // Filtro por estado procesado
        if (isset($filters['is_processed'])) {
            $sql .= " AND h.is_processed = ?";
            $params[] = $filters['is_processed'];
        }

        $sql .= " ORDER BY h.attendance_date DESC";

        // Paginación
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ?";
            $params[] = (int)$filters['limit'];

            if (!empty($filters['offset'])) {
                $sql .= " OFFSET ?";
                $params[] = (int)$filters['offset'];
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un registro por fecha
     */
    public function getByDate($date, $deviceId = null)
    {
        $sql = "SELECT h.*, d.device_name, d.device_type
                FROM {$this->table} h
                LEFT JOIN attendance_devices d ON h.device_id = d.id
                WHERE h.attendance_date = ?";

        $params = [$date];

        if ($deviceId) {
            $sql .= " AND h.device_id = ?";
            $params[] = $deviceId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene registros por rango de fechas
     */
    public function getByDateRange($startDate, $endDate, $deviceId = null)
    {
        $sql = "SELECT h.*, d.device_name, d.device_type
                FROM {$this->table} h
                LEFT JOIN attendance_devices d ON h.device_id = d.id
                WHERE h.attendance_date BETWEEN ? AND ?";

        $params = [$startDate, $endDate];

        if ($deviceId) {
            $sql .= " AND h.device_id = ?";
            $params[] = $deviceId;
        }

        $sql .= " ORDER BY h.attendance_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas generales
     */
    public function getStatistics($filters = [])
    {
        $sql = "SELECT
                    COUNT(*) as total_days,
                    COUNT(DISTINCT device_id) as total_devices,
                    SUM(total_employees) as total_employee_records,
                    SUM(total_on_time) as total_on_time,
                    SUM(total_late) as total_late,
                    SUM(total_absent) as total_absent,
                    ROUND(AVG(total_on_time / NULLIF(total_employees, 0) * 100), 2) as avg_punctuality
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(attendance_date) = ?";
            $params[] = $filters['year'];
        }

        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(attendance_date) = ?";
            $params[] = $filters['month'];
        }

        if (!empty($filters['device_id'])) {
            $sql .= " AND device_id = ?";
            $params[] = $filters['device_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo registro de cabecera
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    attendance_date, device_id,
                    total_records, total_employees, total_on_time, total_late, total_absent,
                    is_processed, processed_at, processed_by,
                    sync_batch_id, synced_from, file_import_id, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $data['attendance_date'],
            $data['device_id'] ?? null,
            $data['total_records'] ?? 0,
            $data['total_employees'] ?? 0,
            $data['total_on_time'] ?? 0,
            $data['total_late'] ?? 0,
            $data['total_absent'] ?? 0,
            $data['is_processed'] ?? 0,
            $data['processed_at'] ?? null,
            $data['processed_by'] ?? null,
            $data['sync_batch_id'] ?? null,
            $data['synced_from'] ?? 'MANUAL',
            $data['file_import_id'] ?? null,
            $data['notes'] ?? null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Actualiza un registro de cabecera
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET
                    attendance_date = ?,
                    device_id = ?,
                    total_records = ?,
                    total_employees = ?,
                    total_on_time = ?,
                    total_late = ?,
                    total_absent = ?,
                    is_processed = ?,
                    processed_at = ?,
                    processed_by = ?,
                    notes = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['attendance_date'],
            $data['device_id'] ?? null,
            $data['total_records'] ?? 0,
            $data['total_employees'] ?? 0,
            $data['total_on_time'] ?? 0,
            $data['total_late'] ?? 0,
            $data['total_absent'] ?? 0,
            $data['is_processed'] ?? 0,
            $data['processed_at'] ?? null,
            $data['processed_by'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }

    /**
     * Elimina un registro de cabecera (y sus detalles por CASCADE)
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Recalcula las estadísticas de un día específico
     */
    public function reprocess($id)
    {
        // Obtener estadísticas recalculadas desde attendance_detail
        $sql = "SELECT
                    COUNT(*) as total_records,
                    COUNT(DISTINCT employee_id) as total_employees,
                    SUM(CASE WHEN is_late = 0 AND status = 'PRESENT' THEN 1 ELSE 0 END) as total_on_time,
                    SUM(CASE WHEN is_late = 1 THEN 1 ELSE 0 END) as total_late,
                    SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as total_absent
                FROM attendance_detail
                WHERE header_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($stats) {
            // Actualizar la cabecera con las estadísticas recalculadas
            $updateSql = "UPDATE {$this->table} SET
                            total_records = ?,
                            total_employees = ?,
                            total_on_time = ?,
                            total_late = ?,
                            total_absent = ?,
                            is_processed = 1,
                            processed_at = NOW()
                          WHERE id = ?";

            $updateStmt = $this->db->prepare($updateSql);
            return $updateStmt->execute([
                $stats['total_records'],
                $stats['total_employees'],
                $stats['total_on_time'],
                $stats['total_late'],
                $stats['total_absent'],
                $id
            ]);
        }

        return false;
    }

    /**
     * Obtiene años disponibles
     */
    public function getAvailableYears()
    {
        $sql = "SELECT DISTINCT YEAR(attendance_date) as year
                FROM {$this->table}
                ORDER BY year DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Obtiene meses disponibles para un año
     */
    public function getAvailableMonths($year)
    {
        $sql = "SELECT DISTINCT MONTH(attendance_date) as month
                FROM {$this->table}
                WHERE YEAR(attendance_date) = ?
                ORDER BY month DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Cuenta total de registros (para paginación)
     */
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(attendance_date) = ?";
            $params[] = $filters['year'];
        }

        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(attendance_date) = ?";
            $params[] = $filters['month'];
        }

        if (!empty($filters['device_id'])) {
            $sql .= " AND device_id = ?";
            $params[] = $filters['device_id'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
