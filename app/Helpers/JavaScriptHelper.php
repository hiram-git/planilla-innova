<?php

namespace App\Helpers;

/**
 * Helper para generar URLs y configuraciones dinámicas para JavaScript
 */
class JavaScriptHelper
{
    /**
     * Genera URLs dinámicas para módulos DRY (Reference Controllers)
     */
    public static function getReferenceDryUrls(): array
    {
        $modules = ['cargos', 'funciones', 'partidas', 'schedules', 'frecuencias', 'situaciones', 'tipos-planilla'];
        $urls = [];
        
        foreach ($modules as $module) {
            $urls[$module] = [
                'index' => url("/panel/{$module}"),
                'create' => url("/panel/{$module}/create"),
                'store' => url("/panel/{$module}/store"),
                'edit' => url("/panel/{$module}/edit"),
                'update' => url("/panel/{$module}/update"),
                'delete' => url("/panel/{$module}/delete"),
                'toggle' => url("/panel/{$module}/toggle-status"),
                'datatables_ajax' => url("/panel/{$module}/datatables-ajax"),
                'check_duplicate' => url("/panel/{$module}/check-duplicate")
            ];
        }
        
        return $urls;
    }
    
    /**
     * Genera URLs comunes para DataTables y otros componentes
     */
    public static function getCommonUrls(): array
    {
        return [
            'datatables_spanish' => url('/assets/js/datatables-spanish.json'),
            'base_url' => url('/'),
            'panel_url' => url('/panel')
        ];
    }
    
    /**
     * Genera configuraciones globales para JavaScript
     */
    public static function getGlobalConfig(): array
    {
        return [
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'app_name' => 'Sistema de Planillas MVC',
            'version' => '2.0.0',
            'environment' => 'development'
        ];
    }
    
    /**
     * Genera el script de configuración completo para módulos DRY
     */
    public static function generateDryModulesConfig(): string
    {
        $referenceUrls = self::getReferenceDryUrls();
        $commonUrls = self::getCommonUrls();
        $globalConfig = self::getGlobalConfig();
        
        $config = [
            'urls' => array_merge($commonUrls, ['modules' => $referenceUrls]),
            'config' => $globalConfig
        ];
        
        return "window.APP_CONFIG = " . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . ";";
    }
    
    /**
     * Obtiene la lista de archivos JavaScript necesarios para un módulo DRY
     */
    public static function getDryModuleScripts(string $module, string $action = 'index'): array
    {
        $scripts = [
            // Scripts comunes base
            '/assets/javascript/common/datatables-config.js',
            '/assets/javascript/common/reference-crud.js',
            '/assets/javascript/common/toggle-handlers.js'
        ];
        
        // Script específico del módulo si existe
        $moduleScript = "/assets/javascript/modules/{$module}/{$action}.js";
        if (file_exists(__DIR__ . "/../../.." . $moduleScript)) {
            $scripts[] = $moduleScript;
        }
        
        return $scripts;
    }
    
    /**
     * Renderiza los scripts de configuración en el HTML
     */
    public static function renderConfigScript($customConfig = null): string
    {
        if ($customConfig !== null) {
            // Configuración personalizada para módulos específicos
            $configJs = "window.appConfig = " . json_encode($customConfig) . ";";
            return '<script type="text/javascript">' . "\n" . $configJs . "\n" . '</script>';
        }
        
        // Configuración por defecto para módulos DRY
        return '<script type="text/javascript">' . "\n" . 
               self::generateDryModulesConfig() . "\n" . 
               '</script>';
    }
    
    /**
     * Renderiza la lista de archivos JavaScript como tags script
     */
    public static function renderScriptTags(array $scripts): string
    {
        $html = '';
        foreach ($scripts as $script) {
            $html .= '<script src="' . url($script) . '"></script>' . "\n";
        }
        return $html;
    }
    
    /**
     * Almacén estático para módulos JavaScript que se cargarán
     */
    private static $jsModules = [];
    
