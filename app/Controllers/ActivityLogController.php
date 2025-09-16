<?php

namespace App\Controllers;

use App\Core\ActivityLogger;

class ActivityLogController extends BaseController
{
    protected $module = 'activity_logs';
    
    public function index()
    {
        $this->requirePermission('index');
        
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 50);
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'table_affected' => $_GET['table_affected'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $result = ActivityLogger::getLogs($page, $perPage, $filters);
        
        // Get unique actions for filter dropdown
        $actions = $this->getUniqueActions();
        
        // Get unique tables for filter dropdown
        $tables = $this->getUniqueTables();
        
        // Get users for filter dropdown
        $users = $this->getUsers();
        
        $data = [
            'title' => 'Bitácora de Actividades',
            'logs' => $result['logs'],
            'pagination' => [
                'current_page' => $result['page'],
                'total_pages' => $result['total_pages'],
                'total_records' => $result['total'],
                'per_page' => $result['per_page']
            ],
            'filters' => $filters,
            'actions' => $actions,
            'tables' => $tables,
            'users' => $users
        ];
        
        // Log viewing activity logs
        ActivityLogger::log('VIEW_ACTIVITY_LOGS', 'system_logs', null, 'Usuario consultó bitácora de actividades');
        
        $this->view('admin/activity_logs/index', $data);
    }
    
    public function show($id)
    {
        $this->requirePermission('show');
        
        try {
            $conn = ActivityLogger::getConnection();
            $sql = "SELECT sl.*, 
                           COALESCE(u.username, a.username, 'Sistema') as username,
                           COALESCE(u.email, CONCAT(a.firstname, ' ', a.lastname), 'N/A') as user_display_name
                    FROM system_logs sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    LEFT JOIN admin a ON sl.user_id = a.id
                    WHERE sl.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$id]);
            $log = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$log) {
                $_SESSION['error'] = 'Registro de actividad no encontrado';
                $this->redirect('/panel/activity-logs');
                return;
            }
            
            // Parse additional data if it's JSON
            if ($log['additional_data']) {
                $log['parsed_data'] = json_decode($log['additional_data'], true);
            }
            
            $data = [
                'title' => 'Detalle de Actividad',
                'log' => $log
            ];
            
            ActivityLogger::log('VIEW_ACTIVITY_LOG_DETAIL', 'system_logs', $id, "Usuario visualizó detalle de actividad ID: {$id}");
            
            $this->view('admin/activity_logs/show', $data);
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al obtener el detalle: ' . $e->getMessage();
            $this->redirect('/panel/activity-logs');
        }
    }
    
    public function dashboard()
    {
        $this->requirePermission('dashboard');
        
        $stats = ActivityLogger::getDashboardStats(7);
        
        // Process stats for chart
        $chartData = $this->processStatsForChart($stats);
        
        $data = [
            'title' => 'Dashboard de Actividades',
            'stats' => $stats,
            'chart_data' => $chartData
        ];
        
        ActivityLogger::log('VIEW_ACTIVITY_DASHBOARD', 'system_logs', null, 'Usuario consultó dashboard de actividades');
        
        $this->view('admin/activity_logs/dashboard', $data);
    }
    
    public function export()
    {
        $this->requirePermission('export');
        
        $filters = [
            'user_id' => $_GET['user_id'] ?? null,
            'action' => $_GET['action'] ?? null,
            'table_affected' => $_GET['table_affected'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        // Get all logs with filters (no pagination)
        $result = ActivityLogger::getLogs(1, 10000, $filters);
        
        // Generate CSV
        $filename = 'activity_logs_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'ID',
            'Usuario',
            'Acción', 
            'Tabla Afectada',
            'ID Registro',
            'Descripción',
            'IP',
            'Fecha/Hora'
        ]);
        
        // CSV Data
        foreach ($result['logs'] as $log) {
            fputcsv($output, [
                $log['id'],
                $log['username'],
                $log['action'],
                $log['table_affected'],
                $log['record_id'],
                $log['description'],
                $log['ip_address'],
                $log['created_at']
            ]);
        }
        
        fclose($output);
        
        // Log export action
        ActivityLogger::log('EXPORT_ACTIVITY_LOGS', 'system_logs', null, 
                           'Usuario exportó bitácora de actividades', null, ['filters' => $filters]);
    }
    
    public function clean()
    {
        $this->requirePermission('delete');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método no permitido';
            $this->redirect('/panel/activity-logs');
            return;
        }
        
        $days = (int)($_POST['days_to_keep'] ?? 365);
        
        if ($days < 30) {
            $_SESSION['error'] = 'Debe conservar al menos 30 días de registros';
            $this->redirect('/panel/activity-logs');
            return;
        }
        
        $deletedCount = ActivityLogger::cleanOldLogs($days);
        
        if ($deletedCount !== false) {
            $_SESSION['success'] = "Se eliminaron {$deletedCount} registros antiguos";
        } else {
            $_SESSION['error'] = 'Error al limpiar registros antiguos';
        }
        
        $this->redirect('/panel/activity-logs');
    }
    
    private function getUniqueActions()
    {
        try {
            $conn = ActivityLogger::getConnection();
            $sql = "SELECT DISTINCT action FROM system_logs ORDER BY action";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getUniqueTables()
    {
        try {
            $conn = ActivityLogger::getConnection();
            $sql = "SELECT DISTINCT table_affected FROM system_logs WHERE table_affected IS NOT NULL ORDER BY table_affected";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getUsers()
    {
        try {
            $conn = ActivityLogger::getConnection();
            $sql = "SELECT DISTINCT sl.user_id, 
                           COALESCE(u.username, a.username, 'Sistema') as username
                    FROM system_logs sl
                    LEFT JOIN users u ON sl.user_id = u.id
                    LEFT JOIN admin a ON sl.user_id = a.id
                    WHERE sl.user_id IS NOT NULL
                    ORDER BY username";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function processStatsForChart($stats)
    {
        $chartData = [
            'labels' => [],
            'datasets' => []
        ];
        
        $actionCounts = [];
        $dates = [];
        
        foreach ($stats as $stat) {
            if (!in_array($stat['date'], $dates)) {
                $dates[] = $stat['date'];
            }
            
            if (!isset($actionCounts[$stat['action']])) {
                $actionCounts[$stat['action']] = [];
            }
            
            $actionCounts[$stat['action']][$stat['date']] = $stat['count'];
        }
        
        $chartData['labels'] = array_reverse($dates);
        
        foreach ($actionCounts as $action => $dateCounts) {
            $data = [];
            foreach ($chartData['labels'] as $date) {
                $data[] = $dateCounts[$date] ?? 0;
            }
            
            $chartData['datasets'][] = [
                'label' => $action,
                'data' => $data,
                'backgroundColor' => $this->getRandomColor(),
                'borderColor' => $this->getRandomColor(),
                'fill' => false
            ];
        }
        
        return $chartData;
    }
    
    private function getRandomColor()
    {
        $colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
            '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
        ];
        
        return $colors[array_rand($colors)];
    }
}