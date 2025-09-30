<?php
/**
 * Test usando el flujo real del sistema
 */

require_once 'vendor/autoload.php';

echo "=== TEST FLUJO REAL DEL SISTEMA ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Conectar a la BD
    $pdo = new PDO("mysql:host=localhost;dbname=planilla_innova29092025", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener la fórmula ISR desde la BD
    $sql = "SELECT formula FROM concepto WHERE id = 4";
    $stmt = $pdo->query($sql);
    $concept = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$concept) {
        die("ERROR: No se encontró el concepto ISR (ID: 4)\n");
    }

    $formulaFromDB = $concept['formula'];
    echo "FÓRMULA DESDE BD:\n";
    echo "================\n";
    echo $formulaFromDB . "\n\n";

    // Simular datos de empleado
    $empleadoData = [
        'SALARIO' => 2525.00,
        'GASTOS_REPRESENTACION' => 2435.00,
        'CLAVE_SS' => 'E-02'
    ];

    echo "DATOS EMPLEADO:\n";
    echo "==============\n";
    foreach ($empleadoData as $var => $val) {
        echo "$var = $val\n";
    }
    echo "\n";

    // Crear clase simulando PlanillaConceptCalculator
    class SystemCalculatorTest {
        private $variables = [];

        public function setVariables($vars) {
            $this->variables = $vars;
        }

        public function evaluarFormula(string $formula): float {
            return $this->evaluarFormulaSimple($formula);
        }

        private function evaluarFormulaSimple(string $formula): float {
            $variablesLocales = [];
            $lineas = $this->dividirFormulaEnLineas($formula);
            $ultimoResultado = 0;

            echo "LÍNEAS PROCESADAS:\n";
            foreach ($lineas as $i => $linea) {
                echo ($i + 1) . ". $linea\n";
            }
            echo "\n";

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;

                if ($linea === 'RETURN monto') {
                    echo "RETURN detectado, valor: " . ($variablesLocales['monto'] ?? 'NO DEFINIDO') . "\n";
                    return $variablesLocales['monto'] ?? 0;
                }

                if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(.+)$/', $linea, $matches)) {
                    $nombreVariable = $matches[1];
                    $expresion = trim($matches[2]);

                    echo "Procesando: $nombreVariable = $expresion\n";

                    $expresionProcesada = $this->procesarExpresionConVariables($expresion, $variablesLocales);
                    echo "Expresión procesada: $expresionProcesada\n";

                    $valor = $this->evaluarExpresionMatematica($expresionProcesada);
                    $variablesLocales[$nombreVariable] = $valor;
                    $ultimoResultado = $valor;

                    echo "Resultado: $nombreVariable = $valor\n\n";
                }
            }

            return $ultimoResultado;
        }

        private function dividirFormulaEnLineas(string $formula): array {
            $formula = str_replace(["\r\n", "\r"], "\n", $formula);
            $lineas = explode("\n", $formula);
            return array_filter(array_map('trim', $lineas), function($linea) {
                return !empty($linea);
            });
        }

        private function procesarExpresionConVariables(string $expresion, array $variablesLocales): string {
            // 1. Reemplazar variables globales
            $expresionProcesada = $this->reemplazarVariables($expresion);

            // 2. Procesar funciones
            $expresionProcesada = $this->procesarFunciones($expresionProcesada);

            // 3. Reemplazar variables locales
            foreach ($variablesLocales as $varNombre => $varValor) {
                $expresionProcesada = preg_replace('/\b' . preg_quote($varNombre, '/') . '\b/', (string)$varValor, $expresionProcesada);
            }

            return $expresionProcesada;
        }

        private function reemplazarVariables(string $formula): string {
            foreach ($this->variables as $variable => $valor) {
                $formula = str_replace($variable, (string)$valor, $formula);
            }
            return $formula;
        }

        private function procesarFunciones(string $formula): string {
            // Procesar LEFT() primero
            $formula = preg_replace_callback('/LEFT\(([^,]+),\s*(\d+)\)/', function($matches) {
                $texto = trim($matches[1]);
                $longitud = (int)trim($matches[2]);

                $textoResuelto = $this->reemplazarVariables($texto);
                $textoResuelto = trim($textoResuelto, '"\'');
                return '"' . substr($textoResuelto, 0, $longitud) . '"';
            }, $formula);

            // Procesar SI()
            $formula = $this->procesarFuncionSI($formula);

            return $formula;
        }

        private function procesarFuncionSI(string $formula): string {
            while (preg_match('/SI\(/', $formula)) {
                $formula = preg_replace_callback('/SI\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', function($matches) {
                    $contenido = $matches[1];
                    $parametros = $this->dividirParametrosSI($contenido);

                    if (count($parametros) !== 3) {
                        return '0';
                    }

                    $condicion = trim($parametros[0]);
                    $valorVerdadero = trim($parametros[1]);
                    $valorFalso = trim($parametros[2]);

                    $condicionResult = $this->evaluarCondicion($condicion);
                    return $condicionResult ? $valorVerdadero : $valorFalso;
                }, $formula);
            }
            return $formula;
        }

        private function dividirParametrosSI(string $contenido): array {
            $parametros = [];
            $parametroActual = '';
            $nivelParentesis = 0;
            $enComillas = false;
            $caracterComilla = '';

            for ($i = 0; $i < strlen($contenido); $i++) {
                $char = $contenido[$i];

                if (($char === '"' || $char === "'") && !$enComillas) {
                    $enComillas = true;
                    $caracterComilla = $char;
                    $parametroActual .= $char;
                } elseif ($char === $caracterComilla && $enComillas) {
                    $enComillas = false;
                    $caracterComilla = '';
                    $parametroActual .= $char;
                } elseif ($enComillas) {
                    $parametroActual .= $char;
                } elseif ($char === '(') {
                    $nivelParentesis++;
                    $parametroActual .= $char;
                } elseif ($char === ')') {
                    $nivelParentesis--;
                    $parametroActual .= $char;
                } elseif ($char === ',' && $nivelParentesis === 0) {
                    $parametros[] = $parametroActual;
                    $parametroActual = '';
                } else {
                    $parametroActual .= $char;
                }
            }

            if ($parametroActual !== '') {
                $parametros[] = $parametroActual;
            }

            return $parametros;
        }

        private function evaluarCondicion(string $condicion): bool {
            $condicion = $this->reemplazarVariables($condicion);

            // Comparaciones de strings
            if (preg_match('/["\']([^"\']*)["\']\s*=\s*["\']([^"\']*)["\']/', $condicion, $matches)) {
                return $matches[1] === $matches[2];
            }

            // Comparaciones numéricas
            if (preg_match('/([0-9.]+)\s*([><=]+)\s*([0-9.]+)/', $condicion, $matches)) {
                $valor1 = (float)$matches[1];
                $operador = $matches[2];
                $valor2 = (float)$matches[3];

                switch ($operador) {
                    case '>': return $valor1 > $valor2;
                    case '<': return $valor1 < $valor2;
                    case '>=': return $valor1 >= $valor2;
                    case '<=': return $valor1 <= $valor2;
                    case '==': case '=': return $valor1 == $valor2;
                    default: return false;
                }
            }

            return false;
        }

        private function evaluarExpresionMatematica(string $expresion): float {
            try {
                $result = eval("return $expresion;");
                return (float)$result;
            } catch (Exception $e) {
                echo "ERROR evaluando '$expresion': " . $e->getMessage() . "\n";
                return 0;
            }
        }
    }

    $calculator = new SystemCalculatorTest();
    $calculator->setVariables($empleadoData);

    $resultado = $calculator->evaluarFormula($formulaFromDB);

    echo "=== RESULTADO FINAL ===\n";
    echo "ISR Quincenal: B/." . number_format($resultado, 2) . "\n";
    echo "Esperado: B/.121.30\n";

    $diferencia = abs($resultado - 121.30);
    echo "Diferencia: B/." . number_format($diferencia, 2);

    if ($diferencia < 0.10) {
        echo " ✅ CORRECTO\n";
    } else {
        echo " ❌ INCORRECTO\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN TEST ===\n";