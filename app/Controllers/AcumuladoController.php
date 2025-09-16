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
        
        // Obtener lista de empleados para filtro
        $employees = $this->employeeModel->getActiveEmployees();
        
        // Obtener tipos de acumulados activos
        $tiposAcumulados = $this->tipoAcumuladoModel->getActivos();
        
        // Si se especifica un empleado, obtener sus acumulados
        $acumulados = [];
        $selectedEmployee = null;
        
        if ($empleadoId) {
            $selectedEmployee = $this->employeeModel->getEmployeeWithFullDetails($empleadoId);
            $acumulados = $this->getAcumuladosByEmployee($empleadoId, $year);
        }
        
        $data = [
            'title' => 'Acumulados de Empleados',
            'employees' => $employees,
            'tiposAcumulados' => $tiposAcumulados,
            'acumulados' => $acumulados,
            'selectedEmployee' => $selectedEmployee,
            'selectedYear' => $year,
            'years' => $this->getAvailableYears()
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
     * Vista de acumulados por tipo (método legacy con ID)
     */
    public function byTypeId($tipoId)
    {
        $tipo = $this->tipoAcumuladoModel->getById($tipoId);
        
        if (!$tipo) {
            header('HTTP/1.1 404 Not Found');
            $this->render('errors/404', ['title' => 'Tipo de acumulado no encontrado']);
            return;
        }
        
        $year = $_GET['year'] ?? date('Y');
        $acumulados = $this->getAcumuladosByType($tipoId, $year);
        
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
                    WHERE ape.employee_id = ? AND ape.ano = ?
                    GROUP BY c.id, c.descripcion, ape.tipo_concepto, ape.frecuencia, ape.ano
                    ORDER BY ape.tipo_concepto, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $year]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener acumulados por concepto específico
     */
    private function getAcumuladosByType($conceptoId, $year)
    {
        try {
            $sql = "SELECT 
                        e.document_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        e.id as employee_id,
                        c.descripcion as concepto_descripcion,
                        SUM(ape.monto) as total_acumulado,
                        COUNT(ape.planilla_id) as total_planillas,
                        MIN(ape.created_at) as fecha_primer_calculo,
                        MAX(ape.created_at) as fecha_ultimo_calculo,
                        pc.descripcion as ultima_planilla,
                        ape.frecuencia
                    FROM acumulados_por_empleado ape
                    INNER JOIN employees e ON ape.employee_id = e.id
                    INNER JOIN concepto c ON ape.concepto_id = c.id
                    LEFT JOIN planilla_cabecera pc ON ape.planilla_id = pc.id
                    WHERE ape.concepto_id = ? AND ape.ano = ?
                    GROUP BY e.id, c.id, ape.frecuencia
                    ORDER BY e.lastname, e.firstname";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptoId, $year]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error obteniendo acumulados por concepto: " . $e->getMessage());
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
            
            return $years;
        } catch (\PDOException $e) {
            error_log("Error obteniendo años disponibles: " . $e->getMessage());
            // Si hay error, retornar al menos los últimos 3 años
            $currentYear = (int)date('Y');
            return [$currentYear, $currentYear - 1, $currentYear - 2];
        }
    }

    /**
     * Vista de acumulados por planilla específica
     */
    public function byPayroll($payrollId = null)
    {
        // Si viene como array de parámetros, tomar el primer elemento
        if (is_array($payrollId)) {
            $payrollId = $payrollId[0] ?? null;
        } elseif (func_num_args() > 0 && func_get_arg(0) && is_array(func_get_args())) {
            $args = func_get_args();
            $payrollId = $args[0] ?? null;
        }
        
        if (!$payrollId) {
            $_SESSION['error'] = 'ID de planilla requerido';
            header('Location: /panel/payrolls');
            exit;
        }

        try {
            // Obtener información de la planilla
            $payroll = $this->payrollModel->find($payrollId);
            if (!$payroll) {
                $_SESSION['error'] = 'Planilla no encontrada';
                header('Location: /panel/payrolls');
                exit;
            }

            // Obtener acumulados específicos de esta planilla
            $acumulados = $this->getAcumuladosByPayroll($payrollId);

            // Agrupar por empleado y tipo de concepto
            $acumuladosAgrupados = [];
            foreach ($acumulados as $acumulado) {
                $empleadoId = $acumulado['empleado_id'];
                $tipoConcepto = $acumulado['tipo_concepto']; // ASIGNACION o DEDUCCION
                
                if (!isset($acumuladosAgrupados[$empleadoId])) {
                    $acumuladosAgrupados[$empleadoId] = [
                        'empleado' => $acumulado['nombre_empleado'],
                        'document_id' => $acumulado['document_id'],
                        'acumulados' => []
                    ];
                }
                
                if (!isset($acumuladosAgrupados[$empleadoId]['acumulados'][$tipoConcepto])) {
                    $acumuladosAgrupados[$empleadoId]['acumulados'][$tipoConcepto] = [
                        'tipo_descripcion' => $tipoConcepto,
                        'tipo_codigo' => $tipoConcepto,
                        'total_acumulado' => 0,
                        'conceptos' => []
                    ];
                }
                
                $acumuladosAgrupados[$empleadoId]['acumulados'][$tipoConcepto]['total_acumulado'] += $acumulado['monto_acumulado'];
                $acumuladosAgrupados[$empleadoId]['acumulados'][$tipoConcepto]['conceptos'][] = [
                    'concepto_descripcion' => $acumulado['concepto_descripcion'],
                    'monto_concepto' => $acumulado['monto_acumulado'],
                    'factor_acumulacion' => 1.0000, // Ya está calculado en monto_acumulado
                    'monto_acumulado' => $acumulado['monto_acumulado']
                ];
            }

            $this->render('admin/acumulados/by_payroll', [
                'payroll' => $payroll,
                'acumulados' => $acumuladosAgrupados,
                'totalEmpleados' => count($acumuladosAgrupados)
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
     * Vista de acumulados por tipo específico
     */
    public function byType()
    {
        $year = $_GET['year'] ?? date('Y');
        $tipoAcumulado = $_GET['tipo'] ?? null;
        
        try {
            // Obtener tipos de acumulados disponibles
            $tiposAcumulados = $this->tipoAcumuladoModel->getActivos();
            
            // Si se especifica un tipo, filtrar por ese tipo
            $acumulados = [];
            $selectedTipo = null;
            
            if ($tipoAcumulado) {
                // Buscar el tipo seleccionado
                foreach ($tiposAcumulados as $tipo) {
                    if ($tipo['codigo'] === $tipoAcumulado) {
                        $selectedTipo = $tipo;
                        break;
                    }
                }
                
                if ($selectedTipo) {
                    $acumulados = $this->getAcumuladosByType($selectedTipo['id'], $year);
                }
            }
            
            $this->render('admin/acumulados/by_type', [
                'year' => $year,
                'tiposAcumulados' => $tiposAcumulados,
                'selectedTipo' => $selectedTipo,
                'acumulados' => $acumulados,
                'availableYears' => $this->getAvailableYears()
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en AcumuladoController@byType: " . $e->getMessage());
            $_SESSION['error'] = 'Error obteniendo acumulados por tipo';
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
        
        try {
            // Obtener lista de empleados activos
            $employees = $this->employeeModel->getActiveEmployees();
            
            // Si se especifica un empleado, obtener sus acumulados
            $acumulados = [];
            $selectedEmployee = null;
            
            if ($empleadoId) {
                $selectedEmployee = $this->employeeModel->getEmployeeWithFullDetails($empleadoId);
                if ($selectedEmployee) {
                    $acumulados = $this->getAcumuladosByEmployee($empleadoId, $year);
                }
            }
            
            $this->render('admin/acumulados/by_employee', [
                'year' => $year,
                'employees' => $employees,
                'selectedEmployee' => $selectedEmployee,
                'acumulados' => $acumulados,
                'availableYears' => $this->getAvailableYears()
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
                    WHERE c.activo = 1
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

}