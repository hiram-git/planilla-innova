<?php
/**
 * 🚨 ANÁLISIS CRÍTICO: VULNERABILIDAD DE ALMACENAMIENTO PERSISTENTE
 *
 * HALLAZGO: El payload "1 UNION SELECT password FROM users" se guarda en BD
 * pero NO se ejecuta inmediatamente durante la validación.
 *
 * ⚠️  ESTO CREA UNA BOMBA DE TIEMPO DE SEGURIDAD
 */

echo "<h1>🚨 VULNERABILIDAD DE ALMACENAMIENTO PERSISTENTE</h1>\n";

echo "<h2>🔍 ANÁLISIS DEL PROBLEMA ENCONTRADO:</h2>\n";

echo "<h3>1. 📥 PROCESO DE ALMACENAMIENTO (Vulnerable)</h3>\n";
echo "<p><strong>Archivo:</strong> <code>ConceptController.php:122-127</code></p>\n";
echo "<pre>";
echo "// VALIDACIÓN INSUFICIENTE:\n";
echo "if (!empty(\$formula)) {\n";
echo "    \$validation = \$this->validateFormula(\$formula);\n";
echo "    if (!\$validation['valid']) {\n";
echo "        throw new \\Exception('Fórmula inválida: ' . \$validation['message']);\n";
echo "    }\n";
echo "}\n\n";
echo "// EL PAYLOAD SE GUARDA EN BD:\n";
echo "'formula' => \$formula ?: null,  // ⚠️ SIN SANITIZACIÓN\n";
echo "</pre>\n";

echo "<h3>2. 🧪 PROCESO DE VALIDACIÓN (Limitado)</h3>\n";
echo "<p><strong>Archivo:</strong> <code>ConceptController.php:753</code></p>\n";
echo "<pre>";
echo "// SOLO VALIDA SI DEVUELVE NÚMERO:\n";
echo "\$resultado = \$calculator->evaluarFormula(\$conceptoTest);\n";
echo "if (is_numeric(\$resultado)) {\n";
echo "    return ['valid' => true, ...];\n";
echo "}\n\n";
echo "// ⚠️ EL SQL INJECTION NO DEVUELVE NÚMERO, PERO SE GUARDA IGUAL\n";
echo "</pre>\n";

echo "<h3>3. 💾 ALMACENAMIENTO EN BASE DE DATOS</h3>\n";
echo "<p><strong>Archivo:</strong> <code>Concept.php (createWithRelations)</code></p>\n";
echo "<pre>";
echo "// EL PAYLOAD MALICIOSO SE ALMACENA:\n";
echo "INSERT INTO concepto (..., formula, ...) VALUES (..., '1 UNION SELECT password FROM users', ...)\n";
echo "</pre>\n";

echo "<h2>💣 ESCENARIOS DE EXPLOTACIÓN RETARDADA:</h2>\n\n";

echo "<h3>Escenario 1: Ejecución durante procesamiento de planillas</h3>\n";
echo "<p>El SQL injection se ejecutaría cuando:</p>\n";
echo "<ul>\n";
echo "<li>Se procese una planilla que use este concepto</li>\n";
echo "<li>Se ejecute el motor de fórmulas en producción</li>\n";
echo "<li>Un administrador \"pruebe\" la fórmula más tarde</li>\n";
echo "</ul>\n";

echo "<h3>Escenario 2: Escalación de privilegios</h3>\n";
echo "<pre>";
echo "# Payloads almacenados sin ejecutar:\n";
echo "1; CREATE USER hacker@localhost IDENTIFIED BY 'password123'; //\n";
echo "1; GRANT ALL PRIVILEGES ON *.* TO hacker@localhost; //\n";
echo "1; INSERT INTO users (username, password, role) VALUES ('admin2', 'hash', 'admin'); //\n";
echo "</pre>\n";

echo "<h3>Escenario 3: Exfiltración de datos</h3>\n";
echo "<pre>";
echo "# Fórmulas que extraen información:\n";
echo "1 UNION SELECT CONCAT(username, ':', password) FROM users INTO OUTFILE '/tmp/dump.txt'\n";
echo "1; SELECT * FROM employees WHERE salary > 5000 INTO OUTFILE '/var/www/leak.txt'; //\n";
echo "</pre>\n";

echo "<h2>🔧 LUGARES DONDE SE EJECUTARÍA EL PAYLOAD:</h2>\n\n";

echo "<h3>1. En PlanillaConceptCalculator::evaluarFormula()</h3>\n";
echo "<p><strong>Archivo:</strong> <code>PlanillaConceptCalculator.php:387</code></p>\n";
echo "<pre>";
echo "// AQUÍ SE EJECUTARÍA EL SQL INJECTION:\n";
echo "try {\n";
echo "    \$result = eval(\"return \$expresionLimpia;\");  // ⚠️ BOOM!\n";
echo "    return (float)\$result;\n";
echo "}\n";
echo "</pre>\n";

echo "<h3>2. Durante procesamiento de planillas</h3>\n";
echo "<p><strong>Archivo:</strong> <code>PayrollController.php</code></p>\n";
echo "<pre>";
echo "// Cuando se procesa planilla que usa el concepto malicioso:\n";
echo "\$calculator->evaluarFormulaPorConcepto('CONCEPTO_MALICIOSO');\n";
echo "</pre>\n";

