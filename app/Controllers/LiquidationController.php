<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Employee;
use App\Models\Planilla;
use App\Services\PlanillaConceptCalculator;
use PDO;
use PDOException;

class LiquidationController extends Controller
{
    protected $calculator;
    private $liquidationFrequencyId = null; // Caché para frecuencia de liquidación

    public function __construct()
    {
        parent::__construct();
        $this->calculator = new PlanillaConceptCalculator();
    }

    /**
     * Obtener ID de frecuencia de liquidación dinámicamente por código
     * Usa caché para evitar consultas repetidas en la misma petición
     *
     * @return int|null ID de la frecuencia de liquidación, o null si no existe
     */
    public function getLiquidationFrequencyId()
    {
        // Retornar desde caché si ya se consultó
        if ($this->liquidationFrequencyId !== null) {
            return $this->liquidationFrequencyId;
        }

        try {
            // Buscar frecuencia por código 'LIQUIDACION' (más portable que ID hardcoded)
            $sql = "SELECT id FROM frecuencias
                    WHERE UPPER(codigo) = 'LIQUIDACION' AND activo = 1
                    LIMIT 1";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $this->liquidationFrequencyId = (int)$result['id'];
                return $this->liquidationFrequencyId;
            }

            // Fallback: buscar por nombre si código no existe (case-insensitive)
            $sql = "SELECT id FROM frecuencias
                    WHERE UPPER(nombre) LIKE '%LIQUIDACI%' AND activo = 1
                    LIMIT 1";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $this->liquidationFrequencyId = (int)$result['id'];
                error_log("Warning: Frecuencia de liquidación encontrada por nombre, considere actualizar campo 'codigo' a 'LIQUIDACION'");
                return $this->liquidationFrequencyId;
            }

