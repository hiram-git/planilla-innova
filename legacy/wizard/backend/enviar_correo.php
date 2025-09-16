<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

class Correo {

    private $mail;
    private $url_licencias;

    public function __construct() {
        $this->mail = new PHPMailer(true);
    }
	public function generarLicencia( $url_licencias )
	{
		$curl = curl_init();

		$licencia=sprintf("HT%09d", rand(100000000, 9999999999));;
        $data_json = json_encode(array(
            'searchLicense' => 'yes',
            'License' => $licencia
        ));

        $api_sqlserver = $url_licencias .'/ajax/user.php';
		curl_setopt($curl, CURLOPT_URL, $api_sqlserver);
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

        curl_close($curl);
		$data = json_decode( $response , true);

		if($data["success"] == "1"){
        	generarLicencia();
		}
		else{
	        return $licencia;
		}
	}

	public function onLicense( $datos, $url_licencias ){
		$curl = curl_init();
		
        $data_json = json_encode(array(
            'registerLicense' => 'yes',
            'License' => $datos['licencia'],
            'RUC' => $datos['empresa_ruc'],
            'Buyer' => $datos['user_name'],
            'Company' => $datos['empresa_name'],
            'Email' => $datos['user_email'],
            //'Email' => "lorogon@gmail.com",
            'Phone' => $datos['user_contacto'],
            'Expiration' => $datos['Expiration'],
            'MaxActivations' => "50",
            'CurActivations' => "1",
            'SaintLicense' => $datos['licencia'],
            'State' => "XSU_TRIAL",
            'CurActCompleted' => "1",
            'UniqueTable' => "1",
            'FinalUser' => $datos['final_user'],
            'FirstActivation' => $datos['FirstActivation'],
            'Country' => $datos['user_pais'],
            'Product' => $datos['Product'],
            'Reactivation' => null,
            'HasCoronaTest' => "1",
            'IdAnalitica' => null,
            'FinalUserRUC' =>null
        ));

        $api_sqlserver = $url_licencias.'/ajax/license.php';
		curl_setopt($curl, CURLOPT_URL, $api_sqlserver);

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

        curl_close($curl);
		$data = json_decode( $response , true);


		if($data["success"] == "1"){
			
			//$CORREO = self::onEnvioCorreo($data_json,$datos["nombre_usuario"] );

			$return["success"]=1;
			$return["message"]="Empresa Activada exitosamente. Revise su bandeja de entrada con las credenciales de autenticación";
			return $return;
		}

		else{
			$return["success"]=0;
			$return["message"]="Error al registrar la empresa";
	        return $return;

		}

	}
	public function onLogin( $datos, $url_licencias ){
		$curl = curl_init();
        $data_json = json_encode(array(
            'LoginUser' => 'yes',
            'usuario' => $datos['usuario'],
            'password' => $datos['password']
        ));
        $api_sqlserver = $url_licencias.'/ajax/user.php';
		curl_setopt($curl, CURLOPT_URL, $api_sqlserver);
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

        curl_close($curl);
		$data = json_decode( $response , true);

		if($data["success"] == "1"){
			$return["success"]=true;
        	return $return;
		}

		else{
			$return["success"]=false;
			$return["message"]=$data["message"];
	        return $return;

		}

	}
	public function onLoginUser( $datos, $url_licencias ){
        $data_json = json_encode(array(
            'LoginUser' => 'yes',
            'usuario' => $datos['usuario'],
            'password' => $datos['password']
        ));

        $api_sqlserver = $url_licencias.'/ajax/user.php';
        $curl = curl_init();

		curl_setopt($curl, CURLOPT_URL, $api_sqlserver);

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

        curl_close($curl);
		$data = json_decode( $response , true);

		if($data["success"] == "1"){

        	return $data;
		}

		else{
			$return["success"]=false;
			$return["message"]=$data["message"];
	        return $return;

		}
	}

