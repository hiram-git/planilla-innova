<?php

namespace App\Services\Attendance;

use App\Models\AttendanceApiConfig;
use App\Core\Database;
use Exception;

/**
 * Servicio para sincronización periódica de marcaciones desde API externa
 */
class AttendanceSyncService
{
    private $apiClient;
    private $db;
    private $config;
    private $syncLogId;
    private $stats = [
        'fetched' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0
    ];
    private $errors = [];

    public function __construct($configId = null)
    {
        $this->db = Database::getInstance();

        // Obtener configuración
        $configModel = new AttendanceApiConfig();

        if ($configId) {
            $this->config = $configModel->find($configId);
        } else {
            $this->config = $configModel->getActiveConfig();
        }

        if (!$this->config) {
            throw new Exception('No se encontró configuración activa de API');
        }

        // Inicializar cliente API
        $this->apiClient = new Base44ApiClient(
            $this->config['api_key'],
            $this->config['app_id'],
            $this->config['api_url']
        );

        // Configurar cliente desde config_json
        if (!empty($this->config['config_json'])) {
            $configJson = json_decode($this->config['config_json'], true);
            if (isset($configJson['timeout'])) {
                $this->apiClient->setTimeout($configJson['timeout']);
            }
            if (isset($configJson['max_retries'])) {
                $this->apiClient->setMaxRetries($configJson['max_retries']);
            }
            if (isset($configJson['log_enabled'])) {
                $this->apiClient->setLogEnabled($configJson['log_enabled']);
            }
        }
    }

