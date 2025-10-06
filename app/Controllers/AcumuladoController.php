<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payroll;
use App\Models\Employee;
use App\Models\TipoAcumulado;

/**
 * AcumuladoController
 * Controlador para visualización de acumulados de empleados
 */
class AcumuladoController extends Controller
{
    private $payrollModel;
    private $employeeModel;
    private $tipoAcumuladoModel;

    public function __construct()
    {
        parent::__construct();
        $this->payrollModel = new Payroll();
        $this->employeeModel = new Employee();
        $this->tipoAcumuladoModel = new TipoAcumulado();
    }

    /**
     * Vista principal de acumulados
     */
    public function index()
    {
        $year = $_GET['year'] ?? date('Y');
        $empleadoId = $_GET['empleado_id'] ?? null;
        $tipoPlanillaId = $_GET['tipo_planilla'] ?? null;

        // Obtener lista de empleados para filtro (filtrados por tipo de planilla si aplica)
        $employees = $tipoPlanillaId
            ? $this->employeeModel->getEmployeesByTipoPlanilla($tipoPlanillaId)
            : $this->employeeModel->getAllEmployees();
        
        // Si se especifica un empleado, obtener sus acumulados
        $acumulados = [];
        $selectedEmployee = null;
        $tiposAcumulados = [];

        if ($empleadoId) {
            $selectedEmployee = $this->employeeModel->getEmployeeWithFullDetails($empleadoId);
            $acumulados = $this->getAcumuladosByEmployeeAndType($empleadoId, $year);
            $tiposAcumulados = $this->tipoAcumuladoModel->getActivos(); // No se usan cuando hay empleado específico
        } else {
            // Vista para todos los empleados: mostrar tipos con totales agregados
            $tiposAcumulados = $this->getTiposAcumuladosWithTotals($year, $tipoPlanillaId);
        }
        
        $data = [
            'title' => 'Acumulados de Empleados',
            'employees' => $employees,
            'tiposAcumulados' => $tiposAcumulados,
            'acumulados' => $acumulados,
            'selectedEmployee' => $selectedEmployee,
            'selectedYear' => $year,
            'years' => $this->getAvailableYears(),
            'scriptFiles' => [
                'assets/javascript/modules/acumulados/common.js?' . date('His')
            ]
        ];

        $this->render('admin/acumulados/index', $data);
    }

    /**
     * Vista de resumen por empleado
     */
    public function employee($empleadoId)
    {
        $employee = $this->employeeModel->getEmployeeWithFullDetails($empleadoId);
        
        if (!$employee) {
            header('HTTP/1.1 404 Not Found');
            $this->render('errors/404', ['title' => 'Empleado no encontrado']);
            return;
        }
        
        $year = $_GET['year'] ?? date('Y');
        $acumulados = $this->getAcumuladosByEmployee($empleadoId, $year);
        
        // Calcular XIII Mes actual
        $xiiiMes = $this->payrollModel->calculateXIIIMes($empleadoId, $year);
        
        $data = [
            'title' => 'Acumulados: ' . $employee['firstname'] . ' ' . $employee['lastname'],
            'employee' => $employee,
            'acumulados' => $acumulados,
            'xiiiMes' => $xiiiMes,
            'selectedYear' => $year,
            'years' => $this->getAvailableYears()
        ];
        
        $this->render('admin/acumulados/employee', $data);
    }

    /**
     * Vista de acumulados por tipo de acumulado (usando código)
     */
    public function byTypeId($tipoCodigoOrId)
    {
        // Intentar obtener por código primero, luego por ID
        $tipo = $this->tipoAcumuladoModel->getByCodigo($tipoCodigoOrId);

        if (!$tipo && is_numeric($tipoCodigoOrId)) {
            $tipo = $this->tipoAcumuladoModel->getById($tipoCodigoOrId);
        }

        if (!$tipo) {
            header('HTTP/1.1 404 Not Found');
            $this->render('errors/404', ['title' => 'Tipo de acumulado no encontrado']);
            return;
        }

        $year = $_GET['year'] ?? date('Y');
        // Usar el código del tipo de acumulado para filtrar
        $acumulados = $this->getAcumuladosByType($tipo['codigo'], $year);

        $data = [
            'title' => 'Acumulados: ' . $tipo['descripcion'],
            'tipo' => $tipo,
            'acumulados' => $acumulados,
            'selectedYear' => $year,
            'years' => $this->getAvailableYears()
        ];

        $this->render('admin/acumulados/by-type', $data);
    }

