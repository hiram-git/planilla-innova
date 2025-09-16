<?php

/**
 * WizardController - Sistema Multitenancy
 * Controlador para wizard de creación de empresas y bases de datos
 * 
 * Funcionalidades:
 * - Wizard 3 pasos sin autenticación previa
 * - Creación automática BD por empresa
 * - Setup inicial empresa + usuario admin
 * - Base para arquitectura multitenancy
 */

use App\Core\Database;

require_once dirname(__DIR__) . '/Models/WizardModel.php';

class WizardController {
    private $wizardModel;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->wizardModel = new WizardModel();
    }

    /**
     * Mostrar wizard inicial AdminLTE (sin autenticación)
     * URL: /setup/wizard
     */
    public function index() {
        // Verificar si ya hay empresas configuradas
        if ($this->wizardModel->hasConfiguredCompanies()) {
            $this->redirectToLogin();
            return;
        }

        $this->renderWizardView(false); // AdminLTE version
    }

    /**
     * Mostrar wizard Vuetify moderno (sin autenticación)
     * URL: /crear-empresa
     */
    public function crearEmpresa() {
        // Verificar si ya hay empresas configuradas
        if ($this->wizardModel->hasConfiguredCompanies()) {
            $this->redirectToLogin();
            return;
        }

        $this->renderVuetifyWizard();
    }

    /**
     * STEP 1: Validar distribuidor con servidor remoto
     * POST /setup/wizard/validate-distributor
     */
    public function validateDistributor() {
        $this->validateAjaxRequest();
        
        $username = $this->getPostData('distributor_username');
        $password = $this->getPostData('distributor_password');
        
        // Validar distribuidor con servidor remoto
        $distributorResult = $this->wizardModel->validateRemoteDistributor($username, $password);
        
        if ($distributorResult['success']) {
            $_SESSION['wizard_distributor_validated'] = true;
            $_SESSION['wizard_distributor_email'] = $distributorResult['email'];
            $_SESSION['wizard_step'] = 2;
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Distribuidor encontrado',
                'email' => $distributorResult['email'],
                'next_step' => 2
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $distributorResult['message'] ?? 'Credenciales de distribuidor inválidas'
            ]);
        }
    }

    /**
     * STEP 1 (Legacy): Validar credenciales administrador plataforma (mantener compatibilidad)
     * POST /setup/wizard/validate-admin
     */
    public function validateAdmin() {
        // Redirect to distributor validation for legacy compatibility
        $this->validateDistributor();
    }

    /**
     * STEP 2: Registrar datos de empresa y usuario admin
     * POST /setup/wizard/register-company
     */
    public function registerCompany() {
        $this->validateAjaxRequest();
        $this->validateWizardSession();
        
        $companyData = [
            'company_name' => $this->getPostData('company_name'),
            'ruc' => $this->getPostData('company_ruc'),
            'admin_username' => $this->getPostData('admin_username'),
            'admin_email' => $this->getPostData('admin_email'),
            'admin_password' => $this->getPostData('admin_password'),
            'admin_firstname' => $this->getPostData('admin_firstname'),
            'admin_lastname' => $this->getPostData('admin_lastname')
        ];

        // Validar datos
        $validation = $this->validateCompanyData($companyData);
        if (!$validation['valid']) {
            $this->jsonResponse([
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors'] ?? []
            ]);
            return;
        }

        // Verificar RUC único
        if ($this->wizardModel->rucExists($companyData['ruc'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El RUC ingresado ya está registrado en el sistema'
            ]);
            return;
        }

        // Guardar datos en sesión para paso 3
        $_SESSION['wizard_company_data'] = $companyData;
        $_SESSION['wizard_step'] = 3;

        $this->jsonResponse([
            'success' => true,
            'message' => 'Datos registrados correctamente',
            'next_step' => 3,
            'company_data' => [
                'company_name' => $companyData['company_name'],
                'ruc' => $companyData['ruc'],
                'admin_name' => $companyData['admin_firstname'] . ' ' . $companyData['admin_lastname'],
                'admin_email' => $companyData['admin_email']
            ]
        ]);
    }

    /**
     * STEP 3: Crear empresa, base datos y configuración inicial
     * POST /setup/wizard/create-company
     */
    public function createCompany() {
        $this->validateAjaxRequest();
        $this->validateWizardSession();

        if (!isset($_SESSION['wizard_company_data'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Datos de empresa no encontrados. Reinicie el proceso.'
            ]);
            return;
        }

        $companyData = $_SESSION['wizard_company_data'];

        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            // 1. Crear empresa en BD master
            $companyId = $this->wizardModel->createCompanyRecord($companyData);

            // 2. Crear base de datos específica para la empresa
            $databaseName = $this->wizardModel->generateTenantDatabaseName($companyData['ruc']);
            $this->wizardModel->createTenantDatabase($databaseName);

            // 3. Importar estructura completa en BD tenant
            $this->wizardModel->importTenantSchema($databaseName);

            // 4. Configurar datos iniciales empresa en BD tenant
            $this->wizardModel->setupTenantCompanyData($databaseName, $companyData, $companyId);

            // 5. Crear usuario administrador en BD tenant
            $adminUserId = $this->wizardModel->createTenantAdminUser($databaseName, $companyData);

            // 6. Generar y validar licencia remota
            $licenseResult = $this->wizardModel->generateAndValidateLicense($companyId, $companyData);
            if (!$licenseResult['success']) {
                throw new Exception('Error en validación de licencia: ' . $licenseResult['message']);
            }

            // 7. Actualizar registro empresa con BD asignada
            $this->wizardModel->updateCompanyDatabase($companyId, $databaseName);

            // Confirmar transacción
            $this->db->commit();

            // Limpiar datos de sesión
            $this->clearWizardSession();

            // Enviar email de bienvenida (opcional)
            $this->sendWelcomeEmail($companyData, $databaseName);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Empresa creada exitosamente',
                'company_id' => $companyId,
                'database_name' => $databaseName,
                'admin_user_id' => $adminUserId,
                'login_url' => $this->getCompanyLoginUrl($databaseName),
                'next_action' => 'redirect_to_login'
            ]);

        } catch (Exception $e) {
            // Rollback en caso de error
            $this->db->rollback();
            
            error_log("Error creando empresa multitenancy: " . $e->getMessage());
            
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error creando la empresa: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener progreso actual del wizard
     * GET /setup/wizard/progress
     */
    public function getProgress() {
        $this->validateAjaxRequest();
        
        $step = $_SESSION['wizard_step'] ?? 1;
        $adminValidated = $_SESSION['wizard_admin_validated'] ?? false;
        $companyData = $_SESSION['wizard_company_data'] ?? null;

        $this->jsonResponse([
            'current_step' => $step,
            'admin_validated' => $adminValidated,
            'has_company_data' => !is_null($companyData),
            'steps_completed' => $step - 1
        ]);
    }

    /**
     * Reiniciar wizard (limpiar sesión)
     * POST /setup/wizard/reset
     */
    public function resetWizard() {
        $this->clearWizardSession();
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Wizard reiniciado'
        ]);
    }

    // ===== MÉTODOS PRIVADOS =====

    private function renderWizardView($useVuetify = false) {
        $pageTitle = 'Setup Inicial - Sistema de Planillas';
        $currentStep = $_SESSION['wizard_step'] ?? 1;
        
        // Cargar vista wizard AdminLTE sin layout de autenticación
        include dirname(__DIR__) . '/Views/wizard/setup.php';
    }

    private function renderVuetifyWizard() {
        $pageTitle = 'Crear Empresa - Sistema de Planillas';
        $currentStep = $_SESSION['wizard_step'] ?? 1;
        
        // Cargar vista Vuetify wizard sin layout de autenticación
        include dirname(__DIR__) . '/Views/wizard/crear_empresa.php';
    }

    private function validateWizardSession() {
        if (!isset($_SESSION['wizard_distributor_validated']) || !$_SESSION['wizard_distributor_validated']) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Sesión de distribuidor inválida. Reinicie el proceso.',
                'redirect' => '/setup/wizard'
            ]);
            exit;
        }
    }

    private function validateCompanyData($data) {
        $errors = [];
        
        // Validar nombre empresa
        if (empty($data['company_name']) || strlen($data['company_name']) < 3) {
            $errors['company_name'] = 'El nombre de la empresa debe tener al menos 3 caracteres';
        }

        // Validar RUC (formato guatemalteco)
        if (empty($data['ruc']) || !$this->validateGuatemalanRUC($data['ruc'])) {
            $errors['ruc'] = 'El RUC debe tener un formato válido';
        }

        // Validar datos administrador
        if (empty($data['admin_username']) || strlen($data['admin_username']) < 3) {
            $errors['admin_username'] = 'El nombre de usuario debe tener al menos 3 caracteres';
        }

        if (empty($data['admin_email']) || !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'El email debe tener un formato válido';
        }

        if (empty($data['admin_password']) || strlen($data['admin_password']) < 6) {
            $errors['admin_password'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        if (empty($data['admin_firstname']) || strlen($data['admin_firstname']) < 2) {
            $errors['admin_firstname'] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (empty($data['admin_lastname']) || strlen($data['admin_lastname']) < 2) {
            $errors['admin_lastname'] = 'El apellido debe tener al menos 2 caracteres';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Datos válidos' : 'Errores en los datos ingresados',
            'errors' => $errors
        ];
    }

    private function validateGuatemalanRUC($ruc) {
        // Validación básica RUC guatemalteco (8-12 dígitos)
        return preg_match('/^[0-9]{8,12}$/', $ruc);
    }


    private function getCompanyLoginUrl($databaseName) {
        // En futuro: subdomain o tenant parameter
        return '/panel/login?tenant=' . $databaseName;
    }

    private function sendWelcomeEmail($companyData, $databaseName) {
        // TODO: Implementar envío email bienvenida
        // Usar PHPMailer o similar
        error_log("Email bienvenida pendiente para: " . $companyData['admin_email']);
    }

    private function clearWizardSession() {
        unset($_SESSION['wizard_step']);
        unset($_SESSION['wizard_distributor_validated']);
        unset($_SESSION['wizard_distributor_email']);
        unset($_SESSION['wizard_company_data']);
        // Legacy compatibility
        unset($_SESSION['wizard_admin_validated']);
    }

    private function redirectToLogin() {
        header('Location: /panel/login');
        exit;
    }

    private function validateAjaxRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(400);
            exit('Invalid request');
        }
    }

    private function getPostData($key) {
        return trim($_POST[$key] ?? '');
    }

    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}