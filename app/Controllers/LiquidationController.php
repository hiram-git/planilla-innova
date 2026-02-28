<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\TenantStorage;
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
                           e.fecha_ingreso, e.sueldo_individual,
                           COALESCE(cargo_directo.nombre, cargo_posicion.nombre) as position_name,
                           s.nombre as situacion_nombre,
                           DATEDIFF(CURDATE(), e.fecha_ingreso) as dias_trabajados,
                           FLOOR(DATEDIFF(CURDATE(), e.fecha_ingreso) / 365) as anos_trabajados
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos cargo_directo ON e.cargo_id = cargo_directo.id
                    LEFT JOIN cargos cargo_posicion ON p.id_cargo = cargo_posicion.id
                    LEFT JOIN situaciones s ON e.situacion_id = s.id
                    WHERE e.situacion_id = 1
                    ORDER BY e.firstname, e.lastname";

            $stmt = $this->db->query($sql);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener terminaciones pendientes
            $sql = "SELECT et.*, e.firstname, e.lastname, e.employee_id, e.document_id
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
            $sql = "SELECT e.*,
                           COALESCE(cargo_directo.nombre, cargo_posicion.nombre) as position_name,
                           s.nombre as situacion_nombre
                    FROM employees e
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    LEFT JOIN cargos cargo_directo ON e.cargo_id = cargo_directo.id
                    LEFT JOIN cargos cargo_posicion ON p.id_cargo = cargo_posicion.id
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
            $notice_period_days = !empty($_POST['notice_period_days']) ? (int)$_POST['notice_period_days'] : 0;

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
    protected function getTerminationData($termination_id)
    {
        $sql = "SELECT et.*, e.id as employee_table_id, e.firstname, e.lastname, e.employee_id, e.document_id,
                       e.fecha_ingreso, e.sueldo_individual, e.tipo_planilla_id,
                       COALESCE(cargo_directo.nombre, cargo_posicion.nombre) as position_name
                FROM employee_terminations et
                INNER JOIN employees e ON et.employee_id = e.id
                LEFT JOIN posiciones p ON e.position_id = p.id
                LEFT JOIN cargos cargo_directo ON e.cargo_id = cargo_directo.id
                LEFT JOIN cargos cargo_posicion ON p.id_cargo = cargo_posicion.id
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
    protected function calculateTotals($calculations)
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

            $liquidationAccumulations = [];
            if (!empty($details)) {
                $employeeId = (int)$details[0]['employee_id'];
                $endDate = $payroll['fecha_hasta'] ?? $payroll['fecha'] ?? date('Y-m-d');
                $accumulatedConceptCodes = ['LIQ001', 'LIQ002', 'LIQ005', 'LIQ007'];
                $accumulatedTypesByConcept = $this->getConceptAccumulatedTypes($accumulatedConceptCodes);
                foreach ($accumulatedConceptCodes as $conceptCode) {
                    $types = $accumulatedTypesByConcept[$conceptCode] ?? ['SALARIO_BASE'];
                    $liquidationAccumulations[$conceptCode] = $this->getLiquidationAccumulatedMonths(
                        $employeeId,
                        $endDate,
                        $types,
                        $conceptCode  // Pasar código de concepto para detectar LIQ007
                    );
                }
            }

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
                'liquidationAccumulations' => $liquidationAccumulations,
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
     * Obtener tipos de acumulados de conceptos según sus fórmulas
     * Protected para permitir acceso desde clases heredadas
     */
    protected function getConceptAccumulatedTypes(array $conceptCodes): array
    {
        if (empty($conceptCodes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($conceptCodes), '?'));
        $sql = "SELECT concepto, formula
                FROM concepto
                WHERE concepto IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($conceptCodes);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $typesByConcept = [];
        foreach ($rows as $row) {
            $concepto = $row['concepto'] ?? '';
            if ($concepto === '') {
                continue;
            }
            $types = $this->extractAccumulatedTypesFromFormula($row['formula'] ?? '');
            if (empty($types)) {
                $types = ['SALARIO_BASE'];
            }
            $typesByConcept[$concepto] = $types;
        }

        return $typesByConcept;
    }

    /**
     * Extraer tipos de acumulados de una fórmula
     * Protected para permitir acceso desde clases heredadas
     */
    protected function extractAccumulatedTypesFromFormula(string $formula): array
    {
        if ($formula === '') {
            return [];
        }

        $types = [];
        $pattern = '/ACUMULADOS\\s*\\(\\s*(?:\"([^\"]+)\"|\\\'([^\\\']+)\\\'|([^,\\)]+))/i';
        if (preg_match_all($pattern, $formula, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $raw = $match[1] ?? ($match[2] ?? ($match[3] ?? ''));
                $raw = trim($raw, " \t\n\r\"'");
                if ($raw === '') {
                    continue;
                }
                foreach (explode(',', $raw) as $item) {
                    $item = trim($item);
                    if ($item !== '') {
                        $types[] = $item;
                    }
                }
            }
        }

        $types = array_values(array_unique($types));
        return $types;
    }

    /**
     * Obtener acumulados mensuales para liquidación
     * Para LIQ007 (XIII mes): usa período trimestral correcto según legislación panameña
     * Para otros conceptos: usa 11 meses hacia atrás
     * Protected para permitir acceso desde clases heredadas
     */
    protected function getLiquidationAccumulatedMonths(int $employeeId, string $terminationDate, $tipoAcumulado = 'SALARIO_BASE', string $conceptCode = ''): array
    {
        try {
            $fechaFin = new \DateTime($terminationDate);

            // Para LIQ007 (XIII mes), usar período trimestral correcto de Panamá
            if ($conceptCode === 'LIQ007') {
                $xiiiMesCalculator = new \App\Services\XIIIMesPeriodoTrimestralCalculator();
                $periodoInfo = $xiiiMesCalculator->determinarPeriodoTrimestral($terminationDate);
                $fechaInicio = new \DateTime($periodoInfo['fecha_inicio']);
                $fechaFin = new \DateTime($periodoInfo['fecha_fin']);

                error_log("LIQ007: Usando período trimestral XIII mes - Período {$periodoInfo['periodo']}: {$periodoInfo['fecha_inicio']} a {$periodoInfo['fecha_fin']}");
            } else {
                // Para otros conceptos, usar 11 meses hacia atrás (período de liquidación estándar)
                $fechaInicio = (clone $fechaFin)->modify('-11 months');
            }

            $tipos = is_array($tipoAcumulado) ? $tipoAcumulado : [$tipoAcumulado];
            $tipos = array_values(array_filter(array_map(static function ($value) {
                return trim((string)$value);
            }, $tipos), static fn($value) => $value !== ''));
            if (empty($tipos)) {
                $tipos = ['SALARIO_BASE'];
            }
            $placeholders = implode(',', array_fill(0, count($tipos), '?'));

            // LEFT JOIN para incluir acumulados importados (planilla_id = 0)
            // Lógica dual: planilla_id = 0 usa ano/mes, planilla_id != 0 usa fechas de planilla_cabecera
            $sql = "SELECT ape.ano, ape.mes, SUM(ape.monto) as total
                    FROM acumulados_por_empleado ape
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.employee_id = ?
                    AND ape.tipo_acumulado IN ($placeholders)
                    AND (
                        (ape.planilla_id = 0 AND DATE(CONCAT(ape.ano, '-', LPAD(ape.mes, 2, '0'), '-01')) BETWEEN ? AND ?)
                        OR
                        (ape.planilla_id != 0 AND pc.fecha_hasta >= ? AND pc.fecha_desde <= ?)
                    )
                    GROUP BY ape.ano, ape.mes
                    ORDER BY ape.ano, ape.mes";

            $stmt = $this->db->prepare($sql);
            $fechaInicioStr = $fechaInicio->format('Y-m-d');
            $fechaFinStr = $fechaFin->format('Y-m-d');
            $stmt->execute(array_merge(
                [$employeeId],
                $tipos,
                [$fechaInicioStr, $fechaFinStr, $fechaInicioStr, $fechaFinStr]
            ));
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $byMonth = [];
            foreach ($rows as $row) {
                $key = sprintf('%04d-%02d', (int)$row['ano'], (int)$row['mes']);
                $byMonth[$key] = (float)$row['total'];
            }

            // Calcular meses a mostrar según el período
            $numMonths = $conceptCode === 'LIQ007' ? 4 : 12; // XIII mes: 4 meses del trimestre, otros: 12 meses
            $months = [];
            $total = 0.0;
            $cursor = (clone $fechaInicio)->modify('first day of this month');

            for ($i = 0; $i < $numMonths; $i++) {
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
                'total' => $total,
                'concept' => $conceptCode,
                'periodo_info' => $conceptCode === 'LIQ007' ? $periodoInfo : null
            ];
        } catch (\Exception $e) {
            error_log("Error building liquidation accumulations: " . $e->getMessage());
        }

        // Fallback en caso de error
        $numMonths = $conceptCode === 'LIQ007' ? 4 : 12;
        $months = [];
        $cursor = new \DateTime(date('Y-m-01'));
        $cursor->modify($conceptCode === 'LIQ007' ? '-3 months' : '-11 months');

        for ($i = 0; $i < $numMonths; $i++) {
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
}
// NOTA: Los métodos de reportes (preview, exportPayrollExcel, exportPayrollPdf)
// han sido movidos a LiquidationReportController para mejor organización del código
