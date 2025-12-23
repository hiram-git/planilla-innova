<?php

namespace App\Services;

use Exception;

/**
 * LicenseValidator - Servicio de Validación de Licencias
 *
 * Valida licencias contra el servidor de Innovasoft Latam y retorna
 * siempre una respuesta estandarizada para evitar errores en login.
 */
class LicenseValidator
{
    private $licenseApiUrl;
    private $userApiUrl;
    private $timeout;
    private $sslVerify;
    private $prefix;

    public function __construct()
    {
        $baseUrl = $_ENV['LICENSE_VALIDATION_URL'] ?? 'https://plataforma.innovasoftlatam.com:8080';
        // Remover el path de endpoint si viene en la URL base.
        $baseUrl = rtrim(str_replace('/ajax/license.php', '', $baseUrl), '/');

        $this->licenseApiUrl = $baseUrl . '/ajax/license.php';
        $this->userApiUrl = $baseUrl . '/ajax/user.php';
        $this->timeout = (int)($_ENV['HTTP_TIMEOUT'] ?? 8);
        $this->sslVerify = $this->envToBool($_ENV['LICENSING_SSL_VERIF'] ?? null, true);
        $this->prefix = $_ENV['LICENSE_PREFIX'] ?? 'PINN'; // PINN = Panama Innova
    }

    /**
    * Validar licencia contra servidor remoto.
    *
    * @param string $licenseKey
    * @return array{valid:bool,expiration_date:?string,days_remaining:?int,message:string,raw_response:mixed}
    */
    public function validate($licenseKey)
    {
        if (empty($licenseKey)) {
            return $this->buildResponse(false, null, 'Licencia no proporcionada');
        }

        try {
            $response = $this->makeRequest($licenseKey);
            if ($response === false) {
                return $this->buildResponse(false, null, 'No se pudo contactar el servidor de licencias');
            }

            $data = json_decode($response, true);
            if (!is_array($data)) {
                return $this->buildResponse(false, null, 'Respuesta de licencia inválida', $data);
            }

            $isValid = false;
            $expirationRaw = null;
            $message = $data['message'] ?? 'Licencia no valida';

            if ((isset($data['success']) && (int)$data['success'] === 1) || isset($data['Expiration']) || isset($data['expiration'])) {
                $isValid = true;
                $expirationRaw = $data['Expiration'] ?? $data['expiration'] ?? null;
                $message = 'Licencia valida';
            }

            $normalizedExpiration = $this->normalizeExpirationDate($expirationRaw);
            $daysRemaining = $normalizedExpiration ? $this->getDaysRemaining($normalizedExpiration) : null;

            return $this->buildResponse($isValid, $normalizedExpiration, $message, $data, $daysRemaining);
        } catch (Exception $e) {
            error_log("Excepcion verificando licencia: " . $e->getMessage());
            return $this->buildResponse(false, null, 'Error al validar la licencia');
        }
    }

    /**
     * Convierte variables de entorno en booleanos confiables.
     */
    private function envToBool($value, $default = false)
    {
        if ($value === null) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower((string)$value);
        $truthy = ['1', 'true', 'yes', 'on'];
        $falsy = ['0', 'false', 'no', 'off', ''];

        if (in_array($value, $truthy, true)) {
            return true;
        }

        if (in_array($value, $falsy, true)) {
            return false;
        }

        return $default;
    }

    /**
     * Realizar petición cURL al servidor de licencias.
     */
    private function makeRequest($licenseKey)
    {
        $curl = curl_init();

        $payload = json_encode([
            'searchLicense' => 'yes',
            'License' => $licenseKey
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->licenseApiUrl,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_POST => 1,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            error_log("Error cURL validando licencia: {$error}");
            return false;
        }

        if ($httpCode !== 200) {
            error_log("HTTP {$httpCode} al validar licencia");
            return false;
        }

        return $response;
    }

    /**
     * Normaliza fecha de expiración a formato Y-m-d H:i:s.
     */
    private function normalizeExpirationDate($expirationRaw)
    {
        if (empty($expirationRaw)) {
            return null;
        }

        try {
            $clean = preg_replace('/\\.\\d+$/', '', str_replace('T', ' ', $expirationRaw));
            $date = new \DateTime($clean);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            error_log("No se pudo normalizar fecha de licencia: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener días restantes de la licencia.
     */
    private function getDaysRemaining($expirationDate)
    {
        try {
            $expiration = new \DateTime($expirationDate);
            $today = new \DateTime(date("Y-m-d"));
            $interval = $expiration->diff($today);

            if ($interval->invert == 1) {
                // La fecha de expiración está en el futuro
                return $interval->days;
            }

            // Ya expiró
            return -$interval->days;
        } catch (Exception $e) {
            error_log("Error calculando días restantes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir respuesta estandarizada.
     */
    private function buildResponse($valid, $expirationDate, $message, $rawResponse = null, $daysRemaining = null)
    {
        return [
            'valid' => $valid,
            'expiration_date' => $expirationDate,
            'days_remaining' => $daysRemaining,
            'message' => $message,
            'raw_response' => $rawResponse
        ];
    }

    /**
     * Verifica si la licencia vence pronto.
     */
    public function isLicenseExpiringSoon($expirationDate, $warningDays = 30)
    {
        $daysRemaining = $this->getDaysRemaining($expirationDate);
        return $daysRemaining !== null && $daysRemaining > 0 && $daysRemaining <= $warningDays;
    }

    /**
     * Validar licencia y almacenar resultado en sesión.
     */
    public function validateAndStore($licenseKey)
    {
        $result = $this->validate($licenseKey);

        if ($result['valid']) {
            $_SESSION['license_validated'] = true;
            $_SESSION['license_key'] = $licenseKey;
            $_SESSION['license_expiration'] = $result['expiration_date'];
            $_SESSION['license_days_remaining'] = $result['days_remaining'];
            $_SESSION['license_validated_at'] = date('Y-m-d H:i:s');

            if ($result['expiration_date'] && $this->isLicenseExpiringSoon($result['expiration_date'])) {
                $_SESSION['license_warning'] = "Su licencia vence en {$result['days_remaining']} días. Por favor, renueve pronto.";
            }
        } else {
            unset($_SESSION['license_validated']);
            unset($_SESSION['license_key']);
            unset($_SESSION['license_expiration']);
            unset($_SESSION['license_days_remaining']);
            unset($_SESSION['license_validated_at']);
            unset($_SESSION['license_warning']);
        }

        return $result;
    }

    /**
     * Verificar si hay una licencia válida en sesión.
     */
    public static function hasValidLicenseInSession()
    {
        return isset($_SESSION['license_validated']) && $_SESSION['license_validated'] === true;
    }
}
