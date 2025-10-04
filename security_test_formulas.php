<?php
/**
 * 🔒 PRUEBA DE SEGURIDAD - FÓRMULAS DE CONCEPTOS
 *
 * Este script demuestra vulnerabilidades en el sistema de prueba de fórmulas
 * Endpoint vulnerable: /panel/concepts/test-formula
 *
 * ⚠️  SOLO PARA PROPÓSITOS DE TESTING Y MEJORA DE SEGURIDAD
 */

echo "<h1>🔒 Prueba de Seguridad - Fórmulas de Conceptos</h1>\n";

// Configuración
$base_url = "http://localhost/planilla-innova";
$endpoint = "/panel/concepts/test-formula";

echo "<h2>📍 Endpoint analizado:</h2>\n";
echo "<p><strong>URL:</strong> $base_url$endpoint</p>\n";
echo "<p><strong>Método:</strong> POST</p>\n";
echo "<p><strong>Parámetros:</strong> formula, employee_id</p>\n\n";

echo "<h2>🚨 VULNERABILIDADES IDENTIFICADAS:</h2>\n\n";

echo "<h3>1. 🔓 CODE INJECTION - eval() sin sanitización completa</h3>\n";
echo "<p><strong>Ubicación:</strong> <code>PlanillaConceptCalculator.php:387</code></p>\n";
echo "<pre>// CÓDIGO VULNERABLE:\n";
echo "try {\n";
echo "    \$result = eval(\"return \$expresionLimpia;\");\n";
echo "    return (float)\$result;\n";
echo "}\n</pre>\n";

echo "<p><strong>Problema:</strong> Aunque hay sanitización con regex, eval() es inherentemente peligroso.</p>\n";

echo "<h4>💣 Payload de prueba:</h4>\n";
echo "<pre>";
echo "# En el campo 'formula' del formulario:\n";
echo "1; system('whoami'); //\n\n";
echo "# O para obtener información:\n";
echo "1; phpinfo(); //\n\n";
echo "# Para ejecutar comandos SQL:\n";
echo "1; \$_GET['x'] = 'SELECT * FROM users'; //\n";
echo "</pre>\n";

echo "<h3>2. 🗃️ SQL INJECTION INDIRECTA</h3>\n";
echo "<p><strong>Ubicación:</strong> <code>ConceptController.php:599</code></p>\n";
echo "<pre>// CÓDIGO POTENCIALMENTE VULNERABLE:\n";
echo "\$employee = \$this->employeeModel->find(\$employeeId);\n</pre>\n";

echo "<p><strong>Problema:</strong> Si employee_id no está correctamente validado en el modelo.</p>\n";

echo "<h4>💣 Payload de prueba:</h4>\n";
echo "<pre>";
echo "# En el campo 'employee_id':\n";
echo "1 UNION SELECT password FROM users --\n";
echo "# O:\n";
echo "1; DROP TABLE employees; --\n";
echo "</pre>\n";

echo "<h3>3. 📊 INFORMATION DISCLOSURE</h3>\n";
echo "<p><strong>Ubicación:</strong> <code>ConceptController.php:616</code></p>\n";
echo "<pre>// INFORMACIÓN EXPUESTA:\n";
echo "echo json_encode([\n";
echo "    'variables' => \$calculator->getVariablesColaborador()\n";
echo "]);\n</pre>\n";

echo "<p><strong>Problema:</strong> Expone todas las variables del empleado sin filtrar.</p>\n";

echo "<h3>4. 🔍 DEBUG INFORMATION LEAKAGE</h3>\n";
echo "<p><strong>Ubicación:</strong> <code>PlanillaConceptCalculator.php:255, 262</code></p>\n";
echo "<pre>// LOGS DE DEBUG COMENTADOS (pero presentes):\n";
echo "// error_log(\"DEBUG: Asignación ...\");\n";
echo "// error_log(\"DEBUG: Expresión final ...\");\n</pre>\n";

echo "<p><strong>Problema:</strong> Información de debug que podría activarse accidentalmente.</p>\n";

echo "<h2>🛡️ RECOMENDACIONES DE SEGURIDAD:</h2>\n\n";