    /**
     * Sincronizar todas las marcaciones
     * @param array $filters Filtros opcionales para la API
     * @return array Estadísticas de sincronización
     */
    public function syncAll($filters = [])
    {
        $this->startSyncLog('FULL', $filters);

        try {
            // Obtener marcaciones desde API
            $attendances = $this->apiClient->getAttendances($filters);
            $this->stats['fetched'] = is_array($attendances) ? count($attendances) : 0;

            if (empty($attendances)) {
                $this->endSyncLog('SUCCESS', 'No hay marcaciones para sincronizar');
                return $this->stats;
            }

            // Procesar cada marcación
            foreach ($attendances as $attendance) {
                $this->processAttendanceRecord($attendance);
            }

            // Finalizar log
            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = "Sincronización completada: {$this->stats['inserted']} insertados, {$this->stats['updated']} actualizados, {$this->stats['skipped']} omitidos, {$this->stats['errors']} errores";
            $this->endSyncLog($status, $message);

            // Actualizar configuración
            $configModel = new AttendanceApiConfig();
            $configModel->updateLastSync($this->config['id'], $status);

            return $this->stats;

        } catch (Exception $e) {
            $this->endSyncLog('FAILED', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincronizar por rango de fechas
     * @param string $startDate Fecha inicio (YYYY-MM-DD)
     * @param string $endDate Fecha fin (YYYY-MM-DD)
     * @return array Estadísticas de sincronización
     */
    public function syncByDateRange($startDate, $endDate)
    {
        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $this->startSyncLog('INCREMENTAL', $filters);

        try {
            // Nota: Base44 puede no soportar filtros de fecha directamente
            // En ese caso, filtraremos después de obtener los datos
            $attendances = $this->apiClient->getAttendances();
            $this->stats['fetched'] = is_array($attendances) ? count($attendances) : 0;

            if (empty($attendances)) {
                $this->endSyncLog('SUCCESS', 'No hay marcaciones en el rango especificado');
                return $this->stats;
            }

            // Filtrar por rango de fechas
            $filteredAttendances = array_filter($attendances, function($record) use ($startDate, $endDate) {
                if (!isset($record['timestamp'])) {
                    return false;
                }

                $recordDate = date('Y-m-d', strtotime($record['timestamp']));
                return $recordDate >= $startDate && $recordDate <= $endDate;
            });

            // Procesar marcaciones filtradas
            foreach ($filteredAttendances as $attendance) {
                $this->processAttendanceRecord($attendance);
            }

            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = "Sincronización por rango completada: {$this->stats['inserted']} insertados, {$this->stats['updated']} actualizados";
            $this->endSyncLog($status, $message);

            return $this->stats;

        } catch (Exception $e) {
            $this->endSyncLog('FAILED', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincronizar marcaciones de un empleado específico
     * @param string $employeeEmail Email del empleado
     * @return array Estadísticas de sincronización
     */
    public function syncEmployee($employeeEmail)
    {
        $filters = ['employee_email' => $employeeEmail];
        $this->startSyncLog('MANUAL', $filters);

        try {
            $attendances = $this->apiClient->getAttendances($filters);
            $this->stats['fetched'] = is_array($attendances) ? count($attendances) : 0;

            if (empty($attendances)) {
                $this->endSyncLog('SUCCESS', "No hay marcaciones para el empleado {$employeeEmail}");
                return $this->stats;
            }

            foreach ($attendances as $attendance) {
                $this->processAttendanceRecord($attendance);
            }

            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $this->endSyncLog($status, "Sincronización de empleado completada");

            return $this->stats;

        } catch (Exception $e) {
            $this->endSyncLog('FAILED', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincronizar solo registros nuevos desde última sincronización
     * @return array Estadísticas de sincronización
     */
    public function syncSince()
    {
        $lastSync = $this->config['last_sync_at'];

        if (empty($lastSync)) {
            // Si nunca se ha sincronizado, hacer sync completo
            return $this->syncAll();
        }

        $this->startSyncLog('INCREMENTAL', ['since' => $lastSync]);

        try {
            // Obtener todas las marcaciones
            $attendances = $this->apiClient->getAttendances();
            $this->stats['fetched'] = is_array($attendances) ? count($attendances) : 0;

            if (empty($attendances)) {
                $this->endSyncLog('SUCCESS', 'No hay marcaciones nuevas');
                return $this->stats;
            }

            // Filtrar solo registros posteriores a última sincronización
            $lastSyncTimestamp = strtotime($lastSync);
            $newAttendances = array_filter($attendances, function($record) use ($lastSyncTimestamp) {
                if (!isset($record['timestamp'])) {
                    return false;
                }
                return strtotime($record['timestamp']) > $lastSyncTimestamp;
            });

            foreach ($newAttendances as $attendance) {
                $this->processAttendanceRecord($attendance);
            }

            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = "Sincronización incremental completada: {$this->stats['inserted']} nuevos registros";
            $this->endSyncLog($status, $message);

            $configModel = new AttendanceApiConfig();
            $configModel->updateLastSync($this->config['id'], $status);

            return $this->stats;

        } catch (Exception $e) {
            $this->endSyncLog('FAILED', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar un registro de marcación individual
     * @param array $rawData Datos crudos desde API
     */
    private function processAttendanceRecord($rawData)
    {
        try {
            // Validar que tenga los campos mínimos requeridos
            if (!isset($rawData['employee_email']) || !isset($rawData['timestamp'])) {
                $this->stats['skipped']++;
                $this->errors[] = "Registro sin email o timestamp: " . json_encode($rawData);
                return;
            }

            // Guardar datos crudos en attendance_raw_data
            $rawDataId = $this->saveRawData($rawData);

            // Verificar si ya existe
            $existing = $this->findExistingRecord($rawData);

            if ($existing) {
                // Verificar si necesita actualización
                if ($this->needsUpdate($existing, $rawData)) {
                    $this->updateRecord($existing['id'], $rawData);
                    $this->stats['updated']++;

                    // Marcar raw_data como procesado
                    $this->markRawDataProcessed($rawDataId);
                } else {
                    $this->stats['skipped']++;
                }
            } else {
                // Insertar nuevo registro
                $this->insertRecord($rawData, $rawDataId);
                $this->stats['inserted']++;

                // Marcar raw_data como procesado
                $this->markRawDataProcessed($rawDataId);
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errors[] = "Error procesando registro: " . $e->getMessage();
            $this->log("Error: " . $e->getMessage());
        }
    }

    /**
     * Guardar datos crudos en attendance_raw_data
     * @param array $data Datos crudos
     * @return int ID del registro insertado
     */
    private function saveRawData($data)
    {
        $externalId = $data['id'] ?? null;

        $sql = "INSERT INTO attendance_raw_data
                (external_id, api_provider, entity_type, raw_json, sync_batch_id, received_at)
                VALUES (?, ?, 'Attendance', ?, ?, NOW())";

        $this->db->query($sql, [
            $externalId,
            $this->config['api_provider'],
            json_encode($data),
            $this->syncLogId
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Buscar registro existente
     * @param array $data Datos desde API
     * @return array|null Registro existente o null
     */
    private function findExistingRecord($data)
    {
        // Buscar por employee_email + date
        $date = date('Y-m-d', strtotime($data['timestamp']));

        $sql = "SELECT ar.*, e.id as employee_id
                FROM attendance_raw_data ar
                LEFT JOIN employees e ON JSON_EXTRACT(ar.raw_json, '$.employee_email') = e.email
                WHERE ar.api_provider = ?
                  AND ar.entity_type = 'Attendance'
                  AND DATE(JSON_EXTRACT(ar.raw_json, '$.timestamp')) = ?
                  AND JSON_EXTRACT(ar.raw_json, '$.employee_email') = ?
                  AND ar.processed = 1
                LIMIT 1";

        return $this->db->find($sql, [$this->config['api_provider'], $date, $data['employee_email']]);
    }

    /**
     * Verificar si un registro necesita actualización
     * @param array $existing Registro existente
     * @param array $newData Datos nuevos
     * @return bool
     */
    private function needsUpdate($existing, $newData)
    {
        // Comparar timestamps de modificación si existen
        if (isset($newData['updated_at'])) {
            $existingJson = json_decode($existing['raw_json'], true);
            $existingUpdated = $existingJson['updated_at'] ?? null;

            if ($existingUpdated && $newData['updated_at'] > $existingUpdated) {
                return true;
            }
        }

        // Por defecto, no actualizar si ya está procesado
        return false;
    }

    /**
     * Insertar nuevo registro en attendance (tabla original)
     * @param array $data Datos desde API
     * @param int $rawDataId ID en attendance_raw_data
     */
    private function insertRecord($data, $rawDataId)
    {
        // Buscar employee_id por email
        $employee = $this->db->find("SELECT id FROM employees WHERE email = ?", [$data['employee_email']]);

        if (!$employee) {
            throw new Exception("Empleado no encontrado: {$data['employee_email']}");
        }

        $date = date('Y-m-d', strtotime($data['timestamp']));
        $timeIn = date('H:i:s', strtotime($data['timestamp']));

        // Determinar si es entrada o salida según el tipo
        $type = strtoupper($data['type'] ?? 'CHECK_IN');
        $status = ($data['is_late'] ?? false) ? 0 : 1;

        $sql = "INSERT INTO attendance
                (employee_id, date, time_in, time_out, num_hr, status)
                VALUES (?, ?, ?, NULL, 0, ?)";

        $this->db->query($sql, [$employee['id'], $date, $timeIn, $status]);
    }

    /**
     * Actualizar registro existente
     * @param int $recordId ID del registro
     * @param array $data Datos nuevos
     */
    private function updateRecord($recordId, $data)
    {
        $sql = "UPDATE attendance_raw_data
                SET raw_json = ?,
                    processed_at = NOW()
                WHERE id = ?";

        $this->db->query($sql, [json_encode($data), $recordId]);
    }

    /**
     * Marcar raw_data como procesado
     * @param int $rawDataId ID del registro
     */
    private function markRawDataProcessed($rawDataId)
    {
        $sql = "UPDATE attendance_raw_data
                SET processed = 1,
                    processed_at = NOW()
                WHERE id = ?";

        $this->db->query($sql, [$rawDataId]);
    }

    /**
     * Iniciar log de sincronización
     * @param string $syncType FULL, INCREMENTAL, MANUAL, WEBHOOK
     * @param array $filters Filtros aplicados
     */
    private function startSyncLog($syncType, $filters = [])
    {
        $triggeredBy = php_sapi_name() === 'cli' ? 'CRON' : 'USER_' . ($_SESSION['user_id'] ?? 'UNKNOWN');

        $sql = "INSERT INTO attendance_sync_log
                (sync_type, start_time, status, triggered_by, api_provider, filters_json)
                VALUES (?, NOW(), 'RUNNING', ?, ?, ?)";

        $this->db->query($sql, [
            $syncType,
            $triggeredBy,
            $this->config['api_provider'],
            json_encode($filters)
        ]);

        $this->syncLogId = $this->db->lastInsertId();
    }

    /**
     * Finalizar log de sincronización
     * @param string $status SUCCESS, FAILED, PARTIAL
     * @param string $message Mensaje descriptivo
     */
    private function endSyncLog($status, $message)
    {
        $sql = "UPDATE attendance_sync_log
                SET end_time = NOW(),
                    duration_seconds = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                    records_fetched = ?,
                    records_inserted = ?,
                    records_updated = ?,
                    records_skipped = ?,
                    errors_count = ?,
                    error_details = ?,
                    status = ?,
                    status_message = ?
                WHERE id = ?";

        $this->db->query($sql, [
            $this->stats['fetched'],
            $this->stats['inserted'],
            $this->stats['updated'],
            $this->stats['skipped'],
            $this->stats['errors'],
            json_encode($this->errors),
            $status,
            $message,
            $this->syncLogId
        ]);
    }

    /**
     * Logging simple
     * @param string $message Mensaje a escribir
     */
    private function log($message)
    {
        $logDir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/attendance_sync_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND);
    }

    /**
     * Obtener estadísticas de sincronización
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Obtener errores de sincronización
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
