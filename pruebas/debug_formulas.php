<?php
/**
 * Herramienta de Depuración de Fórmulas
 */

// Configurar la sesión y autenticación
session_start();
/*if (!isset($_SESSION['admin_id'])) {
    header('Location: /panel');
    exit();
}*/

require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;
use PDO;

$db = Database::getInstance()->getConnection();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug de Fórmulas - Planilla Simple</title>
    <link rel="stylesheet" href="/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="/plugins/fontawesome-free/css/all.min.css">
    <style>
        .debug-section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; }
        .formula-test { background: #fff; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        pre { background: #2d3748; color: #e2e8f0; padding: 10px; border-radius: 5px; font-size: 12px; }
        .result-success { color: #28a745; font-weight: bold; }
        .result-error { color: #dc3545; font-weight: bold; }
        .variable-table th, .variable-table td { padding: 8px 12px; }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-bug"></i> Herramienta de Depuración de Fórmulas</h2>
            <p class="text-muted">Esta herramienta te permite probar las fórmulas y verificar las variables de los empleados.</p>
        </div>
    </div>

    <form method="GET" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label>Seleccionar Empleado:</label>
                <select name="employee_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Seleccionar Empleado --</option>
                    <?php
                    $sql = "SELECT id, CONCAT(firstname, ' ', lastname) as name, employee_id FROM employees ORDER BY firstname";
                    $stmt = $db->query($sql);
                    while ($emp = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $selected = (isset($_GET['employee_id']) && $_GET['employee_id'] == $emp['id']) ? 'selected' : '';
                        echo "<option value='{$emp['id']}' $selected>{$emp['name']} (ID: {$emp['employee_id']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-4">
                <label>Fórmula a Probar:</label>
                <input type="text" name="test_formula" class="form-control" 
                       value="<?= htmlspecialchars($_GET['test_formula'] ?? 'SUELDO*0.5') ?>" 
                       placeholder="Ej: SUELDO*0.5">
            </div>
            <div class="col-md-4">
                <label>&nbsp;</label><br>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-play"></i> Probar Fórmula
                </button>
            </div>
        </div>
    </form>

    <?php if (isset($_GET['employee_id']) && !empty($_GET['employee_id'])): ?>
    
    <div class="debug-section">
        <h4>1. Variables del Empleado</h4>
        <?php
        // Cargar variables del empleado
        require_once __DIR__ . '/app/Services/PlanillaConceptCalculator.php';
        $calculadora = new \App\Services\PlanillaConceptCalculator();
        $calculadora->setVariablesColaborador((int)$_GET['employee_id']);
        
        // Usar reflexión para acceder a las variables privadas
        $reflection = new ReflectionClass($calculadora);
        $variablesProperty = $reflection->getProperty('variablesColaborador');
        $variablesProperty->setAccessible(true);
        $variables = $variablesProperty->getValue($calculadora);
        
        // Obtener datos completos del empleado
        $sql = "SELECT e.*, p.codigo as posicion_codigo, p.sueldo
                FROM employees e 
                LEFT JOIN posiciones p ON p.id = e.position_id 
                WHERE e.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_GET['employee_id']]);
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        
        <div class="row">
            <div class="col-md-6">
                <h6>Información del Empleado:</h6>
                <table class="table table-sm variable-table">
                    <tr><th>Nombre:</th><td><?= htmlspecialchars($empleado['firstname'] . ' ' . $empleado['lastname']) ?></td></tr>
                    <tr><th>Employee ID:</th><td><?= htmlspecialchars($empleado['employee_id']) ?></td></tr>
                    <tr><th>Posición:</th><td><?= htmlspecialchars($empleado['posicion_codigo'] ?? 'N/A') ?></td></tr>
                    <tr><th>Sueldo Base:</th><td>Q <?= number_format($empleado['sueldo'] ?? 0, 2) ?></td></tr>
                    <tr><th>Schedule ID:</th><td><?= htmlspecialchars($empleado['schedule_id'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Variables Disponibles en Fórmulas:</h6>
                <table class="table table-sm variable-table">
                    <?php foreach ($variables as $var => $valor): ?>
                    <tr>
                        <th><?= $var ?>:</th>
                        <td><code><?= $valor ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['test_formula']) && !empty($_GET['test_formula'])): ?>
    <div class="debug-section">
        <h4>2. Prueba de Fórmula Manual</h4>
        <?php
        $formula = $_GET['test_formula'];
        echo "<p><strong>Fórmula Original:</strong> <code>{$formula}</code></p>";
        
        // Simulación del proceso interno
        echo "<h6>Paso a Paso:</h6>";
        
        // Paso 1: Reemplazo de variables
        $formulaConVariables = $formula;
        foreach ($variables as $var => $valor) {
            $formulaConVariables = str_replace($var, (string)$valor, $formulaConVariables);
        }
        echo "<p><strong>Después de reemplazar variables:</strong> <code>{$formulaConVariables}</code></p>";
        
        // Paso 2: Procesar funciones (simulado)
        echo "<p><strong>Después de procesar funciones:</strong> <code>{$formulaConVariables}</code></p>";
        
        // Paso 3: Sanitizar para eval
        $formulaSanitizada = preg_replace('/[^0-9+\-*\/\(\)\.]/', '', $formulaConVariables);
        echo "<p><strong>Después de sanitizar:</strong> <code>{$formulaSanitizada}</code></p>";
        
        // Paso 4: Evaluar
        try {
            if (!empty($formulaSanitizada)) {
                $resultado = eval("return $formulaSanitizada;");
                echo "<p class='result-success'><strong>Resultado Final:</strong> Q " . number_format($resultado, 2) . "</p>";
            } else {
                echo "<p class='result-error'><strong>Error:</strong> Fórmula vacía después de sanitizar</p>";
            }
        } catch (Exception $e) {
            echo "<p class='result-error'><strong>Error en evaluación:</strong> " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    <?php endif; ?>

    <div class="debug-section">
        <h4>3. Conceptos Configurados</h4>
        <?php
        $sql = "SELECT id, concepto, descripcion, formula, valor_fijo, monto_calculo, monto_cero, activo 
                FROM concepto 
                WHERE activo = 1 
                ORDER BY concepto";
        $stmt = $db->query($sql);
        $conceptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Fórmula</th>
                        <th>Valor Fijo</th>
                        <th>Configuración</th>
                        <?php if (isset($_GET['employee_id'])): ?>
                        <th>Resultado</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conceptos as $concepto): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($concepto['concepto']) ?></code></td>
                        <td><?= htmlspecialchars($concepto['descripcion']) ?></td>
                        <td><code><?= htmlspecialchars($concepto['formula'] ?: '-') ?></code></td>
                        <td><?= $concepto['valor_fijo'] ? 'Q ' . number_format($concepto['valor_fijo'], 2) : '-' ?></td>
                        <td>
                            <?= $concepto['monto_cero'] ? '<span class="badge badge-secondary">Permite 0</span> ' : '' ?>
                            <?= $concepto['monto_calculo'] ? '<span class="badge badge-primary">Calcula</span>' : '' ?>
                        </td>
                        <?php if (isset($_GET['employee_id'])): ?>
                        <td>
                            <?php
                            if (!empty($concepto['valor_fijo']) && $concepto['valor_fijo'] > 0) {
                                echo '<span class="result-success">Q ' . number_format($concepto['valor_fijo'], 2) . ' (fijo)</span>';
                            } elseif ($concepto['monto_cero'] == 1) {
                                echo '<span class="result-error">Q 0.00 (configurado en cero)</span>';
                            } elseif ($concepto['monto_calculo'] == 1 && !empty($concepto['formula'])) {
                                // Evaluar la fórmula para este concepto
                                $formulaConcepto = $concepto['formula'];
                                $formulaConVariables = $formulaConcepto;
                                foreach ($variables as $var => $valor) {
                                    $formulaConVariables = str_replace($var, (string)$valor, $formulaConVariables);
                                }
                                $formulaSanitizada = preg_replace('/[^0-9+\-*\/\(\)\.]/', '', $formulaConVariables);
                                try {
                                    if (!empty($formulaSanitizada)) {
                                        $resultado = eval("return $formulaSanitizada;");
                                        echo '<span class="result-success">Q ' . number_format($resultado, 2) . '</span>';
                                    } else {
                                        echo '<span class="result-error">Error: fórmula vacía</span>';
                                    }
                                } catch (Exception $e) {
                                    echo '<span class="result-error">Error: ' . $e->getMessage() . '</span>';
                                }
                            } else {
                                echo '<span class="text-muted">No calculado</span>';
                            }
                            ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php endif; ?>

    <div class="debug-section">
        <h4>4. Logs Recientes</h4>
        <div class="mb-2">
            <small class="text-muted">Últimas 20 líneas del log de errores PHP:</small>
        </div>
        <?php
        $logFile = 'C:/xampp82/php/logs/php_error_log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLines = array_slice($lines, -20);
            echo '<pre>' . htmlspecialchars(implode('', $recentLines)) . '</pre>';
        } else {
            echo '<p class="text-muted">No se encontró el archivo de log.</p>';
        }
        ?>
    </div>

    <div class="text-center mt-4">
        <a href="/panel/dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>
    </div>
</div>

<script src="/plugins/jquery/jquery.min.js"></script>
<script src="/plugins/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>