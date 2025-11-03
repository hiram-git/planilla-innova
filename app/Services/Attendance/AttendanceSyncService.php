<?php

namespace App\Services\Attendance;

use App\Models\AttendanceApiConfig;
use App\Models\AttendanceRecord;
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
    private $recordModel;
    private $stats = [
        'fetched' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'records_created' => 0
    ];
    private $errors = [];

    public function __construct($configId = null)
    {
        $this->db = Database::getInstance();
        $this->recordModel = new AttendanceRecord();

        // Obtener configuración (opcional)
        $configModel = new AttendanceApiConfig();

        if ($configId) {
            $this->config = $configModel->find($configId);
        } else {
            $this->config = $configModel->getActiveConfig();
        }

        // Si no hay configuración, usar valores por defecto
        if (!$this->config) {
            $this->config = [
                'id' => null,
                'api_provider' => 'Base44',
                'api_key' => getenv('BASE44_API_KEY') ?: '',
                'app_id' => getenv('BASE44_APP_ID') ?: '',
                'api_url' => getenv('BASE44_API_URL') ?: 'https://api.base44.com',
                'sync_interval_minutes' => 15
            ];
            error_log("AttendanceSyncService: No se encontró configuración activa, usando valores por defecto");
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

            // Actualizar configuración si existe
            if (!empty($this->config['id'])) {
                $configModel = new AttendanceApiConfig();
                $configModel->updateLastSync($this->config['id'], $status);
            }

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

            // Actualizar configuración si existe
            if (!empty($this->config['id'])) {
                $configModel = new AttendanceApiConfig();
                $configModel->updateLastSync($this->config['id'], $status);
            }

            return $this->stats;

        } catch (Exception $e) {
            $this->endSyncLog('FAILED', 'Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar un registro de marcación individual
     * NUEVO FLUJO: Guarda en attendance_records en lugar de procesar directamente a detail
     * @param array $rawData Datos crudos desde API
     */
    private function processAttendanceRecord($rawData)
    {
        try {
            // Normalizar timestamp (soportar timestamp, actual_timestamp, registered_timestamp)
            if (!isset($rawData['timestamp'])) {
                if (isset($rawData['actual_timestamp'])) {
                    $rawData['timestamp'] = $rawData['actual_timestamp'];
                } elseif (isset($rawData['registered_timestamp'])) {
                    $rawData['timestamp'] = $rawData['registered_timestamp'];
                }
            }

            // Validar que tenga los campos mínimos requeridos
            if (!isset($rawData['employee_email']) || !isset($rawData['timestamp'])) {
                $this->stats['skipped']++;
                $this->errors[] = "Registro sin email o timestamp: " . json_encode($rawData);
                return;
            }

            // 1. Guardar datos crudos en attendance_raw_data
            $rawDataId = $this->saveRawData($rawData);

            // 2. NUEVO: Guardar en attendance_records
            $recordCreated = $this->saveToRecords($rawData, $rawDataId);

            if ($recordCreated) {
                $this->stats['records_created']++;
                $this->stats['inserted']++;
                $this->markRawDataProcessed($rawDataId);
            } else {
                $this->stats['skipped']++;
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errors[] = "Error procesando registro: " . $e->getMessage();
            $this->log("Error: " . $e->getMessage());
        }
    }

    /**
     * Mapear tipo de marcación del API a ENUM punch_type
     * @param string $apiType Tipo desde API (entrada, salida, etc.)
     * @return string Valor ENUM (CHECK_IN o CHECK_OUT)
     */
    private function mapPunchType($apiType)
    {
        $apiType = strtolower(trim($apiType));

        // Mapeo de tipos Base44 a ENUM punch_type
        $mapping = [
            'entrada' => 'CHECK_IN',
            'entrada_almuerzo' => 'CHECK_IN',
            'check_in' => 'CHECK_IN',
            'in' => 'CHECK_IN',
            'salida' => 'CHECK_OUT',
            'salida_almuerzo' => 'CHECK_OUT',
            'check_out' => 'CHECK_OUT',
            'out' => 'CHECK_OUT'
        ];

        return $mapping[$apiType] ?? 'CHECK_IN';
    }

    /**
     * Guardar marcación en attendance_records (capa intermedia)
     * @param array $rawData Datos del API
     * @param int $rawDataId ID en attendance_raw_data
     * @return bool True si se creó, False si ya existía
     */
    private function saveToRecords($rawData, $rawDataId)
    {
        try {
            // 1. Buscar employee_id por email Y verificar marca_asistencia = 1
            $employee = $this->db->find(
                "SELECT id, marca_asistencia FROM employees WHERE email = ? AND marca_asistencia = 1",
                [$rawData['employee_email']]
            );

            // Si no se encuentra por email, intentar por nombre completo
            if (!$employee && isset($rawData['employee_name'])) {
                $nameParts = explode(' ', trim($rawData['employee_name']));
                if (count($nameParts) >= 2) {
                    $firstName = strtoupper($nameParts[0]);
                    $lastName = strtoupper($nameParts[count($nameParts) - 1]);

                    $sql = "SELECT id, marca_asistencia FROM employees
                            WHERE UPPER(firstname) LIKE ?
                              AND UPPER(lastname) LIKE ?
                              AND marca_asistencia = 1
                            LIMIT 1";
                    $employee = $this->db->find($sql, ["%{$firstName}%", "%{$lastName}%"]);
                }
            }

            if (!$employee) {
                // Log diferente según si es por marca_asistencia o no encontrado
                $this->errors[] = "Empleado no encontrado o no marca asistencia: {$rawData['employee_email']} / " . ($rawData['employee_name'] ?? 'N/A');
                return false;
            }

            // 2. Preparar datos para attendance_records
            $timestamp = $rawData['timestamp'];
            $punchType = $this->mapPunchType($rawData['type'] ?? 'entrada');

            // Convertir timestamp ISO 8601 a formato MySQL
            $timestampObj = new \DateTime($timestamp);
            $mysqlTimestamp = $timestampObj->format('Y-m-d H:i:s');

            $recordData = [
                'raw_data_id' => $rawDataId,
                'external_id' => $rawData['id'] ?? null,
                'employee_id' => $employee['id'],
                'timestamp' => $mysqlTimestamp,
                'punch_date' => $timestampObj->format('Y-m-d'),
                'punch_time' => $timestampObj->format('H:i:s'),
                'punch_type' => $punchType,
                'device_id' => null,
                'device_serial' => $rawData['device_serial'] ?? null,
                'source' => 'API',
                'metadata' => $rawData,
                'notes' => 'Sincronizado desde ' . $this->config['api_provider']
            ];

            // 3. Calcular hash para detectar duplicados
            $recordData['record_hash'] = $this->recordModel->calculateHash($recordData);

            // 4. Verificar si ya existe
            if ($this->recordModel->existsByHash($recordData['record_hash'])) {
                return false;
            }

            // 5. Crear registro
            $recordId = $this->recordModel->create($recordData);

            if ($recordId) {
                error_log("Record creado ID {$recordId} para empleado {$employee['id']} - {$punchType} {$mysqlTimestamp}");
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Error en saveToRecords: " . $e->getMessage());
            $this->errors[] = "Error guardando en records: " . $e->getMessage();
            return false;
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

    // =====================================================
    // MÉTODOS LEGACY ELIMINADOS
    // =====================================================
    // Los siguientes métodos fueron removidos ya que ahora se usa
    // attendance_records como capa intermedia y RecordsProcessor
    // para consolidar a attendance_detail:
    //
    // - findExistingRecord()
    // - needsUpdate()
    // - insertRecord()
    // - updateRecord()
    // - updateHeaderStats()
    //
    // El nuevo flujo es:
    // API → raw_data → attendance_records → RecordsProcessor → attendance_detail
    // =====================================================

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
            $this->config['api_provider'] ?? 'Base44',
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
