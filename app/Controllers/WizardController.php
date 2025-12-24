<?php

namespace App\Controllers;

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
use App\Core\TenantStorage;
use App\Models\WizardModel;
use App\Services\LicenseGenerator;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// Autoloaded via Composer (PSR-4): App\\Models\\WizardModel

class WizardController{
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
        //if ($this->wizardModel->hasConfiguredCompanies()) {
        //    $this->redirectToLogin();
        //    return;
        //}

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
            $_SESSION['wizard_distributor_username'] = $username;
            $_SESSION['wizard_distributor_phone'] = $distributorResult['user_contacto'];
            $_SESSION['wizard_distributor_name'] = $distributorResult['user_name'];
            $_SESSION['wizard_distributor_empresa_name'] = $distributorResult['empresa_name'];
            $_SESSION['wizard_distributor_empresa_ruc'] = $distributorResult['empresa_ruc'];
            $_SESSION['wizard_step'] = 2;
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Distribuidor encontrado',
                'email' => $distributorResult['email'],
                'username' => $username,
                'name' => $distributorResult['user_name'],
                'phone' => $distributorResult['user_contacto'],
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
            'admin_lastname' => $this->getPostData('admin_lastname'),
            'distribuitor_phone' => $this->getPostData('distribuitor_phone'),
            'distribuitor_name' => $this->getPostData('distribuitor_name'),
            'distribuitor_name' => $this->getPostData('distribuitor_name'),
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

        // NOTA: Validación de RUC único deshabilitada para permitir múltiples empresas por distribuidor
        // Un distribuidor puede crear tantas empresas como necesite sin validar si el RUC ya existe
        /*
        if ($this->wizardModel->rucExists($companyData['ruc'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El RUC ingresado ya está registrado en el sistema'
            ]);
            return;
        }
        */

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

        // ===== DEBUG: Variables de entorno críticas =====
        error_log("========== INICIO DEBUG CREATECOMPANY ==========");
        error_log("🔧 TENANT_DB_HOST: " . ($_ENV['TENANT_DB_HOST'] ?? 'NOT_SET'));
        error_log("🔧 TENANT_DB_PORT: " . ($_ENV['TENANT_DB_PORT'] ?? 'NOT_SET'));
        error_log("🔧 TENANT_DB_USER: " . ($_ENV['TENANT_DB_USER'] ?? 'NOT_SET'));
        error_log("🔧 TENANT_DB_PASS: " . (empty($_ENV['TENANT_DB_PASS']) ? 'EMPTY/NOT_SET' : '[SET - Length: ' . strlen($_ENV['TENANT_DB_PASS']) . ']'));
        error_log("🔧 TENANT_DB_CHARSET: " . ($_ENV['TENANT_DB_CHARSET'] ?? 'NOT_SET'));
        error_log("🔧 DB_HOST (master): " . ($_ENV['DB_HOST'] ?? 'NOT_SET'));
        error_log("🔧 DB_USER (master): " . ($_ENV['DB_USER'] ?? 'NOT_SET'));
        error_log("🔧 DB_PASS (master): " . (empty($_ENV['DB_PASS']) ? 'EMPTY/NOT_SET' : '[SET - Length: ' . strlen($_ENV['DB_PASS']) . ']'));
        error_log("🔧 DB_NAME (master): " . ($_ENV['DB_NAME'] ?? 'NOT_SET'));
        error_log("📋 Company Name: " . $companyData['company_name']);
        error_log("📋 RUC: " . $companyData['ruc']);
        error_log("================================================");

