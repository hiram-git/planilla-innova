<?php

namespace App\Middleware;

class AuthMiddleware
{
    public static function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            // Establecer mensaje apropiado para sesión expirada
            session_start();
            $_SESSION['error'] = 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.';
            header('Location: ' . url('admin'));
            exit();
        }
    }

    public static function redirectIfAuthenticated()
    {
        if (isset($_SESSION['admin'])) {
            header('Location: ' . url('panel/dashboard'));
            exit();
        }
    }

    public static function validateCSRF()
    {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            // Manejar error CSRF con mensaje para usuario final y redirección
            session_start();
            $_SESSION['error'] = 'Token de seguridad inválido. Por favor, inicie sesión nuevamente.';

            // Limpiar sesión para forzar nuevo login
            unset($_SESSION['csrf_token']);
            unset($_SESSION['admin']);

            header('Location: ' . url('admin'));
            exit();
        }
    }

    public static function generateCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function rateLimit($maxAttempts = 5, $timeWindow = 300)
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'rate_limit_' . $ip;
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'last_attempt' => time()
            ];
        }

        $data = $_SESSION[$key];
        
        if (time() - $data['last_attempt'] > $timeWindow) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'last_attempt' => time()
            ];
            return true;
        }

        if ($data['attempts'] >= $maxAttempts) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many attempts. Try again later.']);
            exit();
        }

        $_SESSION[$key]['attempts']++;
        $_SESSION[$key]['last_attempt'] = time();
        
        return true;
    }
}