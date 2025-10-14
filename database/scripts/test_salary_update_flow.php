<?php
/**
 * Test Script: Flujo de Actualización de Salarios
 *
 * Simula el flujo completo de actualización de salarios cuando se cambia
 * de tipo de planilla en un empleado.
 *
 * Escenario: Empleado 17
 * - Antes: Tipos 1,2 con salarios $100 y $200 respectivamente
 * - Después: Tipos 2,5 con salarios $200 y $300 respectivamente
 * - Resultado esperado: Se elimina salario tipo 1, se mantiene tipo 2, se agrega tipo 5
 */

echo "\n=== TEST: Flujo de Actualización de Salarios ===\n\n";

// Conectar directamente a la BD usando PDO
$host = 'localhost';
$dbname = 'planilla_innova29092025';
$username = 'root';
$password = '';

try {
    $connection = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexión exitosa a BD: {$dbname}\n\n";
} catch (PDOException $e) {
    die("Error conectando a la BD: " . $e->getMessage() . "\n");
}

$employeeId = 17;

// ===========================================================================
// PASO 1: Estado Inicial
// ===========================================================================
echo "📋 PASO 1: Estado Inicial\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connection->prepare("
    SELECT e.id, e.firstname, e.lastname, e.tipo_planilla_id, e.sueldo_individual,
           e.gastos_representacion
    FROM employees e
    WHERE e.id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Empleado: {$employee['firstname']} {$employee['lastname']} (ID: {$employee['id']})\n";
echo "Tipos de planilla asignados: {$employee['tipo_planilla_id']}\n";
echo "Sueldo Individual (campo legacy): \${$employee['sueldo_individual']}\n";
echo "Gastos Representación (campo legacy): \${$employee['gastos_representacion']}\n\n";

