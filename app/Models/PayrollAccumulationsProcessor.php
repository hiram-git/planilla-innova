<?php

namespace App\Models;

use App\Core\Database;
use \PDO;

/**
 * Procesador de Acumulados - Nueva Arquitectura Optimizada
 * 
 * Maneja dos tablas:
 * 1. acumulados_por_empleado: Registro detallado por transacción
 * 2. acumulados_por_planilla: Consolidado optimizado para reportes
 */
class PayrollAccumulationsProcessor
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Determinar el tipo de acumulado para un concepto basado en la tabla conceptos_acumulados
     * @param int $conceptoId ID del concepto
     * @param string $tipoPreferido Tipo preferido de acumulado (opcional)
     * @return string|null Código del tipo de acumulado o null si no aplica
     */
    private function getTipoAcumuladoParaConcepto($conceptoId, $tipoPreferido = 'XIII_MES')
    {
        try {
            // Primero intentar encontrar el tipo preferido
            $sql = "SELECT ta.codigo
                    FROM conceptos_acumulados ca
                    INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
                    WHERE ca.concepto_id = ?
                    AND ca.incluir_en_acumulado = 1
                    AND ta.activo = 1
                    AND ta.codigo = ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptoId, $tipoPreferido]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['codigo'];
            }

            // Si no encuentra el tipo preferido, devolver cualquier tipo activo
            $sql = "SELECT ta.codigo
                    FROM conceptos_acumulados ca
                    INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
                    WHERE ca.concepto_id = ?
                    AND ca.incluir_en_acumulado = 1
                    AND ta.activo = 1
                    ORDER BY ta.id
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptoId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result['codigo'] : null;

        } catch (\Exception $e) {
            error_log("Error obteniendo tipo_acumulado para concepto $conceptoId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesar acumulados completos para una planilla
     * @param int $payrollId ID de la planilla a procesar
     * @return array Resultado del procesamiento
     */
    public function processPayrollAccumulations($payrollId)
    {
        try {
            // No iniciar transacción aquí - usar la transacción externa del método closePayroll
            error_log("Iniciando procesamiento de acumulados para planilla ID: $payrollId");
            
            // 1. Obtener información de la planilla
            $payroll = $this->getPayrollInfo($payrollId);
            if (!$payroll) {
                throw new \Exception("Planilla no encontrada: $payrollId");
            }
            
            // 2. Procesar acumulados detallados por empleado
            $detailedResults = $this->processDetailedAccumulations($payrollId, $payroll);
            
            // 3. Procesar acumulados consolidados por planilla
            $consolidatedResults = $this->processConsolidatedAccumulations($payrollId, $payroll);
            
            // 4. Actualizar campos de cierre en planilla_cabecera
            $this->updatePayrollClosureFields($payrollId);
            
            $results = [
                'payroll_id' => $payrollId,
                'detailed_records' => $detailedResults['records_created'],
                'consolidated_records' => $consolidatedResults['employees_processed'],
                'total_employees' => $consolidatedResults['total_employees'],
                'processing_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
            ];
            
            error_log("Acumulados procesados exitosamente: " . json_encode($results));
            
            return $results;
            
        } catch (\Exception $e) {
            error_log("Error en processPayrollAccumulations: " . $e->getMessage());
            throw new \Exception("Error procesando acumulados: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener información básica de la planilla
     */
    private function getPayrollInfo($payrollId)
    {
        $sql = "SELECT 
                    pc.id, 
                    pc.descripcion, 
                    pc.fecha, 
                    pc.tipo_planilla_id,
                    tp.descripcion as tipo_planilla,
                    pc.frecuencia_id as frecuencia,
                    MONTH(pc.fecha) as mes,
                    YEAR(pc.fecha) as ano
                FROM planilla_cabecera pc
                INNER JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
                WHERE pc.id = ?";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$payrollId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Procesar acumulados detallados por empleado (tabla: acumulados_por_empleado)
     * Cada registro de planilla_detalle genera un registro en acumulados_por_empleado
     */
    private function processDetailedAccumulations($payrollId, $payroll)
    {
        try {
            // Primero eliminar registros existentes para esta planilla (por si se reprocesa)
            $deleteStmt = $this->db->prepare("DELETE FROM acumulados_por_empleado WHERE planilla_id = ?");
            $deleteStmt->execute([$payrollId]);
            $deletedRecords = $deleteStmt->rowCount();
            
            // Obtener todos los detalles de la planilla
            $sql = "SELECT 
                        pd.employee_id,
                        pd.concepto_id,
                        pd.monto,
                        c.tipo_concepto as tipo_concepto,
                        c.descripcion as concepto_descripcion
                    FROM planilla_detalle pd
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    WHERE pd.planilla_cabecera_id = ?
                    AND pd.monto != 0";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar statement para inserción
            $insertSql = "INSERT INTO acumulados_por_empleado
                         (employee_id, concepto_id, planilla_id, monto, mes, ano, frecuencia, tipo_concepto, tipo_acumulado)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = $this->db->prepare($insertSql);
            
            $recordsCreated = 0;
            
            foreach ($details as $detail) {
                // Convertir tipo de concepto
                $tipoConcepto = ($detail['tipo_concepto'] === 'A') ? 'ASIGNACION' : 'DEDUCCION';

                // Determinar tipo_acumulado basado en la tabla conceptos_acumulados
                $tipoAcumulado = $this->getTipoAcumuladoParaConcepto($detail['concepto_id']);

                $insertStmt->execute([
                    $detail['employee_id'],
                    $detail['concepto_id'],
                    $payrollId,
                    $detail['monto'],
                    $payroll['mes'],
                    $payroll['ano'],
                    strtoupper($payroll['frecuencia']),
                    $tipoConcepto,
                    $tipoAcumulado
                ]);
                
                $recordsCreated++;
            }
            
            return [
                'records_deleted' => $deletedRecords,
                'records_created' => $recordsCreated,
                'details_processed' => count($details)
            ];
            
        } catch (\Exception $e) {
            error_log("Error en processDetailedAccumulations: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Procesar acumulados consolidados por planilla (tabla: acumulados_por_planilla)
     * Un registro por empleado con todos sus conceptos consolidados en campos específicos
     */
    private function processConsolidatedAccumulations($payrollId, $payroll)
    {
        try {
            // Primero eliminar registros existentes para esta planilla
            $deleteStmt = $this->db->prepare("DELETE FROM acumulados_por_planilla WHERE planilla_id = ?");
            $deleteStmt->execute([$payrollId]);
            
            // Obtener empleados únicos de la planilla con sus totales consolidados
            $sql = "SELECT 
                        pd.employee_id,
                        
                        -- ASIGNACIONES
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'A' AND c.id IN (1, 2, 3) THEN pd.monto 
                            ELSE 0 
                        END) as sueldos,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'A' AND UPPER(c.descripcion) LIKE '%GASTOS%REPRESENTACI%' THEN pd.monto 
                            ELSE 0 
                        END) as gastos_representacion,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'A' AND c.id NOT IN (1, 2, 3) AND UPPER(c.descripcion) NOT LIKE '%GASTOS%REPRESENTACI%' THEN pd.monto 
                            ELSE 0 
                        END) as otras_asignaciones,
                        
                        SUM(CASE WHEN c.tipo_concepto = 'A' THEN pd.monto ELSE 0 END) as total_asignaciones,
                        
                        -- DEDUCCIONES LEGALES
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND (UPPER(c.descripcion) LIKE '%SEGURO SOCIAL%' OR UPPER(c.descripcion) LIKE '%S.S.%') THEN pd.monto 
                            ELSE 0 
                        END) as seguro_social,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND (UPPER(c.descripcion) LIKE '%SEGURO EDUCATIVO%' OR UPPER(c.descripcion) LIKE '%S.E.%') THEN pd.monto 
                            ELSE 0 
                        END) as seguro_educativo,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND (UPPER(c.descripcion) LIKE '%IMPUESTO%RENTA%' OR UPPER(c.descripcion) LIKE '%I.S.R%' OR UPPER(c.descripcion) LIKE '%ISR%') THEN pd.monto 
                            ELSE 0 
                        END) as impuesto_renta,
                        
                        -- DESCUENTOS DE LEY PARA GASTOS DE REPRESENTACIÓN (campos específicos)
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND UPPER(c.descripcion) LIKE '%GASTOS%SS%' THEN pd.monto 
                            ELSE 0 
                        END) as desc_gastos_ss,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND UPPER(c.descripcion) LIKE '%GASTOS%SE%' THEN pd.monto 
                            ELSE 0 
                        END) as desc_gastos_se,
                        
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' AND UPPER(c.descripcion) LIKE '%GASTOS%ISR%' THEN pd.monto 
                            ELSE 0 
                        END) as desc_gastos_isr,
                        
                        -- OTRAS DEDUCCIONES
                        SUM(CASE 
                            WHEN c.tipo_concepto = 'D' 
                            AND UPPER(c.descripcion) NOT LIKE '%SEGURO SOCIAL%' 
                            AND UPPER(c.descripcion) NOT LIKE '%S.S.%'
                            AND UPPER(c.descripcion) NOT LIKE '%SEGURO EDUCATIVO%' 
                            AND UPPER(c.descripcion) NOT LIKE '%S.E.%'
                            AND UPPER(c.descripcion) NOT LIKE '%IMPUESTO%RENTA%' 
                            AND UPPER(c.descripcion) NOT LIKE '%I.S.R%'
                            AND UPPER(c.descripcion) NOT LIKE '%ISR%'
                            AND UPPER(c.descripcion) NOT LIKE '%GASTOS%SS%'
                            AND UPPER(c.descripcion) NOT LIKE '%GASTOS%SE%'
                            AND UPPER(c.descripcion) NOT LIKE '%GASTOS%ISR%'
                            THEN pd.monto 
                            ELSE 0 
                        END) as otras_deducciones,
                        
                        SUM(CASE WHEN c.tipo_concepto = 'D' THEN pd.monto ELSE 0 END) as total_deducciones,
                        
                        -- NETO
                        (SUM(CASE WHEN c.tipo_concepto = 'A' THEN pd.monto ELSE 0 END) - 
                         SUM(CASE WHEN c.tipo_concepto = 'D' THEN pd.monto ELSE 0 END)) as total_neto
                         
                    FROM planilla_detalle pd
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    WHERE pd.planilla_cabecera_id = ?
                    GROUP BY pd.employee_id
                    HAVING total_asignaciones > 0 OR total_deducciones > 0";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $employeeConsolidated = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Preparar statement para inserción consolidada
            $insertSql = "INSERT INTO acumulados_por_planilla 
                         (employee_id, planilla_id, mes, ano, frecuencia,
                          sueldos, gastos_representacion, otras_asignaciones, total_asignaciones,
                          seguro_social, seguro_educativo, impuesto_renta,
                          desc_gastos_ss, desc_gastos_se, desc_gastos_isr,
                          otras_deducciones, total_deducciones, total_neto) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                         
            $insertStmt = $this->db->prepare($insertSql);
            
            $employeesProcessed = 0;
            
            foreach ($employeeConsolidated as $employee) {
                $insertStmt->execute([
                    $employee['employee_id'],
                    $payrollId,
                    $payroll['mes'],
                    $payroll['ano'],
                    strtoupper($payroll['frecuencia']),
                    $employee['sueldos'],
                    $employee['gastos_representacion'],
                    $employee['otras_asignaciones'],
                    $employee['total_asignaciones'],
                    $employee['seguro_social'],
                    $employee['seguro_educativo'],
                    $employee['impuesto_renta'],
                    $employee['desc_gastos_ss'],
                    $employee['desc_gastos_se'],
                    $employee['desc_gastos_isr'],
                    $employee['otras_deducciones'],
                    $employee['total_deducciones'],
                    $employee['total_neto']
                ]);
                
                $employeesProcessed++;
            }
            
            return [
                'employees_processed' => $employeesProcessed,
                'total_employees' => count($employeeConsolidated)
            ];
            
        } catch (\Exception $e) {
            error_log("Error en processConsolidatedAccumulations: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Eliminar acumulados de una planilla (cuando se cambia a PENDIENTE)
     */
    public function deletePayrollAccumulations($payrollId)
    {
        try {
            // No iniciar transacción aquí - usar la transacción externa
            
            // Eliminar de acumulados_por_empleado
            $stmt1 = $this->db->prepare("DELETE FROM acumulados_por_empleado WHERE planilla_id = ?");
            $stmt1->execute([$payrollId]);
            $detailedDeleted = $stmt1->rowCount();
            
            // Eliminar de acumulados_por_planilla
            $stmt2 = $this->db->prepare("DELETE FROM acumulados_por_planilla WHERE planilla_id = ?");
            $stmt2->execute([$payrollId]);
            $consolidatedDeleted = $stmt2->rowCount();
            
            // Resetear campos de cierre en planilla_cabecera
            $stmt3 = $this->db->prepare("
                UPDATE planilla_cabecera 
                SET fecha_cierre = NULL,
                    usuario_cierre = NULL,
                    acumulados_generados = 0
                WHERE id = ?
            ");
            $stmt3->execute([$payrollId]);
            
            error_log("Acumulados eliminados de planilla $payrollId: $detailedDeleted detallados, $consolidatedDeleted consolidados");
            
            return [
                'detailed_deleted' => $detailedDeleted,
                'consolidated_deleted' => $consolidatedDeleted,
                'total_deleted' => $detailedDeleted + $consolidatedDeleted
            ];
            
        } catch (\Exception $e) {
            error_log("Error eliminando acumulados de planilla $payrollId: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Actualizar campos de cierre en planilla_cabecera
     */
    private function updatePayrollClosureFields($payrollId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE planilla_cabecera 
                SET fecha_cierre = NOW(),
                    usuario_cierre = ?,
                    acumulados_generados = 1
                WHERE id = ?
            ");
            
            $usuario = $_SESSION['admin_name'] ?? $_SESSION['user_name'] ?? 'Sistema';
            $stmt->execute([$usuario, $payrollId]);
            
            error_log("Campos de cierre actualizados para planilla $payrollId por usuario: $usuario");
            
        } catch (\Exception $e) {
            error_log("Error actualizando campos de cierre: " . $e->getMessage());
            throw new \Exception("Error actualizando campos de cierre: " . $e->getMessage());
        }
    }
}