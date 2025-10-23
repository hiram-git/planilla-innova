<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class AttendanceDevice
{
    private $db;
    private $table = 'attendance_devices';

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Obtiene todos los dispositivos
     */
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un dispositivo por ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene dispositivos activos
     */
    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY device_name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene dispositivos por tipo
     */
    public function getByType($type)
    {
        $sql = "SELECT * FROM {$this->table} WHERE device_type = ? AND is_active = 1 ORDER BY device_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo dispositivo
     */
    public function create($data)
    {
        try {
            $sql = "INSERT INTO {$this->table} (
                        device_code, device_name, device_type, location, is_active,
                        config_json, api_url, api_key,
                        file_format, delimiter, date_format, time_format, column_mapping,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            error_log("AttendanceDevice@create - SQL: " . $sql);

            $stmt = $this->db->prepare($sql);

            $params = [
                $data['device_code'],
                $data['device_name'],
                $data['device_type'],
                $data['location'] ?? null,
                $data['is_active'] ?? 1,
                $data['config_json'] ?? null,
                $data['api_url'] ?? null,
                $data['api_key'] ?? null,
                $data['file_format'] ?? null,
                $data['delimiter'] ?? ',',
                $data['date_format'] ?? 'Y-m-d',
                $data['time_format'] ?? 'H:i:s',
                $data['column_mapping'] ?? null,
                $data['created_by'] ?? null
            ];

            error_log("AttendanceDevice@create - Params: " . print_r($params, true));

            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("AttendanceDevice@create - SQL Error: " . print_r($errorInfo, true));
                return false;
            }

            $lastId = $this->db->lastInsertId();
            error_log("AttendanceDevice@create - Last insert ID: " . $lastId);

            return $lastId;

        } catch (\PDOException $e) {
            error_log("AttendanceDevice@create - PDO Exception: " . $e->getMessage());
            error_log("AttendanceDevice@create - Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Actualiza un dispositivo
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET
                    device_code = ?,
                    device_name = ?,
                    device_type = ?,
                    location = ?,
                    is_active = ?,
                    config_json = ?,
                    api_url = ?,
                    api_key = ?,
                    file_format = ?,
                    delimiter = ?,
                    date_format = ?,
                    time_format = ?,
                    column_mapping = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['device_code'],
            $data['device_name'],
            $data['device_type'],
            $data['location'] ?? null,
            $data['is_active'] ?? 1,
            $data['config_json'] ?? null,
            $data['api_url'] ?? null,
            $data['api_key'] ?? null,
            $data['file_format'] ?? null,
            $data['delimiter'] ?? ',',
            $data['date_format'] ?? 'Y-m-d',
            $data['time_format'] ?? 'H:i:s',
            $data['column_mapping'] ?? null,
            $id
        ]);
    }

    /**
     * Elimina un dispositivo
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Cambia el estado activo/inactivo de un dispositivo
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Verifica si un código de dispositivo ya existe
     */
    public function codeExists($code, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE device_code = ?";
        $params = [$code];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtiene estadísticas de dispositivos
     */
    public function getStats()
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN device_type = 'API' THEN 1 ELSE 0 END) as api_devices,
                    SUM(CASE WHEN device_type = 'BIOMETRIC' THEN 1 ELSE 0 END) as biometric_devices,
                    SUM(CASE WHEN device_type = 'TEXT_FILE' THEN 1 ELSE 0 END) as file_devices,
                    SUM(CASE WHEN device_type = 'MANUAL' THEN 1 ELSE 0 END) as manual_devices
                FROM {$this->table}";

        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
