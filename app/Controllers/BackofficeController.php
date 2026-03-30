<?php

namespace App\Controllers;

use App\Core\BackofficeAuth;
use App\Core\MasterDatabase;
use App\Core\Security;
use PDO;

/**
 * BackofficeController - Controlador del panel de administración backoffice
 *
 * Gestiona autenticación y operaciones CRUD sobre empresas (tenants) en la base de datos master.
 * Solo accesible con token de seguridad configurado en .env
 *
 * @version 1.0.0
 * @date 2026-03-19
 */
class BackofficeController
{
    private ?PDO $master = null;

    public function __construct()
    {
        // Obtener conexión a base de datos master
        try {
            $this->master = MasterDatabase::getInstance()->getConnection();
        } catch (\Throwable $e) {
            error_log('Master DB connection failed in BackofficeController: ' . $e->getMessage());
        }
    }

    /**
     * Página de login del backoffice
     * GET /backoffice
     */
    public function index()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (BackofficeAuth::isAuthenticated()) {
            $dashboardUrl = \App\Core\UrlHelper::backoffice('dashboard');
            header('Location: ' . $dashboardUrl);
            exit;
        }

        // Obtener mensaje de error si existe
        $error = $_SESSION['backoffice_login_error'] ?? null;
        unset($_SESSION['backoffice_login_error']);

