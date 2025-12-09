<?php

namespace App\Controllers\Webhooks;

use App\Core\Controller;
use App\Models\AttendanceApiConfig;
use App\Services\Attendance\AttendanceSyncService;
use Exception;

/**
 * Controlador para recibir webhooks de API
 * Endpoint público para notificaciones en tiempo real
 */
class ApiWebhookController extends Controller
{
    /**
     * Recibir notificación de webhook
     * POST /webhooks/api/attendance
     */
    public function receive()
    {
        // Solo permitir POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            $this->json(['error' => 'Método no permitido'], 405);
            return;
        }

        try {
            // Obtener payload
            $rawPayload = file_get_contents('php://input');
            $payload = json_decode($rawPayload, true);

            // Logging del webhook recibido
            $this->logWebhook('RECEIVED', $rawPayload);

            // Validar que el payload no esté vacío
            if (empty($payload)) {
                $this->logWebhook('ERROR', 'Payload vacío');
                http_response_code(400);
                $this->json(['error' => 'Payload vacío'], 400);
                return;
            }

            // Obtener configuración
            $configModel = new AttendanceApiConfig();
            $config = $configModel->getByProvider('api');

            if (!$config) {
                $this->logWebhook('ERROR', 'Configuración no encontrada');
                http_response_code(404);
                $this->json(['error' => 'Configuración no encontrada'], 404);
                return;
            }

            // Validar firma del webhook (si está configurada)
            if (!empty($config['webhook_secret'])) {
                $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
                if (!$this->validateSignature($rawPayload, $signature, $config['webhook_secret'])) {
                    $this->logWebhook('ERROR', 'Firma inválida');
                    http_response_code(401);
                    $this->json(['error' => 'Firma inválida'], 401);
                    return;
                }
            }

            // Determinar tipo de evento
            $eventType = $payload['event'] ?? 'unknown';
            $this->logWebhook('EVENT_TYPE', $eventType);

            // Procesar según tipo de evento
            $result = $this->processEvent($eventType, $payload);

            // Responder inmediatamente con éxito
            http_response_code(200);
            $this->json([
                'success' => true,
                'message' => 'Webhook procesado',
                'event_type' => $eventType,
                'result' => $result
            ]);

        } catch (Exception $e) {
            $this->logWebhook('ERROR', $e->getMessage());
            http_response_code(500);
            $this->json([
                'error' => 'Error procesando webhook',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validar firma del webhook
     * @param string $payload Payload crudo
     * @param string $signature Firma recibida
     * @param string $secret Secret configurado
     * @return bool
     */
    private function validateSignature($payload, $signature, $secret)
    {
        // Calcular firma esperada (HMAC SHA256)
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        // Comparación segura
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Procesar evento según tipo
     * @param string $eventType Tipo de evento
     * @param array $data Datos del evento
     * @return array Resultado del procesamiento
     */
    private function processEvent($eventType, $data)
    {
        switch ($eventType) {
            case 'attendance.created':
            case 'attendance.updated':
                return $this->processAttendanceEvent($data);

            case 'employee.created':
            case 'employee.updated':
                return $this->processEmployeeEvent($data);

            case 'ping':
            case 'test':
                // Evento de prueba, solo responder OK
                return ['processed' => true, 'message' => 'Ping received'];

            default:
                $this->logWebhook('WARNING', "Tipo de evento no soportado: {$eventType}");
                return ['processed' => false, 'message' => 'Evento no soportado'];
        }
    }

    /**
     * Procesar evento de asistencia
     * @param array $data Datos del evento
     * @return array
     */
    private function processAttendanceEvent($data)
    {
        try {
            // Obtener configuración
            $configModel = new AttendanceApiConfig();
            $config = $configModel->getByProvider('api');

            if (!$config) {
                throw new Exception('Configuración no encontrada');
            }

            // Guardar datos crudos en attendance_raw_data
            $sql = "INSERT INTO attendance_raw_data
                    (external_id, api_provider, entity_type, raw_json, received_at, processed)
                    VALUES (?, 'api', 'Attendance', ?, NOW(), 0)";

            $externalId = $data['data']['id'] ?? null;
            $rawJson = json_encode($data['data'] ?? $data);

            $db = \App\Core\Database::getInstance();
            $db->query($sql, [$externalId, $rawJson]);

            // Procesar en background (opcional)
            // Podría agregarse a una cola de jobs si se implementa un sistema de colas

            $this->logWebhook('SUCCESS', "Evento de asistencia guardado: {$externalId}");

            return [
                'processed' => true,
                'external_id' => $externalId,
                'message' => 'Evento de asistencia guardado'
            ];

        } catch (Exception $e) {
            $this->logWebhook('ERROR', "Error procesando asistencia: " . $e->getMessage());
            return [
                'processed' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesar evento de empleado
     * @param array $data Datos del evento
     * @return array
     */
    private function processEmployeeEvent($data)
    {
        try {
            // Guardar datos crudos
            $sql = "INSERT INTO attendance_raw_data
                    (external_id, api_provider, entity_type, raw_json, received_at, processed)
                    VALUES (?, 'api', 'Employee', ?, NOW(), 0)";

            $externalId = $data['data']['id'] ?? null;
            $rawJson = json_encode($data['data'] ?? $data);

            $db = \App\Core\Database::getInstance();
            $db->query($sql, [$externalId, $rawJson]);

            $this->logWebhook('SUCCESS', "Evento de empleado guardado: {$externalId}");

            return [
                'processed' => true,
                'external_id' => $externalId,
                'message' => 'Evento de empleado guardado'
            ];

        } catch (Exception $e) {
            $this->logWebhook('ERROR', "Error procesando empleado: " . $e->getMessage());
            return [
                'processed' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Logging de webhooks
     * @param string $level Nivel del log (RECEIVED, SUCCESS, ERROR, WARNING)
     * @param string $message Mensaje
     */
    private function logWebhook($level, $message)
    {
        $logDir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/webhooks_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        $logMessage = sprintf(
            "[%s] [%s] [IP: %s] %s\n",
            $timestamp,
            $level,
            $ip,
            is_string($message) ? $message : json_encode($message)
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
