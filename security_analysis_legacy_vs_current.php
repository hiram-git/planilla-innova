<?php
/**
 * 🔍 ANÁLISIS COMPARATIVO: LEGACY (SEGURO) vs ACTUAL (VULNERABLE)
 *
 * Comparación detallada entre la implementación legacy con MathExecutor
 * y la implementación actual con eval() para restaurar la seguridad
 */

echo "<h1>🔍 ANÁLISIS COMPARATIVO: LEGACY vs ACTUAL</h1>\n";

echo "<h2>📊 COMPARACIÓN GENERAL:</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr style='background-color: #f0f0f0;'>\n";
echo "<th>Aspecto</th><th>LEGACY (Seguro)</th><th>ACTUAL (Vulnerable)</th><th>Impacto</th>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Motor de Evaluación</strong></td>\n";
echo "<td>✅ <code>NXP\\MathExecutor</code></td>\n";
echo "<td>❌ <code>eval()</code> directo</td>\n";
echo "<td>🚨 <strong>CRÍTICO</strong></td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Validación Variables</strong></td>\n";
echo "<td>✅ <code>setVarValidationHandler()</code></td>\n";
echo "<td>❌ Sin validación</td>\n";
echo "<td>⚠️ Alto</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Funciones Personalizadas</strong></td>\n";
echo "<td>✅ <code>addFunction()</code> seguro</td>\n";
echo "<td>❌ Regex manual vulnerable</td>\n";
echo "<td>🚨 <strong>CRÍTICO</strong></td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Prevención Dependencias Cíclicas</strong></td>\n";
echo "<td>✅ Array <code>\$evaluando</code></td>\n";
echo "<td>❌ Sin protección</td>\n";
echo "<td>⚠️ Medio</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Manejo de Variables No Encontradas</strong></td>\n";
echo "<td>✅ <code>setVarNotFoundHandler()</code></td>\n";
echo "<td>❌ Errores silenciosos</td>\n";
echo "<td>⚠️ Medio</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Soporte Multilínea</strong></td>\n";
echo "<td>❌ Solo fórmulas simples</td>\n";
echo "<td>✅ Variables locales + contexto</td>\n";
echo "<td>✅ Funcionalidad</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>INIPERIODO/FINPERIODO</strong></td>\n";
echo "<td>❌ No implementado</td>\n";
echo "<td>✅ Fechas dinámicas</td>\n";
echo "<td>✅ Funcionalidad</td>\n";
echo "</tr>\n";

echo "<tr>\n";
echo "<td><strong>Función ACUMULADOS</strong></td>\n";
echo "<td>❌ No implementado</td>\n";
echo "<td>✅ Con preservación strings</td>\n";
echo "<td>✅ Funcionalidad</td>\n";
echo "</tr>\n";

echo "</table>\n\n";

echo "<h2>🔧 ANÁLISIS DETALLADO DE IMPLEMENTACIONES:</h2>\n\n";

echo "<h3>1. 🛡️ LEGACY: Implementación Segura</h3>\n";
echo "<h4>✅ Fortalezas:</h4>\n";
echo "<pre>";
echo "// Validación estricta de variables\n";
echo "\$this->executor->setVarValidationHandler(function (string \$nombre, \$valor) {\n";
echo "    if (!is_numeric(\$valor) and \$nombre !== 'FICHA') {\n";
echo "        throw new MathExecutorException(\"La variable '\$nombre' debe ser numérica\");\n";
echo "    }\n";
echo "});\n\n";

echo "// Funciones personalizadas seguras\n";
echo "\$this->executor->addFunction('SI', function (\$condicion, \$valorSiVerdadero, \$valorSiFalso) {\n";
echo "    return \$condicion ? \$valorSiVerdadero : \$valorSiFalso;\n";
echo "}, 3);\n\n";

echo "// Prevención dependencias cíclicas\n";
echo "if (in_array(\$nombre, \$this->evaluando)) {\n";
echo "    throw new MathExecutorException(\"Dependencia cíclica detectada en '\$nombre'\");\n";
echo "}\n";
echo "</pre>\n";

echo "<h4>❌ Limitaciones Legacy:</h4>\n";
echo "<ul>\n";
echo "<li>No soporte para fórmulas multilínea</li>\n";
echo "<li>Sin variables INIPERIODO/FINPERIODO dinámicas</li>\n";
echo "<li>Función ACUMULADOS no implementada</li>\n";
echo "<li>Sin manejo de variables locales en contexto</li>\n";
echo "</ul>\n";

echo "<h3>2. ⚠️ ACTUAL: Implementación Vulnerable pero Funcional</h3>\n";
echo "<h4>✅ Funcionalidades Avanzadas:</h4>\n";
echo "<pre>";
echo "// Soporte multilínea con variables locales\n";
echo "foreach (\$lineas as \$linea) {\n";
echo "    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\\s*=\\s*(.+)\$/', \$linea, \$matches)) {\n";
echo "        \$nombreVariable = \$matches[1];\n";
echo "        \$expresion = trim(\$matches[2]);\n";
echo "        \$valor = \$this->evaluarExpresionMatematica(\$expresionProcesada);\n";
echo "        \$variablesLocales[\$nombreVariable] = \$valor;\n";
echo "    }\n";
echo "}\n\n";

