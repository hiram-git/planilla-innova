<?php
/**
 * Prueba de Stress - Procesamiento de Planilla con 5000 empleados
 * Ejecutar: php stress_test.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Models/Payroll.php';
require_once __DIR__ . '/app/Services/PlanillaConceptCalculator.php';

use App\Core\Database;
use App\Models\Payroll;
use App\Services\PlanillaConceptCalculator;
use PDO;

class PayrollStressTest
{
    private $db;
    private $payrollModel;
    private $calculator;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->payrollModel = new Payroll();
        $this->calculator = new PlanillaConceptCalculator();
        
        echo "🚀 PRUEBA DE STRESS - SISTEMA DE PLANILLAS\n";
        echo "==========================================\n";
    }
    
    public function run()
    {
        $this->showPreTestInfo();
        
        if (!$this->askConfirmation("\n¿Proceder con la prueba de stress? (y/N): ")) {
            echo "Prueba cancelada.\n";
            return;
        }
        
        echo "\n📋 INICIANDO PRUEBA DE STRESS...\n";
        echo "==================================\n";
        
        $results = [];
        
        // 1. Crear planilla de prueba
        $payrollId = $this->createTestPayroll();
        $results['payroll_id'] = $payrollId;
        
        // 2. Procesar planilla con medición de tiempo
        $results['processing'] = $this->processPayrollWithMetrics($payrollId);
        
        // 3. Verificar integridad de datos
        $results['integrity'] = $this->verifyDataIntegrity($payrollId);
        
        // 4. Pruebas de rendimiento adicionales
        $results['performance'] = $this->runPerformanceTests($payrollId);
        
        // 5. Mostrar resultados completos
        $this->showResults($results);
        
        // 6. Cleanup opcional
        if ($this->askConfirmation("\n¿Eliminar la planilla de prueba? (y/N): ")) {
            $this->cleanupTestPayroll($payrollId);
        }
    }
    
    private function showPreTestInfo()
    {
        try {
            $employeeCount = $this->db->find("SELECT COUNT(*) as total FROM employees")['total'];
            $conceptCount = $this->db->find("SELECT COUNT(*) as total FROM concepto")['total'];
            $positionCount = $this->db->find("SELECT COUNT(*) as total FROM posiciones")['total'];
            
            echo "📊 ESTADO PRE-PRUEBA\n";
            echo "====================\n";
            echo "• Total empleados: " . number_format($employeeCount) . "\n";
            echo "• Conceptos activos: $conceptCount\n";
            echo "• Posiciones disponibles: $positionCount\n";
            
            if ($employeeCount < 5000) {
                echo "\n⚠️  ADVERTENCIA: Se recomiendan 5000+ empleados para la prueba.\n";
                echo "   Ejecute primero: php seeder_employees.php\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error al obtener información pre-prueba: " . $e->getMessage() . "\n";
        }
    }
    
    private function createTestPayroll()
    {
        echo "\n1. Creando planilla de prueba...\n";
        
        try {
            $payrollData = [
                'descripcion' => 'STRESS TEST - ' . date('Y-m-d H:i:s'),
                'fecha' => date('Y-m-d'),
                'periodo_inicio' => date('Y-m-01'),
                'periodo_fin' => date('Y-m-t'),
                'estado' => 'PENDIENTE',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $payrollId = $this->payrollModel->create($payrollData);
            echo "✅ Planilla creada con ID: $payrollId\n";
            
            return $payrollId;
            
        } catch (Exception $e) {
            echo "❌ Error al crear planilla: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function processPayrollWithMetrics($payrollId)
    {
        echo "\n2. Procesando planilla con métricas...\n";
        
        $results = [
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
            'memory_peak' => 0,
            'employees_processed' => 0,
            'concepts_calculated' => 0,
            'errors' => [],
            'phases' => []
        ];
        
        try {
            // Fase 1: Obtener empleados
            $phaseStart = microtime(true);
            $employees = $this->getActiveEmployees();
            $results['phases']['get_employees'] = [
                'time' => round(microtime(true) - $phaseStart, 3),
                'count' => count($employees)
            ];
            echo "   📝 Empleados obtenidos: " . count($employees) . " en " . $results['phases']['get_employees']['time'] . "s\n";
            
            // Fase 2: Obtener conceptos
            $phaseStart = microtime(true);
            $concepts = $this->getActiveConcepts();
            $results['phases']['get_concepts'] = [
                'time' => round(microtime(true) - $phaseStart, 3),
                'count' => count($concepts)
            ];
            echo "   📋 Conceptos obtenidos: " . count($concepts) . " en " . $results['phases']['get_concepts']['time'] . "s\n";
            
            // Debug: Mostrar algunos conceptos
            if (!empty($concepts)) {
                echo "   📝 Primeros conceptos encontrados:\n";
                foreach (array_slice($concepts, 0, 3) as $concept) {
                    echo "      • ID: {$concept['id']}, Concepto: {$concept['concepto']}, Valor Fijo: {$concept['valor_fijo']}, Fórmula: {$concept['formula']}\n";
                }
            }
            
            // Fase 3: Procesamiento masivo
            $phaseStart = microtime(true);
            $batchSize = 100;
            $totalEmployees = count($employees);
            $batches = ceil($totalEmployees / $batchSize);
            
            echo "   🔄 Procesando en $batches lotes de $batchSize empleados...\n";
            
            for ($batch = 0; $batch < $batches; $batch++) {
                $batchEmployees = array_slice($employees, $batch * $batchSize, $batchSize);
                
                foreach ($batchEmployees as $employee) {
                    try {
                        $this->processEmployeeWithConcepts($payrollId, $employee, $concepts);
                        $results['employees_processed']++;
                        $results['concepts_calculated'] += count($concepts);
                        
                        // Actualizar memoria pico
                        $currentMemory = memory_get_usage(true);
                        if ($currentMemory > $results['memory_peak']) {
                            $results['memory_peak'] = $currentMemory;
                        }
                        
                    } catch (Exception $e) {
                        $results['errors'][] = "Empleado {$employee['id']}: " . $e->getMessage();
                    }
                }
                
                // Mostrar progreso cada 10 lotes
                if (($batch + 1) % 10 == 0 || $batch == $batches - 1) {
                    $progress = round((($batch + 1) / $batches) * 100, 1);
                    $processed = min(($batch + 1) * $batchSize, $totalEmployees);
                    echo "   ⏳ Progreso: $progress% ($processed/$totalEmployees empleados)\n";
                }
            }
            
            $results['phases']['process_employees'] = [
                'time' => round(microtime(true) - $phaseStart, 3),
                'count' => $results['employees_processed']
            ];
            
            // Fase 4: Actualizar estado de planilla
            $phaseStart = microtime(true);
            $this->payrollModel->update($payrollId, [
                'estado' => 'PROCESADA',
                'fecha_procesamiento' => date('Y-m-d H:i:s')
            ]);
            $results['phases']['update_status'] = [
                'time' => round(microtime(true) - $phaseStart, 3)
            ];
            
        } catch (Exception $e) {
            $results['critical_error'] = $e->getMessage();
            echo "❌ Error crítico: " . $e->getMessage() . "\n";
        }
        
        $results['end_time'] = microtime(true);
        $results['total_time'] = round($results['end_time'] - $results['start_time'], 3);
        $results['memory_end'] = memory_get_usage(true);
        
        return $results;
    }
    
    private function processEmployeeWithConcepts($payrollId, $employee, $concepts)
    {
        $totalIngresos = 0;
        $totalDeducciones = 0;
        $conceptsProcessed = 0;
        $conceptsInserted = 0;
        
        // Debug solo para el primer empleado
        $debug = ($employee['id'] == $employee['id']); // Para debuggear todos los empleados inicialmente
        
        if ($debug && $employee['id'] <= 10) { // Solo los primeros 10 empleados para no saturar el log
            echo "      🔍 DEBUG - Procesando empleado ID: {$employee['id']}, Conceptos disponibles: " . count($concepts) . "\n";
        }
        
        foreach ($concepts as $concept) {
            $conceptsProcessed++;
            try {
                // Establecer variables del colaborador en la calculadora
                $this->calculator->setVariablesColaborador($employee['id']);
                
                $monto = 0;
                
                if ($debug && $employee['id'] <= 10) {
                    echo "         • Concepto {$concept['concepto']}: valor_fijo={$concept['valor_fijo']}, monto_calculo={$concept['monto_calculo']}, formula={$concept['formula']}\n";
                }
                
                // Calcular monto según la configuración del concepto
                if (!empty($concept['valor_fijo']) && $concept['valor_fijo'] > 0) {
                    // Concepto con valor fijo
                    $monto = floatval($concept['valor_fijo']);
                    if ($debug && $employee['id'] <= 10) {
                        echo "           → Usando valor fijo: $monto\n";
                    }
                } elseif ($concept['monto_calculo'] == 1 && !empty($concept['formula'])) {
                    // Concepto con fórmula a evaluar
                    try {
                        $monto = $this->calculator->evaluarFormula($concept['formula']);
                        if ($monto < 0) $monto = 0; // No permitir montos negativos
                        if ($debug && $employee['id'] <= 10) {
                            echo "           → Evaluando fórmula '{$concept['formula']}': $monto\n";
                        }
                    } catch (\Exception $e) {
                        if ($debug && $employee['id'] <= 10) {
                            echo "           → Error en fórmula: " . $e->getMessage() . "\n";
                        }
                        error_log("Error evaluando fórmula para concepto {$concept['concepto']}: " . $e->getMessage());
                        $monto = 0;
                    }
                } else {
                    if ($debug && $employee['id'] <= 10) {
                        echo "           → No cumple condiciones para cálculo\n";
                    }
                }
                
                if ($monto > 0) {
                    // Crear detalle de planilla
                    $sql = "INSERT INTO planilla_detalle 
                            (planilla_cabecera_id, employee_id, concepto_id, monto, tipo, 
                             position_id, schedule_id, firstname, lastname, organigrama_path,
                             cargo_id, funcion_id, partida_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $this->db->getConnection()->prepare($sql);
                    $result = $stmt->execute([
                        $payrollId,
                        $employee['id'],
                        $concept['id'],
                        $monto,
                        $concept['tipo'], // A, D, P directamente
                        $employee['position_id'],
                        $employee['schedule_id'],
                        $employee['firstname'],
                        $employee['lastname'],
                        $employee['organigrama_path'],
                        $employee['cargo_id'],
                        $employee['funcion_id'],
                        $employee['partida_id']
                    ]);
                    
                    if ($result) {
                        $conceptsInserted++;
                        if ($concept['tipo'] === 'A') {
                            $totalIngresos += $monto;
                        } else {
                            $totalDeducciones += $monto;
                        }
                        
                        if ($debug && $employee['id'] <= 10) {
                            echo "           ✅ Insertado: $monto (tipo: {$concept['tipo']})\n";
                        }
                    } else {
                        if ($debug && $employee['id'] <= 10) {
                            echo "           ❌ Error al insertar en BD\n";
                        }
                    }
                } else {
                    if ($debug && $employee['id'] <= 10) {
                        echo "           → Monto = 0, no se inserta\n";
                    }
                }
                
            } catch (Exception $e) {
                // Log del error pero continuar con otros conceptos
                error_log("Error procesando concepto {$concept['id']} para empleado {$employee['id']}: " . $e->getMessage());
                if ($debug && $employee['id'] <= 10) {
                    echo "           ❌ Error: " . $e->getMessage() . "\n";
                }
            }
        }
        
        if ($debug && $employee['id'] <= 10) {
            echo "      📊 Empleado {$employee['id']}: {$conceptsProcessed} conceptos procesados, {$conceptsInserted} insertados\n";
        }
        
        return [
            'ingresos' => $totalIngresos,
            'deducciones' => $totalDeducciones,
            'neto' => $totalIngresos - $totalDeducciones
        ];
    }
    
    private function verifyDataIntegrity($payrollId)
    {
        echo "\n3. Verificando integridad de datos...\n";
        
        $results = [];
        
        try {
            // Contar registros de detalle
            $detailCount = $this->db->find("
                SELECT COUNT(*) as total 
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$payrollId])['total'];
            
            // Contar empleados únicos
            $uniqueEmployees = $this->db->find("
                SELECT COUNT(DISTINCT employee_id) as total 
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$payrollId])['total'];
            
            // Contar conceptos únicos
            $uniqueConcepts = $this->db->find("
                SELECT COUNT(DISTINCT concepto_id) as total 
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$payrollId])['total'];
            
            // Calcular totales
            $totals = $this->db->find("
                SELECT 
                    SUM(CASE WHEN tipo = 'A' THEN monto ELSE 0 END) as total_ingresos,
                    SUM(CASE WHEN tipo IN ('D', 'P') THEN monto ELSE 0 END) as total_deducciones,
                    SUM(CASE WHEN tipo = 'A' THEN monto ELSE -monto END) as total_neto
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
            ", [$payrollId]);
            
            $results = [
                'detail_records' => $detailCount,
                'unique_employees' => $uniqueEmployees,
                'unique_concepts' => $uniqueConcepts,
                'total_ingresos' => $totals['total_ingresos'] ?? 0,
                'total_deducciones' => $totals['total_deducciones'] ?? 0,
                'total_neto' => $totals['total_neto'] ?? 0
            ];
            
            echo "✅ Integridad verificada:\n";
            echo "   • Registros de detalle: " . number_format($detailCount) . "\n";
            echo "   • Empleados únicos: " . number_format($uniqueEmployees) . "\n";
            echo "   • Conceptos únicos: $uniqueConcepts\n";
            echo "   • Total ingresos: Q" . number_format($results['total_ingresos'], 2) . "\n";
            echo "   • Total deducciones: Q" . number_format($results['total_deducciones'], 2) . "\n";
            echo "   • Total neto: Q" . number_format($results['total_neto'], 2) . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error en verificación: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    private function runPerformanceTests($payrollId)
    {
        echo "\n4. Ejecutando pruebas de rendimiento...\n";
        
        $results = [];
        
        try {
            // Test 1: Consulta de empleados de planilla
            $start = microtime(true);
            $employees = $this->payrollModel->getPayrollDetails($payrollId);
            $results['query_employees'] = [
                'time' => round(microtime(true) - $start, 3),
                'count' => count($employees)
            ];
            
            // Test 2: Cálculo de totales por empleado
            $start = microtime(true);
            $employeeTotals = $this->db->findAll("
                SELECT 
                    employee_id,
                    SUM(CASE WHEN tipo = 'A' THEN monto ELSE 0 END) as ingresos,
                    SUM(CASE WHEN tipo IN ('D', 'P') THEN monto ELSE 0 END) as deducciones
                FROM planilla_detalle 
                WHERE planilla_cabecera_id = ?
                GROUP BY employee_id
                LIMIT 1000
            ", [$payrollId]);
            $results['calc_totals'] = [
                'time' => round(microtime(true) - $start, 3),
                'count' => count($employeeTotals)
            ];
            
            // Test 3: Agregación por conceptos
            $start = microtime(true);
            $conceptTotals = $this->db->findAll("
                SELECT 
                    c.descripcion,
                    COUNT(*) as empleados_afectados,
                    SUM(pd.monto) as total_concepto
                FROM planilla_detalle pd
                JOIN concepto c ON pd.concepto_id = c.id
                WHERE pd.planilla_cabecera_id = ?
                GROUP BY pd.concepto_id, c.descripcion
                ORDER BY total_concepto DESC
            ", [$payrollId]);
            $results['concept_aggregation'] = [
                'time' => round(microtime(true) - $start, 3),
                'count' => count($conceptTotals)
            ];
            
            echo "✅ Pruebas de rendimiento completadas:\n";
            echo "   • Consulta empleados: {$results['query_employees']['time']}s\n";
            echo "   • Cálculo totales: {$results['calc_totals']['time']}s\n";
            echo "   • Agregación conceptos: {$results['concept_aggregation']['time']}s\n";
            
        } catch (Exception $e) {
            echo "❌ Error en pruebas de rendimiento: " . $e->getMessage() . "\n";
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
    
    private function showResults($results)
    {
        echo "\n📈 RESULTADOS DE LA PRUEBA DE STRESS\n";
        echo "=====================================\n";
        
        if (isset($results['processing'])) {
            $proc = $results['processing'];
            
            echo "🕒 TIEMPO DE PROCESAMIENTO:\n";
            echo "   • Tiempo total: {$proc['total_time']}s\n";
            echo "   • Empleados procesados: " . number_format($proc['employees_processed']) . "\n";
            echo "   • Conceptos calculados: " . number_format($proc['concepts_calculated']) . "\n";
            echo "   • Velocidad: " . round($proc['employees_processed'] / $proc['total_time'], 0) . " empleados/segundo\n";
            
            echo "\n💾 USO DE MEMORIA:\n";
            echo "   • Memoria inicial: " . $this->formatBytes($proc['memory_start']) . "\n";
            echo "   • Memoria pico: " . $this->formatBytes($proc['memory_peak']) . "\n";
            echo "   • Memoria final: " . $this->formatBytes($proc['memory_end']) . "\n";
            echo "   • Memoria utilizada: " . $this->formatBytes($proc['memory_peak'] - $proc['memory_start']) . "\n";
            
            if (count($proc['errors']) > 0) {
                echo "\n⚠️  ERRORES ENCONTRADOS (" . count($proc['errors']) . "):\n";
                foreach (array_slice($proc['errors'], 0, 5) as $error) {
                    echo "   • $error\n";
                }
                if (count($proc['errors']) > 5) {
                    echo "   ... y " . (count($proc['errors']) - 5) . " errores más\n";
                }
            }
        }
        
        if (isset($results['integrity'])) {
            $int = $results['integrity'];
            echo "\n📊 INTEGRIDAD DE DATOS:\n";
            echo "   • Registros creados: " . number_format($int['detail_records']) . "\n";
            echo "   • Empleados procesados: " . number_format($int['unique_employees']) . "\n";
            echo "   • Total neto generado: Q" . number_format($int['total_neto'], 2) . "\n";
        }
        
        echo "\n🎯 CONCLUSIÓN:\n";
        if (isset($proc['total_time']) && $proc['total_time'] < 60) {
            echo "   ✅ Rendimiento EXCELENTE: Procesamiento completado en menos de 1 minuto\n";
        } elseif (isset($proc['total_time']) && $proc['total_time'] < 300) {
            echo "   ✅ Rendimiento BUENO: Procesamiento completado en menos de 5 minutos\n";
        } else {
            echo "   ⚠️  Rendimiento MEJORABLE: Considerar optimizaciones\n";
        }
        
        if (isset($proc['memory_peak']) && $proc['memory_peak'] < 512 * 1024 * 1024) {
            echo "   ✅ Uso de memoria EFICIENTE: Menos de 512MB utilizados\n";
        } else {
            echo "   ⚠️  Uso de memoria ALTO: Considerar optimizaciones de memoria\n";
        }
    }
    
    private function cleanupTestPayroll($payrollId)
    {
        echo "\n🧹 Limpiando planilla de prueba...\n";
        
        try {
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            $deletedDetails = $this->db->getConnection()->prepare("DELETE FROM planilla_detalle WHERE planilla_cabecera_id = ?");
            $deletedDetails->execute([$payrollId]);
            $detailCount = $deletedDetails->rowCount();
            
            $this->payrollModel->delete($payrollId);
            
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "✅ Limpieza completada:\n";
            echo "   • Detalles eliminados: " . number_format($detailCount) . "\n";
            echo "   • Planilla eliminada: ID $payrollId\n";
            
        } catch (Exception $e) {
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            echo "❌ Error en limpieza: " . $e->getMessage() . "\n";
        }
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function getActiveEmployees()
    {
        $sql = "SELECT e.id, e.employee_id, e.firstname, e.lastname, e.organigrama_path,
                       e.position_id, e.schedule_id, e.id_cargo as cargo_id, 
                       e.id_funcion as funcion_id, e.id_partida as partida_id,
                       p.codigo as position_codigo, p.sueldo as position_sueldo,
                       c.codigo as cargo_codigo, c.nombre as cargo_nombre,
                       f.codigo as funcion_codigo, f.nombre as funcion_nombre,
                       pt.codigo as partida_codigo, pt.nombre as partida_nombre,
                       s.codigo as schedule_codigo, s.descripcion as schedule_nombre
                FROM employees e 
                LEFT JOIN posiciones p ON p.id = e.position_id 
                LEFT JOIN cargos c ON c.id = e.id_cargo 
                LEFT JOIN funciones f ON f.id = e.id_funcion 
                LEFT JOIN partidas pt ON pt.id = e.id_partida
                LEFT JOIN schedules s ON s.id = e.schedule_id 
                WHERE e.position_id IS NOT NULL";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getActiveConcepts()
    {
        $sql = "SELECT id, concepto, descripcion, tipo_concepto as tipo, 
                       tipos_planilla, frecuencias, situaciones,
                       formula, valor_fijo, monto_calculo, monto_cero, imprime_detalles 
                FROM concepto 
                WHERE imprime_detalles = 1";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function askConfirmation($question)
    {
        echo $question;
        $handle = fopen("php://stdin", "r");
        $response = strtolower(trim(fgets($handle)));
        fclose($handle);
        return in_array($response, ['y', 'yes', 'sí', 'si']);
    }
}

// Ejecutar solo si se llama directamente
if (php_sapi_name() === 'cli') {
    try {
        $test = new PayrollStressTest();
        $test->run();
    } catch (Exception $e) {
        echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";
        exit(1);
    }
} else {
    echo "Este script debe ejecutarse desde la línea de comandos: php stress_test.php\n";
}
?>