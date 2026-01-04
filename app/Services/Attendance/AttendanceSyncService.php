<?php

namespace App\Services\Attendance;

use App\Models\AttendanceApiConfig;
use App\Models\AttendanceRecord;
use App\Models\AttendanceHeader;
use App\Models\AttendanceDetail;
use App\Models\BusinessCalendar;
use App\Core\Database;
use App\Services\Attendance\Calculators\AbsenceDetector;
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
    private $businessCalendar;
    private $absenceDetector;
    private $stats = [
        'fetched' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'records_created' => 0,
        // Rango de fechas detectado desde la API (YYYY-MM-DD)
        'min_date' => null,
        'max_date' => null,
        // Estadísticas de detección de ausencias
        'working_days_checked' => 0,
        'employees_checked' => 0,
        'missing_days_detected' => 0,
        'absences_created' => 0,
        'absences_errors' => 0
    ];
    private $errors = [];

    public function __construct($configId = null)
    {
        $this->db = Database::getInstance();
        $this->recordModel = new AttendanceRecord();
        $this->businessCalendar = new BusinessCalendar();
        $this->absenceDetector = new AbsenceDetector();

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
                'api_provider' => 'api',
                'api_key' => getenv('API_KEY') ?: '',
                'app_id' => getenv('API_APP_ID') ?: '',
                'api_url' => getenv('API_URL') ?: 'https://api.api.com',
                'sync_interval_minutes' => 15
            ];
            error_log("AttendanceSyncService: No se encontró configuración activa, usando valores por defecto");
        }

        // Inicializar cliente API
        $this->apiClient = new ApiClient(
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
                // Detectar rango de fechas desde campos de la API
                $date = $this->extractRecordDate($attendance);
                if ($date) {
                    if (empty($this->stats['min_date']) || $date < $this->stats['min_date']) {
                        $this->stats['min_date'] = $date;
                    }
                    if (empty($this->stats['max_date']) || $date > $this->stats['max_date']) {
                        $this->stats['max_date'] = $date;
                    }
                }
                $this->processAttendanceRecord($attendance);
            }

            // Detectar ausencias automáticamente
            $this->detectMissingAttendanceRecords();

            // Finalizar log
            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = "Sincronización completada: {$this->stats['inserted']} insertados, {$this->stats['updated']} actualizados, {$this->stats['skipped']} omitidos, {$this->stats['errors']} errores. Ausencias: {$this->stats['absences_created']} creadas";
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
            // Nota: El API puede no soportar filtros de fecha directamente
            // Obtenemos todos los registros y filtramos localmente
            $attendances = $this->apiClient->getAttendances();
            $this->stats['fetched'] = is_array($attendances) ? count($attendances) : 0;

            if (empty($attendances)) {
                $this->endSyncLog('SUCCESS', 'No hay marcaciones disponibles en el API');
                return $this->stats;
            }

            // DEBUG: Ver primeras 3 marcaciones para entender el formato
            $sampleDates = [];
            foreach (array_slice($attendances, 0, 3) as $sample) {
                $ts = $sample['timestamp'] ?? $sample['actual_timestamp'] ?? $sample['registered_timestamp'] ?? 'NO_TIMESTAMP';
                $parsed = $ts !== 'NO_TIMESTAMP' ? date('Y-m-d H:i:s', strtotime($ts)) : 'ERROR';
                $sampleDates[] = "Original: {$ts} | Parsed: {$parsed}";
            }
            error_log("SYNC DEBUG - Muestras de timestamps del API:\n" . implode("\n", $sampleDates));

            // Filtrar por rango de fechas
            $filteredCount = 0;
            $filteredAttendances = array_filter($attendances, function($record) use ($startDate, $endDate, &$filteredCount) {
                // Usar la misma lógica que extractRecordDate() para obtener el timestamp
                $ts = $record['timestamp'] ?? ($record['actual_timestamp'] ?? ($record['registered_timestamp'] ?? null));

                if (!$ts) {
                    return false;
                }

                try {
                    $recordDate = date('Y-m-d', strtotime($ts));
                    $match = $recordDate >= $startDate && $recordDate <= $endDate;
                    if ($match) {
                        $filteredCount++;
                    }
                    return $match;
                } catch (\Exception $e) {
                    return false;
                }
            });

            error_log("SYNC DEBUG: Total API: {$this->stats['fetched']}, Filtradas: {$filteredCount}, Rango: {$startDate} a {$endDate}");

            // Procesar marcaciones filtradas
            foreach ($filteredAttendances as $attendance) {
                $this->processAttendanceRecord($attendance);
            }

            // Establecer rango reportado según parámetros del API/rango aplicados
            $this->stats['min_date'] = $startDate;
            $this->stats['max_date'] = $endDate;

            // NOTA: La detección de ausencias se ejecuta en el cron de fin de día
            // NO en la sincronización automática cada 15 minutos

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
                $date = $this->extractRecordDate($attendance);
                if ($date) {
                    if (empty($this->stats['min_date']) || $date < $this->stats['min_date']) {
                        $this->stats['min_date'] = $date;
                    }
                    if (empty($this->stats['max_date']) || $date > $this->stats['max_date']) {
                        $this->stats['max_date'] = $date;
                    }
                }
                $this->processAttendanceRecord($attendance);
            }

            // Detectar ausencias automáticamente
            $this->detectMissingAttendanceRecords();

            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $this->endSyncLog($status, "Sincronización de empleado completada. Ausencias: {$this->stats['absences_created']} creadas");

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
                $date = $this->extractRecordDate($attendance);
                if ($date) {
                    if (empty($this->stats['min_date']) || $date < $this->stats['min_date']) {
                        $this->stats['min_date'] = $date;
                    }
                    if (empty($this->stats['max_date']) || $date > $this->stats['max_date']) {
                        $this->stats['max_date'] = $date;
                    }
                }
                $this->processAttendanceRecord($attendance);
            }

            // Detectar ausencias automáticamente
            $this->detectMissingAttendanceRecords();

            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = "Sincronización incremental completada: {$this->stats['inserted']} nuevos registros. Ausencias: {$this->stats['absences_created']} creadas";
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
     * Extraer fecha (Y-m-d) desde un registro crudo de la API
     */
    private function extractRecordDate(array $raw): ?string
    {
        try {
            $ts = $raw['timestamp'] ?? ($raw['actual_timestamp'] ?? ($raw['registered_timestamp'] ?? null));
            if (!$ts) return null;
            $dt = new \DateTime($ts);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
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

        // Mapeo de tipos API a ENUM punch_type
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
            $this->config['api_provider'] ?? 'api',
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
        // Consolidar detalles (errores + estadísticas de ausencias)
        $details = [
            'errors' => $this->errors,
            'absence_detection' => [
                'working_days_checked' => $this->stats['working_days_checked'],
                'employees_checked' => $this->stats['employees_checked'],
                'missing_days_detected' => $this->stats['missing_days_detected'],
                'absences_created' => $this->stats['absences_created'],
                'absences_errors' => $this->stats['absences_errors'],
                'date_range' => [
                    'min_date' => $this->stats['min_date'],
                    'max_date' => $this->stats['max_date']
                ]
            ]
        ];

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
            json_encode($details),
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
        $timestamp = date('Y-m-d H:i:s');
        $logFile = null;

        try {
            $logDir = \App\Core\TenantStorage::getLogDirectory();
            \App\Core\TenantStorage::ensureDirectory($logDir);
            $logFile = rtrim($logDir, '/\\') . '/attendance_sync_' . date('Y-m-d') . '.log';
        } catch (\Throwable $e) {
            $logDir = __DIR__ . '/../../../storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/attendance_sync_' . date('Y-m-d') . '.log';
        }

        if (!$logFile || @file_put_contents($logFile, "[{$timestamp}] {$message}" . PHP_EOL, FILE_APPEND) === false) {
            error_log("AttendanceSyncService log fallback: {$message}");
        }
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

    // =====================================================
    // DETECCIÓN AUTOMÁTICA DE AUSENCIAS
    // =====================================================

    /**
     * Detectar días laborables sin marcaciones y crear ausencias automáticas
     *
     * Compara el rango de fechas sincronizado con el calendario empresarial
     * para detectar días sin marcaciones y crea:
     * - attendance_header para el día
     * - attendance_detail con status ABSENT para cada empleado
     * - attendance_absence_log para tracking
     *
     * @return void
     */
    private function detectMissingAttendanceRecords()
    {
        // Determinar rango de fechas para análisis de ausencias
        $today = date('Y-m-d');
        $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

        // Si no hay rango detectado desde marcaciones, usar últimos 7 días hasta hoy
        if (!$this->stats['min_date']) {
            $this->stats['min_date'] = $sevenDaysAgo;
            $this->log("No hay marcaciones, usando rango por defecto: últimos 7 días");
        }

        // Asegurar que el rango cubra al menos los últimos 7 días
        // Esto es importante para sincronizaciones de "día actual" que solo analizan hoy
        if ($this->stats['min_date'] > $sevenDaysAgo) {
            $originalMinDate = $this->stats['min_date'];
            $this->stats['min_date'] = $sevenDaysAgo;
            $this->log("Extendiendo rango hacia atrás. Original: {$originalMinDate}, Nuevo: {$sevenDaysAgo} (últimos 7 días)");
        }

        // Siempre extender hasta hoy para detectar ausencias recientes
        // Esto asegura que se detecten días laborables sin marcaciones
        if (!$this->stats['max_date'] || $this->stats['max_date'] < $today) {
            $originalMaxDate = $this->stats['max_date'] ?? 'N/A';
            $this->stats['max_date'] = $today;
            $this->log("Extendiendo rango hasta hoy. Original: {$originalMaxDate}, Nuevo: {$today}");
        }

        $this->log("Iniciando detección de ausencias para período: {$this->stats['min_date']} - {$this->stats['max_date']}");

        try {
            // 1. Obtener días laborables en el rango
            $workingDays = $this->businessCalendar->getWorkingDaysList(
                $this->stats['min_date'],
                $this->stats['max_date']
            );

            $this->stats['working_days_checked'] = count($workingDays);

            if (empty($workingDays)) {
                $this->log("No hay días laborables en el rango especificado");
                return;
            }

            // 2. Obtener empleados activos con marca_asistencia = 1
            $employees = $this->getActiveMarkingEmployees();
            $this->stats['employees_checked'] = count($employees);

            if (empty($employees)) {
                $this->log("No hay empleados con marca_asistencia activa");
                return;
            }

            $this->log("Analizando {$this->stats['working_days_checked']} días laborables para {$this->stats['employees_checked']} empleados");

            // 3. Por cada día laboral, verificar si faltan marcaciones
            foreach ($workingDays as $date) {
                $this->processAbsentDay($date, $employees);
            }

            $this->log("Detección completada: {$this->stats['missing_days_detected']} ausencias detectadas, {$this->stats['absences_created']} registros creados");

        } catch (Exception $e) {
            $this->stats['absences_errors']++;
            $this->errors[] = "Error en detección de ausencias: " . $e->getMessage();
            error_log("Error detectMissingAttendanceRecords: " . $e->getMessage());
        }
    }

    /**
     * Procesar un día que podría tener ausencias
     * Crea header + details si hay empleados sin marcación
     *
     * @param string $date Fecha a procesar (Y-m-d)
     * @param array $employees Lista de empleados activos
     * @return void
     */
    private function processAbsentDay($date, $employees)
    {
        $absentEmployees = [];

        // 1. Identificar empleados sin marcación ese día
        foreach ($employees as $employee) {
            // Verificar si el empleado estaba activo en esa fecha
            if (!$this->isEmployeeActiveOnDate($employee, $date)) {
                continue;
            }

            // Verificar si tiene marcación en attendance_records
            if (!$this->hasAttendanceRecord($employee['id'], $date)) {
                $absentEmployees[] = $employee;
            }
        }

        // Si no hay ausentes, no hacer nada
        if (empty($absentEmployees)) {
            return;
        }

        $this->log("Día {$date}: detectados " . count($absentEmployees) . " empleados ausentes");

        try {
            // 2. Verificar si ya existe header para este día
            $headerModel = new AttendanceHeader();
            $existingHeader = $headerModel->getByDate($date);

            if ($existingHeader) {
                $headerId = $existingHeader['id'];
                $this->log("Usando header existente ID {$headerId} para {$date}");
            } else {
                // 3. Crear header para el día
                $headerId = $this->createAbsenceHeader($date, count($absentEmployees));
                if (!$headerId) {
                    $this->log("Error: No se pudo crear header para {$date}");
                    return;
                }
                $this->log("Header creado ID {$headerId} para {$date}");
            }

            // 4. Crear attendance_detail + absence_log para cada ausente
            foreach ($absentEmployees as $employee) {
                $this->createAbsenceRecords($headerId, $employee, $date);
            }

        } catch (Exception $e) {
            $this->stats['absences_errors']++;
            $this->errors[] = "Error procesando día {$date}: " . $e->getMessage();
            error_log("Error processAbsentDay {$date}: " . $e->getMessage());
        }
    }

    /**
     * Crear header de asistencia para día con ausencias
     *
     * @param string $date Fecha (Y-m-d)
     * @param int $totalAbsent Total de empleados ausentes
     * @return int|false ID del header creado o false si falla
     */
    private function createAbsenceHeader($date, $totalAbsent)
    {
        try {
            $headerModel = new AttendanceHeader();

            $headerData = [
                'attendance_date' => $date,
                'device_id' => null,
                'total_records' => $totalAbsent,
                'total_employees' => $totalAbsent,
                'total_on_time' => 0,
                'total_late' => 0,
                'total_absent' => $totalAbsent,
                'is_processed' => 1,
                'processed_at' => date('Y-m-d H:i:s'),
                'processed_by' => null,
                'sync_batch_id' => $this->syncLogId,
                'synced_from' => 'API',
                'notes' => 'Ausencias detectadas automáticamente - sin marcaciones en API'
            ];

            return $headerModel->create($headerData);

        } catch (Exception $e) {
            error_log("Error createAbsenceHeader: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear registros de ausencia en attendance_detail y attendance_absence_log
     *
     * @param int $headerId ID del header
     * @param array $employee Datos del empleado
     * @param string $date Fecha de la ausencia
     * @return void
     */
    private function createAbsenceRecords($headerId, $employee, $date)
    {
        try {
            $detailModel = new AttendanceDetail();

            // 1. Verificar si ya existe el detalle para evitar duplicados
            if ($detailModel->exists($headerId, $employee['id'])) {
                $this->log("Ya existe detalle para empleado {$employee['id']} en header {$headerId}");
                return;
            }

            // 2. Crear registro en attendance_detail
            $detailData = [
                'header_id' => $headerId,
                'employee_id' => $employee['id'],
                'schedule_id' => null,
                'time_in' => null,
                'time_out' => null,
                'scheduled_time_in' => null,
                'scheduled_time_out' => null,
                'device_id' => null,
                'external_id' => null,
                'tardiness_minutes' => 0,
                'is_late' => 0,
                'early_departure_minutes' => 0,
                'hours_worked' => 0,
                'status' => 'ABSENT',
                'justification_type' => null,
                'justification_notes' => null,
                'justification_document' => null,
                'notes' => 'Ausencia detectada automáticamente - sin marcación en API'
            ];

            $detailId = $detailModel->create($detailData);

            if ($detailId) {
                $this->log("Detalle creado ID {$detailId} para empleado {$employee['id']} ({$employee['firstname']} {$employee['lastname']})");
                $this->stats['missing_days_detected']++;
            }

            // 3. Crear registro en attendance_absence_log
            $absence = [
                'employee_id' => $employee['id'],
                'date' => $date,
                'absence_type' => 'UNJUSTIFIED',
                'is_working_day' => true,
                'day_type' => 'LABORAL',
                'detected_at' => date('Y-m-d H:i:s'),
                'detection_method' => 'AUTO_SYNC'
            ];

            $this->absenceDetector->saveAbsence($absence);
            $this->stats['absences_created']++;

        } catch (Exception $e) {
            $this->stats['absences_errors']++;
            $this->errors[] = "Error creando registros de ausencia para empleado {$employee['id']} en {$date}: " . $e->getMessage();
            error_log("Error createAbsenceRecords: " . $e->getMessage());
        }
    }

    /**
     * Obtener empleados activos que deben marcar asistencia
     *
     * @return array Array de empleados con marca_asistencia = 1
     */
    private function getActiveMarkingEmployees()
    {
        try {
            // Usar max_date (que ahora es hoy) como referencia
            // Esto incluye empleados activos al final del período
            $referenceDate = $this->stats['max_date'] ?? date('Y-m-d');

            // Considerar empleados activos por situación (igual que Employee::getActiveMarkingEmployees)
            $sql = "SELECT e.id, e.firstname, e.lastname, e.employee_id, e.email, e.fecha_ingreso
                    FROM employees e
                    LEFT JOIN situaciones sit ON e.situacion_id = sit.id
                    WHERE (e.situacion_id = 1 OR sit.descripcion LIKE '%activ%' OR sit.descripcion LIKE '%ACTIV%' OR e.situacion_id IS NULL)
                      AND COALESCE(e.marca_asistencia, 0) = 1
                    ORDER BY e.lastname, e.firstname";

            $results = $this->db->findAll($sql);

            $this->log("Empleados con marca_asistencia encontrados: " . count($results) . " (filtro por situación activa)");

            return $results ?: [];

        } catch (Exception $e) {
            error_log("Error getActiveMarkingEmployees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un empleado estaba activo en una fecha específica
     *
     * @param array $employee Datos del empleado
     * @param string $date Fecha a verificar (Y-m-d)
     * @return bool
     */
    private function isEmployeeActiveOnDate($employee, $date)
    {
        // Verificar fecha de ingreso
        $fechaIngreso = $employee['fecha_ingreso'] ?? null;
        if ($fechaIngreso && $date < $fechaIngreso) {
            return false;
        }

        // Si llegó hasta acá, está activo
        return true;
    }

    /**
     * Verificar si existe marcación para un empleado en una fecha
     *
     * @param int $employeeId ID del empleado
     * @param string $date Fecha a verificar (Y-m-d)
     * @return bool True si tiene marcación, False si no
     */
    private function hasAttendanceRecord($employeeId, $date)
    {
        try {
            $sql = "SELECT COUNT(*) as count
                    FROM attendance_records
                    WHERE employee_id = ?
                    AND punch_date = ?
                    LIMIT 1";

            $result = $this->db->find($sql, [$employeeId, $date]);

            return isset($result['count']) && $result['count'] > 0;

        } catch (Exception $e) {
            error_log("Error hasAttendanceRecord: " . $e->getMessage());
            return false;
        }
    }

}
