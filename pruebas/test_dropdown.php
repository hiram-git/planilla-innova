<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Models\Payroll;

try {
    echo "=== PRUEBA DROPDOWN PROCESAMIENTO ===\n";
    
    $db = Database::getInstance();
    $payrollModel = new Payroll();
    
    $payrollId = 5;
    echo "Procesando planilla ID: $payrollId\n";
    
    // Verificar estado inicial
    $payroll = $payrollModel->find($payrollId);
    echo "Estado inicial: {$payroll['estado']}\n";
    echo "Tipo planilla: " . ($payroll['tipo_planilla_id'] ?? 'NULL') . "\n";
    
    // Verificar empleados disponibles
    $sql = "SELECT COUNT(*) as total FROM employees e WHERE e.position_id IS NOT NULL";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $empCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Empleados con posición: {$empCount['total']}\n";
    
    // Verificar conceptos aplicables
    $sql = "SELECT COUNT(*) as total FROM concepto WHERE imprime_detalles = 1 AND (tipos_planilla IS NULL OR tipos_planilla LIKE '%2%')";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute();
    $conceptCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Conceptos aplicables para tipo mensual: {$conceptCount['total']}\n";
    
    // Procesar
    echo "Iniciando procesamiento...\n";
    $result = $payrollModel->processPayroll($payrollId);
    
    if ($result) {
        echo "✓ Procesamiento exitoso!\n";
        
        $payrollAfter = $payrollModel->find($payrollId);
        echo "Estado final: {$payrollAfter['estado']}\n";
        
        $sql = "SELECT COUNT(*) as total FROM planilla_detalle WHERE planilla_cabecera_id = ?";
        $stmt = $db->getConnection()->prepare($sql);
        $stmt->execute([$payrollId]);
        $detailCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Detalles creados: {$detailCount['total']}\n";
        
    } else {
        echo "✗ Error en el procesamiento\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>