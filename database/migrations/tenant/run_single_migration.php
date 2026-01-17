<?php
/**
 * Ejecutar migración individual en la base de datos de tenant
 */

$migrationFile = __DIR__ . '/2026_01_16_employee_additional_fields.sql';

// Cargar variables de entorno manualmente
$envFile = __DIR__ . '/../../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Configuración de la base de datos
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'planilla_prod';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

/**
 * Dividir contenido SQL en statements individuales
 * Maneja correctamente string literals y comentarios
 */
function splitSqlStatements($sql)
{
    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $inComment = false;
    $commentType = '';

    $lines = explode("\n", $sql);

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // Ignorar líneas vacías
        if (empty($trimmed)) {
            continue;
        }

        // Ignorar comentarios de línea completa
        if (substr($trimmed, 0, 2) === '--' || substr($trimmed, 0, 1) === '#') {
            continue;
        }

        // Procesar línea caracter por caracter
        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $char = $line[$i];
            $nextChar = ($i + 1 < $len) ? $line[$i + 1] : '';

            // Detectar inicio/fin de string literals
            if (!$inComment && ($char === '"' || $char === "'")) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar && ($i === 0 || $line[$i - 1] !== '\\')) {
                    $inString = false;
                    $stringChar = '';
                }
            }

            // Detectar inicio de comentario
            if (!$inString && !$inComment) {
                if ($char === '-' && $nextChar === '-') {
                    $inComment = true;
                    $commentType = 'line';
                    $i++; // Skip next char
                    continue;
                }
                if ($char === '/' && $nextChar === '*') {
                    $inComment = true;
                    $commentType = 'block';
                    $i++; // Skip next char
                    continue;
                }
            }

            // Detectar fin de comentario
            if ($inComment) {
                if ($commentType === 'block' && $char === '*' && $nextChar === '/') {
                    $inComment = false;
                    $commentType = '';
                    $i++; // Skip next char
                    continue;
                }
                if ($commentType === 'line') {
                    // Comentario de línea termina al final de la línea
                    break;
                }
                continue; // No agregar caracteres de comentarios
            }

            // Detectar fin de statement (;)
            if (!$inString && !$inComment && $char === ';') {
                $buffer .= $char;
                $statement = trim($buffer);
                if (!empty($statement)) {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        // Agregar newline si no estamos en comentario
        if (!$inComment) {
            $buffer .= "\n";
        }

        // Reset comentario de línea al final de la línea
        if ($inComment && $commentType === 'line') {
            $inComment = false;
            $commentType = '';
        }
    }

    // Agregar último statement si existe
    $lastStatement = trim($buffer);
    if (!empty($lastStatement) && $lastStatement !== ';') {
        $statements[] = $lastStatement;
    }

    return $statements;
}

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Ejecutando migración: " . basename($migrationFile) . "\n";
    echo "Base de datos: $dbname\n";
    echo "Conexión exitosa\n\n";

    // Leer archivo SQL
    $sql = file_get_contents($migrationFile);

    // Separar SQL en statements individuales usando parser robusto
    $statements = splitSqlStatements($sql);

    echo "Ejecutando " . count($statements) . " sentencias SQL...\n\n";

    $executed = 0;
    foreach ($statements as $index => $statement) {
        $trimmedStatement = trim($statement);
        if (!empty($trimmedStatement)) {
            try {
                // Usar query() en lugar de exec() para evitar errores de buffering
                $result = $pdo->query($trimmedStatement);
                if ($result) {
                    $result->closeCursor(); // Liberar resultado inmediatamente
                }
                $executed++;

                // Mostrar progreso para sentencias importantes
                if (stripos($trimmedStatement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?([\w_]+)`?/i', $trimmedStatement, $matches);
                    $tableName = $matches[1] ?? 'tabla';
                    echo "✓ Tabla '$tableName' creada\n";
                } elseif (stripos($trimmedStatement, 'INSERT INTO') !== false) {
                    echo "✓ Datos de ejemplo insertados\n";
                }
            } catch (PDOException $e) {
                // Ignorar errores de "tabla ya existe"
                if (strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "⚠ Advertencia en statement #" . ($index + 1) . ": " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "\n✅ Migración ejecutada exitosamente ($executed sentencias)\n\n";

    // Verificar tablas creadas
    echo "Verificación de tablas:\n";
    $tables = ['employee_additional_fields', 'employee_additional_field_values'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "✓ Tabla '$table' existe ($count registros)\n";
        } else {
            echo "✗ Tabla '$table' NO existe\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