    /**
     * Agrega un módulo JavaScript para cargar con su configuración
     */
    public static function addModule(string $modulePath, array $config = []): void
    {
        self::$jsModules[] = [
            'path' => $modulePath,
            'config' => $config
        ];
    }
    
    /**
     * Renderiza todos los módulos JavaScript agregados
     */
    public static function renderModules(): string
    {
        if (empty(self::$jsModules)) {
            return '';
        }
        
        $html = "\n<script type=\"module\">\n";
        
        foreach (self::$jsModules as $module) {
            $modulePath = $module['path'];
            $config = $module['config'];
            
            // Generar nombre de clase desde el path
            $pathParts = explode('/', $modulePath);
            $filename = end($pathParts); // 'show'
            $moduleDir = $pathParts[count($pathParts) - 2]; // 'payroll'
            
            // Convertir a PascalCase para el nombre de la clase
            $className = ucfirst($moduleDir) . ucfirst(str_replace('.js', '', $filename)) . 'Module';
            
            $html .= "    // Cargar módulo: {$modulePath}\n";
            $html .= "    import { {$className} } from '" . url("assets/javascript/{$modulePath}.js") . "';\n";
            $html .= "    \n";
            $html .= "    // Configuración del módulo\n";
            $html .= "    const " . lcfirst($className) . "Config = " . json_encode($config, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . ";\n";
            $html .= "    \n";
            $html .= "    // Inicializar módulo\n";
            $html .= "    document.addEventListener('DOMContentLoaded', function() {\n";
            $html .= "        const " . lcfirst($className) . " = new {$className}();\n";
            $html .= "        " . lcfirst($className) . ".setConfig(" . lcfirst($className) . "Config);\n";
            $html .= "        " . lcfirst($className) . ".init();\n";
            $html .= "    });\n";
            $html .= "    \n";
        }
        
        $html .= "</script>\n";
        
        // Limpiar módulos después de renderizar
        self::$jsModules = [];
        
        return $html;
    }
    
    /**
     * Limpia los módulos almacenados (útil para testing)
     */
    public static function clearModules(): void
    {
        self::$jsModules = [];
    }

    /**
     * Carga un módulo JavaScript específico
     * @param string $modulePath Ruta del módulo (ej: 'payroll/create')
     * @param array $config Configuración opcional para el módulo
     * @return string HTML con el script tag del módulo
     */
    public static function loadModule(string $modulePath, array $config = []): string
    {
        // Agregar configuración CSRF por defecto
        $defaultConfig = [
            'csrf_token' => $_SESSION['csrf_token'] ?? '',
            'base_url' => \App\Core\UrlHelper::url('/'),
            'panel_url' => \App\Core\UrlHelper::url('/panel')
        ];

        $finalConfig = array_merge($defaultConfig, $config);

        // Generar path completo del archivo
        $fullPath = "/assets/javascript/modules/{$modulePath}.js";

        // Verificar si el archivo existe
        $filePath = $_SERVER['DOCUMENT_ROOT'] . $fullPath;
        if (!file_exists($filePath)) {
            error_log("JavaScriptHelper: Archivo no encontrado: {$filePath}");
            return "<!-- Error: Módulo JavaScript no encontrado: {$modulePath} -->";
        }

        $html = "";

        // Si hay configuración, hacerla disponible globalmente antes de cargar el módulo
        if (!empty($finalConfig)) {
            $html .= "\n<script type=\"text/javascript\">\n";
            $html .= "    // Configuración del módulo {$modulePath}\n";
            $html .= "    window.moduleConfig = window.moduleConfig || {};\n";
            $html .= "    window.moduleConfig['{$modulePath}'] = " . json_encode($finalConfig, JSON_UNESCAPED_SLASHES) . ";\n";
            $html .= "</script>\n";
        }

        // Cargar el archivo JavaScript como script normal
        $html .= '<script src="' . \App\Core\UrlHelper::url($fullPath) . '"></script>' . "\n";

        return $html;
    }
}