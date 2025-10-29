<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AttendanceDevice;
use App\Core\Security;
use App\Middleware\AuthMiddleware;

class AttendanceDeviceController extends Controller
{
    private $deviceModel;

    public function __construct()
    {
        parent::__construct();
        $this->deviceModel = new AttendanceDevice();
    }

    /**
     * Lista de dispositivos
     */
    public function index()
    {
        $devices = $this->deviceModel->getAll();
        $stats = $this->deviceModel->getStats();

        $this->render('admin/attendance/devices/index', [
            'devices' => $devices,
            'stats' => $stats,
            'title' => 'Dispositivos de Marcación',
            'csrf_token' => Security::generateToken()
        ]);
    }

    /**
     * Formulario crear dispositivo
     */
    public function create()
    {
        $this->render('admin/attendance/devices/form', [
            'device' => null,
            'action' => 'create',
            'title' => 'Crear Dispositivo',
            'csrf_token' => Security::generateToken()
        ]);
    }

    /**
     * Guardar nuevo dispositivo
     */
    public function store()
    {
        try {
            error_log("AttendanceDeviceController@store - Iniciando guardado de dispositivo");
            error_log("POST data: " . print_r($_POST, true));

            // Validar CSRF token
            AuthMiddleware::validateCSRF();
            error_log("AttendanceDeviceController@store - CSRF validado");

            // Validar datos
            $this->validateDeviceData($_POST);
            error_log("AttendanceDeviceController@store - Datos validados");

            // Verificar que el código no exista
            if ($this->deviceModel->codeExists($_POST['device_code'])) {
                error_log("AttendanceDeviceController@store - Código ya existe: " . $_POST['device_code']);
                $this->setFlashMessage('El código de dispositivo ya existe.', 'error');
                return $this->redirect('/panel/attendance/devices/create');
            }

            // Preparar datos
            $data = $this->prepareDeviceData($_POST);
            $data['created_by'] = $_SESSION['admin_id'] ?? null;
            error_log("AttendanceDeviceController@store - Datos preparados: " . print_r($data, true));

            // Crear dispositivo
            $deviceId = $this->deviceModel->create($data);
            error_log("AttendanceDeviceController@store - deviceId retornado: " . ($deviceId ? $deviceId : 'FALSE'));

            if ($deviceId) {
                $this->setFlashMessage('Dispositivo creado exitosamente.', 'success');
                return $this->redirect('/panel/attendance/devices');
            } else {
                error_log("AttendanceDeviceController@store - ERROR: create() retornó FALSE");
                $this->setFlashMessage('Error al crear el dispositivo.', 'error');
                return $this->redirect('/panel/attendance/devices/create');
            }

        } catch (\Exception $e) {
            error_log("Error creating device: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->setFlashMessage('Error: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/attendance/devices/create');
        }
    }

    /**
     * Formulario editar dispositivo
     */
    public function edit($id)
    {
        $device = $this->deviceModel->getById($id);

        if (!$device) {
            $this->setFlashMessage('Dispositivo no encontrado.', 'error');
            return $this->redirect('/panel/attendance/devices');
        }

        $this->render('admin/attendance/devices/form', [
            'device' => $device,
            'action' => 'edit',
            'title' => 'Editar Dispositivo',
            'csrf_token' => Security::generateToken()
        ]);
    }

    /**
     * Actualizar dispositivo
     */
    public function update($id)
    {
        try {
            // Validar CSRF token
            AuthMiddleware::validateCSRF();

            // Validar que el dispositivo exista
            $device = $this->deviceModel->getById($id);
            if (!$device) {
                $this->setFlashMessage('Dispositivo no encontrado.', 'error');
                return $this->redirect('/panel/attendance/devices');
            }

            // Validar datos
            $this->validateDeviceData($_POST, $id);

            // Verificar que el código no exista (excepto el actual)
            if ($this->deviceModel->codeExists($_POST['device_code'], $id)) {
                $this->setFlashMessage('El código de dispositivo ya existe.', 'error');
                return $this->redirect('/panel/attendance/devices/' . $id . '/edit');
            }

            // Preparar datos
            $data = $this->prepareDeviceData($_POST);

            // Actualizar dispositivo
            $result = $this->deviceModel->update($id, $data);

            if ($result) {
                $this->setFlashMessage('Dispositivo actualizado exitosamente.', 'success');
                return $this->redirect('/panel/attendance/devices');
            } else {
                $this->setFlashMessage('Error al actualizar el dispositivo.', 'error');
                return $this->redirect('/panel/attendance/devices/' . $id . '/edit');
            }

        } catch (\Exception $e) {
            error_log("Error updating device: " . $e->getMessage());
            $this->setFlashMessage('Error: ' . $e->getMessage(), 'error');
            return $this->redirect('/panel/attendance/devices/' . $id . '/edit');
        }
    }

    /**
     * Eliminar dispositivo
     */
    public function delete($id)
    {
        try {
            // Validar CSRF token
            AuthMiddleware::validateCSRF();

            $device = $this->deviceModel->getById($id);

            if (!$device) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dispositivo no encontrado.'
                ]);
            }

