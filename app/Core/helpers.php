<?php

use App\Core\Config;

if (!function_exists('getBaseUrl')) {
    /**
     * Obtiene la URL base del proyecto dinámicamente
     * 
     * @return string URL base del proyecto
     */
    function getBaseUrl()
    {
        // Si está configurado en .env, usar esa configuración
        if (!empty($_ENV['APP_URL'])) {
            return rtrim($_ENV['APP_URL'], '/');
        }
        
        // Detectar automáticamente
        if (!isset($_SERVER['HTTP_HOST'])) {
            return 'http://localhost';
        }
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Obtener el directorio del script actual
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $projectPath = '';
        
        // Si estamos en el directorio raíz del proyecto
        if (strpos($scriptPath, '/index.php') !== false) {
            $projectPath = str_replace('/index.php', '', $scriptPath);
        } elseif (strpos($scriptPath, '/app/') !== false) {
            // Si estamos en una subcarpeta del proyecto, obtener la ruta del proyecto
            $pathParts = explode('/app/', $scriptPath);
            $projectPath = $pathParts[0];
        } elseif (!empty($scriptPath)) {
            // Obtener el directorio padre
            $projectPath = dirname($scriptPath);
            if ($projectPath === '/') {
                $projectPath = '';
            }
        }
        
        return $protocol . '://' . $host . $projectPath;
    }
}

if (!function_exists('url')) {
    /**
     * Genera una URL completa usando la configuración de APP_URL
     * 
     * @param string $path Ruta relativa (ej: '/panel/dashboard')
     * @param bool $absolute Si debe ser absoluta o relativa
     * @return string URL completa
     */
    function url($path = '', $absolute = true)
    {
        if ($absolute) {
            $baseUrl = getBaseUrl();
            $path = ltrim($path, '/');
            return $baseUrl . '/' . $path;
        } else {
            // Para URLs relativas, extraer solo el directorio del APP_URL
            $baseUrl = Config::get('app.url', 'http://localhost');
            $parsed = parse_url($baseUrl);
            $basePath = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';
            $path = ltrim($path, '/');
            return $basePath . '/' . $path;
        }
    }
}

if (!function_exists('asset')) {
    /**
     * Genera URL para assets (CSS, JS, imágenes)
     * 
     * @param string $path Ruta al asset
     * @return string URL completa del asset
     */
    function asset($path)
    {
        $path = ltrim($path, '/');
        return url('assets/' . $path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirecciona a una URL usando el helper url()
     * 
     * @param string $path Ruta destino
     * @param int $code Código HTTP (default: 302)
     */
    function redirect($path, $code = 302)
    {
        $location = url($path);
        header("Location: $location", true, $code);
        exit;
    }
}

if (!function_exists('base_path')) {
    /**
     * Obtiene la ruta base del proyecto
     * 
     * @param string $path Ruta adicional
     * @return string Ruta completa
     */
    function base_path($path = '')
    {
        $basePath = dirname(dirname(__DIR__));
        return $basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Obtiene el símbolo de moneda configurado en la empresa
     * 
     * @return string Símbolo de moneda (ej: Q, $, €)
     */
    function currency_symbol()
    {
        static $symbol = null;
        
        if ($symbol === null) {
            try {
                $company = new \App\Models\Company();
                $symbol = $company->getCurrencySymbol();
            } catch (\Exception $e) {
                error_log("Error getting currency symbol: " . $e->getMessage());
                $symbol = 'Q'; // Fallback por defecto
            }
        }
        
        return $symbol;
    }
}

if (!function_exists('currency_code')) {
    /**
     * Obtiene el código de moneda configurado en la empresa
     * 
     * @return string Código de moneda (ej: GTQ, USD, EUR)
     */
    function currency_code()
    {
        static $code = null;
        
        if ($code === null) {
            try {
                $company = new \App\Models\Company();
                $code = $company->getCurrencyCode();
            } catch (\Exception $e) {
                error_log("Error getting currency code: " . $e->getMessage());
                $code = 'GTQ'; // Fallback por defecto
            }
        }
        
        return $code;
    }
}