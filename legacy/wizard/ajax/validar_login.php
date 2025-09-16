<?php

define ("SYS_LICENCIAS","https://plataforma.innovasoftlatam.com:8080");
$data = array(
    'LoginUser' => 'yes',
    'usuario' => $_POST['usuario'] ?? '',
    'password' => $_POST['password'] ?? ''
);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => SYS_LICENCIAS .'/ajax/user.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/json',
    ),
    CURLOPT_SSL_VERIFYPEER => false // Desactivar solo para desarrollo
));

$response = curl_exec($curl);
if ($response === false) {
    echo 'Error de cURL: ' . curl_error($curl);
} else {
    $data = json_decode($response, true);
    if ($data && isset($data["success"])) {
        if ($data["success"] == "1") {
            $return["success"] = true;
            $return["user_id"] = $data["user_id"];
            $return["email"] = $data["user_email"];
        } else {
            $return["success"] = false;
            $return["message"] = $data["message"];
        }
        echo json_encode($return);
    } else {
        echo 'Error en el formato de la respuesta del servidor.';
    }
}

curl_close($curl);