<?php

namespace App\Services;

use App\Core\Database;
use App\Models\BusinessCalendar;
use Exception;

/**
 * Servicio para sincronización manual del calendario empresarial desde API externa
 * Sincroniza días especiales, feriados y configuraciones del calendario
 */
class CalendarSyncService
{
    private $db;
    private $calendarModel;
    private $apiKey;
    private $appId;
    private $apiUrl;
    private $stats = [
        'fetched' => 0,
        'inserted' => 0,
        'updated' => 0,
        'skipped' => 0,
        'deleted' => 0,
        'errors' => 0
    ];
    private $errors = [];
    private $syncLogId;

    /**
     * Constructor
     * @param string|null $apiKey API key de la API externa (opcional, usa env var si no se provee)
     * @param string|null $appId App ID de la API externa (opcional, usa env var si no se provee)
     */
    public function __construct($apiKey = null, $appId = null)
    {
        $this->db = Database::getInstance();
        $this->calendarModel = new BusinessCalendar();

        // Configuración API
        $this->apiKey = $apiKey ?: '40162908d71941b98636b38106be556e';
        $this->appId = $appId ?: '68dd9181444436f4bd157e1d';
        $this->apiUrl = 'https://app.base44.com/api';
    }

    /**
     * Sincronizar calendario desde API externa
     * @param array $options Opciones de sincronización
     *        - year: Año específico a sincronizar (opcional, por defecto año actual)
     *        - replace: Si true, reemplaza todos los registros existentes (opcional, default false)
     *        - dry_run: Si true, solo simula sin guardar cambios (opcional, default false)
     * @return array Estadísticas de sincronización
     */
    public function syncFromApi($options = [])
    {
        $year = $options['year'] ?? date('Y');
        $replace = $options['replace'] ?? false;
        $dryRun = $options['dry_run'] ?? false;

        $this->startSyncLog('MANUAL', ['year' => $year, 'replace' => $replace, 'dry_run' => $dryRun]);

        try {
            // 1. Obtener datos del calendario desde API
            $this->log("Iniciando sincronización del calendario para año {$year}");
            $calendarEntities = $this->fetchCalendarFromApi();

            if (empty($calendarEntities)) {
                $message = "No se obtuvieron datos del calendario desde la API";
                $this->log($message);
                $this->endSyncLog('SUCCESS', $message);
                return $this->stats;
            }

            $this->stats['fetched'] = count($calendarEntities);
            $this->log("Obtenidos {$this->stats['fetched']} registros del calendario desde API");

            // 2. Si replace = true, eliminar registros existentes del año
            if ($replace && !$dryRun) {
                $deleted = $this->deleteYearRecords($year);
                $this->stats['deleted'] = $deleted;
                $this->log("Eliminados {$deleted} registros existentes del año {$year}");
            }

            // 3. Procesar cada entidad del calendario
            foreach ($calendarEntities as $entity) {
                $this->processCalendarEntity($entity, $dryRun);
            }

            // 4. Finalizar sincronización
            $status = $this->stats['errors'] > 0 ? 'PARTIAL' : 'SUCCESS';
            $message = $dryRun
                ? "Simulación completada (dry-run): {$this->stats['inserted']} a insertar, {$this->stats['updated']} a actualizar, {$this->stats['skipped']} omitidos"
                : "Sincronización completada: {$this->stats['inserted']} insertados, {$this->stats['updated']} actualizados, {$this->stats['skipped']} omitidos, {$this->stats['errors']} errores";

            $this->log($message);
            $this->endSyncLog($status, $message);

            return $this->stats;

        } catch (Exception $e) {
            $message = 'Error durante sincronización: ' . $e->getMessage();
            $this->log($message);
            $this->endSyncLog('FAILED', $message);
            throw $e;
        }
    }

    /**
     * Obtener datos del calendario desde API externa
     * @return array Array de entidades del calendario
     * @throws Exception Si falla la petición
     */
    private function fetchCalendarFromApi()
    {
        try {
            $url = "{$this->apiUrl}/apps/{$this->appId}/entities/Calendar";

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'api_key: ' . $this->apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("Error cURL: {$error}");
            }

            if ($httpCode !== 200) {
                throw new Exception("Error HTTP {$httpCode}: {$response}");
            }

            $data = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error decodificando JSON: " . json_last_error_msg());
            }

