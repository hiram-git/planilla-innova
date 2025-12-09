<?php

namespace App\Services\Attendance;

use Exception;

/**
 * Cliente para integración con API
 * Gestiona autenticación, requests HTTP y manejo de errores
 */
class ApiClient
{
    private $apiKey;
    private $appId;
    private $baseUrl;
    private $timeout = 30; // segundos
    private $maxRetries = 3;
    private $logEnabled = true;

    /**
     * Constructor
     * @param string $apiKey API Key de la API
     * @param string $appId App ID de la API
     * @param string $baseUrl URL base de la API (opcional)
     */
    public function __construct($apiKey = null, $appId = null, $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? getenv('API_KEY');
        $this->appId = $appId ?? getenv('API_APP_ID');
        $this->baseUrl = $baseUrl ?? getenv('API_URL') ?? 'https://app.api.com/api';

        if (empty($this->apiKey) || empty($this->appId)) {
            throw new Exception('API Key y App ID son requeridos');
        }
    }

    /**
     * Obtener todos los empleados desde la API
     * @param array $filters Filtros opcionales (user_email, full_name, employee_id, etc.)
     * @return array Lista de empleados
     */
    public function getEmployees($filters = [])
    {
        $endpoint = "/apps/{$this->appId}/entities/Employee";
        return $this->makeRequest('GET', $endpoint, $filters);
    }