            // Si no se encuentra, loggear error
            error_log("Error crítico: No se encontró frecuencia de liquidación en tabla 'frecuencias'. Debe existir registro con codigo='LIQUIDACION'");
            return null;

        } catch (PDOException $e) {
            error_log("Error al obtener frecuencia de liquidación: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lista de empleados activos con opción de liquidar
     */
    public function index()
    {
        try {
            // Obtener empleados activos
            $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, c.nombre as position_name,
                           s.nombre as situacion_nombre,
                           DATEDIFF(CURDATE(), e.fecha_ingreso) as dias_trabajados,
                           YEAR(CURDATE()) - YEAR(e.fecha_ingreso) as anos_trabajados
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    WHERE e.situacion_id = 1
                    ORDER BY e.firstname, e.lastname";

            $stmt = $this->db->query($sql);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener terminaciones pendientes
            $sql = "SELECT et.*, e.firstname, e.lastname, e.employee_id
                    FROM employee_terminations et
                    INNER JOIN employees e ON et.employee_id = e.id
                    WHERE et.status IN ('PENDIENTE', 'CALCULADA')
                    ORDER BY et.created_at DESC";

            $stmt = $this->db->query($sql);
            $pending_terminations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/liquidation/index', [
                'employees' => $employees,
                'pending_terminations' => $pending_terminations,
                'pageTitle' => 'Liquidaciones de Empleados'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar empleados: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel');
        }
    }

    /**
     * Mostrar formulario para crear nueva liquidación
     */
    public function create($employee_id = null)
    {
        try {
            if (!$employee_id) {
                $this->setToastrMessage('error', 'ID de empleado requerido', 'Parámetro Faltante');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Obtener datos del empleado
            $sql = "SELECT e.*, c.nombre as position_name, s.nombre as situacion_nombre
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    WHERE e.id = ? AND e.situacion_id = 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->setToastrMessage('error', 'Empleado no encontrado o ya terminado', 'Empleado No Válido');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Verificar si ya tiene una liquidación pendiente
            $sql = "SELECT id FROM employee_terminations WHERE employee_id = ? AND status IN ('PENDIENTE', 'CALCULADA')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);

            if ($stmt->fetch()) {
                $this->setToastrMessage('error', 'Este empleado ya tiene una liquidación en proceso', 'Liquidación Duplicada');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Calcular período trabajado hasta hoy
            $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
            $fecha_actual = new \DateTime();
            $intervalo = $fecha_ingreso->diff($fecha_actual);

            // Calcular días trabajados (aproximación sin feriados por ahora)
            $dias_trabajados = $this->calculateBusinessDays($fecha_ingreso->format('Y-m-d'), $fecha_actual->format('Y-m-d'));

            // Crear objeto con información detallada del período
            $periodo_trabajado = [
                'anos' => $intervalo->y,
                'meses' => $intervalo->m,
                'dias' => $intervalo->d,
                'total_dias_calendario' => $fecha_ingreso->diff($fecha_actual)->days,
                'total_dias_laborables' => $dias_trabajados,
                'anos_completos' => $intervalo->y // Para cálculos legales
            ];

            $this->render('admin/liquidation/create', [
                'employee' => $employee,
                'periodo_trabajado' => $periodo_trabajado,
                'anos_trabajados' => $periodo_trabajado['anos_completos'], // Para retrocompatibilidad
                'pageTitle' => 'Nueva Liquidación - ' . $employee['firstname'] . ' ' . $employee['lastname']
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar empleado: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Guardar nueva liquidación
     */
    public function store()
    {
        try {
            $this->validateCsrfToken();

            $employee_id = $_POST['employee_id'] ?? null;
            $termination_date = $_POST['termination_date'] ?? null;
            $termination_type = $_POST['termination_type'] ?? null;
            $reason = $_POST['reason'] ?? '';
            $notice_period_days = $_POST['notice_period_days'] ?? 30;

            // Validaciones
            if (!$employee_id || !$termination_date || !$termination_type || empty(trim($reason))) {
                $this->setToastrMessage('error', 'Todos los campos obligatorios deben ser completados', 'Validación de Formulario');
                $this->redirect('/panel/liquidation/create/' . $employee_id);
                return;
            }

            // Validar fecha de terminación
            $termination_datetime = new \DateTime($termination_date);
            $today = new \DateTime();

            if ($termination_datetime > $today) {
                $this->setToastrMessage('error', 'La fecha de terminación no puede ser futura', 'Fecha Inválida');
                $this->redirect('/panel/liquidation/create/' . $employee_id);
                return;
            }

            // Obtener datos del empleado para validar fecha de ingreso
            $sql = "SELECT fecha_ingreso FROM employees WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employee_id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $this->setToastrMessage('error', 'Empleado no encontrado', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation');
                return;
            }

            $fecha_ingreso = new \DateTime($employee['fecha_ingreso']);
            if ($termination_datetime < $fecha_ingreso) {
                $this->setToastrMessage('error', 'La fecha de terminación no puede ser anterior a la fecha de ingreso', 'Fecha Inválida');
                $this->redirect('/panel/liquidation/create/' . $employee_id);
                return;
            }

            // Calcular datos de la liquidación
            $years_worked = $fecha_ingreso->diff($termination_datetime)->y;
            $months_current_year = $this->calculateMonthsWorkedCurrentYear($fecha_ingreso, $termination_datetime);

            $this->db->beginTransaction();

            // Crear registro de terminación
            $sql = "INSERT INTO employee_terminations
                    (employee_id, termination_date, termination_type, notice_period_days,
                     reason, years_worked, months_worked_current_year, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDIENTE')";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $employee_id,
                $termination_date,
                $termination_type,
                $notice_period_days,
                $reason,
                $years_worked,
                $months_current_year
            ]);

            $termination_id = $this->db->lastInsertId();

            // Actualizar estado del empleado (situacion_id = 6 para 'Baja')
            $sql = "UPDATE employees SET
                    fecha_terminacion = ?,
                    motivo_terminacion = ?,
                    situacion_id = 6
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_date, $reason, $employee_id]);

            // Registrar en historial
            $this->logLiquidationAction($termination_id, 'CREACION', 'Liquidación creada');

            $this->db->commit();

            $this->setToastrMessage('success', 'Liquidación creada exitosamente', 'Liquidación Registrada');
            $this->redirect('/panel/liquidation/calculate/' . $termination_id);

        } catch (PDOException $e) {
            $this->db->rollback();
            $this->setToastrMessage('error', 'Error al crear liquidación: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Calcular montos de liquidación
     */
    public function calculate($termination_id)
    {
        try {
            // Obtener datos de la terminación
            $termination = $this->getTerminationData($termination_id);

            if (!$termination) {
                $this->setToastrMessage('error', 'Liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Configurar calculadora con variables del empleado
            $this->calculator->setVariablesLiquidacion($termination['employee_table_id'], $termination_id);

            // Obtener ID de frecuencia de liquidación dinámicamente
            $liquidation_frequency_id = $this->getLiquidationFrequencyId();

            if (!$liquidation_frequency_id) {
                $this->setToastrMessage('error', 'No se pudo obtener la frecuencia de liquidación. Contacte al administrador.', 'Error de Configuración');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Obtener tipos de planilla del empleado (puede tener múltiples)
            $employee_tipo_planilla_ids = !empty($termination['tipo_planilla_id'])
                ? explode(',', $termination['tipo_planilla_id'])
                : [];

            // Obtener conceptos de liquidación validando frecuencia y tipo de planilla
            if (!empty($employee_tipo_planilla_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_tipo_planilla_ids), '?'));
                $sql = "SELECT DISTINCT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                        FROM concepto c
                        INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                        INNER JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id
                        WHERE cf.frecuencia_id = ?  -- Frecuencia de liquidación (dinámico por código 'LIQUIDACION')
                        AND ctp.tipo_planilla_id IN ($placeholders)
                        ORDER BY c.tipo_concepto, c.concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute(array_merge([$liquidation_frequency_id], $employee_tipo_planilla_ids));
            } else {
                // Si el empleado no tiene tipo de planilla, obtener todos los conceptos de liquidación
                $sql = "SELECT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                        FROM concepto c
                        INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                        WHERE cf.frecuencia_id = ?  -- Frecuencia de liquidación (dinámico por código 'LIQUIDACION')
                        ORDER BY c.tipo_concepto, c.concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$liquidation_frequency_id]);
            }

            $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $calculations = [];
            $total_asignaciones = 0;
            $total_deducciones = 0;

            foreach ($concepts as $concept) {
                // Calcular monto según fórmula
                $amount = $this->calculator->evaluarFormula($concept['formula']);

                if ($concept['tipo_concepto'] === 'ASIGNACION' || $concept['tipo_concepto'] === 'A') {
                    $total_asignaciones += $amount;
                } elseif ($concept['tipo_concepto'] === 'DEDUCCION' || $concept['tipo_concepto'] === 'D') {
                    $total_deducciones += $amount;
                }

                $calculations[] = [
                    'concept_id' => $concept['id'],
                    'concept_code' => $concept['concepto'],
                    'concept_description' => $concept['descripcion'],
                    'formula' => $concept['formula'],
                    'amount' => $amount,
                    'type' => $concept['tipo_concepto']
                ];
            }

            // Guardar cálculos en base de datos
            $this->saveCalculations($termination_id, $calculations);

            // Actualizar estado de terminación
            $sql = "UPDATE employee_terminations SET status = 'CALCULADA' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);

            // Registrar en historial
            $this->logLiquidationAction($termination_id, 'CALCULO', 'Cálculos realizados');

            $totals = [
                'total_asignaciones' => $total_asignaciones,
                'total_deducciones' => $total_deducciones,
                'total_neto' => $total_asignaciones - $total_deducciones
            ];

            $this->render('admin/liquidation/calculate', [
                'termination' => $termination,
                'calculations' => $calculations,
                'totals' => $totals,
                'pageTitle' => 'Cálculo de Liquidación'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al calcular liquidación: ' . $e->getMessage(), 'Error de Cálculo');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Vista previa de liquidación
     */
    public function preview($termination_id)
    {
        try {
            $termination = $this->getTerminationData($termination_id);

            if (!$termination || $termination['status'] !== 'CALCULADA') {
                $this->setToastrMessage('error', 'Liquidación no encontrada o no calculada', 'Estado Inválido');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Obtener cálculos guardados con tipo de concepto
            $sql = "SELECT lc.*, c.tipo_concepto
                    FROM liquidation_calculations lc
                    LEFT JOIN concepto c ON lc.concept_id = c.id
                    WHERE lc.termination_id = ?
                    ORDER BY lc.concept_code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);
            $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totals = $this->calculateTotals($calculations);
            $liquidationAccumulations = [];
            $accumulatedMonths = $this->getLiquidationAccumulatedMonths(
                (int)$termination['employee_table_id'],
                $termination['termination_date']
            );
            foreach (['LIQ005', 'LIQ007'] as $conceptCode) {
                $liquidationAccumulations[$conceptCode] = $accumulatedMonths;
            }

            $this->render('admin/liquidation/preview', [
                'termination' => $termination,
                'calculations' => $calculations,
                'totals' => $totals,
                'liquidationAccumulations' => $liquidationAccumulations,
                'pageTitle' => 'Vista Previa de Liquidación'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al mostrar vista previa: ' . $e->getMessage(), 'Error de Vista');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Recalcular liquidación
     */
    public function recalculate($termination_id)
    {
        try {
            $start_time = microtime(true); // Para medir tiempo de procesamiento
            $this->validateCsrfToken();

            $termination = $this->getTerminationData($termination_id);

            if (!$termination) {
                // Si es una petición AJAX, devolver JSON de error
                if (isset($_POST['_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-Type: application/json');
                    http_response_code(404);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Liquidación no encontrada',
                        'error_type' => 'NotFound'
                    ]);
                    exit;
                }

                $this->setToastrMessage('error', 'Liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Solo permitir recálculo en estados específicos
            if (!in_array($termination['status'], ['CALCULADA', 'PROCESADA'])) {
                // Si es una petición AJAX, devolver JSON de error
                if (isset($_POST['_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => 'No se puede recalcular una liquidación en estado ' . $termination['status'],
                        'error_type' => 'InvalidState',
                        'current_status' => $termination['status']
                    ]);
                    exit;
                }

                $this->setToastrMessage('error', 'No se puede recalcular una liquidación en estado ' . $termination['status'], 'Estado Inválido');
                $this->redirect('/panel/liquidation');
                return;
            }

            $this->db->beginTransaction();

            // Marcar como que necesita recálculo
            $sql = "UPDATE employee_terminations SET
                    needs_recalculation = 0,
                    status = 'CALCULADA'
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);

            // Configurar calculadora con variables del empleado
            $this->calculator->setVariablesLiquidacion($termination['employee_table_id'], $termination_id);

            // Obtener ID de frecuencia de liquidación dinámicamente
            $liquidation_frequency_id = $this->getLiquidationFrequencyId();

            if (!$liquidation_frequency_id) {
                $this->setToastrMessage('error', 'No se pudo obtener la frecuencia de liquidación. Contacte al administrador.', 'Error de Configuración');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Obtener tipos de planilla del empleado (puede tener múltiples)
            $employee_tipo_planilla_ids = !empty($termination['tipo_planilla_id'])
                ? explode(',', $termination['tipo_planilla_id'])
                : [];

            // Obtener conceptos de liquidación validando frecuencia y tipo de planilla
            if (!empty($employee_tipo_planilla_ids)) {
                $placeholders = implode(',', array_fill(0, count($employee_tipo_planilla_ids), '?'));
                $sql = "SELECT DISTINCT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                        FROM concepto c
                        INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                        INNER JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id
                        WHERE cf.frecuencia_id = ?  -- Frecuencia de liquidación (dinámico por código 'LIQUIDACION')
                        AND ctp.tipo_planilla_id IN ($placeholders)
                        ORDER BY c.tipo_concepto, c.concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute(array_merge([$liquidation_frequency_id], $employee_tipo_planilla_ids));
            } else {
                // Si el empleado no tiene tipo de planilla, obtener todos los conceptos de liquidación
                $sql = "SELECT c.id, c.concepto, c.descripcion, c.formula, c.tipo_concepto
                        FROM concepto c
                        INNER JOIN concepto_frecuencias cf ON c.id = cf.concepto_id
                        WHERE cf.frecuencia_id = ?  -- Frecuencia de liquidación (dinámico por código 'LIQUIDACION')
                        ORDER BY c.tipo_concepto, c.concepto";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([$liquidation_frequency_id]);
            }

            $concepts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $calculations = [];
            $total_asignaciones = 0;
            $total_deducciones = 0;

            foreach ($concepts as $concept) {
                // Calcular monto según fórmula
                $amount = $this->calculator->evaluarFormula($concept['formula']);

                if ($concept['tipo_concepto'] === 'ASIGNACION' || $concept['tipo_concepto'] === 'A') {
                    $total_asignaciones += $amount;
                } elseif ($concept['tipo_concepto'] === 'DEDUCCION' || $concept['tipo_concepto'] === 'D') {
                    $total_deducciones += $amount;
                }

                $calculations[] = [
                    'concept_id' => $concept['id'],
                    'concept_code' => $concept['concepto'],
                    'concept_description' => $concept['descripcion'],
                    'formula' => $concept['formula'],
                    'amount' => $amount,
                    'type' => $concept['tipo_concepto']
                ];
            }

            // Guardar nuevos cálculos
            $this->saveCalculations($termination_id, $calculations);

            // Registrar en historial
            $this->logLiquidationAction($termination_id, 'RECALCULO', 'Liquidación recalculada con nuevos valores');

            $this->db->commit();

            // Si es una petición AJAX, devolver JSON
            if (isset($_POST['_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Liquidación recalculada exitosamente',
                    'concepts_count' => count($calculations),
                    'total_asignaciones' => $total_asignaciones,
                    'total_deducciones' => $total_deducciones,
                    'termination_id' => $termination_id,
                    'processing_time' => isset($start_time) ? round((microtime(true) - $start_time) * 1000, 2) : 0
                ]);
                exit;
            }

            $this->setToastrMessage('success', 'Liquidación recalculada exitosamente', 'Recálculo Completado');
            $this->redirect('/panel/liquidation/preview/' . $termination_id);

        } catch (PDOException $e) {
            $this->db->rollback();

            // Si es una petición AJAX, devolver JSON de error
            if (isset($_POST['_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al recalcular liquidación: ' . $e->getMessage(),
                    'error_type' => 'PDOException',
                    'termination_id' => $termination_id
                ]);
                exit;
            }

            $this->setToastrMessage('error', 'Error al recalcular liquidación: ' . $e->getMessage(), 'Error de Recálculo');
            $this->redirect('/panel/liquidation');
        } catch (Exception $e) {
            if (isset($this->db)) {
                $this->db->rollback();
            }

            // Si es una petición AJAX, devolver JSON de error
            if (isset($_POST['_ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Error inesperado: ' . $e->getMessage(),
                    'error_type' => 'Exception',
                    'termination_id' => $termination_id
                ]);
                exit;
            }

            $this->setToastrMessage('error', 'Error inesperado: ' . $e->getMessage(), 'Error de Recálculo');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Generar planilla de liquidación
     */
    public function generatePayroll($termination_id)
    {
        try {
            $termination = $this->getTerminationData($termination_id);

            if (!$termination || $termination['status'] !== 'CALCULADA') {
                $this->setToastrMessage('error', 'Liquidación no encontrada o no calculada', 'Estado Inválido');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Calcular fechas del periodo (11 meses hacia atrás desde fecha de terminación)
            $fecha_terminacion = new \DateTime($termination['termination_date']);
            $fecha_inicio = clone $fecha_terminacion;
            $fecha_inicio->modify('-11 months');

            // Crear planilla cabecera con frecuencia de liquidación
            $payroll_id = $this->createLiquidationPayrollHeader(
                $termination,
                $fecha_inicio->format('Y-m-d'),
                $fecha_terminacion->format('Y-m-d')
            );

            // Obtener cálculos de liquidación
            $sql = "SELECT lc.*, c.tipo_concepto
                    FROM liquidation_calculations lc
                    LEFT JOIN concepto c ON lc.concept_id = c.id
                    WHERE lc.termination_id = ?
                    ORDER BY lc.concept_code";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);
            $calculations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Crear detalles de planilla
            $this->createLiquidationPayrollDetails($payroll_id, $termination, $calculations);

            // Actualizar estado de la liquidación y guardar referencia a la planilla
            $sql = "UPDATE employee_terminations SET status = 'PROCESADA', liquidation_payroll_id = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $termination_id]);

            // Registrar en historial
            $this->logLiquidationAction($termination_id, 'PLANILLA_GENERADA', 'Planilla de liquidación generada');

            $this->setToastrMessage('success', 'Planilla de liquidación generada exitosamente', 'Planilla Creada');
            $this->redirect('/panel/liquidation/payrolls');

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al generar planilla: ' . $e->getMessage(), 'Error de Generación');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Revertir planilla de liquidación procesada a estado CALCULADA
     * Elimina la planilla generada y vuelve el estado a CALCULADA
     */
    public function revertPayroll($termination_id)
    {
        try {
            $this->validateCsrfToken();

            $termination = $this->getTerminationData($termination_id);

            if (!$termination || $termination['status'] !== 'PROCESADA') {
                $this->setToastrMessage('error', 'Liquidación no encontrada o no está procesada', 'Estado Inválido');
                $this->redirect('/panel/liquidation');
                return;
            }

            $this->db->beginTransaction();

            // 1. Buscar la planilla generada para esta liquidación
            $liquidation_frequency_id = $this->getLiquidationFrequencyId();

            $sql = "SELECT id FROM planilla_cabecera
                    WHERE descripcion LIKE ?
                    AND frecuencia_id = ?
                    ORDER BY created_at DESC
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['%Liquidación - ' . $termination['firstname'] . ' ' . $termination['lastname'] . '%', $liquidation_frequency_id]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payroll) {
                $payroll_id = $payroll['id'];

                // 2. Eliminar detalles de la planilla
                $sql = "DELETE FROM planilla_detalle WHERE planilla_cabecera_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$payroll_id]);
                $deleted_details = $stmt->rowCount();

                // 3. Eliminar cabecera de la planilla
                $sql = "DELETE FROM planilla_cabecera WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$payroll_id]);

                error_log("Planilla revertida: ID $payroll_id, $deleted_details detalles eliminados");
            } else {
                error_log("No se encontró planilla para revertir (liquidación {$termination_id})");
            }

            // 4. Cambiar estado de la liquidación a CALCULADA y limpiar referencia a planilla
            $sql = "UPDATE employee_terminations SET status = 'CALCULADA', liquidation_payroll_id = NULL WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);

            // 5. Registrar en historial
            $this->logLiquidationAction($termination_id, 'PLANILLA_REVERTIDA', 'Planilla de liquidación revertida a estado CALCULADA');

            $this->db->commit();

            $this->setToastrMessage('success', 'Planilla de liquidación revertida exitosamente. La liquidación volvió a estado CALCULADA.', 'Reversión Exitosa');
            $this->redirect('/panel/liquidation/preview/' . $termination_id);

        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Error revirtiendo planilla de liquidación: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al revertir planilla: ' . $e->getMessage(), 'Error de Reversión');
            $this->redirect('/panel/liquidation');
        }
    }

    // ==========================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ==========================================

    /**
     * Obtener datos completos de la terminación
     */
    private function getTerminationData($termination_id)
    {
        $sql = "SELECT et.*, e.id as employee_table_id, e.firstname, e.lastname, e.employee_id, e.document_id,
                       e.fecha_ingreso, e.sueldo_individual, e.tipo_planilla_id, c.nombre as position_name
                FROM employee_terminations et
                INNER JOIN employees e ON et.employee_id = e.id
                LEFT JOIN posiciones p ON e.position_id = p.id
                LEFT JOIN cargos c ON p.id_cargo = c.id
                WHERE et.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$termination_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Calcular meses trabajados en el año actual
     */
    private function calculateMonthsWorkedCurrentYear($fecha_ingreso, $fecha_terminacion)
    {
        $inicio_ano = new \DateTime(date('Y-01-01'));
        $fecha_inicio_calculo = $fecha_ingreso > $inicio_ano ? $fecha_ingreso : $inicio_ano;

        $diferencia = $fecha_inicio_calculo->diff($fecha_terminacion);
        return ($diferencia->y * 12) + $diferencia->m;
    }

    /**
     * Guardar cálculos en base de datos
     */
    private function saveCalculations($termination_id, $calculations)
    {
        // Limpiar cálculos previos
        $sql = "DELETE FROM liquidation_calculations WHERE termination_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$termination_id]);

        // Insertar nuevos cálculos
        $sql = "INSERT INTO liquidation_calculations
                (termination_id, concept_id, concept_code, concept_description, calculated_amount, formula_used)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        foreach ($calculations as $calc) {
            $stmt->execute([
                $termination_id,
                $calc['concept_id'],
                $calc['concept_code'],
                $calc['concept_description'],
                $calc['amount'],
                $calc['formula']
            ]);
        }
    }

    /**
     * Calcular totales de asignaciones y deducciones
     */
    private function calculateTotals($calculations)
    {
        $total_asignaciones = 0;
        $total_deducciones = 0;

        foreach ($calculations as $calc) {
            // Usar el tipo_concepto que ya viene en los datos del JOIN
            if (isset($calc['tipo_concepto']) && ($calc['tipo_concepto'] === 'ASIGNACION' || $calc['tipo_concepto'] === 'A')) {
                $total_asignaciones += $calc['calculated_amount'];
            } elseif (isset($calc['tipo_concepto']) && ($calc['tipo_concepto'] === 'DEDUCCION' || $calc['tipo_concepto'] === 'D')) {
                $total_deducciones += $calc['calculated_amount'];
            }
        }

        return [
            'total_asignaciones' => $total_asignaciones,
            'total_deducciones' => $total_deducciones,
            'total_neto' => $total_asignaciones - $total_deducciones
        ];
    }

    /**
     * Obtener acumulados por mes para liquidacion (ultimos 12 meses).
     */
    private function getLiquidationAccumulatedMonths(int $employeeId, string $terminationDate, string $tipoAcumulado = 'SALARIO_BASE'): array
    {
        try {
            $fechaFin = new \DateTime($terminationDate);
            $fechaInicio = (clone $fechaFin)->modify('-11 months');

            $sql = "SELECT ape.ano, ape.mes, SUM(ape.monto) as total
                    FROM acumulados_por_empleado ape
                    INNER JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ?
                    AND ape.tipo_acumulado = ?
                    AND pc.fecha_hasta >= ?
                    AND pc.fecha_desde <= ?
                    GROUP BY ape.ano, ape.mes
                    ORDER BY ape.ano, ape.mes";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $employeeId,
                $tipoAcumulado,
                $fechaInicio->format('Y-m-d'),
                $fechaFin->format('Y-m-d')
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $byMonth = [];
            foreach ($rows as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ano'], (int)$row['mes']);
                $byMonth[$key] = (float)$row['total'];
            }

            $months = [];
            $total = 0.0;
            $cursor = (clone $fechaFin)->modify('first day of this month')->modify('-11 months');
            for ($i = 0; $i < 12; $i++) {
                $key = $cursor->format('Y-m');
                $amount = (float)($byMonth[$key] ?? 0);
                $months[] = [
                    'label' => $cursor->format('m/Y'),
                    'amount' => $amount
                ];
                $total += $amount;
                $cursor->modify('+1 month');
            }

            return [
                'start' => $fechaInicio->format('Y-m-d'),
                'end' => $fechaFin->format('Y-m-d'),
                'months' => $months,
                'total' => $total
            ];
        } catch (\Exception $e) {
            error_log("Error building liquidation accumulations: " . $e->getMessage());
        }

        $months = [];
        $cursor = new \DateTime(date('Y-m-01'));
        $cursor->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $months[] = [
                'label' => $cursor->format('m/Y'),
                'amount' => 0.0
            ];
            $cursor->modify('+1 month');
        }

        return [
            'start' => null,
            'end' => null,
            'months' => $months,
            'total' => 0.0
        ];
    }

    /**
     * Registrar acción en historial de liquidación
     */
    private function logLiquidationAction($termination_id, $action, $description)
    {
        $sql = "INSERT INTO liquidation_history
                (termination_id, action, description, user_id, ip_address)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $termination_id,
            $action,
            $description,
            $_SESSION['user']['id'] ?? 1,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }

    /**
     * Crear cabecera de planilla de liquidación
     */
    private function createLiquidationPayrollHeader($termination, $fecha_desde, $fecha_hasta)
    {
        // Verificar si ya existe una planilla para esta liquidación
        $sql = "SELECT id FROM planilla_cabecera
                WHERE descripcion LIKE ? AND fecha_desde = ? AND fecha_hasta = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            '%Liquidación - ' . $termination['firstname'] . ' ' . $termination['lastname'] . '%',
            $fecha_desde,
            $fecha_hasta
        ]);

        if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return $existing['id'];
        }

        // Obtener tipo de planilla de liquidación (crear si no existe)
        $tipo_planilla_id = $this->getOrCreateLiquidationPayrollType();

        // Obtener ID de frecuencia de liquidación dinámicamente
        $liquidation_frequency_id = $this->getLiquidationFrequencyId();

        $sql = "INSERT INTO planilla_cabecera
                (descripcion, tipo_planilla_id, frecuencia_id, fecha, fecha_desde, fecha_hasta, estado)
                VALUES (?, ?, ?, ?, ?, ?, 'PROCESADA')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'Liquidación - ' . $termination['firstname'] . ' ' . $termination['lastname'] . ' (' . date('d/m/Y', strtotime($termination['termination_date'])) . ')',
            $tipo_planilla_id,
            $liquidation_frequency_id, // Frecuencia de liquidación (dinámico por código 'LIQUIDACION')
            $fecha_hasta,
            $fecha_desde,
            $fecha_hasta
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Crear detalles de planilla de liquidación
     */
    private function createLiquidationPayrollDetails($payroll_id, $termination, $calculations)
    {
        foreach ($calculations as $calc) {
            if ($calc['calculated_amount'] <= 0) {
                continue; // Saltar conceptos con monto cero
            }

            // Determinar tipo (A=Asignación, D=Deducción)
            $tipo = 'A';
            if ($calc['tipo_concepto'] === 'DEDUCCION' || $calc['tipo_concepto'] === 'D') {
                $tipo = 'D';
            }

            $sql = "INSERT INTO planilla_detalle
                    (planilla_cabecera_id, employee_id, concepto_id, monto, tipo,
                     firstname, lastname)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $payroll_id,
                $termination['employee_table_id'],
                $calc['concept_id'],
                $calc['calculated_amount'],
                $tipo,
                $termination['firstname'],
                $termination['lastname']
            ]);
        }
    }

    /**
     * Obtener o crear tipo de planilla de liquidación
     */
    private function getOrCreateLiquidationPayrollType()
    {
        // Primero buscar por código LIQ
        $sql = "SELECT id FROM tipos_planilla WHERE codigo = 'LIQ'";
        $stmt = $this->db->query($sql);
        $tipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tipo) {
            return $tipo['id'];
        }

        // Buscar por descripción (puede existir registro incompleto)
        $sql = "SELECT id, codigo, nombre FROM tipos_planilla WHERE descripcion LIKE '%Liquidación%' OR descripcion LIKE '%Liquidacion%'";
        $stmt = $this->db->query($sql);
        $tipo_existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tipo_existente) {
            // Si existe pero le faltan campos, actualizarlo
            if (empty($tipo_existente['codigo']) || empty($tipo_existente['nombre'])) {
                $sql = "UPDATE tipos_planilla SET codigo = 'LIQ', nombre = 'Liquidación', activo = 1 WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$tipo_existente['id']]);
                error_log("Tipo de planilla de liquidación actualizado (ID: {$tipo_existente['id']})");
            }
            return $tipo_existente['id'];
        }

        // Crear nuevo tipo de planilla de liquidación con todos los campos requeridos
        $sql = "INSERT INTO tipos_planilla (codigo, nombre, descripcion, activo) VALUES ('LIQ', 'Liquidación', 'Planilla de Liquidación', 1)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        error_log("Tipo de planilla de liquidación creado (ID: " . $this->db->lastInsertId() . ")");
        return $this->db->lastInsertId();
    }

    /**
     * Mostrar planillas de liquidación
     */
    public function payrolls()
    {
        try {
            // Obtener ID de frecuencia de liquidación dinámicamente
            $liquidation_frequency_id = $this->getLiquidationFrequencyId();

            // Obtener planillas de liquidación con información de la liquidación
            $sql = "SELECT pc.*,
                           tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre,
                           COUNT(DISTINCT pd.employee_id) as total_empleados,
                           SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) as total_asignaciones,
                           SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END) as total_deducciones,
                           (SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) -
                            SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END)) as total_neto,
                           (SELECT et.id FROM employee_terminations et
                            INNER JOIN planilla_detalle pd2 ON et.employee_id = pd2.employee_id
                            WHERE pd2.planilla_cabecera_id = pc.id
                            AND et.liquidation_payroll_id = pc.id
                            LIMIT 1) as termination_id,
                           (SELECT et.status FROM employee_terminations et
                            INNER JOIN planilla_detalle pd2 ON et.employee_id = pd2.employee_id
                            WHERE pd2.planilla_cabecera_id = pc.id
                            AND et.liquidation_payroll_id = pc.id
                            LIMIT 1) as liquidation_status
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    LEFT JOIN planilla_detalle pd ON pc.id = pd.planilla_cabecera_id
                    WHERE pc.frecuencia_id = ?
                    GROUP BY pc.id
                    ORDER BY pc.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$liquidation_frequency_id]);
            $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/liquidation/payrolls', [
                'payrolls' => $payrolls,
                'pageTitle' => 'Planillas de Liquidación'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar planillas de liquidación: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Ver detalle de planilla de liquidación
     */
    public function payrollDetail($payroll_id)
    {
        try {
            // Obtener datos de la planilla
            $sql = "SELECT pc.*, tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE pc.id = ? AND pc.frecuencia_id = ?";

            $liquidation_frequency_id = $this->getLiquidationFrequencyId();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $liquidation_frequency_id]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                $this->setToastrMessage('error', 'Planilla de liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation/payrolls');
                return;
            }

            // Obtener detalles de la planilla
            $sql = "SELECT pd.*, c.concepto, c.descripcion as concepto_descripcion,
                           c.tipo_concepto, e.employee_id as cedula, e.document_id
                    FROM planilla_detalle pd
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY pd.firstname, pd.lastname, c.concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calcular totales
            $totals = [
                'total_asignaciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'A'), 'monto')),
                'total_deducciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'D'), 'monto'))
            ];
            $totals['total_neto'] = $totals['total_asignaciones'] - $totals['total_deducciones'];

            $this->render('admin/liquidation/payroll_detail', [
                'payroll' => $payroll,
                'details' => $details,
                'totals' => $totals,
                'pageTitle' => 'Detalle de Planilla de Liquidación'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar detalle de planilla: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation/payrolls');
        }
    }

    // ==========================================
    // MÉTODOS DE CANCELACIÓN DE LIQUIDACIONES
    // ==========================================

    /**
     * Mostrar formulario de cancelación
     */
    public function cancelForm($termination_id)
    {
        try {
            $termination = $this->getTerminationData($termination_id);

            if (!$termination) {
                $this->setToastrMessage('error', 'Liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Verificar si la liquidación puede ser cancelada
            if (!$this->canBeCancelled($termination['status'])) {
                $this->setToastrMessage('error', 'Esta liquidación no puede ser cancelada en su estado actual', 'Acción No Permitida');
                $this->redirect('/panel/liquidation');
                return;
            }

            $this->render('admin/liquidation/cancel', [
                'termination' => $termination,
                'pageTitle' => 'Cancelar Liquidación - ' . $termination['firstname'] . ' ' . $termination['lastname']
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar datos: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Procesar cancelación de liquidación
     */
    public function cancel($termination_id)
    {
        try {
            $this->validateCsrfToken();

            $cancel_reason = $_POST['cancel_reason'] ?? '';

            // Validaciones
            if (empty(trim($cancel_reason))) {
                $this->setToastrMessage('error', 'El motivo de cancelación es obligatorio', 'Validación de Formulario');
                $this->redirect('/panel/liquidation/cancel-form/' . $termination_id);
                return;
            }

            // Obtener datos de la liquidación
            $termination = $this->getTerminationData($termination_id);

            if (!$termination) {
                $this->setToastrMessage('error', 'Liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation');
                return;
            }

            // Verificar si puede ser cancelada
            if (!$this->canBeCancelled($termination['status'])) {
                $this->setToastrMessage('error', 'Esta liquidación no puede ser cancelada en su estado actual', 'Acción No Permitida');
                $this->redirect('/panel/liquidation');
                return;
            }

            $this->db->beginTransaction();

            // Guardar estado anterior y cancelar liquidación
            $sql = "UPDATE employee_terminations SET
                    status = 'CANCELADA',
                    previous_status = ?,
                    cancelled_at = NOW(),
                    cancelled_by = ?,
                    cancel_reason = ?
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $termination['status'],
                $_SESSION['user']['id'] ?? 1,
                $cancel_reason,
                $termination_id
            ]);

            // Reversar estado del empleado si corresponde
            if ($this->shouldRevertEmployeeStatus($termination['status'])) {
                $this->revertEmployeeStatus($termination['employee_table_id']);
            }

            // Registrar en historial
            $this->logLiquidationAction($termination_id, 'CANCELACION', 'Liquidación cancelada: ' . $cancel_reason);

            $this->db->commit();

            $this->setToastrMessage('success', 'Liquidación cancelada exitosamente', 'Cancelación Completada');
            $this->redirect('/panel/liquidation');

        } catch (PDOException $e) {
            $this->db->rollback();
            $this->setToastrMessage('error', 'Error al cancelar liquidación: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    /**
     * Lista de liquidaciones canceladas
     */
    public function cancelledList()
    {
        try {
            $sql = "SELECT et.*, e.firstname, e.lastname, e.employee_id, e.document_id,
                           c.nombre as position_name, a.firstname as cancelled_by_name,
                           a.lastname as cancelled_by_lastname
                    FROM employee_terminations et
                    INNER JOIN employees e ON et.employee_id = e.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos c ON p.id_cargo = c.id
                    LEFT JOIN admin a ON et.cancelled_by = a.id
                    WHERE et.status = 'CANCELADA'
                    ORDER BY et.cancelled_at DESC";

            $stmt = $this->db->query($sql);
            $cancelled_liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('admin/liquidation/cancelled', [
                'cancelled_liquidations' => $cancelled_liquidations,
                'pageTitle' => 'Liquidaciones Canceladas'
            ]);

        } catch (PDOException $e) {
            $this->setToastrMessage('error', 'Error al cargar liquidaciones canceladas: ' . $e->getMessage(), 'Error de Base de Datos');
            $this->redirect('/panel/liquidation');
        }
    }

    // ==========================================
    // MÉTODOS AUXILIARES PARA CANCELACIÓN
    // ==========================================

    /**
     * Verificar si una liquidación puede ser cancelada
     */
    private function canBeCancelled($status)
    {
        $cancellable_statuses = ['PENDIENTE', 'CALCULADA', 'PROCESADA'];
        return in_array($status, $cancellable_statuses);
    }

    /**
     * Verificar si se debe reversar el estado del empleado
     */
    private function shouldRevertEmployeeStatus($status)
    {
        $revertible_statuses = ['PENDIENTE', 'CALCULADA'];
        return in_array($status, $revertible_statuses);
    }

    /**
     * Reversar estado del empleado a ACTIVO
     */
    private function revertEmployeeStatus($employee_id)
    {
        $sql = "UPDATE employees SET
                situacion_id = 1,
                fecha_terminacion = NULL,
                motivo_terminacion = NULL
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employee_id]);
    }

    /**
     * Calcular días laborables entre dos fechas
     * Excluye sábados y domingos (aproximación básica)
     */
    private function calculateBusinessDays($start_date, $end_date)
    {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $working_days = 0;

        $current = clone $start;
        while ($current <= $end) {
            $dayOfWeek = (int) $current->format('w'); // 0=domingo, 6=sábado
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Lunes a Viernes
                $working_days++;
            }
            $current->add(new \DateInterval('P1D'));
        }

        return $working_days;
    }

    /**
     * Calcular período detallado entre dos fechas
     * Retorna array con años, meses, días y totales
     */
    private function calculateDetailedPeriod($start_date, $end_date)
    {
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        $interval = $start->diff($end);

        $working_days = $this->calculateBusinessDays($start_date, $end_date);

        return [
            'anos' => $interval->y,
            'meses' => $interval->m,
            'dias' => $interval->d,
            'total_dias_calendario' => $interval->days,
            'total_dias_laborables' => $working_days,
            'anos_completos' => $interval->y,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }

    /**
     * AJAX - Actualizar días de preaviso
     */
    public function updateNoticeDays($termination_id)
    {
        header('Content-Type: application/json');

        try {
            $this->validateCsrfToken();

            $notice_period_days = (int)($_POST['notice_period_days'] ?? 0);

            // Validaciones
            if ($notice_period_days < 0 || $notice_period_days > 365) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Los días de preaviso deben estar entre 0 y 365'
                ]);
                return;
            }

            // Verificar que la liquidación existe
            $sql = "SELECT et.id, et.status, e.firstname, e.lastname FROM employee_terminations et
                    INNER JOIN employees e ON et.employee_id = e.id
                    WHERE et.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$termination_id]);
            $termination = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$termination) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Liquidación no encontrada'
                ]);
                return;
            }

            // Verificar que se puede modificar (no debe estar PAGADA o CANCELADA)
            if (in_array($termination['status'], ['PAGADA', 'CANCELADA'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pueden modificar los días de preaviso de una liquidación ' . strtolower($termination['status'])
                ]);
                return;
            }

            // Actualizar días de preaviso
            $sql = "UPDATE employee_terminations
                    SET notice_period_days = ?,
                        needs_recalculation = 1,
                        updated_at = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$notice_period_days, $termination_id]);

            // Registrar en historial
            $this->logLiquidationAction(
                $termination_id,
                'MODIFICACION_PREAVISO',
                "Días de preaviso actualizados a {$notice_period_days} días"
            );

            echo json_encode([
                'success' => true,
                'message' => 'Días de preaviso actualizados exitosamente',
                'new_value' => $notice_period_days,
                'needs_recalculation' => true
            ]);

        } catch (PDOException $e) {
            error_log("Error updating notice days: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            error_log("Error updating notice days: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error interno del servidor'
            ]);
        }
    }

    /**
     * AJAX - Calcular período trabajado dinámicamente
     */
    public function calculatePeriod()
    {
        header('Content-Type: application/json');

        try {
            $start_date = $_POST['start_date'] ?? $_GET['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? $_GET['end_date'] ?? null;

            if (!$start_date || !$end_date) {
                echo json_encode(['error' => 'Faltan fechas requeridas']);
                return;
            }

            // Validar formato de fechas
            $start = \DateTime::createFromFormat('Y-m-d', $start_date);
            $end = \DateTime::createFromFormat('Y-m-d', $end_date);

            if (!$start || !$end) {
                echo json_encode(['error' => 'Formato de fecha inválido']);
                return;
            }

            if ($start > $end) {
                echo json_encode(['error' => 'Fecha de inicio no puede ser posterior a fecha fin']);
                return;
            }

            $period = $this->calculateDetailedPeriod($start_date, $end_date);

            echo json_encode([
                'success' => true,
                'period' => $period,
                'formatted' => [
                    'periodo_completo' => $period['anos'] . ' años, ' . $period['meses'] . ' meses, ' . $period['dias'] . ' días',
                    'dias_calendario' => number_format($period['total_dias_calendario']),
                    'dias_laborables' => number_format($period['total_dias_laborables']),
                    'anos_completos' => $period['anos_completos']
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode(['error' => 'Error en cálculo: ' . $e->getMessage()]);
        }
    }

    /**
     * Exportar planilla de liquidación a Excel
     */
    public function exportPayrollExcel($payroll_id)
    {
        try {
            // Obtener datos de la planilla
            $sql = "SELECT pc.*, tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE pc.id = ? AND pc.frecuencia_id = ?";

            $liquidation_frequency_id = $this->getLiquidationFrequencyId();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $liquidation_frequency_id]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                $this->setToastrMessage('error', 'Planilla de liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation/payrolls');
                return;
            }

            // Obtener detalles de la planilla
            $sql = "SELECT pd.*, c.concepto, c.descripcion as concepto_descripcion,
                           c.tipo_concepto, e.employee_id as cedula, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, org.descripcion as departamento,
                           cargos.nombre as position_name, et.termination_date,
                           et.termination_type, et.notice_period_days, et.reason as termination_reason
                    FROM planilla_cabecera pc
                    INNER JOIN planilla_detalle pd ON pd.planilla_cabecera_id = pc.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN organigrama org ON e.organigrama_id = org.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos ON p.id_cargo = cargos.id
                    LEFT JOIN employee_terminations et ON et.employee_id = e.id
                        AND pc.fecha_hasta = et.termination_date
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY c.tipo_concepto, c.concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la empresa para las firmas
            $sql_company = "SELECT legal_representative, jefe_recursos_humanos FROM companies LIMIT 1";
            $stmt_company = $this->db->prepare($sql_company);
            $stmt_company->execute();
            $company_data = $stmt_company->fetch(PDO::FETCH_ASSOC);

            // Calcular totales
            $totals = [
                'total_asignaciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'A'), 'monto')),
                'total_deducciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'D'), 'monto'))
            ];
            $totals['total_neto'] = $totals['total_asignaciones'] - $totals['total_deducciones'];

            // Obtener información del empleado
            $employee_info = null;
            if (!empty($details)) {
                $first_detail = $details[0];

                // Calcular tiempo en la empresa
                $tiempo_empresa = 'N/A';
                if (!empty($first_detail['fecha_ingreso']) && !empty($first_detail['termination_date'])) {
                    $fecha_inicio = new \DateTime($first_detail['fecha_ingreso']);
                    $fecha_fin = new \DateTime($first_detail['termination_date']);
                    $intervalo = $fecha_inicio->diff($fecha_fin);

                    $tiempo_empresa = '';
                    if ($intervalo->y > 0) {
                        $tiempo_empresa .= $intervalo->y . ($intervalo->y == 1 ? ' año' : ' años');
                    }
                    if ($intervalo->m > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->m . ($intervalo->m == 1 ? ' mes' : ' meses');
                    }
                    if ($intervalo->d > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->d . ($intervalo->d == 1 ? ' día' : ' días');
                    }
                    if (empty($tiempo_empresa)) {
                        $tiempo_empresa = '0 días';
                    }
                }

                // Mapear tipo de terminación a texto legible
                $termination_types = [
                    'DESPIDO_CON_CAUSA' => 'Despido con Causa Justificada',
                    'DESPIDO_SIN_CAUSA' => 'Despido sin Causa Justificada',
                    'RENUNCIA' => 'Renuncia Voluntaria',
                    'MUTUO_ACUERDO' => 'Mutuo Acuerdo'
                ];
                $termination_type_text = isset($first_detail['termination_type']) && isset($termination_types[$first_detail['termination_type']])
                    ? $termination_types[$first_detail['termination_type']]
                    : 'N/A';

                $notice_period_days = $first_detail['notice_period_days'] ?? 0;
                $con_preaviso = $notice_period_days > 0 ? 'Sí' : 'No';

                $employee_info = [
                    'name' => $first_detail['firstname'] . ' ' . $first_detail['lastname'],
                    'cedula' => $first_detail['document_id'] ?? $first_detail['cedula'] ?? 'N/A',
                    'departamento' => $first_detail['departamento'] ?? 'N/A',
                    'position' => $first_detail['position_name'] ?? 'N/A',
                    'fecha_ingreso' => $first_detail['fecha_ingreso'] ?? 'N/A',
                    'fecha_terminacion' => $first_detail['termination_date'] ?? 'N/A',
                    'salario' => $first_detail['sueldo_individual'] ?? 0,
                    'tiempo_empresa' => $tiempo_empresa,
                    'motivo_liquidacion' => $termination_type_text,
                    'con_preaviso' => $con_preaviso,
                    'dias_preaviso' => $notice_period_days,
                    'razon_terminacion' => $first_detail['termination_reason'] ?? 'N/A'
                ];
            }

            // Crear Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Configurar hoja
            $sheet->setTitle('Liquidación');

            // ===== ENCABEZADO =====
            $sheet->mergeCells('A1:E1');
            $sheet->setCellValue('A1', 'INNOVA PLANILLA');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:E2');
            $sheet->setCellValue('A2', 'LIQUIDACIÓN LABORAL');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $periodo_texto = 'DEL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_desde']))) .
                           ' HASTA EL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_hasta'])));
            $sheet->mergeCells('A3:E3');
            $sheet->setCellValue('A3', $periodo_texto);
            $sheet->getStyle('A3')->getFont()->setSize(11);
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $row = 5;

            // ===== INFORMACIÓN DEL EMPLEADO =====
            if ($employee_info) {
                $sheet->setCellValue('A' . $row, 'Nombre:');
                $sheet->setCellValue('B' . $row, $employee_info['name']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Cédula:');
                $sheet->setCellValue('B' . $row, $employee_info['cedula']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Cargo:');
                $sheet->setCellValue('B' . $row, $employee_info['position']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Departamento:');
                $sheet->setCellValue('B' . $row, $employee_info['departamento']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Salario:');
                $sheet->setCellValue('B' . $row, '$' . number_format($employee_info['salario'], 2));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Fecha de Ingreso:');
                $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($employee_info['fecha_ingreso'])));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Fecha Fin Contrato:');
                $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($employee_info['fecha_terminacion'])));
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Tiempo en Empresa:');
                $sheet->setCellValue('B' . $row, $employee_info['tiempo_empresa']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Motivo de Liquidación:');
                $sheet->setCellValue('B' . $row, $employee_info['motivo_liquidacion']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Con Preaviso:');
                $sheet->setCellValue('B' . $row, $employee_info['con_preaviso']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row++;

                $sheet->setCellValue('A' . $row, 'Días de Preaviso:');
                $sheet->setCellValue('B' . $row, $employee_info['dias_preaviso']);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $row += 2;
            }

            // ===== CONCEPTOS DE ASIGNACIÓN =====
            $sheet->setCellValue('A' . $row, 'ASIGNACIONES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FF90EE90');
            $row++;

            $sheet->setCellValue('A' . $row, 'Código');
            $sheet->setCellValue('B' . $row, 'Concepto');
            $sheet->setCellValue('C' . $row, 'Descripción');
            $sheet->setCellValue('D' . $row, 'Monto');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $row++;

            $asignaciones = array_filter($details, fn($d) => $d['tipo'] === 'A');
            foreach ($asignaciones as $asignacion) {
                $sheet->setCellValue('A' . $row, $asignacion['concepto']);
                $sheet->setCellValue('B' . $row, $asignacion['concepto']);
                $sheet->setCellValue('C' . $row, $asignacion['concepto_descripcion']);
                $sheet->setCellValue('D' . $row, $asignacion['monto']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            $sheet->setCellValue('C' . $row, 'TOTAL ASIGNACIONES:');
            $sheet->setCellValue('D' . $row, $totals['total_asignaciones']);
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('C' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFCCFFCC');
            $row += 2;

            // ===== CONCEPTOS DE DEDUCCIÓN =====
            $sheet->setCellValue('A' . $row, 'DEDUCCIONES');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFFFCCCC');
            $row++;

            $sheet->setCellValue('A' . $row, 'Código');
            $sheet->setCellValue('B' . $row, 'Concepto');
            $sheet->setCellValue('C' . $row, 'Descripción');
            $sheet->setCellValue('D' . $row, 'Monto');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFE0E0E0');
            $row++;

            $deducciones = array_filter($details, fn($d) => $d['tipo'] === 'D');
            foreach ($deducciones as $deduccion) {
                $sheet->setCellValue('A' . $row, $deduccion['concepto']);
                $sheet->setCellValue('B' . $row, $deduccion['concepto']);
                $sheet->setCellValue('C' . $row, $deduccion['concepto_descripcion']);
                $sheet->setCellValue('D' . $row, $deduccion['monto']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
                $row++;
            }

            $sheet->setCellValue('C' . $row, 'TOTAL DEDUCCIONES:');
            $sheet->setCellValue('D' . $row, $totals['total_deducciones']);
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('C' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFFFDDDD');
            $row += 2;

            // ===== TOTAL NETO =====
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL NETO A PAGAR:');
            $sheet->setCellValue('D' . $row, $totals['total_neto']);
            $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFCCCCFF');

            // ===== SECCIÓN DE FIRMAS =====
            $row += 4; // Espacio antes de firmas

            // Líneas para firmas
            $sheet->setCellValue('A' . $row, '____________________');
            $sheet->setCellValue('B' . $row, '____________________');
            $sheet->setCellValue('C' . $row, '____________________');
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Etiquetas de firmas
            $sheet->setCellValue('A' . $row, 'Autorizado por');
            $sheet->setCellValue('B' . $row, 'Elaborado por');
            $sheet->setCellValue('C' . $row, 'Recibido por');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Nombres de quien firma
            $gerencia_name = $company_data['legal_representative'] ?? 'N/A';
            $rrhh_name = $company_data['jefe_recursos_humanos'] ?? 'N/A';
            $empleado_name = $employee_info ? $employee_info['name'] : 'N/A';
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $sheet->setCellValue('A' . $row, $gerencia_name);
            $sheet->setCellValue('B' . $row, $rrhh_name);
            $sheet->setCellValue('C' . $row, $empleado_name);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setSize(9);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $row++;

            // Subtextos (cargo/cédula)
            $sheet->setCellValue('A' . $row, '(Gerencia)');
            $sheet->setCellValue('B' . $row, '(Recursos Humanos)');
            $sheet->setCellValue('C' . $row, 'Cédula: ' . $empleado_cedula);
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setSize(8);
            $sheet->getStyle('A' . $row . ':C' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // ===== AJUSTAR ANCHOS DE COLUMNA =====
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(40);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);

            // ===== BORDES =====
            $lastRow = $row;
            $sheet->getStyle('A5:D' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000']
                    ]
                ]
            ]);

            // Generar nombre de archivo
            $filename = 'Liquidacion_' . ($employee_info ? preg_replace('/[^A-Za-z0-9_]/', '_', $employee_info['name']) : 'Planilla') .
                       '_' . date('Y-m-d', strtotime($payroll['fecha'])) . '.xlsx';

            // Configurar headers para descarga
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (PDOException $e) {
            error_log("Error generating Excel: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar Excel: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        } catch (\Exception $e) {
            error_log("Error generating Excel: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar Excel: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        }
    }

    /**
     * Exportar planilla de liquidación a PDF
     */
    public function exportPayrollPdf($payroll_id)
    {
        try {
            // Obtener datos de la planilla
            $sql = "SELECT pc.*, tp.descripcion as tipo_planilla_nombre,
                           f.nombre as frecuencia_nombre
                    FROM planilla_cabecera pc
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                    LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                    WHERE pc.id = ? AND pc.frecuencia_id = ?";

            $liquidation_frequency_id = $this->getLiquidationFrequencyId();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id, $liquidation_frequency_id]);
            $payroll = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payroll) {
                $this->setToastrMessage('error', 'Planilla de liquidación no encontrada', 'Error de Búsqueda');
                $this->redirect('/panel/liquidation/payrolls');
                return;
            }

            // Obtener detalles de la planilla
           $sql = "SELECT pd.*, c.concepto, c.descripcion as concepto_descripcion,
                           c.tipo_concepto, e.employee_id as cedula, e.document_id,
                           e.fecha_ingreso, e.sueldo_individual, org.descripcion as departamento,
                           cargos.nombre as position_name, 
                           et.termination_date,
                           et.termination_type, et.notice_period_days, et.reason as termination_reason
                    FROM planilla_cabecera pc
                    INNER JOIN planilla_detalle pd ON pd.planilla_cabecera_id = pc.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN organigrama org ON e.organigrama_id = org.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos ON cargos.id = e.cargo_id
                    LEFT JOIN employee_terminations et ON et.employee_id = e.id
                        AND pc.fecha_hasta = et.termination_date
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY c.tipo_concepto, c.concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payroll_id]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la empresa para las firmas y logos
            $sql_company = "SELECT legal_representative, jefe_recursos_humanos,
                                   company_name, logo_izquierdo_reportes, logo_derecho_reportes,
                                   logo_empresa
                            FROM companies LIMIT 1";
            $stmt_company = $this->db->prepare($sql_company);
            $stmt_company->execute();
            $company_data = $stmt_company->fetch(PDO::FETCH_ASSOC);

            // Calcular totales
            $totals = [
                'total_asignaciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'A'), 'monto')),
                'total_deducciones' => array_sum(array_column(array_filter($details, fn($d) => $d['tipo'] === 'D'), 'monto'))
            ];
            $totals['total_neto'] = $totals['total_asignaciones'] - $totals['total_deducciones'];

            // Obtener información del empleado
            $employee_info = null;
            if (!empty($details)) {
                $first_detail = $details[0];

                // Calcular tiempo en la empresa
                $tiempo_empresa = 'N/A';
                if (!empty($first_detail['fecha_ingreso']) && !empty($first_detail['termination_date'])) {
                    $fecha_inicio = new \DateTime($first_detail['fecha_ingreso']);
                    $fecha_fin = new \DateTime($first_detail['termination_date']);
                    $intervalo = $fecha_inicio->diff($fecha_fin);

                    $tiempo_empresa = '';
                    if ($intervalo->y > 0) {
                        $tiempo_empresa .= $intervalo->y . ($intervalo->y == 1 ? ' año' : ' años');
                    }
                    if ($intervalo->m > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->m . ($intervalo->m == 1 ? ' mes' : ' meses');
                    }
                    if ($intervalo->d > 0) {
                        $tiempo_empresa .= ($tiempo_empresa ? ', ' : '') . $intervalo->d . ($intervalo->d == 1 ? ' día' : ' días');
                    }
                    if (empty($tiempo_empresa)) {
                        $tiempo_empresa = '0 días';
                    }
                }

                // Mapear tipo de terminación a texto legible
                $termination_types = [
                    'DESPIDO_CON_CAUSA' => 'Despido con Causa Justificada',
                    'DESPIDO_SIN_CAUSA' => 'Despido sin Causa Justificada',
                    'RENUNCIA' => 'Renuncia Voluntaria',
                    'MUTUO_ACUERDO' => 'Mutuo Acuerdo'
                ];
                $termination_type_text = isset($first_detail['termination_type']) && isset($termination_types[$first_detail['termination_type']])
                    ? $termination_types[$first_detail['termination_type']]
                    : 'N/A';

                $notice_period_days = $first_detail['notice_period_days'] ?? 0;
                $con_preaviso = $notice_period_days > 0 ? 'Sí' : 'No';

                $employee_info = [
                    'name' => $first_detail['firstname'] . ' ' . $first_detail['lastname'],
                    'firstname' => $first_detail['firstname'],
                    'lastname' => $first_detail['lastname'],
                    'cedula' => $first_detail['document_id'] ?? $first_detail['cedula'] ?? 'N/A',
                    'departamento' => $first_detail['departamento'] ?? 'N/A',
                    'position' => $first_detail['position_name'] ?? 'N/A',
                    'fecha_ingreso' => $first_detail['fecha_ingreso'] ?? 'N/A',
                    'fecha_terminacion' => $first_detail['termination_date'] ?? 'N/A',
                    'salario' => $first_detail['sueldo_individual'] ?? 0,
                    'tiempo_empresa' => $tiempo_empresa,
                    'motivo_liquidacion' => $termination_type_text,
                    'con_preaviso' => $con_preaviso,
                    'dias_preaviso' => $notice_period_days,
                    'razon_terminacion' => $first_detail['termination_reason'] ?? 'N/A'
                ];
            }

            // Crear PDF
            $pdf = new \TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Configuración del documento
            $pdf->SetCreator('Sistema de Planillas INNOVA');
            $pdf->SetAuthor($company_data['company_name'] ?? 'INNOVA PLANILLA');
            $pdf->SetTitle('Liquidación Laboral - ' . ($employee_info['name'] ?? 'N/A'));
            $pdf->SetSubject('Liquidación Laboral');

            // Desactivar header y footer automáticos
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Márgenes
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);

            // Agregar página
            $pdf->AddPage();

            // Insertar logos y nombre de empresa
            $this->insertLogosInPDF($pdf, $company_data);

            $pdf->Ln();
            // ===== ENCABEZADO =====

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 8, 'LIQUIDACIÓN LABORAL', 0, 1, 'C');
            $pdf->Ln();

            /*$pdf->SetFont('helvetica', '', 10);
            $periodo_texto = 'DEL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_desde']))) .
                           ' HASTA EL ' . strtoupper(date('d \d\e F \d\e Y', strtotime($payroll['fecha_hasta'])));
            $pdf->Cell(0, 6, $periodo_texto, 0, 1, 'C');
            $pdf->Ln(5);*/

            // ===== INFORMACIÓN DEL EMPLEADO =====
            if ($employee_info) {
                // Columna izquierda
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Nombre:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['name'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Cédula:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['cedula'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Cargo:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['position'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Departamento:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['departamento'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Salario:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, '$' . number_format($employee_info['salario'], 2), 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Fecha de Ingreso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, date('d/m/Y', strtotime($employee_info['fecha_ingreso'])), 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Fecha Fin Contrato:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, date('d/m/Y', strtotime($employee_info['fecha_terminacion'])), 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Tiempo en Empresa:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['tiempo_empresa'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Motivo de Liquidación:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['motivo_liquidacion'], 0, 1);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Con Preaviso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['con_preaviso'], 0, 0);

                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(40, 6, 'Días de Preaviso:', 0, 0);
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(50, 6, $employee_info['dias_preaviso'], 0, 1);

                $pdf->Ln(5);
            }

            // Calcular anchos de columna (ancho disponible: página - márgenes)
            $pageWidth = $pdf->getPageWidth();
            $margins = $pdf->getMargins();
            $availableWidth = $pageWidth - $margins['left'] - $margins['right'];

            // Distribución de columnas: 17% Código, 58% Descripción, 25% Monto
            $colCodigo = $availableWidth * 0.17;
            $colDescripcion = $availableWidth * 0.58;
            $colMonto = $availableWidth * 0.25;

            // ===== ASIGNACIONES =====
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'ASIGNACIONES', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // Cabecera de tabla
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 6, 'Monto', 1, 1, 'C', true);

            // Datos asignaciones
            $pdf->SetFont('helvetica', '', 9);
            $asignaciones = array_filter($details, fn($d) => $d['tipo'] === 'A');
            foreach ($asignaciones as $asignacion) {
                $pdf->Cell($colCodigo, 6, $asignacion['concepto'], 1, 0, 'L');
                $pdf->Cell($colDescripcion, 6, $asignacion['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell($colMonto, 6, '$' . number_format($asignacion['monto'], 2), 1, 1, 'R');
            }

            // Total asignaciones
            $pdf->SetFillColor(255, 180, 120); // Naranja claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL ASIGNACIONES:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($totals['total_asignaciones'], 2), 1, 1, 'R', true);
            $pdf->Ln(3);

            // ===== DEDUCCIONES =====
            $pdf->SetFillColor(255, 140, 0); // Naranja intenso (mismo que asignaciones)
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 7, 'DEDUCCIONES', 1, 1, 'C', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // Cabecera de tabla
            $pdf->SetFillColor(224, 224, 224); // Gris claro
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo, 6, 'Código', 1, 0, 'C', true);
            $pdf->Cell($colDescripcion, 6, 'Descripción', 1, 0, 'C', true);
            $pdf->Cell($colMonto, 6, 'Monto', 1, 1, 'C', true);

            // Datos deducciones
            $pdf->SetFont('helvetica', '', 9);
            $deducciones = array_filter($details, fn($d) => $d['tipo'] === 'D');
            foreach ($deducciones as $deduccion) {
                $pdf->Cell($colCodigo, 6, $deduccion['concepto'], 1, 0, 'L');
                $pdf->Cell($colDescripcion, 6, $deduccion['concepto_descripcion'], 1, 0, 'L');
                $pdf->Cell($colMonto, 6, '$' . number_format($deduccion['monto'], 2), 1, 1, 'R');
            }

            // Total deducciones
            $pdf->SetFillColor(255, 180, 120); // Naranja claro (mismo que total asignaciones)
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($colCodigo + $colDescripcion, 6, 'TOTAL DEDUCCIONES:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 6, '$' . number_format($totals['total_deducciones'], 2), 1, 1, 'R', true);
            $pdf->Ln(5);

            // ===== TOTAL NETO =====
            $pdf->SetFillColor(25, 118, 210); // Azul profundo
            $pdf->SetTextColor(255, 255, 255); // Texto blanco
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell($colCodigo + $colDescripcion, 8, 'TOTAL NETO A PAGAR:', 1, 0, 'R', true);
            $pdf->Cell($colMonto, 8, '$' . number_format($totals['total_neto'], 2), 1, 1, 'R', true);
            $pdf->SetTextColor(0, 0, 0); // Restaurar texto negro

            // ===== SECCIÓN DE FIRMAS =====
            $pdf->Ln(15); // Espacio antes de firmas
            $availableWidth = ($pageWidth - $margins['left'] - $margins['right']) / 3;

            // Calcular ancho de cada columna de firma (3 columnas)
            $firmaWidth = $availableWidth; // Restar un poco para espacio entre columnas

            $pdf->SetFont('helvetica', '', 9);

            // Primera línea: espacios para firmar
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C'); // Columna 1
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C'); // Columna 2
            $pdf->Cell($firmaWidth, 5, '', 0, 1, 'C'); // Columna 3

            $pdf->Ln(10); // Espacio para la firma

            // Segunda línea: líneas de firma
            $pdf->SetFont('helvetica', '', 9);
            $lineaPadding = 1; // Padding interno para las líneas
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 0, 'C'); // Línea firma 1
            $pdf->Cell($lineaPadding * 2, 5, '', 0, 0); // Espacio entre columnas

            $pdf->Cell($lineaPadding, 5, '', 0, 0); // Padding izquierdo
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 0, 'C'); // Línea firma 2
            $pdf->Cell($lineaPadding * 2, 5, '', 0, 0); // Espacio entre columnas

            $pdf->Cell($lineaPadding, 5, '', 0, 0); // Padding izquierdo
            $pdf->Cell($firmaWidth - ($lineaPadding * 2), 0, '', 'T', 1, 'C'); // Línea firma 3

            $pdf->Ln(2);

            // Tercera línea: etiquetas de firmas
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell($firmaWidth, 5, 'Autorizado por', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Elaborado por', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Recibido por', 0, 1, 'C');

            $pdf->Ln(2);

            // Cuarta línea: Nombres de quien firma
            $gerencia_name = $company_data['legal_representative'] ?? 'N/A';
            $rrhh_name = $company_data['jefe_recursos_humanos'] ?? 'N/A';
            $empleado_name = $employee_info ? $employee_info['name'] : 'N/A';

            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell($firmaWidth, 5, $gerencia_name, 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $rrhh_name, 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $employee_info['firstname'], 0, 1, 'C');

            $pdf->Ln(1);

            // Quinta línea: subtexto (cargo/cédula)
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($firmaWidth, 5, '(Gerencia)', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, '(Recursos Humanos)', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, $employee_info['lastname'], 0, 1, 'C');

            $pdf->Ln(1);

            // Quinta línea: subtexto (cargo/cédula)
            $empleado_cedula = $employee_info ? $employee_info['cedula'] : 'N/A';

            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, '', 0, 0, 'C');
            $pdf->Cell($firmaWidth, 5, 'Cedula: ' . $empleado_cedula, 0, 1, 'C');

            // Generar nombre de archivo
            $filename = 'Liquidacion_' . ($employee_info ? preg_replace('/[^A-Za-z0-9_]/', '_', $employee_info['name']) : 'Planilla') .
                       '_' . date('Y-m-d', strtotime($payroll['fecha'])) . '.pdf';

            // Salida del PDF
            $pdf->Output($filename, 'I');
            exit;

        } catch (PDOException $e) {
            error_log("Error generating PDF: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar PDF: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        } catch (\Exception $e) {
            error_log("Error generating PDF: " . $e->getMessage());
            $this->setToastrMessage('error', 'Error al generar PDF: ' . $e->getMessage(), 'Error de Exportación');
            $this->redirect('/panel/liquidation/payroll-detail/' . $payroll_id);
        }
    }

    /**
     * Insertar logos en el PDF alineados con el título
     */
    private function insertLogosInPDF($pdf, $company_data)
    {
        $logoPath = __DIR__ . '/../../images/logos/';
        $logoHeight = 12; // Altura del logo
        $pageWidth = $pdf->getPageWidth();
        $margin = 15; // Margen desde los bordes

        // Guardar posición actual
        $currentY = $pdf->GetY();

        // Logo izquierdo - alineado con el margen izquierdo
        if (!empty($company_data['logo_izquierdo_reportes'])) {
            $leftLogoPath = $logoPath . $company_data['logo_izquierdo_reportes'];
            if (file_exists($leftLogoPath)) {
                $leftLogoWidth = 25;
                try {
                    $pdf->Image($leftLogoPath, $margin, $currentY, $leftLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo izquierdo: " . $e->getMessage());
                }
            }
        }

        // Logo derecho - alineado con el margen derecho
        if (!empty($company_data['logo_derecho_reportes'])) {
            $rightLogoPath = $logoPath . $company_data['logo_derecho_reportes'];
            if (file_exists($rightLogoPath)) {
                $rightLogoWidth = 35;
                $rightX = $pageWidth - $margin - $rightLogoWidth;
                try {
                    $pdf->Image($rightLogoPath, $rightX, $currentY, $rightLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo derecho: " . $e->getMessage());
                }
            }
        }

        // Logo principal (centro) - solo si no hay logos laterales
        if (empty($company_data['logo_izquierdo_reportes']) &&
            empty($company_data['logo_derecho_reportes']) &&
            !empty($company_data['logo_empresa'])) {
            $mainLogoPath = $logoPath . $company_data['logo_empresa'];
            if (file_exists($mainLogoPath)) {
                $mainLogoWidth = 40;
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                $pdf->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
            }
        }

        // NOMBRE DE LA EMPRESA centrado a la misma altura de los logos
        if (!empty($company_data['company_name'])) {
            // Guardar la fuente actual
            $currentFont = $pdf->getFontFamily();
            $currentSize = $pdf->getFontSizePt();

            // Configurar fuente para el nombre de la empresa
            $pdf->SetFont('helvetica', 'B', 16);

            // Calcular posición centrada
            $companyNameWidth = $pdf->GetStringWidth($company_data['company_name']);
            $centerX = ($pageWidth - $companyNameWidth) / 2;

            // Posicionar el texto a la misma altura de los logos (centrado verticalmente)
            $textY = $currentY + ($logoHeight / 2) - 3; // Centrado vertical con pequeño ajuste

            // Escribir el nombre de la empresa
            $pdf->SetXY($centerX, $textY);
            $pdf->Cell($companyNameWidth, 0, $company_data['company_name'], 0, 0, 'C');

            // Restaurar fuente anterior
            $pdf->SetFont($currentFont, '', $currentSize);
        }

        // Reservar espacio después de los logos para que el contenido esté alineado
        if (!empty($company_data['logo_izquierdo_reportes']) ||
            !empty($company_data['logo_derecho_reportes']) ||
            !empty($company_data['logo_empresa'])) {
            $pdf->SetY(22); // Espacio después de logos
        }
    }
}
