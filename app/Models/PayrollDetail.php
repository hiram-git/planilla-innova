<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * Modelo para gestión de detalles de planilla por empleado
 */
class PayrollDetail extends Model
{
    public $table = 'planilla_detalle';
    protected $fillable = [
        'planilla_cabecera_id',
        'employee_id', 
        'concepto_id',
        'monto',
        'tipo',
        'organigrama_id',
        'position_id',
        'schedule_id',
        'firstname',
        'lastname',
        'valores_editados_manual',
        'conceptos_editados_manual',
        'cargo_id',
        'funcion_id', 
        'partida_id'
    ];

    /**
     * Obtener detalles de una planilla específica
     */
    public function getByPayrollId($payrollId)
    {
        try {
            $sql = "SELECT 
                        pd.*,
                        e.employee_id as employee_code,
                        CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                        e.firstname,
                        e.lastname,
                        p.codigo as position_name
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE pd.planilla_cabecera_id = ? 
                    ORDER BY e.lastname, e.firstname";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($result) > 0) {
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error obteniendo detalles por planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalle específico con conceptos aplicados
     */
    public function getDetailWithConcepts($detailId)
    {
        try {
            // DEBUG: Verificar qué IDs existen
            $allIds = $this->db->query("SELECT id FROM planilla_detalle WHERE planilla_cabecera_id = 13")->fetchAll();
            
            // Obtener información del detalle
            $detail = $this->find($detailId);
            if (!$detail) {
                return null;
            }

            // Obtener información adicional del empleado
            $sql = "SELECT 
                        pd.*,
                        e.employee_id as employee_code,
                        CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                        e.firstname,
                        e.lastname,
                        p.codigo as position_name,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha as planilla_fecha
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE pd.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            $detailInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($detailInfo) {
                // Obtener conceptos aplicados
                $conceptsSql = "SELECT 
                                    pcon.*,
                                    c.descripcion,
                                    c.tipo,
                                    c.formula
                                FROM planilla_conceptos pcon
                                INNER JOIN concepto c ON pcon.concepto_id = c.id
                                WHERE pcon.detalle_id = ?
                                ORDER BY c.tipo DESC, c.descripcion";
                
                $stmt = $this->db->prepare($conceptsSql);
                $stmt->execute([$detailId]);
                $detailInfo['conceptos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $detailInfo;
        } catch (PDOException $e) {
            error_log("Error obteniendo detalle con conceptos: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Recalcular totales de un detalle basado en sus conceptos
     */
    public function recalculateTotals($detailId)
    {
        try {
            $sql = "SELECT 
                        SUM(CASE WHEN c.tipo = 'INGRESO' THEN pc.monto ELSE 0 END) as total_ingresos,
                        SUM(CASE WHEN c.tipo = 'DEDUCCION' THEN pc.monto ELSE 0 END) as total_deducciones
                    FROM planilla_conceptos pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalIngresos = floatval($totals['total_ingresos'] ?? 0);
            $totalDeducciones = floatval($totals['total_deducciones'] ?? 0);
            $salarioNeto = $totalIngresos - $totalDeducciones;

            // Actualizar el detalle
            return $this->update($detailId, [
                'total_ingresos' => $totalIngresos,
                'total_deducciones' => $totalDeducciones,
                'salario_neto' => $salarioNeto
            ]);

        } catch (PDOException $e) {
            error_log("Error recalculando totales: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener histórico de planillas de un empleado
     */
    public function getEmployeePayrollHistory($employeeId, $limit = 12)
    {
        try {
            $sql = "SELECT 
                        pd.*,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha as planilla_fecha,
                        pc.periodo_inicio,
                        pc.periodo_fin,
                        pc.estado
                    FROM planilla_detalle pd
                    INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                    WHERE pd.employee_id = ?
                    ORDER BY pc.fecha DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo histórico del empleado: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de un empleado en planillas
     */
    public function getEmployeeStats($employeeId)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_planillas,
                        AVG(salario_neto) as promedio_neto,
                        MAX(salario_neto) as maximo_neto,
                        MIN(salario_neto) as minimo_neto,
                        SUM(total_ingresos) as ingresos_acumulados,
                        SUM(total_deducciones) as deducciones_acumuladas,
                        SUM(salario_neto) as neto_acumulado
                    FROM planilla_detalle pd
                    INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                    WHERE pd.employee_id = ? 
                    AND pc.estado IN ('PROCESADA', 'CERRADA')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas del empleado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar que no exista duplicado empleado-planilla
     */
    public function existsEmployeeInPayroll($employeeId, $payrollId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE employee_id = ? AND planilla_cabecera_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $payrollId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log("Error validando duplicado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear detalle de planilla con validaciones
     */
    public function create($data)
    {
        try {
            // Validar que no existe duplicado
            if ($this->existsEmployeeInPayroll($data['employee_id'], $data['planilla_cabecera_id'])) {
                throw new \Exception('El empleado ya está incluido en esta planilla');
            }

            return parent::create($data);
        } catch (\Exception $e) {
            error_log("Error creando detalle de planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener resumen de detalles para exportación
     */
    public function getPayrollSummaryForExport($payrollId)
    {
        try {
            $sql = "SELECT 
                        e.employee_id as codigo,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
                        p.descripcion as posicion,
                        pd.salario_base,
                        pd.horas_trabajadas,
                        pd.total_ingresos,
                        pd.total_deducciones,
                        pd.salario_neto,
                        pc.descripcion as planilla,
                        pc.fecha as fecha_planilla,
                        pc.periodo_inicio,
                        pc.periodo_fin
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE pd.planilla_cabecera_id = ?
                    ORDER BY e.lastname, e.firstname";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo datos para exportación: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar observaciones de un detalle
     */
    public function updateObservations($detailId, $observations)
    {
        return $this->update($detailId, [
            'observaciones' => $observations
        ]);
    }

    /**
     * Actualizar valor específico de concepto manualmente
     */
    public function updateConceptValue($detailId, $conceptId, $newValue, $markAsManual = true)
    {
        try {
            $this->db->beginTransaction();

            // Actualizar el concepto específico
            $payrollConceptModel = new PayrollConcept();
            
            // Buscar el concepto en la planilla_conceptos
            $sql = "SELECT id FROM planilla_conceptos WHERE detalle_id = ? AND concepto_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId, $conceptId]);
            $conceptRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($conceptRow) {
                // Actualizar valor existente
                $payrollConceptModel->update($conceptRow['id'], ['monto' => $newValue]);
            } else {
                // Crear nuevo concepto si no existe
                $payrollConceptModel->create([
                    'detalle_id' => $detailId,
                    'concepto_id' => $conceptId,
                    'monto' => $newValue
                ]);
            }

            // Marcar como editado manualmente si se solicita
            if ($markAsManual) {
                $this->markConceptAsManuallyEdited($detailId, $conceptId);
            }

            // Recalcular totales del detalle
            $this->recalculateTotals($detailId);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error actualizando valor de concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar concepto como editado manualmente
     */
    public function markConceptAsManuallyEdited($detailId, $conceptId)
    {
        try {
            $detail = $this->find($detailId);
            if (!$detail) {
                return false;
            }

            // Obtener lista actual de conceptos editados manualmente
            $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
            
            // Agregar concepto si no está
            if (!in_array($conceptId, $editedConcepts)) {
                $editedConcepts[] = intval($conceptId);
            }

            // Actualizar detalle
            return $this->update($detailId, [
                'conceptos_editados_manual' => json_encode($editedConcepts)
            ]);
        } catch (\Exception $e) {
            error_log("Error marcando concepto como editado manualmente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un concepto fue editado manualmente
     */
    public function isConceptManuallyEdited($detailId, $conceptId)
    {
        try {
            $detail = $this->find($detailId);
            if (!$detail) {
                return false;
            }

            $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
            return in_array(intval($conceptId), $editedConcepts);
        } catch (\Exception $e) {
            error_log("Error verificando edición manual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaurar valor calculado de un concepto (quitar edición manual)
     */
    public function restoreCalculatedValue($detailId, $conceptId, $employeeId)
    {
        try {
            $this->db->beginTransaction();

            // Quitar de la lista de editados manualmente
            $detail = $this->find($detailId);
            if ($detail) {
                $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
                $editedConcepts = array_values(array_filter($editedConcepts, function($id) use ($conceptId) {
                    return intval($id) !== intval($conceptId);
                }));
                
                $this->update($detailId, [
                    'conceptos_editados_manual' => json_encode($editedConcepts)
                ]);
            }

            // Recalcular valor usando la calculadora
            $payrollConceptModel = new PayrollConcept();
            $result = $payrollConceptModel->applyConceptToDetail($detailId, $conceptId, $employeeId);

            if ($result) {
                // Recalcular totales
                $this->recalculateTotals($detailId);
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                return false;
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error restaurando valor calculado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Agregar concepto específico a empleado
     */
    public function addConceptToEmployee($detailId, $conceptId, $initialValue = null)
    {
        try {
            $payrollConceptModel = new PayrollConcept();
            
            // Verificar si ya existe
            if ($payrollConceptModel->existsConceptInDetail($detailId, $conceptId)) {
                throw new \Exception('El concepto ya está aplicado a este empleado');
            }

            $detail = $this->find($detailId);
            if (!$detail) {
                throw new \Exception('Detalle de planilla no encontrado');
            }

            if ($initialValue !== null) {
                // Crear con valor específico y marcar como manual
                $result = $payrollConceptModel->create([
                    'detalle_id' => $detailId,
                    'concepto_id' => $conceptId,
                    'monto' => $initialValue
                ]);
                
                if ($result) {
                    $this->markConceptAsManuallyEdited($detailId, $conceptId);
                }
                
                return $result;
            } else {
                // Aplicar con cálculo automático
                return $payrollConceptModel->applyConceptToDetail($detailId, $conceptId, $detail['employee_id']);
            }
        } catch (\Exception $e) {
            error_log("Error agregando concepto a empleado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remover concepto específico de empleado
     */
    public function removeConceptFromEmployee($detailId, $conceptId)
    {
        try {
            $this->db->beginTransaction();

            $payrollConceptModel = new PayrollConcept();
            
            // Buscar y eliminar el concepto
            $sql = "SELECT id FROM planilla_conceptos WHERE detalle_id = ? AND concepto_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId, $conceptId]);
            $conceptRow = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($conceptRow) {
                $payrollConceptModel->delete($conceptRow['id']);
                
                // Quitar de editados manualmente si estaba
                $detail = $this->find($detailId);
                if ($detail) {
                    $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
                    $editedConcepts = array_values(array_filter($editedConcepts, function($id) use ($conceptId) {
                        return intval($id) !== intval($conceptId);
                    }));
                    
                    $this->update($detailId, [
                        'conceptos_editados_manual' => json_encode($editedConcepts)
                    ]);
                }
                
                // Recalcular totales
                $this->recalculateTotals($detailId);
                
                $this->db->commit();
                return true;
            }
            
            $this->db->rollback();
            return false;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error removiendo concepto de empleado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar rango de valores para un concepto
     */
    public function validateValueRange($conceptId, $value)
    {
        try {
            // Obtener información del concepto
            $conceptModel = new \App\Models\Concept();
            $concept = $conceptModel->find($conceptId);
            
            if (!$concept) {
                return ['valid' => false, 'message' => 'Concepto no encontrado'];
            }

            // Validaciones básicas
            if (!is_numeric($value)) {
                return ['valid' => false, 'message' => 'El valor debe ser numérico'];
            }

            $numValue = floatval($value);

            // Validar valor negativo según tipo
            if ($concept['tipo'] === 'INGRESO' && $numValue < 0) {
                return ['valid' => false, 'message' => 'Los ingresos no pueden ser negativos'];
            }

            if ($concept['tipo'] === 'DEDUCCION' && $numValue < 0) {
                return ['valid' => false, 'message' => 'Las deducciones no pueden ser negativas'];
            }

            // Validar límites si están definidos (se pueden agregar campos en la BD)
            $maxValue = isset($concept['valor_maximo']) ? floatval($concept['valor_maximo']) : null;
            $minValue = isset($concept['valor_minimo']) ? floatval($concept['valor_minimo']) : null;

            if ($maxValue !== null && $numValue > $maxValue) {
                return ['valid' => false, 'message' => "El valor no puede ser mayor a {$maxValue}"];
            }

            if ($minValue !== null && $numValue < $minValue) {
                return ['valid' => false, 'message' => "El valor no puede ser menor a {$minValue}"];
            }

            return ['valid' => true, 'message' => 'Valor válido'];
        } catch (\Exception $e) {
            error_log("Error validando rango de valor: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en validación'];
        }
    }

    /**
     * Obtener resumen de ediciones manuales para un detalle
     */
    public function getManualEditsSummary($detailId)
    {
        try {
            $detail = $this->find($detailId);
            if (!$detail) {
                return null;
            }

            $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
            
            if (empty($editedConcepts)) {
                return [
                    'has_manual_edits' => false,
                    'edited_concepts_count' => 0,
                    'edited_concepts' => []
                ];
            }

            // Obtener información de los conceptos editados
            $conceptModel = new \App\Models\Concept();
            $conceptsInfo = [];
            
            foreach ($editedConcepts as $conceptId) {
                $concept = $conceptModel->find($conceptId);
                if ($concept) {
                    $conceptsInfo[] = [
                        'id' => $concept['id'],
                        'descripcion' => $concept['descripcion'],
                        'tipo' => $concept['tipo']
                    ];
                }
            }

            return [
                'has_manual_edits' => true,
                'edited_concepts_count' => count($editedConcepts),
                'edited_concepts' => $conceptsInfo
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo resumen de ediciones manuales: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Recalcular empleado específico (todos sus conceptos)
     */
    public function recalculateEmployeeAllConcepts($detailId)
    {
        try {
            $detail = $this->find($detailId);
            if (!$detail) {
                return false;
            }

            $payrollConceptModel = new PayrollConcept();
            
            // Solo recalcular conceptos que NO fueron editados manualmente
            $editedConcepts = json_decode($detail['conceptos_editados_manual'] ?? '[]', true);
            
            // Obtener todos los conceptos del empleado
            $currentConcepts = $payrollConceptModel->getByDetailId($detailId);
            
            foreach ($currentConcepts as $concept) {
                // Solo recalcular si no fue editado manualmente
                if (!in_array(intval($concept['concepto_id']), $editedConcepts)) {
                    $this->restoreCalculatedValue($detailId, $concept['concepto_id'], $detail['employee_id']);
                }
            }

            return true;
        } catch (\Exception $e) {
            error_log("Error recalculando empleado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar detalle y sus conceptos asociados
     */
    public function delete($id)
    {
        try {
            $this->db->beginTransaction();

            // Eliminar conceptos asociados
            $sql = "DELETE FROM planilla_conceptos WHERE detalle_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            // Eliminar detalle
            $result = parent::delete($id);

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error eliminando detalle de planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener detalle específico por planilla y empleado
     */
    public function getDetailByPayrollAndEmployee($payrollId, $employeeId)
    {
        try {
            $sql = "SELECT 
                        pd.*,
                        e.employee_id as employee_code,
                        CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                        e.firstname,
                        e.lastname,
                        p.codigo as position_name,
                        pc.descripcion as planilla_descripcion,
                        pc.fecha as planilla_fecha,
                        -- Agregar campos que faltan con valores por defecto
                        COALESCE(pd.monto, 0) as salario_base,
                        8.0 as horas_trabajadas
                    FROM planilla_detalle pd
                    INNER JOIN employees e ON pd.employee_id = e.id
                    INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                    LEFT JOIN posiciones p ON e.position_id = p.id
                    WHERE pd.planilla_cabecera_id = ? AND pd.employee_id = ?
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId, $employeeId]);
            $detailInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($detailInfo) {
                // Obtener conceptos aplicados - parece que se almacenan directamente en planilla_detalle
                $conceptsSql = "SELECT 
                                    pd.id,
                                    pd.concepto_id,
                                    pd.monto,
                                    pd.tipo,
                                    c.descripcion,
                                    c.tipo_concepto as concepto_tipo,
                                    c.formula
                                FROM planilla_detalle pd
                                INNER JOIN concepto c ON pd.concepto_id = c.id
                                WHERE pd.planilla_cabecera_id = ? AND pd.employee_id = ?
                                ORDER BY c.tipo_concepto DESC, c.descripcion";
                
                $stmt = $this->db->prepare($conceptsSql);
                $stmt->execute([$payrollId, $employeeId]);
                $detailInfo['conceptos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
            }

            return $detailInfo;
        } catch (PDOException $e) {
            error_log("Error obteniendo detalle por planilla y empleado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar valor de un concepto específico manualmente
     * 
     * @param int $detailId ID del registro en planilla_detalle
     * @param float $newValue Nuevo valor a asignar
     * @param bool $markAsManual Marcar como editado manualmente
     * @return bool
     */
    public function updateValue($detailId, $newValue, $markAsManual = true)
    {
        try {
            $updateData = ['monto' => $newValue];
            
            if ($markAsManual) {
                // Obtener información actual del registro
                $current = $this->find($detailId);
                if (!$current) {
                    throw new \Exception("Registro de detalle no encontrado");
                }

                // Marcar como editado manualmente
                $editedValues = json_decode($current['valores_editados_manual'] ?: '{}', true);
                $editedValues[$current['concepto_id']] = [
                    'valor_original' => $current['monto'],
                    'valor_editado' => $newValue,
                    'fecha_edicion' => date('Y-m-d H:i:s'),
                    'es_manual' => true
                ];
                
                $updateData['valores_editados_manual'] = json_encode($editedValues);
            }

            return $this->update($detailId, $updateData);
        } catch (\Exception $e) {
            error_log("Error actualizando valor manualmente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar un concepto como editado manualmente
     * 
     * @param int $detailId ID del registro
     * @param array $metadata Metadatos adicionales
     * @return bool
     */
    public function markAsManuallyEdited($detailId, $metadata = [])
    {
        try {
            $current = $this->find($detailId);
            if (!$current) {
                return false;
            }

            $editedValues = json_decode($current['valores_editados_manual'] ?: '{}', true);
            $editedValues[$current['concepto_id']] = array_merge([
                'fecha_edicion' => date('Y-m-d H:i:s'),
                'es_manual' => true,
                'valor_actual' => $current['monto']
            ], $metadata);

            return $this->update($detailId, [
                'valores_editados_manual' => json_encode($editedValues)
            ]);
        } catch (\Exception $e) {
            error_log("Error marcando como manual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaurar valor calculado automáticamente (remover edición manual)
     * 
     * @param int $detailId ID del registro
     * @return bool
     */
    public function recalculateValue($detailId)
    {
        try {
            $current = $this->find($detailId);
            if (!$current) {
                return false;
            }

            // Obtener información del concepto para recalcular
            $sql = "SELECT c.*, pc.planilla_cabecera_id 
                    FROM concepto c, planilla_detalle pd, planilla_cabecera pc
                    WHERE pd.id = ? AND pd.concepto_id = c.id AND pd.planilla_cabecera_id = pc.id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$detailId]);
            $conceptInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conceptInfo) {
                throw new \Exception("No se pudo obtener información del concepto");
            }

            // Recalcular usando la calculadora si tiene fórmula
            $newValue = 0;
            if (!empty($conceptInfo['formula']) && $conceptInfo['monto_calculo'] == 1) {
                if (!class_exists('\App\Services\PlanillaConceptCalculator')) {
                    require_once __DIR__ . '/../Services/PlanillaConceptCalculator.php';
                }
                
                $calculator = new \App\Services\PlanillaConceptCalculator();
                $calculator->setVariablesColaborador($current['employee_id']);
                $newValue = $calculator->evaluarFormula($conceptInfo['formula']);
            } elseif (!empty($conceptInfo['valor_fijo'])) {
                $newValue = floatval($conceptInfo['valor_fijo']);
            }

            // Actualizar valor y remover de editados manuales
            $editedValues = json_decode($current['valores_editados_manual'] ?: '{}', true);
            unset($editedValues[$current['concepto_id']]);

            return $this->update($detailId, [
                'monto' => $newValue,
                'valores_editados_manual' => json_encode($editedValues)
            ]);

        } catch (\Exception $e) {
            error_log("Error recalculando valor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si un valor ha sido editado manualmente
     * 
     * @param int $detailId ID del registro
     * @return bool
     */
    public function isManuallyEdited($detailId)
    {
        try {
            $current = $this->find($detailId);
            if (!$current) {
                return false;
            }

            $editedValues = json_decode($current['valores_editados_manual'] ?: '{}', true);
            return isset($editedValues[$current['concepto_id']]['es_manual']) && 
                   $editedValues[$current['concepto_id']]['es_manual'] === true;
        } catch (\Exception $e) {
            error_log("Error verificando edición manual: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener metadatos de edición manual
     * 
     * @param int $detailId ID del registro
     * @return array|null
     */
    public function getManualEditMetadata($detailId)
    {
        try {
            $current = $this->find($detailId);
            if (!$current) {
                return null;
            }

            $editedValues = json_decode($current['valores_editados_manual'] ?: '{}', true);
            return $editedValues[$current['concepto_id']] ?? null;
        } catch (\Exception $e) {
            error_log("Error obteniendo metadatos: " . $e->getMessage());
            return null;
        }
    }

}