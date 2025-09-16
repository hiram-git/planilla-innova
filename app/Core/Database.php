<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $app_config = require './config/database.php';
        $config = $app_config['connections']['mysql'] ?? [];

        try {
            $this->connection = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw $e;
        }
    }

    public function find($sql, $params = [])
    {
        return $this->query($sql, $params)->fetch();
    }

    public function findAll($sql, $params = [])
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $fields = implode(',', $keys);
        $placeholders = ':' . implode(', :', $keys);
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = [])
    {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }
        $fields = implode(', ', $fields);
        
        $sql = "UPDATE {$table} SET {$fields} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        try {
            $stmt = $this->query($sql, $params);
            $rowCount = $stmt->rowCount();
            
            // Si no se actualizó ninguna fila, verificar si el registro existe
            if ($rowCount == 0) {
                // Extraer el campo WHERE principal (asumiendo formato "campo = :param")
                $whereParts = explode('=', trim($where));
                if (count($whereParts) == 2) {
                    // Verificar si existe el registro
                    $checkSql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
                    $checkStmt = $this->query($checkSql, $whereParams);
                    $exists = $checkStmt->fetchColumn() > 0;
                    
                    // Si el registro existe, considerarlo exitoso (sin cambios)
                    if ($exists) {
                        return 1; // Simular que se actualizó una fila
                    }
                }
            }
            
            return $rowCount;
        } catch (\Exception $e) {
            error_log("UPDATE ERROR: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($table, $where, $whereParams = [])
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $whereParams)->rowCount();
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollback()
    {
        return $this->connection->rollback();
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function prepare($sql)
    {
        return $this->connection->prepare($sql);
    }
}