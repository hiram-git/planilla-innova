<?php


$query = "SELECT * FROM USUARIOS_EMPRESAS WHERE ID_SISTEMA = '{$data["ID_SISTEMA"]}' ";

$resultado_conf_reporte = $empresas->query($query);
$conf_reporte_lista =  $resultado_conf_reporte->fetch_assoc();

$curl = curl_init();

$data_json = json_encode(array(
    'searchLicense' => 'yes',
    'License' => $conf_reporte_lista["licencia"]
));

curl_setopt($curl, CURLOPT_URL, 'https://plataforma.innovasoftlatam.com:8080/ajax/license.php');
curl_setopt(
    $curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
    )
);
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($curl);
//echo var_dump($response);exit;

curl_close($curl);
$data = json_decode( $response , true);
if ($response === false) {
    echo 'Error de cURL: ' . curl_error($curl);
} else {
    $data = json_decode($response, true);
    if ($data && isset($data["success"])) {
        if ($data["success"] == "1") {
            $return["success"] = true;
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