        // Renderizar vista de login
        $this->renderLogin($error);
    }

    /**
     * Procesar autenticación con token POST
     * POST /backoffice/authenticate
     */
    public function authenticate()
    {
        // Solo aceptar POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Obtener token (puede venir encriptado o en texto plano)
        $encryptedToken = $_POST['access_key'] ?? '';
        $plainToken = $_POST['access_key_plain'] ?? ''; // DEBUG: también aceptar en texto plano

        // DEBUG: Log lo que llega
        error_log('BackofficeController::authenticate() - Received encrypted token length: ' . strlen($encryptedToken));
        error_log('BackofficeController::authenticate() - Received plain token length: ' . strlen($plainToken));

        if (empty($encryptedToken) && empty($plainToken)) {
            $this->jsonResponse(false, 'Access key is required');
            return;
        }

        // Intentar validar primero el encriptado, luego el plano
        $isValid = false;

        if (!empty($encryptedToken)) {
            $isValid = BackofficeAuth::validateToken($encryptedToken);
        }

        // Si la validación encriptada falla, intentar con el texto plano (DEBUG/FALLBACK)
        if (!$isValid && !empty($plainToken)) {
            error_log('Encrypted validation failed, trying plain token...');
            $isValid = BackofficeAuth::validateToken($plainToken);
        }

        // Validar token
        if ($isValid) {
            // Token válido - autenticar
            BackofficeAuth::authenticate();

            $this->jsonResponse(true, 'Authentication successful', [
                'redirect' => \App\Core\UrlHelper::backoffice('dashboard')
            ]);
        } else {
            // Token inválido
            $this->jsonResponse(false, 'Invalid access key');
        }
    }

    /**
     * Dashboard principal del backoffice
     * GET /backoffice/dashboard
     */
    public function dashboard()
    {
        // Verificar autenticación (el middleware ya lo hace, pero doble check)
        if (!BackofficeAuth::isAuthenticated()) {
            $loginUrl = \App\Core\UrlHelper::backoffice();
            header('Location: ' . $loginUrl);
            exit;
        }

        // Obtener estadísticas de tenants
        $stats = $this->getTenantStats();

        // Renderizar dashboard
        $this->renderDashboard($stats);
    }

    /**
     * Listado de empresas con DataTables (AJAX)
     * GET /backoffice/tenants/data
     */
    public function tenantsData()
    {
        if (!$this->master) {
            $this->jsonResponse(false, 'Database connection not available');
            return;
        }

        // Parámetros DataTables
        $draw = (int)($_GET['draw'] ?? 1);
        $start = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 10);
        $search = $_GET['search']['value'] ?? '';
        $orderColumn = (int)($_GET['order'][0]['column'] ?? 0);
        $orderDir = $_GET['order'][0]['dir'] ?? 'desc';

        // Mapeo de columnas
        $columns = ['id', 'company_name', 'ruc', 'license_key', 'db_name', 'status', 'created_at'];
        $orderBy = $columns[$orderColumn] ?? 'id';

        // Query base
        $whereClause = "WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $whereClause .= " AND (company_name LIKE ? OR ruc LIKE ? OR license_key LIKE ? OR db_name LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }

        // Total de registros (sin filtro)
        $totalQuery = "SELECT COUNT(*) as total FROM tenants";
        $stmt = $this->master->query($totalQuery);
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total filtrado
        $filteredQuery = "SELECT COUNT(*) as total FROM tenants {$whereClause}";
        $stmt = $this->master->prepare($filteredQuery);
        $stmt->execute($params);
        $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener datos
        $dataQuery = "SELECT id, company_name, ruc, license_key, db_name, status,
                             license_expires_at, created_at, updated_at
                      FROM tenants
                      {$whereClause}
                      ORDER BY {$orderBy} {$orderDir}
                      LIMIT {$start}, {$length}";

        $stmt = $this->master->prepare($dataQuery);
        $stmt->execute($params);
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear datos para DataTables
        $data = array_map(function ($tenant) {
            return [
                'id' => $tenant['id'],
                'company_name' => htmlspecialchars($tenant['company_name']),
                'ruc' => htmlspecialchars($tenant['ruc']),
                'license_key' => htmlspecialchars($tenant['license_key']),
                'db_name' => htmlspecialchars($tenant['db_name']),
                'status' => $tenant['status'],
                'status_badge' => $this->getStatusBadge($tenant['status']),
                'license_expires_at' => $tenant['license_expires_at'] ?? 'N/A',
                'license_expiration_badge' => $this->getLicenseExpirationBadge($tenant['license_expires_at']),
                'created_at' => $tenant['created_at'],
                'actions' => $this->getActionButtons($tenant['id'])
            ];
        }, $tenants);

        // Respuesta DataTables
        header('Content-Type: application/json');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => (int)$totalRecords,
            'recordsFiltered' => (int)$filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Cerrar sesión del backoffice
     * GET /backoffice/logout
     */
    public function logout()
    {
        BackofficeAuth::logout();
        $loginUrl = \App\Core\UrlHelper::backoffice();
        header('Location: ' . $loginUrl);
        exit;
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Obtener estadísticas de empresas (por estado de licencia)
     *
     * @return array Estadísticas
     */
    private function getTenantStats(): array
    {
        if (!$this->master) {
            return [
                'total' => 0,
                'expired' => 0,
                'red' => 0,
                'yellow' => 0,
                'green' => 0
            ];
        }

        $stats = [
            'total' => 0,
            'expired' => 0,
            'red' => 0,      // < 3 días
            'yellow' => 0,   // < 7 días
            'green' => 0     // >= 7 días
        ];

        // Total de empresas
        $stmt = $this->master->query("SELECT COUNT(*) as total FROM tenants");
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener todas las empresas con su fecha de expiración
        $stmt = $this->master->query("
            SELECT license_expires_at
            FROM tenants
            WHERE license_expires_at IS NOT NULL
        ");
        $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $now = new \DateTime();

        foreach ($tenants as $tenant) {
            $expiresAt = new \DateTime($tenant['license_expires_at']);
            $interval = $now->diff($expiresAt);
            $daysRemaining = (int)$interval->format('%r%a'); // Negativo si ya expiró

            if ($daysRemaining < 0) {
                $stats['expired']++;
            } elseif ($daysRemaining < 3) {
                $stats['red']++;
            } elseif ($daysRemaining < 7) {
                $stats['yellow']++;
            } else {
                $stats['green']++;
            }
        }

        return $stats;
    }

    /**
     * Generar badge HTML para status
     *
     * @param string $status Status de la empresa
     * @return string HTML del badge
     */
    private function getStatusBadge(string $status): string
    {
        $badges = [
            'ACTIVE' => '<span class="badge badge-success">Activo</span>',
            'INACTIVE' => '<span class="badge badge-secondary">Inactivo</span>',
            'PENDING' => '<span class="badge badge-warning">Pendiente</span>',
            'SUSPENDED' => '<span class="badge badge-danger">Suspendido</span>',
        ];

        return $badges[$status] ?? '<span class="badge badge-secondary">' . htmlspecialchars($status) . '</span>';
    }

    /**
     * Generar badge HTML para expiración de licencia con color según días restantes
     *
     * @param string|null $expirationDate Fecha de expiración (Y-m-d H:i:s)
     * @return string HTML del badge con días restantes
     */
    private function getLicenseExpirationBadge(?string $expirationDate): string
    {
        if (empty($expirationDate)) {
            return '<span class="badge badge-secondary">Sin fecha</span>';
        }

        try {
            $now = new \DateTime();
            $expiresAt = new \DateTime($expirationDate);
            $interval = $now->diff($expiresAt);
            $daysRemaining = (int)$interval->format('%r%a'); // Negativo si ya expiró

            // Determinar color y texto del badge
            if ($daysRemaining < 0) {
                $days = abs($daysRemaining);
                $text = $days === 1 ? "Expiró hace 1 día" : "Expiró hace {$days} días";
                return '<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> ' . $text . '</span>';
            } elseif ($daysRemaining === 0) {
                return '<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> Expira hoy</span>';
            } elseif ($daysRemaining < 3) {
                $text = $daysRemaining === 1 ? "1 día" : "{$daysRemaining} días";
                return '<span class="badge badge-danger"><i class="fas fa-clock"></i> ' . $text . '</span>';
            } elseif ($daysRemaining < 7) {
                return '<span class="badge badge-warning"><i class="fas fa-clock"></i> ' . $daysRemaining . ' días</span>';
            } else {
                return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> ' . $daysRemaining . ' días</span>';
            }
        } catch (\Exception $e) {
            return '<span class="badge badge-secondary">Fecha inválida</span>';
        }
    }

    /**
     * Generar botones de acción para cada empresa
     *
     * @param int $tenantId ID de la empresa
     * @return string HTML de botones
     */
    private function getActionButtons(int $tenantId): string
    {
        return '
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-info" onclick="viewTenant(' . $tenantId . ')" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-warning" onclick="editTenant(' . $tenantId . ')" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger" onclick="deleteTenant(' . $tenantId . ')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
    }

    /**
     * Renderizar vista de login
     *
     * @param string|null $error Mensaje de error
     */
    private function renderLogin(?string $error = null)
    {
        ob_start();
        include __DIR__ . '/../Views/backoffice/login.php';
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * Renderizar dashboard
     *
     * @param array $stats Estadísticas
     */
    private function renderDashboard(array $stats)
    {
        ob_start();
        include __DIR__ . '/../Views/backoffice/dashboard.php';
        $content = ob_get_clean();
        echo $content;
    }

    /**
     * Responder con JSON
     *
     * @param bool $success Estado de la operación
     * @param string $message Mensaje
     * @param array $data Datos adicionales
     */
    private function jsonResponse(bool $success, string $message, array $data = [])
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
    }
}
