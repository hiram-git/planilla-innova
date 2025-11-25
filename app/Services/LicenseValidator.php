<?php

namespace App\Services;

use Exception;

/**
 * LicenseValidator - Servicio de Validación de Licencias
 *
 * Valida licencias contra el servidor de Innovasoft Latam
 * Basado en el sistema legacy con mejoras de seguridad y logging
 *
 * @version 1.0.0
 * @date 2025-11-24
 */
class LicenseValidator
{
    private $validationUrl;
    private $timeout;
    private $sslVerify;

    public function __construct()
    {
        $this->validationUrl = $_ENV['LICENSE_VALIDATION_URL'] ?? 'https://plataforma.innovasoftlatam.com:8080/ajax/licensePlanilla.php';
        $this->timeout = (int)($_ENV['HTTP_TIMEOUT'] ?? 8);
        $this->sslVerify = (bool)($_ENV['LICENSING_SSL_VERIF'] ?? false);
    }

    /**
     * Validar licencia contra servidor remoto
     *
     * @param string $licenseKey Clave de licencia a validar
     * @return array [
     *   'valid' => bool,
     *   'expiration_date' => string|null,
     *   'days_remaining' => int|null,
     *   'message' => string,
     *   'raw_response' => array|null
     * ]
     */
    public function validate($licenseKey)
    {
        if (empty($licenseKey)) {
            return $this->buildResponse(false, null, 'Licencia no proporcionada');
        }

        try {
            // Realizar petición cURL
            $response = $this->makeRequest($licenseKey);

            if (!$response) {
                return $this->buildResponse(false, null, 'No se pudo conectar al servidor de licencias');
            }

            // Parsear respuesta
            $data = json_decode($response, true);

            if (!isset($data['success']) || $data['success'] != 1) {
                $message = $data['message'] ?? 'Licencia no válida o inactiva';
                error_log("Validación de licencia fallida para: {$licenseKey} - {$message}");
                return $this->buildResponse(false, null, $message, $data);
            }

            // Verificar fecha de expiración
            $expirationDate = $data['Expiration'] ?? null;

            if (!$expirationDate) {
                return $this->buildResponse(false, null, 'Fecha de expiración no disponible', $data);
            }

            $isExpired = $this->isLicenseExpired($expirationDate);
            $daysRemaining = $this->getDaysRemaining($expirationDate);

            if ($isExpired) {
                $formattedDate = date('d/m/Y', strtotime($expirationDate));
                $message = "Su licencia {$licenseKey} expiró el {$formattedDate}. Por favor, contactar al distribuidor.";
                error_log("Licencia expirada: {$licenseKey} - Expiró: {$formattedDate}");
                return $this->buildResponse(false, $expirationDate, $message, $data, $daysRemaining);
            }

            // Licencia válida
            error_log("Licencia válida: {$licenseKey} - Días restantes: {$daysRemaining}");
            return $this->buildResponse(true, $expirationDate, 'Licencia válida', $data, $daysRemaining);

        } catch (Exception $e) {
            error_log("Error validando licencia {$licenseKey}: " . $e->getMessage());
            return $this->buildResponse(false, null, 'Error al validar licencia: ' . $e->getMessage());
        }
    }

    /**
     * Realizar petición cURL al servidor de licencias
     *
     * @param string $licenseKey
     * @return string|false
     */
    private function makeRequest($licenseKey)
    {
        $curl = curl_init();

        $data_json = json_encode([
            'searchLicense' => 'yes',
            'License' => $licenseKey
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->validationUrl,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
            CURLOPT_POST => 1,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_POSTFIELDS => $data_json,
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
     * Verificar si una licencia está expirada
     *
     * @param string $expirationDate Fecha en formato Y-m-d
     * @return bool
     */
    private function isLicenseExpired($expirationDate)
    {
        try {
            $datetime1 = new \DateTime($expirationDate);
            $datetime2 = new \DateTime(date("Y-m-d"));
            $interval = $datetime1->diff($datetime2);

            // Si invert es 0, significa que la fecha de expiración ya pasó
            return $interval->invert == 0;
        } catch (Exception $e) {
            error_log("Error comparando fechas de licencia: " . $e->getMessage());
            return true; // Por seguridad, considerar expirada si hay error
        }
    }

    /**
     * Obtener días restantes de la licencia
     *
     * @param string $expirationDate
     * @return int|null
     */
    private function getDaysRemaining($expirationDate)
    {
        try {
            $datetime1 = new \DateTime($expirationDate);
            $datetime2 = new \DateTime(date("Y-m-d"));
            $interval = $datetime1->diff($datetime2);

            if ($interval->invert == 1) {
                // La fecha de expiración está en el futuro
                return $interval->days;
            } else {
                // La licencia ya expiró
                return -$interval->days;
            }
        } catch (Exception $e) {
            error_log("Error calculando días restantes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir respuesta estandarizada
     *
     * @param bool $valid
     * @param string|null $expirationDate
     * @param string $message
     * @param array|null $rawResponse
     * @param int|null $daysRemaining
     * @return array
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
     * Verificar si una licencia está próxima a vencer (menos de 30 días)
     *
     * @param string $expirationDate
     * @return bool
     */
    public function isLicenseExpiringSoon($expirationDate, $warningDays = 30)
    {
        $daysRemaining = $this->getDaysRemaining($expirationDate);
        return $daysRemaining !== null && $daysRemaining > 0 && $daysRemaining <= $warningDays;
    }

    /**
     * Validar licencia y almacenar resultado en sesión
     *
     * @param string $licenseKey
     * @return array Resultado de validación
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

            // Advertencia si está próxima a vencer
            if ($this->isLicenseExpiringSoon($result['expiration_date'])) {
                $_SESSION['license_warning'] = "Su licencia vence en {$result['days_remaining']} días. Por favor, renueve pronto.";
            }
        } else {
            // Limpiar datos de licencia de la sesión
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
     * Verificar si hay una licencia válida en sesión
     *
     * @return bool
     */
    public static function hasValidLicenseInSession()
    {
        return isset($_SESSION['license_validated']) && $_SESSION['license_validated'] === true;
    }
}
