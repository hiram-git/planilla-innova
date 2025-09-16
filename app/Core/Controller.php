<?php

namespace App\Core;

class Controller
{
    protected $db;

    public function __construct()
    {
        // Inicializar conexión a base de datos
        $this->initDatabase();
    }

    private function initDatabase()
    {
        try {
            // Cargar configuración de la base de datos
            Config::load();
            $dbConfig = Config::get('database.connections.mysql');
            
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $dbname = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            $charset = $dbConfig['charset'];
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
            $this->db = new \PDO($dsn, $username, $password, $dbConfig['options']);
        } catch (\PDOException $e) {
            throw new \Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }
    public function model($model)
    {
        $modelClass = "App\\Models\\" . $model;
        return new $modelClass();
    }

    public function view($view, $data = [])
    {
        extract($data);
        // Obtener la ruta raíz del proyecto de forma más confiable
        $projectRoot = dirname(dirname(dirname(__FILE__)));
        
        // Convertir puntos a slashes para la estructura de directorios
        $viewPath = str_replace('.', '/', $view);
        $fullViewPath = $projectRoot . '/app/Views/' . $viewPath . '.php';
        
        if (file_exists($fullViewPath)) {
            require_once $fullViewPath;
        } else {
            throw new \Exception("Vista no encontrada: " . $view . " en ruta: " . $fullViewPath);
        }
    }

    public function redirect($location)
    {
        // Si la URL ya es absoluta (empieza con http), usarla tal como está
        if (strpos($location, 'http') === 0) {
            header('Location: ' . $location);
            exit();
        }
        
        // Si es una URL que ya incluye el path base del proyecto, usarla directamente
        $appUrl = Config::get('app.url', 'http://localhost');
        $parsed = parse_url($appUrl);
        $basePath = isset($parsed['path']) ? $parsed['path'] : '';
        
        if (!empty($basePath) && strpos($location, $basePath) === 0) {
            header('Location: ' . $location);
            exit();
        }
        
        // Si empieza con /, agregar el basePath dinámico solo si no lo tiene ya
        if (strpos($location, '/') === 0) {
            // Usar basePath dinámico en lugar de hardcode
            $location = $basePath . $location;
        }
        
        header('Location: ' . $location);
        exit();
    }

    public function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function validateCSRF()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $this->json(['error' => 'Invalid CSRF token'], 403);
        }
    }

    /**
     * Renderizar vista con layout de administración
     */
    public function render($view, $data = [])
    {
        // Extraer datos para usar en la vista
        extract($data);
        
        // Definir ruta de la vista
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            // Capturar el contenido de la vista
            ob_start();
            include $viewPath;
            $content = ob_get_clean();
            
            // Incluir el layout con el contenido
            $layoutPath = __DIR__ . '/../Views/layouts/admin.php';
            if (file_exists($layoutPath)) {
                include $layoutPath;
            } else {
                // Si no existe layout, mostrar solo el contenido
                echo $content;
            }
        } else {
            throw new \Exception("Vista no encontrada: $view");
        }
    }

    protected function generateCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar si la petición es AJAX
     */
    protected function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Alias para el método json() para compatibilidad
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        return $this->json($data, $statusCode);
    }
}