<?php

namespace App\Controllers;

/**
 * Controlador de Reportes de Estimados Anuales
 * Extiende ReportController para mantener acceso a métodos compartidos
 */
class EstimateReportController extends ReportController
{
    /**
     * Estimado anual de liquidaciones
     * Calcula el monto estimado de liquidación para todos los empleados activos
     * sin que estén dados de baja
     */
    public function estimadoAnualLiquidaciones()
    {
        try {
            $this->requireAuth();

            // Obtener todos los empleados activos (no terminados)
            $sql = "SELECT
                        e.id,
                        e.employee_id,
                        e.document_id,
                        e.firstname,
                        e.lastname,
                        e.fecha_ingreso,
                        e.sueldo_individual,
                        e.tipo_planilla_id,
                        IFNULL(c.nombre, 'Sin Cargo') as position_name,
                        DATEDIFF(CURDATE(), e.fecha_ingreso) / 365 as years_worked
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    WHERE e.id NOT IN (
                        SELECT employee_id FROM employee_terminations WHERE status IN ('CALCULADA', 'PROCESADA')
                    )
                    AND e.fecha_ingreso IS NOT NULL
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $employees = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($employees)) {
                $_SESSION['error'] = 'No hay empleados activos para calcular estimado';
                $this->redirect('/panel/reports');
                return;
            }

            // Obtener calculadora de liquidación (está en Services, no en Models)
            $calculatorModel = new \App\Services\PlanillaConceptCalculator();

            // Obtener frecuencia de liquidación dinámicamente
            $liquidationController = new \App\Controllers\LiquidationController();
            $liquidation_frequency_id = $liquidationController->getLiquidationFrequencyId();

            if (!$liquidation_frequency_id) {
                $_SESSION['error'] = 'No se pudo obtener la frecuencia de liquidación';
                $this->redirect('/panel/reports');
                return;
            }

            // Array para almacenar cálculos de todos los empleados
            $estimates = [];
            $total_general_asignaciones = 0;
            $total_general_deducciones = 0;
            $total_general_neto = 0;

            foreach ($employees as $employee) {
                // Simular variables de liquidación para el empleado
                // Usar fecha actual como fecha de terminación hipotética
                $today = date('Y-m-d');

                // Configurar calculadora con variables del empleado
                // Nota: No existe termination_id, así que pasamos NULL o 0
                $calculatorModel->setVariablesColaborador(
                    $employee['id'],
                    $employee['tipo_planilla_id'] ? explode(',', $employee['tipo_planilla_id'])[0] : 1,
                    $today,
                    $today
                );

                // Establecer variables específicas de liquidación manualmente
                $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
                $fecha_simulada_terminacion = new \DateTime($today);
                $total_years = $fecha_ingreso->diff($fecha_simulada_terminacion)->y;
                $total_months = $fecha_ingreso->diff($fecha_simulada_terminacion)->m;
                $total_days = $fecha_ingreso->diff($fecha_simulada_terminacion)->d;

                // Calcular total de días trabajados
                $dias_trabajados = $fecha_ingreso->diff($fecha_simulada_terminacion)->days;

                // Establecer solo variables numéricas necesarias para liquidación
                $calculatorModel->setVariable('ANOS_TRABAJADOS', (float)$total_years);
                $calculatorModel->setVariable('MESES_TRABAJADOS', (float)$total_months);
                $calculatorModel->setVariable('DIAS_TRABAJADOS', (float)$dias_trabajados);
                $calculatorModel->setVariable('DIAS_PREAVISO', 0); // Estimado sin preaviso

                // Establecer salarios calculados para liquidaciones
                $calculatorModel->setVariable('SUELDO_SEMANAL', (float)($employee['sueldo_individual'] / 4.33));
                $calculatorModel->setVariable('SUELDO_MENSUAL', (float)$employee['sueldo_individual']);
                $calculatorModel->setVariable('SUELDO_DIARIO', (float)($employee['sueldo_individual'] / 30));

                // Obtener conceptos de liquidación
                $employee_tipo_planilla_ids = !empty($employee['tipo_planilla_id'])
                    ? explode(',', $employee['tipo_planilla_id'])
                    : [];

                if (!empty($employee_tipo_planilla_ids)) {
                    $placeholders = implode(',', array_fill(0, count($employee_tipo_planilla_ids), '?'));
                    $sql = "SELECT DISTINCT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                            FROM concepto c
                            INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                            INNER JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id
                            WHERE cf.frecuencia_id = ?
                            AND ctp.tipo_planilla_id IN ($placeholders)
                            ORDER BY c.tipo_concepto, c.concepto";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute(array_merge([$liquidation_frequency_id], $employee_tipo_planilla_ids));
                } else {
                    $sql = "SELECT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                            FROM concepto c
                            INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                            WHERE cf.frecuencia_id = ?
                            ORDER BY c.tipo_concepto, c.concepto";

                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$liquidation_frequency_id]);
                }

