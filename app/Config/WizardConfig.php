<?php

/**
 * WizardConfig - Configuración del Sistema Multitenancy
 * Configuraciones centralizadas para el wizard de setup
 */

class WizardConfig {
    
    /**
     * Credenciales de administradores de plataforma
     * En producción: mover a base de datos o archivo .env seguro
     */
    public static function getPlatformAdmins() {
        return [
            'admin' => [
                'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'email' => 'admin@planillas.com',
                'name' => 'Administrador Sistema'
            ],
            'superadmin' => [
                'password_hash' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password  
                'email' => 'superadmin@planillas.com',
                'name' => 'Super Administrador'
            ],
            'wizard' => [
                'password_hash' => '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', // secret
                'email' => 'wizard@planillas.com', 
                'name' => 'Wizard Setup'
            ]
        ];
    }

    /**
     * Configuraciones de base de datos tenant
     */
    public static function getTenantDatabaseConfig() {
        return [
            'prefix' => 'planilla_empresa_',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'max_length' => 64, // Límite MySQL para nombres BD
            'allowed_chars' => '/^[a-zA-Z0-9_]+$/', // Solo alfanuméricos y underscore
        ];
    }

    /**
     * Configuraciones de empresa por defecto
     */
    public static function getDefaultCompanySettings() {
        return [
            'max_employees' => 100,
            'max_storage_mb' => 1000,
            'backup_schedule' => 'weekly',
            'tipo_institucion' => 'privada',
            'moneda_default' => 'GTQ',
            'features_enabled' => [
                'payroll_processing' => true,
                'reports_pdf' => true,
                'employee_management' => true,
                'concepts_management' => true,
                'creditors_management' => true,
                'dashboard_analytics' => true,
                'user_roles' => true
            ]
        ];
    }

    /**
     * Estructura básica de tablas para tenant
     */
    public static function getTenantRequiredTables() {
        return [
            // Tablas core del sistema
            'companies',
            'users', 
            'roles',
            'employees',
            'positions',
            
            // Tablas de configuración
            'tipos_planilla',
            'frecuencias', 
            'situaciones',
            'cargos',
            'partidas',
            'funciones',
            'horarios',
            
            // Tablas operativas
            'concepts',
            'creditors',
            'deductions',
            'payrolls',
            'payroll_details',
            
            // Tablas de sistema
            'monedas',
            'activity_logs'
        ];
    }

    /**
     * Datos iniciales que se insertan en cada tenant
     */
    public static function getInitialTenantData() {
        return [
            'monedas' => [
                ['codigo' => 'GTQ', 'descripcion' => 'Quetzal Guatemalteco', 'simbolo' => 'Q'],
                ['codigo' => 'USD', 'descripcion' => 'Dólar Estadounidense', 'simbolo' => '$']
            ],
            'tipos_planilla' => [
                ['descripcion' => 'Quincenal'],
                ['descripcion' => 'Mensual'], 
                ['descripcion' => 'Semanal']
            ],
            'frecuencias' => [
                ['descripcion' => 'Quincenal'],
                ['descripcion' => 'Mensual'],
                ['descripcion' => 'Semanal'] 
            ],
            'situaciones' => [
                ['descripcion' => 'Activo'],
                ['descripcion' => 'Inactivo'],
                ['descripcion' => 'Suspendido']
            ],
            'roles' => [
                [
                    'name' => 'Super Administrador',
                    'description' => 'Administrador con permisos completos',
                    'permissions' => json_encode([
                        'all' => true,
                        'dashboard' => ['read'],
                        'employees' => ['create', 'read', 'update', 'delete'],
                        'payrolls' => ['create', 'read', 'update', 'delete', 'process'],
                        'concepts' => ['create', 'read', 'update', 'delete'],
                        'creditors' => ['create', 'read', 'update', 'delete'],
                        'deductions' => ['create', 'read', 'update', 'delete'],
                        'reports' => ['read', 'generate'],
                        'users' => ['create', 'read', 'update', 'delete'],
                        'roles' => ['create', 'read', 'update', 'delete'],
                        'company' => ['read', 'update']
                    ])
                ],
                [
                    'name' => 'Administrador',
                    'description' => 'Administrador con permisos limitados',
                    'permissions' => json_encode([
                        'dashboard' => ['read'],
                        'employees' => ['create', 'read', 'update'],
                        'payrolls' => ['create', 'read', 'process'],
                        'concepts' => ['read'],
                        'creditors' => ['read'],
                        'deductions' => ['create', 'read', 'update'],
                        'reports' => ['read', 'generate']
                    ])
                ],
                [
                    'name' => 'Operador',
                    'description' => 'Usuario operativo básico',
                    'permissions' => json_encode([
                        'dashboard' => ['read'],
                        'employees' => ['read'],
                        'payrolls' => ['read'],
                        'reports' => ['read']
                    ])
                ]
            ]
        ];
    }

