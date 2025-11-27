<?php
require_once 'app/Core/TenantResolver.php';
require_once 'app/Core/Database.php';

$systemModules = [
    1 => ['name' => 'Dashboard', 'url' => 'dashboard', 'icon' => 'fas fa-tachometer-alt'],
    2 => ['name' => 'Empresa', 'url' => 'company', 'icon' => 'fas fa-building'],
    3 => ['name' => 'Plazas', 'url' => 'positions', 'icon' => 'fas fa-chair'],
    4 => ['name' => 'Partidas', 'url' => 'partidas', 'icon' => 'fas fa-money-check-alt'],
    5 => ['name' => 'Organigrama', 'url' => 'organigrama', 'icon' => 'fas fa-sitemap'],
    6 => ['name' => 'Cargos', 'url' => 'cargos', 'icon' => 'fas fa-briefcase'],
    7 => ['name' => 'Funciones', 'url' => 'funciones', 'icon' => 'fas fa-tasks'],
    8 => ['name' => 'Empleados', 'url' => 'employees', 'icon' => 'fas fa-users'],
    9 => ['name' => 'Horas Extras', 'url' => 'overtime', 'icon' => 'fas fa-clock'],
    10 => ['name' => 'Horarios', 'url' => 'schedules', 'icon' => 'fas fa-calendar-alt'],
    11 => ['name' => 'Asistencia', 'url' => 'attendance', 'icon' => 'fas fa-user-clock'],
    12 => ['name' => 'Acreedores', 'url' => 'creditors', 'icon' => 'fas fa-hand-holding-usd'],
    13 => ['name' => 'Planillas', 'url' => 'payrolls', 'icon' => 'fas fa-file-invoice-dollar'],
    14 => ['name' => 'Conceptos', 'url' => 'concepts', 'icon' => 'fas fa-calculator'],
    15 => ['name' => 'Tipos de Planilla', 'url' => 'tipos-planilla', 'icon' => 'fas fa-list-alt'],
    16 => ['name' => 'Usuarios', 'url' => 'users', 'icon' => 'fas fa-user-cog'],
    17 => ['name' => 'Roles y Permisos', 'url' => 'roles', 'icon' => 'fas fa-user-shield'],
    18 => ['name' => 'Acumulados', 'url' => 'acumulados', 'icon' => 'fas fa-piggy-bank'],
    19 => ['name' => 'Tipos de Acumulados', 'url' => 'tipos-acumulados', 'icon' => 'fas fa-coins'],
    20 => ['name' => 'Frecuencias', 'url' => 'frecuencias', 'icon' => 'fas fa-history'],
    21 => ['name' => 'Situaciones', 'url' => 'situaciones', 'icon' => 'fas fa-info-circle'],
    22 => ['name' => 'Liquidaciones', 'url' => 'liquidation', 'icon' => 'fas fa-door-open'],
    23 => ['name' => 'Vacaciones', 'url' => 'vacation', 'icon' => 'fas fa-umbrella-beach'],
    24 => ['name' => 'Calendario', 'url' => 'business-calendar', 'icon' => 'fas fa-calendar-day'],
    25 => ['name' => 'Reportes', 'url' => 'reports', 'icon' => 'fas fa-chart-bar']
];

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    $db->beginTransaction();

    echo "Seeding menu_items...\n";

    foreach ($systemModules as $id => $module) {
        // Check if exists
        $stmt = $db->prepare("SELECT id, name FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Skip update to preserve legacy URLs
            echo "Skipping existing ID {$id}: {$existing['name']}\n";
        } else {
            // Insert
            echo "Inserting ID {$id}: {$module['name']}\n";
            $sql = "INSERT INTO menu_items (id, name, url) VALUES (?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id, $module['name'], $module['url']]);
        }
    }

    $db->commit();
    echo "Done!\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
