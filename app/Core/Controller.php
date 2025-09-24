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

    /**
     * Establecer mensaje flash en la sesión
     */
    protected function setFlashMessage($type, $message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$type] = $message;
    }

    /**
     * Obtener mensaje flash y eliminarlo de la sesión
     */
    protected function getFlashMessage($type)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION[$type])) {
            $message = $_SESSION[$type];
            unset($_SESSION[$type]);
            return $message;
        }

        return null;
    }

    /**
     * Verificar si hay mensaje flash
     */
    protected function hasFlashMessage($type)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION[$type]);
    }

    /**
     * Validar token CSRF
     */
    protected function validateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';

        if (empty($token) || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            $this->setFlashMessage('error', 'Token de seguridad inválido');
            return false;
        }

        return true;
    }

    /**
     * Generar campo de token CSRF para formularios
     */
    public static function getCsrfTokenInput()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }

    /**
     * Requerir autenticación
     */
    protected function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_id'])) {
            $this->redirect('/admin/login');
        }
    }

    /**
     * Almacenar datos de entrada para reutilizar en caso de error
     */
    protected function flashInput($data = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($data === null) {
            $data = $_POST;
        }

        $_SESSION['old_input'] = $data;
    }

    /**
     * Limpiar datos de entrada almacenados
     */
    protected function clearOldInput()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['old_input']);
    }

    /**
     * Redireccionar con datos de entrada preservados
     */
    protected function redirectWithInput($location, $message = null, $type = 'error')
    {
        $this->flashInput();

        if ($message) {
            $this->setFlashMessage($type, $message);
        }

        $this->redirect($location);
    }

    /**
     * Agregar notificación toastr a la sesión
     */
    protected function setToastrMessage($type, $message, $title = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['toastr_messages'])) {
            $_SESSION['toastr_messages'] = [];
        }

        $_SESSION['toastr_messages'][] = [
            'type' => $type,
            'message' => $message,
            'title' => $title
        ];
    }

    /**
     * Obtener notificaciones toastr de la sesión y limpiarlas
     */
    public static function getToastrMessages()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $messages = $_SESSION['toastr_messages'] ?? [];
        unset($_SESSION['toastr_messages']);
        return $messages;
    }

    /**
     * Redireccionar con notificación toastr
     */
    protected function redirectWithToastr($location, $type, $message, $title = null)
    {
        $this->setToastrMessage($type, $message, $title);
        $this->redirect($location);
    }
}