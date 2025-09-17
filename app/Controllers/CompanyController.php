<?php

namespace App\Controllers;

use App\Core\Controller;

class CompanyController extends Controller
{
    private $companyModel;

    public function __construct()
    {
        parent::__construct();
        $this->companyModel = $this->model('Company');
    }

    /**
     * Mostrar formulario de configuración de empresa
     */
    public function index()
    {
        try {
            $this->requireAuth();
            
            // Obtener datos actuales de la empresa
            $company = $this->companyModel->getCompanyConfig();
            $stats = $this->companyModel->getConfigStats();
            
            // Lista de monedas disponibles
            $currencies = [
                ['code' => 'GTQ', 'name' => 'Quetzal Guatemalteco', 'symbol' => 'Q'],
                ['code' => 'USD', 'name' => 'Dólar Estadounidense', 'symbol' => '$'],
                ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                ['code' => 'MXN', 'name' => 'Peso Mexicano', 'symbol' => '$'],
                ['code' => 'HNL', 'name' => 'Lempira Hondureña', 'symbol' => 'L'],
                ['code' => 'CRC', 'name' => 'Colón Costarricense', 'symbol' => '₡'],
                ['code' => 'PAB', 'name' => 'Balboa Panameña', 'symbol' => 'B/.']
            ];
            
            $data = [
                'company' => $company,
                'stats' => $stats,
                'currencies' => $currencies
            ];
            
            $this->render('admin/company/index', $data);
            
        } catch (\Exception $e) {
            error_log("Error en CompanyController@index: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la configuración de empresa';
            $this->redirect('/panel/dashboard');
        }
    }

    /**
     * Guardar configuración de empresa
     */
    public function store()
    {
        try {
            $this->requireAuth();
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $companyData = [
                'company_name' => $_POST['company_name'] ?? '',
                'ruc' => $_POST['ruc'] ?? '',
                'legal_representative' => $_POST['legal_representative'] ?? '',
                'address' => $_POST['address'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'email' => $_POST['email'] ?? '',
                'currency_symbol' => $_POST['currency_symbol'] ?? 'Q',
                'currency_code' => $_POST['currency_code'] ?? 'GTQ',
                'tipo_institucion' => $_POST['tipo_institucion'] ?? 'privada',
                'jefe_recursos_humanos' => $_POST['jefe_recursos_humanos'] ?? '',
                'cargo_jefe_rrhh' => $_POST['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                'elaborado_por' => $_POST['elaborado_por'] ?? '',
                'cargo_elaborador' => $_POST['cargo_elaborador'] ?? 'Especialista en Nóminas',
                'logo_empresa' => $_POST['logo_empresa'] ?? '',
                'logo_izquierdo_reportes' => $_POST['logo_izquierdo_reportes'] ?? '',
                'logo_derecho_reportes' => $_POST['logo_derecho_reportes'] ?? ''
            ];

            $result = $this->companyModel->saveCompanyConfig($companyData);

            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }

            $this->redirect('/panel/company');

        } catch (\Exception $e) {
            error_log("Error en CompanyController@store: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar la configuración de empresa';
            $this->redirect('/panel/company');
        }
    }

    /**
     * API: Obtener configuración actual (AJAX)
     */
    public function getConfig()
    {
        try {
            $this->requireAuth();
            
            $company = $this->companyModel->getCompanyConfig();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $company
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener configuración'
            ]);
        }
        exit;
    }

    /**
     * API: Obtener estadísticas de configuración (AJAX)
     */
    public function getStats()
    {
        try {
            $this->requireAuth();
            
            $stats = $this->companyModel->getConfigStats();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ]);
        }
        exit;
    }

    /**
     * Subir logo de empresa (AJAX)
     */
    public function uploadLogo()
    {
        try {
            $this->requireAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || !\App\Core\Security::validateToken($_POST['csrf_token'])) {
                throw new \Exception('Token de seguridad inválido');
            }

            if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                throw new \Exception('No se recibió archivo o error en subida');
            }

            $fieldName = $_POST['field_name'] ?? '';
            if (!in_array($fieldName, ['logo_empresa', 'logo_izquierdo_reportes', 'logo_derecho_reportes'])) {
                throw new \Exception('Campo de logo no válido');
            }

            $file = $_FILES['logo'];
            $result = $this->processLogoUpload($file, $fieldName);

            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (\Exception $e) {
            error_log("Error en uploadLogo: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Eliminar logo de empresa (AJAX)
     */
    public function deleteLogo()
    {
        try {
            $this->requireAuth();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar CSRF token
            if (!isset($_POST['csrf_token']) || !\App\Core\Security::validateToken($_POST['csrf_token'])) {
                throw new \Exception('Token de seguridad inválido');
            }

            $fieldName = $_POST['field_name'] ?? '';
            $filename = $_POST['filename'] ?? '';

            if (!$fieldName || !$filename) {
                throw new \Exception('Parámetros requeridos faltantes');
            }

            if (!in_array($fieldName, ['logo_empresa', 'logo_izquierdo_reportes', 'logo_derecho_reportes'])) {
                throw new \Exception('Campo de logo no válido');
            }

            // Eliminar archivo del servidor
            $logoPath = __DIR__ . '/../../images/logos/' . $filename;
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Logo eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            error_log("Error en deleteLogo: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Procesar subida de logo
     */
    private function processLogoUpload($file, $fieldName)
    {
        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new \Exception('Tipo de archivo no permitido. Solo JPG, PNG, SVG');
        }

        // Validar tamaño (2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new \Exception('El archivo es muy grande. Máximo 2MB');
        }

        // Crear directorio si no existe
        $logoDir = __DIR__ . '/../../images/logos/';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $fieldName . '_' . time() . '.' . $extension;
        $targetPath = $logoDir . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \Exception('Error al guardar el archivo');
        }

        return [
            'success' => true,
            'filename' => $filename,
            'message' => 'Logo subido correctamente'
        ];
    }

    /**
     * Verificar autenticación
     */
    private function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
    }
}