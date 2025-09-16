<?php
// backend/guardar.php
header('Content-Type: application/json');
include 'db.php'; 
include 'config/server.php'; 

// Leer el cuerpo de la solicitud POST como JSON
$input = json_decode(file_get_contents('php://input'), true);
//$rutaArchivoSQL = "./api/script/crear_hotel.sql";
$rutaArchivoSQL = "/home/laravel83/hotel/releases/9/artisan";
// Ejemplo de uso en tu API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener parámetros del JSON
    $nombreEmpresa = $input["empresa"]["nombre"] ?? null;
    $direccion = "";
    $num_fiscal =  $input["empresa"]["ruc"] ?? null;
    $email = $input["empresa"]["email"] ?? null;

    $user = $input["usuario"]["user"];
    $password = $input["usuario"]["password"];

    // Validaciones
    if (empty($rutaArchivoSQL) || !file_exists($rutaArchivoSQL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Ruta del archivo SQL no válida o no encontrada', 'code' => 400]);
        exit;
    }
    if (empty($nombreEmpresa)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'El nombre de la empresa es requerido', 'code' => 400]);
        exit;
    }
    if ( empty($user) || empty($password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos de empresa o usuario', 'code' => 400]);
        exit;
    }
    try 
    {

        $fecha_inicio =  date("Y-m-d");
        $expiration = strtotime("+30 days", strtotime($fecha_inicio));

        $datos_login = [];
        $datos_login["usuario"] = $user;
        $datos_login["password"] = $password;

        $data_license = [];

        include_once "enviar_correo.php";

        $correo = new Correo();
        //$correo->url_licencias = $dbConfig["SYS_LICENCIAS"];

        $data_license =  $correo->onLoginUser( $datos_login, $dbConfig["SYS_LICENCIAS"] );        

        $data_license["usuario"] = $datos_login["usuario"];
        $data_license["password"] = $datos_login["password"];
        $data_license["licencia"] = $correo->generarLicencia( $dbConfig["SYS_LICENCIAS"] );
        $data_license["final_user"] = $nombreEmpresa;
        $data_license["Product"] = "HT";
        $data_license["FirstActivation"] = $fecha_inicio;
        $expiration = strtotime("+30 days", strtotime($fecha_inicio));
        $data_license["Expiration"] = date("Y-m-d",$expiration);


        $license = $correo->onLicense( $data_license, $dbConfig["SYS_LICENCIAS"] );

        if($license){
            // Instanciar la clase

            $usuarioNombre = "001";
            $usuarioClave = "12345";

            $restorer = new DatabaseRestorer();
            
            $token = $restorer->generateUUID();
            // Ejecutar la restauración
            $nombreBaseDatos = $restorer->generarNombreBaseDatos($nombreEmpresa);
            $restorer->restoreDatabase($rutaArchivoSQL, $data_license["licencia"]);

            // Obtener el objeto PDO conectado a la nueva base de datos
            $pdo = $restorer->getPdo();

            /*// Actualizar o insertar en BASEEMPRESA
            $stmt = $pdo->prepare("SELECT CONTROL FROM BASEEMPRESA WHERE CONTROL = 1;");
            $stmt->execute();
            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare("UPDATE BASEEMPRESA SET NOMBRE = :nombre, DIRECC1 = :direccion, NUMFISCAL = :numero_fiscal WHERE CONTROL = '1';");
            } else {
                $stmt = $pdo->prepare("INSERT INTO BASEEMPRESA (NOMBRE, DIRECC1, NUMFISCAL) VALUES (:nombre, :direccion, :numero_fiscal);");
            }
            $stmt->execute([':nombre' => $nombreBaseDatos,':direccion' => $direccion, ':numero_fiscal' => $num_fiscal]); 

            // Actualizar o insertar en BASEUSUARIOS
            $stmt = $pdo->prepare("SELECT CODUSER FROM BASEUSUARIOS WHERE CODUSER = '001';");
            $stmt->execute();
            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare("UPDATE BASEUSUARIOS SET CODUSER = :nombre, CLAVE = :clave WHERE CODUSER = '001';");
            } else {
                $stmt = $pdo->prepare("INSERT INTO BASEUSUARIOS ( CODUSER, CLAVE ) VALUES  (:nombre, :clave);");
            }
            $stmt->execute([
                ':nombre' => $usuarioNombre,
                ':clave' => $usuarioClave
            ]);*/

            $restorer->setPDO($dbConfig); // Configurar la conexión
            $pdo = $restorer->getPdo();

            /// Generar token
            $token = $restorer->generateUUID();

            // Consulta SQL
            $sql = "INSERT INTO Empresas (nombre, numFiscal, direccion, email, token, nombreBaseDatos, licencia, licenciaVencimiento) 
                    VALUES (?, ?, ?, ?, ?, ?,?, ?)";
            $params = array(
                $nombreEmpresa,
                $num_fiscal,
                $direccion,
                $email,
                $token,
                $data_license["licencia"],
                $data_license["licencia"],
                $data_license["Expiration"]

            );
            $stmt = $pdo->prepare($sql);

            $stmt->execute($params);

            $emailConfig = array(
                'smtp_host'     => 'smtp.zeptomail.com',
                'smtp_username' => 'plataforma@innovasoftlatam.com',
                'smtp_password' => 'esQP5Ws8ZAjp',
                'smtp_secure'   => 'ssl',
                'smtp_port'     => 465,
                'from_email'    => 'plataforma@innovasoftlatam.com',
                'from_name'     => 'INNOVASOFT - NOTIFICACIONES',
                'to_email'      => $email,
                //'to_email'      => "lorogon@gmail.com",
                'to_name'       => $nombreEmpresa,
                'subject'       => 'INNOVA HOTELES | Creación Hotel | '.$nombreEmpresa,
                'datos'         => array(
                    'FinalUser' => "usuario",
                    'License'   => $data_license["licencia"],
                    'Company'   => $nombreEmpresa,
                    'Token'     => $token
                ),
                'nombre_usuario' => $user
            );

            if ($correo->enviarCorreoHoteles($emailConfig)) {
            }

            // Respuesta exitosa
            http_response_code(201); // Created (se creó una nueva base de datos)
            echo json_encode([
                'status' => 'success',
                'message' => 'HOTEL CREADO CON ÉXITO. Por favor, revise su bandeja de entrada para con las credenciales de autenticación.',
                'data' => array(
                    "num_fiscal"=> $num_fiscal,
                    "direccion"=> $direccion,
                    "email"=> $email,
                    "usuarioNombre"=> $usuarioNombre,
                    "token"=> $token
                ),
                'code' => 201
            ]);

        }
    } catch (Exception $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'code' => 500
        ]);
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido, use POST',
        'code' => 405
    ]);
}
?>