            $result = $this->deviceModel->delete($id);

            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Dispositivo eliminado exitosamente.'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al eliminar el dispositivo.'
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error deleting device: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test de conexión API (AJAX)
     */
    public function testConnection($id)
    {
        try {
            // Validar CSRF token
            AuthMiddleware::validateCSRF();

            $device = $this->deviceModel->getById($id);

            if (!$device) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dispositivo no encontrado.'
                ]);
            }

            if ($device['device_type'] !== 'API') {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Este tipo de dispositivo no soporta test de conexión.'
                ]);
            }

            // Test básico de conexión
            $ch = curl_init($device['api_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            if ($device['api_key']) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $device['api_key'],
                    'Content-Type: application/json'
                ]);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error de conexión: ' . $error
                ]);
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Conexión exitosa (HTTP ' . $httpCode . ')',
                    'data' => [
                        'http_code' => $httpCode,
                        'response_length' => strlen($response)
                    ]
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error HTTP ' . $httpCode
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error testing connection: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cambiar estado activo/inactivo (AJAX)
     */
    public function toggle($id)
    {
        try {
            // Validar CSRF token
            AuthMiddleware::validateCSRF();

            $device = $this->deviceModel->getById($id);

            if (!$device) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Dispositivo no encontrado.'
                ]);
            }

            $result = $this->deviceModel->toggleStatus($id);

            if ($result) {
                $newStatus = $device['is_active'] ? 'inactivo' : 'activo';
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Dispositivo marcado como ' . $newStatus . '.',
                    'new_status' => !$device['is_active']
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error al cambiar el estado.'
                ]);
            }

        } catch (\Exception $e) {
            error_log("Error toggling device: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validar datos del dispositivo
     */
    private function validateDeviceData($data, $excludeId = null)
    {
        $errors = [];

        if (empty($data['device_code'])) {
            $errors[] = 'El código del dispositivo es requerido.';
        }

        if (empty($data['device_name'])) {
            $errors[] = 'El nombre del dispositivo es requerido.';
        }

        if (empty($data['device_type'])) {
            $errors[] = 'El tipo de dispositivo es requerido.';
        }

        // Validaciones específicas por tipo
        /*if ($data['device_type'] === 'API') {
            if (empty($data['api_url'])) {
                $errors[] = 'La URL del API es requerida para dispositivos tipo API.';
            }
        }*/

        if ($data['device_type'] === 'TEXT_FILE') {
            if (empty($data['file_format'])) {
                $errors[] = 'El formato de archivo es requerido para dispositivos tipo TEXT_FILE.';
            }
        }

        if (!empty($errors)) {
            throw new \Exception(implode(' ', $errors));
        }
    }

    /**
     * Preparar datos del dispositivo para insert/update
     */
    private function prepareDeviceData($post)
    {
        $data = [
            'device_code' => trim($post['device_code']),
            'device_name' => trim($post['device_name']),
            'device_type' => $post['device_type'],
            'location' => trim($post['location'] ?? ''),
            'is_active' => isset($post['is_active']) ? 1 : 0
        ];

        // Campos específicos para API
        if ($post['device_type'] === 'API') {
            $data['api_url'] = trim($post['api_url'] ?? '');
            $data['api_key'] = trim($post['api_key'] ?? '');
        }

        // Campos específicos para TEXT_FILE
        if ($post['device_type'] === 'TEXT_FILE') {
            $data['file_format'] = trim($post['file_format'] ?? 'CSV');
            $data['delimiter'] = trim($post['delimiter'] ?? ',');
            $data['date_format'] = trim($post['date_format'] ?? 'Y-m-d');
            $data['time_format'] = trim($post['time_format'] ?? 'H:i:s');

            // Column mapping (puede ser JSON)
            if (!empty($post['column_mapping'])) {
                $data['column_mapping'] = is_array($post['column_mapping'])
                    ? json_encode($post['column_mapping'])
                    : $post['column_mapping'];
            }
        }

        // Config JSON general (si existe)
        if (!empty($post['config_json'])) {
            $data['config_json'] = is_array($post['config_json'])
                ? json_encode($post['config_json'])
                : $post['config_json'];
        }

        return $data;
    }
}
