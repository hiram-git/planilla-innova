<?php
// Archivo de prueba para verificar actualización de roles
require_once 'app/Core/Database.php';
require_once 'app/Core/Model.php';
require_once 'app/Models/Role.php';

use App\Core\Database;
use App\Models\Role;

try {
    echo "=== Prueba de Actualización de Rol ===\n";
    
    // Inicializar modelo
    $roleModel = new Role();
    
    // Obtener primer rol existente
    $roles = $roleModel->all();
    if (empty($roles)) {
        echo "No hay roles en la base de datos\n";
        exit;
    }
    
    $testRole = $roles[0];
    echo "Rol a actualizar: ID {$testRole['id']}, Nombre: {$testRole['name']}\n";
    
    // Datos de prueba
    $updateData = [
        'name' => $testRole['name'] . ' (Actualizado)',
        'description' => 'Descripción actualizada - ' . date('Y-m-d H:i:s'),
        'status' => 1,
        'permissions' => [
            1 => ['read' => true, 'write' => false, 'delete' => false],
            2 => ['read' => true, 'write' => true, 'delete' => false]
        ]
    ];
    
    echo "Intentando actualizar rol...\n";
    
    // Intentar actualización
    $result = $roleModel->update($testRole['id'], $updateData);
    
    if ($result['success']) {
        echo "✅ Actualización exitosa!\n";
    } else {
        echo "❌ Error en actualización: " . $result['message'] . "\n";
    }
    
    // Verificar el resultado
    $updatedRole = $roleModel->find($testRole['id']);
    echo "Rol después de actualización: " . json_encode($updatedRole) . "\n";
    
} catch (Exception $e) {
    echo "❌ Excepción capturada: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>