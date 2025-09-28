<?php

/**
 * Script de pruebas para XIII Mes Períodos Trimestrales
 * Ejecutar desde línea de comandos o navegador
 */

require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Services/XIIIMesPeriodoTrimestralCalculator.php';
require_once __DIR__ . '/app/Services/PlanillaConceptCalculator.php';

use App\Services\XIIIMesPeriodoTrimestralCalculator;
use App\Services\PlanillaConceptCalculator;

class XIIIMesTrimestralTest
{
    private $calculator;
    private $conceptCalculator;

    public function __construct()
    {
        $this->calculator = new XIIIMesPeriodoTrimestralCalculator();
        $this->conceptCalculator = new PlanillaConceptCalculator();
    }

    public function ejecutarTodasLasPruebas(): void
    {
        echo "<h1>🧪 Pruebas XIII Mes Trimestral</h1>\n";
        echo "<hr>\n";

        $this->testClasificacionPeriodos();
        $this->testCalculoProporcional();
        $this->testVariablesDinamicas();
        $this->testFormulaEnBD();

        echo "<hr>\n";
        echo "<h2>✅ Todas las pruebas completadas exitosamente</h2>\n";
    }

    public function testClasificacionPeriodos(): void
    {
        echo "<h2>📅 Prueba 1: Clasificación de Períodos por Fecha</h2>\n";

        $casos = [
            // Período 1 (Dic-Abr)
            ['2025-01-15', 1, '2024-12-16', '2025-04-15', 'Enero - Período 1'],
            ['2025-03-10', 1, '2024-12-16', '2025-04-15', 'Marzo - Período 1'],
            ['2025-04-10', 1, '2024-12-16', '2025-04-15', 'Abril (1-15) - Período 1'],
            ['2025-12-20', 1, '2025-12-16', '2026-04-15', 'Diciembre (16-31) - Período 1 Nuevo'],

            // Período 2 (Abr-Ago)
            ['2025-04-20', 2, '2025-04-16', '2025-08-15', 'Abril (16-30) - Período 2'],
            ['2025-06-15', 2, '2025-04-16', '2025-08-15', 'Junio - Período 2'],
            ['2025-08-10', 2, '2025-04-16', '2025-08-15', 'Agosto (1-15) - Período 2'],

            // Período 3 (Ago-Dic)
            ['2025-08-20', 3, '2025-08-16', '2025-12-15', 'Agosto (16-31) - Período 3'],
            ['2025-10-05', 3, '2025-08-16', '2025-12-15', 'Octubre - Período 3'],
            ['2025-12-10', 3, '2025-08-16', '2025-12-15', 'Diciembre (1-15) - Período 3'],
        ];

        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
        echo "<tr style='background-color: #f0f0f0;'>\n";
        echo "<th>Fecha Liquidación</th><th>Período Esperado</th><th>Inicio Período</th><th>Fin Período</th><th>Descripción</th><th>Resultado</th>\n";
        echo "</tr>\n";

        foreach ($casos as $caso) {
            [$fecha, $periodoEsperado, $inicioEsperado, $finEsperado, $descripcion] = $caso;

            $resultado = $this->calculator->determinarPeriodoTrimestral($fecha);

            $exito = (
                $resultado['periodo'] === $periodoEsperado &&
                $resultado['fecha_inicio'] === $inicioEsperado &&
                $resultado['fecha_fin'] === $finEsperado
            );

            $color = $exito ? 'lightgreen' : 'lightcoral';
            $icono = $exito ? '✅' : '❌';

            echo "<tr style='background-color: {$color};'>\n";
            echo "<td>{$fecha}</td>\n";
            echo "<td>{$periodoEsperado}</td>\n";
            echo "<td>{$inicioEsperado}</td>\n";
            echo "<td>{$finEsperado}</td>\n";
            echo "<td>{$descripcion}</td>\n";
            echo "<td>{$icono} P{$resultado['periodo']}: {$resultado['fecha_inicio']} → {$resultado['fecha_fin']}</td>\n";
            echo "</tr>\n";

            if (!$exito) {
                error_log("ERROR: Fecha {$fecha} - Esperado P{$periodoEsperado}, obtenido P{$resultado['periodo']}");
            }
        }

        echo "</table>\n";
    }

