<?php
/**
 * Test: Verificar datos que se pasan a la vista de edición
 *
 * Simula lo que el controlador Employee::edit() envía a la vista
 * para el empleado ID 5 de producción
 */

echo "\n=== TEST: Datos de Edición Empleado 5 (Producción) ===\n\n";

// Conectar a BD de producción
$host = 'localhost';
$dbname = 'planilla_prod';
$username = 'root';
$password = '';

try {
    $connection = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conexión exitosa a BD: {$dbname}\n\n";
} catch (PDOException $e) {
    die("Error conectando a la BD: " . $e->getMessage() . "\n");
}

$employeeId = 5;

// ===========================================================================
// PASO 1: Obtener datos del empleado (igual que el controlador)
// ===========================================================================
echo "📋 PASO 1: Datos del Empleado\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connection->prepare("
    SELECT e.*
    FROM employees e
    WHERE e.id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("❌ Empleado no encontrado\n");
}

echo "ID: {$employee['id']}\n";
echo "Nombre: {$employee['firstname']} {$employee['lastname']}\n";
echo "Tipos de Planilla (campo): {$employee['tipo_planilla_id']}\n";
echo "Sueldo Individual (legacy): \${$employee['sueldo_individual']}\n";
echo "Gastos Representación (legacy): \${$employee['gastos_representacion']}\n\n";

// ===========================================================================
// PASO 2: Obtener salarios existentes (igual que el controlador)
// ===========================================================================
echo "📋 PASO 2: Salarios Existentes en BD\n";
echo str_repeat("-", 80) . "\n";

$stmt = $connection->prepare("
    SELECT eps.*, tp.descripcion as tipo_planilla_nombre, tp.codigo as tipo_planilla_codigo
    FROM employee_payroll_salaries eps
    LEFT JOIN tipos_planilla tp ON eps.tipo_planilla_id = tp.id
    WHERE eps.employee_id = ?
    AND eps.is_active = 1
    AND (eps.fecha_fin IS NULL OR eps.fecha_fin >= CURDATE())
    ORDER BY tp.descripcion
");
$stmt->execute([$employeeId]);
$existingSalaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($existingSalaries)) {
    echo "⚠️ NO hay salarios configurados en employee_payroll_salaries\n\n";
} else {
    echo "Salarios encontrados:\n";
    foreach ($existingSalaries as $salary) {
        echo "  - Tipo {$salary['tipo_planilla_id']} ({$salary['tipo_planilla_nombre']}): ";
        echo "Sueldo: \${$salary['sueldo_base']}, Gastos: \${$salary['gastos_representacion']}\n";
    }
    echo "\n";
}

// ===========================================================================
// PASO 3: Preparar array $salariesByType (igual que el controlador)
// ===========================================================================
echo "📋 PASO 3: Array \$salariesByType para JavaScript\n";
echo str_repeat("-", 80) . "\n";

$salariesByType = [];
foreach ($existingSalaries as $salary) {
    $salariesByType[$salary['tipo_planilla_id']] = [
        'sueldo_base' => $salary['sueldo_base'],
        'gastos_representacion' => $salary['gastos_representacion']
    ];
}

echo "Array \$salariesByType:\n";
echo json_encode($salariesByType, JSON_PRETTY_PRINT) . "\n\n";

echo "JavaScript recibiría:\n";
echo "window.EMPLOYEE_SALARIES = " . json_encode($salariesByType) . ";\n\n";

// ===========================================================================
// PASO 4: Simular lo que debería mostrar el JavaScript
// ===========================================================================
echo "📋 PASO 4: Campos que JavaScript DEBERÍA Generar\n";
echo str_repeat("-", 80) . "\n";

// Obtener tipos de planilla del empleado
$tiposPlanillaIds = explode(',', $employee['tipo_planilla_id']);
$tiposPlanillaIds = array_map('trim', $tiposPlanillaIds);
$tiposPlanillaIds = array_filter($tiposPlanillaIds);

echo "Tipos de planilla seleccionados: " . implode(', ', $tiposPlanillaIds) . "\n\n";

// Obtener información de cada tipo
$placeholders = implode(',', array_fill(0, count($tiposPlanillaIds), '?'));
$stmt = $connection->prepare("SELECT id, descripcion FROM tipos_planilla WHERE id IN ($placeholders)");
$stmt->execute($tiposPlanillaIds);
$tiposPlanilla = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "JavaScript debería crear estos campos:\n\n";

foreach ($tiposPlanilla as $tipo) {
    $tipoPlanillaId = $tipo['id'];
    $existingSalary = $salariesByType[$tipoPlanillaId] ?? null;

    echo "  ┌─ Tipo {$tipoPlanillaId}: {$tipo['descripcion']}\n";
    echo "  ├─ Campo: salaries[{$tipoPlanillaId}][sueldo_base]\n";

    if ($existingSalary) {
        echo "  ├─ Valor prellenado: \${$existingSalary['sueldo_base']}\n";
        echo "  └─ Estado: ✅ TIENE VALOR EXISTENTE\n\n";
    } else {
        echo "  ├─ Valor prellenado: (vacío)\n";
        echo "  └─ Estado: ⚠️ NO TIENE VALOR - Usuario debe ingresar\n\n";
    }
}

// ===========================================================================
// PASO 5: Diagnóstico del Problema
// ===========================================================================
echo "📋 PASO 5: Diagnóstico\n";
echo str_repeat("-", 80) . "\n";

$tiposCount = count($tiposPlanillaIds);
$salariesCount = count($salariesByType);

echo "Tipos de planilla asignados: {$tiposCount}\n";
echo "Salarios configurados: {$salariesCount}\n\n";

if ($salariesCount < $tiposCount) {
    echo "❌ PROBLEMA IDENTIFICADO:\n";
    echo "El empleado tiene {$tiposCount} tipo(s) de planilla pero solo {$salariesCount} salario(s) configurado(s).\n\n";

    echo "Tipos SIN salario configurado:\n";
    foreach ($tiposPlanillaIds as $tipoId) {
        if (!isset($salariesByType[$tipoId])) {
            // Obtener nombre del tipo
            $stmt = $connection->prepare("SELECT descripcion FROM tipos_planilla WHERE id = ?");
            $stmt->execute([$tipoId]);
            $tipoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  - Tipo {$tipoId}: {$tipoInfo['descripcion']}\n";
        }
    }
    echo "\n";

    echo "SOLUCIÓN ESPERADA:\n";
    echo "1. El JavaScript debe mostrar campos VACÍOS para los tipos sin salario\n";
    echo "2. El usuario debe ingresar los valores manualmente\n";
    echo "3. Al guardar, el formulario debe enviar:\n";
    foreach ($tiposPlanillaIds as $tipoId) {
        echo "   - salaries[{$tipoId}][sueldo_base] = (valor ingresado por usuario)\n";
    }
    echo "\n";

} else {
    echo "✅ Todos los tipos de planilla tienen salarios configurados\n\n";
}

echo "=== FIN DEL TEST ===\n\n";
