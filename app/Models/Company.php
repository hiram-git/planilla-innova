<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use Exception;

/**
 * Modelo Company - Gestión de configuración de empresa
 */
class Company extends Model
{
    public $table = 'companies';
    protected $fillable = [
        'company_name', 'ruc', 'legal_representative',
        'address', 'phone', 'email', 'currency_symbol',
        'currency_code', 'logo_empresa', 'logo_izquierdo_reportes',
        'logo_derecho_reportes', 'tipo_institucion',
        'jefe_recursos_humanos', 'cargo_jefe_rrhh',
        'elaborado_por', 'cargo_elaborador',
        'mail_host', 'mail_port', 'mail_username', 'mail_password',
        'mail_encryption', 'mail_from_address', 'mail_from_name'
    ];

    private $pdo;

    public function __construct()
    {
        parent::__construct();
        $this->pdo = $this->db->getConnection();
    }

    /**
     * Obtener configuración de la empresa (siempre ID=1)
     */
    public function getCompanyConfig()
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting company config: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear o actualizar configuración de empresa
     */
    public function saveCompanyConfig($data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateCompanyData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar si ya existe una empresa
            $existing = $this->getCompanyConfig();
            
            if ($existing) {
                // Actualizar empresa existente
                $updateFields = [
                    'company_name = ?', 'ruc = ?', 'legal_representative = ?',
                    'address = ?', 'phone = ?', 'email = ?', 'currency_symbol = ?',
                    'currency_code = ?', 'logo_empresa = ?', 'logo_izquierdo_reportes = ?',
                    'logo_derecho_reportes = ?', 'tipo_institucion = ?',
                    'jefe_recursos_humanos = ?', 'cargo_jefe_rrhh = ?',
                    'elaborado_por = ?', 'cargo_elaborador = ?',
                    'mail_host = ?', 'mail_port = ?', 'mail_username = ?',
                    'mail_encryption = ?', 'mail_from_address = ?', 'mail_from_name = ?'
                ];

                $params = [
                    $data['company_name'],
                    $data['ruc'],
                    $data['legal_representative'],
                    $data['address'],
                    $data['phone'],
                    $data['email'],
                    $data['currency_symbol'] ?? 'Q',
                    $data['currency_code'] ?? 'GTQ',
                    $data['logo_empresa'] ?? null,
                    $data['logo_izquierdo_reportes'] ?? null,
                    $data['logo_derecho_reportes'] ?? null,
                    $data['tipo_institucion'] ?? 'privada',
                    $data['jefe_recursos_humanos'] ?? '',
                    $data['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                    $data['elaborado_por'] ?? '',
                    $data['cargo_elaborador'] ?? 'Especialista en Nóminas',
                    $data['mail_host'] ?? null,
                    $data['mail_port'] ?? 587,
                    $data['mail_username'] ?? null,
                    $data['mail_encryption'] ?? 'tls',
                    $data['mail_from_address'] ?? null,
                    $data['mail_from_name'] ?? null
                ];

                // Solo actualizar contraseña si se proporciona una nueva
                if (isset($data['mail_password'])) {
                    $updateFields[] = 'mail_password = ?';
                    $params[] = $data['mail_password']; // Se almacena en texto plano para uso en PHPMailer
                }

                $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE id = 1";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute($params);

                $message = 'Configuración de empresa actualizada exitosamente';
            } else {
                // Crear nueva empresa
                $sql = "INSERT INTO {$this->table}
                       (id, company_name, ruc, legal_representative, address, phone, email,
                        currency_symbol, currency_code, logo_empresa, logo_izquierdo_reportes,
                        logo_derecho_reportes, tipo_institucion,
                        jefe_recursos_humanos, cargo_jefe_rrhh, elaborado_por, cargo_elaborador,
                        mail_host, mail_port, mail_username, mail_password,
                        mail_encryption, mail_from_address, mail_from_name)
                       VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $data['company_name'],
                    $data['ruc'],
                    $data['legal_representative'],
                    $data['address'],
                    $data['phone'],
                    $data['email'],
                    $data['currency_symbol'] ?? 'Q',
                    $data['currency_code'] ?? 'GTQ',
                    $data['logo_empresa'] ?? null,
                    $data['logo_izquierdo_reportes'] ?? null,
                    $data['logo_derecho_reportes'] ?? null,
                    $data['tipo_institucion'] ?? 'privada',
                    $data['jefe_recursos_humanos'] ?? '',
                    $data['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                    $data['elaborado_por'] ?? '',
                    $data['cargo_elaborador'] ?? 'Especialista en Nóminas',
                    $data['mail_host'] ?? null,
                    $data['mail_port'] ?? 587,
                    $data['mail_username'] ?? null,
                    $data['mail_password'] ?? null,
                    $data['mail_encryption'] ?? 'tls',
                    $data['mail_from_address'] ?? null,
                    $data['mail_from_name'] ?? null
                ]);

                $message = 'Configuración de empresa creada exitosamente';
            }

            if ($result) {
                error_log("Company config saved successfully");
                return ['success' => true, 'message' => $message];
            }

            return ['success' => false, 'message' => 'Error al guardar la configuración'];
            
        } catch (Exception $e) {
            error_log("Error saving company config: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el servidor'];
        }
    }