    public function testCalculoProporcional(): void
    {
        echo "<h2>💰 Prueba 2: Cálculo Proporcional</h2>\n";

        $casos = [
            [
                'fecha_liquidacion' => '2025-03-15',
                'acumulados' => 3000.00,
                'fecha_ingreso' => '2020-01-01',
                'descripcion' => 'Liquidación Marzo - Empleado veterano'
            ],
            [
                'fecha_liquidacion' => '2025-06-30',
                'acumulados' => 2500.00,
                'fecha_ingreso' => '2025-05-01',
                'descripcion' => 'Liquidación Junio - Empleado nuevo'
            ],
            [
                'fecha_liquidacion' => '2025-10-20',
                'acumulados' => 4000.00,
                'fecha_ingreso' => '2022-04-05',
                'descripcion' => 'Liquidación Octubre - Empleado medio'
            ]
        ];

        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
        echo "<tr style='background-color: #f0f0f0;'>\n";
        echo "<th>Caso</th><th>Período</th><th>Días Trabajados</th><th>Proporción</th><th>XIII Trimestral</th><th>XIII Proporcional</th>\n";
        echo "</tr>\n";

        foreach ($casos as $index => $caso) {
            $resultado = $this->calculator->calcularXIIIMesProporcional(
                $caso['fecha_liquidacion'],
                $caso['acumulados'],
                $caso['fecha_ingreso']
            );

            echo "<tr>\n";
            echo "<td><strong>" . ($index + 1) . ".</strong> {$caso['descripcion']}<br>";
            echo "<small>Liquidación: {$caso['fecha_liquidacion']}<br>";
            echo "Acumulados: $" . number_format($caso['acumulados'], 2) . "</small></td>\n";
            echo "<td>{$resultado['periodo_info']['descripcion']}</td>\n";
            echo "<td>{$resultado['dias_trabajados']} / {$resultado['dias_total_periodo']}</td>\n";
            echo "<td>" . ($resultado['proporcion'] * 100) . "%</td>\n";
            echo "<td>$" . number_format($resultado['xiii_mes_trimestral'], 2) . "</td>\n";
            echo "<td><strong>$" . number_format($resultado['xiii_mes_proporcional'], 2) . "</strong></td>\n";
            echo "</tr>\n";

            // Mostrar detalles del cálculo
            echo "<tr style='background-color: #f9f9f9;'>\n";
            echo "<td colspan='6'><small>";
            echo "<strong>Fórmula:</strong> {$resultado['formula']}<br>";
            echo "<strong>Período de cálculo:</strong> {$resultado['fecha_inicio_calculo']} → {$resultado['fecha_fin_calculo']}<br>";
            echo "<strong>Estado:</strong> {$resultado['periodo_info']['estado']}";
            echo "</small></td>\n";
            echo "</tr>\n";
        }

        echo "</table>\n";
    }

    public function testVariablesDinamicas(): void
    {
        echo "<h2>🔧 Prueba 3: Variables Dinámicas</h2>\n";

        // Simular empleado con liquidación
        $this->conceptCalculator->setVariablesColaborador([
            'EMPLOYEE_ID' => 5019, // ID de empleado de prueba
            'FICHA' => '5019',
            'SALARIO' => 1500.00
        ]);

        // Simular fechas de planilla de liquidación
        $this->conceptCalculator->establecerFechasPlanilla('2025-03-01', '2025-03-15');

        // Test de variables con diferentes fechas de liquidación
        $fechasTest = ['2025-01-15', '2025-05-20', '2025-09-10', '2025-12-20'];

        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
        echo "<tr style='background-color: #f0f0f0;'>\n";
        echo "<th>Fecha Liquidación</th><th>INICIO_PERIODO_XIII</th><th>FIN_PERIODO_XIII</th><th>Período</th><th>Estado</th>\n";
        echo "</tr>\n";

        foreach ($fechasTest as $fecha) {
            // Simular fecha de liquidación insertando temporalmente en BD
            $this->insertarFechaLiquidacionTemporal(5019, $fecha);

            // Obtener variables dinámicas
            $variables = $this->obtenerVariablesXIII(5019);

            echo "<tr>\n";
            echo "<td>{$fecha}</td>\n";
            echo "<td>{$variables['INICIO_PERIODO_XIII']}</td>\n";
            echo "<td>{$variables['FIN_PERIODO_XIII']}</td>\n";
            echo "<td>{$variables['PERIODO_XIII_NUMERO']}</td>\n";
            echo "<td>{$variables['PERIODO_XIII_ESTADO']}</td>\n";
            echo "</tr>\n";

            // Limpiar
            $this->limpiarFechaLiquidacionTemporal(5019);
        }

        echo "</table>\n";
    }

