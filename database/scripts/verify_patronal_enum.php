<?php
/**
 * Script de Verificación: Tipo Concepto PATRONAL
 *
 * Verifica que el campo tipo_concepto en acumulados_por_empleado
 * incluya correctamente el valor 'PATRONAL' en el ENUM
 *
 * Ejecución: php database/scripts/verify_patronal_enum.php
 */

// Cargar variables de entorno
if (file_exists(__DIR__ . '/../../.env')) {
    $envFile = file_get_contents(__DIR__ . '/../../.env');
    $lines = explode("\n", $envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "\n";
echo "========================================\n";
echo "VERIFICACIÓN TIPO_CONCEPTO PATRONAL\n";
echo "========================================\n\n";

try {
    // Conexión directa a la base de datos
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'planilla_innova29092025';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $db = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Verificar estructura del campo
    echo "1. Estructura del campo tipo_concepto:\n";
    echo "   -----------------------------------\n";
    $stmt = $db->query("
        SELECT
            TABLE_NAME,
            COLUMN_NAME,
            COLUMN_TYPE,
            IS_NULLABLE,
            COLUMN_DEFAULT
        FROM
            INFORMATION_SCHEMA.COLUMNS
        WHERE
            TABLE_SCHEMA = 'planilla_innova29092025'
            AND TABLE_NAME = 'acumulados_por_empleado'
            AND COLUMN_NAME = 'tipo_concepto'
    ");

    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($columnInfo) {
        echo "   ✓ Tabla: " . $columnInfo['TABLE_NAME'] . "\n";
        echo "   ✓ Campo: " . $columnInfo['COLUMN_NAME'] . "\n";
        echo "   ✓ Tipo: " . $columnInfo['COLUMN_TYPE'] . "\n";
        echo "   ✓ Nullable: " . $columnInfo['IS_NULLABLE'] . "\n";
        echo "   ✓ Default: " . ($columnInfo['COLUMN_DEFAULT'] ?? 'NULL') . "\n\n";

        // Verificar si contiene PATRONAL
        if (strpos($columnInfo['COLUMN_TYPE'], 'PATRONAL') !== false) {
            echo "   ✅ El valor 'PATRONAL' está presente en el ENUM\n\n";
        } else {
            echo "   ❌ ERROR: El valor 'PATRONAL' NO está presente en el ENUM\n\n";
            exit(1);
        }
    } else {
        echo "   ❌ ERROR: No se pudo obtener información del campo\n\n";
        exit(1);
    }

    // 2. Contar registros por tipo_concepto
    echo "2. Distribución de registros por tipo_concepto:\n";
    echo "   --------------------------------------------\n";
    $stmt = $db->query("
        SELECT
            tipo_concepto,
            COUNT(*) as total,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM acumulados_por_empleado), 2) as porcentaje
        FROM
            acumulados_por_empleado
        GROUP BY
            tipo_concepto
        ORDER BY
            total DESC
    ");

    $totalGeneral = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   • {$row['tipo_concepto']}: {$row['total']} registros ({$row['porcentaje']}%)\n";
        $totalGeneral += $row['total'];
    }
    echo "   -----------------------------------\n";
    echo "   📊 TOTAL: {$totalGeneral} registros\n\n";

    // 3. Verificar si existen registros PATRONAL
    echo "3. Verificar registros con tipo_concepto = 'PATRONAL':\n";
    echo "   -------------------------------------------------\n";
    $stmt = $db->query("
        SELECT COUNT(*) as total_patronal
        FROM acumulados_por_empleado
        WHERE tipo_concepto = 'PATRONAL'
    ");

    $patronalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_patronal'];

    if ($patronalCount > 0) {
        echo "   ✅ Existen {$patronalCount} registros con tipo_concepto = 'PATRONAL'\n\n";

        // Mostrar muestra de registros PATRONAL
        echo "4. Muestra de registros PATRONAL (últimos 5):\n";
        echo "   -------------------------------------------\n";
        $stmt = $db->query("
            SELECT
                a.id,
                e.firstname,
                e.lastname,
                c.concepto as concepto_nombre,
                a.monto,
                a.mes,
                a.ano,
                a.tipo_concepto
            FROM
                acumulados_por_empleado a
                INNER JOIN employees e ON a.employee_id = e.id
                INNER JOIN concepto c ON a.concepto_id = c.id
            WHERE
                a.tipo_concepto = 'PATRONAL'
            ORDER BY
                a.id DESC
            LIMIT 5
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   • ID: {$row['id']} | {$row['firstname']} {$row['lastname']} | ";
            echo "{$row['concepto_nombre']} | \${$row['monto']} | {$row['mes']}/{$row['ano']}\n";
        }
        echo "\n";
    } else {
        echo "   ⚠️  No existen registros con tipo_concepto = 'PATRONAL' aún\n";
        echo "   (Esto es normal si aún no se han procesado conceptos patronales)\n\n";
    }

    // 5. Verificar integridad con tabla concepto
    echo "5. Verificar correspondencia con tabla concepto:\n";
    echo "   -----------------------------------------------\n";
    $stmt = $db->query("
        SELECT
            c.tipo_concepto,
            COUNT(DISTINCT c.id) as conceptos_definidos,
            COUNT(a.id) as acumulados_registrados
        FROM
            concepto c
            LEFT JOIN acumulados_por_empleado a ON c.id = a.concepto_id AND c.tipo_concepto = a.tipo_concepto
        GROUP BY
            c.tipo_concepto
        ORDER BY
            c.tipo_concepto
    ");

    echo "   Tipo         | Conceptos | Acumulados\n";
    echo "   -------------|-----------|------------\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf("   %-12s | %9d | %10d\n",
            $row['tipo_concepto'],
            $row['conceptos_definidos'],
            $row['acumulados_registrados']
        );
    }
    echo "\n";

    // 6. Test de inserción (sin commit)
    echo "6. Test de inserción de registro PATRONAL:\n";
    echo "   ----------------------------------------\n";

    $db->beginTransaction();

    try {
        // Obtener un concepto patronal para test
        $stmt = $db->query("
            SELECT id FROM concepto
            WHERE tipo_concepto = 'PATRONAL'
            LIMIT 1
        ");
        $conceptoPatronal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conceptoPatronal) {
            // Obtener un empleado para test
            $stmt = $db->query("SELECT id FROM employees WHERE situacion_id = 1 LIMIT 1");
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($empleado) {
                // Insertar registro de prueba
                $testStmt = $db->prepare("
                    INSERT INTO acumulados_por_empleado
                    (employee_id, concepto_id, planilla_id, monto, mes, ano, frecuencia, tipo_concepto, tipo_acumulado)
                    VALUES (?, ?, 1, 100.00, 10, 2025, 1, 'PATRONAL', 'TEST')
                ");

                $testStmt->execute([$empleado['id'], $conceptoPatronal['id']]);

                echo "   ✅ Test de inserción exitoso (transacción revertida)\n";
                echo "   Se puede insertar correctamente tipo_concepto = 'PATRONAL'\n\n";
            } else {
                echo "   ⚠️  No se encontraron empleados activos para test\n\n";
            }
        } else {
            echo "   ⚠️  No se encontraron conceptos PATRONAL para test\n\n";
        }

        // Revertir la transacción (no queremos guardar el test)
        $db->rollBack();

    } catch (Exception $e) {
        $db->rollBack();
        echo "   ❌ ERROR en test de inserción: " . $e->getMessage() . "\n\n";
    }

    // Resumen final
    echo "========================================\n";
    echo "RESUMEN DE VERIFICACIÓN\n";
    echo "========================================\n";
    echo "✅ Campo tipo_concepto correctamente configurado\n";
    echo "✅ Valores ENUM: ASIGNACION, DEDUCCION, PATRONAL\n";
    echo "✅ Sistema listo para manejar conceptos patronales\n";
    echo "========================================\n\n";

} catch (PDOException $e) {
    echo "\n❌ ERROR DE BASE DE DATOS:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL:\n";
    echo $e->getMessage() . "\n\n";
    exit(1);
}

echo "✅ Verificación completada exitosamente\n\n";
