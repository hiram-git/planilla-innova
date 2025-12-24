<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;
use RuntimeException;

/**
 * SqlImporter - Importador robusto de archivos SQL con parser seguro
 *
 * Características:
 * - Parser que maneja strings con punto y coma
 * - Transacciones atómicas con rollback automático
 * - Logging detallado statement por statement
 * - Sistema de reemplazos para customización
 * - Manejo de comentarios SQL
 *
 * @package App\Core
 * @version 1.0.0
 * @author Sistema Planillas MVC
 */
class SqlImporter
{
    private PDO $connection;
    private array $log = [];
    private bool $inTransaction = false;
    private bool $useTransactions = true;

    /**
     * Constructor
     *
     * @param PDO $connection Conexión PDO activa
     * @param bool $useTransactions Si debe usar transacciones (default: true)
     */
    public function __construct(PDO $connection, bool $useTransactions = true)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Forzar buffered queries para evitar error "Cannot execute queries while other unbuffered queries are active"
        $this->connection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->useTransactions = $useTransactions;
    }

    /**
     * Importar archivo SQL con opción de reemplazos
     *
     * @param string $filePath Ruta absoluta al archivo SQL
     * @param array $replacements Array asociativo ['buscar' => 'reemplazar']
     * @return bool True si la importación fue exitosa
     * @throws RuntimeException Si el archivo no existe o hay error en ejecución
     */
    public function importFile(string $filePath, array $replacements = []): bool
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("SQL file not found: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new RuntimeException("SQL file is not readable: {$filePath}");
        }

        $sql = file_get_contents($filePath);

        if ($sql === false) {
            throw new RuntimeException("Failed to read SQL file: {$filePath}");
        }

        // Aplicar reemplazos (útil para customizar seed data)
        foreach ($replacements as $search => $replace) {
            $sql = str_replace($search, $replace, $sql);
        }

        return $this->executeSql($sql);
    }

    /**
     * Ejecutar string SQL completo con múltiples statements
     *
     * @param string $sql String SQL con múltiples statements separados por ;
     * @return bool True si la ejecución fue exitosa
     * @throws RuntimeException Si hay error en algún statement
     */
    private function executeSql(string $sql): bool
    {
        $this->log = []; // Reset log

        // Eliminar comentarios de línea antes de parsear
        $sql = $this->removeComments($sql);

        // Procesar y remover comandos DELIMITER - retorna array de statements
        $statements = $this->processDelimiters($sql);

        // Si processDelimiters retornó statements, usarlos directamente
        // Si no (archivo sin DELIMITER), parsear normalmente
        if (empty($statements)) {
            $statements = $this->parseStatements($sql);
        }

        if (empty($statements)) {
            throw new RuntimeException("No valid SQL statements found");
        }

        // Solo iniciar transacción si está habilitada
        if ($this->useTransactions) {
            $this->connection->beginTransaction();
            $this->inTransaction = true;
        }

        try {
            $successCount = 0;

            foreach ($statements as $index => $stmt) {
                $stmt = trim($stmt);

                // Skip empty statements o solo comentarios
                if (empty($stmt) || $this->isComment($stmt)) {
                    continue;
                }

                // Log statement (primeros 100 caracteres para no saturar memoria)
                $preview = strlen($stmt) > 100 ? substr($stmt, 0, 100) . '...' : $stmt;

                $this->log[] = [
                    'index' => $index,
                    'statement' => $preview,
                    'length' => strlen($stmt),
                    'status' => 'pending',
                    'timestamp' => microtime(true)
                ];

                // Ejecutar statement
                try {
                    // Detectar si es un statement que retorna resultados (SELECT, SHOW, DESCRIBE)
                    // o usa variables de usuario SET @var = (SELECT ...)
                    $returnsResults = preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\s+/i', $stmt);
                    $usesUserVariable = preg_match('/^\s*SET\s+@/i', $stmt);
                    $isPreparedStmt = preg_match('/^\s*(PREPARE|EXECUTE|DEALLOCATE)\s+/i', $stmt);

                    if ($returnsResults || $usesUserVariable || $isPreparedStmt) {
                        // Para statements que retornan resultados o usan variables/prepared statements
                        // usar query() y asegurar que todos los cursores se cierren
                        $result = $this->connection->query($stmt);

                        if ($result instanceof \PDOStatement) {
                            // Consumir todos los resultados para liberar el buffer
                            while ($result->nextRowset()) {
                                // Consumir múltiples resultados si existen (prepared statements)
                            }
                            $result->closeCursor();
                            unset($result);
                        }
                    } else {
                        // Para DDL/DML sin resultados (CREATE, ALTER, DROP, INSERT, UPDATE, DELETE)
                        // usar exec() que es más eficiente y no crea cursores
                        $this->connection->exec($stmt);
                    }

                    $this->log[$successCount]['status'] = 'success';
                    $this->log[$successCount]['execution_time'] = microtime(true) - $this->log[$successCount]['timestamp'];

                    $successCount++;

                } catch (PDOException $e) {
                    $this->log[$successCount]['status'] = 'error';
                    $this->log[$successCount]['error'] = $e->getMessage();
                    $this->log[$successCount]['error_code'] = $e->getCode();

                    throw new RuntimeException(
                        "SQL execution failed at statement #{$index}:\n" .
                        "Statement: {$preview}\n" .
                        "Error: " . $e->getMessage() . "\n" .
                        "Error Code: " . $e->getCode()
                    );
                }
            }

            // Verificar si PDO todavía tiene una transacción activa
            // (statements DDL como ALTER TABLE hacen commit implícito en MySQL)
            if ($this->useTransactions && $this->connection->inTransaction()) {
                $this->connection->commit();
            }
            $this->inTransaction = false;

            return true;

        } catch (Exception $e) {
            if ($this->useTransactions && $this->inTransaction) {
                try {
                    if ($this->connection->inTransaction()) {
                        $this->connection->rollback();
                    }
                } catch (PDOException $rollbackException) {
                    // Si rollback falla (ej: no hay transacción activa), loguear pero continuar
                    error_log("Rollback warning: " . $rollbackException->getMessage());
                }
                $this->inTransaction = false;
            }

            throw $e;
        }
    }

    /**
     * Procesar comandos DELIMITER del SQL
     *
     * Los archivos SQL exportados de MySQL Workbench/Navicat usan DELIMITER
     * para cambiar el delimitador cuando definen procedures/triggers/functions.
     * Este método divide el SQL en statements usando el delimitador correcto.
     *
     * @param string $sql String SQL con posibles comandos DELIMITER
     * @return array Array de statements ya procesados, o array vacío si no hay DELIMITER
     */
    private function processDelimiters(string $sql): array
    {
        // Verificar si el SQL contiene comandos DELIMITER
        if (!preg_match('/delimiter\s+/i', $sql)) {
            // No hay comandos DELIMITER, retornar array vacío para usar parseStatements normal
            return [];
        }

        $output = [];
        $currentDelimiter = ';';
        $buffer = '';
        $position = 0;
        $length = strlen($sql);

        while ($position < $length) {
            // Buscar la siguiente línea
            $endOfLine = strpos($sql, "\n", $position);
            if ($endOfLine === false) {
                $endOfLine = $length;
            }

            $line = substr($sql, $position, $endOfLine - $position);
            $trimmedLine = trim($line);

            // Detectar comando DELIMITER
            if (preg_match('/^delimiter\s+(.+)$/i', $trimmedLine, $matches)) {
                // Si hay buffer acumulado, procesarlo con el delimitador anterior
                if (!empty(trim($buffer))) {
                    // Dividir por el delimitador actual y agregar cada statement
                    $statements = explode($currentDelimiter, $buffer);
                    foreach ($statements as $stmt) {
                        $stmt = trim($stmt);
                        if (!empty($stmt) && !$this->isComment($stmt)) {
                            $output[] = $stmt; // NO agregar ; extra - ya viene completo
                        }
                    }
                    $buffer = '';
                }

                // Cambiar delimitador
                $currentDelimiter = trim($matches[1]);
                $position = $endOfLine + 1;
                continue;
            }

            // Acumular la línea en el buffer
            $buffer .= $line . "\n";
            $position = $endOfLine + 1;
        }

        // Procesar el buffer final
        if (!empty(trim($buffer))) {
            $statements = explode($currentDelimiter, $buffer);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt) && !$this->isComment($stmt)) {
                    $output[] = $stmt; // NO agregar ; extra - ya viene completo
                }
            }
        }

        return $output;
    }

    /**
     * Parsear SQL string en statements individuales
     *
     * Parser robusto que maneja:
     * - Strings con punto y coma interno
     * - Escape de caracteres
     * - Comentarios SQL
     * - Delimitadores custom
     *
     * @param string $sql String SQL completo
     * @return array Array de statements individuales
     */
    private function parseStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inString = false;
        $stringChar = null;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $prevChar = $i > 0 ? $sql[$i - 1] : '';

            // Detectar inicio/fin de strings
            if (($char === '"' || $char === "'") && $prevChar !== '\\') {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                    $stringChar = null;
                }
            }

            // Separar por ; solo cuando estamos fuera de strings
            if ($char === ';' && !$inString) {
                $statement = trim($buffer);

                // Solo agregar statements no vacíos
                if (!empty($statement)) {
                    $statements[] = $statement;
                }

                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        // Agregar último statement si no termina con ;
        $lastStatement = trim($buffer);
        if (!empty($lastStatement) && !$this->isComment($lastStatement)) {
            $statements[] = $lastStatement;
        }

        return $statements;
    }

    /**
     * Eliminar comentarios SQL del código
     *
     * @param string $sql String SQL completo
     * @return string SQL sin comentarios
     */
    private function removeComments(string $sql): string
    {
        // Eliminar comentarios de línea (-- comentario)
        $sql = preg_replace('/--[^\r\n]*/', '', $sql);

        // Eliminar comentarios de bloque (/* comentario */)
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        return $sql;
    }

    /**
     * Verificar si un statement es solo un comentario o directiva a ignorar
     *
     * @param string $stmt Statement a verificar
     * @return bool True si es un comentario o directiva a ignorar
     */
    private function isComment(string $stmt): bool
    {
        $trimmed = ltrim($stmt);

        // Comentario de línea (-- comentario)
        if (strpos($trimmed, '--') === 0) {
            return true;
        }

        // Comentario de bloque (/* comentario */)
        if (strpos($trimmed, '/*') === 0) {
            return true;
        }

        // Comando DELIMITER (debería estar eliminado por processDelimiters, pero por seguridad)
        if (stripos($trimmed, 'DELIMITER') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Obtener log detallado de ejecución
     *
     * Útil para debugging y troubleshooting
     *
     * @return array Array con información de cada statement ejecutado
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Obtener estadísticas de la última importación
     *
     * @return array Array con contadores de éxito/error
     */
    public function getStats(): array
    {
        $success = 0;
        $failed = 0;
        $totalTime = 0;

        foreach ($this->log as $entry) {
            if ($entry['status'] === 'success') {
                $success++;
                $totalTime += $entry['execution_time'] ?? 0;
            } elseif ($entry['status'] === 'error') {
                $failed++;
            }
        }

        return [
            'total_statements' => count($this->log),
            'successful' => $success,
            'failed' => $failed,
            'total_execution_time' => round($totalTime, 4),
            'average_execution_time' => $success > 0 ? round($totalTime / $success, 4) : 0
        ];
    }

    /**
     * Verificar si hay una transacción activa
     *
     * @return bool True si hay una transacción en curso
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Limpiar log de memoria (útil para importaciones grandes)
     */
    public function clearLog(): void
    {
        $this->log = [];
    }
}
