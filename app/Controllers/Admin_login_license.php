<?php
/**
 * Fragmento de controlador Admin.php con validación de licencia corregida.
 * Reemplaza este archivo en Admin.php si necesitas comparar.
 */
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Core\Security;
use App\Core\ActivityLogger;
use App\Middleware\AuthMiddleware;
use App\Core\MasterDatabase;

class Admin extends Controller
{
    // ... resto de métodos

    // coloca este método al final de Admin.php
    private function updateTenantLicenseExpiration($tenantId, $expirationDate)
    {
        try {
            $masterDb = MasterDatabase::getInstance()->getConnection();
            $stmt = $masterDb->prepare("UPDATE tenants SET license_expires_at = ? WHERE id = ?");
            $stmt->execute([$expirationDate, $tenantId]);
        } catch (\Exception $e) {
            error_log("No se pudo actualizar license_expires_at para tenant {$tenantId}: " . $e->getMessage());
        }
    }
}
