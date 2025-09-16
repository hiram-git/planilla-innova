<?php
class DatabaseRestorer {
    private PDO $pdo;
    private array $config;

    // Constructor: carga la configuración y establece la conexión inicial a 'master'
    public function __construct() {
        // Cargar la configuración desde el archivo
        $this->config = require __DIR__ . '/config/data.php';
        
        // Validar que todos los parámetros necesarios están presentes
        $requiredKeys = ['serverName', 'username', 'password', 'defaultDatabase'];
        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key])) {
                throw new Exception("Falta el parámetro de configuración: $key");
            }
        }

        $this->connect($this->config['defaultDatabase']); // Conexión inicial a 'master'
    }

    // Método privado para establecer la conexión
    private function connect(string $database): void {
        try {
            $this->pdo = new PDO(
                "sqlsrv:Server={$this->config['serverName']};Database=$database",
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Error de conexión a la base de datos '$database': " . $e->getMessage());
        }
    }

    // Método para verificar si la base de datos existe
    private function databaseExists(string $nombreBaseDatos): bool {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sys.databases WHERE name = :dbName");
            $stmt->execute(['dbName' => $nombreBaseDatos]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Error al verificar la existencia de la base de datos: " . $e->getMessage());
        }
    }
    
    // Función para limpiar el nombre y generar un sufijo aleatorio
    public function generarNombreBaseDatos(string $nombreOriginal): string {
        // Quitar caracteres especiales, dejar solo letras, números y guiones bajos
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9_]/', '', $nombreOriginal);
        
        // Generar una cadena aleatoria de 10 caracteres (letras y números)
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $sufijoAleatorio = '';
        /*for ($i = 0; $i < 10; $i++) {
            $sufijoAleatorio .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        $caracteres = '0123456789';
        for ($i = 0; $i < 6; $i++) {
            $sufijoAleatorio .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }*/
        
        // Concatenar el nombre limpio con el sufijo aleatorio
        $nombreFinal = $nombreLimpio ."_". $sufijoAleatorio;
        
        // Limitar a 128 caracteres (máximo en SQL Server)
        return substr($nombreFinal, 0, 128);
    }
    public function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    // Método público para restaurar la base de datos usando migraciones
    public function restoreDatabase(string $rutaMigraciones, string $nombreBaseDatos) {
        try {
            // Verificar si la base de datos ya existe
            if ($this->databaseExists($nombreBaseDatos)) {
                throw new Exception("La base de datos '$nombreBaseDatos' ya existe.");
            }

            // Crear la base de datos si no existe
            $comandoCreate = "CREATE DATABASE {$nombreBaseDatos};";
            $this->pdo->exec($comandoCreate);
            // echo "Base de datos '$nombreBaseDatos' creada con éxito.\n";

            // Ruta absoluta al ejecutable de PHP 8.3
            $phpBinary = '/usr/bin/php8.3'; // Ajusta esta ruta según tu servidor

            // Ruta al comando artisan (ajusta según la ubicación en tu servidor)
            $artisanPath = $rutaMigraciones . ' ';

            // Comando completo para ejecutar las migraciones
            $comando = $phpBinary." ".$artisanPath. " migrate:fresh --seed --database=dynamic";
            putenv("DB_DATABASE=$nombreBaseDatos");
            // Ejecutar el comando en el sistema
            $output = [];
            $returnVar = 0;
            exec($comando . ' 2>&1', $output, $returnVar);

            // Verificar si hubo un error en la ejecución
            if ($returnVar !== 0) {
                throw new Exception("Error al ejecutar las migraciones: " . implode("\n", $output));
            }

            // echo "Migraciones ejecutadas con éxito en '$nombreBaseDatos'.\n";
        } catch (Exception $e) {
            throw new Exception("Error al restaurar la base de datos: " . $e->getMessage());
        }
    }
    public function saveDatabase(string $nombreBaseDatos, array $data) {
        $this->connect($nombreBaseDatos);

    }

    // Método para obtener el objeto PDO (opcional)
    public function getPdo(): PDO {
        return $this->pdo;
    }

    // Método para configurar el PDO con un array de parámetros
    public function setPDO(array $data): PDO {
        // Validar que todos los parámetros necesarios están presentes
        $requiredKeys = ['serverName', 'username', 'password', 'defaultDatabase'];
        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                throw new Exception("Falta el parámetro de configuración: $key");
            }
        }

        // Asignar los datos al atributo config
        $this->config = $data;

        // Establecer la conexión con la base de datos por defecto
        $this->connect($this->config['defaultDatabase']);
        
        return $this->pdo;
    }

}