$stmt = $connection->prepare("
    SELECT eps.id, eps.tipo_planilla_id, eps.sueldo_base, eps.gastos_representacion,
           tp.descripcion as tipo_planilla_nombre
    FROM employee_payroll_salaries eps
    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
    WHERE eps.employee_id = ?
    ORDER BY eps.tipo_planilla_id
");
$stmt->execute([$employeeId]);
$salariesBefore = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Salarios por Tipo de Planilla:\n";
foreach ($salariesBefore as $salary) {
    echo "  - Tipo {$salary['tipo_planilla_id']} ({$salary['tipo_planilla_nombre']}): ";
    echo "Sueldo Base: \${$salary['sueldo_base']}, Gastos Rep: \${$salary['gastos_representacion']}\n";
}
echo "\n";

// ===========================================================================
// PASO 2: Simular Cambio de Tipos de Planilla
// ===========================================================================
echo "📋 PASO 2: Simular Actualización (Tipos 2,5 con salarios \$250 y \$350)\n";
echo str_repeat("-", 80) . "\n";

// Simular lo que vendría del formulario
$newTiposPlanilla = [2, 5]; // Quitar tipo 1, mantener tipo 2, agregar tipo 5
$newSalaries = [
    2 => [
        'sueldo_base' => 250.00,  // Actualizar sueldo del tipo 2
        'gastos_representacion' => 0.00
    ],
    5 => [
        'sueldo_base' => 350.00,  // Nuevo salario para tipo 5
        'gastos_representacion' => 0.00
    ]
];

echo "Nuevos tipos de planilla seleccionados: " . implode(', ', $newTiposPlanilla) . "\n";
echo "Salarios a guardar:\n";
foreach ($newSalaries as $tipoId => $salaryData) {
    echo "  - Tipo {$tipoId}: Sueldo Base: \${$salaryData['sueldo_base']}, ";
    echo "Gastos Rep: \${$salaryData['gastos_representacion']}\n";
}
echo "\n";

// ===========================================================================
// PASO 3: Eliminar Salarios Huérfanos (Simulando deleteOrphanSalaries)
// ===========================================================================
echo "📋 PASO 3: Eliminar Salarios Huérfanos\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connection->prepare("
    SELECT id, tipo_planilla_id
    FROM employee_payroll_salaries
    WHERE employee_id = ?
");
$stmt->execute([$employeeId]);
$existingSalaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deleted = 0;
foreach ($existingSalaries as $salary) {
    if (!in_array($salary['tipo_planilla_id'], $newTiposPlanilla)) {
        echo "  - Eliminando salario huérfano: Tipo {$salary['tipo_planilla_id']} (ID: {$salary['id']})\n";
        $deleteSql = "DELETE FROM employee_payroll_salaries WHERE id = ?";
        $deleteStmt = $connection->prepare($deleteSql);
        $deleteStmt->execute([$salary['id']]);
        $deleted++;
    }
}

echo "Total eliminados: {$deleted}\n\n";

// ===========================================================================
// PASO 4: Guardar/Actualizar Salarios (Simulando saveBulkSalaries)
// ===========================================================================
echo "📋 PASO 4: Guardar/Actualizar Salarios\n";
echo str_repeat("-", 80) . "\n";

$saved = 0;
$updated = 0;

foreach ($newSalaries as $tipoPlanillaId => $salaryData) {
    // Verificar si ya existe
    $checkSql = "SELECT id, sueldo_base FROM employee_payroll_salaries
                 WHERE employee_id = ? AND tipo_planilla_id = ? AND is_active = 1";
    $checkStmt = $connection->prepare($checkSql);
    $checkStmt->execute([$employeeId, $tipoPlanillaId]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Actualizar existente
        echo "  - Actualizando tipo {$tipoPlanillaId}: \${$existing['sueldo_base']} → \${$salaryData['sueldo_base']}\n";
        $updateSql = "UPDATE employee_payroll_salaries
                      SET sueldo_base = ?, gastos_representacion = ?, updated_at = NOW()
                      WHERE id = ?";
        $updateStmt = $connection->prepare($updateSql);
        $updateStmt->execute([
            $salaryData['sueldo_base'],
            $salaryData['gastos_representacion'],
            $existing['id']
        ]);
        $updated++;
    } else {
        // Insertar nuevo
        echo "  - Insertando nuevo salario tipo {$tipoPlanillaId}: \${$salaryData['sueldo_base']}\n";
        $insertSql = "INSERT INTO employee_payroll_salaries
                      (employee_id, tipo_planilla_id, sueldo_base, gastos_representacion,
                       fecha_inicio, is_active, created_at, updated_at)
                      VALUES (?, ?, ?, ?, CURDATE(), 1, NOW(), NOW())";
        $insertStmt = $connection->prepare($insertSql);
        $insertStmt->execute([
            $employeeId,
            $tipoPlanillaId,
            $salaryData['sueldo_base'],
            $salaryData['gastos_representacion']
        ]);
        $saved++;
    }
}

echo "Nuevos insertados: {$saved}, Actualizados: {$updated}\n\n";

// ===========================================================================
// PASO 5: Actualizar campo tipo_planilla_id en employees
// ===========================================================================
echo "📋 PASO 5: Actualizar campo tipo_planilla_id en employees\n";
echo str_repeat("-", 80) . "\n";

$newTipoPlanillaString = implode(',', $newTiposPlanilla);
echo "Actualizando employees.tipo_planilla_id: '{$employee['tipo_planilla_id']}' → '{$newTipoPlanillaString}'\n\n";

$updateEmployeeSql = "UPDATE employees SET tipo_planilla_id = ? WHERE id = ?";
$updateEmployeeStmt = $connection->prepare($updateEmployeeSql);
$updateEmployeeStmt->execute([$newTipoPlanillaString, $employeeId]);

// ===========================================================================
// PASO 6: Verificar Estado Final
// ===========================================================================
echo "📋 PASO 6: Verificar Estado Final\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connection->prepare("
    SELECT e.id, e.firstname, e.lastname, e.tipo_planilla_id, e.sueldo_individual
    FROM employees e
    WHERE e.id = ?
");
$stmt->execute([$employeeId]);
$employeeAfter = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Empleado: {$employeeAfter['firstname']} {$employeeAfter['lastname']}\n";
echo "Tipos de planilla asignados: {$employeeAfter['tipo_planilla_id']}\n";
echo "Sueldo Individual (campo legacy): \${$employeeAfter['sueldo_individual']} (sin cambios)\n\n";

$stmt = $connection->prepare("
    SELECT eps.id, eps.tipo_planilla_id, eps.sueldo_base, eps.gastos_representacion,
           tp.descripcion as tipo_planilla_nombre
    FROM employee_payroll_salaries eps
    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
    WHERE eps.employee_id = ?
    ORDER BY eps.tipo_planilla_id
");
$stmt->execute([$employeeId]);
$salariesAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Salarios por Tipo de Planilla:\n";
foreach ($salariesAfter as $salary) {
    echo "  - Tipo {$salary['tipo_planilla_id']} ({$salary['tipo_planilla_nombre']}): ";
    echo "Sueldo Base: \${$salary['sueldo_base']}, Gastos Rep: \${$salary['gastos_representacion']}\n";
}
echo "\n";

// ===========================================================================
// VERIFICACIÓN FINAL
// ===========================================================================
echo "✅ VERIFICACIÓN FINAL\n";
echo str_repeat("-", 80) . "\n";

$expectedTypes = $newTiposPlanilla;
$actualTypes = array_column($salariesAfter, 'tipo_planilla_id');
$actualTypes = array_map('intval', $actualTypes);

if (count(array_diff($expectedTypes, $actualTypes)) === 0 && count(array_diff($actualTypes, $expectedTypes)) === 0) {
    echo "✓ Los tipos de planilla coinciden correctamente\n";
} else {
    echo "✗ ERROR: Los tipos no coinciden\n";
    echo "  Esperados: " . implode(', ', $expectedTypes) . "\n";
    echo "  Encontrados: " . implode(', ', $actualTypes) . "\n";
}

// Verificar que el sueldo del tipo 2 se actualizó
$tipo2Salary = null;
foreach ($salariesAfter as $salary) {
    if ($salary['tipo_planilla_id'] == 2) {
        $tipo2Salary = $salary['sueldo_base'];
        break;
    }
}

if ($tipo2Salary == 250.00) {
    echo "✓ El salario del tipo 2 se actualizó correctamente (\$250.00)\n";
} else {
    echo "✗ ERROR: El salario del tipo 2 no se actualizó (\${$tipo2Salary})\n";
}

// Verificar que existe el tipo 5
$hasType5 = false;
foreach ($salariesAfter as $salary) {
    if ($salary['tipo_planilla_id'] == 5) {
        $hasType5 = true;
        if ($salary['sueldo_base'] == 350.00) {
            echo "✓ El salario del tipo 5 se creó correctamente (\$350.00)\n";
        } else {
            echo "✗ ERROR: El salario del tipo 5 tiene monto incorrecto (\${$salary['sueldo_base']})\n";
        }
        break;
    }
}

if (!$hasType5) {
    echo "✗ ERROR: No se creó el salario para el tipo 5\n";
}

// Verificar que no existe el tipo 1
$hasType1 = false;
foreach ($salariesAfter as $salary) {
    if ($salary['tipo_planilla_id'] == 1) {
        $hasType1 = true;
        break;
    }
}

if (!$hasType1) {
    echo "✓ El salario del tipo 1 se eliminó correctamente\n";
} else {
    echo "✗ ERROR: El salario del tipo 1 todavía existe\n";
}

echo "\n=== FIN DEL TEST ===\n\n";