echo "<h3>3. En función de prueba de fórmulas</h3>\n";
echo "<p><strong>Archivo:</strong> <code>ConceptController.php:609</code></p>\n";
echo "<pre>";
echo "// Si alguien \"prueba\" la fórmula guardada:\n";
echo "\$result = \$calculator->evaluarFormula(\$formula);  // ⚠️ EJECUTA SQL\n";
echo "</pre>\n";

echo "<h2>🛡️ DEFENSAS REQUERIDAS:</h2>\n\n";

echo "<h3>1. Validación estricta en almacenamiento</h3>\n";
echo "<pre>";
echo "// En ConceptController::store()\n";
echo "if (!empty(\$formula)) {\n";
echo "    // VALIDACIÓN ESTRICTA:\n";
echo "    if (!preg_match('/^[a-zA-Z0-9_+\\-*\\/()\\s\\.=,\"\\[\\]]+\$/', \$formula)) {\n";
echo "        throw new \\Exception('Fórmula contiene caracteres no válidos');\n";
echo "    }\n";
echo "    \n";
echo "    // VERIFICAR PALABRAS PROHIBIDAS:\n";
echo "    \$forbidden = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'UNION', 'exec', 'system'];\n";
echo "    foreach (\$forbidden as \$word) {\n";
echo "        if (stripos(\$formula, \$word) !== false) {\n";
echo "            throw new \\Exception('Fórmula contiene comandos prohibidos');\n";
echo "        }\n";
echo "    }\n";
echo "}\n";
echo "</pre>\n";

echo "<h3>2. Sanitización en base de datos</h3>\n";
echo "<pre>";
echo "// En Concept.php - antes de guardar:\n";
echo "\$formula = \$this->sanitizeFormula(\$data['formula']);\n";
echo "\n";
echo "private function sanitizeFormula(\$formula) {\n";
echo "    // Escapar caracteres peligrosos\n";
echo "    \$formula = htmlspecialchars(\$formula, ENT_QUOTES, 'UTF-8');\n";
echo "    \n";
echo "    // Remover caracteres SQL\n";
echo "    \$formula = preg_replace('/[;\\'\"`]/', '', \$formula);\n";
echo "    \n";
echo "    return \$formula;\n";
echo "}\n";
echo "</pre>\n";

echo "<h3>3. Parser matemático seguro</h3>\n";
echo "<pre>";
echo "// REEMPLAZAR eval() completamente:\n";
echo "use NXP\\MathExecutor;\n";
echo "\n";
echo "private function evaluarExpresionMatematica(string \$expresion): float {\n";
echo "    \$executor = new MathExecutor();\n";
echo "    \n";
echo "    // Solo permitir operaciones matemáticas:\n";
echo "    \$executor->setVars([\n";
echo "        'SUELDO' => \$this->variablesColaborador['SUELDO'] ?? 0,\n";
echo "        'ANTIGUEDAD' => \$this->variablesColaborador['ANTIGUEDAD_DIAS'] ?? 0\n";
echo "    ]);\n";
echo "    \n";
echo "    try {\n";
echo "        return (float)\$executor->execute(\$expresion);\n";
echo "    } catch (Exception \$e) {\n";
echo "        error_log('Error en fórmula: ' . \$e->getMessage());\n";
echo "        return 0;\n";
echo "    }\n";
echo "}\n";
echo "</pre>\n";

echo "<h2>🧪 CÓMO VERIFICAR LA VULNERABILIDAD:</h2>\n";

echo "<ol>\n";
echo "<li><strong>Crear concepto</strong> con fórmula: <code>1 UNION SELECT password FROM users</code></li>\n";
echo "<li><strong>Verificar almacenamiento</strong>: <code>SELECT formula FROM concepto WHERE descripcion = 'TEST';</code></li>\n";
echo "<li><strong>Intentar ejecución</strong>: Usar el concepto en una planilla</li>\n";
echo "<li><strong>Monitorear logs</strong>: Revisar error_log por intentos de ejecución</li>\n";
echo "</ol>\n";

echo "<h2>🚨 SEVERIDAD: CRÍTICA</h2>\n";
echo "<p><strong>CVSS Score:</strong> 9.8 (Critical)</p>\n";
echo "<p><strong>Impacto:</strong></p>\n";
echo "<ul>\n";
echo "<li>Ejecución de código arbitrario</li>\n";
echo "<li>Acceso a toda la base de datos</li>\n";
echo "<li>Escalación de privilegios</li>\n";
echo "<li>Compromiso del servidor</li>\n";
echo "</ul>\n";

echo "<h2>🔒 RECOMENDACIÓN INMEDIATA:</h2>\n";
echo "<div style='background-color: #ffebee; padding: 10px; border-left: 4px solid #f44336;'>\n";
echo "<p><strong>ACCIÓN URGENTE:</strong></p>\n";
echo "<ol>\n";
echo "<li>Auditar conceptos existentes: <code>SELECT id, descripcion, formula FROM concepto WHERE formula LIKE '%SELECT%' OR formula LIKE '%UNION%';</code></li>\n";
echo "<li>Implementar validación estricta ANTES del almacenamiento</li>\n";
echo "<li>Reemplazar eval() con parser matemático seguro</li>\n";
echo "<li>Escapar/sanitizar fórmulas existentes en BD</li>\n";
echo "</ol>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><strong>⚠️  CONFIDENCIAL:</strong> Este análisis contiene información sensible de seguridad.</p>\n";
echo "<p><strong>📝 FECHA:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>