<?php
// Test para verificar estados de planillas
require_once 'app/Core/Database.php';

try {
    $db = App\Core\Database::getInstance();
    
    echo "<h1>Test de Estados de Planillas</h1>";
    
    // Ver todas las planillas y sus estados
    $stmt = $db->query("SELECT id, descripcion, estado, fecha FROM planilla_cabecera ORDER BY id DESC LIMIT 10");
    $planillas = $stmt->fetchAll();
    
    echo "<h2>Últimas 10 planillas:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Descripción</th><th>Estado</th><th>Fecha</th></tr>";
    
    foreach ($planillas as $planilla) {
        $bgColor = '';
        switch (strtoupper($planilla['estado'])) {
            case 'CERRADA':
                $bgColor = 'background-color: #d4edda;'; // Verde claro
                break;
            case 'PROCESADA':
                $bgColor = 'background-color: #fff3cd;'; // Amarillo claro
                break;
            case 'PENDIENTE':
                $bgColor = 'background-color: #f8d7da;'; // Rojo claro
                break;
        }
        
        echo "<tr style='$bgColor'>";
        echo "<td>{$planilla['id']}</td>";
        echo "<td>" . htmlspecialchars($planilla['descripcion']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($planilla['estado']) . "</strong></td>";
        echo "<td>{$planilla['fecha']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Ver estados únicos
    $statesStmt = $db->query("SELECT DISTINCT estado, COUNT(*) as count FROM planilla_cabecera GROUP BY estado");
    $states = $statesStmt->fetchAll();
    
    echo "<h2>Estados existentes:</h2>";
    echo "<ul>";
    foreach ($states as $state) {
        echo "<li><strong>" . htmlspecialchars($state['estado']) . "</strong>: {$state['count']} planillas</li>";
    }
    echo "</ul>";
    
    // Test de la condición PHP
    echo "<h2>Test de Condiciones PHP:</h2>";
    foreach ($planillas as $planilla) {
        $estado = $planilla['estado'];
        $shouldShow = ($estado === 'CERRADA' || $estado === 'cerrada');
        
        echo "<p>Planilla ID {$planilla['id']} - Estado: '<strong>$estado</strong>' - ";
        echo "¿Mostrar botón reabrir? " . ($shouldShow ? "<span style='color:green'>SÍ</span>" : "<span style='color:red'>NO</span>");
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
<hr>
<h2>Crear planilla de prueba CERRADA</h2>
<form method="post">
    <button type="submit" name="create_test">Crear planilla CERRADA para pruebas</button>
</form>

<?php
if (isset($_POST['create_test'])) {
    try {
        $db = App\Core\Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO planilla_cabecera (descripcion, estado, fecha, fecha_desde, fecha_hasta, usuario_creacion)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'PLANILLA TEST - CERRADA',
            'CERRADA',
            date('Y-m-d'),
            date('Y-m-01'),
            date('Y-m-t'),
            'Admin Test'
        ]);
        
        echo "<p style='color:green'>✅ Planilla de prueba creada con estado CERRADA</p>";
        echo "<script>setTimeout(() => location.reload(), 1000);</script>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    }
}
?>