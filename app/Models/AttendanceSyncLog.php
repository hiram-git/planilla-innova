<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AttendanceSyncLog
{
    private $db;
    private $table = 'attendance_sync_log';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los logs con filtros
     */
    public function getAll($filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Filtro por tipo de sincronización
        if (!empty($filters['sync_type'])) {
            $sql .= " AND sync_type = ?";
            $params[] = $filters['sync_type'];
        }

        // Filtro por estado
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        // Filtro por proveedor API
        if (!empty($filters['api_provider'])) {
            $sql .= " AND api_provider = ?";
            $params[] = $filters['api_provider'];
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(start_time) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(start_time) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY start_time DESC";

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
     * Obtiene un log por ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle completo de una sincronización
     * Incluye información de headers y details creados/actualizados
     */
    public function getDetailBySyncId($syncId)
    {
        // Obtener información del log principal
        $log = $this->getById($syncId);

        if (!$log) {
            return null;
        }

        // Obtener headers creados en este batch
        $headerSql = "SELECT h.*, d.device_name
                      FROM attendance_header h
                      LEFT JOIN attendance_devices d ON h.device_id = d.id
                      WHERE h.sync_batch_id = ?
                      ORDER BY h.attendance_date DESC";

        $headerStmt = $this->db->prepare($headerSql);
        $headerStmt->execute([$syncId]);
        $headers = $headerStmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener detalles agrupados por fecha
        $detailSql = "SELECT
                        h.attendance_date,
                        COUNT(d.id) as total_details,
                        COUNT(DISTINCT d.employee_id) as unique_employees,
                        SUM(CASE WHEN d.is_late = 1 THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN d.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
                      FROM attendance_detail d
                      INNER JOIN attendance_header h ON d.header_id = h.id
                      WHERE h.sync_batch_id = ?
                      GROUP BY h.attendance_date
                      ORDER BY h.attendance_date DESC";

        $detailStmt = $this->db->prepare($detailSql);
        $detailStmt->execute([$syncId]);
        $detailStats = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener información de errores si existen
        $errors = [];
        if ($log['errors_count'] > 0 && $log['error_details']) {
            $errors = json_decode($log['error_details'], true) ?? [];
        }

        return [
            'log' => $log,
            'headers' => $headers,
            'detail_stats' => $detailStats,
            'errors' => $errors,
            'summary' => [
                'total_headers' => count($headers),
                'total_details' => array_sum(array_column($detailStats, 'total_details')),
                'unique_employees' => array_sum(array_column($detailStats, 'unique_employees')),
                'total_late' => array_sum(array_column($detailStats, 'late_count')),
                'total_absent' => array_sum(array_column($detailStats, 'absent_count'))
            ]
        ];
    }

    /**
     * Crea un nuevo log de sincronización
     */
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    sync_type, start_time,
                    status, triggered_by, api_provider, filters_json
                ) VALUES (?, NOW(), ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $data['sync_type'],
            $data['status'] ?? 'RUNNING',
            $data['triggered_by'] ?? 'MANUAL',
            $data['api_provider'] ?? 'api',
            $data['filters_json'] ?? null
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }

    /**
     * Actualiza el estado de un log
     */
    public function updateStatus($id, $status, $data = [])
    {
        $sql = "UPDATE {$this->table} SET
                    status = ?,
                    end_time = NOW(),
                    duration_seconds = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                    records_fetched = ?,
                    records_inserted = ?,
                    records_updated = ?,
                    records_skipped = ?,
                    errors_count = ?,
                    error_details = ?,
                    status_message = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $status,
            $data['records_fetched'] ?? 0,
            $data['records_inserted'] ?? 0,
            $data['records_updated'] ?? 0,
            $data['records_skipped'] ?? 0,
            $data['errors_count'] ?? 0,
            $data['error_details'] ?? null,
            $data['status_message'] ?? null,
            $id
        ]);
    }

    /**
     * Obtiene estadísticas generales de sincronización
     */
    public function getStats($filters = [])
    {
        $sql = "SELECT
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful_syncs,
                    SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed_syncs,
                    SUM(CASE WHEN status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_syncs,
                    SUM(records_fetched) as total_records_fetched,
                    SUM(records_inserted) as total_records_inserted,
                    SUM(records_updated) as total_records_updated,
                    AVG(duration_seconds) as avg_duration,
                    MAX(start_time) as last_sync_time
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(start_time) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(start_time) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['api_provider'])) {
            $sql .= " AND api_provider = ?";
            $params[] = $filters['api_provider'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los últimos N logs
     */
    public function getRecent($limit = 10)
    {
        $sql = "SELECT * FROM {$this->table}
                ORDER BY start_time DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Limpia logs antiguos
     */
    public function cleanOldLogs($days = 90)
    {
        $sql = "DELETE FROM {$this->table}
                WHERE start_time < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND status IN ('SUCCESS', 'FAILED')";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$days]);
    }

    /**
     * Cuenta total de registros (para paginación)
     */
    public function getTotalCount($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['sync_type'])) {
            $sql .= " AND sync_type = ?";
            $params[] = $filters['sync_type'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['api_provider'])) {
            $sql .= " AND api_provider = ?";
            $params[] = $filters['api_provider'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
