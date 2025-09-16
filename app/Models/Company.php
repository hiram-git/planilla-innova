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
        'currency_code', 'logo_path', 'tipo_institucion',
        'jefe_recursos_humanos', 'cargo_jefe_rrhh', 
        'elaborado_por', 'cargo_elaborador'
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
                $sql = "UPDATE {$this->table} 
                       SET company_name = ?, ruc = ?, legal_representative = ?, 
                           address = ?, phone = ?, email = ?, currency_symbol = ?, 
                           currency_code = ?, logo_path = ?, tipo_institucion = ?,
                           jefe_recursos_humanos = ?, cargo_jefe_rrhh = ?, 
                           elaborado_por = ?, cargo_elaborador = ?
                       WHERE id = 1";
                
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
                    $data['logo_path'] ?? null,
                    $data['tipo_institucion'] ?? 'privada',
                    $data['jefe_recursos_humanos'] ?? '',
                    $data['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                    $data['elaborado_por'] ?? '',
                    $data['cargo_elaborador'] ?? 'Especialista en Nóminas'
                ]);
                
                $message = 'Configuración de empresa actualizada exitosamente';
            } else {
                // Crear nueva empresa
                $sql = "INSERT INTO {$this->table} 
                       (id, company_name, ruc, legal_representative, address, phone, email, 
                        currency_symbol, currency_code, logo_path, tipo_institucion,
                        jefe_recursos_humanos, cargo_jefe_rrhh, elaborado_por, cargo_elaborador)
                       VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
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
                    $data['logo_path'] ?? null,
                    $data['tipo_institucion'] ?? 'privada',
                    $data['jefe_recursos_humanos'] ?? '',
                    $data['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                    $data['elaborado_por'] ?? '',
                    $data['cargo_elaborador'] ?? 'Especialista en Nóminas'
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
     * Verificar si es una empresa pública
     * @return bool
     */
    public function isEmpresaPublica()
    {
        return $this->getTipoInstitucion() === 'publica';
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
            'elaborado_por' => $company['firma_director_planilla'] ?? 'Por definir',
            'cargo_elaborador' => $company['cargo_director_planilla'] ?? 'Especialista en Nóminas',
            'jefe_recursos_humanos' => $company['firma_contador_planilla'] ?? 'Por definir',
            'cargo_jefe_rrhh' => $company['cargo_contador_planilla'] ?? 'Jefe de Recursos Humanos',
            
            // Campos nuevos para reportes de planilla
            'firma_director_planilla' => $company['firma_director_planilla'] ?? 'Director General',
            'cargo_director_planilla' => $company['cargo_director_planilla'] ?? 'Director General',
            'firma_contador_planilla' => $company['firma_contador_planilla'] ?? 'Contador General',
            'cargo_contador_planilla' => $company['cargo_contador_planilla'] ?? 'Contador General'
        ];
    }
}