    /**
     * Obtener un empleado específico por ID
     * @param string $entityId ID de la entidad en la API
     * @return array Datos del empleado
     */
    public function getEmployee($entityId)
    {
        $endpoint = "/apps/{$this->appId}/entities/Employee/{$entityId}";
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Actualizar datos de un empleado
     * @param string $entityId ID de la entidad en la API
     * @param array $data Datos a actualizar
     * @return array Respuesta de la API
     */
    public function updateEmployee($entityId, $data)
    {
        $endpoint = "/apps/{$this->appId}/entities/Employee/{$entityId}";
        return $this->makeRequest('PUT', $endpoint, null, $data);
    }

    /**
     * Obtener todas las marcaciones de asistencia
     * @param array $filters Filtros opcionales (employee_email, type, timestamp, etc.)
     * @return array Lista de marcaciones
     */
    public function getAttendances($filters = [])
    {
        $endpoint = "/apps/{$this->appId}/entities/Attendance";
        return $this->makeRequest('GET', $endpoint, $filters);
    }

    /**
     * Obtener una marcación específica por ID
     * @param string $entityId ID de la entidad en la API
     * @return array Datos de la marcación
     */
    public function getAttendance($entityId)
    {
        $endpoint = "/apps/{$this->appId}/entities/Attendance/{$entityId}";
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Actualizar datos de una marcación
     * @param string $entityId ID de la entidad en la API
     * @param array $data Datos a actualizar
     * @return array Respuesta de la API
     */
    public function updateAttendance($entityId, $data)
    {
        $endpoint = "/apps/{$this->appId}/entities/Attendance/{$entityId}";
        return $this->makeRequest('PUT', $endpoint, null, $data);
    }

    /**
     * Probar conexión con la API
     * @return array ['success' => bool, 'message' => string, 'response' => array]
     */
    public function testConnection()
    {
        try {
            $response = $this->getEmployees(['limit' => 1]);
            return [
                'success' => true,
                'message' => 'Conexión exitosa con API',
                'response' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'response' => null
            ];
        }
    }

    /**
     * Realizar request HTTP a la API con retry logic
     * @param string $method GET, POST, PUT, DELETE
     * @param string $endpoint Endpoint de la API
     * @param array|null $queryParams Query parameters para GET
     * @param array|null $bodyData Body data para POST/PUT
     * @return array Respuesta decodificada
     * @throws Exception Si el request falla después de todos los reintentos
     */
    private function makeRequest($method, $endpoint, $queryParams = null, $bodyData = null)
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $attempt++;

                // Construir URL completa
                $url = $this->baseUrl . $endpoint;
                if ($queryParams && !empty($queryParams)) {
                    $url .= '?' . http_build_query($queryParams);
                }

                // Preparar headers
                $headers = [
                    'api_key: ' . $this->apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ];

                // Inicializar cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

                // Configurar método y body
                switch (strtoupper($method)) {
                    case 'POST':
                        curl_setopt($ch, CURLOPT_POST, true);
                        if ($bodyData) {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyData));
                        }
                        break;
                    case 'PUT':
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                        if ($bodyData) {
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyData));
                        }
                        break;
                    case 'DELETE':
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                        break;
                    case 'GET':
                    default:
                        // GET es el default
                        break;
                }

                // Ejecutar request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                // Logging
                if ($this->logEnabled) {
                    $this->logRequest($method, $url, $httpCode, $response, $curlError);
                }

                // Verificar errores de cURL
                if ($response === false) {
                    throw new Exception("cURL Error: {$curlError}");
                }

                // Verificar código HTTP
                if ($httpCode >= 200 && $httpCode < 300) {
                    // Success
                    $decoded = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Error decodificando respuesta JSON: ' . json_last_error_msg());
                    }
                    return $decoded;
                } else {
                    // Error HTTP
                    $errorMessage = $this->parseErrorResponse($response, $httpCode);
                    throw new Exception($errorMessage);
                }

            } catch (Exception $e) {
                $lastException = $e;

                // Si es el último intento, lanzar excepción
                if ($attempt >= $this->maxRetries) {
                    break;
                }

                // Backoff exponencial: esperar 2^attempt segundos
                $waitTime = pow(2, $attempt);
                $this->log("Intento {$attempt} falló. Reintentando en {$waitTime} segundos...");
                sleep($waitTime);
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        throw new Exception(
            "Request falló después de {$this->maxRetries} intentos. Último error: " .
            $lastException->getMessage()
        );
    }

    /**
     * Parsear respuesta de error de la API
     * @param string $response Respuesta cruda
     * @param int $httpCode Código HTTP
     * @return string Mensaje de error formateado
     */
    private function parseErrorResponse($response, $httpCode)
    {
        $decoded = json_decode($response, true);

        if ($decoded && isset($decoded['error'])) {
            return "HTTP {$httpCode}: {$decoded['error']}";
        }

        if ($decoded && isset($decoded['message'])) {
            return "HTTP {$httpCode}: {$decoded['message']}";
        }

        // Mensajes estándar HTTP
        $standardMessages = [
            400 => 'Bad Request - Parámetros inválidos',
            401 => 'Unauthorized - API Key inválida',
            403 => 'Forbidden - Acceso denegado',
            404 => 'Not Found - Recurso no encontrado',
            429 => 'Too Many Requests - Rate limit excedido',
            500 => 'Internal Server Error - Error en el servidor',
            503 => 'Service Unavailable - Servicio no disponible'
        ];

        $message = $standardMessages[$httpCode] ?? "HTTP Error {$httpCode}";
        return $message;
    }

    /**
     * Logging de requests
     * @param string $method Método HTTP
     * @param string $url URL completa
     * @param int $httpCode Código HTTP de respuesta
     * @param string $response Respuesta cruda
     * @param string $error Error de cURL (si existe)
     */
    private function logRequest($method, $url, $httpCode, $response, $error = '')
    {
        $logMessage = sprintf(
            "[%s] %s %s - HTTP %d - Response: %s - Error: %s",
            date('Y-m-d H:i:s'),
            $method,
            $url,
            $httpCode,
            substr($response, 0, 200), // Primeros 200 caracteres
            $error
        );

        $this->log($logMessage);
    }

    /**
     * Escribir en log file
     * @param string $message Mensaje a escribir
     */
    private function log($message)
    {
        $logDir = __DIR__ . '/../../../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/api_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
    }

    /**
     * Configurar timeout del request
     * @param int $seconds Timeout en segundos
     */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }

    /**
     * Configurar número máximo de reintentos
     * @param int $retries Número de reintentos
     */
    public function setMaxRetries($retries)
    {
        $this->maxRetries = $retries;
    }

    /**
     * Habilitar/Deshabilitar logging
     * @param bool $enabled
     */
    public function setLogEnabled($enabled)
    {
        $this->logEnabled = $enabled;
    }

    /**
     * Obtener configuración actual
     * @return array
     */
    public function getConfig()
    {
        return [
            'app_id' => $this->appId,
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'max_retries' => $this->maxRetries,
            'log_enabled' => $this->logEnabled
        ];
    }
}
