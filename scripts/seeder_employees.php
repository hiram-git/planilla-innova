<?php
/**
 * Seeder para 5000 empleados - Prueba de Stress
 * Ejecutar: php seeder_employees.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

class EmployeeSeeder
{
    private $db;
    private $positionIds = [1, 2, 3, 4, 5, 6, 7]; // IDs de posiciones existentes
    private $scheduleIds = [1, 2, 3, 4, 5]; // IDs de horarios existentes
    
    // Arrays para generar datos realistas
    private $firstNames = [
        'Carlos', 'María', 'José', 'Ana', 'Luis', 'Carmen', 'Miguel', 'Elena', 'Antonio', 'Isabel',
        'Manuel', 'Pilar', 'Francisco', 'Mercedes', 'David', 'Dolores', 'José Antonio', 'Antonia',
        'Rafael', 'Francisca', 'Jesús', 'Cristina', 'Ángel', 'Rosa', 'Javier', 'Lucía', 'Juan Carlos',
        'María Carmen', 'Daniel', 'María José', 'José Luis', 'Ana María', 'Juan', 'Rosario', 'Alejandro',
        'Teresa', 'Fernando', 'Concepción', 'Sergio', 'Encarnación', 'Pablo', 'Manuela', 'Jorge',
        'Josefa', 'Alberto', 'Montserrat', 'Ricardo', 'Amparo', 'Íñigo', 'Inmaculada', 'Raúl',
        'Remedios', 'Ramón', 'Susana', 'Enrique', 'María Pilar', 'Iván', 'Begoña', 'Roberto',
        'María Teresa', 'Gonzalo', 'Soledad', 'Víctor', 'Silvia', 'Pedro', 'Rosa María', 'Rubén',
        'Consuelo', 'Julián', 'Purificación', 'Marcos', 'Patricia', 'Ignacio', 'Yolanda', 'Eduardo',
        'Sonia', 'Salvador', 'Nuria', 'Andrés', 'Esperanza', 'Adrián', 'Carmen María', 'Juan José'
    ];
    
    private $lastNames = [
        'García', 'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez',
        'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández', 'Díaz', 'Moreno', 'Muñoz', 'Álvarez',
        'Romero', 'Alonso', 'Gutiérrez', 'Navarro', 'Torres', 'Domínguez', 'Vázquez', 'Ramos',
        'Gil', 'Ramírez', 'Serrano', 'Blanco', 'Suárez', 'Molina', 'Morales', 'Ortega', 'Delgado',
        'Castro', 'Ortiz', 'Rubio', 'Marín', 'Sanz', 'Iglesias', 'Nuñez', 'Medina', 'Garrido',
        'Cortés', 'Castillo', 'Santos', 'Lozano', 'Guerrero', 'Cano', 'Prieto', 'Méndez', 'Cruz',
        'Herrera', 'Peña', 'Flores', 'Cabrera', 'Campos', 'Vega', 'Fuentes', 'Carrasco', 'Diez',
        'Caballero', 'León', 'Márquez', 'Reyes', 'Vicente', 'Ferrer', 'Silva', 'Vargas', 'Pascual',
        'Rivas', 'Calvo', 'Giménez', 'Santana', 'Herrero', 'Aguilar', 'Lorenzo', 'Hidalgo', 'Santiago'
    ];
    
    private $streets = [
        'Avenida Central', 'Calle Principal', 'Boulevard Los Robles', 'Avenida La Reforma',
        'Calle del Sol', '6ta Avenida', '7ma Calle', 'Avenida Las Américas', 'Calle Real',
        'Avenida Roosevelt', 'Calle Montúfar', 'Avenida Bolívar', 'Calle La Libertad',
        'Avenida Universidad', 'Calle del Centro', 'Boulevard Los Próceres', 'Avenida Hincapié',
        'Calle Simón Cañas', 'Avenida Elena', 'Calle Mariscal Cruz', 'Avenida Los Volcanes',
        'Calle San José', 'Avenida Petapa', 'Calle Vista Hermosa', 'Avenida Las Flores'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        echo "Seeder de Empleados Inicializado\n";
        echo "==============================\n";
    }
    
    public function run()
    {
        $startTime = microtime(true);
        echo "Iniciando creación de 5000 empleados...\n";
        
        // Limpiar empleados existentes (opcional)
        if ($this->askConfirmation("¿Desea limpiar empleados existentes antes de crear los nuevos? (y/N): ")) {
            $this->cleanExistingEmployees();
        }
        
        $batchSize = 100; // Insertar en lotes para optimizar
        $totalEmployees = 5000;
        $batches = ceil($totalEmployees / $batchSize);
        
        for ($batch = 0; $batch < $batches; $batch++) {
            $startEmployee = $batch * $batchSize + 1;
            $endEmployee = min(($batch + 1) * $batchSize, $totalEmployees);
            
            echo "Procesando lote " . ($batch + 1) . "/$batches (empleados $startEmployee-$endEmployee)...\n";
            
            $this->insertEmployeeBatch($startEmployee, $endEmployee);
            
            // Mostrar progreso cada 10 lotes
            if (($batch + 1) % 10 == 0) {
                $progress = round((($batch + 1) / $batches) * 100, 1);
                echo "Progreso: $progress% completado\n";
            }
        }
        
        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        
        echo "\n✅ COMPLETADO!\n";
        echo "==============================\n";
        echo "• Empleados creados: $totalEmployees\n";
        echo "• Tiempo de ejecución: {$executionTime}s\n";
        echo "• Promedio: " . round($totalEmployees / $executionTime, 0) . " empleados/segundo\n";
        
        $this->showStatistics();
    }
    
    private function insertEmployeeBatch($startEmployee, $endEmployee)
    {
        $values = [];
        $params = [];
        
        for ($i = $startEmployee; $i <= $endEmployee; $i++) {
            $employeeData = $this->generateEmployeeData($i);
            
            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            array_push($params, 
                $employeeData['employee_id'],
                $employeeData['firstname'],
                $employeeData['lastname'],
                $employeeData['address'],
                $employeeData['birthdate'],
                $employeeData['fecha_ingreso'],
                $employeeData['contact_info'],
                $employeeData['gender'],
                $employeeData['position_id'],
                $employeeData['schedule_id'],
                $employeeData['photo'],
                $employeeData['created_on']
            );
        }
        
        $sql = "INSERT INTO employees 
                (employee_id, firstname, lastname, address, birthdate, fecha_ingreso, 
                 contact_info, gender, position_id, schedule_id, photo, created_on) 
                VALUES " . implode(', ', $values);
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
        } catch (Exception $e) {
            echo "Error en lote $startEmployee-$endEmployee: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function generateEmployeeData($index)
    {
        // Generar employee_id único (formato: EMP + 5 dígitos)
        $employeeId = 'EMP' . str_pad($index, 5, '0', STR_PAD_LEFT);
        
        // Nombres y apellidos aleatorios
        $firstName = $this->firstNames[array_rand($this->firstNames)];
        $lastName = $this->lastNames[array_rand($this->lastNames)] . ' ' . 
                   $this->lastNames[array_rand($this->lastNames)];
        
        // Dirección aleatoria
        $street = $this->streets[array_rand($this->streets)];
        $houseNumber = rand(1, 999);
        $zone = rand(1, 25);
        $address = "$street No. $houseNumber, Zona $zone, Guatemala";
        
        // Fecha de nacimiento (entre 22 y 65 años)
        $minAge = 22;
        $maxAge = 65;
        $birthYear = date('Y') - rand($minAge, $maxAge);
        $birthDate = "$birthYear-" . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
                     str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        
        // Fecha de ingreso (últimos 10 años)
        $ingresoYear = rand(date('Y') - 10, date('Y'));
        $fechaIngreso = "$ingresoYear-" . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . '-' . 
                        str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        
        // Información de contacto
        $phonePrefix = rand(2000, 9999);
        $phoneSuffix = rand(1000, 9999);
        $contactInfo = "$phonePrefix-$phoneSuffix";
        
        // Género aleatorio
        $gender = rand(0, 1) ? 'M' : 'F';
        
        // Posición y horario aleatorios
        $positionId = $this->positionIds[array_rand($this->positionIds)];
        $scheduleId = $this->scheduleIds[array_rand($this->scheduleIds)];
        
        return [
            'employee_id' => $employeeId,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'address' => $address,
            'birthdate' => $birthDate,
            'fecha_ingreso' => $fechaIngreso,
            'contact_info' => $contactInfo,
            'gender' => $gender,
            'position_id' => $positionId,
            'schedule_id' => $scheduleId,
            'photo' => null, // Sin foto para pruebas
            'created_on' => date('Y-m-d H:i:s')
        ];
    }
    
    private function cleanExistingEmployees()
    {
        echo "Limpiando empleados existentes...\n";
        
        try {
            // Primero limpiar referencias en planilla_detalle si existen
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 0");
            $this->db->getConnection()->exec("DELETE FROM planilla_detalle");
            $this->db->getConnection()->exec("DELETE FROM employees");
            $this->db->getConnection()->exec("ALTER TABLE employees AUTO_INCREMENT = 1");
            $this->db->getConnection()->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            echo "✅ Empleados existentes eliminados\n";
        } catch (Exception $e) {
            echo "❌ Error al limpiar empleados: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    private function showStatistics()
    {
        try {
            // Total de empleados
            $totalEmployees = $this->db->find("SELECT COUNT(*) as total FROM employees")['total'];
            
            // Empleados por posición
            $positionStats = $this->db->findAll("
                SELECT p.codigo, p.sueldo, COUNT(e.id) as empleados
                FROM posiciones p
                LEFT JOIN employees e ON p.id = e.position_id
                GROUP BY p.id, p.codigo, p.sueldo
                ORDER BY empleados DESC
            ");
            
            // Empleados por horario
            $scheduleStats = $this->db->findAll("
                SELECT s.descripcion, COUNT(e.id) as empleados
                FROM schedules s
                LEFT JOIN employees e ON s.id = e.schedule_id
                GROUP BY s.id, s.descripcion
                ORDER BY empleados DESC
            ");
            
            echo "\n📊 ESTADÍSTICAS\n";
            echo "==============================\n";
            echo "Total de empleados: $totalEmployees\n\n";
            
            echo "Distribución por Posición:\n";
            foreach ($positionStats as $stat) {
                echo "  • {$stat['codigo']}: {$stat['empleados']} empleados (Q" . number_format($stat['sueldo'], 2) . ")\n";
            }
            
            echo "\nDistribución por Horario:\n";
            foreach ($scheduleStats as $stat) {
                echo "  • {$stat['descripcion']}: {$stat['empleados']} empleados\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error al generar estadísticas: " . $e->getMessage() . "\n";
        }
    }
    
    private function askConfirmation($question)
    {
        echo $question;
        $handle = fopen("php://stdin", "r");
        $response = strtolower(trim(fgets($handle)));
        fclose($handle);
        return in_array($response, ['y', 'yes', 'sí', 'si']);
    }
}

// Ejecutar solo si se llama directamente
if (php_sapi_name() === 'cli') {
    try {
        $seeder = new EmployeeSeeder();
        $seeder->run();
    } catch (Exception $e) {
        echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        echo "Línea: " . $e->getLine() . "\n";
        echo "Archivo: " . $e->getFile() . "\n";
        exit(1);
    }
} else {
    echo "Este script debe ejecutarse desde la línea de comandos: php seeder_employees.php\n";
}
?>