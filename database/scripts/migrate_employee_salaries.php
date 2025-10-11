<?php
/**
 * Script de Migración: Salarios de Empleados
 *
 * Migra los salarios existentes desde la tabla employees (campos sueldo_individual y gastos_representacion)
 * hacia la nueva tabla employee_payroll_salaries vinculados con tipos de planilla.
 *
 * Estrategia:
 * 1. Para cada empleado con sueldo_individual > 0
 * 2. Obtener los tipos de planilla asignados al empleado (campo tipo_planilla)
 * 3. Crear un registro en employee_payroll_salaries por cada tipo de planilla
 * 4. Fecha inicio = fecha_ingreso del empleado
 * 5. Fecha fin = NULL (vigente indefinidamente)
 * 6. is_active = 1
 *
 * @package Planilla-Innova
 * @version 3.4.1
 */

// Configuración de base de datos (ajustar según tu entorno)

// Cargar variables de entorno desde .env
$rootPath = dirname(dirname(__DIR__));
$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, "\"'");
        }
    }
}

// Configuración de BD
$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_DATABASE'] ?? 'planilla_innova29092025';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';
try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "===============================================\n";
    echo "MIGRACIÓN DE SALARIOS DE EMPLEADOS\n";
    echo "===============================================\n\n";

    // Iniciar transacción
    $pdo->beginTransaction();

    // Obtener todos los empleados con tipo_planilla_id y salario
    $query = "
        SELECT
            id,
            employee_id,
            tipo_planilla_id,
            sueldo_individual,
            gastos_representacion,
            fecha_ingreso
        FROM employees
        WHERE sueldo_individual > 0
        AND sueldo_individual IS NOT NULL
        ORDER BY id
    ";

    $stmt = $pdo->query($query);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total de empleados a migrar: " . count($employees) . "\n\n";

    $migrated = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($employees as $employee) {
        echo "Procesando empleado: {$employee['employee_id']} - ID: {$employee['id']}\n";

        // Obtener tipos de planilla (puede ser CSV)
        $tiposPlanilla = !empty($employee['tipo_planilla_id']) ? explode(',', $employee['tipo_planilla_id']) : [];

        if (empty($tiposPlanilla)) {
            echo "  ⚠️  Sin tipos de planilla asignados - OMITIDO\n\n";
            $skipped++;
            continue;
        }

        echo "  Tipos de planilla: " . implode(', ', $tiposPlanilla) . "\n";
        echo "  Sueldo base: {$employee['sueldo_individual']}\n";
        echo "  Gastos representación: {$employee['gastos_representacion']}\n";

        $recordsCreated = 0;

        foreach ($tiposPlanilla as $tipoPlanillaId) {
            $tipoPlanillaId = trim($tipoPlanillaId);

            if (empty($tipoPlanillaId)) {
                continue;
            }

            // Verificar si ya existe un registro
            $checkQuery = "
                SELECT id
                FROM employee_payroll_salaries
                WHERE employee_id = ?
                AND tipo_planilla_id = ?
            ";

            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$employee['id'], $tipoPlanillaId]);

            if ($checkStmt->fetch()) {
                echo "    ℹ️  Ya existe registro para tipo_planilla_id: {$tipoPlanillaId} - OMITIDO\n";
                continue;
            }

            // Insertar nuevo registro
            try {
                $insertQuery = "
                    INSERT INTO employee_payroll_salaries (
                        employee_id,
                        tipo_planilla_id,
                        sueldo_base,
                        gastos_representacion,
                        fecha_inicio,
                        fecha_fin,
                        is_active,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, NULL, 1, NOW(), NOW())
                ";

                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([
                    $employee['id'],
                    $tipoPlanillaId,
                    $employee['sueldo_individual'],
                    $employee['gastos_representacion'] ?? 0,
                    $employee['fecha_ingreso'] ?? date('Y-m-d')
                ]);

                echo "    ✅ Registro creado para tipo_planilla_id: {$tipoPlanillaId}\n";
                $recordsCreated++;

            } catch (PDOException $e) {
                echo "    ❌ Error insertando tipo_planilla_id {$tipoPlanillaId}: " . $e->getMessage() . "\n";
                $errors++;
            }
        }

        if ($recordsCreated > 0) {
            echo "  ✅ Total registros creados: {$recordsCreated}\n";
            $migrated++;
        }

        echo "\n";
    }

    // Confirmar transacción
    $pdo->commit();

    echo "===============================================\n";
    echo "RESUMEN DE MIGRACIÓN\n";
    echo "===============================================\n";
    echo "Empleados migrados exitosamente: {$migrated}\n";
    echo "Empleados omitidos: {$skipped}\n";
    echo "Errores encontrados: {$errors}\n";
    echo "\n";

    // Estadísticas finales
    $statsQuery = "
        SELECT COUNT(*) as total_records
        FROM employee_payroll_salaries
    ";
    $statsStmt = $pdo->query($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo "Total de registros en employee_payroll_salaries: {$stats['total_records']}\n";
    echo "\n✅ Migración completada exitosamente\n\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