    /**
     * Obtener símbolo de moneda configurado
     */
    public function getCurrencySymbol()
    {
        $company = $this->getCompanyConfig();
        return $company['currency_symbol'] ?? 'Q';
    }

    /**
     * Obtener código de moneda configurado
     */
    public function getCurrencyCode()
    {
        $company = $this->getCompanyConfig();
        return $company['currency_code'] ?? 'GTQ';
    }

    /**
     * Obtener información básica para reportes
     */
    public function getCompanyForReports()
    {
        $company = $this->getCompanyConfig();
        if (!$company) {
            return [
                'company_name' => 'Empresa No Configurada',
                'ruc' => 'N/A',
                'address' => 'N/A',
                'phone' => 'N/A',
                'currency_symbol' => 'Q'
            ];
        }
        
        return $company;
    }

    /**
     * Validar datos de la empresa
     */
    private function validateCompanyData($data)
    {
        $errors = [];

        // Nombre de empresa requerido
        if (empty($data['company_name'])) {
            $errors[] = 'El nombre de la empresa es requerido';
        } elseif (strlen($data['company_name']) < 3 || strlen($data['company_name']) > 255) {
            $errors[] = 'El nombre de la empresa debe tener entre 3 y 255 caracteres';
        }

        // RUC requerido
        if (empty($data['ruc'])) {
            $errors[] = 'El RUC es requerido';
        } elseif (strlen($data['ruc']) < 6 || strlen($data['ruc']) > 20) {
            $errors[] = 'El RUC debe tener entre 6 y 20 caracteres';
        }

        // Email válido si se proporciona
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido';
        }

