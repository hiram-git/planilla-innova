<?php

namespace App\Core;

/**
 * BackofficeAuth - Sistema de autenticación para panel de administración backoffice
 *
 * Proporciona métodos de encriptación/desencriptación seguros y validación de tokens
 * para acceso al backoffice de gestión de tenants.
 *
 * @version 1.0.0
 * @date 2026-03-19
 */
class BackofficeAuth
{
    /**
     * Encriptar un token usando AES-256-CBC
     *
     * @param string $plainText Token en texto plano
     * @return string Token encriptado en base64
     */
    public static function encrypt(string $plainText): string
    {
        $key = self::getEncryptionKey();
        $iv = self::getIV();

        $encrypted = openssl_encrypt($plainText, 'AES-256-CBC', $key, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($encrypted);
    }

    /**
     * Desencriptar un token
     *
     * @param string $encryptedText Token encriptado en base64
     * @return string|false Token en texto plano, o false si falla
     */
    public static function decrypt(string $encryptedText)
    {
        $key = self::getEncryptionKey();
        $iv = self::getIV();

        $decoded = base64_decode($encryptedText, true);

        if ($decoded === false) {
            return false;
        }

        $decrypted = openssl_decrypt($decoded, 'AES-256-CBC', $key, 0, $iv);

        return $decrypted;
    }

    /**
     * Validar token de acceso contra el configurado en .env
     *
     * @param string $token Token a validar (puede estar encriptado o en texto plano)
     * @return bool True si el token es válido
     */
    public static function validateToken(string $token): bool
    {
        // Obtener token esperado y limpiar comillas si las tiene
        $expectedToken = $_ENV['BACKOFFICE_ACCESS_TOKEN'] ?? null;

        if (empty($expectedToken)) {
            error_log('BACKOFFICE_ACCESS_TOKEN not configured in .env');
            return false;
        }

        // Limpiar comillas del token esperado (si las tiene)
        $expectedToken = trim($expectedToken, '"\'');

        // DEBUG: Log temporal para ver qué está pasando
        error_log('========== BACKOFFICE AUTH DEBUG ==========');
        error_log('Expected token: ' . $expectedToken);
        error_log('Received token length: ' . strlen($token));
        error_log('Expected token length: ' . strlen($expectedToken));

        // Intentar desencriptar primero
        $decryptedToken = self::decrypt($token);

        error_log('Decryption successful: ' . ($decryptedToken !== false ? 'YES' : 'NO'));

        if ($decryptedToken !== false) {
            error_log('Decrypted token: ' . $decryptedToken);
            error_log('Decrypted token length: ' . strlen($decryptedToken));
        }

        // Si la desencriptación falla, comparar directamente (fallback)
        $tokenToCompare = ($decryptedToken !== false) ? $decryptedToken : $token;

        error_log('Token to compare: ' . substr($tokenToCompare, 0, 20) . '...');
        error_log('Expected token: ' . substr($expectedToken, 0, 20) . '...');

        // Usar hash_equals para prevenir timing attacks
        $isValid = hash_equals($expectedToken, $tokenToCompare);

        error_log('Validation result: ' . ($isValid ? 'VALID' : 'INVALID'));
        error_log('==========================================');

        // Log de seguridad
        if ($isValid) {
            self::logSecurityEvent('backoffice_access_granted', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            self::logSecurityEvent('backoffice_access_denied', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s'),
                'reason' => 'Invalid token',
                'expected_length' => strlen($expectedToken),
                'received_length' => strlen($tokenToCompare)
            ]);
        }

        return $isValid;
    }

    /**
     * Verificar si el usuario actual está autenticado en el backoffice
     *
     * @return bool True si está autenticado
     */
    public static function isAuthenticated(): bool
    {
        return isset($_SESSION['backoffice_authenticated']) &&
               $_SESSION['backoffice_authenticated'] === true;
    }

    /**
     * Autenticar usuario en el backoffice
     * Guarda estado de autenticación en sesión
     */
    public static function authenticate(): void
    {
        $_SESSION['backoffice_authenticated'] = true;
        $_SESSION['backoffice_auth_time'] = time();
        $_SESSION['backoffice_ip'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        self::logSecurityEvent('backoffice_session_started', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Cerrar sesión del backoffice
     */
    public static function logout(): void
    {
        self::logSecurityEvent('backoffice_logout', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'session_duration' => isset($_SESSION['backoffice_auth_time'])
                ? (time() - $_SESSION['backoffice_auth_time'])
                : 0
        ]);

        unset($_SESSION['backoffice_authenticated']);
        unset($_SESSION['backoffice_auth_time']);
        unset($_SESSION['backoffice_ip']);
    }

    /**
     * Verificar si la sesión ha expirado (2 horas por defecto)
     *
     * @return bool True si la sesión ha expirado
     */
    public static function isSessionExpired(): bool
    {
        if (!isset($_SESSION['backoffice_auth_time'])) {
            return true;
        }

        $sessionLifetime = 7200; // 2 horas en segundos
        $elapsed = time() - $_SESSION['backoffice_auth_time'];

        return $elapsed > $sessionLifetime;
    }

    /**
     * Verificar cambio de IP (prevenir session hijacking)
     *
     * @return bool True si la IP cambió
     */
    public static function hasIpChanged(): bool
    {
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sessionIp = $_SESSION['backoffice_ip'] ?? null;

        return $sessionIp !== null && $sessionIp !== $currentIp;
    }

    /**
     * Generar clave de encriptación desde APP_KEY
     *
     * @return string Clave de 32 bytes para AES-256
     */
    private static function getEncryptionKey(): string
    {
        $appKey = $_ENV['APP_KEY'] ?? 'default-insecure-key-change-me';
        return hash('sha256', $appKey, true);
    }

    /**
     * Generar IV (Initialization Vector) determinístico
     *
     * @return string IV de 16 bytes para AES-256-CBC
     */
    private static function getIV(): string
    {
        $appKey = $_ENV['APP_KEY'] ?? 'default-insecure-key-change-me';
        return substr(hash('sha256', $appKey . '_iv'), 0, 16);
    }

    /**
     * Registrar evento de seguridad en log
     *
     * @param string $event Tipo de evento
     * @param array $details Detalles adicionales
     */
    private static function logSecurityEvent(string $event, array $details = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];

        try {
            // Intentar usar TenantStorage si está disponible
            $logDir = __DIR__ . '/../../storage/logs/';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            $logFile = $logDir . 'backoffice_security.log';

            file_put_contents(
                $logFile,
                json_encode($logEntry) . "\n",
                FILE_APPEND | LOCK_EX
            );
        } catch (\Throwable $e) {
            error_log("Failed to write backoffice security log: " . $e->getMessage());
        }
    }
}
