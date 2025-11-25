<?php

namespace App\Services;

use Exception;

/**
 * LicenseGenerator - Servicio de Generación de Licencias
 *
 * Genera y registra licencias únicas en el servidor de Innovasoft Latam
 * Basado en el sistema legacy con mejoras de seguridad y logging
 *
 * @version 1.0.0
 * @date 2025-11-24
 */
class LicenseGenerator
{
    private $licenseApiUrl;
    private $userApiUrl;
    private $timeout;
    private $sslVerify;
    private $prefix;

    public function __construct()
    {
        $baseUrl = $_ENV['LICENSE_VALIDATION_URL'] ?? 'https://plataforma.innovasoftlatam.com:8080';
        // Remover '/ajax/license.php' si está presente en la URL base
        $baseUrl = rtrim(str_replace('/ajax/license.php', '', $baseUrl), '/');

        $this->licenseApiUrl = $baseUrl . '/ajax/license.php';
        $this->userApiUrl = $baseUrl . '/ajax/user.php';
        $this->timeout = (int)($_ENV['HTTP_TIMEOUT'] ?? 8);
        $this->sslVerify = (bool)($_ENV['LICENSING_SSL_VERIF'] ?? true);
        $this->prefix = $_ENV['LICENSE_PREFIX'] ?? 'PINN'; // PINN = Panama Innova
    }

    /**
     * Generar una licencia única
     *
     * Genera un código de licencia aleatorio y verifica que no exista
     * Si existe, genera uno nuevo recursivamente
     *
     * @param int $maxAttempts Número máximo de intentos
     * @return string Código de licencia único
     * @throws Exception Si no se puede generar una licencia única
     */
    public function generateUniqueLicense($maxAttempts = 10)
    {
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            $attempts++;

            // Generar licencia: PREFIX + 10 dígitos aleatorios
            $randomDigits = rand(1000000000, 9999999999);
            $license = $this->prefix . $randomDigits;

            // Verificar si la licencia ya existe
            $exists = $this->checkLicenseExists($license);

            if (!$exists) {
                error_log("Licencia única generada: {$license} (intento {$attempts})");
                return $license;
            }

            error_log("Licencia {$license} ya existe, generando otra... (intento {$attempts})");
        }