echo "<h3>1. Eliminar eval() completamente</h3>\n";
echo "<pre>";
echo "// REEMPLAZAR eval() con un parser matemático seguro\n";
echo "// Usar librerías como:\n";
echo "// - nxp/math-executor (ya incluida en composer)\n";
echo "// - brick/math\n";
echo "// - o implementar parser recursivo descendente\n";
echo "</pre>\n";

echo "<h3>2. Validación estricta de parámetros</h3>\n";
echo "<pre>";
echo "// En ConceptController::testFormula()\n";
echo "\$employeeId = filter_var(\$_POST['employee_id'], FILTER_VALIDATE_INT);\n";
echo "if (!\$employeeId || \$employeeId <= 0) {\n";
echo "    throw new \\Exception('ID de empleado inválido');\n";
echo "}\n\n";
echo "// Validar formato de fórmula\n";
echo "if (!preg_match('/^[a-zA-Z0-9_+\\-*\\/()\\s\\.=]+\$/', \$formula)) {\n";
echo "    throw new \\Exception('Fórmula contiene caracteres no válidos');\n";
echo "}\n";
echo "</pre>\n";

echo "<h3>3. Filtrar variables expuestas</h3>\n";
echo "<pre>";
echo "// Solo exponer variables seguras\n";
echo "\$safeVariables = array_intersect_key(\n";
echo "    \$calculator->getVariablesColaborador(),\n";
echo "    array_flip(['SUELDO', 'GASTOS_REP', 'ANTIGUEDAD_DIAS'])\n";
echo ");\n";
echo "</pre>\n";

echo "<h3>4. Implementar rate limiting</h3>\n";
echo "<pre>";
echo "// Limitar intentos de test de fórmulas por IP/usuario\n";
echo "// Usar AuthMiddleware::rateLimit() existente\n";
echo "</pre>\n";

echo "<h2>🧪 CÓMO PROBAR ESTAS VULNERABILIDADES:</h2>\n\n";

echo "<ol>\n";
echo "<li><strong>Navegar a:</strong> $base_url/panel/concepts/create</li>\n";
echo "<li><strong>Crear concepto</strong> con fórmula maliciosa</li>\n";
echo "<li><strong>Usar botón 'Probar Fórmula'</strong> en el formulario</li>\n";
echo "<li><strong>Insertar payloads</strong> en los campos formula y employee_id</li>\n";
echo "<li><strong>Observar respuestas</strong> en Network tab de DevTools</li>\n";
echo "</ol>\n";

echo "<h2>📱 CÓDIGO PARA DEPURACIÓN SEGURA:</h2>\n";

echo "<p>Para agregar depuración sin comprometer seguridad:</p>\n";
echo "<pre>";
echo "// En PlanillaConceptCalculator.php\n";
echo "public function addDebugInfo(): array {\n";
echo "    if (!defined('DEBUG_MODE') || !DEBUG_MODE) {\n";
echo "        return [];\n";
echo "    }\n";
echo "    \n";
echo "    return [\n";
echo "        'variables_count' => count(\$this->variablesColaborador),\n";
echo "        'last_formula_length' => strlen(\$this->lastFormula ?? ''),\n";
echo "        'execution_time' => microtime(true) - \$this->startTime\n";
echo "    ];\n";
echo "}\n";
echo "</pre>\n";

echo "<h2>🔐 CONSULTA SQL SEGURA PARA DEBUGGING:</h2>\n";
echo "<p>Si necesitas debugging, usa consultas preparadas:</p>\n";
echo "<pre>";
echo "// Para debug de empleados\n";
echo "\$sql = \"SELECT id, firstname, lastname, position_id FROM employees WHERE id = ? LIMIT 1\";\n";
echo "\$stmt = \$this->db->prepare(\$sql);\n";
echo "\$stmt->execute([\$employeeId]);\n";
echo "\$employee = \$stmt->fetch(PDO::FETCH_ASSOC);\n";
echo "error_log('DEBUG Employee: ' . json_encode(\$employee));\n";
echo "</pre>\n";

echo "<hr>\n";
echo "<p><strong>⚠️  IMPORTANTE:</strong> Este archivo debe eliminarse en producción.</p>\n";
echo "<p><strong>📝 FECHA:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>