<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * Modelo para gestión de conceptos aplicados en planillas
 */
class PayrollConcept extends Model
{
    public $table = 'planilla_conceptos';
    protected $fillable = [
        'detalle_id',
        'concepto_id',
        'monto',
        'observaciones'
    ];

    /**
     * Obtener conceptos de un detalle de planilla específico
     */
    public function getByDetailId($detailId)
    {
        try {
            $sql = "SELECT 
                        pc.*,
                        c.descripcion,
                        c.tipo,
                        c.formula
                    FROM {$this->table} pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = ?
                    ORDER BY c.tipo DESC, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos por detalle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos de ingresos de un detalle
     */
    public function getIncomesByDetailId($detailId)
    {
        try {
            $sql = "SELECT 
                        pc.*,
                        c.descripcion,
                        c.formula
                    FROM {$this->table} pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = ? AND c.tipo = 'INGRESO'
                    ORDER BY c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo ingresos por detalle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos de deducciones de un detalle
     */
    public function getDeductionsByDetailId($detailId)
    {
        try {
            $sql = "SELECT 
                        pc.*,
                        c.descripcion,
                        c.formula
                    FROM {$this->table} pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = ? AND c.tipo = 'DEDUCCION'
                    ORDER BY c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo deducciones por detalle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un concepto ya está aplicado a un detalle
     */
    public function existsConceptInDetail($detailId, $conceptId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE detalle_id = ? AND concepto_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId, $conceptId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error verificando existencia de concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear concepto en planilla con validaciones
     */
    public function create($data)
    {
        try {
            // Validar que no existe duplicado
            if ($this->existsConceptInDetail($data['detalle_id'], $data['concepto_id'])) {
                throw new \Exception('Este concepto ya está aplicado al empleado en esta planilla');
            }

            $result = parent::create($data);

            if ($result) {
                // Recalcular totales del detalle
                $payrollDetailModel = new PayrollDetail();
                $payrollDetailModel->recalculateTotals($data['detalle_id']);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error creando concepto en planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar concepto y recalcular totales
     */
    public function update($id, $data)
    {
        try {
            $concept = $this->find($id);
            if (!$concept) {
                return false;
            }

            $result = parent::update($id, $data);

            if ($result) {
                // Recalcular totales del detalle
                $payrollDetailModel = new PayrollDetail();
                $payrollDetailModel->recalculateTotals($concept['detalle_id']);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error actualizando concepto en planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar concepto y recalcular totales
     */
    public function delete($id)
    {
        try {
            $concept = $this->find($id);
            if (!$concept) {
                return false;
            }

            $result = parent::delete($id);

            if ($result) {
                // Recalcular totales del detalle
                $payrollDetailModel = new PayrollDetail();
                $payrollDetailModel->recalculateTotals($concept['detalle_id']);
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error eliminando concepto de planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aplicar concepto automáticamente a un detalle usando calculadora
     */
    public function applyConceptToDetail($detailId, $conceptId, $employeeId)
    {
        try {
            // Verificar que no existe
            if ($this->existsConceptInDetail($detailId, $conceptId)) {
                return false;
            }

            // Obtener información del concepto
            $conceptModel = new Concept();
            $concept = $conceptModel->find($conceptId);
            
            if (!$concept || !$concept['activo']) {
                throw new \Exception('Concepto no válido o inactivo');
            }

            // Calcular monto usando la calculadora
            $calculator = $this->getCalculator();
            $calculator->setVariablesColaborador($employeeId);
            
            $amount = $calculator->evaluarFormula($concept['formula']);

            // Solo crear si el monto es mayor a 0 o si permite monto cero
            if ($amount > 0 || $concept['monto_cero'] == 1) {
                return $this->create([
                    'detalle_id' => $detailId,
                    'concepto_id' => $conceptId,
                    'monto' => $amount
                ]);
            }

            return true; // No se aplicó pero no es error
        } catch (\Exception $e) {
            error_log("Error aplicando concepto automáticamente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalcular todos los conceptos de un detalle
     */
    public function recalculateAllConcepts($detailId, $employeeId)
    {
        try {
            $this->db->beginTransaction();

            // Obtener conceptos actuales
            $currentConcepts = $this->getByDetailId($detailId);
            
            if (empty($currentConcepts)) {
                $this->db->commit();
                return true;
            }

            $calculator = $this->getCalculator();
            $calculator->setVariablesColaborador($employeeId);

            foreach ($currentConcepts as $concept) {
                try {
                    // Recalcular monto
                    $newAmount = $calculator->evaluarFormula($concept['formula']);
                    
                    // Actualizar si cambió
                    if (abs($newAmount - $concept['monto']) > 0.01) {
                        $this->update($concept['id'], ['monto' => $newAmount]);
                    }
                } catch (\Exception $e) {
                    error_log("Error recalculando concepto {$concept['descripcion']}: " . $e->getMessage());
                    // Continuar con los demás
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error recalculando conceptos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener calculadora de conceptos
     */
    private function getCalculator()
    {
        if (!class_exists('\App\Libraries\PlanillaConceptCalculator')) {
            require_once __DIR__ . '/../../Libraries/PlanillaConceptCalculator.php';
        }
        
        return new \App\Libraries\PlanillaConceptCalculator($this->db);
    }

    /**
     * Obtener estadísticas de aplicación de conceptos
     */
    public function getApplicationStats($payrollId = null)
    {
        try {
            $where = $payrollId ? "AND pd.cabecera_id = ?" : "";
            $params = $payrollId ? [$payrollId] : [];

            $sql = "SELECT 
                        c.descripcion,
                        c.tipo,
                        COUNT(pc.id) as aplicaciones,
                        AVG(pc.monto) as promedio_monto,
                        MIN(pc.monto) as minimo_monto,
                        MAX(pc.monto) as maximo_monto,
                        SUM(pc.monto) as total_monto
                    FROM concepto c
                    LEFT JOIN planilla_conceptos pc ON c.id = pc.concepto_id
                    LEFT JOIN planilla_detalle pd ON pc.detalle_id = pd.id
                    WHERE c.activo = 1 $where
                    GROUP BY c.id, c.descripcion, c.tipo
                    ORDER BY c.tipo DESC, aplicaciones DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de aplicación: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener resumen detallado para exportación
     */
    public function getDetailedSummary($payrollId)
    {
        try {
            $sql = "SELECT 
                        e.employee_id as codigo_empleado,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_empleado,
                        c.descripcion as concepto,
                        c.tipo,
                        pc.monto,
                        pc.observaciones
                    FROM planilla_conceptos pc
                    INNER JOIN planilla_detalle pd ON pc.detalle_id = pd.id
                    INNER JOIN employees e ON pd.employee_id = e.id
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pd.cabecera_id = ?
                    ORDER BY e.lastname, e.firstname, c.tipo DESC, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo resumen detallado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clonar conceptos de una planilla anterior para una nueva
     */
    public function cloneFromPreviousPayroll($newDetailId, $previousDetailId)
    {
        try {
            $this->db->beginTransaction();

            // Obtener conceptos de la planilla anterior
            $previousConcepts = $this->getByDetailId($previousDetailId);

            foreach ($previousConcepts as $concept) {
                // Crear nuevo registro sin recalcular (se hará después)
                $this->db->prepare("INSERT INTO {$this->table} (detalle_id, concepto_id, monto, observaciones) 
                                   VALUES (?, ?, ?, ?)")
                         ->execute([
                             $newDetailId,
                             $concept['concepto_id'],
                             $concept['monto'],
                             $concept['observaciones']
                         ]);
            }

            $this->db->commit();
            return count($previousConcepts);
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error clonando conceptos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener totales por tipo para un detalle
     */
    public function getTotalsByType($detailId)
    {
        try {
            $sql = "SELECT 
                        c.tipo,
                        COUNT(pc.id) as cantidad_conceptos,
                        SUM(pc.monto) as total_monto
                    FROM {$this->table} pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = ?
                    GROUP BY c.tipo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            
            $result = ['INGRESO' => 0, 'DEDUCCION' => 0];
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $result[$row['tipo']] = floatval($row['total_monto']);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Error obteniendo totales por tipo: " . $e->getMessage());
            return ['INGRESO' => 0, 'DEDUCCION' => 0];
        }
    }
}