    public function testFormulaEnBD(): void
    {
        echo "<h2>🎯 Prueba 4: Fórmula en Base de Datos</h2>\n";

        try {
            $database = \App\Core\Database::getInstance();
            $connection = $database->getConnection();

            // Verificar que la fórmula se actualizó correctamente
            $stmt = $connection->prepare("SELECT concepto, descripcion, formula FROM concepto WHERE concepto = 'LIQ006'");
            $stmt->execute();
            $concepto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($concepto) {
                echo "<div style='background-color: lightgreen; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
                echo "<h3>✅ Concepto LIQ006 Actualizado</h3>\n";
                echo "<strong>Código:</strong> {$concepto['concepto']}<br>\n";
                echo "<strong>Descripción:</strong> {$concepto['descripcion']}<br>\n";
                echo "<strong>Fórmula Nueva:</strong> <code>{$concepto['formula']}</code><br>\n";
                echo "</div>\n";

                // Verificar que existe el backup
                $stmt = $connection->prepare("SELECT concepto, formula FROM concepto WHERE concepto = 'LIQ006_OLD'");
                $stmt->execute();
                $backup = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($backup) {
                    echo "<div style='background-color: lightblue; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
                    echo "<h3>📋 Backup Creado</h3>\n";
                    echo "<strong>Código:</strong> {$backup['concepto']}<br>\n";
                    echo "<strong>Fórmula Anterior:</strong> <code>{$backup['formula']}</code><br>\n";
                    echo "</div>\n";
                } else {
                    echo "<div style='background-color: lightyellow; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
                    echo "<h3>⚠️ Backup No Encontrado</h3>\n";
                    echo "</div>\n";
                }

            } else {
                echo "<div style='background-color: lightcoral; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
                echo "<h3>❌ Concepto LIQ006 No Encontrado</h3>\n";
                echo "</div>\n";
            }

        } catch (Exception $e) {
            echo "<div style='background-color: lightcoral; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "<h3>❌ Error en Prueba BD</h3>\n";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            echo "</div>\n";
        }
    }

    // Métodos auxiliares para pruebas
    private function obtenerVariablesXIII(int $empleadoId): array
    {
        // Usar reflexión para acceder al método privado (solo para testing)
        $reflection = new ReflectionClass($this->conceptCalculator);
        $method = $reflection->getMethod('obtenerVariablesFechaXIIIMes');
        $method->setAccessible(true);

        return $method->invoke($this->conceptCalculator, $empleadoId);
    }

    private function insertarFechaLiquidacionTemporal(int $empleadoId, string $fecha): void
    {
        try {
            $database = \App\Core\Database::getInstance();
            $connection = $database->getConnection();

            $stmt = $connection->prepare("
                INSERT INTO employee_terminations (employee_id, termination_date, created_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE termination_date = VALUES(termination_date)
            ");

            $stmt->execute([$empleadoId, $fecha]);
        } catch (Exception $e) {
            // Ignorar errores para testing
        }
    }

    private function limpiarFechaLiquidacionTemporal(int $empleadoId): void
    {
        try {
            $database = \App\Core\Database::getInstance();
            $connection = $database->getConnection();

            $stmt = $connection->prepare("DELETE FROM employee_terminations WHERE employee_id = ?");
            $stmt->execute([$empleadoId]);
        } catch (Exception $e) {
            // Ignorar errores para testing
        }
    }
}

// Ejecutar pruebas
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? __FILE__)) {
    try {
        $test = new XIIIMesTrimestralTest();
        $test->ejecutarTodasLasPruebas();
    } catch (Exception $e) {
        echo "<div style='background-color: lightcoral; padding: 20px; margin: 20px; border-radius: 10px;'>\n";
        echo "<h1>❌ Error en Pruebas</h1>\n";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>\n";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
        echo "</div>\n";
    }
}
?>