            // La API externa típicamente retorna: {data: [...], meta: {...}}
            return $data['data'] ?? $data;

        } catch (Exception $e) {
            $this->errors[] = "Error obteniendo datos de API: " . $e->getMessage();
            error_log("CalendarSyncService - fetchCalendarFromApi error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar una entidad individual del calendario
     * @param array $entity Datos de la entidad desde API
     * @param bool $dryRun Si true, solo simula sin guardar
     */
    private function processCalendarEntity($entity, $dryRun = false)
    {
        try {
            // Mapear campos del API a campos de BD
            $calendarData = $this->mapApiToDatabase($entity);

            if (!$calendarData) {
                $this->stats['skipped']++;
                return;
            }

            // Verificar si ya existe el registro
            $existing = $this->calendarModel->getByDate($calendarData['date_value']);

            if ($dryRun) {
                // Modo simulación
                if ($existing) {
                    $this->stats['updated']++;
                    $this->log("(DRY-RUN) Actualizaría: {$calendarData['date_value']} - {$calendarData['description']}");
                } else {
                    $this->stats['inserted']++;
                    $this->log("(DRY-RUN) Insertaría: {$calendarData['date_value']} - {$calendarData['description']}");
                }
            } else {
                // Modo real
                if ($existing) {
                    // Actualizar registro existente
                    $this->calendarModel->update($existing['id'], $calendarData);
                    $this->stats['updated']++;
                    $this->log("Actualizado: {$calendarData['date_value']} - {$calendarData['description']}");
                } else {
                    // Insertar nuevo registro
                    $this->calendarModel->create($calendarData);
                    $this->stats['inserted']++;
                    $this->log("Insertado: {$calendarData['date_value']} - {$calendarData['description']}");
                }
            }

        } catch (Exception $e) {
            $this->stats['errors']++;
            $this->errors[] = "Error procesando entidad: " . $e->getMessage();
            $this->log("Error: " . $e->getMessage());
        }
    }

    /**
     * Mapear datos del API a estructura de base de datos
     * @param array $entity Datos desde API
     * @return array|null Datos mapeados o null si no es válido
     */
    private function mapApiToDatabase($entity)
    {
        // Validar campos requeridos
        if (empty($entity['date']) && empty($entity['fecha'])) {
            $this->errors[] = "Entidad sin fecha: " . json_encode($entity);
            return null;
        }

        // Obtener fecha (puede venir como 'date' o 'fecha')
        $date = $entity['date'] ?? $entity['fecha'] ?? null;

        // Convertir fecha a formato Y-m-d si es necesario
        if ($date) {
            try {
                $dateObj = new \DateTime($date);
                $date = $dateObj->format('Y-m-d');
            } catch (Exception $e) {
                $this->errors[] = "Fecha inválida: {$date}";
                return null;
            }
        }

        // Mapear tipo de día
        $dayType = $this->mapDayType($entity['type'] ?? $entity['tipo'] ?? 'LABORAL');

        // Determinar si es fin de semana
        $dateObj = new \DateTime($date);
        $dayOfWeek = (int)$dateObj->format('N'); // 1 (lunes) a 7 (domingo)
        $isWeekend = ($dayOfWeek === 6 || $dayOfWeek === 7) ? 1 : 0;

        // Determinar si es feriado pagado
        // El API puede enviarlo como 'paid', 'pagado', 'is_paid', 'is_paid_holiday'
        $isPaidHoliday = 0;
        if (isset($entity['paid'])) {
            $isPaidHoliday = $entity['paid'] ? 1 : 0;
        } elseif (isset($entity['pagado'])) {
            $isPaidHoliday = $entity['pagado'] ? 1 : 0;
        } elseif (isset($entity['is_paid'])) {
            $isPaidHoliday = $entity['is_paid'] ? 1 : 0;
        } elseif (isset($entity['is_paid_holiday'])) {
            $isPaidHoliday = $entity['is_paid_holiday'] ? 1 : 0;
        } elseif ($dayType === 'FERIADO') {
            // Por defecto, los feriados nacionales son pagados
            $isPaidHoliday = 1;
        }

        return [
            'date_value' => $date,
            'day_type' => $dayType,
            'is_weekend' => $isWeekend,
            'is_paid_holiday' => $isPaidHoliday,
            'description' => $entity['description'] ?? $entity['descripcion'] ?? null,
            'notes' => $entity['notes'] ?? $entity['notas'] ?? 'Sincronizado desde API externa',
            'year' => (int)$dateObj->format('Y'),
            'month' => (int)$dateObj->format('m'),
            'day' => (int)$dateObj->format('d')
        ];
    }

    /**
     * Mapear tipo de día del API a ENUM de BD
     * @param string $apiType Tipo desde API
     * @return string Tipo ENUM válido
     */
    private function mapDayType($apiType)
    {
        $apiType = strtoupper(trim($apiType));

        $mapping = [
            'LABORAL' => 'LABORAL',
            'WORKING' => 'LABORAL',
            'NO_LABORAL' => 'NO_LABORAL',
            'NO LABORAL' => 'NO_LABORAL',
            'NON_WORKING' => 'NO_LABORAL',
            'FERIADO' => 'FERIADO',
            'HOLIDAY' => 'FERIADO',
            'FESTIVO' => 'FERIADO',
            'DUELO' => 'DUELO_NACIONAL',
            'DUELO_NACIONAL' => 'DUELO_NACIONAL',
            'DUELO NACIONAL' => 'DUELO_NACIONAL',
            'MOURNING' => 'DUELO_NACIONAL',
            'ESPECIAL' => 'ESPECIAL',
            'SPECIAL' => 'ESPECIAL'
        ];

        return $mapping[$apiType] ?? 'LABORAL';
    }

    /**
     * Eliminar registros del calendario para un año específico
     * @param int $year Año a limpiar
     * @return int Número de registros eliminados
     */
    private function deleteYearRecords($year)
    {
        try {
            $sql = "DELETE FROM business_calendar WHERE year = ?";
            $this->db->getConnection()->prepare($sql)->execute([$year]);
            return $this->db->getConnection()->rowCount();
        } catch (Exception $e) {
            $this->log("Error eliminando registros del año {$year}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Iniciar log de sincronización
     * @param string $syncType Tipo de sincronización
     * @param array $options Opciones de sincronización
     */
    private function startSyncLog($syncType, $options = [])
    {
        try {
            $triggeredBy = 'USER_' . ($_SESSION['user_id'] ?? 'UNKNOWN');

            $sql = "INSERT INTO calendar_sync_log
                    (sync_type, start_time, status, triggered_by, options_json)
                    VALUES (?, NOW(), 'RUNNING', ?, ?)";

            $this->db->getConnection()->prepare($sql)->execute([
                $syncType,
                $triggeredBy,
                json_encode($options)
            ]);

            $this->syncLogId = $this->db->getConnection()->lastInsertId();
            $this->log("Sincronización iniciada - Log ID: {$this->syncLogId}");
        } catch (Exception $e) {
            // Si falla el log, continuar sin bloquer la sincronización
            error_log("Error creando sync log: " . $e->getMessage());
        }
    }

    /**
     * Finalizar log de sincronización
     * @param string $status SUCCESS, FAILED, PARTIAL
     * @param string $message Mensaje descriptivo
     */
    private function endSyncLog($status, $message)
    {
        if (!$this->syncLogId) {
            return;
        }

        try {
            $details = [
                'errors' => $this->errors,
                'stats' => $this->stats
            ];

            $sql = "UPDATE calendar_sync_log
                    SET end_time = NOW(),
                        duration_seconds = TIMESTAMPDIFF(SECOND, start_time, NOW()),
                        records_fetched = ?,
                        records_inserted = ?,
                        records_updated = ?,
                        records_skipped = ?,
                        records_deleted = ?,
                        errors_count = ?,
                        error_details = ?,
                        status = ?,
                        status_message = ?
                    WHERE id = ?";

            $this->db->getConnection()->prepare($sql)->execute([
                $this->stats['fetched'],
                $this->stats['inserted'],
                $this->stats['updated'],
                $this->stats['skipped'],
                $this->stats['deleted'],
                $this->stats['errors'],
                json_encode($details),
                $status,
                $message,
                $this->syncLogId
            ]);
        } catch (Exception $e) {
            error_log("Error actualizando sync log: " . $e->getMessage());
        }
    }

    /**
     * Logging simple
     * @param string $message Mensaje a escribir
     */
    private function log($message)
    {
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/calendar_sync_' . date('Y-m-d') . '.log';
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
