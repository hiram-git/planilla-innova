<?php
// Debug específico para el botón de reabrir
session_start();
require_once 'app/Core/Bootstrap.php';

try {
    // Simular una planilla cerrada
    $payroll = [
        'id' => 999,
        'descripcion' => 'PLANILLA DEBUG - CERRADA',
        'estado' => 'CERRADA'
    ];
    
    echo "<h1>Debug del Botón de Reabrir</h1>";
    echo "<p>Estado de la planilla: <strong>{$payroll['estado']}</strong></p>";
    
    // Test de las condiciones
    $condition1 = ($payroll['estado'] === 'CERRADA');
    $condition2 = ($payroll['estado'] === 'cerrada');
    $finalCondition = ($payroll['estado'] === 'CERRADA' || $payroll['estado'] === 'cerrada');
    
    echo "<p>¿Estado === 'CERRADA'? " . ($condition1 ? "✅ SÍ" : "❌ NO") . "</p>";
    echo "<p>¿Estado === 'cerrada'? " . ($condition2 ? "✅ SÍ" : "❌ NO") . "</p>";
    echo "<p>¿Condición final? " . ($finalCondition ? "✅ SÍ - MOSTRAR BOTÓN" : "❌ NO - OCULTAR BOTÓN") . "</p>";
    
    echo "<hr>";
    echo "<h2>Simulación del botón:</h2>";
    
    // Mostrar el HTML que se generaría
    ?>
    <div class="btn-group" role="group">
        <?php if ($payroll['estado'] === 'PENDIENTE'): ?>
            <p>Caso: PENDIENTE - Mostrar botones de procesar/editar</p>
        <?php elseif ($payroll['estado'] === 'PROCESADA'): ?>
            <p>Caso: PROCESADA - Mostrar botones de reprocesar/pendiente/cerrar</p>
        <?php elseif ($payroll['estado'] === 'CERRADA' || $payroll['estado'] === 'cerrada'): ?>
            <button type="button" class="btn btn-warning btn-sm" id="reopenBtn" 
                    data-id="<?= $payroll['id'] ?>" 
                    data-description="<?= htmlspecialchars($payroll['descripcion']) ?>">
                <i class="fas fa-unlock"></i> Reabrir
            </button>
            <p style="color: green;">✅ <strong>BOTÓN DE REABRIR MOSTRADO</strong></p>
        <?php else: ?>
            <p style="color: red;">❌ Estado no reconocido: <?= $payroll['estado'] ?></p>
        <?php endif; ?>
    </div>

    <hr>
    <h2>Test con diferentes estados:</h2>
    <?php
    $testStates = ['PENDIENTE', 'PROCESADA', 'CERRADA', 'cerrada', 'ANULADA'];
    
    foreach ($testStates as $testState) {
        $testPayroll = ['estado' => $testState];
        $shouldShow = ($testPayroll['estado'] === 'CERRADA' || $testPayroll['estado'] === 'cerrada');
        
        echo "<p>Estado: <strong>$testState</strong> → ";
        echo ($shouldShow ? "<span style='color:green'>MOSTRAR BOTÓN</span>" : "<span style='color:gray'>OCULTAR BOTÓN</span>");
        echo "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>