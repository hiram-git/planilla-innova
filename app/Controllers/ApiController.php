<?php

namespace App\Controllers;

use App\Core\Controller;

class ApiController extends Controller 
{
    /**
     * Obtener tipos de planilla para el dropdown del navbar
     */
    public function getPayrollTypes()
    {
        header('Content-Type: application/json');
        
        try {
            $tipoPlanilla = $this->model('TipoPlanilla');
            $tipos = $tipoPlanilla->getActive();
            
            if ($tipos) {
                echo json_encode([
                    'success' => true,
                    'data' => $tipos
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'data' => []
                ]);
            }
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al cargar tipos de planilla'
            ]);
        }
    }
}