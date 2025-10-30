<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

/**
 * Model para attendance_records
 * Capa intermedia entre raw_data y attendance_detail
 * Almacena marcaciones individuales normalizadas
 */
class AttendanceRecord
{
    private $db;
    private $table = 'attendance_records';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crear un nuevo registro de marcación
     * @param array $data Datos del registro
     * @return int|false ID del registro creado o false
     */
    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                        raw_data_id, external_id,
                        employee_id, timestamp, punch_date, punch_time, punch_type,
                        device_id, device_serial,
                        source, metadata, notes, record_hash
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                $data['raw_data_id'] ?? null,
                $data['external_id'] ?? null,
                $data['employee_id'],
                $data['timestamp'],
                $data['punch_date'] ?? date('Y-m-d', strtotime($data['timestamp'])),
                $data['punch_time'] ?? date('H:i:s', strtotime($data['timestamp'])),
                $data['punch_type'],
                $data['device_id'] ?? null,
                $data['device_serial'] ?? null,
                $data['source'] ?? 'API',
                isset($data['metadata']) ? json_encode($data['metadata']) : null,
                $data['notes'] ?? null,
                $data['record_hash'] ?? $this->calculateHash($data)
            ]);

            return $result ? $this->db->lastInsertId() : false;

        } catch (Exception $e) {
            error_log("Error creating attendance record: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inserción masiva de registros
     * @param array $records Array de registros
     * @return array Estadísticas de inserción
     */
    public function bulkInsert($records)
    {
        if (empty($records)) {
            return ['inserted' => 0, 'duplicates' => 0, 'errors' => 0];
        }

        $stats = [
            'inserted' => 0,
            'duplicates' => 0,
            'errors' => 0
        ];

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO {$this->table} (
                        raw_data_id, external_id,
                        employee_id, timestamp, punch_date, punch_time, punch_type,
                        device_id, device_serial,
                        source, metadata, notes, record_hash
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        is_duplicate = IF(VALUES(record_hash) = record_hash, 1, is_duplicate),
                        duplicate_of = IF(VALUES(record_hash) = record_hash, id, duplicate_of)";

            $stmt = $this->db->prepare($sql);

            foreach ($records as $data) {
                try {
                    // Calcular hash
                    $hash = $data['record_hash'] ?? $this->calculateHash($data);

                    // Verificar si ya existe
                    if ($this->existsByHash($hash)) {
                        $stats['duplicates']++;
                        continue;
                    }

                    $result = $stmt->execute([
                        $data['raw_data_id'] ?? null,
                        $data['external_id'] ?? null,
                        $data['employee_id'],
                        $data['timestamp'],
                        $data['punch_date'] ?? date('Y-m-d', strtotime($data['timestamp'])),
                        $data['punch_time'] ?? date('H:i:s', strtotime($data['timestamp'])),
                        $data['punch_type'],
                        $data['device_id'] ?? null,
                        $data['device_serial'] ?? null,
                        $data['source'] ?? 'API',
                        isset($data['metadata']) ? json_encode($data['metadata']) : null,
                        $data['notes'] ?? null,
                        $hash
                    ]);

                    if ($result) {
                        $stats['inserted']++;
                    }

                } catch (Exception $e) {
                    $stats['errors']++;
                    error_log("Error inserting record: " . $e->getMessage());
                }
            }

            $this->db->commit();
            return $stats;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error in bulkInsert: " . $e->getMessage());
            return $stats;
        }
    }

    /**
     * Obtener registros sin procesar
     * @param string|null $dateFrom Fecha desde
     * @param string|null $dateTo Fecha hasta
     * @return array
     */
    public function getUnprocessed($dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT r.*,
                       CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                       e.employee_id as employee_code
                FROM {$this->table} r
                INNER JOIN employees e ON r.employee_id = e.id
                WHERE r.is_processed = 0
                  AND r.is_duplicate = 0";

        $params = [];

        if ($dateFrom) {
            $sql .= " AND r.punch_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND r.punch_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY r.punch_date ASC, r.timestamp ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener registros agrupados por empleado y fecha
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getGroupedByEmployeeAndDate($dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT
                    r.employee_id,
                    r.punch_date,
                    CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                    e.employee_id as employee_code,
                    MIN(CASE WHEN r.punch_type = 'CHECK_IN' THEN r.timestamp END) as first_check_in,
                    MAX(CASE WHEN r.punch_type = 'CHECK_OUT' THEN r.timestamp END) as last_check_out,
                    COUNT(*) as total_punches,
                    SUM(CASE WHEN r.punch_type = 'CHECK_IN' THEN 1 ELSE 0 END) as check_ins,
                    SUM(CASE WHEN r.punch_type = 'CHECK_OUT' THEN 1 ELSE 0 END) as check_outs,
                    GROUP_CONCAT(r.id ORDER BY r.timestamp) as record_ids
                FROM {$this->table} r
                INNER JOIN employees e ON r.employee_id = e.id
                WHERE r.is_processed = 0
                  AND r.is_duplicate = 0";

        $params = [];

        if ($dateFrom) {
            $sql .= " AND r.punch_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND r.punch_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY r.employee_id, r.punch_date
                  ORDER BY r.punch_date DESC, e.lastname, e.firstname";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener registros de un empleado en una fecha específica
     * @param int $employeeId
     * @param string $date
     * @return array
     */
    public function getByEmployeeAndDate($employeeId, $date)
    {
        $sql = "SELECT *
                FROM {$this->table}
                WHERE employee_id = ?
                  AND punch_date = ?
                  AND is_duplicate = 0
                ORDER BY timestamp ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marcar registros como procesados
     * @param int $employeeId
     * @param string $date
     * @param int $detailId ID del registro en attendance_detail
     * @return bool
     */
    public function markAsProcessed($employeeId, $date, $detailId)
    {
        $sql = "UPDATE {$this->table}
                SET is_processed = 1,
                    processed_at = NOW(),
                    detail_id = ?
                WHERE employee_id = ?
                  AND punch_date = ?
                  AND is_processed = 0
                  AND is_duplicate = 0";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$detailId, $employeeId, $date]);
    }

    /**
     * Marcar múltiples registros como procesados por IDs
     * @param array $recordIds Array de IDs
     * @param int $detailId
     * @return bool
     */
    public function markMultipleAsProcessed($recordIds, $detailId)
    {
        if (empty($recordIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));

        $sql = "UPDATE {$this->table}
                SET is_processed = 1,
                    processed_at = NOW(),
                    detail_id = ?
                WHERE id IN ($placeholders)
                  AND is_processed = 0";

        $params = array_merge([$detailId], $recordIds);

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Calcular hash de un registro para detección de duplicados
     * @param array $data
     * @return string
     */
    public function calculateHash($data)
    {
        $hashString = sprintf(
            "%d|%s|%s",
            $data['employee_id'],
            $data['timestamp'],
            $data['punch_type']
        );

        return md5($hashString);
    }

    /**
     * Verificar si existe un registro con un hash específico
     * @param string $hash
     * @return bool
     */
    public function existsByHash($hash)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE record_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hash]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Detectar duplicados y marcarlos
     * @return int Cantidad de duplicados detectados
     */
    public function detectDuplicates()
    {
        try {
            // Ejecutar stored procedure
            $stmt = $this->db->prepare("CALL sp_detect_duplicates()");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['duplicates_found'] ?? 0;

        } catch (Exception $e) {
            error_log("Error detecting duplicates: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas de registros por fecha
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getStatsByDate($dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT
                    punch_date,
                    COUNT(*) as total_records,
                    COUNT(DISTINCT employee_id) as total_employees,
                    SUM(CASE WHEN punch_type = 'CHECK_IN' THEN 1 ELSE 0 END) as check_ins,
                    SUM(CASE WHEN punch_type = 'CHECK_OUT' THEN 1 ELSE 0 END) as check_outs,
                    SUM(CASE WHEN is_processed = 1 THEN 1 ELSE 0 END) as processed,
                    SUM(CASE WHEN is_processed = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN is_duplicate = 1 THEN 1 ELSE 0 END) as duplicates
                FROM {$this->table}
                WHERE 1=1";

        $params = [];

        if ($dateFrom) {
            $sql .= " AND punch_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND punch_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " GROUP BY punch_date
                  ORDER BY punch_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener registros duplicados
     * @return array
     */
    public function getDuplicates()
    {
        $sql = "SELECT r.*,
                       CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                       e.employee_id as employee_code,
                       orig.timestamp as original_timestamp
                FROM {$this->table} r
                INNER JOIN employees e ON r.employee_id = e.id
                LEFT JOIN {$this->table} orig ON r.duplicate_of = orig.id
                WHERE r.is_duplicate = 1
                ORDER BY r.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener un registro por ID
     * @param int $id
     * @return array|false
     */
    public function getById($id)
    {
        $sql = "SELECT r.*,
                       CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                       e.employee_id as employee_code
                FROM {$this->table} r
                INNER JOIN employees e ON r.employee_id = e.id
                WHERE r.id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar un registro
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['timestamp'])) {
            $fields[] = "timestamp = ?";
            $params[] = $data['timestamp'];
        }

        if (isset($data['punch_type'])) {
            $fields[] = "punch_type = ?";
            $params[] = $data['punch_type'];
        }

        if (isset($data['is_processed'])) {
            $fields[] = "is_processed = ?";
            $params[] = $data['is_processed'];
        }

        if (isset($data['is_duplicate'])) {
            $fields[] = "is_duplicate = ?";
            $params[] = $data['is_duplicate'];
        }

        if (isset($data['notes'])) {
            $fields[] = "notes = ?";
            $params[] = $data['notes'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar un registro
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Eliminar registros por fecha
     * @param string $date
     * @return bool
     */
    public function deleteByDate($date)
    {
        $sql = "DELETE FROM {$this->table} WHERE punch_date = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$date]);
    }

    /**
     * Contar registros
     * @param array $filters
     * @return int
     */
    public function count($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];

        if (isset($filters['is_processed'])) {
            $sql .= " AND is_processed = ?";
            $params[] = $filters['is_processed'];
        }

        if (isset($filters['is_duplicate'])) {
            $sql .= " AND is_duplicate = ?";
            $params[] = $filters['is_duplicate'];
        }

        if (isset($filters['punch_date'])) {
            $sql .= " AND punch_date = ?";
            $params[] = $filters['punch_date'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
