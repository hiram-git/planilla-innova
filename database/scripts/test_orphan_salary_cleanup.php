<?php
/**
 * Test Script: Eliminación de Salarios Huérfanos
 *
 * Prueba la funcionalidad de limpieza automática de salarios cuando
 * se quita un tipo de planilla de un empleado.
 *
 * Caso de prueba: Empleado 17
 * - Tipos de planilla actuales: 2,5
 * - Salarios en BD: tipo 1, 2, 5
 * - Resultado esperado: Se elimina el salario del tipo 1 (huérfano)
 */

echo "\n=== TEST: Eliminación de Salarios Huérfanos ===\n\n";

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

// Crear wrapper para el modelo que use nuestra conexión directa
class TestEmployeePayrollSalary {
    private $connection;
    private $table = 'employee_payroll_salaries';

    public function __construct($connection) {
        $this->connection = $connection;
    }

    public function deleteOrphanSalaries($employeeId, $currentTiposPlanillaIds) {
        try {
            $deleted = 0;

            // Si no hay tipos de planilla seleccionados, eliminar todos los salarios
            if (empty($currentTiposPlanillaIds)) {
                $sql = "DELETE FROM {$this->table} WHERE employee_id = ?";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute([$employeeId]);
                $deleted = $stmt->rowCount();

                return [
                    'success' => true,
                    'deleted' => $deleted,
                    'message' => "Todos los salarios eliminados (empleado sin tipos de planilla)"
                ];
            }

            // Obtener salarios existentes
            $sql = "SELECT id, tipo_planilla_id FROM {$this->table} WHERE employee_id = ?";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([$employeeId]);
            $existingSalaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Identificar salarios huérfanos (tipos de planilla que ya no están asignados)
            foreach ($existingSalaries as $salary) {
                if (!in_array($salary['tipo_planilla_id'], $currentTiposPlanillaIds)) {
                    // Eliminar salario huérfano
                    $deleteSql = "DELETE FROM {$this->table} WHERE id = ?";
                    $deleteStmt = $this->connection->prepare($deleteSql);
                    if ($deleteStmt->execute([$salary['id']])) {
                        $deleted++;
                    }
                }
            }

            return [
                'success' => true,
                'deleted' => $deleted,
                'message' => $deleted > 0 ? "Se eliminaron {$deleted} salario(s) huérfano(s)" : "No hay salarios huérfanos"
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'deleted' => 0,
                'message' => 'Error al eliminar salarios huérfanos',
                'error' => $e->getMessage()
            ];
        }
    }
}

$employeePayrollSalary = new TestEmployeePayrollSalary($connection);

// Empleado de prueba
$employeeId = 17;

echo "📋 PASO 1: Estado inicial\n";
echo str_repeat("-", 60) . "\n";

// Obtener tipos de planilla actuales del empleado
$stmt = $connection->prepare("SELECT id, firstname, lastname, tipo_planilla_id FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Empleado: {$employee['firstname']} {$employee['lastname']} (ID: {$employee['id']})\n";
echo "Tipos de planilla asignados: {$employee['tipo_planilla_id']}\n\n";

// Obtener salarios actuales
$stmt = $connection->prepare("SELECT id, tipo_planilla_id, sueldo_base, gastos_representacion FROM employee_payroll_salaries WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Salarios en BD:\n";
foreach ($salaries as $salary) {
    echo "  - ID: {$salary['id']} | Tipo Planilla: {$salary['tipo_planilla_id']} | Sueldo: \${$salary['sueldo_base']}\n";
}
echo "\nTotal salarios: " . count($salaries) . "\n";

// Identificar tipos de planilla seleccionados
$selectedTiposPlanillaIds = array_map('intval', explode(',', $employee['tipo_planilla_id']));
echo "\nTipos de planilla seleccionados (array): " . implode(', ', $selectedTiposPlanillaIds) . "\n";

echo "\n📋 PASO 2: Ejecutar limpieza de salarios huérfanos\n";
echo str_repeat("-", 60) . "\n";

// Llamar al método deleteOrphanSalaries
$result = $employeePayrollSalary->deleteOrphanSalaries($employeeId, $selectedTiposPlanillaIds);

echo "Resultado de limpieza:\n";
echo "  - Success: " . ($result['success'] ? 'SI' : 'NO') . "\n";
echo "  - Salarios eliminados: {$result['deleted']}\n";
echo "  - Mensaje: {$result['message']}\n";

if (isset($result['error'])) {
    echo "  - Error: {$result['error']}\n";
}

echo "\n📋 PASO 3: Verificar estado final\n";
echo str_repeat("-", 60) . "\n";

// Obtener salarios después de la limpieza
$stmt = $connection->prepare("SELECT id, tipo_planilla_id, sueldo_base, gastos_representacion FROM employee_payroll_salaries WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$salariesAfter = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Salarios en BD después de limpieza:\n";
foreach ($salariesAfter as $salary) {
    echo "  - ID: {$salary['id']} | Tipo Planilla: {$salary['tipo_planilla_id']} | Sueldo: \${$salary['sueldo_base']}\n";
}
echo "\nTotal salarios: " . count($salariesAfter) . "\n";

echo "\n✅ VERIFICACIÓN:\n";
echo str_repeat("-", 60) . "\n";

$salariesBefore = count($salaries);
$salariesAfterCount = count($salariesAfter);
$expectedDeleted = $salariesBefore - $salariesAfterCount;

if ($result['deleted'] === $expectedDeleted) {
    echo "✓ Cantidad de eliminaciones correcta\n";
} else {
    echo "✗ ERROR: Se esperaban {$expectedDeleted} eliminaciones, pero se reportaron {$result['deleted']}\n";
}

// Verificar que solo queden los tipos de planilla seleccionados
$remainingTypes = array_column($salariesAfter, 'tipo_planilla_id');
$unexpectedTypes = array_diff($remainingTypes, $selectedTiposPlanillaIds);

if (empty($unexpectedTypes)) {
    echo "✓ Solo quedan salarios de tipos de planilla seleccionados\n";
} else {
    echo "✗ ERROR: Aún existen salarios de tipos no seleccionados: " . implode(', ', $unexpectedTypes) . "\n";
}

// Verificar que todos los tipos seleccionados tienen salario (opcional, podría no tener)
$missingTypes = array_diff($selectedTiposPlanillaIds, $remainingTypes);
if (!empty($missingTypes)) {
    echo "⚠ ADVERTENCIA: Faltan salarios para tipos de planilla: " . implode(', ', $missingTypes) . "\n";
    echo "  (Esto es normal si no se han guardado aún)\n";
}

echo "\n=== FIN DEL TEST ===\n\n";
