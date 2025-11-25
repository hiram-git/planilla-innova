<?php
// legacy/verificarLicencia.php 
    if (1 == 1) {
        $query = "SELECT cr.*,n.*,de.nombre_sistema,de.nombre_empresa,de.img_izq, de.img_innova, de.licencia FROM conf_ejecucion_reporte cr "
                . "inner join nomempresa n on n.codigo='".$_POST['empresaSeleccionada']."' ";
        $query .= " left join datos_empresa de on de.cod_empresa = n.codigo   WHERE 1;";

        $resultado_conf_reporte = $empresas->query($query);
        $conf_reporte_lista =  $resultado_conf_reporte->fetch_assoc();

        $curl = curl_init();

        $data_json = json_encode(array(
            'searchLicense' => 'yes',
            'License' => $conf_reporte_lista["licencia"]
        ));

        #curl_setopt($curl, CURLOPT_URL, 'http://108.181.156.39/sqlsrv/ajax/license.php');
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
        $data["success"] = 1;
        //$data["Expiration"] = "2023-12-31";
        if($data["success"] == 1){

            $datetime1 = new DateTime($data["Expiration"]);
            $fecha_expiracion = $datetime1->format("d/m/Y");
            $datetime2 = new DateTime(date("Y-m-d h:i:s"));
            $interval = $datetime1->diff($datetime2);     
            //Si invert es cero, significa que la diferencia entre la fecha de hoy y la licencia es menor a cero
            if($interval->invert == 0){
                $aux=-1;
            
                $smarty->assign("acceso", 0);
                echo "

                <div id='alerta' class='alert alert-danger'>
                    <button class='close' data-close='alert'></button>
                    <span class='text-center'>
                         Su licencia  ".$conf_reporte_lista["licencia"]." expiró el ".$fecha_expiracion.". Por favor, contactar al distribuidor de su preferencia.
                    </span>
                </div>";


                $aux=-1;
            
                $smarty->assign("acceso", 0);
                session_destroy();
            }
            else{

                $smarty->assign("acceso", 1);

                $_SESSION['nombre_sistema'] = $conf_reporte_lista['nombre_sistema']; 
                $_SESSION['nombre_empresa'] = $conf_reporte_lista['nombre_empresa']; 
                $_SESSION["codigo_nomempresa"] = $conf_reporte_lista['codigo'];

                //LEctura de accesos por nivel
                $resultArray = array();
                $query = "SELECT  * from operacion_nivel  WHERE 1;";
                $resultado_nivel_operacion = $empresas->query($query);
                while($rownivel_operacion =  $resultado_nivel_operacion->fetch_assoc()){
                    $resultArray[$rownivel_operacion['operacion_id']] = $rownivel_operacion['nivel_id'];
                }
                $_SESSION['operacion_nivel'] = json_encode($resultArray);
                //fin lectura de accesos por nivel

                $smarty->assign("titulo_pagina", $_SESSION['nombre_sistema']." :: Login");
                if (isset($_POST['empresaSeleccionada'])){
                    $_SESSION['EmpresaFacturacion'] = $conf_reporte_lista['bd'] ; #Base de datos
                    $_SESSION['logo_empresa'] = $conf_reporte_lista['img_izq'];
                    $_SESSION['logo_cliente'] = $conf_reporte_lista['img_innova'];
                    $_SESSION['logo_blanco'] = $conf_reporte_lista['img_innova_blanco'];
                    $_SESSION['conf_reportes_host'] = $conf_reporte_lista['host']; 
                    $_SESSION['conf_reportes_puerto'] = $conf_reporte_lista['puerto']; 
                    $_SESSION['conf_reportes_app'] = $conf_reporte_lista['app']; 
                    $_SESSION['bd_administrativo'] = $conf_reporte_lista['bd'];#Base de datos
                    $_SESSION['bd_contabilidad'] = $conf_reporte_lista['bd_contabilidad'];#Base de datos
                    $_SESSION['bd_nomina'] = $conf_reporte_lista['bd_nomina'];#Base de datos
                    $_SESSION['nombre_empresa'] = $conf_reporte_lista['nombre'];#Base de datos
                    $_SESSION['admis_activo'] = $conf_reporte_lista['admis_activo'];#Base de datos
                    $_SESSION['contab_activo'] = $conf_reporte_lista['contab_activo'];#Base de datos
                    $_SESSION['nomina_activo'] = $conf_reporte_lista['nomina_activo'];#Base de datos    

                     $query = "SELECT * FROM ".$conf_reporte_lista['bd'].".parametros_generales WHERE 1;";
                     $resparam = $empresas->query($query);
                     $params =  $resparam->fetch_assoc(); 
                     $_SESSION['sucursal'] = $params['sucursal_id'];         
                }
                $permiso = $permisos->getPermisosUsuarios($_SESSION['cod_usuario']);    
                $modulos = $permisos->getPermisosModulo();
           
                //header("Location: ../../../app/index_entrada_1.php");
                header("Location: ../principal/?opt_menu=54");



            }
        }else 
        {
            echo "

                <div id='alerta' class='alert alert-danger'>
                    <button class='close' data-close='alert'></button>
                    <span class='text-center'>
                        Su licencia ha terminado.
                    </span>
                </div>";
                
            $aux=-1;
            $smarty->assign("acceso", 0);
        }
    }