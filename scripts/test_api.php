<?php
// Función para obtener entidades de asistencia de una fecha específica
function fetchAttendanceByDate($dateString = '2025-11-11') {
    // Construye el rango: inicio del día y fin del día (hasta 23:59:59)
    $startOfDay = $dateString . 'T00:00:00';
    $endOfDay = $dateString . 'T23:59:59'; // O usa lt al día siguiente para precisión

    $filter = [
        'actual_timestamp' => $dateString
    ];

    $filterJson = json_encode($filter);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al codificar el filtro JSON: " . json_last_error_msg());
    }
    
    $filterEncoded = urlencode($filterJson);

     $url = "https://app.api.com/api/apps/68dd9181444436f4bd157e1d/entities/Attendance?filter={$filterEncoded}"; 

    // Inicializa cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'api_key: 40162908d71941b98636b38106be556e', // Usa una variable o obtén dinámicamente si es posible
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para desarrollo; actívalo en producción

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("Error cURL: " . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception("Error HTTP: {$httpCode} - Respuesta: " . $response);
    }

    $data = json_decode($response, true); // Decodifica como array asociativo
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }

    // Opcional: imprimir para depuración
    print_r($data);

    return $data;
}

// Ejemplo de uso
try {
    $result = fetchAttendanceByDate('2025-11-11');
    echo "Datos obtenidos exitosamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>