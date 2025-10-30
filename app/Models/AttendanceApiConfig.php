<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo para gestión de configuración de API externa de asistencias
 */
class AttendanceApiConfig extends Model
{
    public $table = 'attendance_api_config';

    public $fillable = [
        'api_provider',
        'api_key',
        'app_id',
        'api_url',
        'sync_enabled',
        'sync_interval_minutes',
        'last_sync_at',
        'last_sync_status',
        'webhook_url',
        'webhook_secret',
        'config_json'
    ];

    /**
     * Obtener configuración activa (primera configuración habilitada)
     * @return array|null
     */
    public function getActiveConfig()
    {
        $sql = "SELECT * FROM {$this->table} WHERE sync_enabled = 1 ORDER BY id DESC LIMIT 1";
        return $this->db->find($sql);
    }

    /**
     * Obtener la última configuración (independientemente del estado enabled/disabled)
     * @return array|null
     */
    public function getLatestConfig()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1";
        return $this->db->find($sql);
    }

    /**
     * Obtener configuración por proveedor
     * @param string $provider Nombre del proveedor (base44, clockify, etc.)
     * @return array|null
     */
    public function getByProvider($provider)
    {
        $sql = "SELECT * FROM {$this->table} WHERE api_provider = ? ORDER BY id DESC LIMIT 1";
        return $this->db->find($sql, [$provider]);
    }

    /**
     * Actualizar timestamp de última sincronización
     * @param int $configId ID de configuración
     * @param string $status SUCCESS, FAILED, PARTIAL
     * @return bool
     */
    public function updateLastSync($configId, $status = 'SUCCESS')
    {
        $sql = "UPDATE {$this->table}
                SET last_sync_at = NOW(),
                    last_sync_status = ?,
                    updated_at = NOW()
                WHERE id = ?";

        return $this->db->query($sql, [$status, $configId]);
    }

    /**
     * Verificar si la sincronización debe ejecutarse
     * @param int $configId ID de configuración
     * @return bool
     */
    public function shouldSync($configId)
    {
        $config = $this->find($configId);

        if (!$config || !$config['sync_enabled']) {
            return false;
        }

        // Si nunca se ha sincronizado, debe ejecutarse
        if (empty($config['last_sync_at'])) {
            return true;
        }

        // Calcular tiempo desde última sincronización
        $lastSync = strtotime($config['last_sync_at']);
        $now = time();
        $minutesSinceLastSync = ($now - $lastSync) / 60;

        // Ejecutar si han pasado más minutos que el intervalo configurado
        return $minutesSinceLastSync >= $config['sync_interval_minutes'];
    }

    /**
     * Obtener tiempo hasta próxima sincronización (en minutos)
     * @param int $configId ID de configuración
     * @return int|null Minutos hasta próxima sincronización, null si debe ejecutarse ya
     */
    public function getMinutesUntilNextSync($configId)
    {
        $config = $this->find($configId);

        if (!$config || !$config['sync_enabled']) {
            return null;
        }

        if (empty($config['last_sync_at'])) {
            return 0; // Debe ejecutarse inmediatamente
        }

        $lastSync = strtotime($config['last_sync_at']);
        $now = time();
        $minutesSinceLastSync = ($now - $lastSync) / 60;
        $minutesUntilNext = $config['sync_interval_minutes'] - $minutesSinceLastSync;

        return $minutesUntilNext > 0 ? (int) ceil($minutesUntilNext) : 0;
    }

    /**
     * Habilitar sincronización automática
     * @param int $configId ID de configuración
     * @return bool
     */
    public function enableSync($configId)
    {
        return $this->update($configId, ['sync_enabled' => 1]);
    }

    /**
     * Deshabilitar sincronización automática
     * @param int $configId ID de configuración
     * @return bool
     */
    public function disableSync($configId)
    {
        return $this->update($configId, ['sync_enabled' => 0]);
    }

    /**
     * Actualizar intervalo de sincronización
     * @param int $configId ID de configuración
     * @param int $minutes Minutos de intervalo
     * @return bool
     */
    public function updateSyncInterval($configId, $minutes)
    {
        if ($minutes < 1) {
            $minutes = 1; // Mínimo 1 minuto
        }

        return $this->update($configId, ['sync_interval_minutes' => $minutes]);
    }

    /**
     * Obtener configuración en formato JSON decodificado
     * @param int $configId ID de configuración
     * @return array
     */
    public function getConfigJson($configId)
    {
        $config = $this->find($configId);

        if (!$config || empty($config['config_json'])) {
            return [];
        }

        $decoded = json_decode($config['config_json'], true);
        return $decoded ?: [];
    }

    /**
     * Actualizar configuración JSON
     * @param int $configId ID de configuración
     * @param array $configData Datos de configuración
     * @return bool
     */
    public function updateConfigJson($configId, $configData)
    {
        $json = json_encode($configData);
        return $this->update($configId, ['config_json' => $json]);
    }

    /**
     * Validar credenciales de API
     * @param array $data Datos de configuración
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateApiCredentials($data)
    {
        $errors = [];

        if (empty($data['api_key'])) {
            $errors['api_key'] = 'API Key es requerida';
        }

        if (empty($data['app_id'])) {
            $errors['app_id'] = 'App ID es requerido';
        }

        if (empty($data['api_url'])) {
            $errors['api_url'] = 'API URL es requerida';
        } elseif (!filter_var($data['api_url'], FILTER_VALIDATE_URL)) {
            $errors['api_url'] = 'API URL debe ser una URL válida';
        }

        if (isset($data['sync_interval_minutes'])) {
            $interval = (int) $data['sync_interval_minutes'];
            if ($interval < 1) {
                $errors['sync_interval_minutes'] = 'Intervalo debe ser al menos 1 minuto';
            } elseif ($interval > 1440) { // 24 horas
                $errors['sync_interval_minutes'] = 'Intervalo no puede ser mayor a 1440 minutos (24 horas)';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtener estadísticas de sincronizaciones
     * @param int $configId ID de configuración
     * @param int $days Número de días hacia atrás (default: 7)
     * @return array
     */
    public function getSyncStats($configId, $days = 7)
    {
        $config = $this->find($configId);
        if (!$config) {
            return null;
        }

        $sql = "SELECT
                    COUNT(*) as total_syncs,
                    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful_syncs,
                    SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed_syncs,
                    SUM(CASE WHEN status = 'PARTIAL' THEN 1 ELSE 0 END) as partial_syncs,
                    SUM(records_inserted) as total_records_inserted,
                    SUM(records_updated) as total_records_updated,
                    SUM(errors_count) as total_errors,
                    AVG(duration_seconds) as avg_duration_seconds,
                    MAX(end_time) as last_sync_time
                FROM attendance_sync_log
                WHERE api_provider = ?
                  AND start_time >= DATE_SUB(NOW(), INTERVAL ? DAY)";

        return $this->db->find($sql, [$config['api_provider'], $days]);
    }
}
