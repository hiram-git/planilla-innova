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

if (!function_exists('csrf_field')) {
    /**
     * Genera un campo oculto con el token CSRF
     *
     * @return string Campo input hidden con token CSRF
     */
    function csrf_field()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Devuelve la cadena 'csrf_token' para usar como nombre de campo
     *
     * @return string
     */
    function csrf_token()
    {
        return 'csrf_token';
    }
}

if (!function_exists('csrf_hash')) {
    /**
     * Obtiene el valor actual del token CSRF
     *
     * @return string
     */
    function csrf_hash()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('old')) {
    /**
     * Obtiene el valor anterior de un campo de formulario después de validación
     * Útil para mantener valores cuando hay errores de validación
     *
     * @param string $key Nombre del campo
     * @param string $default Valor por defecto si no existe
     * @return string Valor anterior o default
     */
    function old($key, $default = '')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Buscar en $_POST primero (datos enviados)
        if (isset($_POST[$key])) {
            return htmlspecialchars($_POST[$key]);
        }

        // Buscar en datos flash de sesión
        if (isset($_SESSION['old_input'][$key])) {
            $value = $_SESSION['old_input'][$key];
            return htmlspecialchars($value);
        }

        return htmlspecialchars($default);
    }
}

if (!function_exists('business_days_between')) {
    /**
     * Calcular días laborables entre dos fechas usando el calendario empresarial
     *
     * @param string $startDate Fecha inicial (Y-m-d)
     * @param string $endDate Fecha final (Y-m-d)
     * @return int Número de días laborables
     */
    function business_days_between($startDate, $endDate)
    {
        try {
            $calendar = new \App\Models\BusinessCalendar();
            return $calendar->getWorkingDaysBetween($startDate, $endDate);
        } catch (\Exception $e) {
            error_log("Error calculating business days: " . $e->getMessage());

            // Fallback: cálculo simple sin base de datos
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            $workingDays = 0;

            while ($start <= $end) {
                $dayOfWeek = $start->format('N');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Lunes a Viernes
                    $workingDays++;
                }
                $start->modify('+1 day');
            }

            return $workingDays;
        }
    }
}

if (!function_exists('is_working_day')) {
    /**
     * Verificar si una fecha es día laboral según el calendario empresarial
     *
     * @param string $date Fecha a verificar (Y-m-d)
     * @return bool True si es día laboral
     */
    function is_working_day($date)
    {
        try {
            $calendar = new \App\Models\BusinessCalendar();
            return $calendar->isWorkingDay($date);
        } catch (\Exception $e) {
            error_log("Error checking working day: " . $e->getMessage());

            // Fallback: verificar solo días de semana
            $dayOfWeek = date('N', strtotime($date));
            return $dayOfWeek >= 1 && $dayOfWeek <= 5;
        }
    }
}

if (!function_exists('get_day_type_class')) {
    /**
     * Obtener clase CSS para el tipo de día
     *
     * @param string $dayType Tipo de día (LABORAL, NO_LABORAL, FERIADO, etc.)
     * @return string Clase CSS
     */
    function get_day_type_class($dayType)
    {
        $classes = [
            'LABORAL' => 'badge-success',
            'NO_LABORAL' => 'badge-secondary',
            'FERIADO' => 'badge-danger',
            'DUELO_NACIONAL' => 'badge-dark',
            'ESPECIAL' => 'badge-info'
        ];

        return $classes[$dayType] ?? 'badge-light';
    }
}

if (!function_exists('format_day_type')) {
    /**
     * Formatear tipo de día para mostrar
     *
     * @param string $dayType Tipo de día
     * @return string Texto formateado
     */
    function format_day_type($dayType)
    {
        $formats = [
            'LABORAL' => 'Día Laboral',
            'NO_LABORAL' => 'No Laboral',
            'FERIADO' => 'Feriado Nacional',
            'DUELO_NACIONAL' => 'Duelo Nacional',
            'ESPECIAL' => 'Día Especial'
        ];

        return $formats[$dayType] ?? $dayType;
    }
}