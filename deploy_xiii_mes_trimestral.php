<?php

/**
 * Script de Deploy para XIII Mes Períodos Trimestrales
 * Ejecutar ÚNICAMENTE en ambiente de producción
 *
 * IMPORTANTE: Crear backup completo de BD antes de ejecutar
 */

require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

class XIIIMesTrimestralDeploy
{
    private $db;
    private $connection;
    private $backupFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getConnection();
        $this->backupFile = __DIR__ . "/backup_concepto_" . date('Y_m_d_His') . ".sql";
    }

    public function ejecutarDeploy(): void
    {
        echo "<h1>🚀 Deploy XIII Mes Trimestral</h1>\n";
        echo "<hr>\n";

        try {
            $this->verificarPrerequisitos();
            $this->crearBackupConcepto();
            $this->actualizarConceptoLIQ006();
            $this->verificarDespliegue();

            echo "<div style='background-color: lightgreen; padding: 20px; margin: 20px 0; border-radius: 10px;'>\n";
            echo "<h2>✅ Deploy Completado Exitosamente</h2>\n";
            echo "<p><strong>Backup creado:</strong> {$this->backupFile}</p>\n";
            echo "<p><strong>Concepto LIQ006:</strong> Actualizado con nueva fórmula trimestral</p>\n";
            echo "<p><strong>Estado:</strong> Sistema listo para usar</p>\n";
            echo "</div>\n";

        } catch (Exception $e) {
            $this->manejarError($e);
        }
    }

    private function verificarPrerequisitos(): void
    {
        echo "<h2>🔍 Verificando Prerequisitos</h2>\n";

        // Verificar que existe el concepto LIQ006
        $stmt = $this->connection->prepare("SELECT concepto, descripcion, formula FROM concepto WHERE concepto = 'LIQ006'");
        $stmt->execute();
        $concepto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$concepto) {
            throw new Exception("❌ Concepto LIQ006 no encontrado en la base de datos");
        }

        echo "<div style='background-color: lightblue; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "<h3>📋 Concepto Actual LIQ006</h3>\n";
        echo "<p><strong>Descripción:</strong> {$concepto['descripcion']}</p>\n";
        echo "<p><strong>Fórmula Actual:</strong> <code>{$concepto['formula']}</code></p>\n";
        echo "</div>\n";

        // Verificar que no existe ya el backup
        $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM concepto WHERE concepto = 'LIQ006_OLD'");
        $stmt->execute();
        $backupExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($backupExists) {
            echo "<div style='background-color: lightyellow; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "<h3>⚠️ Backup Existente Detectado</h3>\n";
            echo "<p>Ya existe un concepto LIQ006_OLD. Se mantendrá el backup original.</p>\n";
            echo "</div>\n";
        }

        // Verificar archivos de clases necesarios
        $archivosNecesarios = [
            __DIR__ . '/app/Services/XIIIMesPeriodoTrimestralCalculator.php',
            __DIR__ . '/app/Services/PlanillaConceptCalculator.php'
        ];

        foreach ($archivosNecesarios as $archivo) {
            if (!file_exists($archivo)) {
                throw new Exception("❌ Archivo requerido no encontrado: " . basename($archivo));
            }
        }

        echo "<p style='color: green;'>✅ Todos los prerequisitos verificados correctamente</p>\n";
    }

    private function crearBackupConcepto(): void
    {
        echo "<h2>💾 Creando Backup de Concepto</h2>\n";

        try {
            // Verificar si ya existe backup
            $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM concepto WHERE concepto = 'LIQ006_OLD'");
            $stmt->execute();
            $backupExists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($backupExists) {
                echo "<p style='color: orange;'>⚠️ Backup LIQ006_OLD ya existe, se conservará el original</p>\n";
                return;
            }

            // Obtener concepto actual
            $stmt = $this->connection->prepare("
                SELECT concepto, descripcion, formula, tipo_concepto, activo,
                       created_at, updated_at
                FROM concepto
                WHERE concepto = 'LIQ006'
            ");
            $stmt->execute();
            $conceptoOriginal = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$conceptoOriginal) {
                throw new Exception("No se pudo obtener el concepto LIQ006 para backup");
            }

            // Crear backup en BD
            $stmt = $this->connection->prepare("
                INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, activo, created_at, updated_at)
                VALUES ('LIQ006_OLD', ?, ?, ?, ?, ?, NOW())
            ");

            $descripcionBackup = "[BACKUP] " . $conceptoOriginal['descripcion'];

            $stmt->execute([
                $descripcionBackup,
                $conceptoOriginal['formula'],
                $conceptoOriginal['tipo_concepto'],
                $conceptoOriginal['activo'],
                $conceptoOriginal['created_at']
            ]);

            // Crear archivo de backup SQL
            $sqlBackup = "-- Backup Concepto LIQ006 - " . date('Y-m-d H:i:s') . "\n";
            $sqlBackup .= "-- Restaurar con: UPDATE concepto SET formula = '{$conceptoOriginal['formula']}' WHERE concepto = 'LIQ006';\n\n";
            $sqlBackup .= "INSERT INTO concepto (concepto, descripcion, formula, tipo_concepto, activo, created_at, updated_at) VALUES (\n";
            $sqlBackup .= "  'LIQ006_RESTORE',\n";
            $sqlBackup .= "  " . $this->connection->quote($conceptoOriginal['descripcion']) . ",\n";
            $sqlBackup .= "  " . $this->connection->quote($conceptoOriginal['formula']) . ",\n";
            $sqlBackup .= "  " . $this->connection->quote($conceptoOriginal['tipo_concepto']) . ",\n";
            $sqlBackup .= "  {$conceptoOriginal['activo']},\n";
            $sqlBackup .= "  " . $this->connection->quote($conceptoOriginal['created_at']) . ",\n";
            $sqlBackup .= "  NOW()\n";
            $sqlBackup .= ");\n";

            file_put_contents($this->backupFile, $sqlBackup);

            echo "<p style='color: green;'>✅ Backup creado: LIQ006_OLD en BD y archivo SQL</p>\n";

        } catch (Exception $e) {
            throw new Exception("Error creando backup: " . $e->getMessage());
        }
    }

    private function actualizarConceptoLIQ006(): void
    {
        echo "<h2>🔄 Actualizando Concepto LIQ006</h2>\n";

        try {
            $nuevaFormula = 'ACUMULADOS("SALARIO_BASE", FICHA, INICIO_PERIODO_XIII, FIN_PERIODO_XIII)/4';

            $stmt = $this->connection->prepare("
                UPDATE concepto
                SET formula = ?, updated_at = NOW()
                WHERE concepto = 'LIQ006'
            ");

            $resultado = $stmt->execute([$nuevaFormula]);

            if (!$resultado) {
                throw new Exception("Error ejecutando UPDATE en concepto LIQ006");
            }

            $filasAfectadas = $stmt->rowCount();

            if ($filasAfectadas === 0) {
                throw new Exception("No se actualizó ningún registro. Verifique que existe LIQ006");
            }

            echo "<div style='background-color: lightgreen; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
            echo "<h3>✅ Concepto Actualizado</h3>\n";
            echo "<p><strong>Concepto:</strong> LIQ006</p>\n";
            echo "<p><strong>Nueva Fórmula:</strong> <code>{$nuevaFormula}</code></p>\n";
            echo "<p><strong>Filas Afectadas:</strong> {$filasAfectadas}</p>\n";
            echo "</div>\n";

        } catch (Exception $e) {
            throw new Exception("Error actualizando concepto: " . $e->getMessage());
        }
    }

    private function verificarDespliegue(): void
    {
        echo "<h2>🧪 Verificando Deploy</h2>\n";

        try {
            // Verificar concepto actualizado
            $stmt = $this->connection->prepare("SELECT concepto, formula FROM concepto WHERE concepto = 'LIQ006'");
            $stmt->execute();
            $conceptoActualizado = $stmt->fetch(PDO::FETCH_ASSOC);

            $formulaEsperada = 'ACUMULADOS("SALARIO_BASE", FICHA, INICIO_PERIODO_XIII, FIN_PERIODO_XIII)/4';
            $formulaCorrecta = $conceptoActualizado['formula'] === $formulaEsperada;

            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
            echo "<tr style='background-color: #f0f0f0;'>\n";
            echo "<th>Verificación</th><th>Estado</th><th>Detalle</th>\n";
            echo "</tr>\n";

            // Verificar fórmula actualizada
            $colorFormula = $formulaCorrecta ? 'lightgreen' : 'lightcoral';
            $iconoFormula = $formulaCorrecta ? '✅' : '❌';
            echo "<tr style='background-color: {$colorFormula};'>\n";
            echo "<td>Fórmula LIQ006</td>\n";
            echo "<td>{$iconoFormula}</td>\n";
            echo "<td><code>{$conceptoActualizado['formula']}</code></td>\n";
            echo "</tr>\n";

            // Verificar backup existe
            $stmt = $this->connection->prepare("SELECT COUNT(*) as count FROM concepto WHERE concepto = 'LIQ006_OLD'");
            $stmt->execute();
            $backupExiste = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            $colorBackup = $backupExiste ? 'lightgreen' : 'lightcoral';
            $iconoBackup = $backupExiste ? '✅' : '❌';
            echo "<tr style='background-color: {$colorBackup};'>\n";
            echo "<td>Backup LIQ006_OLD</td>\n";
            echo "<td>{$iconoBackup}</td>\n";
            echo "<td>" . ($backupExiste ? 'Backup creado correctamente' : 'Backup no encontrado') . "</td>\n";
            echo "</tr>\n";

            // Verificar archivo backup
            $archivoExiste = file_exists($this->backupFile);
            $colorArchivo = $archivoExiste ? 'lightgreen' : 'lightcoral';
            $iconoArchivo = $archivoExiste ? '✅' : '❌';
            echo "<tr style='background-color: {$colorArchivo};'>\n";
            echo "<td>Archivo Backup SQL</td>\n";
            echo "<td>{$iconoArchivo}</td>\n";
            echo "<td>" . ($archivoExiste ? basename($this->backupFile) : 'Archivo no creado') . "</td>\n";
            echo "</tr>\n";

            // Verificar clases existen
            $calculatorExists = class_exists('App\\Services\\XIIIMesPeriodoTrimestralCalculator');
            $colorClase = $calculatorExists ? 'lightgreen' : 'lightcoral';
            $iconoClase = $calculatorExists ? '✅' : '❌';
            echo "<tr style='background-color: {$colorClase};'>\n";
            echo "<td>Clase XIIIMesPeriodoTrimestralCalculator</td>\n";
            echo "<td>{$iconoClase}</td>\n";
            echo "<td>" . ($calculatorExists ? 'Clase disponible' : 'Clase no encontrada') . "</td>\n";
            echo "</tr>\n";

            echo "</table>\n";

            if (!$formulaCorrecta || !$backupExiste || !$archivoExiste) {
                throw new Exception("Verificación de deploy falló. Revisar tabla de verificación.");
            }

        } catch (Exception $e) {
            throw new Exception("Error en verificación: " . $e->getMessage());
        }
    }

    private function manejarError(Exception $e): void
    {
        echo "<div style='background-color: lightcoral; padding: 20px; margin: 20px 0; border-radius: 10px;'>\n";
        echo "<h2>❌ Error en Deploy</h2>\n";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>\n";

        echo "<h3>🔧 Instrucciones de Rollback</h3>\n";
        echo "<ol>\n";
        echo "<li>Restaurar desde backup BD: <code>UPDATE concepto SET formula = (SELECT formula FROM concepto WHERE concepto = 'LIQ006_OLD') WHERE concepto = 'LIQ006';</code></li>\n";
        if (file_exists($this->backupFile)) {
            echo "<li>O restaurar desde archivo: <code>{$this->backupFile}</code></li>\n";
        }
        echo "<li>Eliminar backup: <code>DELETE FROM concepto WHERE concepto = 'LIQ006_OLD';</code></li>\n";
        echo "</ol>\n";
        echo "</div>\n";
    }
}

