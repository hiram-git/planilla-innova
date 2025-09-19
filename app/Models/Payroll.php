<?php

namespace App\Models;

use App\Core\Model;
use App\Services\PlanillaConceptCalculator;
use PDO;
use PDOException;

/**
 * Modelo para gestión de planillas de pago
 */
class Payroll extends Model
{
    public $table = 'planilla_cabecera';
    protected $fillable = [
        'descripcion',
        'tipo_planilla_id',
        'frecuencia_id',
        'fecha',
        'fecha_desde',
        'fecha_hasta',
        'usuario_creacion',
        'estado',
        'fecha_reapertura',
        'usuario_reapertura',
        'motivo_reapertura'
    ];

    /**
     * Obtener una planilla específica con información del tipo
     */
    public function findWithType($id)
    {
        try {
            $sql = "SELECT pc.*, tp.nombre as tipo_planilla_nombre, tp.codigo as tipo_planilla_codigo
                    FROM {$this->table} pc 
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id 
                    WHERE pc.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en findWithType: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todas las planillas con información completa incluyendo tipo
     */
    public function getAllWithStats($tipoPlanillaId = null)
    {
        try {
            // Construir consulta con filtro opcional por tipo de planilla
            $whereClause = "";
            $params = [];
            
            if ($tipoPlanillaId) {
                $whereClause = "WHERE pc.tipo_planilla_id = ?";
                $params[] = $tipoPlanillaId;
            }
            
            $sql = "SELECT pc.*, 
                           tp.descripcion as tipo_planilla_nombre, 
                           tp.codigo as tipo_planilla_codigo
                    FROM {$this->table} pc 
                    LEFT JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id 
                    $whereClause
                    ORDER BY pc.fecha DESC";
            
            if (!empty($params)) {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            } else {
                $stmt = $this->db->query($sql);
            }
            $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar estadísticas básicas para cada planilla
            foreach ($payrolls as &$payroll) {
                try {
                    // Contar empleados únicos
                    $statsSql = "SELECT COUNT(DISTINCT employee_id) as total_empleados
                                 FROM planilla_detalle 
                                 WHERE planilla_cabecera_id = ?";
                    
                    $statsStmt = $this->db->prepare($statsSql);
                    $statsStmt->execute([$payroll['id']]);
                    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $payroll['total_empleados'] = $stats['total_empleados'] ?? 0;
                    $payroll['total_neto'] = 0; // Por ahora 0, mejoraremos después
                } catch (PDOException $e) {
                    // Si hay error en estadísticas, usar valores por defecto
                    $payroll['total_empleados'] = 0;
                    $payroll['total_neto'] = 0;
                    error_log("Error calculando estadísticas básicas para planilla {$payroll['id']}: " . $e->getMessage());
                }
            }
            
            return $payrolls;
            
        } catch (PDOException $e) {
            error_log("Error en getAllWithStats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear nueva planilla
     */
    public function create($data)
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO {$this->table} (descripcion, tipo_planilla_id, frecuencia_id, fecha, fecha_desde, fecha_hasta, estado) 
                    VALUES (:descripcion, :tipo_planilla_id, :frecuencia_id, :fecha, :fecha_desde, :fecha_hasta, :estado)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':descripcion' => $data['descripcion'],
                ':tipo_planilla_id' => $data['tipo_planilla_id'] ?? null,
                ':frecuencia_id' => $data['frecuencia_id'] ?? null,
                ':fecha' => $data['fecha'],
                ':fecha_desde' => $data['periodo_inicio'] ?? $data['fecha_desde'] ?? null,
                ':fecha_hasta' => $data['periodo_fin'] ?? $data['fecha_hasta'] ?? null,
                ':estado' => 'PENDIENTE'
            ]);

            if ($result) {
                $payrollId = $this->db->lastInsertId();
                $this->db->commit();
                return $payrollId;
            }

            $this->db->rollback();
            return false;
        } catch (PDOException $e) {
            $this->db->rollback();
            // Error creating payroll
            // Error in payroll creation
            return false;
        }
    }

    /**
     * Procesar planilla - generar detalles para todos los empleados activos
     * Incluye validación de condicionales y datos transaccionales completos
     */
    public function processPayroll($payrollId, $userId = null, $tipoPlanillaId = null)
    {
        try {
            $this->db->beginTransaction();

            // Verificar que la planilla esté en estado PENDIENTE y obtener sus datos
            $payroll = $this->findWithType($payrollId);
            $fecha = $payroll['fecha'] ?? date('Y-m-d');
            $periodo_inicio = $payroll['fecha_desde'] ?? null;
            $periodo_fin = $payroll['fecha_hasta'] ?? null;

            if (!$payroll || $payroll['estado'] !== 'PENDIENTE') {
                throw new \Exception('La planilla no está en estado PENDIENTE');
            }

            // Limpiar detalles existentes
            $sql = "DELETE FROM planilla_detalle WHERE planilla_cabecera_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);

            // Obtener empleados con todas sus relaciones (posición, cargo, función, partida, horario)
            // Filtrar por empleados activos, con situación activa y que correspondan al tipo de planilla
            $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.organigrama_id,
                           e.position_id, e.schedule_id, e.situacion_id, e.tipo_planilla_id,
                           e.cargo_id, e.funcion_id, e.partida_id,
                           p.description as position_nombre, p.rate as position_sueldo,
                           c.descripcion as cargo_nombre,
                           f.descripcion as funcion_nombre,
                           pt.partida as partida_codigo, pt.descripcion as partida_nombre,
                           s.nombre as schedule_nombre,
                           sit.descripcion as situacion_nombre,
                           tp.descripcion as tipo_planilla_nombre
                    FROM employees e 
                    LEFT JOIN position p ON p.id = e.position_id 
                    LEFT JOIN cargos c ON c.id = e.cargo_id 
                    LEFT JOIN funciones f ON f.id = e.funcion_id 
                    LEFT JOIN partidas pt ON pt.id = e.partida_id
                    LEFT JOIN schedules s ON s.id = e.schedule_id
                    LEFT JOIN situaciones sit ON sit.id = e.situacion_id
                    LEFT JOIN tipos_planilla tp ON tp.id = e.tipo_planilla_id
                    WHERE  e.tipo_planilla_id = ?
                      AND e.situacion_id IN (
                          SELECT DISTINCT cs.situacion_id 
                          FROM concepto_situaciones cs
                      )";
            
            $stmt = $this->db->prepare($sql);
            // Usar el tipo de planilla del parámetro si se proporciona, sino el de la planilla original
            $tipoId = $tipoPlanillaId ?: $payroll['tipo_planilla_id'];
            $stmt->execute([$tipoId]);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($employees)) {
                throw new \Exception('No hay empleados activos para procesar');
            }

            // Obtener conceptos con todos sus condicionales
            $sql = "SELECT id, concepto, descripcion, tipo_concepto as tipo, 
                           tipos_planilla, frecuencias, situaciones,
                           formula, valor_fijo, monto_calculo, monto_cero, imprime_detalles 
                    FROM concepto 
                    WHERE imprime_detalles = 1";
            $stmt = $this->db->query($sql);
            $conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Inicializar calculadora
            if (!class_exists('\App\Services\PlanillaConceptCalculator')) {
                require_once __DIR__ . '/../Services/PlanillaConceptCalculator.php';
            }
            $calculadora = new \App\Services\PlanillaConceptCalculator();


            // Establecer fechas de la planilla para variables INIPERIODO/FINPERIODO
            $calculadora->establecerFechasPlanilla($periodo_inicio, $periodo_fin, $fecha);
            $processedCount = 0;
            $employeeCount = 0;

            foreach ($employees as $employee) {
                $employeeCount++;
                $employeeProcessedCount = 0;
                
                // Obtener situación real del empleado desde la query
                $employeeSituacion = $employee['situacion_id'] ?? 1;
                
                
                
                foreach ($conceptos as $concepto) {
                    // Validar condicionales del concepto
                    if (!$this->validateConceptConditions($concepto, $payroll, $employeeSituacion)) {
                        continue; // Saltar este concepto para este empleado
                    }

                    $monto = 0;

                    // Establecer variables del colaborador en la calculadora
                    $calculadora->setVariablesColaborador($employee['id']);


                    // Calcular monto según la configuración del concepto
                    if (!empty($concepto['valor_fijo']) && $concepto['valor_fijo'] > 0) {
                        // Concepto con valor fijo
                        $monto = floatval($concepto['valor_fijo']);
                    } elseif ($concepto['monto_calculo'] == 1 && !empty($concepto['formula'])) {
                        // Concepto con fórmula a evaluar
                        try {
                            $monto = $calculadora->evaluarFormula($concepto['formula']);
                            if ($monto < 0) $monto = 0; // No permitir montos negativos
                        } catch (\Exception $e) {
                            error_log("Error evaluando fórmula para concepto {$concepto['concepto']}: " . $e->getMessage());
                            $monto = 0;
                        }
                    } else {
                    }

                    // Insertar en planilla_detalle si hay monto o si el concepto permite monto cero
                    if ($monto > 0 || ($concepto['monto_cero'] == 1 && $monto == 0)) {
                        $sql = "INSERT INTO planilla_detalle (
                            planilla_cabecera_id, employee_id, concepto_id, monto, tipo, 
                            organigrama_id, position_id, schedule_id, cargo_id, funcion_id, partida_id,
                            firstname, lastname
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        
                        $stmt = $this->db->prepare($sql);
                        $result = $stmt->execute([
                            $payrollId,
                            $employee['id'],
                            $concepto['id'],
                            $monto,
                            $concepto['tipo'],
                            $employee['organigrama_id'],
                            $employee['position_id'],
                            $employee['schedule_id'],
                            $employee['cargo_id'],
                            $employee['funcion_id'],
                            $employee['partida_id'],
                            $employee['firstname'],
                            $employee['lastname']
                        ]);

                        if ($result) {
                            $processedCount++;
                            $employeeProcessedCount++;
                        }
                    }
                }
                
                // Hacer commit parcial después de cada empleado para que el progress endpoint vea los cambios
                if ($employeeProcessedCount > 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                    
                    // Pequeña pausa más corta para no ralentizar el proceso
                    usleep(50000); // 0.05 segundos
                }
            }

            // Actualizar estado de la planilla
            $sql = "UPDATE {$this->table} SET estado = 'PROCESADA' WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);

            $this->db->commit();
            
            // Planilla procesada exitosamente
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error procesando planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcular salario base para un empleado
     */
    private function calculateBaseSalary($employee, $payroll)
    {
        $baseSalary = floatval($employee['sueldo'] ?? 0);
        
        // Aquí podrías agregar lógica para calcular proporcional
        // si el período no es un mes completo
        
        return $baseSalary;
    }

    /**
     * Aplicar conceptos a un empleado específico
     */
    private function applyConceptsToEmployee($detailId, $employee, $payroll)
    {
        try {
            $conceptModel = new Concept();
            $concepts = $conceptModel->getAllActive();
            
            $payrollConceptModel = new PayrollConcept();
            $calculator = $this->getCalculator();
            
            // Configurar variables del empleado para el calculador
            $calculator->setVariablesColaborador($employee['id']);

            $totalIngresos = 0;
            $totalDeducciones = 0;

            foreach ($concepts as $concept) {
                try {
                    // ✅ VALIDAR CONDICIONES PRIMERO (tipo_planilla, frecuencia, situación)
                    $employeeSituacion = $employee['situacion_id'] ?? null;
                    
                    if (!$this->validateConceptConditions($concept, $payroll, $employeeSituacion)) {
                        // Concepto no aplica para esta planilla/empleado - saltar
                        error_log("Concepto {$concept['descripcion']} no aplica para empleado {$employee['id']} en planilla {$payroll['id']}");
                        continue;
                    }
                    
                    // Calcular monto del concepto solo si pasa validaciones
                    $amount = $calculator->evaluarFormula($concept['formula']);
                    
                    if ($amount > 0 || $concept['monto_cero'] == 1) {
                        // Crear registro del concepto aplicado
                        $payrollConceptModel->create([
                            'detalle_id' => $detailId,
                            'concepto_id' => $concept['id'],
                            'monto' => $amount
                        ]);

                        // Sumar al total correspondiente
                        if ($concept['tipo'] === 'INGRESO') {
                            $totalIngresos += $amount;
                        } else {
                            $totalDeducciones += $amount;
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error calculando concepto {$concept['descripcion']}: " . $e->getMessage());
                    // Continuar con el siguiente concepto
                }
            }

            // Actualizar totales en el detalle
            $payrollDetailModel = new PayrollDetail();
            $payrollDetailModel->update($detailId, [
                'total_ingresos' => $totalIngresos,
                'total_deducciones' => $totalDeducciones,
                'salario_neto' => $totalIngresos - $totalDeducciones
            ]);

        } catch (\Exception $e) {
            error_log("Error aplicando conceptos al empleado {$employee['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener calculadora de conceptos
     */
    private function getCalculator()
    {
        // Asegurar que el autoloader está cargado
        if (!class_exists('\App\Libraries\PlanillaConceptCalculator')) {
            require_once __DIR__ . '/../../Libraries/PlanillaConceptCalculator.php';
        }
        
        return new \App\Libraries\PlanillaConceptCalculator($this->db);
    }

    /**
     * Obtener detalle de planilla agrupado por empleados con totales
     */
    public function getPayrollDetails($payrollId)
    {
        try {
            $sql = "SELECT 
                        pd.employee_id,
                        pd.id,
                        CONCAT(pd.firstname, ' ', pd.lastname) as nombre_completo,
                        pd.firstname,
                        pd.lastname,
                        pd.position_id,
                        pd.schedule_id,
                        pd.cargo_id,
                        pd.funcion_id,
                        pd.partida_id,
                        pd.organigrama_id,
                        -- Obtener employee_code del empleado
                        e.employee_id as employee_code,
                        -- Obtener datos de la posición
                        p.description as position_code,
                        p.rate as salario_base,
                        -- Calcular totales
                        SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) as total_ingresos,
                        SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END) as total_deducciones,
                        (SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) - SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END)) as salario_neto,
                        -- Calcular horas trabajadas estimadas (8 horas por defecto)
                        8.0 as horas_trabajadas,
                        -- Nombre de la posición
                        CONCAT(p.description, ' - Posición') as posicion
                    FROM planilla_detalle pd 
                    LEFT JOIN employees e ON e.id = pd.employee_id
                    LEFT JOIN position p ON p.id = pd.position_id
                    WHERE pd.planilla_cabecera_id = :payroll_id 
                    GROUP BY pd.employee_id, pd.firstname, pd.lastname, pd.position_id, 
                             e.employee_id, p.description, p.rate
                    ORDER BY pd.firstname, pd.lastname";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':payroll_id' => $payrollId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo detalles de planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos aplicados a un detalle de planilla
     */
    public function getDetailConcepts($detailId)
    {
        try {
            $sql = "SELECT pc.*, c.descripcion, c.tipo 
                    FROM planilla_conceptos pc
                    INNER JOIN concepto c ON pc.concepto_id = c.id
                    WHERE pc.detalle_id = :detail_id
                    ORDER BY c.tipo, c.descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':detail_id' => $detailId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos del detalle: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cerrar planilla (cambiar estado a CERRADA)
     */
    public function closePayroll($payrollId)
    {
        try {
            $this->db->beginTransaction();
            
            // 1. Procesar acumulados usando el nuevo procesador optimizado
            require_once __DIR__ . '/PayrollAccumulationsProcessor.php';
            $accumulationsProcessor = new PayrollAccumulationsProcessor();
            $accumulationResults = $accumulationsProcessor->processPayrollAccumulations($payrollId);
            error_log("Acumulados procesados: " . json_encode($accumulationResults));
            
            // 2. Cambiar estado a CERRADA
            $result = $this->update($payrollId, [
                'estado' => 'CERRADA'
            ]);
            
            if (!$result) {
                throw new \Exception('Error al actualizar estado de planilla');
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error en closePayroll: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Anular planilla
     */
    public function cancelPayroll($payrollId)
    {
        try {
            $this->db->beginTransaction();

            // Eliminar detalles y conceptos asociados
            $this->db->prepare("DELETE pc FROM planilla_conceptos pc 
                               INNER JOIN planilla_detalle pd ON pc.detalle_id = pd.id 
                               WHERE pd.cabecera_id = ?")->execute([$payrollId]);

            $this->db->prepare("DELETE FROM planilla_detalle WHERE cabecera_id = ?")->execute([$payrollId]);

            // Cambiar estado a ANULADA
            $this->update($payrollId, [
                'estado' => 'ANULADA',
                'total_ingresos' => 0,
                'total_deducciones' => 0,
                'total_neto' => 0
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error anulando planilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de una planilla
     */
    public function getPayrollStats($payrollId)
    {
        try {
            // Primera consulta: estadísticas generales
            $sql = "SELECT 
                        COUNT(DISTINCT pd.employee_id) as total_empleados,
                        SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) as total_ingresos,
                        SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END) as total_deducciones,
                        (SUM(CASE WHEN pd.tipo = 'A' THEN pd.monto ELSE 0 END) - SUM(CASE WHEN pd.tipo = 'D' THEN pd.monto ELSE 0 END)) as total_neto
                    FROM planilla_detalle pd
                    WHERE pd.planilla_cabecera_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats && $stats['total_empleados'] > 0) {
                // Segunda consulta: estadísticas por empleado para calcular promedio y máximo
                $sql = "SELECT 
                            employee_id,
                            SUM(CASE WHEN tipo = 'A' THEN monto ELSE 0 END) - SUM(CASE WHEN tipo = 'D' THEN monto ELSE 0 END) as neto_empleado
                        FROM planilla_detalle 
                        WHERE planilla_cabecera_id = ?
                        GROUP BY employee_id";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$payrollId]);
                $employeeNets = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
                
                if (!empty($employeeNets)) {
                    $stats['promedio_neto'] = array_sum($employeeNets) / count($employeeNets);
                    $stats['maximo_neto'] = max($employeeNets);
                } else {
                    $stats['promedio_neto'] = 0;
                    $stats['maximo_neto'] = 0;
                }
            } else {
                // Valores por defecto si no hay empleados
                $stats = [
                    'total_empleados' => 0,
                    'total_ingresos' => 0,
                    'total_deducciones' => 0,
                    'total_neto' => 0,
                    'promedio_neto' => 0,
                    'maximo_neto' => 0
                ];
            }
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [
                'total_empleados' => 0,
                'total_ingresos' => 0,
                'total_deducciones' => 0,
                'total_neto' => 0,
                'promedio_neto' => 0,
                'maximo_neto' => 0
            ];
        }
    }

    /**
     * Validar si se puede procesar una planilla
     */
    public function canProcess($payrollId)
    {
        $payroll = $this->find($payrollId);
        return $payroll && $payroll['estado'] === 'PENDIENTE';
    }

    /**
     * Validar si se puede editar una planilla
     */
    public function canEdit($payrollId)
    {
        $payroll = $this->find($payrollId);
        return $payroll && in_array($payroll['estado'], ['PENDIENTE', 'PROCESADA']);
    }

    /**
     * Obtener resumen mensual de planillas
     */
    public function getMonthlySummary($year = null, $month = null)
    {
        try {
            $year = $year ?: date('Y');
            $month = $month ?: date('m');
            
            $sql = "SELECT 
                        COUNT(*) as total_planillas,
                        SUM(total_ingresos) as ingresos_totales,
                        SUM(total_deducciones) as deducciones_totales,
                        SUM(total_neto) as neto_total,
                        AVG(total_neto) as promedio_neto
                    FROM planilla_cabecera 
                    WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?
                    AND estado IN ('PROCESADA', 'CERRADA')";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$year, $month]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en resumen mensual: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Convertir nombres textuales de tipos de planilla a IDs numéricos
     */
    private function convertTipoPlanillaToIds($tiposText)
    {
        // Cache estático para evitar consultas repetitivas
        static $cache = null;
        
        if ($cache === null) {
            try {
                // Consultar dinámicamente todos los tipos de planilla
                $stmt = $this->db->query("SELECT id, nombre FROM tipos_planilla WHERE activo = 1");
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $cache = [];
                foreach ($tipos as $tipo) {
                    // Crear mapeo dinámico nombre => id (case insensitive)
                    $nombreLower = strtolower(trim($tipo['nombre']));
                    $cache[$nombreLower] = (string)$tipo['id'];
                }
                
                // Mapeo dinámico tipos planilla completado
            } catch (\Exception $e) {
                error_log("Error obteniendo tipos planilla: " . $e->getMessage());
                return []; // Retornar vacío si hay error
            }
        }
        
        $tipos = array_map('trim', explode(',', $tiposText));
        $ids = [];
        
        foreach ($tipos as $tipo) {
            if (is_numeric($tipo)) {
                $ids[] = $tipo; // Ya es un ID
            } elseif (isset($cache[strtolower($tipo)])) {
                $ids[] = $cache[strtolower($tipo)]; // Convertir nombre a ID dinámicamente
            } else {
                error_log("Tipo de planilla no encontrado: '$tipo'");
            }
        }
        
        return $ids;
    }
    
    /**
     * Convertir nombres textuales de situaciones a IDs numéricos
     */
    private function convertSituacionToIds($situacionesText)
    {
        // Cache estático para evitar consultas repetitivas
        static $cache = null;
        
        if ($cache === null) {
            try {
                // Consultar dinámicamente todas las situaciones
                $stmt = $this->db->query("SELECT id, nombre FROM situaciones WHERE activo = 1");
                $situaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $cache = [];
                foreach ($situaciones as $situacion) {
                    // Crear mapeo dinámico nombre => id (case insensitive)
                    $nombreLower = strtolower(trim($situacion['nombre']));
                    $cache[$nombreLower] = (string)$situacion['id'];
                }
                
                // Mapeo dinámico situaciones completado
            } catch (\Exception $e) {
                error_log("Error obteniendo situaciones: " . $e->getMessage());
                return []; // Retornar vacío si hay error
            }
        }
        
        $situaciones = array_map('trim', explode(',', $situacionesText));
        $ids = [];
        
        foreach ($situaciones as $situacion) {
            if (is_numeric($situacion)) {
                $ids[] = $situacion; // Ya es un ID
            } elseif (isset($cache[strtolower($situacion)])) {
                $ids[] = $cache[strtolower($situacion)]; // Convertir nombre a ID dinámicamente
            } else {
                error_log("Situación no encontrada: '$situacion'");
            }
        }
        
        return $ids;
    }

    /**
     * Validar condicionales de un concepto para aplicarlo a una planilla y empleado
     * @param array $concepto - Datos del concepto con tipos_planilla, frecuencias, situaciones
     * @param array $payroll - Datos de la planilla con tipo_planilla_id
     * @param int $employeeSituacion - ID de situación del empleado (1=activo, etc.)
     * @return bool - true si el concepto aplica, false si no
     */
    public function validateConceptConditions($concepto, $payroll, $employeeSituacion)
    {
        try {
            
            $conceptoId = $concepto['id'];
            $tipoPlanillaId = $payroll['tipo_planilla_id'] ?? null;
            $situacionEmpleadoId = $employeeSituacion;
            
            // Verificar restricciones de TIPO DE PLANILLA usando tabla relacional
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_tipos_planilla WHERE concepto_id = ?");
            $stmt->execute([$conceptoId]);
            $tiposPlanillaCount = $stmt->fetch()['count'];

            if ($tiposPlanillaCount > 0) {
                // Hay restricciones de tipo de planilla, verificar si la planilla actual es válida
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_tipos_planilla WHERE concepto_id = ? AND tipo_planilla_id = ?");
                $stmt->execute([$conceptoId, $tipoPlanillaId]);
                $validTipoPlanilla = $stmt->fetch()['count'] > 0;
                
                if (!$validTipoPlanilla) {
                    return false;
                }
            } else {
                    return false;
            }
            
            // Verificar restricciones de SITUACIÓN using tabla relacional
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_situaciones WHERE concepto_id = ?");
            $stmt->execute([$conceptoId]);
            $situacionesCount = $stmt->fetch()['count'];

            if ($situacionesCount > 0) {
                // Hay restricciones de situación, verificar si el empleado actual es válido
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_situaciones WHERE concepto_id = ? AND situacion_id = ?");
                $stmt->execute([$conceptoId, $situacionEmpleadoId]);
                $validSituacion = $stmt->fetch()['count'] > 0;
                
                if (!$validSituacion) {
                    return false;
                }
            } else {
                    return false;
            }
            
            // Verificar restricciones de FRECUENCIA using tabla relacional
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_frecuencias WHERE concepto_id = ?");
            $stmt->execute([$conceptoId]);
            $frecuenciasCount = $stmt->fetch()['count'];
            
            if ($frecuenciasCount > 0) {
                // Hay restricciones de frecuencia, verificar si la planilla actual es válida
                $frecuenciaPlanillaId = $payroll['frecuencia_id'] ?? null;
                if ($frecuenciaPlanillaId) {
                    $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM concepto_frecuencias WHERE concepto_id = ? AND frecuencia_id = ?");
                    $stmt->execute([$conceptoId, $frecuenciaPlanillaId]);
                    $validFrecuencia = $stmt->fetch()['count'] > 0;
                    
                    if (!$validFrecuencia) {
                        return false;
                    }
                } else {
                    // Si la planilla no tiene frecuencia definida, el concepto no aplica
                    return false;
                }
            } else {
                // No hay restricciones de frecuencia, el concepto aplica para cualquier frecuencia
                    return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error validando concepto {$concepto['id']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reprocesar una planilla existente (limpiar y volver a procesar)
     */
    public function reprocessPayroll($payrollId, $userId = null, $tipoPlanillaId = null)
    {
        try {
            // Verificar que la planilla esté en estado PROCESADA
            $payroll = $this->find($payrollId);
            if (!$payroll || $payroll['estado'] !== 'PROCESADA') {
                throw new \Exception('La planilla debe estar en estado PROCESADA para ser reprocesada');
            }


            // 1. Limpiar datos existentes de planilla_detalle (sin transacción)
            $deleteStmt = $this->db->prepare("DELETE FROM planilla_detalle WHERE planilla_cabecera_id = ?");
            $deleteStmt->execute([$payrollId]);
            
            $deletedCount = $deleteStmt->rowCount();

            // 2. Cambiar estado temporalmente a PENDIENTE (sin transacción)
            $updateStmt = $this->db->prepare("UPDATE planilla_cabecera SET estado = 'PENDIENTE' WHERE id = ?");
            $updateStmt->execute([$payrollId]);

            // 3. Usar la lógica existente de procesamiento (que maneja su propia transacción)
            $processedCount = $this->processPayroll($payrollId, $userId, $tipoPlanillaId);


            return $processedCount;

        } catch (\Exception $e) {
            error_log("Error reprocesando planilla: " . $e->getMessage());
            
            // Intentar restaurar el estado a PROCESADA si algo falló
            try {
                $restoreStmt = $this->db->prepare("UPDATE planilla_cabecera SET estado = 'PROCESADA' WHERE id = ?");
                $restoreStmt->execute([$payrollId]);
            } catch (\Exception $restoreError) {
                error_log("Error restaurando estado de planilla: " . $restoreError->getMessage());
            }
            
            throw $e;
        }
    }
    
    /**
     * Procesar acumulados al cerrar planilla
     */
    private function processPayrollAccumulations($payrollId)
    {
        try {
            error_log("Procesando acumulados para planilla ID: $payrollId");
            
            // 1. Obtener información de la planilla
            $payroll = $this->find($payrollId);
            if (!$payroll) {
                throw new \Exception("Planilla no encontrada: $payrollId");
            }
            
            // 2. Obtener todos los detalles de la planilla con conceptos que tienen acumulados configurados
            $sql = "SELECT 
                        pd.id as detalle_id,
                        pd.employee_id as empleado_id,
                        pd.planilla_cabecera_id as planilla_id,
                        pd.concepto_id,
                        pd.monto as monto_concepto,
                        ca.tipo_acumulado_id,
                        ca.factor_acumulacion,
                        ta.codigo as tipo_codigo,
                        ta.descripcion as tipo_descripcion,
                        c.descripcion as concepto_descripcion
                    FROM planilla_detalle pd
                    INNER JOIN conceptos_acumulados ca ON pd.concepto_id = ca.concepto_id
                    INNER JOIN tipos_acumulados ta ON ca.tipo_acumulado_id = ta.id
                    INNER JOIN concepto c ON pd.concepto_id = c.id
                    WHERE pd.planilla_cabecera_id = ? 
                    AND ca.incluir_en_acumulado = 1
                    AND ta.activo = 1
                    AND pd.monto > 0
                    ORDER BY pd.employee_id, ta.codigo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollId]);
            $acumuladosData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (empty($acumuladosData)) {
                error_log("No hay datos de acumulados para procesar en planilla $payrollId");
                return;
            }
            
            error_log("Encontrados " . count($acumuladosData) . " registros de acumulados para procesar");
            
            // 3. Eliminar consolidados existentes por si se está reprocesando
            $deleteStmt = $this->db->prepare("DELETE FROM planillas_acumulados_consolidados WHERE planilla_id = ?");
            $deleteStmt->execute([$payrollId]);
            
            // Eliminar acumulados por planilla existentes
            $deleteAcumuladosStmt = $this->db->prepare("DELETE FROM acumulados_por_planilla WHERE planilla_id = ?");
            $deleteAcumuladosStmt->execute([$payrollId]);
            
            // 4. Insertar nuevos consolidados
            $insertConsolidadoStmt = $this->db->prepare("
                INSERT INTO planillas_acumulados_consolidados 
                (planilla_id, tipo_acumulado_id, empleado_id, concepto_id, monto_concepto, factor_acumulacion, monto_acumulado, periodo_inicio, periodo_fin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Insertar en acumulados por planilla (registro detallado)
            $insertAcumuladoPorPlanillaStmt = $this->db->prepare("
                INSERT INTO acumulados_por_planilla 
                (planilla_id, empleado_id, concepto_id, tipo_acumulado_id, monto_concepto, factor_acumulacion, monto_acumulado, periodo_inicio, periodo_fin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // 5. Preparar statement para actualizar historicos
            $selectHistoricoStmt = $this->db->prepare("
                SELECT * FROM empleados_acumulados_historicos 
                WHERE empleado_id = ? AND tipo_acumulado_id = ? 
                AND activo = 1 
                ORDER BY periodo_inicio DESC 
                LIMIT 1
            ");
            
            $updateHistoricoStmt = $this->db->prepare("
                UPDATE empleados_acumulados_historicos 
                SET total_acumulado = total_acumulado + ?, 
                    total_conceptos_incluidos = total_conceptos_incluidos + 1,
                    ultima_planilla_id = ?,
                    fecha_ultimo_calculo = NOW()
                WHERE id = ?
            ");
            
            $insertHistoricoStmt = $this->db->prepare("
                INSERT INTO empleados_acumulados_historicos 
                (empleado_id, tipo_acumulado_id, periodo_inicio, periodo_fin, total_acumulado, total_conceptos_incluidos, ultima_planilla_id) 
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ");
            
            // 6. Procesar cada registro
            $consolidadosCreados = 0;
            $historicosActualizados = 0;
            
            foreach ($acumuladosData as $row) {
                try {
                    $montoAcumulado = $row['monto_concepto'] * $row['factor_acumulacion'];
                    
                    // Calcular período (por ahora usar año completo - se puede personalizar después)
                    $periodoInicio = date('Y-01-01', strtotime($payroll['fecha']));
                    $periodoFin = date('Y-12-31', strtotime($payroll['fecha']));
                    
                    // Insertar consolidado
                    $insertConsolidadoStmt->execute([
                        $payrollId,
                        $row['tipo_acumulado_id'],
                        $row['empleado_id'],
                        $row['concepto_id'],
                        $row['monto_concepto'],
                        $row['factor_acumulacion'],
                        $montoAcumulado,
                        $periodoInicio,
                        $periodoFin
                    ]);
                    $consolidadosCreados++;
                    
                    // Insertar en acumulados por planilla para registro detallado
                    $insertAcumuladoPorPlanillaStmt->execute([
                        $payrollId,
                        $row['empleado_id'],
                        $row['concepto_id'],
                        $row['tipo_acumulado_id'],
                        $row['monto_concepto'],
                        $row['factor_acumulacion'],
                        $montoAcumulado,
                        $periodoInicio,
                        $periodoFin
                    ]);
                    
                    // Verificar/actualizar histórico del empleado
                    $selectHistoricoStmt->execute([$row['empleado_id'], $row['tipo_acumulado_id']]);
                    $historico = $selectHistoricoStmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if ($historico) {
                        // Actualizar existente
                        $updateHistoricoStmt->execute([
                            $montoAcumulado,
                            $payrollId,
                            $historico['id']
                        ]);
                        $historicosActualizados++;
                    } else {
                        // Crear nuevo registro histórico
                        $insertHistoricoStmt->execute([
                            $row['empleado_id'],
                            $row['tipo_acumulado_id'],
                            $periodoInicio,
                            $periodoFin,
                            $montoAcumulado,
                            $payrollId
                        ]);
                        $historicosActualizados++;
                    }
                    
                    error_log("Procesado acumulado: Empleado {$row['empleado_id']}, Tipo {$row['tipo_codigo']}, Monto: $montoAcumulado");
                    
                } catch (\Exception $e) {
                    error_log("Error procesando acumulado individual: " . $e->getMessage());
                    // Continuar con los demás registros
                }
            }
            
            error_log("Acumulados procesados exitosamente: $consolidadosCreados consolidados creados, $historicosActualizados históricos actualizados");
            
        } catch (\Exception $e) {
            error_log("Error en processPayrollAccumulations: " . $e->getMessage());
            throw new \Exception("Error procesando acumulados: " . $e->getMessage());
        }
    }
    
    /**
     * Calcular XIII Mes (Décimo Tercer Mes) para empleado específico
     * Legislación Panameña: XIII Mes = 122 días de salario - días no laborados
     * Formula: (Salario Anual ÷ 3) - Días No Laborados
     */
    public function calculateXIIIMes($empleadoId, $year = null)
    {
        try {
            $year = $year ?: date('Y');
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
            
            // Obtener salario anual acumulado del empleado
            $sql = "SELECT 
                        SUM(eah.total_acumulado) as salario_anual_acumulado,
                        COUNT(DISTINCT eah.ultima_planilla_id) as planillas_procesadas
                    FROM empleados_acumulados_historicos eah
                    INNER JOIN tipos_acumulados ta ON eah.tipo_acumulado_id = ta.id
                    WHERE eah.empleado_id = ?
                    AND ta.codigo = 'XIII_MES'
                    AND eah.periodo_inicio >= ? 
                    AND eah.periodo_fin <= ?
                    AND eah.activo = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $startDate, $endDate]);
            $resultado = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$resultado || !$resultado['salario_anual_acumulado']) {
                return [
                    'monto_xiii_mes' => 0,
                    'salario_anual_acumulado' => 0,
                    'dias_base' => 122,
                    'dias_no_laborados' => 0,
                    'dias_a_pagar' => 0,
                    'planillas_procesadas' => 0,
                    'observaciones' => 'No hay salarios acumulados para el XIII Mes en el período'
                ];
            }
            
            // Obtener días no laborados del empleado en el año (campo referencia del concepto DIAS_NO_TRAB)
            $sqlDiasNoLaborados = "SELECT 
                                    COALESCE(SUM(CASE 
                                        WHEN pd.referencia IS NOT NULL AND pd.referencia != '' 
                                        THEN CAST(pd.referencia AS DECIMAL(10,2))
                                        ELSE 0 
                                    END), 0) as total_dias_no_laborados
                                FROM planilla_detalle pd
                                INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                                INNER JOIN concepto c ON pd.concepto_id = c.id
                                WHERE pd.employee_id = ?
                                AND c.concepto = 'DIAS_NO_TRAB'
                                AND pc.fecha BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sqlDiasNoLaborados);
            $stmt->execute([$empleadoId, $startDate, $endDate]);
            $diasNoLaborados = $stmt->fetchColumn() ?: 0;
            
            // Calcular XIII Mes según legislación panameña
            $salario_anual = (float) $resultado['salario_anual_acumulado'];
            $dias_base = 122; // Salario anual dividido en 3 partes
            $dias_no_laborados = (int) $diasNoLaborados;
            $dias_a_pagar = max(0, $dias_base - $dias_no_laborados); // No puede ser negativo
            
            // XIII Mes = (Salario Anual ÷ 3) - ajuste por días no laborados
            $monto_base = $salario_anual / 3; // 122 días de salario
            $valor_dia = $salario_anual / 365; // Valor de un día de salario
            $descuento_dias_no_laborados = $valor_dia * $dias_no_laborados;
            $monto_xiii_mes = max(0, $monto_base - $descuento_dias_no_laborados);
            
            return [
                'monto_xiii_mes' => round($monto_xiii_mes, 2),
                'salario_anual_acumulado' => $salario_anual,
                'monto_base_122_dias' => round($monto_base, 2),
                'dias_base' => $dias_base,
                'dias_no_laborados' => $dias_no_laborados,
                'dias_a_pagar' => $dias_a_pagar,
                'valor_dia' => round($valor_dia, 2),
                'descuento_por_dias' => round($descuento_dias_no_laborados, 2),
                'planillas_procesadas' => (int) $resultado['planillas_procesadas'],
                'periodo' => "$year",
                'observaciones' => "XIII Mes calculado según legislación panameña: 122 días de salario ($dias_base) menos $dias_no_laborados días no laborados = $dias_a_pagar días de salario"
            ];
            
        } catch (\Exception $e) {
            error_log("Error calculando XIII Mes: " . $e->getMessage());
            throw new \Exception("Error en cálculo de XIII Mes: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener resumen de acumulados por empleado y año
     */
    public function getEmployeeAccumulations($empleadoId, $year = null)
    {
        try {
            $year = $year ?: date('Y');
            
            $sql = "SELECT 
                        ta.codigo,
                        ta.descripcion,
                        eah.total_acumulado,
                        eah.total_conceptos_incluidos,
                        eah.fecha_ultimo_calculo,
                        eah.periodo_inicio,
                        eah.periodo_fin
                    FROM empleados_acumulados_historicos eah
                    INNER JOIN tipos_acumulados ta ON eah.tipo_acumulado_id = ta.id
                    WHERE eah.empleado_id = ?
                    AND YEAR(eah.periodo_inicio) = ?
                    AND eah.activo = 1
                    ORDER BY ta.codigo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$empleadoId, $year]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Error obteniendo acumulados del empleado: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener días descontados total considerando días y horas
     * Campo referencia ejemplos:
     * - Sueldo Quincenal: referencia = 15 (días pagados)
     * - XIII Mes: referencia = 122 (días base legal)
     * - Días No Laborados: referencia = 2 (días descontados)
     * - Horas Descontadas: referencia = 16 (horas descontadas)
     */
    public function getTotalDaysDeducted($empleadoId, $year = null)
    {
        try {
            $year = $year ?: date('Y');
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
            
            // Obtener días directamente descontados
            $sqlDiasDirectos = "SELECT 
                                COALESCE(SUM(CASE 
                                    WHEN pd.referencia IS NOT NULL AND pd.referencia != '' 
                                    THEN CAST(pd.referencia AS DECIMAL(10,2))
                                    ELSE 0 
                                END), 0) as total_dias
                            FROM planilla_detalle pd
                            INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                            INNER JOIN concepto c ON pd.concepto_id = c.id
                            WHERE pd.employee_id = ?
                            AND c.concepto = 'DIAS_NO_TRAB'
                            AND c.unidad = 'DIAS'
                            AND pc.fecha BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sqlDiasDirectos);
            $stmt->execute([$empleadoId, $startDate, $endDate]);
            $diasDirectos = $stmt->fetchColumn() ?: 0;
            
            // Obtener horas descontadas y convertirlas a días (8 horas = 1 día)
            $sqlHorasDescontadas = "SELECT 
                                    COALESCE(SUM(CASE 
                                        WHEN pd.referencia IS NOT NULL AND pd.referencia != '' 
                                        THEN CAST(pd.referencia AS DECIMAL(10,2))
                                        ELSE 0 
                                    END), 0) as total_horas
                                FROM planilla_detalle pd
                                INNER JOIN planilla_cabecera pc ON pd.planilla_cabecera_id = pc.id
                                INNER JOIN concepto c ON pd.concepto_id = c.id
                                WHERE pd.employee_id = ?
                                AND (c.concepto LIKE '%HORA%' OR c.unidad = 'HORAS')
                                AND c.tipo_concepto = 'D'
                                AND pc.fecha BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sqlHorasDescontadas);
            $stmt->execute([$empleadoId, $startDate, $endDate]);
            $horasDescontadas = $stmt->fetchColumn() ?: 0;
            
            // Convertir horas a días (asumiendo jornada de 8 horas)
            $diasDeHoras = $horasDescontadas / 8;
            
            $totalDiasDescontados = $diasDirectos + $diasDeHoras;
            
            return [
                'dias_directos' => (float) $diasDirectos,
                'horas_descontadas' => (float) $horasDescontadas,
                'dias_de_horas' => round($diasDeHoras, 4),
                'total_dias_descontados' => round($totalDiasDescontados, 4),
                'observaciones' => "Días directos: $diasDirectos + Días de horas: " . round($diasDeHoras, 4) . " (de $horasDescontadas horas ÷ 8)"
            ];
            
        } catch (\Exception $e) {
            error_log("Error calculando días descontados: " . $e->getMessage());
            return [
                'dias_directos' => 0,
                'horas_descontadas' => 0,
                'dias_de_horas' => 0,
                'total_dias_descontados' => 0,
                'observaciones' => 'Error en cálculo'
            ];
        }
    }
}