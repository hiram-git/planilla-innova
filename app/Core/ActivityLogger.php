<?php

namespace App\Core;

use App\Core\Database;
use PDO;

class ActivityLogger
{
    private static $db = null;
    
    private static function getConnection()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    /**
     * Log user activity to database
     * 
     * @param string $action Action performed (LOGIN, CREATE, UPDATE, DELETE, etc.)
     * @param string $table_affected Table that was affected (optional)
     * @param int $record_id ID of affected record (optional)
     * @param string $description Human readable description
     * @param array $old_data Previous data (for updates)
     * @param array $new_data New data (for creates/updates)
     * @param int $user_id User ID (if null, gets from session)
     */
    public static function log($action, $table_affected = null, $record_id = null, $description = null, $old_data = null, $new_data = null, $user_id = null)
    {
        try {
            $conn = self::getConnection();
            
            // Get user ID from session if not provided
            if ($user_id === null && isset($_SESSION['user'])) {
                $user_id = $_SESSION['user']['id'] ?? null;
            }
            
            // Get client info
            $ip_address = self::getClientIp();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Prepare additional data
            $additional_data = [];
            if ($old_data !== null) {
                $additional_data['old_data'] = $old_data;
            }
            if ($new_data !== null) {
                $additional_data['new_data'] = $new_data;
            }
            
            $additional_data_json = !empty($additional_data) ? json_encode($additional_data, JSON_UNESCAPED_UNICODE) : null;
            
            $sql = "INSERT INTO system_logs (user_id, action, table_affected, record_id, description, additional_data, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $user_id,
                $action,
                $table_affected,
                $record_id,
                $description,
                $additional_data_json,
                $ip_address,
                $user_agent
            ]);
            
        } catch (\Exception $e) {
            // Log to file as fallback (don't break the application)
            error_log("ActivityLogger Error: " . $e->getMessage());
        }
    }
    
    /**
     * Log user login
     */
    public static function logLogin($user_id, $username, $success = true)
    {
        $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        $description = $success 
            ? "Usuario '{$username}' inició sesión exitosamente"
            : "Intento de login fallido para usuario '{$username}'";
            
        self::log($action, 'users', $user_id, $description, null, null, $user_id);
    }
    
    /**
     * Log user logout
     */
    public static function logLogout($user_id, $username)
    {
        self::log('LOGOUT', 'users', $user_id, "Usuario '{$username}' cerró sesión", null, null, $user_id);
    }
    
    /**
     * Log record creation
     */
    public static function logCreate($table, $record_id, $data, $description = null)
    {
        if ($description === null) {
            $description = "Nuevo registro creado en tabla '{$table}' con ID {$record_id}";
        }
        self::log('CREATE', $table, $record_id, $description, null, $data);
    }
    
    /**
     * Log record update
     */
    public static function logUpdate($table, $record_id, $old_data, $new_data, $description = null)
    {
        if ($description === null) {
            $description = "Registro actualizado en tabla '{$table}' con ID {$record_id}";
        }
        self::log('UPDATE', $table, $record_id, $description, $old_data, $new_data);
    }
    
    /**
     * Log record deletion
     */
    public static function logDelete($table, $record_id, $data, $description = null)
    {
        if ($description === null) {
            $description = "Registro eliminado de tabla '{$table}' con ID {$record_id}";
        }
        self::log('DELETE', $table, $record_id, $description, $data, null);
    }
    
    /**
     * Log payroll processing
     */
    public static function logPayrollProcess($payroll_id, $action, $description, $additional_data = null)
    {
        self::log("PAYROLL_{$action}", 'payrolls', $payroll_id, $description, null, $additional_data);
    }
    
    /**
     * Log report generation
     */
    public static function logReport($report_type, $filters, $description = null)
    {
        if ($description === null) {
            $description = "Reporte generado: {$report_type}";
        }
        self::log('REPORT_GENERATED', null, null, $description, null, ['report_type' => $report_type, 'filters' => $filters]);
    }
    
    /**
     * Log security events
     */
    public static function logSecurity($event, $description, $additional_data = null)
    {
        self::log("SECURITY_{$event}", null, null, $description, null, $additional_data);
    }
    
    /**
     * Log system configuration changes
     */
    public static function logConfig($setting_key, $old_value, $new_value, $description = null)
    {
        if ($description === null) {
            $description = "Configuración modificada: {$setting_key}";
        }
        self::log('CONFIG_CHANGE', 'system_settings', null, $description, 
                 ['setting' => $setting_key, 'value' => $old_value], 
                 ['setting' => $setting_key, 'value' => $new_value]);
    }
    
    /**
     * Get activity logs with pagination and filters
     */
    public static function getLogs($page = 1, $per_page = 50, $filters = [])
    {
        try {
            $conn = self::getConnection();
            $offset = ($page - 1) * $per_page;
            
            $where_conditions = [];
            $params = [];
            
            // Apply filters
            if (isset($filters['user_id']) && $filters['user_id']) {
                $where_conditions[] = "sl.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (isset($filters['action']) && $filters['action']) {
                $where_conditions[] = "sl.action LIKE ?";
                $params[] = '%' . $filters['action'] . '%';
            }
            
            if (isset($filters['table_affected']) && $filters['table_affected']) {
                $where_conditions[] = "sl.table_affected = ?";
                $params[] = $filters['table_affected'];
            }
            
            if (isset($filters['date_from']) && $filters['date_from']) {
                $where_conditions[] = "DATE(sl.created_at) >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && $filters['date_to']) {
                $where_conditions[] = "DATE(sl.created_at) <= ?";
                $params[] = $filters['date_to'];
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $sql = "SELECT sl.*, 
                           COALESCE(u.username, a.username, 'Sistema') as username,
                           COALESCE(u.email, CONCAT(a.firstname, ' ', a.lastname), 'N/A') as user_display_name
                    FROM system_logs sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    LEFT JOIN admin a ON sl.user_id = a.id
                    {$where_clause}
                    ORDER BY sl.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $per_page;
            $params[] = $offset;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM system_logs sl {$where_clause}";
            $count_params = array_slice($params, 0, -2); // Remove LIMIT and OFFSET params
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->execute($count_params);
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'logs' => $logs,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page)
            ];
            
        } catch (\Exception $e) {
            error_log("Error getting activity logs: " . $e->getMessage());
            return [
                'logs' => [],
                'total' => 0,
                'page' => 1,
                'per_page' => $per_page,
                'total_pages' => 0
            ];
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public static function getDashboardStats($days = 7)
    {
        try {
            $conn = self::getConnection();
            
            $sql = "SELECT 
                        action,
                        COUNT(*) as count,
                        DATE(created_at) as date
                    FROM system_logs 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY action, DATE(created_at)
                    ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error getting dashboard stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old logs
     */
    public static function cleanOldLogs($days_to_keep = 365)
    {
        try {
            $conn = self::getConnection();
            
            $sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$days_to_keep]);
            
            $deleted_count = $stmt->rowCount();
            
            self::log('SYSTEM_MAINTENANCE', 'system_logs', null, 
                     "Limpieza de logs antiguos: {$deleted_count} registros eliminados");
            
            return $deleted_count;
            
        } catch (\Exception $e) {
            error_log("Error cleaning old logs: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIp()
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}