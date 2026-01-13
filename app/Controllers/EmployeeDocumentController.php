<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\TenantStorage;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\Company;
use App\Services\DocumentGenerators\WorkCertificatePdfGenerator;
use App\Services\DocumentGenerators\WorkCertificateWordGenerator;
use App\Services\DocumentGenerators\WorkContractPdfGenerator;
use App\Services\DocumentGenerators\WorkContractWordGenerator;

class EmployeeDocumentController extends Controller
{
    private const DOCUMENT_TYPES = [
        'work_certificate' => 'Carta de trabajo',
        'work_contract' => 'Contrato de trabajo'
    ];

    private $employeeModel;
    private $companyModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->employeeModel = new Employee();
        $this->companyModel = new Company();
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

                $documentsHtmlWithId = str_replace(
                    'generate-document',
                    'generate-document" data-employee-id="' . $emp['id'],
                    $documentsHtml
                );

                $data[] = [
                    $photoHtml,
                    htmlspecialchars($emp['employee_id']),
                    htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']),
                    htmlspecialchars($emp['document_id'] ?? ''),
                    $conditionalColumn,
                    $documentsHtmlWithId
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
        return '<div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-file-alt"></i> Documentos
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item generate-document" href="#" data-type="work_certificate" data-format="pdf">
                            <i class="far fa-file-pdf text-danger"></i> Carta de Trabajo (PDF)
                        </a>
                        <a class="dropdown-item generate-document" href="#" data-type="work_certificate" data-format="word">
                            <i class="far fa-file-word text-primary"></i> Carta de Trabajo (Word)
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item generate-document" href="#" data-type="work_contract" data-format="pdf">
                            <i class="far fa-file-pdf text-danger"></i> Contrato de Trabajo (PDF)
                        </a>
                        <a class="dropdown-item generate-document" href="#" data-type="work_contract" data-format="word">
                            <i class="far fa-file-word text-primary"></i> Contrato de Trabajo (Word)
                        </a>
                    </div>
                </div>';
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

    /**
     * Generar documento (router principal)
     */
    public function generate()
    {
        try {
            PermissionMiddleware::requirePermission('panel/employee-documents', 'read');

            // Obtener parámetros de la solicitud
            $employeeId = $_POST['employee_id'] ?? $_GET['employee_id'] ?? null;
            $documentType = $_POST['document_type'] ?? $_GET['document_type'] ?? null;
            $format = $_POST['format'] ?? $_GET['format'] ?? 'pdf';

            // Validar parámetros
            if (!$employeeId || !$documentType) {
                throw new \Exception('Parámetros inválidos: employee_id y document_type son requeridos');
            }

            // Validar tipo de documento
            if (!array_key_exists($documentType, self::DOCUMENT_TYPES)) {
                throw new \Exception('Tipo de documento inválido');
            }

            // Validar formato
            $validFormats = ['pdf', 'word'];
            if (!in_array($format, $validFormats)) {
                throw new \Exception('Formato inválido');
            }

            // Obtener datos del empleado
            $employeeData = $this->employeeModel->getEmployeeWithFullDetails($employeeId);

            if (!$employeeData) {
                throw new \Exception('Empleado no encontrado');
            }

            // Obtener información de la empresa
            $companyInfo = $this->getCompanyInfo();

            // Generar documento según tipo y formato
            $this->generateDocument($documentType, $format, $companyInfo, $employeeData);

        } catch (\Exception $e) {
            error_log('Error generando documento: ' . $e->getMessage());
            $this->redirect('/panel/employee-documents?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Generar documento específico
     *
     * @param string $type Tipo de documento (work_certificate | work_contract)
     * @param string $format Formato (pdf | word)
     * @param array $companyInfo Información de la empresa
     * @param array $employeeData Datos del empleado
     */
    private function generateDocument($type, $format, $companyInfo, $employeeData)
    {
        $generator = null;

        // Seleccionar generador según tipo y formato
        if ($type === 'work_certificate') {
            if ($format === 'pdf') {
                $generator = new WorkCertificatePdfGenerator($companyInfo, $employeeData);
            } else { // word
                $generator = new WorkCertificateWordGenerator($companyInfo, $employeeData);
            }
        } elseif ($type === 'work_contract') {
            if ($format === 'pdf') {
                $generator = new WorkContractPdfGenerator($companyInfo, $employeeData);
            } else { // word
                $generator = new WorkContractWordGenerator($companyInfo, $employeeData);
            }
        }

        if (!$generator) {
            throw new \Exception('No se pudo instanciar el generador de documentos');
        }

        // Generar y descargar
        $generator->generate();
    }

    /**
     * Obtener información de la empresa
     *
     * @return array
     */
    private function getCompanyInfo()
    {
        try {
            $db = $this->companyModel->getDatabase();
            $connection = $db->getConnection();

            $sql = "SELECT * FROM companies WHERE id = 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$company) {
                return $this->getDefaultCompanyInfo();
            }

            return [
                'company_name' => $company['company_name'] ?? 'EMPRESA EJEMPLO S.A.',
                'ruc' => $company['ruc'] ?? '1234567890-1-DV',
                'address' => $company['address'] ?? 'Dirección Empresa',
                'phone' => $company['phone'] ?? '',
                'email' => $company['email'] ?? '',
                'legal_representative' => $company['legal_representative'] ?? 'Representante Legal',
                'legal_representative_id' => $company['legal_representative_id'] ?? 'N/A',
                'currency_symbol' => $company['currency_symbol'] ?? 'B/.',
                'currency_code' => $company['currency_code'] ?? 'PAB',
                'logo_empresa' => $company['logo_empresa'] ?? '',
                'logo_izquierdo_reportes' => $company['logo_izquierdo_reportes'] ?? '',
                'logo_derecho_reportes' => $company['logo_derecho_reportes'] ?? ''
            ];
        } catch (\Exception $e) {
            error_log('Error obteniendo información de empresa: ' . $e->getMessage());
            return $this->getDefaultCompanyInfo();
        }
    }

    /**
     * Obtener información por defecto de la empresa
     *
     * @return array
     */
    private function getDefaultCompanyInfo()
    {
        return [
            'company_name' => 'EMPRESA EJEMPLO S.A.',
            'ruc' => '1234567890-1-DV',
            'address' => 'Dirección Empresa',
            'phone' => '',
            'email' => '',
            'legal_representative' => 'Representante Legal',
            'legal_representative_id' => 'N/A',
            'currency_symbol' => 'B/.',
            'currency_code' => 'PAB',
            'logo_empresa' => '',
            'logo_izquierdo_reportes' => '',
            'logo_derecho_reportes' => ''
        ];
    }
}
