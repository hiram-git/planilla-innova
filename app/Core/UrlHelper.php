<?php

namespace App\Core;

/**
 * Helper para generación de URLs
 * Proporciona métodos para generar URLs correctas en toda la aplicación
 */
class UrlHelper
{
    private static $baseUrl = null;
    private static $basePath = null;

    /**
     * Inicializar el helper con la configuración base
     */
    private static function init()
    {
        if (self::$baseUrl === null) {
            // Usar configuración del .env si está disponible
            self::$baseUrl = Config::get('app.url');
            
            if (self::$baseUrl) {
                // Extraer el path base de la URL configurada
                $parsed = parse_url(self::$baseUrl);
                self::$basePath = $parsed['path'] ?? '';
            } else {
                // Fallback: detectar automáticamente la URL base
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
                $basePath = dirname($scriptName);
                
                // Normalizar el path base
                if ($basePath === '/' || $basePath === '\\') {
                    $basePath = '';
                }
                
                self::$baseUrl = $protocol . '://' . $host . $basePath;
                self::$basePath = $basePath;
            }
        }
    }

    /**
     * Generar URL absoluta para una ruta
     */
    public static function url($path = '')
    {
        self::init();
        
        // Limpiar el path
        $path = ltrim($path, '/');
        
        // Si el path ya es una URL completa, devolverlo
        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }
        
        return self::$baseUrl . '/' . $path;
    }

    /**
     * Generar URL relativa para una ruta
     */
    public static function route($path = '')
    {
        self::init();
        
        if (empty($path)) {
            return self::$basePath . '/';
        }
        
        // Generar ruta usando el path base configurado
        return self::$basePath . '/' . ltrim($path, '/');
    }

    /**
     * Generar URL para assets (CSS, JS, imágenes)
     */
    public static function asset($path)
    {
        self::init();
        $path = ltrim($path, '/');
        return self::$baseUrl . '/' . $path;
    }

    /**
     * Obtener la URL base
     */
    public static function base()
    {
        self::init();
        return self::$baseUrl;
    }

    /**
     * Generar URL para el panel de administración
     */
    public static function panel($path = '')
    {
        return self::route('panel/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para el admin (método legacy para compatibilidad)
     */
    public static function admin($path = '')
    {
        return self::route('panel/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para empleados
     */
    public static function employee($path = '')
    {
        return self::route('panel/employees/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para asistencia
     */
    public static function attendance($path = '')
    {
        return self::route('panel/attendance/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para posiciones
     */
    public static function position($path = '')
    {
        return self::route('panel/positions/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para cargos
     */
    public static function cargo($path = '')
    {
        return self::route('panel/cargos/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para partidas
     */
    public static function partida($path = '')
    {
        return self::route('panel/partidas/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para funciones
     */
    public static function funcion($path = '')
    {
        return self::route('panel/funciones/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para horarios
     */
    public static function schedule($path = '')
    {
        return self::route('panel/schedules/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para planillas
     */
    public static function payroll($path = '')
    {
        return self::route('panel/payrolls/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para conceptos
     */
    public static function concept($path = '')
    {
        return self::route('panel/concepts/' . ltrim($path, '/'));
    }

    /**
     * Generar URL para el sistema de marcaciones
     */
    public static function timeclock($path = '')
    {
        if (empty($path)) {
            return self::route('');
        }
        return self::route('timeclock/' . ltrim($path, '/'));
    }

    /**
     * Verificar si una URL está activa (para navegación)
     */
    public static function isActive($path)
    {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($currentPath, PHP_URL_PATH);
        
        return strpos($currentPath, $path) !== false;
    }

    /**
     * Redireccionar a una URL
     */
    public static function redirect($path, $statusCode = 302)
    {
        $url = self::route($path);
        header("Location: $url", true, $statusCode);
        exit;
    }

    /**
     * Obtener la URL actual
     */
    public static function current()
    {
        self::init();
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        return self::$baseUrl . $currentPath;
    }
}