        // Teléfono si se proporciona
        if (!empty($data['phone']) && (strlen($data['phone']) < 8 || strlen($data['phone']) > 15)) {
            $errors[] = 'El teléfono debe tener entre 8 y 15 caracteres';
        }

        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }

    /**
     * Verificar si la empresa está configurada
     */
    public function isConfigured()
    {
        $company = $this->getCompanyConfig();
        return $company !== null && !empty($company['company_name']);
    }

    /**
     * Obtener estadísticas de configuración
     */
    public function getConfigStats()
    {
        $company = $this->getCompanyConfig();
        
        if (!$company) {
            return [
                'configured' => false,
                'completion' => 0,
                'missing_fields' => ['Toda la configuración']
            ];
        }

        $requiredFields = ['company_name', 'ruc', 'legal_representative', 'address', 'phone', 'email'];
        $configured = 0;
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!empty($company[$field])) {
                $configured++;
            } else {
                $missing[] = ucfirst(str_replace('_', ' ', $field));
            }
        }

        return [
            'configured' => true,
            'completion' => round(($configured / count($requiredFields)) * 100),
            'missing_fields' => $missing
        ];
    }

    /**
     * Obtener tipo de institución configurada
     * @return string 'publica' | 'privada'
     */
    public function getTipoInstitucion()
    {
        $company = $this->getCompanyConfig();
        return $company['tipo_institucion'] ?? 'privada';
    }

    /**
     * Verificar si es una empresa con posiciones
     * @return bool
     */
    public function isEmpresaConPosiciones()
    {
        return $this->getTipoInstitucion() === 'publica';
    }

    /**
     * Verificar si es una empresa pública (método legacy - usar isEmpresaConPosiciones)
     * @return bool
     * @deprecated Use isEmpresaConPosiciones() instead
     */
    public function isEmpresaPublica()
    {
        return $this->isEmpresaConPosiciones();
    }

    /**
     * Verificar si es una empresa privada
     * @return bool
     */
    public function isEmpresaPrivada()
    {
        return $this->getTipoInstitucion() === 'privada';
    }

    /**
     * Actualizar solo el tipo de institución
     * @param string $tipo 'publica' | 'privada'
     * @return array
     */
    public function updateTipoInstitucion($tipo)
    {
        try {
            // Validar tipo
            if (!in_array($tipo, ['publica', 'privada'])) {
                return ['success' => false, 'message' => 'Tipo de institución inválido'];
            }

            $sql = "UPDATE {$this->table} SET tipo_institucion = ? WHERE id = 1";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$tipo]);

            if ($result) {
                return ['success' => true, 'message' => 'Tipo de institución actualizado exitosamente'];
            }

            return ['success' => false, 'message' => 'Error al actualizar el tipo de institución'];
            
        } catch (Exception $e) {
            error_log("Error updating tipo_institucion: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el servidor'];
        }
    }

    /**
     * Obtener firmas configuradas para reportes de planilla
     * @return array
     */
    public function getSignaturesForReports()
    {
        $company = $this->getCompanyConfig();

        if (!$company) {
            return [
                'elaborado_por' => 'Por definir',
                'cargo_elaborador' => 'Especialista en Nóminas',
                'jefe_recursos_humanos' => 'Por definir',
                'cargo_jefe_rrhh' => 'Jefe de Recursos Humanos',
                'firma_director_planilla' => 'Director General',
                'cargo_director_planilla' => 'Director General',
                'firma_contador_planilla' => 'Contador General',
                'cargo_contador_planilla' => 'Contador General'
            ];
        }

        return [
            // Campos legacy para compatibilidad con reportes existentes
            'elaborado_por' => $company['elaborado_por'] ?? 'Por definir',
            'cargo_elaborador' => $company['cargo_elaborador'] ?? 'Especialista en Nóminas',
            'jefe_recursos_humanos' => $company['jefe_recursos_humanos'] ?? 'Por definir',
            'cargo_jefe_rrhh' => $company['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',

            // Campos nuevos para reportes de planilla
            'firma_director_planilla' => $company['firma_director_planilla'] ?? 'Director General',
            'cargo_director_planilla' => $company['cargo_director_planilla'] ?? 'Director General',
            'firma_contador_planilla' => $company['firma_contador_planilla'] ?? 'Contador General',
            'cargo_contador_planilla' => $company['cargo_contador_planilla'] ?? 'Contador General'
        ];
    }

    /**
     * Obtener configuración de correo SMTP
     * Prioriza configuración de BD, fallback a variables de entorno
     * @return array Configuración de correo lista para usar con PHPMailer
     */
    public function getMailConfig()
    {
        $company = $this->getCompanyConfig();

        // Valores por defecto desde .env con fallbacks seguros
        $defaults = [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? '',
            'password' => $_ENV['MAIL_PASSWORD'] ?? '',
            'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
            'from_address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@planillas.com',
            'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Sistema de Planillas'
        ];

        // Si hay configuración en BD, usarla (con fallback a .env)
        if ($company) {
            return [
                'host' => !empty($company['mail_host']) ? $company['mail_host'] : $defaults['host'],
                'port' => !empty($company['mail_port']) ? (int)$company['mail_port'] : $defaults['port'],
                'username' => !empty($company['mail_username']) ? $company['mail_username'] : $defaults['username'],
                'password' => !empty($company['mail_password']) ? $company['mail_password'] : $defaults['password'],
                'encryption' => !empty($company['mail_encryption']) ? $company['mail_encryption'] : $defaults['encryption'],
                'from_address' => !empty($company['mail_from_address']) ? $company['mail_from_address'] : $defaults['from_address'],
                'from_name' => !empty($company['mail_from_name']) ? $company['mail_from_name'] : $defaults['from_name']
            ];
        }

        // Si no hay empresa configurada, usar solo .env
        return $defaults;
    }
}