        throw new Exception("No se pudo generar una licencia única después de {$maxAttempts} intentos");
    }

    /**
     * Verificar si una licencia ya existe en el servidor remoto
     *
     * @param string $license Código de licencia a verificar
     * @return bool True si existe, False si no existe
     */
    private function checkLicenseExists($license)
    {
        try {
            $curl = curl_init();

            $data_json = json_encode([
                'searchLicense' => 'yes',
                'License' => $license
            ]);

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->userApiUrl,
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
                error_log("Error cURL verificando licencia: {$error}");
                return false; // Asumir que no existe si hay error de conexión
            }

            if ($httpCode !== 200) {
                error_log("HTTP {$httpCode} al verificar licencia");
                return false;
            }

            $data = json_decode($response, true);

            // Si success=1, la licencia existe
            return isset($data['success']) && $data['success'] == 1;

        } catch (Exception $e) {
            error_log("Excepción verificando licencia: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registrar una licencia en el servidor remoto
     *
     * @param array $licenseData [
     *   'license' => string,
     *   'ruc' => string,
     *   'company_name' => string,
     *   'buyer_name' => string,
     *   'email' => string,
     *   'phone' => string,
     *   'country' => string (default: 'Panama'),
     *   'final_user' => string,
     *   'expiration_date' => string (Y-m-d),
     *   'first_activation' => string (Y-m-d)
     * ]
     * @return array ['success' => bool, 'message' => string, 'raw_response' => array|null]
     */
    public function registerLicense($licenseData)
    {
        // Validar datos requeridos
        $required = ['license', 'ruc', 'company_name', 'buyer_name', 'email'];
        foreach ($required as $field) {
            if (empty($licenseData[$field])) {
                return [
                    'success' => false,
                    'message' => "Campo requerido faltante: {$field}",
                    'raw_response' => null
                ];
            }
        }

        try {
            $curl = curl_init();

            // Preparar datos para el registro
            $postData = [
                'registerLicense' => 'yes',
                'License' => $licenseData['license'],
                'RUC' => $licenseData['ruc'],
                'Buyer' => $licenseData['buyer_name'],
                'Company' => $licenseData['company_name'],
                'Email' => $licenseData['email'],
                'Phone' => $licenseData['phone'] ?? '',
                'Expiration' => $licenseData['expiration_date'],
                'MaxActivations' => '50',
                'CurActivations' => '1',
                'SaintLicense' => $licenseData['license'],
                'State' => 'XSU_TRIAL',
                'CurActCompleted' => '1',
                'UniqueTable' => '1',
                'FinalUser' => $licenseData['final_user'],
                'FirstActivation' => $licenseData['first_activation'],
                'Country' => $licenseData['country'] ?? 'Panama',
                'Product' => 'PLANILLA_INNOVA',
                'Reactivation' => null,
                'HasCoronaTest' => '1',
                'IdAnalitica' => null,
                'FinalUserRUC' => null
            ];

            $data_json = json_encode($postData);

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->licenseApiUrl,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
                CURLOPT_POST => 1,
                CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
                CURLOPT_POSTFIELDS => $data_json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout * 2, // Mayor timeout para registro
                CURLOPT_CONNECTTIMEOUT => $this->timeout
            ]);

            $response = curl_exec($curl);
            $error = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);

            if ($error) {
                error_log("Error cURL registrando licencia: {$error}");
                return [
                    'success' => false,
                    'message' => 'Error de conexión con el servidor de licencias',
                    'raw_response' => null
                ];
            }

            if ($httpCode !== 200) {
                error_log("HTTP {$httpCode} al registrar licencia");
                return [
                    'success' => false,
                    'message' => "Error del servidor: HTTP {$httpCode}",
                    'raw_response' => null
                ];
            }

            $data = json_decode($response, true);

            if (!isset($data['success']) || $data['success'] != 1) {
                $message = $data['message'] ?? 'Error al registrar la licencia';
                error_log("Registro de licencia fallido: {$message}");
                return [
                    'success' => false,
                    'message' => $message,
                    'raw_response' => $data
                ];
            }

            // Registro exitoso
            error_log("Licencia registrada exitosamente: {$licenseData['license']}");
            return [
                'success' => true,
                'message' => 'Licencia registrada exitosamente',
                'raw_response' => $data
            ];

        } catch (Exception $e) {
            error_log("Excepción registrando licencia: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al registrar licencia: ' . $e->getMessage(),
                'raw_response' => null
            ];
        }
    }

    /**
     * Generar y registrar una licencia completa
     *
     * Este es el método principal que combina generación y registro
     *
     * @param array $companyData Datos de la empresa (ver registerLicense)
     * @param bool $allowOffline Permitir modo offline si servidor no disponible
     * @return array ['success' => bool, 'message' => string, 'license' => string|null, 'offline_mode' => bool]
     */
    public function generateAndRegister($companyData, $allowOffline = true)
    {
        try {
            // 1. Generar licencia única LOCAL (sin verificar servidor si está offline)
            $license = $this->generateUniqueLicenseLocal();

            // 2. Calcular fechas
            $firstActivation = date('Y-m-d');
            $expirationDate = date('Y-m-d', strtotime('+30 days'));

            // 3. Preparar datos para registro
            $licenseData = array_merge($companyData, [
                'license' => $license,
                'first_activation' => $firstActivation,
                'expiration_date' => $expirationDate,
                'final_user' => $companyData['company_name']
            ]);

            // 4. INTENTAR registrar licencia en servidor remoto
            $result = $this->registerLicense($licenseData);

            if (!$result['success']) {
                // Si falla el registro y se permite modo offline
                if ($allowOffline) {
                    error_log("⚠️ MODO OFFLINE ACTIVADO - Servidor de licencias no disponible");
                    error_log("Licencia generada localmente: {$license}");
                    error_log("Se marcará como pendiente de registro en servidor remoto");

                    return [
                        'success' => true,
                        'message' => 'Licencia generada en modo offline (pendiente de registro en servidor)',
                        'license' => $license,
                        'expiration_date' => $expirationDate,
                        'first_activation' => $firstActivation,
                        'offline_mode' => true,
                        'pending_sync' => true,
                        'sync_error' => $result['message']
                    ];
                }

                // Si no se permite offline, retornar error
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'license' => null,
                    'offline_mode' => false
                ];
            }

            // 5. Retornar resultado exitoso con la licencia registrada en servidor
            return [
                'success' => true,
                'message' => 'Licencia generada y registrada exitosamente en servidor remoto',
                'license' => $license,
                'expiration_date' => $expirationDate,
                'first_activation' => $firstActivation,
                'offline_mode' => false,
                'pending_sync' => false
            ];

        } catch (Exception $e) {
            error_log("Error en generateAndRegister: " . $e->getMessage());

            // Si se permite offline, generar licencia local
            if ($allowOffline) {
                $license = $this->generateUniqueLicenseLocal();
                $firstActivation = date('Y-m-d');
                $expirationDate = date('Y-m-d', strtotime('+30 days'));

                error_log("⚠️ MODO OFFLINE ACTIVADO - Excepción capturada");
                error_log("Licencia generada localmente: {$license}");

                return [
                    'success' => true,
                    'message' => 'Licencia generada en modo offline (pendiente de registro)',
                    'license' => $license,
                    'expiration_date' => $expirationDate,
                    'first_activation' => $firstActivation,
                    'offline_mode' => true,
                    'pending_sync' => true,
                    'sync_error' => $e->getMessage()
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al generar licencia: ' . $e->getMessage(),
                'license' => null,
                'offline_mode' => false
            ];
        }
    }

    /**
     * Generar licencia única LOCAL (sin verificar servidor remoto)
     *
     * Para uso en modo offline cuando el servidor no está disponible
     *
     * @return string Código de licencia único local
     */
    private function generateUniqueLicenseLocal()
    {
        // Generar licencia: PREFIX + timestamp + random
        // Formato: PINN + 10 dígitos (timestamp + random para unicidad)
        $timestamp = substr(time(), -5); // Últimos 5 dígitos del timestamp
        $random = rand(10000, 99999);    // 5 dígitos aleatorios

        $license = $this->prefix . $timestamp . $random;

        return $license;
    }

    /**
     * Reintentar registro de licencia pendiente
     *
     * Para sincronizar licencias generadas en modo offline
     *
     * @param array $licenseData Datos de la licencia a registrar
     * @return array ['success' => bool, 'message' => string]
     */
    public function retryRegistration($licenseData)
    {
        try {
            $result = $this->registerLicense($licenseData);

            if ($result['success']) {
                error_log("✅ Licencia sincronizada exitosamente: {$licenseData['license']}");
            } else {
                error_log("❌ Fallo al sincronizar licencia: {$licenseData['license']} - {$result['message']}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("❌ Excepción al sincronizar licencia: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ];
        }
    }
}
