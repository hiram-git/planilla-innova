<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Models\Payroll;

try {
    echo "=== DEBUG PROCESAMIENTO ===\n";
    
    $db = Database::getInstance();
    $payrollModel = new Payroll();
    
    $payrollId = 13;
    echo "Procesando planilla ID: $payrollId\n";
    
    // Verificar estado inicial
    $payroll = $payrollModel->findWithType($payrollId);
    echo "Estado: {$payroll['estado']}\n";
    echo "Tipo planilla: {$payroll['tipo_planilla_id']} ({$payroll['tipo_planilla_nombre']})\n";
    
    // Verificar empleados disponibles
    $sql = "SELECT e.id, e.firstname, e.lastname, e.position_id FROM employees e WHERE e.position_id IS NOT NULL";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Empleados disponibles: " . count($employees) . "\n";
    foreach ($employees as $emp) {
        echo "  - {$emp['firstname']} {$emp['lastname']} (ID: {$emp['id']}, Pos: {$emp['position_id']})\n";
    }
    
    // Verificar conceptos
    $sql = "SELECT id, concepto, descripcion, tipos_planilla, frecuencias, situaciones, imprime_detalles FROM concepto WHERE imprime_detalles = 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Conceptos disponibles: " . count($conceptos) . "\n";
    
    foreach ($conceptos as $concepto) {
        echo "  - {$concepto['concepto']}: {$concepto['descripcion']}\n";
        echo "    Tipos planilla: {$concepto['tipos_planilla']}\n";
        echo "    Frecuencias: {$concepto['frecuencias']}\n";
        echo "    Situaciones: {$concepto['situaciones']}\n";
        
        // Probar validación manual
        $result = $payrollModel->validateConceptConditions($concepto, $payroll, 1);
        echo "    ¿Aplica?: " . ($result ? "SÍ" : "NO") . "\n\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>