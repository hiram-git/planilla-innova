<?php
/**
 * Script de limpieza para datos del seeder
 * Ejecutar: php cleanup_seeder.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

class CleanupSeeder
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        echo "Script de Limpieza de Seeder\n";
        echo "============================\n";
    }
    
    public function run()
    {
        if (!$this->askConfirmation("⚠️  ADVERTENCIA: Esto eliminará TODOS los empleados y planillas procesadas.\n¿Está seguro de continuar? (y/N): ")) {
            echo "Operación cancelada.\n";
            return;
        }
        
        $startTime = microtime(true);
        
        echo "\n🧹 Iniciando limpieza...\n";
        
        try {
            // Deshabilitar foreign key checks temporalmente
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // 1. Limpiar planilla_detalle
            echo "Eliminando detalles de planillas...\n";
            $this->db->getConnection()->exec("DELETE FROM planilla_detalle");
            
            // 2. Limpiar planilla_cabecera con estado PROCESADA/CERRADA
            echo "Eliminando planillas procesadas...\n";
            $deletedPayrolls = $this->db->getConnection()->exec("
                DELETE FROM planilla_cabecera 
                WHERE estado IN ('PROCESADA', 'CERRADA', 'ANULADA')
            ");
            
            // 3. Restablecer estado de planillas pendientes
            echo "Restableciendo planillas pendientes...\n";
            $this->db->getConnection()->exec("
                UPDATE planilla_cabecera 
                SET fecha_procesamiento = NULL, 
                    observaciones = NULL 
                WHERE estado = 'PENDIENTE'
            ");
            
            // 4. Limpiar empleados creados por el seeder (que empiecen con EMP)
            echo "Eliminando empleados del seeder...\n";
            $deletedEmployees = $this->db->getConnection()->exec("
                DELETE FROM employees 
                WHERE employee_id LIKE 'EMP%'
            ");
            
            // 5. Resetear auto_increment
            $this->db->getConnection()->exec("ALTER TABLE employees AUTO_INCREMENT = 1");
            $this->db->getConnection()->exec("ALTER TABLE planilla_detalle AUTO_INCREMENT = 1");
            
            // Rehabilitar foreign key checks
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            
            echo "\n✅ LIMPIEZA COMPLETADA!\n";
            echo "========================\n";
            echo "• Empleados eliminados: $deletedEmployees\n";
            echo "• Planillas eliminadas: $deletedPayrolls\n";
            echo "• Tiempo de ejecución: {$executionTime}s\n";
            
            $this->showRemainingData();
            
        } catch (Exception $e) {
            // Rehabilitar foreign key checks en caso de error
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            throw $e;
        }
    }
    
    private function showRemainingData()
    {
        try {
            $employees = $this->db->find("SELECT COUNT(*) as total FROM employees")['total'];
            $payrolls = $this->db->find("SELECT COUNT(*) as total FROM planilla_cabecera")['total'];
            $details = $this->db->find("SELECT COUNT(*) as total FROM planilla_detalle")['total'];
            
            echo "\n📊 DATOS RESTANTES\n";
            echo "==================\n";
            echo "• Empleados: $employees\n";
            echo "• Planillas: $payrolls\n";
            echo "• Detalles de planilla: $details\n";
            
        } catch (Exception $e) {
            echo "❌ Error al mostrar estadísticas: " . $e->getMessage() . "\n";
        }
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
        $cleanup = new CleanupSeeder();
        $cleanup->run();
    } catch (Exception $e) {
        echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";
        exit(1);
    }
} else {
    echo "Este script debe ejecutarse desde la línea de comandos: php cleanup_seeder.php\n";
}
?>