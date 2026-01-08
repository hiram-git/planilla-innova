<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\TenantStorage;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;

class EmployeeDocumentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'carta-trabajo' => 'Carta de trabajo',
        'carta-recomendacion' => 'Carta de recomendacion',
        'constancia-trabajo' => 'Constancia de trabajo',
        'contrato-trabajo' => 'Contrato de trabajo'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }

    public function index()
    {
        PermissionMiddleware::requirePermission('panel/employee-documents', 'read');

        $data = [
            'title' => 'Documentos laborales',
            'page_title' => 'Documentos laborales',
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employee-documents/index', $data);
    }

    public function datatablesAjax()
    {
        PermissionMiddleware::requirePermission('panel/employee-documents', 'read');

        header('Content-Type: application/json');

        try {
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 10);
            $searchValue = $_GET['search']['value'] ?? '';

            $orderColumn = intval($_GET['order'][0]['column'] ?? 1);
            $orderDir = $_GET['order'][0]['dir'] ?? 'asc';

            $columns = [
                0 => null,
                1 => 'employee_id',
                2 => 'firstname',
                3 => 'document_id',
                4 => 'position_name',
                5 => null
            ];

            $employee = $this->model('Employee');
            $totalRecords = $employee->count();

            $whereConditions = [];
            $params = [];

            $whereConditions[] = "employees.situacion_id = ?";
            $params[] = 1;

            $tipoPlanillaId = intval($_GET['tipo_planilla_id'] ?? 0);
            if ($tipoPlanillaId > 0) {
                $whereConditions[] = "FIND_IN_SET(?, employees.tipo_planilla_id)";
                $params[] = $tipoPlanillaId;
            }

            if (!empty($searchValue)) {
                $whereConditions[] = "(employees.firstname LIKE ? OR employees.lastname LIKE ? OR employees.document_id LIKE ? OR employees.employee_id LIKE ? OR posiciones.codigo LIKE ? OR cargos.nombre LIKE ?)";
                $searchParam = "%{$searchValue}%";
                $params = array_merge($params, array_fill(0, 6, $searchParam));
            }

            $employees = $this->getEmployeesWithPagination(
                $start,
                $length,
                $whereConditions,
                $params,
                $columns[$orderColumn] ?? null,
                $orderDir
            );

            $filteredRecords = $this->getFilteredEmployeesCount($whereConditions, $params);

            $companyModel = $this->model('Company');
            $companyConfig = $companyModel->getCompanyConfig();
            $isEmpresaConPosiciones = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';

            $documentsHtml = $this->renderDocumentBadges();

            $data = [];
            foreach ($employees as $emp) {
                $photoUrl = $emp['photo'] ? TenantStorage::getPublicImageUrl($emp['photo']) : '';
                $photo = $photoUrl ?: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFOUVDRUYiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNiIgcj0iNiIgZmlsbD0iIzZCN0I4NCIvPgo8cGF0aCBkPSJNMzAgMzJDMzAgMjYuNDc3MSAyNS41MjI5IDIyIDIwIDIyUzEwIDI2LjQ3NzEgMTAgMzJIMzBaIiBmaWxsPSIjNkI3Qjg0Ii8+Cjwvc3ZnPgo=';
                $photoHtml = '<img src="' . $photo . '" alt="Foto" class="img-circle" style="width: 40px; height: 40px; object-fit: cover;">';

                if ($isEmpresaConPosiciones) {
                    $conditionalColumn = htmlspecialchars($emp['position_name'] ?? 'Sin posicion');
                } else {
                    $conditionalColumn = htmlspecialchars($emp['cargo_name'] ?? 'Sin cargo');
                }

                $data[] = [
                    $photoHtml,
                    htmlspecialchars($emp['employee_id']),
                    htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']),
                    htmlspecialchars($emp['document_id'] ?? ''),
                    $conditionalColumn,
                    $documentsHtml
                ];
            }

            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $filteredRecords,
                "data" => $data
            ];

            echo json_encode($response);
        } catch (\Exception $e) {
            error_log("Error en EmployeeDocumentController@datatablesAjax: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error al cargar empleados: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    private function renderDocumentBadges(): string
    {
        $badges = [];

        foreach (self::DOCUMENT_TYPES as $label) {
            $badges[] = '<span class="badge badge-secondary mr-1" title="Plantilla pendiente">' . htmlspecialchars($label) . '</span>';
        }

        return implode(' ', $badges);
    }

    private function getEmployeesWithPagination($start, $length, $whereConditions, $params, $orderColumn, $orderDir)
    {
        $employee = $this->model('Employee');
        $db = $employee->getDatabase();
        $connection = $db->getConnection();

        $sql = "SELECT employees.*,
                       employees.id AS id,
                       posiciones.codigo AS position_name,
                       cargos.nombre AS cargo_name
                FROM employees
                LEFT JOIN posiciones ON posiciones.id = employees.position_id
                LEFT JOIN cargos ON cargos.id = employees.cargo_id";

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        if ($orderColumn && in_array($orderDir, ['asc', 'desc'], true)) {
            $sql .= " ORDER BY {$orderColumn} {$orderDir}";
        } else {
            $sql .= " ORDER BY employees.employee_id ASC";
        }

        $sql .= " LIMIT ?, ?";
        $params[] = $start;
        $params[] = $length;

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function getFilteredEmployeesCount($whereConditions, $params)
    {
        $employee = $this->model('Employee');
        $db = $employee->getDatabase();
        $connection = $db->getConnection();

        $sql = "SELECT COUNT(*) as total
                FROM employees
                LEFT JOIN posiciones ON posiciones.id = employees.position_id
                LEFT JOIN cargos ON cargos.id = employees.cargo_id";

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }

        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return intval($result['total'] ?? 0);
    }
}