    /**
     * Configuraciones de validación
     */
    public static function getValidationRules() {
        return [
            'company_name' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 255,
                'pattern' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s\.\-,&]+$/'
            ],
            'ruc' => [
                'required' => true,
                'pattern' => '/^[0-9]{8,12}$/', // RUC guatemalteco
                'unique' => true
            ],
            'admin_username' => [
                'required' => true,
                'min_length' => 3,
                'max_length' => 50,
                'pattern' => '/^[a-zA-Z0-9_]+$/'
            ],
            'admin_email' => [
                'required' => true,
                'email' => true,
                'max_length' => 255
            ],
            'admin_password' => [
                'required' => true,
                'min_length' => 6,
                'max_length' => 255
            ],
            'admin_firstname' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'pattern' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'
            ],
            'admin_lastname' => [
                'required' => true,
                'min_length' => 2,
                'max_length' => 100,
                'pattern' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'
            ]
        ];
    }

    /**
     * Configuraciones de email
     */
    public static function getEmailConfig() {
        return [
            'enabled' => true,
            'welcome_template' => 'wizard/welcome_email.php',
            'from_email' => $_ENV['MAIL_FROM_EMAIL'] ?? 'noreply@planillas.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Planillas',
            'subject' => 'Bienvenido al Sistema de Planillas - Empresa Creada',
            'include_login_credentials' => false, // Por seguridad, no incluir contraseñas en email
        ];
    }

    /**
     * Configuraciones de logging
     */
    public static function getLoggingConfig() {
        return [
            'enabled' => true,
            'log_all_steps' => true,
            'log_performance' => true,
            'log_errors' => true,
            'retention_days' => 90,
            'log_ip_address' => true,
            'log_user_agent' => true
        ];
    }

    /**
     * Configuraciones de seguridad
     */
    public static function getSecurityConfig() {
        return [
            'max_attempts_per_ip' => 5,
            'lockout_duration_minutes' => 15,
            'session_timeout_minutes' => 30,
            'require_https' => false, // En producción: true
            'csrf_protection' => false, // Para wizard sin autenticación
            'rate_limiting' => [
                'enabled' => true,
                'max_requests_per_minute' => 10,
                'window_minutes' => 5
            ]
        ];
    }

    /**
     * Helper: Obtener configuración completa
     */
    public static function getAllConfigurations() {
        return [
            'platform_admins' => self::getPlatformAdmins(),
            'database' => self::getTenantDatabaseConfig(),
            'company_defaults' => self::getDefaultCompanySettings(),
            'required_tables' => self::getTenantRequiredTables(),
            'initial_data' => self::getInitialTenantData(),
            'validation' => self::getValidationRules(),
            'email' => self::getEmailConfig(),
            'logging' => self::getLoggingConfig(),
            'security' => self::getSecurityConfig()
        ];
    }

    /**
     * Helper: Validar configuración del sistema
     */
    public static function validateSystemRequirements() {
        $requirements = [
            'php_version' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'openssl' => extension_loaded('openssl'),
            'json' => extension_loaded('json'),
            'mbstring' => extension_loaded('mbstring'),
            'env_file' => file_exists(dirname(__DIR__, 2) . '/.env'),
            'database_connection' => true, // Se valida en runtime
        ];

        return [
            'valid' => !in_array(false, $requirements, true),
            'requirements' => $requirements
        ];
    }
}