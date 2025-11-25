<?php
class registro_empresa {	
    
	public static function limpiarString($string)
	{
		  $textoLimpio = preg_replace('([^A-Za-z0-9])', '', $string);	     					
		  return $textoLimpio;
	}
	public static function nombrar_base_datos($string)
	{
		$empresa = filter_var(self::limpiarString($string), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
		if(SYS_TEST == "1")
			$empresa = "db_test".$empresa;
		else
			$empresa = "db".$empresa;

		$empresa = strtolower($empresa);
		return $empresa;
	}
	public static function nombrar_usuario($string)
	{
		$empresa = filter_var(self::limpiarString($string), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
		$empresa = strtolower($empresa);
		return $empresa;
	}
    
	public static function onSave( $datos ){        
		//$db=AMAXONIA::DBController();     
		$script_path = AMAXONIA::path()."/data/innova_administrativo.sql";
		$script_sp = AMAXONIA::path()."/data/sp_administrativo.sql";
		if(!$datos["nombre_empresa"])
		return array("success"=>false,"message"=>"Error. El campo Nombre o Razón Social se encuentra vacío.");

		if(!$datos["rif"])
		return array("success"=>false,"message"=>"Error. El campo Cédula o RUC se encuentra vacío.");

		//verificar si existe el bodega
		/*$existe=$db->Execute("SELECT count(*) as total FROM almacen WHERE descripcion='$descripcion' AND cod_almacen<>'$id'");
		if($existe[0]["total"]>0)
		return array("success"=>false,"message"=>"Error. El bodega $descripcion ya existe.");*/
		$licencia = self::generarLicencia();
		$NOMBRE_BD = self::nombrar_base_datos( $licencia );
		$nombre_generado = $datos["nombre_empresa"].$licencia;
		//$nombre_usuario = self::nombrar_usuario( $nombre_generado );
		$nombre_usuario = $datos["usuario_cloud"];
		$Conf = new mysqli(DB_HOST, DB_USUARIO, DB_CLAVE, SELECTRA_CONF_PYME, DB_PUERTO);
		$sql_buscar_usuario = "SELECT usuario from ".SELECTRA_CONF_PYME.".usuarios WHERE usuario = '".$datos["usuario_cloud"]."';";
		$res_buscar_usuario = $Conf -> query($sql_buscar_usuario);
		
		if($res_buscar_usuario-> num_rows > 0)
			return array("success"=>false,"message"=>"Error. Usuario de cloud ya existe.");

		$datos_login["usuario"] = $datos["usuario"];
		$datos_login["password"] = $datos["password"];

		$comando_bd = "CREATE DATABASE ".$NOMBRE_BD." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci; ";
		//$db->Execute($comando_bd,AMAXONIA::REPLICAR);
		$Conf -> query($comando_bd);
		$comando = "/usr/bin/mysql -h ".DB_HOST." -u ".DB_USUARIO." -p".DB_CLAVE." $NOMBRE_BD < $script_path 2>&1";
		exec($comando, $output);
		$comando_sp = "/usr/bin/mysql -h ".DB_HOST." -u ".DB_USUARIO." -p".DB_CLAVE." $NOMBRE_BD < $script_sp 2>&1";
		exec($comando_sp, $output);

		$buscar_ultima_empresa = "SELECT (MAX(codigo)+1) maximo from ".SELECTRA_CONF_PYME.".nomempresa  ";
		$res_max_empresa = $Conf -> query($buscar_ultima_empresa);
		$max_empresa = $res_max_empresa->fetch_array();
		//crear usuario
		$buscar_ultimo_usuario = "SELECT (MAX(cod_usuario)+1) maximo from  ".SELECTRA_CONF_PYME.".usuarios; ";
		$res_max_user = $Conf -> query($buscar_ultimo_usuario);
		$max_user = $res_max_user->fetch_array();
		$maxuser = $max_user['maximo'];

		$emp=  $datos["nombre_empresa"];
		$ruc = $datos["rif"];		$clave = md5("123");
		$fecha_inicio =  date("Y-m-d");
		$expiration = strtotime("+30 days", strtotime($fecha_inicio));

		//$db->Update("innova_conf.nomempresa",$data,"codigo='$id'");
		
		/*$sql = file_get_contents($script_path);*/
            //Modificar registro

		// execute multi query swwww

		$crear_empresa = "INSERT INTO ".SELECTRA_CONF_PYME.".nomempresa (codigo,nombre,bd, bd_contabilidad, bd_nomina, nomina_activo) VALUES ('{$max_empresa[maximo]}','{$emp}','{$NOMBRE_BD}','{$NOMBRE_BD}','{$NOMBRE_BD}', '0')";
		$Conf -> query($crear_empresa);		
		
		$buscar_empresa_nueva = "SELECT codigo from ".SELECTRA_CONF_PYME.".nomempresa  WHERE bd = '{$NOMBRE_BD}';";
		$res_empresa_nueva = $Conf -> query($buscar_empresa_nueva);
		$empresa_nueva = $res_empresa_nueva->fetch_array();

		//crear datos_empresa
		$crear_datos_empresa = "INSERT INTO ".SELECTRA_CONF_PYME.".datos_empresa (cod_empresa,nombre_empresa,nombre_sistema,cod_moneda,pais_id, licencia) 
		VALUES ('{$empresa_nueva[codigo]}','{$emp}','{$emp}','170','170','{$licencia}');";
		$Conf -> query($crear_datos_empresa);
		
		//Se busca el email del usuario
		$data_license =  self::onLoginUser($datos_login);
		$email = $data_license["user_email"];
		$crear_usuario = "INSERT INTO ".SELECTRA_CONF_PYME.".usuarios (cod_usuario,cod_empresas, nombreyapellido, clave, email, usuario, status,nivel_id,clave_caducidad)
		VALUES ('{$maxuser}','{$empresa_nueva[codigo]}','{$emp}','{$clave}','{$email}','{$nombre_usuario}','A','5','180');";
		$Conf -> query($crear_usuario);

		$crear_modulos_usuario = "INSERT INTO ".SELECTRA_CONF_PYME.".modulo_usuario ( cod_usuario, cod_modulo, permitido) VALUES($maxuser,1,1), ($maxuser,3,1), ($maxuser,5,1), ($maxuser,7,1), ($maxuser,8,0), ($maxuser,31,1),($maxuser,61,1),($maxuser,67,1),($maxuser,79,1),($maxuser,109,1),($maxuser,156,1),($maxuser,185,1),($maxuser,216,1),($maxuser,313,1),($maxuser,349,1),($maxuser,350,1),($maxuser,354,1),($maxuser,355,1),($maxuser,357,1),($maxuser,360,0);";
		
		$result=$Conf -> query($crear_modulos_usuario);
		
		if(!$result){
			return array("success"=>false, "message"=>"Error al crear la empresa");
        }


		$data_license["usuario"] = $datos_login["usuario"];
		$data_license["password"] = $datos_login["password"];
		$data_license["licencia"] = $licencia;
		$data_license["final_user"] = $emp;
		$data_license["nombre_usuario"] = $nombre_usuario;
		$data_license["Product"] = "CLOUDLITE";
		$data_license["FirstActivation"] = $fecha_inicio;
	    $expiration = strtotime("+30 days", strtotime($fecha_inicio));
		$data_license["Expiration"] = date("Y-m-d",$expiration);

		//crear datos_empresa
		$UPDATE_fecha_expiration = "UPDATE ".SELECTRA_CONF_PYME.".datos_empresa set fecha_expiracion = '".$data_license["Expiration"]."' WHERE cod_empresa = '{$empresa_nueva[codigo]}';";
		$Conf -> query($UPDATE_fecha_expiration);

		//Se crea la licencia en plataforma
		$license = self::onLicense($data_license);
		

		$sql_vendedor =  "UPDATE {$NOMBRE_BD}.vendedor SET cod_usuarios = '$maxuser';";
		$Conf->query($sql_vendedor);

		$sql_parametros_generales =  "UPDATE {$NOMBRE_BD}.parametros_generales SET nombre_empresa = '{$emp}', rif= '{$ruc}';";
		$Conf->query($sql_parametros_generales);

		if($license["success"]== "1"){

			$return["success"]=true;
	        $return["message"]="Empresa Creada con éxito.";
	        $return["UPDATE_fecha_expiration"]= $UPDATE_fecha_expiration;
	        $return["UPDATE_parametros_generales"]= $sql_parametros_generales;
	        $return["crear_modulos_usuario"]= $crear_modulos_usuario;
	        //$return["shell"]= $output;
	        return $return;
		}
		else{
			return $license;
		}
	}
	public static function onLicense( $datos ){
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

        $api_sqlserver = SYS_LICENCIAS .'/ajax/license.php';
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
			
			$CORREO = self::onEnvioCorreo($data_json,$datos["nombre_usuario"] );

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
    
	public static function generarLicencia( ){
		$curl = curl_init();

		$sieteDigitos = rand(100000,999999);
		$licencia="CLOUD".$sieteDigitos;
        $data_json = json_encode(array(
            'searchLicense' => 'yes',
            'License' => $licencia
        ));

        $api_sqlserver = SYS_LICENCIAS .'/ajax/user.php';
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
        	self::generarLicencia();
		}
		else{
	        return $licencia;
		}
	}

}