    public function enviarCorreoHoteles($config) {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = $config['smtp_host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $config['smtp_username'];
            $this->mail->Password = $config['smtp_password'];
            $this->mail->SMTPSecure = $config['smtp_secure'];
            $this->mail->Port = $config['smtp_port'];
            $this->mail->isHTML(true);

            $this->mail->setFrom($config['from_email'], $config['from_name']);

            $this->mail->addAddress($config['to_email'], $config['to_name']);
            //$this->mail->addAddress("hiram_loreto@yahoo.com","PRUEBA HIRAM");
            //$this->mail->addAddress("lorogon@gmail.com","PRUEBA HIRAM");
			$html = "<!DOCTYPE html>
			<html>
			<head>
			    <meta charset=\"UTF-8\">
			    <title>INNOVAWEB - HOTELES</title>
			    <style>
			        body {
			            font-family: Arial, sans-serif;
			            margin: 0;
			            padding: 0;
			            background-color: #7f7f7f; /* Gray background */
			        }
			        .container {
			            width: 100%;
			            max-width: 800px;
			            margin: 20px auto;
			            background-color: #FFFFFF; /* White content background */
			            border: 1px solid #E0E0E0;
			            padding: 40px;
			            box-sizing: border-box;
			            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
			        }
			        .header {
			            display: flex;
			            align-items: center;
			            background-color: #e85115; /* Orange header */
			            color: #FFFFFF; /* White text */
			            padding: 20px;
			            border-radius: 5px 5px 0 0; /* Rounded top corners */
			        }
			        .header img {
			            max-height: 60px;
			            margin-right: 30px;
			        }
			        .header h1 {
			            flex: 1;
			            margin: 0;
			            font-size: 28px;
			            font-weight: bold;
			        }
			        .content {
			            padding: 30px 0;
			            font-size: 16px;
			            color: #7f7f7f; /* Gray text */
			        }
			        .button {
			            display: inline-block;
			            padding: 12px 24px;
			            margin: 20px 0;
			            background-color: #e85115; /* Orange button */
			            color: #FFFFFF; /* White text for button */
			            text-decoration: none;
			            border-radius: 5px;
			            font-size: 16px;
			            font-weight: bold;
			            transition: background-color 0.3s; /* Smooth transition */
			        }
			        .button:hover {
			            background-color: #c73e12; /* Darker orange on hover */
			        }
			        .footer {
			            text-align: center;
			            font-size: 14px;
			            color: #7f7f7f; /* Gray footer text */
			            padding: 20px 0;
			            border-top: 1px solid #E0E0E0;
			        }
			    </style>
			</head>
			<body>
			    <div class=\"container\">
			        <div class=\"header\">
			            <img src=\"https://innovasoftlatam.com/wp-content/uploads/2024/02/LOGO-INNOVASOFT-blanco.png\" alt=\"Logo\">
			            <h1>INNOVASOFT HOTELES</h1>
			        </div>
			        <div class=\"content\">
			            <p>Estimado Cliente,</p>
			            <p>Nos complace informarle que el módulo Innova Hoteles ha sido activado para su empresa.</p>
			            <p><b>Cliente</b>: " . $config['datos']['Company'] . "<p>
			            <b>Id Sistema</b>: " . $config['datos']['License'] . "</p>
			            <p>Para acceder al sistema, por favor utilice estas credenciales.</p>
			            <p><br>
			             Admin<br>
							- Email: admin@demo.com<br>
							- Password: admin.demo<br><p>

						Manager<br>
							- Email: manager@demo.com<br>
							- Password: manager.demo<br><p>

						Reception<br>
							- Email: reception@demo.com<br>
							- Password: reception.demo<br><p>
			            <p><a href=\"https://hoteles.innovasoftlatam.com:8081/\" class=\"button\">Ingrese aquí</a></p>
			            <p>Agradecemos su confianza en nuestros servicios y quedamos a su disposición para cualquier consulta adicional.</p>
			            <p>Atentamente,</p>
			            <p>El equipo de <b>Innova Soft</b></p>
			        </div>
			        <div class=\"footer\">
			            © " . date('Y') . " INNOVAWEB. Todos los derechos reservados.
			        </div>
			    </div>
			</body>
			</html>";

            $this->mail->Subject = utf8_decode($config['subject']);
            $this->mail->Body = utf8_decode($html);

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
