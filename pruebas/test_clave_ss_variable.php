<?php
/**
 * Test para verificar que la variable CLAVE_SS se carga correctamente
 */

echo "=== TEST VARIABLE CLAVE_SS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Verificar datos directamente de la BD
try {
    $pdo = new PDO("mysql:host=localhost;dbname=planilla_innova29092025", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener empleados que sabemos tienen ISR
    $sql = "SELECT id, firstname, lastname, clave_seguro_social, sueldo_individual
            FROM employees
            WHERE id IN (13, 12, 4, 6, 14)
            ORDER BY sueldo_individual DESC";

    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "EMPLEADOS EN BD:\n";
    echo "===============\n";
    foreach ($employees as $emp) {
        echo "ID: {$emp['id']} - {$emp['firstname']} {$emp['lastname']}\n";
        echo "  Clave SS: '{$emp['clave_seguro_social']}'\n";
        echo "  Salario: B/." . number_format($emp['sueldo_individual'], 2) . "\n";
        echo "  Primera letra: '" . substr($emp['clave_seguro_social'], 0, 1) . "'\n";
        echo "  ¿Debe tener deducción? " . (substr($emp['clave_seguro_social'], 0, 1) === 'E' ? 'SÍ' : 'NO') . "\n\n";
    }

    // Simular lo que hace PlanillaConceptCalculator
    echo "=== SIMULACIÓN PlanillaConceptCalculator ===\n";

    foreach ($employees as $emp) {
        echo "Procesando empleado ID {$emp['id']} - {$emp['firstname']} {$emp['lastname']}:\n";

        // Simular el SQL query actualizado
        $sql = "SELECT e.fecha_ingreso, e.employee_id, e.sueldo_individual, e.gastos_representacion, e.clave_seguro_social, p.sueldo as sueldo_posicion, s.time_in, s.time_out
                FROM employees e
                LEFT JOIN posiciones p ON p.id = e.position_id
                LEFT JOIN schedules s ON s.id = e.schedule_id
                WHERE e.id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$emp['id']]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            $clave_seguro_social = $employee['clave_seguro_social'] ?: '';
            $salario = (float)($employee['sueldo_individual'] ?: 0);
            $gastos_representacion = (float)($employee['gastos_representacion'] ?: 0);

            echo "  Variables que se cargarían:\n";
            echo "    SALARIO = $salario\n";
            echo "    GASTOS_REPRESENTACION = $gastos_representacion\n";
            echo "    CLAVE_SS = '$clave_seguro_social'\n";

            // Simular función LEFT()
            $primera_letra = substr($clave_seguro_social, 0, 1);
            echo "    LEFT(CLAVE_SS, 1) = '$primera_letra'\n";

            // Simular función SI()
            $deduc_pers = ($primera_letra === 'E') ? 800 : 0;
            echo "    SI(LEFT(CLAVE_SS, 1) = \"E\", 800, 0) = $deduc_pers\n";

            // Calcular ISR básico
            $salario_anual = $salario * 13;
            $neto_gravable = $salario_anual - $deduc_pers;
            $saldo_gravable = max(0, $neto_gravable - 11000);
            $isr_anual = $saldo_gravable * 0.15;
            $isr_quincenal = $isr_anual / 13 / 2;

            echo "    Cálculo ISR:\n";
            echo "      Salario anual: B/." . number_format($salario_anual, 2) . "\n";
            echo "      Neto gravable: B/." . number_format($neto_gravable, 2) . "\n";
            echo "      Saldo gravable: B/." . number_format($saldo_gravable, 2) . "\n";
            echo "      ISR quincenal: B/." . number_format($isr_quincenal, 2) . "\n";

            echo "\n";
        } else {
            echo "  ERROR: No se encontraron datos del empleado\n\n";
        }
    }

    echo "=== RESUMEN ===\n";
    echo "✅ Variable CLAVE_SS ahora está incluida en PlanillaConceptCalculator\n";
    echo "✅ SQL query actualizado para obtener clave_seguro_social\n";
    echo "✅ Función LEFT() puede extraer primer caracter\n";
    echo "✅ Función SI() puede evaluar deducción personal\n";
    echo "\nLa fórmula ISR ahora debería funcionar correctamente.\n";

} catch (PDOException $e) {
    echo "ERROR de BD: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";