                $concepts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                $employee_calculations = [];
                $total_asignaciones = 0;
                $total_deducciones = 0;

                foreach ($concepts as $concept) {
                    try {
                        // Calcular monto según fórmula
                        $amount = $calculatorModel->evaluarFormula($concept['formula']);

                        if ($concept['tipo_concepto'] === 'ASIGNACION' || $concept['tipo_concepto'] === 'A') {
                            $total_asignaciones += $amount;
                        } elseif ($concept['tipo_concepto'] === 'DEDUCCION' || $concept['tipo_concepto'] === 'D') {
                            $total_deducciones += $amount;
                        }

                        $employee_calculations[] = [
                            'concept_code' => $concept['concepto'],
                            'concept_description' => $concept['descripcion'],
                            'amount' => $amount,
                            'type' => $concept['tipo_concepto']
                        ];
                    } catch (\Exception $e) {
                        error_log("Error calculando concepto {$concept['concepto']} para empleado {$employee['id']}: " . $e->getMessage());
                        // Continuar con el siguiente concepto
                    }
                }

                $neto = $total_asignaciones - $total_deducciones;

                $estimates[] = [
                    'employee' => $employee,
                    'calculations' => $employee_calculations,
                    'total_asignaciones' => $total_asignaciones,
                    'total_deducciones' => $total_deducciones,
                    'neto' => $neto
                ];