echo "// Variables dinámicas de fecha\n";
echo "\$variablesEspeciales = [\n";
echo "    'INIPERIODO' => \$this->fechasActuales['fecha_desde'] ?? null,\n";
echo "    'FINPERIODO' => \$this->fechasActuales['fecha_hasta'] ?? null\n";
echo "];\n\n";

echo "// Función ACUMULADOS avanzada\n";
echo "\$this->calcularAcumulados(\$conceptosArray, \$fechaDesde, \$fechaHasta);\n";
echo "</pre>\n";

echo "<h4>❌ Vulnerabilidades Críticas:</h4>\n";
echo "<pre>";
echo "// VULNERABLE: eval() directo\n";
echo "try {\n";
echo "    \$result = eval(\"return \$expresionLimpia;\");  // 🚨 CODE INJECTION\n";
echo "    return (float)\$result;\n";
echo "} catch (\\ParseError \$e) {\n";
echo "    error_log(\"Error de sintaxis\");\n";
echo "    return 0;\n";
echo "}\n";
echo "</pre>\n";

echo "<h2>🎯 SOLUCIÓN HÍBRIDA PROPUESTA:</h2>\n\n";

echo "<h3>Integrar las mejores características de ambas implementaciones:</h3>\n";

echo "<h4>1. 🛡️ Base Segura (MathExecutor)</h4>\n";
echo "<pre>";
echo "use NXP\\MathExecutor;\n";
echo "use NXP\\Exception\\MathExecutorException;\n\n";

echo "class PlanillaConceptCalculatorSecure {\n";
echo "    private MathExecutor \$executor;\n";
echo "    private array \$variablesLocales = [];\n";
echo "    private array \$fechasActuales = [];\n";
echo "    \n";
echo "    public function __construct() {\n";
echo "        \$this->executor = new MathExecutor();\n";
echo "        \$this->configurarFuncionesSeguras();\n";
echo "        \$this->configurarValidaciones();\n";
echo "    }\n";
echo "}\n";
echo "</pre>\n";

echo "<h4>2. 🔧 Funciones Personalizadas Seguras</h4>\n";
echo "<pre>";
echo "private function configurarFuncionesSeguras(): void {\n";
echo "    // Función ACUMULADOS segura\n";
echo "    \$this->executor->addFunction('ACUMULADOS', function(\$conceptos, \$fechaDesde, \$fechaHasta) {\n";
echo "        return \$this->calcularAcumuladosSeguro(\$conceptos, \$fechaDesde, \$fechaHasta);\n";
echo "    }, 3);\n";
echo "    \n";
echo "    // Función SI mejorada\n";
echo "    \$this->executor->addFunction('SI', function(\$condicion, \$valorTrue, \$valorFalse) {\n";
echo "        return \$condicion ? \$valorTrue : \$valorFalse;\n";
echo "    }, 3);\n";
echo "    \n";
echo "    // Función ACREEDOR segura\n";
echo "    \$this->executor->addFunction('ACREEDOR', function(\$empleado, \$id_deduction) {\n";
echo "        return \$this->calcularMontoAcreedorSeguro(\$empleado, \$id_deduction);\n";
echo "    }, 2);\n";
echo "}\n";
echo "</pre>\n";

echo "<h4>3. 📝 Soporte Multilínea Seguro</h4>\n";
echo "<pre>";
echo "public function evaluarFormulaMultilinea(string \$formula): float {\n";
echo "    \$lineas = \$this->dividirFormulaEnLineas(\$formula);\n";
echo "    \$ultimoResultado = 0;\n";
echo "    \n";
echo "    foreach (\$lineas as \$linea) {\n";
echo "        if (\$this->esAsignacion(\$linea)) {\n";
echo "            \$this->procesarAsignacionSegura(\$linea);\n";
echo "        } else {\n";
echo "            // Evaluar expresión final con MathExecutor\n";
echo "            \$ultimoResultado = \$this->executor->execute(\$linea);\n";
echo "        }\n";
echo "    }\n";
echo "    \n";
echo "    return \$ultimoResultado;\n";
echo "}\n\n";

echo "private function procesarAsignacionSegura(string \$linea): void {\n";
echo "    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\\s*=\\s*(.+)\$/', \$linea, \$matches);\n";
echo "    \$nombreVariable = \$matches[1];\n";
echo "    \$expresion = \$matches[2];\n";
echo "    \n";
echo "    // Validar nombre de variable\n";
echo "    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\$/', \$nombreVariable)) {\n";
echo "        throw new MathExecutorException('Nombre de variable inválido');\n";
echo "    }\n";
echo "    \n";
echo "    // Evaluar expresión de forma segura\n";
echo "    \$valor = \$this->executor->execute(\$expresion);\n";
echo "    \n";
echo "    // Almacenar variable local\n";
echo "    \$this->executor->setVar(\$nombreVariable, \$valor);\n";
echo "}\n";
echo "</pre>\n";

