<?php

/**
 * WizardModel - Sistema Multitenancy
 * Modelo para gestión de wizard de empresas y creación de bases de datos
 * 
 * Responsabilidades:
 * - Validación credenciales administrador plataforma
 * - Gestión empresas en BD master
 * - Creación automática bases de datos tenant
 * - Setup inicial datos empresa y usuarios
 */

use App\Core\Database;

class WizardModel {
    private $db;
    private $masterConnection;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->masterConnection = $this->db->getConnection();
    }

    /**
     * Verificar si ya existen empresas configuradas
     */
    public function hasConfiguredCompanies() {
        try {
            // Verificar si existe tabla companies y tiene registros
            $stmt = $this->masterConnection->query("SHOW TABLES LIKE 'multitenancy_companies'");
            if ($stmt->rowCount() === 0) {
                return false; // Tabla no existe, primera vez
            }

            $stmt = $this->masterConnection->query("SELECT COUNT(*) FROM multitenancy_companies WHERE status = 'active'");
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking configured companies: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar distribuidor con servidor remoto
     */
    public function validateRemoteDistributor($username, $password) {
        try {
            $curl = curl_init();
            
            $postData = [
                'LoginUser' => 'yes',
                'usuario' => $username,
                'password' => $password
            ];
            print_r($postData);exit;

            curl_setopt_array($curl, [
                CURLOPT_URL => $_ENV['LICENSE_VALIDATION_URL'] ?? 'https://web.innovasoftlatam.com:8443/ajax/validar_login.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postData),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/json',
                ),
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $response = curl_exec($curl);
                        print_r($response);exit;

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($response === false || !empty($curlError)) {
                error_log("cURL error validating distributor: " . $curlError);
                return [
                    'success' => false,
                    'message' => 'Error de conexión con el servidor de licencias'
                ];
            }

            $data = json_decode($response, true);
            
            if ($data && isset($data['success']) && $data['success']) {
                return [
                    'success' => true,
                    'email' => $data['email'] ?? '',
                    'message' => 'Distribuidor encontrado'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Credenciales de distribuidor inválidas'
                ];
            }

        } catch (Exception $e) {
            error_log("Error validating remote distributor: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno validando distribuidor'
            ];
        }
    }

    /**
     * Validar credenciales administrador plataforma (mantener compatibilidad)
     */
    public function validatePlatformAdmin($username, $password) {
        // Redirect to remote distributor validation
        $result = $this->validateRemoteDistributor($username, $password);
        return $result['success'];
    }

    /**
     * Verificar si RUC ya existe
     */
    public function rucExists($ruc) {
        try {
            $this->ensureMultitenancyTables();
            
            $stmt = $this->masterConnection->prepare("SELECT id FROM multitenancy_companies WHERE ruc = ? AND status != 'deleted'");
            $stmt->execute([$ruc]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking RUC existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear registro de empresa en BD master
     */
    public function createCompanyRecord($companyData) {
        try {
            $this->ensureMultitenancyTables();

            $stmt = $this->masterConnection->prepare("
                INSERT INTO multitenancy_companies (
                    company_name, ruc, admin_email, tenant_database, 
                    status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
            ");

            $stmt->execute([
                $companyData['company_name'],
                $companyData['ruc'],
                $companyData['admin_email'],
                null // Se actualizará después de crear BD
            ]);

            return $this->masterConnection->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating company record: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear base de datos para tenant
     */
    public function createTenantDatabase($databaseName) {
        try {
            // Validar nombre de BD
            if (!$this->validateDatabaseName($databaseName)) {
                throw new Exception("Nombre de base de datos inválido: $databaseName");
            }

            // Crear base de datos
            $sql = "CREATE DATABASE IF NOT EXISTS `$databaseName` 
                   CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
            
            $this->masterConnection->exec($sql);
            
            error_log("Base de datos creada: $databaseName");
        } catch (Exception $e) {
            error_log("Error creating tenant database: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Importar estructura completa en BD tenant
     */
    public function importTenantSchema($databaseName) {
        try {
            // Obtener estructura desde archivo SQL
            $schemaFile = dirname(__DIR__, 2) . '/database/tenant_schema.sql';
            
            if (!file_exists($schemaFile)) {
                // Crear esquema básico si no existe archivo
                $this->createBasicTenantSchema($databaseName);
            } else {
                $this->importSchemaFromFile($databaseName, $schemaFile);
            }

            error_log("Esquema importado para tenant: $databaseName");
        } catch (Exception $e) {
            error_log("Error importing tenant schema: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Configurar datos iniciales empresa en BD tenant
     */
    public function setupTenantCompanyData($databaseName, $companyData, $companyId) {
        try {
            $tenantConnection = $this->getTenantConnection($databaseName);

            // Insertar datos empresa
            $stmt = $tenantConnection->prepare("
                INSERT INTO companies (
                    id, nombre_empresa, ruc, telefono, direccion, 
                    email, tipo_institucion, status, created_at, updated_at
                ) VALUES (?, ?, ?, '', '', ?, 'privada', 'active', NOW(), NOW())
            ");

            $stmt->execute([
                $companyId,
                $companyData['company_name'],
                $companyData['ruc'],
                $companyData['admin_email']
            ]);

            // Configurar datos iniciales (monedas, tipos planilla, etc.)
            $this->setupInitialTenantData($tenantConnection);

            error_log("Datos empresa configurados en tenant: $databaseName");
        } catch (Exception $e) {
            error_log("Error setting up tenant company data: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crear usuario administrador en BD tenant
     */
    public function createTenantAdminUser($databaseName, $companyData) {
        try {
            $tenantConnection = $this->getTenantConnection($databaseName);

            // Crear rol administrador si no existe
            $stmt = $tenantConnection->prepare("
                INSERT IGNORE INTO roles (name, description, permissions, status, created_at, updated_at) 
                VALUES ('Super Administrador', 'Administrador con permisos completos', '[]', 'active', NOW(), NOW())
            ");
            $stmt->execute();
            
            $roleId = $tenantConnection->lastInsertId() ?: 1;

            // Crear usuario administrador
            $hashedPassword = password_hash($companyData['admin_password'], PASSWORD_DEFAULT);
            
            $stmt = $tenantConnection->prepare("
                INSERT INTO users (
                    username, email, password, firstname, lastname, 
                    role_id, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");

            $stmt->execute([
                $companyData['admin_username'],
                $companyData['admin_email'],
                $hashedPassword,
                $companyData['admin_firstname'],
                $companyData['admin_lastname'],
                $roleId
            ]);

            $userId = $tenantConnection->lastInsertId();
            
            error_log("Usuario admin creado en tenant: $databaseName, ID: $userId");
            return $userId;
        } catch (Exception $e) {
            error_log("Error creating tenant admin user: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar empresa con BD asignada
     */
    public function updateCompanyDatabase($companyId, $databaseName) {
        try {
            $stmt = $this->masterConnection->prepare("
                UPDATE multitenancy_companies 
                SET tenant_database = ?, status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");

            $stmt->execute([$databaseName, $companyId]);
            
            error_log("Empresa actualizada con BD: $companyId -> $databaseName");
        } catch (Exception $e) {
            error_log("Error updating company database: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar y validar licencia con servidor remoto
     */
    public function generateAndValidateLicense($companyId, $companyData) {
        try {
            // Generar licencia única para la empresa
            $licenseKey = $this->generateLicenseKey($companyId, $companyData);
            
            // Guardar licencia en la tabla de la empresa
            $this->storeLicenseInTenant($companyData['ruc'], $licenseKey);
            
            // Validar licencia con servidor remoto
            $validationResult = $this->validateLicenseRemotely($licenseKey);
            
            if ($validationResult['success']) {
                return [
                    'success' => true,
                    'license' => $licenseKey,
                    'message' => 'Licencia generada y validada correctamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $validationResult['message'] ?? 'Error validando licencia'
                ];
            }

        } catch (Exception $e) {
            error_log("Error generating/validating license: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno procesando licencia'
            ];
        }
    }

    /**
     * Validar licencia con servidor remoto
     */
    private function validateLicenseRemotely($licenseKey) {
        try {
            $curl = curl_init();
            
            $postData = json_encode([
                'searchLicense' => 'yes',
                'License' => $licenseKey
            ]);

            curl_setopt_array($curl, [
                CURLOPT_URL => $_ENV['LICENSE_VALIDATION_URL'] ?? 'https://plataforma.innovasoftlatam.com:8080/ajax/license.php',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);

            if ($response === false || !empty($curlError)) {
                error_log("cURL error validating license: " . $curlError);
                return [
                    'success' => false,
                    'message' => 'Error de conexión con servidor de licencias'
                ];
            }

            $data = json_decode($response, true);
            
            if ($data && isset($data['success']) && $data['success'] == "1") {
                return [
                    'success' => true,
                    'message' => 'Licencia válida'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Licencia inválida'
                ];
            }

        } catch (Exception $e) {
            error_log("Error validating license remotely: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno validando licencia'
            ];
        }
    }

    // ===== MÉTODOS PRIVADOS =====

    /**
     * Asegurar que existan tablas multitenancy
     */
    private function ensureMultitenancyTables() {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS multitenancy_companies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    company_name VARCHAR(255) NOT NULL,
                    ruc VARCHAR(20) UNIQUE NOT NULL,
                    admin_email VARCHAR(255) NOT NULL,
                    tenant_database VARCHAR(100) NULL,
                    status ENUM('pending', 'active', 'inactive', 'deleted') DEFAULT 'pending',
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    INDEX idx_ruc (ruc),
                    INDEX idx_status (status),
                    INDEX idx_tenant_db (tenant_database)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ";
            
            $this->masterConnection->exec($sql);
        } catch (Exception $e) {
            error_log("Error ensuring multitenancy tables: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validar nombre de base de datos
     */
    private function validateDatabaseName($databaseName) {
        // Solo letras, números y guión bajo, máximo 64 caracteres
        return preg_match('/^[a-zA-Z0-9_]{1,64}$/', $databaseName);
    }

    /**
     * Crear esquema básico para tenant
     */
    private function createBasicTenantSchema($databaseName) {
        $tenantConnection = $this->getTenantConnection($databaseName);
        
        // Esquema básico - copiar desde BD actual
        $tables = [
            'companies', 'users', 'roles', 'employees', 'concepts', 
            'creditors', 'deductions', 'payrolls', 'payroll_details',
            'tipos_planilla', 'frecuencias', 'situaciones', 'cargos',
            'partidas', 'funciones', 'horarios', 'positions'
        ];

        foreach ($tables as $table) {
            $this->copyTableStructure($table, $databaseName, $tenantConnection);
        }
    }

    /**
     * Copiar estructura de tabla
     */
    private function copyTableStructure($tableName, $targetDatabase, $tenantConnection) {
        try {
            // Obtener CREATE TABLE de la BD actual
            $stmt = $this->masterConnection->query("SHOW CREATE TABLE `$tableName`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row && isset($row['Create Table'])) {
                $createSQL = $row['Create Table'];
                $tenantConnection->exec($createSQL);
                error_log("Tabla copiada: $tableName -> $targetDatabase");
            }
        } catch (Exception $e) {
            // Tabla no existe en origen, skip
            error_log("Warning: No se pudo copiar tabla $tableName: " . $e->getMessage());
        }
    }

    /**
     * Importar esquema desde archivo SQL
     */
    private function importSchemaFromFile($databaseName, $schemaFile) {
        $tenantConnection = $this->getTenantConnection($databaseName);
        $sql = file_get_contents($schemaFile);
        
        // Ejecutar SQL por partes (dividir por ;)
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $tenantConnection->exec($statement);
            }
        }
    }

    /**
     * Configurar datos iniciales tenant
     */
    private function setupInitialTenantData($tenantConnection) {
        // Monedas básicas
        $stmt = $tenantConnection->prepare("
            INSERT IGNORE INTO monedas (codigo, descripcion, simbolo, status) 
            VALUES ('GTQ', 'Quetzal Guatemalteco', 'Q', 'active'),
                   ('USD', 'Dólar Estadounidense', '$', 'active')
        ");
        $stmt->execute();

        // Tipos de planilla básicos
        $stmt = $tenantConnection->prepare("
            INSERT IGNORE INTO tipos_planilla (descripcion, status, created_at, updated_at) 
            VALUES ('Quincenal', 'active', NOW(), NOW()),
                   ('Mensual', 'active', NOW(), NOW()),
                   ('Semanal', 'active', NOW(), NOW())
        ");
        $stmt->execute();

        // Frecuencias básicas
        $stmt = $tenantConnection->prepare("
            INSERT IGNORE INTO frecuencias (descripcion, status, created_at, updated_at) 
            VALUES ('Quincenal', 'active', NOW(), NOW()),
                   ('Mensual', 'active', NOW(), NOW()),
                   ('Semanal', 'active', NOW(), NOW())
        ");
        $stmt->execute();

        error_log("Datos iniciales configurados en tenant");
    }

    /**
     * Generar clave de licencia única
     */
    private function generateLicenseKey($companyId, $companyData) {
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8));
        $companyHash = substr(md5($companyData['company_name'] . $companyData['ruc']), 0, 8);
        
        return "LIC-{$companyId}-{$companyHash}-{$randomString}-{$timestamp}";
    }

    /**
     * Almacenar licencia en tabla tenant
     */
    private function storeLicenseInTenant($ruc, $licenseKey) {
        try {
            $databaseName = $this->generateTenantDatabaseName($ruc);
            $tenantConnection = $this->getTenantConnection($databaseName);
            
            // Crear tabla de licencias si no existe
            $createTable = "
                CREATE TABLE IF NOT EXISTS licenses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    license_key VARCHAR(255) UNIQUE NOT NULL,
                    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    expires_at DATETIME NULL,
                    INDEX idx_license_key (license_key),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ";
            $tenantConnection->exec($createTable);
            
            // Insertar licencia
            $stmt = $tenantConnection->prepare("
                INSERT INTO licenses (license_key, status, expires_at) 
                VALUES (?, 'active', DATE_ADD(NOW(), INTERVAL 1 YEAR))
            ");
            $stmt->execute([$licenseKey]);
            
            error_log("License stored in tenant database: $databaseName");
            
        } catch (Exception $e) {
            error_log("Error storing license in tenant: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar nombre de BD tenant
     */
    public function generateTenantDatabaseName($ruc) {
        return 'planilla_empresa_' . $ruc;
    }

    /**
     * Obtener conexión a BD tenant
     */
    private function getTenantConnection($databaseName) {
        $config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'dbname' => $databaseName,
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? ''
        ];

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
        
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
}