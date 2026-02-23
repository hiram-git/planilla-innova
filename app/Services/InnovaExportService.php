<?php

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * Servicio para generar archivos de exportación formato INNOVA (ERP)
 * Formato: Fixed-width text file con 3 tipos de registros
 */
class InnovaExportService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Generar archivo de exportación completo para una planilla
     *
     * @param int $payrollId ID de la planilla
     * @return array ['success' => bool, 'file_path' => string, 'message' => string]
     */
    public function generateExportFile($payrollId)
    {
        try {
            // 1. Obtener datos de la planilla
            $payrollData = $this->getPayrollData($payrollId);
            if (!$payrollData) {
                return [
                    'success' => false,
                    'message' => 'Planilla no encontrada o no está procesada/cerrada'
                ];
            }

            // 2. Obtener detalles agrupados por empleado y ordenados por tipo
            $details = $this->getPayrollDetails($payrollId);
            if (empty($details)) {
                return [
                    'success' => false,
                    'message' => 'La planilla no tiene detalles para exportar'
                ];
            }

            // 3. Generar contenido del archivo
            $content = $this->buildFileContent($payrollData, $details);

            // 4. Guardar archivo
            $fileName = $this->saveFile($payrollId, $content);

            return [
                'success' => true,
                'file_path' => $fileName,
                'message' => 'Archivo generado exitosamente'
            ];

        } catch (\Exception $e) {
            error_log("Error en InnovaExportService::generateExportFile - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al generar archivo: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener datos principales de la planilla
     */
    private function getPayrollData($payrollId)
    {
        $sql = "SELECT
                    pc.id,
                    pc.descripcion,
                    pc.fecha,
                    pc.fecha_desde,
                    pc.fecha_hasta,
                    pc.estado,
                    tp.nombre as tipo_planilla_nombre,
                    f.nombre as frecuencia_nombre
                FROM planilla_cabecera pc
                LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                LEFT JOIN frecuencias f ON pc.frecuencia_id = f.id
                WHERE pc.id = ?
                  AND pc.estado IN ('PROCESADA', 'CERRADA')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payrollId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalles de la planilla ordenados por empleado y tipo de concepto
     */
    private function getPayrollDetails($payrollId)
    {
        $sql = "SELECT
                    pd.id,
                    pd.employee_id,
                    pd.concepto_id,
                    pd.monto,
                    pd.tipo,
                    e.document_id,
                    e.firstname,
                    e.lastname,
                    e.forma_pago,
                    e.banco,
                    e.numero_cuenta,
                    e.departamento_id,
                    org.descripcion as departamento,
                    c.concepto as concepto_codigo,
                    c.descripcion as concepto_descripcion,
                    c.cuenta_contable,
                    c.tipo_concepto
                FROM planilla_detalle pd
                INNER JOIN employees e ON pd.employee_id = e.id
                INNER JOIN concepto c ON pd.concepto_id = c.id
                LEFT JOIN organigrama org ON e.departamento_id = org.id
                WHERE pd.planilla_cabecera_id = ?
                ORDER BY
                    e.departamento_id,
                    e.id,
                    FIELD(c.tipo_concepto, 'A', 'D', 'P')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payrollId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Construir contenido completo del archivo
     */
    private function buildFileContent($payrollData, $details)
    {
        $lines = [];

        // Agrupar detalles por empleado
        $employeeGroups = $this->groupByEmployee($details);

        // Agrupar empleados por departamento
        $departmentGroups = $this->groupByDepartment($employeeGroups);

        $totalNetGeneral = 0;

        // Procesar por departamento
        foreach ($departmentGroups as $deptName => $employees) {
            $totalNetDepartment = 0;

            // Procesar cada empleado
            foreach ($employees as $employeeId => $employeeDetails) {
                $employee = $employeeDetails[0]; // Primer registro tiene datos del empleado
                $netEmployee = 0;

                // Tipo 1: Movimientos individuales (ordenados A, D, P)
                foreach ($employeeDetails as $detail) {
                    $line = $this->buildMovementLine($detail, $payrollData);
                    $lines[] = $line;

                    // Calcular neto: Asignaciones - Deducciones (Patronales no afectan neto empleado)
                    if ($detail['tipo_concepto'] === 'A') {
                        $netEmployee += $detail['monto'];
                    } elseif ($detail['tipo_concepto'] === 'D') {
                        $netEmployee -= $detail['monto'];
                    }
                }

                // Tipo 2: Neto del trabajador
                $line = $this->buildEmployeeNetLine($employee, $netEmployee, $payrollData);
                $lines[] = $line;

                $totalNetDepartment += $netEmployee;
            }

            // Tipo 3: Total por departamento/área
            $line = $this->buildDepartmentTotalLine($deptName, $totalNetDepartment, $payrollData);
            $lines[] = $line;

            $totalNetGeneral += $totalNetDepartment;
        }

        // Tipo 3: Total general
        $line = $this->buildGeneralTotalLine($totalNetGeneral, $payrollData);
        $lines[] = $line;

        return implode("\r\n", $lines);
    }

    /**
     * Agrupar detalles por empleado
     */
    private function groupByEmployee($details)
    {
        $groups = [];
        foreach ($details as $detail) {
            $employeeId = $detail['employee_id'];
            if (!isset($groups[$employeeId])) {
                $groups[$employeeId] = [];
            }
            $groups[$employeeId][] = $detail;
        }
        return $groups;
    }

    /**
     * Agrupar empleados por departamento
     */
    private function groupByDepartment($employeeGroups)
    {
        $deptGroups = [];
        foreach ($employeeGroups as $employeeId => $details) {
            $deptName = $details[0]['departamento'] ?? 'SIN DEPARTAMENTO';
            if (!isset($deptGroups[$deptName])) {
                $deptGroups[$deptName] = [];
            }
            $deptGroups[$deptName][$employeeId] = $details;
        }
        return $deptGroups;
    }

    /**
     * Tipo 1: Línea de movimiento individual
     */
    private function buildMovementLine($detail, $payrollData)
    {
        $tipo = '1';
        $nomina = $this->formatFixedWidth($payrollData['id'], 6, 'right', '0');
        $fechaDesde = $this->formatDate($payrollData['fecha_desde']);
        $fechaHasta = $this->formatDate($payrollData['fecha_hasta']);
        $fechaPago = $this->formatDate($payrollData['fecha']);
        $idFiscal = $this->formatFixedWidth($detail['document_id'] ?? '', 20);
        $nombreApellido = $this->formatFixedWidth(
            strtoupper($detail['firstname'] . ' ' . $detail['lastname']),
            60
        );
        $concepto = $this->formatFixedWidth($detail['concepto_codigo'] ?? '', 13);
        $tipoMovimiento = $detail['tipo_concepto'] ?? ' ';
        $cuentaContable = $this->formatFixedWidth($detail['cuenta_contable'] ?? '', 20);
        $descripcion = $this->formatFixedWidth($detail['concepto_descripcion'] ?? '', 60);

        // Débito/Crédito según tipo
        if ($detail['tipo_concepto'] === 'A' || $detail['tipo_concepto'] === 'P') {
            $debito = $this->formatMoney($detail['monto']);
            $credito = $this->formatMoney(0);
        } else {
            $debito = $this->formatMoney(0);
            $credito = $this->formatMoney($detail['monto']);
        }

        $tipoPago = $this->formatFixedWidth($detail['forma_pago'] ?? '', 20);
        $bancoPago = $this->formatFixedWidth($detail['banco'] ?? '', 20);
        $ctaPago = $this->formatFixedWidth($detail['numero_cuenta'] ?? '', 18);
        $tipoNomina = $this->formatFixedWidth($payrollData['tipo_planilla_nombre'] ?? '', 20);
        $frecuencia = $this->formatFixedWidth($payrollData['frecuencia_nombre'] ?? '', 20);
        $nivel1 = $this->formatFixedWidth($detail['departamento'] ?? '', 17);

        return $tipo . $nomina . $fechaDesde . $fechaHasta . $fechaPago .
               $idFiscal . $nombreApellido . $concepto . $tipoMovimiento .
               $cuentaContable . $descripcion . $debito . $credito .
               $tipoPago . $bancoPago . $ctaPago . $tipoNomina .
               $frecuencia . $nivel1;
    }

    /**
     * Tipo 2: Línea de neto del trabajador
     */
    private function buildEmployeeNetLine($employee, $netAmount, $payrollData)
    {
        $tipo = '2';
        $nomina = $this->formatFixedWidth($payrollData['id'], 6, 'right', '0');
        $fechaDesde = $this->formatDate($payrollData['fecha_desde']);
        $fechaHasta = $this->formatDate($payrollData['fecha_hasta']);
        $fechaPago = $this->formatDate($payrollData['fecha']);
        $idFiscal = $this->formatFixedWidth($employee['document_id'] ?? '', 20);
        $nombreApellido = $this->formatFixedWidth(
            strtoupper($employee['firstname'] . ' ' . $employee['lastname']),
            60
        );
        $concepto = $this->formatFixedWidth('', 13);
        $tipoMovimiento = 'Z';
        $cuentaContable = $this->formatFixedWidth('', 20);
        $descripcion = $this->formatFixedWidth('NETO', 60);
        $debito = $this->formatMoney(0);
        $credito = $this->formatMoney($netAmount);
        $tipoPago = $this->formatFixedWidth($employee['forma_pago'] ?? '', 20);
        $bancoPago = $this->formatFixedWidth($employee['banco'] ?? '', 20);
        $ctaPago = $this->formatFixedWidth($employee['numero_cuenta'] ?? '', 18);
        $tipoNomina = $this->formatFixedWidth($payrollData['tipo_planilla_nombre'] ?? '', 20);
        $frecuencia = $this->formatFixedWidth($payrollData['frecuencia_nombre'] ?? '', 20);
        $nivel1 = $this->formatFixedWidth($employee['departamento'] ?? '', 17);

        return $tipo . $nomina . $fechaDesde . $fechaHasta . $fechaPago .
               $idFiscal . $nombreApellido . $concepto . $tipoMovimiento .
               $cuentaContable . $descripcion . $debito . $credito .
               $tipoPago . $bancoPago . $ctaPago . $tipoNomina .
               $frecuencia . $nivel1;
    }

    /**
     * Tipo 3: Total por departamento/área
     */
    private function buildDepartmentTotalLine($deptName, $totalNet, $payrollData)
    {
        $tipo = '3';
        $nomina = $this->formatFixedWidth($payrollData['id'], 6, 'right', '0');
        $fechaDesde = $this->formatDate($payrollData['fecha_desde']);
        $fechaHasta = $this->formatDate($payrollData['fecha_hasta']);
        $fechaPago = $this->formatDate($payrollData['fecha']);
        $idFiscal = $this->formatFixedWidth('', 20);
        $nombreApellido = $this->formatFixedWidth('', 60);
        $concepto = $this->formatFixedWidth('', 13);
        $tipoMovimiento = 'Z';
        $cuentaContable = $this->formatFixedWidth('', 20);
        $descripcion = $this->formatFixedWidth('TOTAL ' . strtoupper($deptName), 60);
        $debito = $this->formatMoney(0);
        $credito = $this->formatMoney($totalNet);
        $tipoPago = $this->formatFixedWidth('', 20);
        $bancoPago = $this->formatFixedWidth('', 20);
        $ctaPago = $this->formatFixedWidth('', 18);
        $tipoNomina = $this->formatFixedWidth($payrollData['tipo_planilla_nombre'] ?? '', 20);
        $frecuencia = $this->formatFixedWidth($payrollData['frecuencia_nombre'] ?? '', 20);
        $nivel1 = $this->formatFixedWidth($deptName, 17);

        return $tipo . $nomina . $fechaDesde . $fechaHasta . $fechaPago .
               $idFiscal . $nombreApellido . $concepto . $tipoMovimiento .
               $cuentaContable . $descripcion . $debito . $credito .
               $tipoPago . $bancoPago . $ctaPago . $tipoNomina .
               $frecuencia . $nivel1;
    }

    /**
     * Tipo 3: Total general
     */
    private function buildGeneralTotalLine($totalNet, $payrollData)
    {
        $tipo = '3';
        $nomina = $this->formatFixedWidth($payrollData['id'], 6, 'right', '0');
        $fechaDesde = $this->formatDate($payrollData['fecha_desde']);
        $fechaHasta = $this->formatDate($payrollData['fecha_hasta']);
        $fechaPago = $this->formatDate($payrollData['fecha']);
        $idFiscal = $this->formatFixedWidth('', 20);
        $nombreApellido = $this->formatFixedWidth('', 60);
        $concepto = $this->formatFixedWidth('', 13);
        $tipoMovimiento = 'Z';
        $cuentaContable = $this->formatFixedWidth('', 20);
        $descripcion = $this->formatFixedWidth('TOTAL NETO', 60);
        $debito = $this->formatMoney(0);
        $credito = $this->formatMoney($totalNet);
        $tipoPago = $this->formatFixedWidth('', 20);
        $bancoPago = $this->formatFixedWidth('', 20);
        $ctaPago = $this->formatFixedWidth('', 18);
        $tipoNomina = $this->formatFixedWidth($payrollData['tipo_planilla_nombre'] ?? '', 20);
        $frecuencia = $this->formatFixedWidth($payrollData['frecuencia_nombre'] ?? '', 20);
        $nivel1 = $this->formatFixedWidth('', 17);

        return $tipo . $nomina . $fechaDesde . $fechaHasta . $fechaPago .
               $idFiscal . $nombreApellido . $concepto . $tipoMovimiento .
               $cuentaContable . $descripcion . $debito . $credito .
               $tipoPago . $bancoPago . $ctaPago . $tipoNomina .
               $frecuencia . $nivel1;
    }

    /**
     * Formatear campo con ancho fijo
     */
    private function formatFixedWidth($value, $length, $align = 'left', $padChar = ' ')
    {
        $value = (string)$value;
        $value = mb_substr($value, 0, $length); // Truncar si es más largo

        if ($align === 'right') {
            return str_pad($value, $length, $padChar, STR_PAD_LEFT);
        } else {
            return str_pad($value, $length, $padChar, STR_PAD_RIGHT);
        }
    }

    /**
     * Formatear monto (12 caracteres, alineado a la derecha, 2 decimales)
     */
    private function formatMoney($amount)
    {
        $formatted = number_format((float)$amount, 2, '.', '');
        return $this->formatFixedWidth($formatted, 12, 'right');
    }

    /**
     * Formatear fecha (DDMMYYYY)
     */
    private function formatDate($date)
    {
        if (empty($date)) {
            return str_repeat(' ', 8);
        }

        try {
            $dt = new \DateTime($date);
            return $dt->format('dmY');
        } catch (\Exception $e) {
            return str_repeat(' ', 8);
        }
    }

    /**
     * Guardar archivo generado
     */
    private function saveFile($payrollId, $content)
    {
        // Crear directorio si no existe
        $exportDir = __DIR__ . '/../../exports/innova';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        // Nombre del archivo
        $fileName = 'planilla_' . $payrollId . '_' . date('Ymd_His') . '.txt';
        $filePath = $exportDir . '/' . $fileName;

        // Guardar archivo
        file_put_contents($filePath, $content);

        return $fileName;
    }

    /**
     * Obtener path completo del archivo
     */
    public function getFilePath($fileName)
    {
        return __DIR__ . '/../../exports/innova/' . $fileName;
    }
}
