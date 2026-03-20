<?php

namespace App\Middleware;

use App\Core\BackofficeAuth;

/**
 * BackofficeAuthMiddleware - Middleware de autenticación para rutas de backoffice
 *
 * Protege todas las rutas del backoffice excepto la página de login.
 * Valida autenticación, expiración de sesión y cambios de IP.
 *
 * @version 1.0.0
 * @date 2026-03-19
 */
class BackofficeAuthMiddleware
{
    /**
     * Rutas públicas que no requieren autenticación
     *
     * @var array
     */
    private static $publicRoutes = [
        '/backoffice',           // Página de login
        '/backoffice/login',     // Endpoint de autenticación
        '/backoffice/authenticate' // Alias
    ];

    /**
     * Verificar autenticación antes de permitir acceso
     *
     * @param string $route Ruta solicitada
     * @return bool True si puede continuar, false si debe redirigir
     */
    public static function handle(string $route): bool
    {
        // Permitir acceso a rutas públicas
        if (self::isPublicRoute($route)) {
            return true;
        }

        // Verificar si está autenticado
        if (!BackofficeAuth::isAuthenticated()) {
            self::redirectToLogin('not_authenticated');
            return false;
        }

        // Verificar expiración de sesión
        if (BackofficeAuth::isSessionExpired()) {
            BackofficeAuth::logout();
            self::redirectToLogin('session_expired');
            return false;
        }

        // Verificar cambio de IP (seguridad adicional)
        if (BackofficeAuth::hasIpChanged()) {
            BackofficeAuth::logout();
            self::redirectToLogin('ip_changed');
            return false;
        }

        // Todo OK - permitir acceso
        return true;
    }

    /**
     * Verificar si la ruta es pública (no requiere autenticación)
     *
     * @param string $route Ruta a verificar
     * @return bool True si es ruta pública
     */
    private static function isPublicRoute(string $route): bool
    {
        // Normalizar ruta
        $route = '/' . trim($route, '/');

        // Verificar rutas exactas
        if (in_array($route, self::$publicRoutes)) {
            return true;
        }

        // Verificar prefijos (para rutas dinámicas si las hay)
        foreach (self::$publicRoutes as $publicRoute) {
            if (strpos($route, $publicRoute) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirigir a la página de login con mensaje
     *
     * @param string $reason Razón de la redirección
     */
    private static function redirectToLogin(string $reason = ''): void
    {
        $messages = [
            'not_authenticated' => 'Debe autenticarse para acceder al backoffice',
            'session_expired' => 'Su sesión ha expirado. Por favor, autentíquese nuevamente',
            'ip_changed' => 'Se detectó un cambio de IP. Por favor, autentíquese nuevamente'
        ];

        $message = $messages[$reason] ?? 'Acceso denegado';

        // Guardar mensaje en sesión para mostrarlo en login
        $_SESSION['backoffice_login_error'] = $message;

        // Redirigir
        $loginUrl = \App\Core\UrlHelper::backoffice();
        header('Location: ' . $loginUrl);
        exit;
    }

    /**
     * Verificar si la solicitud es AJAX
     *
     * @return bool True si es AJAX
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Responder con JSON para solicitudes AJAX
     *
     * @param string $message Mensaje de error
     * @param int $code Código de estado HTTP
     */
    private static function jsonResponse(string $message, int $code = 401): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ]);
        exit;
    }
}
