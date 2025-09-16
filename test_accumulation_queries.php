<?php
/**
 * Test individual PayrollAccumulationsProcessor queries to isolate the error
 */

// Cargar autoloader
require_once 'vendor/autoload.php';

// Inicializar sistema completo
use App\Core\Bootstrap;
use App\Core\Database;

Bootstrap::init();

header('Content-Type: text/html; charset=UTF-8');
echo "<h1>🔍 Test Individual Accumulation Queries</h1>";

$testPlanillaId = 35;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Step 1: Test getPayrollInfo query</h2>";
    
    $sql1 = "SELECT 
                pc.id, 
                pc.descripcion, 
                pc.fecha, 
                pc.tipo_planilla_id,
                tp.descripcion as tipo_planilla,
                tp.frecuencia,
                MONTH(pc.fecha) as mes,
                YEAR(pc.fecha) as ano
            FROM planilla_cabecera pc
            INNER JOIN tipos_planilla tp ON pc.tipo_planilla_id = tp.id
            WHERE pc.id = ?";
    
    echo "<h3>Query 1: getPayrollInfo</h3>";
    echo "<pre>" . htmlspecialchars($sql1) . "</pre>";
    
    $stmt1 = $db->prepare($sql1);
    $stmt1->execute([$testPlanillaId]);
    $payroll = $stmt1->fetch(PDO::FETCH_ASSOC);
    
    if ($payroll) {
        echo "<p>✅ Success</p>";
        echo "<pre>" . json_encode($payroll, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ No result</p>";
    }
    
    echo "<h2>Step 2: Test planilla details query</h2>";
    
    $sql2 = "SELECT 
                pd.employee_id,
                pd.concepto_id,
                pd.monto,
                c.tipo_concepto as tipo_concepto,
                c.descripcion as concepto_descripcion
            FROM planilla_detalle pd
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.planilla_cabecera_id = ?
            AND pd.monto != 0
            LIMIT 5";
    
    echo "<h3>Query 2: planilla details</h3>";
    echo "<pre>" . htmlspecialchars($sql2) . "</pre>";
    
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute([$testPlanillaId]);
    $details = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if ($details) {
        echo "<p>✅ Success - Found " . count($details) . " records</p>";
        echo "<pre>" . json_encode($details, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ No details found</p>";
    }
    
    echo "<h2>Step 3: Test consolidated accumulations query</h2>";
    
    $sql3 = "SELECT 
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
                
                SUM(CASE WHEN c.tipo_concepto = 'D' THEN pd.monto ELSE 0 END) as total_deducciones,
                
                -- NETO
                (SUM(CASE WHEN c.tipo_concepto = 'A' THEN pd.monto ELSE 0 END) - 
                 SUM(CASE WHEN c.tipo_concepto = 'D' THEN pd.monto ELSE 0 END)) as total_neto
                 
            FROM planilla_detalle pd
            INNER JOIN concepto c ON pd.concepto_id = c.id
            WHERE pd.planilla_cabecera_id = ?
            GROUP BY pd.employee_id
            HAVING total_asignaciones > 0 OR total_deducciones > 0
            LIMIT 3";
    
    echo "<h3>Query 3: consolidated accumulations (simplified)</h3>";
    echo "<pre>" . htmlspecialchars($sql3) . "</pre>";
    
    $stmt3 = $db->prepare($sql3);
    $stmt3->execute([$testPlanillaId]);
    $consolidated = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    if ($consolidated) {
        echo "<p>✅ Success - Found " . count($consolidated) . " employees</p>";
        echo "<pre>" . json_encode($consolidated, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ No consolidated data found</p>";
    }
    
    echo "<h2>Step 4: Check table structures</h2>";
    
    $tables = ['planilla_cabecera', 'planilla_detalle', 'concepto', 'tipos_planilla', 'acumulados_por_empleado', 'acumulados_por_planilla'];
    
    foreach ($tables as $table) {
        echo "<h4>$table structure:</h4>";
        $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Columns: " . implode(', ', array_column($columns, 'Field')) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; margin: 15px 0;'>";
    echo "<h3>❌ QUERY FAILED</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . " (Line: " . $e->getLine() . ")</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
</style>