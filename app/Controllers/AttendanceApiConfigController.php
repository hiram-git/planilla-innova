<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Models\AttendanceApiConfig;
use App\Services\Attendance\Base44ApiClient;
use App\Services\Attendance\AttendanceSyncService;
use Exception;

/**
 * Controlador para gestión de configuración de API de asistencias
 */
class AttendanceApiConfigController extends Controller
{
    private $configModel;

    public function __construct()
    {
        $this->requireAuth();
        $this->configModel = new AttendanceApiConfig();
    }

    /**
     * Vista principal de configuración
     */
    public function index()
    {
        $config = $this->configModel->getActiveConfig();
        $syncStats = null;

        if ($config) {
            $syncStats = $this->configModel->getSyncStats($config['id'], 7);
        }

        // Obtener últimos 20 logs de sincronización
        $sql = "SELECT * FROM attendance_sync_log
                ORDER BY start_time DESC
                LIMIT 20";
        $syncLogs = $this->configModel->db->findAll($sql);

        $data = [
            'title' => 'Configuración API Asistencias',
            'page_title' => 'Configuración API de Asistencias',
            'config' => $config,
            'sync_stats' => $syncStats,
            'sync_logs' => $syncLogs,
            'csrf_token' => Security::generateToken()
        ];

        $this->render('admin/attendance/api_config', $data);
    }

