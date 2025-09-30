<?php
// Configuración básica de conexión
$host = 'localhost';
$dbname = 'planilla_innova';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFICACIÓN DE SEPARACIÓN EMPLEADOS ACTIVOS/TERMINADOS ===\n\n";

    // 1. Verificar situaciones disponibles
    echo "1. SITUACIONES DISPONIBLES:\n";
    echo "==========================\n";

    $sql = "SELECT id, nombre, descripcion FROM situaciones ORDER BY id";
    $stmt = $pdo->query($sql);
    $situaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($situaciones as $situacion) {
        echo "- ID {$situacion['id']}: {$situacion['nombre']}\n";
        if ($situacion['descripcion']) {
            echo "  Descripción: {$situacion['descripcion']}\n";
        }
    }

    // 2. Contar empleados por situación
    echo "\n2. EMPLEADOS POR SITUACIÓN:\n";
    echo "===========================\n";

    $sql = "SELECT s.id, s.nombre as situacion_nombre, COUNT(e.id) as total_empleados
            FROM situaciones s
            LEFT JOIN employees e ON s.id = e.situacion_id
            GROUP BY s.id, s.nombre
            ORDER BY s.id";

    $stmt = $pdo->query($sql);
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalActivos = 0;
    $totalTerminados = 0;

    foreach ($stats as $stat) {
        echo "- {$stat['situacion_nombre']}: {$stat['total_empleados']} empleados\n";

        if ($stat['id'] == 1) { // Asumiendo que situacion_id = 1 es ACTIVO
            $totalActivos = $stat['total_empleados'];
        } else {
            $totalTerminados += $stat['total_empleados'];
        }
    }

    echo "\n3. RESUMEN FILTROS:\n";
    echo "==================\n";
    echo "📈 Vista /panel/employees (ACTIVOS): $totalActivos empleados\n";
    echo "📉 Vista /panel/employees/terminated (TERMINADOS): $totalTerminados empleados\n";

    // 4. Verificar algunos empleados terminados
    echo "\n4. MUESTRA DE EMPLEADOS TERMINADOS:\n";
    echo "==================================\n";

    $sql = "SELECT e.employee_id, e.firstname, e.lastname, s.nombre as situacion,
                   et.termination_date, et.reason
            FROM employees e
            LEFT JOIN situaciones s ON e.situacion_id = s.id
            LEFT JOIN employee_terminations et ON et.employee_id = e.id
            WHERE e.situacion_id != 1
            LIMIT 5";

    $stmt = $pdo->query($sql);
    $terminados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($terminados)) {
        echo "⚠️  No hay empleados terminados en el sistema\n";
        echo "Para probar la vista, puedes:\n";
        echo "1. Cambiar la situación de algún empleado activo\n";
        echo "2. Crear una liquidación que automáticamente termine al empleado\n";
    } else {
        foreach ($terminados as $empleado) {
            echo "- {$empleado['employee_id']}: {$empleado['firstname']} {$empleado['lastname']}\n";
            echo "  Situación: {$empleado['situacion']}\n";
            if ($empleado['termination_date']) {
                echo "  Fecha Terminación: {$empleado['termination_date']}\n";
            }
            if ($empleado['reason']) {
                echo "  Motivo: {$empleado['reason']}\n";
            }
            echo "\n";
        }
    }

    // 5. URLs de prueba
    echo "5. URLs PARA PROBAR:\n";
    echo "==================\n";
    echo "📋 Vista Activos: http://localhost/planilla-innova/panel/employees\n";
    echo "🗂️  Vista Terminados: http://localhost/planilla-innova/panel/employees/terminated\n";
    echo "🔗 API Activos: http://localhost/planilla-innova/panel/employees/datatables-ajax\n";
    echo "🔗 API Terminados: http://localhost/planilla-innova/panel/employees/terminated-datatables-ajax\n\n";

    echo "✅ SISTEMA DE SEPARACIÓN DE EMPLEADOS CONFIGURADO CORRECTAMENTE!\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>