        try {
            // Iniciar transacción
            error_log("▶️ PASO 0: Iniciando transacción en BD master");
            $this->db->beginTransaction();
            error_log("✅ Transacción iniciada correctamente");

            // 1. GENERAR Y REGISTRAR LICENCIA EN SERVIDOR REMOTO (antes de crear BD)
            error_log("▶️ PASO 1: Generando licencia");
            error_log("📝 Datos licencia: RUC={$_SESSION['wizard_distributor_empresa_ruc']}, Company={$_SESSION['wizard_distributor_empresa_name']}");
            $licenseGenerator = new LicenseGenerator();
            $allowOffline = filter_var($_ENV['LICENSING_ALLOW_OFFLINE'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
            error_log("🔧 LICENSING_ALLOW_OFFLINE: " . ($allowOffline ? 'true' : 'false'));

            $licenseResult = $licenseGenerator->generateAndRegister([
                'ruc' => $_SESSION['wizard_distributor_empresa_ruc'],
                'company_name' => $_SESSION['wizard_distributor_empresa_name'],
                'final_user' => $companyData['company_name'],
                'buyer_name' => $companyData['distribuitor_name'] ,
                'email' => $companyData['admin_email'],
                'phone' => $companyData['distribuitor_phone'] ?? '',
                'country' => 'PA'
            ], $allowOffline);

            if (!$licenseResult['success']) {
                error_log("❌ ERROR generando licencia: " . $licenseResult['message']);
                throw new Exception('Error generando licencia: ' . $licenseResult['message']);
            }

            $license = $licenseResult['license'];
            error_log("✅ Licencia generada: {$license}");
            $companyData['license_key'] = $license;
            $companyData['license_expiration'] = $licenseResult['expiration_date'];
            $companyData['license_first_activation'] = $licenseResult['first_activation'];
            $companyData['license_sync_pending'] = $licenseResult['pending_sync'] ?? false;
            $companyData['license_offline_mode'] = $licenseResult['offline_mode'] ?? false;

            // Log de licencia generada
            if ($licenseResult['offline_mode']) {
                error_log("=== ⚠️ LICENCIA GENERADA EN MODO OFFLINE ===");
                error_log("Licencia: {$license}");
                error_log("Empresa: {$companyData['company_name']}");
                error_log("RUC: {$companyData['ruc']}");
                error_log("Expiración: {$licenseResult['expiration_date']}");
                error_log("Estado: PENDIENTE DE REGISTRO EN SERVIDOR REMOTO");
                error_log("Motivo: {$licenseResult['sync_error']}");
                error_log("============================================");
            } else {
                error_log("=== LICENCIA GENERADA Y REGISTRADA ===");
                error_log("Licencia: {$license}");
                error_log("Empresa: {$companyData['company_name']}");
                error_log("RUC: {$companyData['ruc']}");
                error_log("Expiración: {$licenseResult['expiration_date']}");
                error_log("Estado: REGISTRADA EN SERVIDOR REMOTO");
                error_log("======================================");
            }

            // 2. Crear empresa en BD master CON licencia
            error_log("▶️ PASO 2: Creando registro empresa en BD master");
            error_log("📝 Company: {$companyData['company_name']}, License: {$license}");
            try {
                $companyId = $this->wizardModel->createCompanyRecord($companyData);
                error_log("✅ Registro empresa creado - ID: {$companyId}");
            } catch (Exception $e) {
                error_log("❌ ERROR creando registro empresa en master: " . $e->getMessage());
                error_log("🔍 Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // 3. Generar nombre de BD basado en LICENCIA
            error_log("▶️ PASO 3: Generando nombre BD tenant basado en licencia");
            $databaseName = $this->wizardModel->generateTenantDatabaseName($license);
            error_log("✅ Nombre BD generado: {$databaseName}");

            // 4. Crear base de datos específica para la empresa
            error_log("▶️ PASO 4: Creando base de datos tenant");
            error_log("📝 DB Name: {$databaseName}");
            error_log("🔧 Connection params: host=" . ($_ENV['TENANT_DB_HOST'] ?? 'localhost') . ", user=" . ($_ENV['TENANT_DB_USER'] ?? 'root') . ", port=" . ($_ENV['TENANT_DB_PORT'] ?? '3306'));
            try {
                $this->wizardModel->createTenantDatabase($databaseName);
                error_log("✅ Base de datos tenant creada: {$databaseName}");
            } catch (Exception $e) {
                error_log("❌ ERROR creando BD tenant: " . $e->getMessage());
                error_log("🔍 Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // 5. Importar estructura completa en BD tenant
            error_log("▶️ PASO 5: Importando schema en BD tenant");
            try {
                $this->wizardModel->importTenantSchema($databaseName);
                error_log("✅ Schema importado exitosamente");
            } catch (Exception $e) {
                error_log("❌ ERROR importando schema: " . $e->getMessage());
                error_log("🔍 Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // 6. Configurar datos iniciales empresa en BD tenant
            error_log("▶️ PASO 6: Configurando datos iniciales empresa en tenant");
            try {
                $this->wizardModel->setupTenantCompanyData($databaseName, $companyData, $companyId);
                error_log("✅ Datos empresa configurados");
            } catch (Exception $e) {
                error_log("⚠️ WARNING configurando datos empresa: " . $e->getMessage());
                // No lanzar excepción - este paso es opcional
            }

            // 7. Crear usuario administrador en BD tenant
            error_log("▶️ PASO 7: Creando usuario admin en BD tenant");
            error_log("📝 Username: {$companyData['admin_username']}, Email: {$companyData['admin_email']}");
            try {
                $adminUserId = $this->wizardModel->createTenantAdminUser($databaseName, $companyData);
                error_log("✅ Usuario admin creado - ID: {$adminUserId}");
            } catch (Exception $e) {
                error_log("❌ ERROR creando usuario admin: " . $e->getMessage());
                error_log("🔍 Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // 8. Actualizar registro empresa con BD asignada
            error_log("▶️ PASO 8: Actualizando registro empresa con BD asignada");
            try {
                $this->wizardModel->updateCompanyDatabase($companyId, $databaseName);
                error_log("✅ Registro empresa actualizado con BD: {$databaseName}");
            } catch (Exception $e) {
                error_log("❌ ERROR actualizando registro empresa: " . $e->getMessage());
                error_log("🔍 Stack trace: " . $e->getTraceAsString());
                throw $e;
            }

            // 8.1 Crear storage aislado por tenant
            error_log("PASO 8.1: Creando storage aislado por tenant");
            try {
                TenantStorage::initializeTenantStorage($companyId, $databaseName);
                error_log("Storage aislado creado para tenant {$companyId}");
            } catch (Exception $e) {
                error_log("WARNING creando storage aislado: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                // No bloquear la creaciÃ³n del tenant si falla el storage
            }

            // Confirmar transacción
            error_log("▶️ PASO 9: Confirmando transacción");
            $this->db->commit();
            error_log("✅ Transacción confirmada exitosamente");

            // Limpiar datos de sesión
            error_log("▶️ PASO 10: Limpiando sesión wizard");
            $this->clearWizardSession();
            error_log("✅ Sesión limpiada");

            // Enviar email de bienvenida (opcional)
            error_log("▶️ PASO 11: Enviando email de bienvenida");
            $this->sendWelcomeEmail($companyData, $databaseName);
            error_log("✅ Email enviado (o error no crítico capturado)");

            // Preparar mensaje según modo de licencia
            $message = 'Empresa creada exitosamente';
            if ($licenseResult['offline_mode']) {
                $message .= ' (Licencia pendiente de registro en servidor remoto)';
            }

            error_log("========== ✅ CREATECOMPANY COMPLETADO EXITOSAMENTE ==========");
            error_log("🏢 Empresa: {$companyData['company_name']} (ID: {$companyId})");
            error_log("🔑 Licencia: {$license}");
            error_log("💾 Base de datos: {$databaseName}");
            error_log("👤 Admin ID: {$adminUserId}");
            error_log("==============================================================");

            $this->jsonResponse([
                'success' => true,
                'message' => $message,
                'company_id' => $companyId,
                'license_key' => $license,
                'license_expiration' => $licenseResult['expiration_date'],
                'license_offline_mode' => $licenseResult['offline_mode'],
                'license_sync_pending' => $licenseResult['pending_sync'] ?? false,
                'database_name' => $databaseName,
                'admin_user_id' => $adminUserId,
                'login_url' => $this->getCompanyLoginUrl($license),
                'next_action' => 'redirect_to_login',
                'warning' => $licenseResult['offline_mode'] ? 'La licencia se registrará en el servidor cuando esté disponible' : null
            ]);

        } catch (Exception $e) {
            // Rollback en caso de error
            error_log("========== ❌ ERROR CRÍTICO EN CREATECOMPANY ==========");
            error_log("💥 Exception Type: " . get_class($e));
            error_log("📝 Error Message: " . $e->getMessage());
            error_log("📄 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
            error_log("🔍 Stack Trace:");
            error_log($e->getTraceAsString());

            // Intentar rollback
            try {
                $this->db->rollback();
                error_log("↩️ Rollback ejecutado correctamente");
            } catch (Exception $rollbackEx) {
                error_log("❌ ERROR en rollback: " . $rollbackEx->getMessage());
            }

            error_log("======================================================");

            // Respuesta al cliente con información detallada (solo en desarrollo)
            $errorMessage = 'Error creando la empresa: ' . $e->getMessage();
            if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
                $errorMessage .= ' | Archivo: ' . basename($e->getFile()) . ':' . $e->getLine();
            }

            $this->jsonResponse([
                'success' => false,
                'message' => $errorMessage,
                'error_type' => get_class($e),
                'debug' => isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true'
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
        // Validación RUC panameño
        // Formatos aceptados:
        // - Persona Natural: X-XXX-XXXX (8 dígitos con guiones)
        // - Persona Jurídica: XXXXXX-X-XXXX (hasta 14 caracteres)
        // - RUC simplificado: solo dígitos sin guiones (8-14 dígitos)

        // Remover espacios
        $ruc = trim($ruc);

        // Validar que no esté vacío
        if (empty($ruc)) {
            return false;
        }

        // Aceptar RUC con guiones (formato estándar panameño)
        if (preg_match('/^[0-9]{1,8}-[0-9]{1,5}-[0-9]{1,6}$/', $ruc)) {
            return true;
        }

        // Aceptar RUC sin guiones (solo dígitos, 8-14 caracteres)
        if (preg_match('/^[0-9]{8,14}$/', $ruc)) {
            return true;
        }

        // Aceptar formato NIT/DV (con letras al final)
        if (preg_match('/^[0-9]{8,14}[A-Z]{0,2}$/', $ruc)) {
            return true;
        }

        return false;
    }


    private function getCompanyLoginUrl($license) {
        // Retornar URL con instrucciones para usar licencia en formulario
        return '/panel/login'; // Usuario ingresará licencia en el campo "Código de Empresa"
    }

    private function sendWelcomeEmail($companyData, $databaseName) {
        try {
            // IMPORTANTE: Email de bienvenida usa SOLO configuración .env
            // (No usar BD porque la empresa se está creando y aún no tiene configuración)
            $mailConfig = [
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
                'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@planillas.com',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Planillas'
            ];

            // Configurar PHPMailer
            $mail = new PHPMailer(true);

            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];

            // Autenticación solo si hay username configurado
            if (!empty($mailConfig['username'])) {
                $mail->SMTPAuth   = true;
                $mail->Username   = $mailConfig['username'];
                $mail->Password   = $mailConfig['password'];
            } else {
                $mail->SMTPAuth   = false;
            }

            // Encriptación solo si está configurada explícitamente
            if (!empty($mailConfig['encryption']) && strtolower($mailConfig['encryption']) !== 'optional') {
                $mail->SMTPSecure = $mailConfig['encryption'];
            }

            $mail->Port       = $mailConfig['port'];
            $mail->CharSet    = 'UTF-8';

            // Debug mode (solo en desarrollo)
            if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
                $mail->SMTPDebug = 0; // 0 = sin debug, 2 = debug completo
            }

            // Remitente
            $mail->setFrom(
                $mailConfig['from_address'],
                $mailConfig['from_name']
            );

            // Destinatario
            $mail->addAddress($companyData['admin_email'], $companyData['admin_firstname'] . ' ' . $companyData['admin_lastname']);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = '¡Bienvenido al Sistema de Planillas! - ' . $companyData['company_name'];

            // URL de login
            $loginUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $loginUrl .= "://" . $_SERVER['HTTP_HOST'] . "/panel/login";

            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 10px; background-color: #ffffff; }
                        .header { background: linear-gradient(135deg, #FF5722 0%, #FF9800 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { padding: 30px; }
                        .info-box { background-color: #f8f9fa; border-left: 4px solid #FF5722; padding: 15px; margin: 20px 0; border-radius: 4px; }
                        .button { display: inline-block; padding: 12px 24px; background-color: #FF5722; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
                        .label { font-weight: bold; color: #555; }
                        .value { font-family: monospace; color: #d84315; font-size: 1.1em; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1 style='margin:0;'>¡Bienvenido a Planilla Innova!</h1>
                            <p style='margin:10px 0 0; opacity:0.9;'>Su empresa ha sido configurada exitosamente</p>
                        </div>
                        <div class='content'>
                            <p>Estimado/a <strong>{$companyData['admin_firstname']} {$companyData['admin_lastname']}</strong>,</p>
                            
                            <p>Nos complace informarle que la configuración de <strong>{$companyData['company_name']}</strong> en nuestro Sistema de Planillas se ha completado correctamente.</p>
                            
                            <div class='info-box'>
                                <h3 style='margin-top:0; color:#FF5722;'>Credenciales de Acceso</h3>
                                <p><span class='label'>Licencia:</span><br><span class='value'>{$companyData['license_key']}</span></p>
                                <p><span class='label'>Vence:</span><br><span class='value'>" . date('d/m/Y', strtotime($companyData['license_expiration'] ?? '+30 days')) . "</span></p>
                                <p><span class='label'>Base de Datos:</span><br><span class='value'>{$databaseName}</span></p>
                                <p><span class='label'>Usuario Administrador:</span><br><span class='value'>{$companyData['admin_username']}</span></p>
                            </div>

                            <p>Ya puede acceder al sistema y comenzar a gestionar su planilla.</p>
                            
                            <div style='text-align: center;'>
                                <a href='{$loginUrl}' class='button'>Ingresar al Sistema</a>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                            <p>&copy; " . date('Y') . " Sistema de Planillas. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $expirationFormatted = date('d/m/Y', strtotime($companyData['license_expiration'] ?? '+30 days'));
            $mail->AltBody = "¡Bienvenido a Bordo!\n\n"
                . "Estimado/a {$companyData['admin_firstname']} {$companyData['admin_lastname']},\n\n"
                . "La configuración de {$companyData['company_name']} se ha completado correctamente.\n\n"
                . "CREDENCIALES DE ACCESO:\n"
                . "------------------------\n"
                . "Licencia: {$companyData['license_key']}\n"
                . "Vence: {$expirationFormatted}\n"
                . "Base de Datos: {$databaseName}\n"
                . "Usuario Administrador: {$companyData['admin_username']}\n\n"
                . "Ingrese al sistema aquí: {$loginUrl}";

            $mail->send();

            // Log exitoso con detalles
            error_log("=== EMAIL ENVIADO EXITOSAMENTE ===");
            error_log("Destinatario: " . $companyData['admin_email']);
            error_log("Empresa: " . $companyData['company_name']);
            error_log("Licencia: " . $companyData['license_key']);
            error_log("Servidor SMTP: " . $mail->Host . ":" . $mail->Port);
            error_log("==================================");

        } catch (Exception $e) {
            // Log detallado del error
            error_log("=== ERROR ENVIANDO EMAIL DE BIENVENIDA ===");
            error_log("Destinatario: " . ($companyData['admin_email'] ?? 'N/A'));
            error_log("Empresa: " . ($companyData['company_name'] ?? 'N/A'));
            error_log("Error PHPMailer: " . $mail->ErrorInfo);
            error_log("Exception: " . $e->getMessage());
            error_log("Servidor SMTP: " . ($mailConfig['host'] ?? 'N/A') . ":" . ($mailConfig['port'] ?? 'N/A'));
            error_log("Config Source: .env (welcome emails always use environment variables)");
            error_log("==========================================");

            // No lanzamos la excepción para no interrumpir el flujo de creación de empresa
            // El usuario ya vio el mensaje de éxito en pantalla
        }
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
        $this->redirect('/crear-empresa');
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