// Ejecutar deploy
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'] ?? __FILE__)) {

    // Confirmación de seguridad
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        echo "<div style='background-color: lightyellow; padding: 20px; margin: 20px; border-radius: 10px;'>\n";
        echo "<h1>⚠️ Confirmación Requerida</h1>\n";
        echo "<p><strong>IMPORTANTE:</strong> Este script modificará la base de datos de producción.</p>\n";
        echo "<p><strong>REQUISITOS ANTES DE CONTINUAR:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>✅ Backup completo de la base de datos creado</li>\n";
        echo "<li>✅ Verificación en ambiente de testing completada</li>\n";
        echo "<li>✅ Autorización para modificar producción</li>\n";
        echo "</ul>\n";
        echo "<p><a href='?confirm=yes' style='background-color: red; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 EJECUTAR DEPLOY</a></p>\n";
        echo "</div>\n";
        exit;
    }

    try {
        $deploy = new XIIIMesTrimestralDeploy();
        $deploy->ejecutarDeploy();
    } catch (Exception $e) {
        echo "<div style='background-color: lightcoral; padding: 20px; margin: 20px; border-radius: 10px;'>\n";
        echo "<h1>❌ Error Crítico en Deploy</h1>\n";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>\n";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
        echo "</div>\n";
    }
}
?>