    /**
     * Exportar acumulados a CSV
     */
    public function export()
    {
        $year = $_GET['year'] ?? date('Y');
        $conceptoId = $_GET['concepto_id'] ?? null;
        $empleadoId = $_GET['empleado_id'] ?? null;
        
        // Construir consulta base usando la nueva estructura
        $sql = "SELECT 
                    e.document_id,
                    CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                    c.descripcion as concepto_descripcion,
                    ape.monto as total_acumulado,
                    ape.mes,
                    ape.ano,
                    ape.frecuencia,
                    ape.tipo_concepto,
                    ape.created_at as fecha_calculo
                FROM acumulados_por_empleado ape
                INNER JOIN employees e ON ape.employee_id = e.id
                INNER JOIN concepto c ON ape.concepto_id = c.id
                WHERE ape.ano = ?";
        
        $params = [$year];
        
        if ($conceptoId) {
            $sql .= " AND c.id = ?";
            $params[] = $conceptoId;
        }
        
        if ($empleadoId) {
            $sql .= " AND e.id = ?";
            $params[] = $empleadoId;
        }
        
        $sql .= " ORDER BY e.lastname, e.firstname, c.descripcion, ape.mes";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Generar CSV
        $filename = "acumulados_{$year}_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Encabezados
        fputcsv($output, [
            'Cédula',
            'Nombre Empleado', 
            'Concepto',
            'Monto',
            'Mes',
            'Año',
            'Frecuencia',
            'Tipo Concepto',
            'Fecha Cálculo'
        ]);
        
        // Datos
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Obtener acumulados por empleado
     */
    private function getAcumuladosByEmployee($empleadoId, $year)
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "SELECT
                        c.id as concepto_id,
                        c.descripcion as concepto_descripcion,
                        ape.tipo_concepto,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(ape.planilla_id) as total_planillas,
                        MIN(ape.created_at) as fecha_primer_calculo,
                        MAX(ape.created_at) as fecha_ultimo_calculo,
                        pc.descripcion as ultima_planilla,
                        ape.frecuencia,
                        ape.ano
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    GROUP BY c.id, c.descripcion, ape.tipo_concepto, ape.frecuencia, ape.ano
                    ORDER BY ape.tipo_concepto, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados por tipo de acumulado (agrupados por concepto)
     */
    private function getAcumuladosByType($tipoAcumuladoCodigo, $year)
    {
        try {
            $sql = "SELECT
                        c.id as concepto_id,
                        c.concepto as concepto_codigo,
                        c.descripcion as concepto_descripcion,
                        ape.tipo_concepto,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(DISTINCT ape.employee_id) as total_empleados,
                        COUNT(DISTINCT ape.planilla_id) as total_planillas,
                        COUNT(DISTINCT ape.ano) as total_anos,
                        MIN(ape.created_at) as fecha_primer_calculo,
                        MAX(ape.created_at) as fecha_ultimo_calculo,
                        GROUP_CONCAT(DISTINCT ape.ano ORDER BY ape.ano SEPARATOR ', ') as anos_procesados,
                        GROUP_CONCAT(DISTINCT CONCAT(e.firstname, ' ', e.lastname) ORDER BY e.lastname SEPARATOR ', ') as empleados_list
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    INNER JOIN employees e ON ape.employee_id = e.id
                    WHERE ape.tipo_acumulado = ? AND ape.ano = ?
                    GROUP BY c.id, c.concepto, c.descripcion, ape.tipo_concepto
                    ORDER BY ape.tipo_concepto, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipoAcumuladoCodigo, $year]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener años disponibles
     */
    private function getAvailableYears()
    {
        try {
            $sql = "SELECT DISTINCT ano as year 
                    FROM acumulados_por_empleado 
                    ORDER BY year DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $years = array_column($results, 'year');

            // Asegurar que el año actual esté incluido
            $currentYear = (int)date('Y');
            if (!in_array($currentYear, $years)) {
                array_unshift($years, $currentYear);
            }

            // Agregar opción "Todos" al inicio
            array_unshift($years, 'todos');

            return $years;
        } catch (\PDOException $e) {
            error_log("Error obteniendo años disponibles: " . $e->getMessage());
            // Si hay error, retornar al menos los últimos 3 años
            $currentYear = (int)date('Y');
            return ['todos', $currentYear, $currentYear - 1, $currentYear - 2];
        }
    }

    /**
     * Vista de acumulados por planilla específica desde acumulados_por_planilla
     */
    public function byPayroll($payrollId = null)
    {
        // Si viene como array de parámetros, tomar el primer elemento
        if (is_array($payrollId)) {
            $payrollId = $payrollId[0] ?? null;
        } elseif (func_num_args() > 0 && func_get_arg(0) && is_array(func_get_args())) {
            $args = func_get_args();
            $payrollId = $payrollId[0] ?? null;
        }

        // También permitir obtener planilla_id desde GET
        $payrollId = $payrollId ?? $_GET['planilla_id'] ?? null;

        try {
            // Obtener lista de planillas para el selector
            $planillas = $this->getAvailablePayrolls();

            $payroll = null;
            $acumulados = [];
            $totales = null;

            if ($payrollId) {
                // Obtener información de la planilla
                $payroll = $this->payrollModel->find($payrollId);
                if ($payroll) {
                    // Obtener acumulados desde acumulados_por_planilla
                    $acumulados = $this->getAcumuladosFromPlanillaTable($payrollId);
                    $totales = $this->getTotalesFromPlanillaTable($payrollId);
                }
            }

            $this->render('admin/acumulados/by_payroll', [
                'planillas' => $planillas,
                'payroll' => $payroll,
                'acumulados' => $acumulados,
                'totales' => $totales,
                'selectedPayrollId' => $payrollId
            ]);

        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@byPayroll: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por planilla';
            header('Location: /panel/payrolls');
            exit;
        }
    }

    /**
     * Obtener acumulados específicos de una planilla
     */
    private function getAcumuladosByPayroll($payrollId)
    {
        try {
            $sql = "SELECT 
                        ape.id,
                        ape.planilla_id,
                        ape.employee_id as empleado_id,
                        ape.concepto_id,
                        ape.monto as monto_acumulado,
                        ape.mes,
                        ape.ano,
                        ape.frecuencia,
                        ape.tipo_concepto,
                        ape.created_at,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto as tipo_codigo,
                        c.descripcion as tipo_descripcion
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    WHERE ape.planilla_id = ?
                    ORDER BY e.lastname, e.firstname, c.tipo_concepto, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vista de acumulados por tipo de acumulado con agrupación
     * Ruta: /panel/acumulados/byType
     */
    public function byType()
    {
        $year = $_GET['year'] ?? date('Y');
        $tipoAcumuladoCodigo = $_GET['tipo_acumulado'] ?? null;
        $month = $_GET['month'] ?? null;
        $groupBy = $_GET['group_by'] ?? 'empleado'; // empleado, mes, ano

        try {
            // Obtener tipos de acumulados disponibles
            $tiposAcumulados = $this->getTiposAcumuladosForFilter();

            // Si se especifica un tipo, filtrar por tipo_acumulado
            $acumulados = [];
            $acumuladosAgrupados = [];
            $selectedTipo = null;

            if ($tipoAcumuladoCodigo) {
                // Buscar el tipo seleccionado
                $selectedTipo = $this->tipoAcumuladoModel->getByCodigo($tipoAcumuladoCodigo);

                if ($selectedTipo) {
                    // Obtener registros detallados
                    $acumulados = $this->getAcumuladosByTipoAcumulado($tipoAcumuladoCodigo, $year, $month);

                    // Obtener datos agrupados según el filtro
                    $acumuladosAgrupados = $this->getAcumuladosAgrupadosByTipo(
                        $tipoAcumuladoCodigo,
                        $year,
                        $month,
                        $groupBy
                    );
                }
            }

            $this->render('admin/acumulados/by_type', [
                'year' => $year,
                'month' => $month,
                'groupBy' => $groupBy,
                'tiposAcumulados' => $tiposAcumulados,
                'tipoAcumuladoCodigo' => $tipoAcumuladoCodigo,
                'selectedTipo' => $selectedTipo,
                'acumulados' => $acumulados,
                'acumuladosAgrupados' => $acumuladosAgrupados,
                'availableYears' => $this->getAvailableYears(),
                'availableMonths' => $this->getAvailableMonths()
            ]);

        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@byType: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por tipo';
            header('Location: /panel/acumulados');
            exit;
        }
    }

    /**
     * Vista de acumulados por concepto específico con agrupación
     * Ruta: /panel/acumulados/byConcepto
     */
    public function byConcepto()
    {
        $year = $_GET['year'] ?? date('Y');
        $conceptoId = $_GET['concepto_id'] ?? null;
        $month = $_GET['month'] ?? null;
        $groupBy = $_GET['group_by'] ?? 'empleado'; // año, planilla, empleado

        try {
            // Obtener conceptos disponibles
            $conceptos = $this->getConceptosForFilter();

            // Si se especifica un concepto, filtrar por concepto_id
            $acumulados = [];
            $acumuladosAgrupados = [];
            $selectedConcepto = null;

            if ($conceptoId) {
                // Buscar el concepto seleccionado
                foreach ($conceptos as $concepto) {
                    if ($concepto['id'] == $conceptoId) {
                        $selectedConcepto = $concepto;
                        break;
                    }
                }

                if ($selectedConcepto) {
                    // Obtener registros detallados
                    $acumulados = $this->getAcumuladosByConcepto($conceptoId, $year, $month);

                    // Obtener datos agrupados según el filtro
                    $acumuladosAgrupados = $this->getAcumuladosAgrupadosByConcepto(
                        $conceptoId,
                        $year,
                        $month,
                        $groupBy
                    );
                }
            }

            $this->render('admin/acumulados/by_concept', [
                'year' => $year,
                'month' => $month,
                'groupBy' => $groupBy,
                'conceptos' => $conceptos,
                'conceptoId' => $conceptoId,
                'selectedConcepto' => $selectedConcepto,
                'acumulados' => $acumulados,
                'acumuladosAgrupados' => $acumuladosAgrupados,
                'availableYears' => $this->getAvailableYears(),
                'availableMonths' => $this->getAvailableMonths()
            ]);

        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@byConcepto: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por concepto';
            header('Location: /panel/acumulados');
            exit;
        }
    }

    /**
     * Vista de acumulados por empleado específico
     */
    public function byEmployee()
    {
        $year = $_GET['year'] ?? date('Y');
        $empleadoId = $_GET['empleado_id'] ?? null;
        $month = $_GET['month'] ?? null;
        $tipoAcumulado = $_GET['tipo_acumulado'] ?? null;
        $tipoPlanillaId = $_GET['tipo_planilla'] ?? null;
        $groupBy = $_GET['group_by'] ?? 'tipo_acumulado'; // tipo_acumulado, mes, planilla

        try {
            // Obtener lista de empleados activos (filtrados por tipo de planilla si aplica)
            $employees = $tipoPlanillaId
                ? $this->employeeModel->getEmployeesByTipoPlanilla($tipoPlanillaId)
                : $this->employeeModel->getAllEmployees();

            // Obtener tipos de acumulados disponibles
            $tiposAcumulados = $this->getTiposAcumulados();

            // Si se especifica un empleado, obtener sus acumulados
            $acumulados = [];
            $acumuladosAgrupados = [];
            $selectedEmployee = null;
            $totales = null;

            if ($empleadoId) {
                $selectedEmployee = $this->employeeModel->getEmployeeWithFullDetails($empleadoId);
                if ($selectedEmployee) {
                    // Obtener registros detallados
                    $acumulados = $this->getAcumuladosDetalleByEmployee($empleadoId, $year, $month, $tipoAcumulado);

                    // Obtener datos agrupados
                    $acumuladosAgrupados = $this->getAcumuladosAgrupadosByEmployee(
                        $empleadoId,
                        $year,
                        $month,
                        $tipoAcumulado,
                        $groupBy
                    );

                    $totales = $this->getTotalesAcumuladosByEmployee($empleadoId, $year, $month, $tipoAcumulado);
                }
            }

            $this->render('admin/acumulados/by_employee', [
                'year' => $year,
                'month' => $month,
                'tipoAcumulado' => $tipoAcumulado,
                'groupBy' => $groupBy,
                'employees' => $employees,
                'tiposAcumulados' => $tiposAcumulados,
                'selectedEmployee' => $selectedEmployee,
                'acumulados' => $acumulados,
                'acumuladosAgrupados' => $acumuladosAgrupados,
                'totales' => $totales,
                'availableYears' => $this->getAvailableYears(),
                'availableMonths' => $this->getAvailableMonths(),
                'scriptFiles' => [
                    'assets/javascript/modules/acumulados/common.js?' . date('His')
                ]
            ]);

        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@byEmployee: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por empleado';
            header('Location: /panel/acumulados');
            exit;
        }
    }

    /**
     * Vista de acumulados por todos los empleados desglosada por conceptos
     */
    public function allEmployees()
    {
        $year = $_GET['year'] ?? date('Y');
        $selectedConcepto = $_GET['concepto_id'] ?? '';
        $tipoConcepto = $_GET['tipo_concepto'] ?? '';
        
        try {
            // Obtener lista de conceptos para el filtro
            $conceptos = $this->getConceptosForFilter();
            
            // Obtener acumulados agrupados por empleado y concepto
            $acumulados = $this->getAllEmployeesAccumulated($year, $selectedConcepto, $tipoConcepto);
            
            $this->render('admin/acumulados/all_employees', [
                'year' => $year,
                'selectedConcepto' => $selectedConcepto,
                'tipoConcepto' => $tipoConcepto,
                'conceptos' => $conceptos,
                'acumulados' => $acumulados,
                'availableYears' => $this->getAvailableYears()
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@allEmployees: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por empleados';
            header('Location: /panel/acumulados');
            exit;
        }
    }

    /**
     * Obtener conceptos para filtro
     */
    private function getConceptosForFilter()
    {
        try {
            $sql = "SELECT DISTINCT
                        c.id,
                        c.descripcion,
                        c.tipo_concepto
                    FROM concepto c
                    INNER JOIN acumulados_por_empleado ape ON c.id = ape.concepto_id
                    ORDER BY c.tipo_concepto, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo conceptos para filtro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de acumulados para filtro
     */
    private function getTiposAcumuladosForFilter()
    {
        try {
            $sql = "SELECT DISTINCT
                        ta.codigo,
                        ta.descripcion
                    FROM tipos_acumulados ta
                    INNER JOIN acumulados_por_empleado ape ON ta.codigo = ape.tipo_acumulado
                    ORDER BY ta.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo tipos de acumulados para filtro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados por tipo de acumulado
     */
    private function getAcumuladosByTipoAcumulado($tipoAcumuladoCodigo, $year, $month = null)
    {
        try {
            $whereClause = "ape.tipo_acumulado = ? AND ape.ano = ?";
            $params = [$tipoAcumuladoCodigo, $year];

            if ($month) {
                $whereClause .= " AND ape.mes = ?";
                $params[] = $month;
            }

            $sql = "SELECT
                        ape.*,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        e.document_id,
                        c.descripcion as concepto_descripcion,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha_inicio,
                        pc.fecha_fin
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    ORDER BY e.lastname, e.firstname, ape.ano, ape.mes";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por tipo de acumulado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados agrupados por tipo de acumulado
     */
    private function getAcumuladosAgrupadosByTipo($tipoAcumuladoCodigo, $year, $month = null, $groupBy = 'empleado')
    {
        try {
            $whereClause = "ape.tipo_acumulado = ? AND ape.ano = ?";
            $params = [$tipoAcumuladoCodigo, $year];

            if ($month) {
                $whereClause .= " AND ape.mes = ?";
                $params[] = $month;
            }

            $sql = "";

            switch ($groupBy) {
                case 'mes':
                    $sql = "SELECT
                                ape.mes as grupo_id,
                                CONCAT('Mes ', ape.mes) as grupo_descripcion,
                                SUM(ape.monto) as total_monto,
                                COUNT(DISTINCT ape.planilla_id) as total_planillas,
                                COUNT(DISTINCT ape.employee_id) as total_empleados
                            FROM acumulados_por_empleado ape
                            WHERE {$whereClause}
                            GROUP BY ape.mes
                            ORDER BY ape.mes";
                    break;

                case 'ano':
                    $sql = "SELECT
                                ape.ano as grupo_id,
                                CAST(ape.ano AS CHAR) as grupo_descripcion,
                                SUM(ape.monto) as total_monto,
                                COUNT(DISTINCT ape.planilla_id) as total_planillas,
                                COUNT(DISTINCT ape.employee_id) as total_empleados
                            FROM acumulados_por_empleado ape
                            WHERE {$whereClause}
                            GROUP BY ape.ano
                            ORDER BY ape.ano";
                    break;

                case 'empleado':
                default:
                    $sql = "SELECT
                                ape.employee_id as grupo_id,
                                CONCAT(e.firstname, ' ', e.lastname) as grupo_descripcion,
                                e.document_id,
                                SUM(ape.monto) as total_monto,
                                COUNT(DISTINCT ape.planilla_id) as total_planillas,
                                COUNT(DISTINCT ape.employee_id) as total_empleados
                            FROM acumulados_por_empleado ape
                            INNER JOIN employees e ON ape.employee_id = e.id
                            WHERE {$whereClause}
                            GROUP BY ape.employee_id, e.firstname, e.lastname, e.document_id
                            ORDER BY e.lastname, e.firstname";
                    break;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados agrupados por tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados por concepto específico desde acumulados_por_empleado
     */
    private function getAcumuladosByConcepto($conceptoId, $year, $month = null)
    {
        try {
            $whereConditions = ["ape.concepto_id = ?", "ape.ano = ?"];
            $params = [$conceptoId, $year];

            if ($month) {
                $whereConditions[] = "ape.mes = ?";
                $params[] = $month;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "SELECT
                        ape.id,
                        ape.employee_id,
                        ape.concepto_id,
                        ape.planilla_id,
                        ape.monto,
                        ape.mes,
                        ape.ano,
                        ape.frecuencia,
                        ape.tipo_concepto,
                        ape.created_at,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        c.descripcion as concepto_descripcion,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha_inicio,
                        pc.fecha_fin
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    ORDER BY ape.mes DESC, e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por concepto: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados agrupados por año, planilla o empleado
     */
    private function getAcumuladosAgrupadosByConcepto($conceptoId, $year, $month = null, $groupBy = 'empleado')
    {
        try {
            $whereConditions = ["ape.concepto_id = ?", "ape.ano = ?"];
            $params = [$conceptoId, $year];

            if ($month) {
                $whereConditions[] = "ape.mes = ?";
                $params[] = $month;
            }

            $whereClause = implode(" AND ", $whereConditions);

            // Determinar agrupación y campos SELECT
            switch ($groupBy) {
                case 'ano':
                    $selectFields = "ape.ano as grupo_clave,
                                    CAST(ape.ano AS CHAR) as grupo_descripcion,
                                    'Año' as grupo_tipo";
                    $groupByClause = "ape.ano";
                    $orderByClause = "ape.ano DESC";
                    break;

                case 'planilla':
                    $selectFields = "pc.id as grupo_clave,
                                    pc.descripcion as grupo_descripcion,
                                    'Planilla' as grupo_tipo,
                                    pc.fecha_inicio,
                                    pc.fecha_fin";
                    $groupByClause = "pc.id, pc.descripcion, pc.fecha_inicio, pc.fecha_fin";
                    $orderByClause = "pc.fecha_inicio DESC";
                    break;

                case 'empleado':
                default:
                    $selectFields = "e.id as grupo_clave,
                                    CONCAT(e.firstname, ' ', e.lastname) as grupo_descripcion,
                                    e.document_id,
                                    'Empleado' as grupo_tipo";
                    $groupByClause = "e.id, e.firstname, e.lastname, e.document_id";
                    $orderByClause = "e.lastname, e.firstname";
                    break;
            }

            $sql = "SELECT
                        {$selectFields},
                        SUM(ape.monto) as total_monto,
                        COUNT(DISTINCT ape.planilla_id) as total_planillas,
                        COUNT(DISTINCT ape.employee_id) as total_empleados,
                        COUNT(DISTINCT ape.ano) as total_anos,
                        MIN(ape.created_at) as fecha_primer_registro,
                        MAX(ape.created_at) as fecha_ultimo_registro
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    GROUP BY {$groupByClause}
                    ORDER BY {$orderByClause}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados agrupados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados agrupados por empleado
     */
    private function getAcumuladosAgrupadosByEmployee($empleadoId, $year, $month = null, $tipoAcumulado = null, $groupBy = 'tipo_acumulado')
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            if ($month) {
                $whereConditions[] = "ape.mes = ?";
                $params[] = $month;
            }

            if ($tipoAcumulado) {
                $whereConditions[] = "ape.tipo_acumulado = ?";
                $params[] = $tipoAcumulado;
            }

            $whereClause = implode(" AND ", $whereConditions);

            // Determinar agrupación y campos SELECT
            switch ($groupBy) {
                case 'mes':
                    $selectFields = "ape.mes as grupo_clave,
                                    CASE ape.mes
                                        WHEN 1 THEN 'Enero'
                                        WHEN 2 THEN 'Febrero'
                                        WHEN 3 THEN 'Marzo'
                                        WHEN 4 THEN 'Abril'
                                        WHEN 5 THEN 'Mayo'
                                        WHEN 6 THEN 'Junio'
                                        WHEN 7 THEN 'Julio'
                                        WHEN 8 THEN 'Agosto'
                                        WHEN 9 THEN 'Septiembre'
                                        WHEN 10 THEN 'Octubre'
                                        WHEN 11 THEN 'Noviembre'
                                        WHEN 12 THEN 'Diciembre'
                                    END as grupo_descripcion,
                                    'Mes' as grupo_tipo";
                    $groupByClause = "ape.mes";
                    $orderByClause = "ape.mes";
                    break;

                case 'planilla':
                    $selectFields = "pc.id as grupo_clave,
                                    COALESCE(pc.descripcion, 'Sin planilla') as grupo_descripcion,
                                    'Planilla' as grupo_tipo,
                                    pc.fecha_inicio,
                                    pc.fecha_fin";
                    $groupByClause = "pc.id, pc.descripcion, pc.fecha_inicio, pc.fecha_fin";
                    $orderByClause = "pc.fecha_inicio DESC";
                    break;

                case 'tipo_acumulado':
                default:
                    $selectFields = "ape.tipo_acumulado as grupo_clave,
                                    COALESCE(ta.descripcion, ape.tipo_acumulado, 'Sin tipo') as grupo_descripcion,
                                    'Tipo Acumulado' as grupo_tipo,
                                    ape.tipo_concepto";
                    $groupByClause = "ape.tipo_acumulado, ta.descripcion, ape.tipo_concepto";
                    $orderByClause = "ape.tipo_acumulado";
                    break;
            }

            $sql = "SELECT
                        {$selectFields},
                        SUM(ape.monto) as total_monto,
                        COUNT(DISTINCT ape.planilla_id) as total_planillas,
                        COUNT(DISTINCT ape.concepto_id) as total_conceptos,
                        COUNT(DISTINCT ape.mes) as total_meses,
                        MIN(ape.created_at) as fecha_primer_registro,
                        MAX(ape.created_at) as fecha_ultimo_registro
                    FROM acumulados_por_empleado ape
                    LEFT JOIN tipos_acumulados ta ON ape.tipo_acumulado = ta.codigo
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    GROUP BY {$groupByClause}
                    ORDER BY {$orderByClause}";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados agrupados por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener meses disponibles
     */
    private function getAvailableMonths()
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
    }

    /**
     * Obtener detalle de acumulados por empleado desde acumulados_por_empleado
     */
    private function getAcumuladosDetalleByEmployee($empleadoId, $year, $month = null, $tipoAcumulado = null)
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            if ($month) {
                $whereConditions[] = "ape.mes = ?";
                $params[] = $month;
            }

            if ($tipoAcumulado) {
                $whereConditions[] = "ape.tipo_acumulado = ?";
                $params[] = $tipoAcumulado;
            }

            $whereClause = implode(" AND ", $whereConditions);

             $sql = "SELECT
                        ape.id,
                        ape.employee_id,
                        ape.concepto_id,
                        ape.planilla_id,
                        ape.monto,
                        ape.mes,
                        ape.ano,
                        ape.frecuencia,
                        ape.tipo_concepto,
                        ape.created_at,
                        c.descripcion as concepto_descripcion,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha_desde,
                        pc.fecha_hasta, 
                        ape.tipo_acumulado
                    FROM acumulados_por_empleado ape
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE {$whereClause}
                    ORDER BY ape.mes DESC, ape.tipo_concepto, c.descripcion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo detalle acumulados por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener totales de acumulados por empleado agrupados por tipo
     */
    private function getTotalesAcumuladosByEmployee($empleadoId, $year, $month = null, $tipoAcumulado = null)
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            if ($month) {
                $whereConditions[] = "ape.mes = ?";
                $params[] = $month;
            }

            if ($tipoAcumulado) {
                $whereConditions[] = "ape.tipo_acumulado = ?";
                $params[] = $tipoAcumulado;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "SELECT
                        ape.tipo_concepto,
                        SUM(ape.monto) as total_monto,
                        COUNT(*) as total_registros,
                        COUNT(DISTINCT ape.concepto_id) as total_conceptos
                    FROM acumulados_por_empleado ape
                    WHERE {$whereClause}
                    GROUP BY ape.tipo_concepto
                    ORDER BY ape.tipo_concepto";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear resultado para fácil acceso
            $totales = [
                'ASIGNACION' => ['total_monto' => 0, 'total_registros' => 0, 'total_conceptos' => 0],
                'DEDUCCION' => ['total_monto' => 0, 'total_registros' => 0, 'total_conceptos' => 0]
            ];

            foreach ($result as $row) {
                $totales[$row['tipo_concepto']] = $row;
            }

            // Calcular neto
            $totales['NETO'] = $totales['ASIGNACION']['total_monto'] - $totales['DEDUCCION']['total_monto'];

            return $totales;

        } catch (\PDOException $e) {
            error_log("Error obteniendo totales acumulados por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados por empleado agrupados por tipo de acumulado
     */
    private function getAcumuladosByEmployeeAndType($empleadoId, $year)
    {
        try {
            $whereConditions = ["ape.employee_id = ?"];
            $params = [$empleadoId];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            $whereClause = implode(" AND ", $whereConditions);

            $sql = "SELECT
                        ape.tipo_acumulado as tipo_codigo,
                        ape.tipo_acumulado as codigo,
                        COALESCE(ta.descripcion, CONCAT('Tipo: ', ape.tipo_acumulado)) as tipo_descripcion,
                        COALESCE(ta.descripcion, CONCAT('Tipo: ', ape.tipo_acumulado)) as descripcion,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(DISTINCT ape.concepto_id) as total_conceptos_incluidos,
                        COUNT(ape.planilla_id) as total_planillas,
                        MIN(ape.created_at) as fecha_primer_calculo,
                        MAX(ape.created_at) as fecha_ultimo_calculo,
                        GROUP_CONCAT(DISTINCT c.concepto ORDER BY c.concepto SEPARATOR ', ') as conceptos_incluidos,
                        GROUP_CONCAT(DISTINCT c.descripcion ORDER BY c.descripcion SEPARATOR ' | ') as conceptos_descripcion
                    FROM acumulados_por_empleado ape
                    LEFT JOIN tipos_acumulados ta ON ape.tipo_acumulado = ta.codigo
                    LEFT JOIN concepto c ON ape.concepto_id = c.id
                    WHERE {$whereClause} AND ape.tipo_acumulado IS NOT NULL AND ape.tipo_acumulado != ''
                    GROUP BY ape.tipo_acumulado, ta.descripcion
                    ORDER BY ape.tipo_acumulado";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por empleado y tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de acumulados disponibles
     */
    private function getTiposAcumulados()
    {
        try {
            $sql = "SELECT DISTINCT
                        ape.tipo_acumulado as codigo,
                        COALESCE(ta.descripcion, ape.tipo_acumulado) as descripcion
                    FROM acumulados_por_empleado ape
                    LEFT JOIN tipos_acumulados ta ON ape.tipo_acumulado = ta.codigo
                    WHERE ape.tipo_acumulado IS NOT NULL AND ape.tipo_acumulado != ''
                    ORDER BY ta.descripcion, ape.tipo_acumulado";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo tipos de acumulados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados de todos los empleados agrupados por concepto
     */
    private function getAllEmployeesAccumulated($year, $conceptoId = null, $tipoConcepto = null)
    {
        try {
            $whereConditions = ["ape.ano = ?"];
            $params = [$year];
            
            // Filtro por concepto específico
            if (!empty($conceptoId)) {
                $whereConditions[] = "c.id = ?";
                $params[] = $conceptoId;
            }
            
            // Filtro por tipo de concepto
            if (!empty($tipoConcepto)) {
                $whereConditions[] = "c.tipo_concepto = ?";
                $params[] = $tipoConcepto;
            }
            
            $whereClause = implode(" AND ", $whereConditions);
            
            $sql = "SELECT 
                        e.id as employee_id,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
                        p.description as position,
                        c.id as concepto_id,
                        c.descripcion as concepto_descripcion,
                        c.tipo_concepto,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(DISTINCT ape.planilla_id) as total_planillas,
                        ape.frecuencia,
                        MAX(ape.created_at) as fecha_ultimo_calculo
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN positions p ON e.position_id = p.id
                    WHERE {$whereClause}
                    GROUP BY e.id, c.id, ape.frecuencia
                    ORDER BY e.lastname, e.firstname, c.tipo_concepto, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Agrupar por empleado
            $acumuladosAgrupados = [];
            foreach ($results as $row) {
                $empleadoId = $row['employee_id'];
                
                if (!isset($acumuladosAgrupados[$empleadoId])) {
                    $acumuladosAgrupados[$empleadoId] = [
                        'empleado' => [
                            'id' => $row['employee_id'],
                            'document_id' => $row['document_id'],
                            'nombre_completo' => $row['nombre_completo'],
                            'position' => $row['position']
                        ],
                        'conceptos' => []
                    ];
                }
                
                $acumuladosAgrupados[$empleadoId]['conceptos'][] = [
                    'concepto_id' => $row['concepto_id'],
                    'concepto_descripcion' => $row['concepto_descripcion'],
                    'tipo_concepto' => $row['tipo_concepto'],
                    'total_acumulado' => $row['total_acumulado'],
                    'total_planillas' => $row['total_planillas'],
                    'frecuencia' => $row['frecuencia'],
                    'fecha_ultimo_calculo' => $row['fecha_ultimo_calculo']
                ];
            }
            
            return array_values($acumuladosAgrupados);
            
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados de todos los empleados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener planillas disponibles para el selector
     */
    private function getAvailablePayrolls()
    {
        try {
            $sql = "SELECT DISTINCT
                        pc.id,
                        pc.descripcion,
                        pc.fecha_inicio,
                        pc.fecha_fin,
                        pc.mes,
                        pc.ano,
                        pc.estado
                    FROM planilla_cabecera pc
                    INNER JOIN acumulados_por_planilla app ON pc.id = app.planilla_id
                    ORDER BY pc.ano DESC, pc.mes DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo planillas disponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados desde acumulados_por_planilla
     */
    private function getAcumuladosFromPlanillaTable($payrollId)
    {
        try {
            $sql = "SELECT
                        app.id,
                        app.employee_id,
                        app.planilla_id,
                        app.mes,
                        app.ano,
                        app.frecuencia,
                        app.sueldos,
                        app.gastos_representacion,
                        app.otras_asignaciones,
                        app.total_asignaciones,
                        app.seguro_social,
                        app.seguro_educativo,
                        app.impuesto_renta,
                        app.desc_gastos_ss,
                        app.desc_gastos_se,
                        app.desc_gastos_isr,
                        app.otras_deducciones,
                        app.total_deducciones,
                        app.total_neto,
                        app.created_at,
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        p.description as position_name
                    FROM acumulados_por_planilla app
                    INNER JOIN employees e ON app.employee_id = e.id
                    LEFT JOIN positions p ON e.position_id = p.id
                    WHERE app.planilla_id = ?
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados de planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener totales de acumulados_por_planilla
     */
    private function getTotalesFromPlanillaTable($payrollId)
    {
        try {
            $sql = "SELECT
                        COUNT(*) as total_empleados,
                        SUM(total_asignaciones) as total_asignaciones,
                        SUM(total_deducciones) as total_deducciones,
                        SUM(total_neto) as total_neto,
                        SUM(sueldos) as total_sueldos,
                        SUM(gastos_representacion) as total_gastos_representacion,
                        SUM(otras_asignaciones) as total_otras_asignaciones,
                        SUM(seguro_social) as total_seguro_social,
                        SUM(seguro_educativo) as total_seguro_educativo,
                        SUM(impuesto_renta) as total_impuesto_renta,
                        SUM(otras_deducciones) as total_otras_deducciones
                    FROM acumulados_por_planilla
                    WHERE planilla_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            error_log("Error obteniendo totales de planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener tipos de acumulados con totales agregados para todos los empleados
     */
    private function getTiposAcumuladosWithTotals($year, $tipoPlanillaId = null)
    {
        try {
            $whereConditions = [];
            $params = [];

            // Filtro de año - acepta "todos" o año específico
            if ($year && $year !== 'todos') {
                $whereConditions[] = "ape.ano = ?";
                $params[] = $year;
            }

            // Filtro por tipo de planilla del empleado
            if ($tipoPlanillaId && is_numeric($tipoPlanillaId)) {
                $whereConditions[] = "e.tipo_planilla_id = ?";
                $params[] = (int)$tipoPlanillaId;
            }

            // Construir WHERE clause para subquery
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(" AND ", $whereConditions);
            }

            $sql = "SELECT
                        ta.id,
                        ta.codigo,
                        ta.descripcion,
                        COALESCE(SUM(ape.monto), 0) as total_acumulado,
                        COUNT(DISTINCT ape.employee_id) as total_empleados,
                        COUNT(DISTINCT ape.concepto_id) as total_conceptos_incluidos,
                        COUNT(ape.planilla_id) as total_planillas
                    FROM tipos_acumulados ta
                    LEFT JOIN (
                        SELECT ape.*
                        FROM acumulados_por_empleado ape
                        INNER JOIN employees e ON ape.employee_id = e.id
                        {$whereClause}
                    ) ape ON ta.codigo = ape.tipo_acumulado
                    WHERE ta.activo = 1
                    GROUP BY ta.id, ta.codigo, ta.descripcion
                    ORDER BY ta.codigo";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            error_log("getTiposAcumuladosWithTotals - Year: $year, TipoPlanillaId: " . ($tipoPlanillaId ?? 'null') . ", Results: " . count($results));

            return $results;

        } catch (\Exception $e) {
            error_log("Error en getTiposAcumuladosWithTotals: " . $e->getMessage());
            return [];
        }
    }

}