    /**
     * Guardar/actualizar configuración
     */
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/attendance/api-config');
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);

        // Validar datos
        $validation = $this->configModel->validateApiCredentials($data);
        if (!$validation['valid']) {
            $_SESSION['errors'] = $validation['errors'];
            $_SESSION['old_data'] = $data;
            $this->redirect('/panel/attendance/api-config');
        }

        try {
            $configData = [
                'api_provider' => $data['api_provider'] ?? 'base44',
                'api_key' => $data['api_key'],
                'app_id' => $data['app_id'],
                'api_url' => $data['api_url'],
                'sync_enabled' => isset($data['sync_enabled']) ? 1 : 0,
                'sync_interval_minutes' => (int) ($data['sync_interval_minutes'] ?? 15),
                'webhook_url' => $data['webhook_url'] ?? null,
                'webhook_secret' => $data['webhook_secret'] ?? null
            ];

            // Verificar si existe configuración
            $existingConfig = $this->configModel->getActiveConfig();

            if ($existingConfig) {
                // Actualizar
                $this->configModel->update($existingConfig['id'], $configData);
                $message = 'Configuración actualizada exitosamente';
            } else {
                // Crear
                $this->configModel->create($configData);
                $message = 'Configuración creada exitosamente';
            }

            $this->setFlashMessage('success', $message);
            $this->redirect('/panel/attendance/api-config');

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Error al guardar configuración: ' . $e->getMessage());
            $_SESSION['old_data'] = $data;
            $this->redirect('/panel/attendance/api-config');
        }
    }

    /**
     * Probar conexión con API (AJAX)
     */
    public function testConnection()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $apiKey = $_POST['api_key'] ?? null;
            $appId = $_POST['app_id'] ?? null;
            $apiUrl = $_POST['api_url'] ?? null;

            if (!$apiKey || !$appId || !$apiUrl) {
                $this->json([
                    'success' => false,
                    'message' => 'Faltan parámetros requeridos'
                ]);
            }

            $client = new Base44ApiClient($apiKey, $appId, $apiUrl);
            $result = $client->testConnection();

            $this->json($result);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al probar conexión: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Sincronizar ahora (manual)
     */
    public function syncNow()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/attendance/api-config');
        }

        AuthMiddleware::validateCSRF();

        try {
            $config = $this->configModel->getActiveConfig();

            if (!$config) {
                $this->setFlashMessage('error', 'No hay configuración activa');
                $this->redirect('/panel/attendance/api-config');
                return;
            }

            // Obtener tipo de sincronización
            $syncType = $_POST['sync_type'] ?? 'full';
            $syncService = new AttendanceSyncService($config['id']);
            $stats = [];

            // Ejecutar sincronización según el tipo
            switch ($syncType) {
                case 'today':
                    // Sincronizar solo el día actual
                    $today = date('Y-m-d');
                    $stats = $syncService->syncByDateRange($today, $today);
                    $syncLabel = 'día actual (' . date('d/m/Y') . ')';
                    break;

                case 'daterange':
                    // Sincronizar por rango de fechas
                    $startDate = $_POST['start_date'] ?? date('Y-m-d');
                    $endDate = $_POST['end_date'] ?? date('Y-m-d');

                    // Validar fechas
                    if (strtotime($startDate) > strtotime($endDate)) {
                        $this->setFlashMessage('error', 'La fecha de inicio no puede ser mayor que la fecha de fin');
                        $this->redirect('/panel/attendance/api-config');
                        return;
                    }

                    $stats = $syncService->syncByDateRange($startDate, $endDate);
                    $syncLabel = "rango " . date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate));
                    break;

                case 'full':
                default:
                    // Sincronizar todo
                    $stats = $syncService->syncAll();
                    $syncLabel = 'completa';
                    break;
            }

            $message = sprintf(
                'Sincronización %s: %d obtenidos, %d insertados, %d actualizados, %d omitidos, %d errores',
                $syncLabel,
                $stats['fetched'],
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped'],
                $stats['errors']
            );

            if ($stats['errors'] > 0) {
                $this->setFlashMessage('warning', $message);
            } else {
                $this->setFlashMessage('success', $message);
            }

            $this->redirect('/panel/attendance/api-config');

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Error en sincronización: ' . $e->getMessage());
            $this->redirect('/panel/attendance/api-config');
        }
    }

    /**
     * Habilitar sincronización automática
     */
    public function enableSync()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/attendance/api-config');
        }

        AuthMiddleware::validateCSRF();

        try {
            $configId = $_POST['config_id'] ?? null;

            if (!$configId) {
                $this->setFlashMessage('error', 'ID de configuración requerido');
                $this->redirect('/panel/attendance/api-config');
            }

            $this->configModel->enableSync($configId);
            $this->setFlashMessage('success', 'Sincronización automática habilitada');
            $this->redirect('/panel/attendance/api-config');

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Error: ' . $e->getMessage());
            $this->redirect('/panel/attendance/api-config');
        }
    }

    /**
     * Deshabilitar sincronización automática
     */
    public function disableSync()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/attendance/api-config');
        }

        AuthMiddleware::validateCSRF();

        try {
            $configId = $_POST['config_id'] ?? null;

            if (!$configId) {
                $this->setFlashMessage('error', 'ID de configuración requerido');
                $this->redirect('/panel/attendance/api-config');
            }

            $this->configModel->disableSync($configId);
            $this->setFlashMessage('success', 'Sincronización automática deshabilitada');
            $this->redirect('/panel/attendance/api-config');

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Error: ' . $e->getMessage());
            $this->redirect('/panel/attendance/api-config');
        }
    }

    /**
     * Ver detalles de un log específico (AJAX)
     */
    public function getLogDetails()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $logId = $_POST['log_id'] ?? null;

        if (!$logId) {
            $this->json(['error' => 'ID de log requerido'], 400);
        }

        $sql = "SELECT * FROM attendance_sync_log WHERE id = ?";
        $log = $this->configModel->db->find($sql, [$logId]);

        if (!$log) {
            $this->json(['error' => 'Log no encontrado'], 404);
        }

        // Decodificar JSON fields
        if (!empty($log['filters_json'])) {
            $log['filters'] = json_decode($log['filters_json'], true);
        }

        if (!empty($log['error_details'])) {
            $log['errors'] = json_decode($log['error_details'], true);
        }

        $this->json([
            'success' => true,
            'log' => $log
        ]);
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanOldLogs()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/attendance/api-config');
        }

        AuthMiddleware::validateCSRF();

        try {
            $days = (int) ($_POST['days'] ?? 30);

            $sql = "DELETE FROM attendance_sync_log
                    WHERE start_time < DATE_SUB(NOW(), INTERVAL ? DAY)";

            $this->configModel->db->query($sql, [$days]);

            $deleted = $this->configModel->db->rowCount();

            $this->setFlashMessage('success', "Se eliminaron {$deleted} logs antiguos");
            $this->redirect('/panel/attendance/api-config');

        } catch (Exception $e) {
            $this->setFlashMessage('error', 'Error al limpiar logs: ' . $e->getMessage());
            $this->redirect('/panel/attendance/api-config');
        }
    }

    /**
     * Obtener estado de sincronización (AJAX)
     * Útil para polling en la interfaz
     */
    public function getSyncStatus()
    {
        $config = $this->configModel->getActiveConfig();

        if (!$config) {
            $this->json([
                'success' => false,
                'message' => 'No hay configuración activa'
            ]);
        }

        // Verificar si hay una sincronización en curso
        $sql = "SELECT * FROM attendance_sync_log
                WHERE status = 'RUNNING'
                ORDER BY start_time DESC
                LIMIT 1";
        $runningSync = $this->configModel->db->find($sql);

        // Obtener última sincronización completada
        $sql = "SELECT * FROM attendance_sync_log
                WHERE status IN ('SUCCESS', 'FAILED', 'PARTIAL')
                ORDER BY end_time DESC
                LIMIT 1";
        $lastSync = $this->configModel->db->find($sql);

        // Calcular tiempo hasta próxima sincronización
        $minutesUntilNext = $this->configModel->getMinutesUntilNextSync($config['id']);

        $this->json([
            'success' => true,
            'config' => [
                'sync_enabled' => (bool) $config['sync_enabled'],
                'sync_interval_minutes' => $config['sync_interval_minutes'],
                'last_sync_at' => $config['last_sync_at'],
                'last_sync_status' => $config['last_sync_status']
            ],
            'running_sync' => $runningSync,
            'last_sync' => $lastSync,
            'minutes_until_next' => $minutesUntilNext
        ]);
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}
