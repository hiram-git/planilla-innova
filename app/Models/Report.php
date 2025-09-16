<?php

namespace App\Models;

use App\Core\Model;

class Report extends Model
{
    public $table = 'planilla_cabecera'; // Tabla base para reportes (legacy)
    
    /**
     * Obtener planillas disponibles para reportes
     */
    public function getPayrollsForReports()
    {
        try {
            $sql = "SELECT 
                        p.id,
                        p.descripcion,
                        p.fecha as fecha_inicio,
                        p.fecha as fecha_fin,
                        p.estado,
                        p.created_at,
                        tp.descripcion as tipo_descripcion,
                        '' as frecuencia_descripcion,
                        COUNT(DISTINCT pd.employee_id) as total_empleados
                    FROM planilla_cabecera p
                    LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                    LEFT JOIN planilla_detalle pd ON p.id = pd.planilla_cabecera_id
                    WHERE p.estado IN ('PROCESADA', 'CERRADA')
                    GROUP BY p.id, p.descripcion, p.fecha, p.estado, p.created_at, tp.descripcion
                    ORDER BY p.fecha DESC, p.created_at DESC";
            
            return $this->db->findAll($sql);
        } catch (\Exception $e) {
            error_log("Error en Report@getPayrollsForReports: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener empleados de una planilla con sus conceptos calculados
     */
    public function getPayrollEmployees($payrollId)
    {
        try {
            $sql = "SELECT DISTINCT
                        e.id as employee_id,
                        e.employee_id as employee_code,
                        e.firstname,
                        e.lastname,
                        e.cedula,
                        e.salary,
                        pos.descripcion as posicion,
                        c.descripcion as cargo
                    FROM payroll_concepts pc
                    INNER JOIN employees e ON pc.employee_id = e.id
                    LEFT JOIN posiciones pos ON e.position_id = pos.id
                    LEFT JOIN cargos c ON e.cargo_id = c.id
                    WHERE pc.payroll_id = ?
                    ORDER BY e.lastname, e.firstname";
                    
            return $this->db->findAll($sql, [$payrollId]);
        } catch (\Exception $e) {
            error_log("Error en Report@getPayrollEmployees: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener conceptos calculados de un empleado en una planilla específica
     */
    public function getEmployeePayrollConcepts($payrollId, $employeeId)
    {
        try {
            $sql = "SELECT 
                        c.codigo,
                        c.descripcion,
                        c.tipo_concepto,
                        pc.monto as monto_original,
                        pc.monto_calculado,
                        c.afecta_planilla
                    FROM payroll_concepts pc
                    INNER JOIN concepts c ON pc.concept_id = c.id
                    WHERE pc.payroll_id = ? AND pc.employee_id = ?
                    ORDER BY c.tipo_concepto, c.codigo";
                    
            return $this->db->findAll($sql, [$payrollId, $employeeId]);
        } catch (\Exception $e) {
            error_log("Error en Report@getEmployeePayrollConcepts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener resumen de totales de una planilla
     */
    public function getPayrollSummary($payrollId)
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT pc.employee_id) as total_empleados,
                        SUM(CASE WHEN c.tipo_concepto = 'INGRESO' THEN pc.monto_calculado ELSE 0 END) as total_ingresos,
                        SUM(CASE WHEN c.tipo_concepto = 'DEDUCCION' THEN pc.monto_calculado ELSE 0 END) as total_deducciones,
                        (SUM(CASE WHEN c.tipo_concepto = 'INGRESO' THEN pc.monto_calculado ELSE 0 END) - 
                         SUM(CASE WHEN c.tipo_concepto = 'DEDUCCION' THEN pc.monto_calculado ELSE 0 END)) as neto_total
                    FROM payroll_concepts pc
                    INNER JOIN concepts c ON pc.concept_id = c.id
                    WHERE pc.payroll_id = ?";
                    
            return $this->db->find($sql, [$payrollId]);
        } catch (\Exception $e) {
            error_log("Error en Report@getPayrollSummary: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener estadísticas por tipo de concepto
     */
    public function getPayrollStatsByConceptType($payrollId)
    {
        try {
            $sql = "SELECT 
                        c.tipo_concepto,
                        COUNT(*) as cantidad_conceptos,
                        SUM(pc.monto_calculado) as total_monto,
                        AVG(pc.monto_calculado) as promedio_monto,
                        MIN(pc.monto_calculado) as monto_minimo,
                        MAX(pc.monto_calculado) as monto_maximo
                    FROM payroll_concepts pc
                    INNER JOIN concepts c ON pc.concept_id = c.id
                    WHERE pc.payroll_id = ?
                    GROUP BY c.tipo_concepto
                    ORDER BY c.tipo_concepto";
                    
            return $this->db->findAll($sql, [$payrollId]);
        } catch (\Exception $e) {
            error_log("Error en Report@getPayrollStatsByConceptType: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si existe una planilla
     */
    public function payrollExists($payrollId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM planilla_cabecera WHERE id = ?";
            $result = $this->db->find($sql, [$payrollId]);
            return $result['count'] > 0;
        } catch (\Exception $e) {
            error_log("Error en Report@payrollExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener información básica de una planilla
     */
    public function getPayrollInfo($payrollId)
    {
        try {
            $sql = "SELECT 
                        p.*,
                        p.fecha as fecha_inicio,
                        p.fecha as fecha_fin,
                        tp.descripcion as tipo_descripcion,
                        '' as frecuencia_descripcion
                    FROM planilla_cabecera p
                    LEFT JOIN tipos_planilla tp ON p.tipo_planilla_id = tp.id
                    WHERE p.id = ?";
                    
            return $this->db->find($sql, [$payrollId]);
        } catch (\Exception $e) {
            error_log("Error en Report@getPayrollInfo: " . $e->getMessage());
            return null;
        }
    }
}