                $total_general_asignaciones += $total_asignaciones;
                $total_general_deducciones += $total_deducciones;
                $total_general_neto += $neto;
            }

            // Preparar datos para la vista
            $companyInfo = $this->getCompanyInfo();
            $signatures = $this->companyModel->getSignaturesForReports();

            $data = [
                'title' => 'Estimado Anual de Liquidaciones',
                'page_title' => 'Estimado Anual de Liquidaciones',
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Reportes', 'url' => '/panel/reports'],
                    ['name' => 'Estimado Anual Liquidaciones', 'url' => '#']
                ],
                'estimates' => $estimates,
                'total_general_asignaciones' => $total_general_asignaciones,
                'total_general_deducciones' => $total_general_deducciones,
                'total_general_neto' => $total_general_neto,
                'total_employees' => count($employees),
                'fecha_estimado' => date('d/m/Y'),
                'companyInfo' => $companyInfo,
                'signatures' => $signatures
            ];

            $this->view('admin/reports/estimado_liquidaciones', $data);

        } catch (\Exception $e) {
            error_log("Error en estimadoAnualLiquidaciones: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar estimado de liquidaciones: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }

    /**
     * Estimado anual de planillas
     * Proyecta el costo mensual de planillas basándose en la última planilla procesada
     * y lo proyecta a 12 meses
     */
    public function estimadoAnualPlanillas()
    {
        try {
            $this->requireAuth();

            // Obtener tipo de planilla del filtro (si existe)
            $tipo_planilla_id = isset($_GET['tipo_planilla_id']) ? (int)$_GET['tipo_planilla_id'] : null;

            // Obtener tipos de planilla disponibles
            $sql = "SELECT id, nombre FROM tipos_planilla ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $tipos_planilla = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Obtener todas las planillas procesadas del año actual o último año disponible
            $where_clause = "pc.estado = 'PROCESADA'";
            $params = [];

            if ($tipo_planilla_id) {
                $where_clause .= " AND pc.tipo_planilla_id = ?";
                $params[] = $tipo_planilla_id;
            }

            // Obtener la última planilla procesada para usar como base
            $sql = "SELECT
                        pc.id,
                        pc.descripcion,
                        pc.fecha_desde,
                        pc.fecha_hasta,
                        tp.nombre as tipo_planilla_nombre,
                        f.nombre as frecuencia_nombre,
                        SUM(CASE WHEN c.tipo_concepto IN ('ASIGNACION', 'A') THEN pd.monto ELSE 0 END) as total_asignaciones,
                        SUM(CASE WHEN c.tipo_concepto IN ('DEDUCCION', 'D') THEN pd.monto ELSE 0 END) as total_deducciones,
                        COUNT(DISTINCT pd.employee_id) as total_empleados
                    FROM planilla_cabecera pc
                    INNER JOIN planilla_detalle pd ON pc.id = pd.planilla_cabecera_id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE $where_clause
                    GROUP BY pc.id
                    ORDER BY pc.fecha_hasta DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ultima_planilla = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$ultima_planilla) {
                $_SESSION['error'] = 'No hay planillas procesadas para generar el estimado';
                $this->redirect('/panel/reports');
                return;
            }

            // Calcular el costo promedio mensual basado en la última planilla
            $costo_mensual_base = $ultima_planilla['total_asignaciones'] - $ultima_planilla['total_deducciones'];

            // Proyectar a 12 meses
            $meses = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];

            $proyeccion_mensual = [];
            $total_anual_asignaciones = 0;
            $total_anual_deducciones = 0;
            $total_anual_neto = 0;

            foreach ($meses as $num_mes => $nombre_mes) {
                // Usar el costo de la última planilla como base
                $asignaciones_mes = $ultima_planilla['total_asignaciones'];
                $deducciones_mes = $ultima_planilla['total_deducciones'];
                $neto_mes = $asignaciones_mes - $deducciones_mes;

                $proyeccion_mensual[] = [
                    'mes_numero' => $num_mes,
                    'mes_nombre' => $nombre_mes,
                    'asignaciones' => $asignaciones_mes,
                    'deducciones' => $deducciones_mes,
                    'neto' => $neto_mes,
                    'empleados' => $ultima_planilla['total_empleados']
                ];

                $total_anual_asignaciones += $asignaciones_mes;
                $total_anual_deducciones += $deducciones_mes;
                $total_anual_neto += $neto_mes;
            }

            // Preparar datos para la vista
            $companyInfo = $this->getCompanyInfo();
            $signatures = $this->companyModel->getSignaturesForReports();

            $data = [
                'title' => 'Estimado Anual de Planillas',
                'page_title' => 'Estimado Anual de Planillas',
                'breadcrumb' => [
                    ['name' => 'Dashboard', 'url' => '/panel/dashboard'],
                    ['name' => 'Reportes', 'url' => '/panel/reports'],
                    ['name' => 'Estimado Anual Planillas', 'url' => '#']
                ],
                'proyeccion_mensual' => $proyeccion_mensual,
                'total_anual_asignaciones' => $total_anual_asignaciones,
                'total_anual_deducciones' => $total_anual_deducciones,
                'total_anual_neto' => $total_anual_neto,
                'ultima_planilla' => $ultima_planilla,
                'tipos_planilla' => $tipos_planilla,
                'tipo_planilla_id' => $tipo_planilla_id,
                'ano_estimado' => date('Y'),
                'fecha_estimado' => date('d/m/Y'),
                'companyInfo' => $companyInfo,
                'signatures' => $signatures
            ];

            $this->view('admin/reports/estimado_planillas', $data);

        } catch (\Exception $e) {
            error_log("Error en estimadoAnualPlanillas: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = 'Error al generar estimado de planillas: ' . $e->getMessage();
            $this->redirect('/panel/reports');
        }
    }
}