echo "<h4>4. 📅 Variables Dinámicas Seguras</h4>\n";
echo "<pre>";
echo "public function establecerFechasPlanilla(string \$fechaDesde, string \$fechaHasta): void {\n";
echo "    // Validar formato de fechas\n";
echo "    if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}\$/', \$fechaDesde) || \n";
echo "        !preg_match('/^\\d{4}-\\d{2}-\\d{2}\$/', \$fechaHasta)) {\n";
echo "        throw new MathExecutorException('Formato de fecha inválido');\n";
echo "    }\n";
echo "    \n";
echo "    // Establecer variables de fecha de forma segura\n";
echo "    \$this->executor->setVar('INIPERIODO', \$fechaDesde);\n";
echo "    \$this->executor->setVar('FINPERIODO', \$fechaHasta);\n";
echo "    \n";
echo "    \$this->fechasActuales = [\n";
echo "        'fecha_desde' => \$fechaDesde,\n";
echo "        'fecha_hasta' => \$fechaHasta\n";
echo "    ];\n";
echo "}\n";
echo "</pre>\n";

echo "<h2>📋 PLAN DE MIGRACIÓN DETALLADO:</h2>\n\n";

echo "<h3>Fase 1: Preparación (1-2 días)</h3>\n";
echo "<ol>\n";
echo "<li>Crear backup completo del sistema actual</li>\n";
echo "<li>Documentar todas las fórmulas existentes en BD</li>\n";
echo "<li>Crear tests para fórmulas críticas</li>\n";
echo "</ol>\n";

echo "<h3>Fase 2: Implementación Base (2-3 días)</h3>\n";
echo "<ol>\n";
echo "<li>Crear PlanillaConceptCalculatorSecure con MathExecutor</li>\n";
echo "<li>Migrar funciones básicas (SI, SUMA, PROMEDIO)</li>\n";
echo "<li>Implementar validaciones estrictas</li>\n";
echo "</ol>\n";

echo "<h3>Fase 3: Funciones Avanzadas (3-4 días)</h3>\n";
echo "<ol>\n";
echo "<li>Implementar función ACUMULADOS segura</li>\n";
echo "<li>Agregar soporte para variables dinámicas</li>\n";
echo "<li>Crear sistema de variables locales seguro</li>\n";
echo "</ol>\n";

echo "<h3>Fase 4: Testing y Validación (2-3 días)</h3>\n";
echo "<ol>\n";
echo "<li>Probar todas las fórmulas existentes</li>\n";
echo "<li>Validar cálculos de planillas completas</li>\n";
echo "<li>Verificar performance y seguridad</li>\n";
echo "</ol>\n";

echo "<h3>Fase 5: Despliegue (1 día)</h3>\n";
echo "<ol>\n";
echo "<li>Reemplazar implementación actual</li>\n";
echo "<li>Monitorear en producción</li>\n";
echo "<li>Rollback plan si es necesario</li>\n";
echo "</ol>\n";

echo "<h2>🚨 CÓDIGO DE MIGRACIÓN INMEDIATA:</h2>\n";

echo "<p>Para implementación inmediata, aquí está el código base:</p>\n";
echo "<pre>";
echo "<?php\n";
echo "// PlanillaConceptCalculatorSecure.php\n";
echo "namespace App\\Services;\n\n";

echo "use NXP\\MathExecutor;\n";
echo "use NXP\\Exception\\MathExecutorException;\n";
echo "use App\\Core\\Database;\n";
echo "use PDO;\n\n";

echo "class PlanillaConceptCalculatorSecure {\n";
echo "    private MathExecutor \$executor;\n";
echo "    private PDO \$db;\n";
echo "    private array \$conceptos = [];\n";
echo "    private array \$variablesColaborador = [];\n";
echo "    private array \$fechasActuales = [];\n";
echo "    \n";
echo "    public function __construct() {\n";
echo "        \$this->db = Database::getInstance()->getConnection();\n";
echo "        \$this->executor = new MathExecutor();\n";
echo "        \$this->configurarSistemaSeguro();\n";
echo "        \$this->cargarConceptos();\n";
echo "    }\n";
echo "    \n";
echo "    private function configurarSistemaSeguro(): void {\n";
echo "        // Configurar validaciones, funciones y handlers seguros\n";
echo "        \$this->configurarValidaciones();\n";
echo "        \$this->configurarFuncionesPersonalizadas();\n";
echo "        \$this->configurarManejadorVariables();\n";
echo "    }\n";
echo "    \n";
echo "    // ... resto de la implementación segura\n";
echo "}\n";
echo "</pre>\n";

echo "<hr>\n";
echo "<p><strong>🎯 CONCLUSIÓN:</strong> La migración es crítica y urgente, pero factible.</p>\n";
echo "<p><strong>⏱️ TIEMPO ESTIMADO:</strong> 10-12 días laborales</p>\n";
echo "<p><strong>🔒 RESULTADO:</strong> Sistema seguro + todas las funcionalidades actuales</p>\n";
echo "<p><strong>📝 FECHA:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
?>