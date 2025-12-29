<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Core\TenantStorage;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Models\Employee;
use App\Models\EmployeeFile;
use App\Models\EmployeeFileAttachment;

class EmployeeFileController extends Controller
{
    private Employee $employeeModel;
    private EmployeeFile $employeeFileModel;
    private EmployeeFileAttachment $attachmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->employeeModel = new Employee();
        $this->employeeFileModel = new EmployeeFile();
        $this->attachmentModel = new EmployeeFileAttachment();
    }

    public function index($employeeId = null)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'read');

        $employeeId = (int)$employeeId;
        if ($employeeId <= 0) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $employee = $this->employeeModel->getEmployeeWithFullDetails($employeeId);
        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $files = $this->employeeFileModel->getByEmployee($employeeId);
        $stats = $this->buildStats($files);

        $data = [
            'title' => 'Expedientes del Empleado',
            'page_title' => 'Expedientes',
            'employee' => $employee,
            'files' => $files,
            'stats' => $stats,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/expedientes', $data);
    }

    public function create($employeeId = null)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'write');

        $employeeId = (int)$employeeId;
        if ($employeeId <= 0) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $employee = $this->employeeModel->getEmployeeWithFullDetails($employeeId);
        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $types = $this->getTypes();

        $data = [
            'title' => 'Nuevo Expediente',
            'page_title' => 'Nuevo Expediente',
            'employee' => $employee,
            'types' => $types,
            'subtypes' => [],
            'file' => null,
            'attachments' => [],
            'dynamic_fields_html' => '',
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employee_files/form', $data);
    }

    public function store()
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'write');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        AuthMiddleware::validateCSRF();

        $input = Security::sanitizeInput($_POST);
        $employeeId = (int)($input['employee_id'] ?? 0);
        $typeId = (int)($input['type_id'] ?? 0);
        $subtypeId = (int)($input['subtype_id'] ?? 0);

        $employee = $this->employeeModel->getEmployeeWithFullDetails($employeeId);
        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $type = $this->getTypeById($typeId);
        $subtype = $this->getSubtypeById($subtypeId);

        $errors = $this->validateBaseFields($input, $type, $subtype);
        $extraFields = $this->filterExtraFields($type['name'] ?? '', $input['extra'] ?? []);

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect(\App\Core\UrlHelper::route('panel/employee-files/create/' . $employeeId));
        }

        $data = [
            'employee_id' => $employeeId,
            'type_id' => $typeId,
            'subtype_id' => $subtypeId,
            'document_date' => $input['document_date'] ?? date('Y-m-d'),
            'document_number' => $input['document_number'] ?? null,
            'observations' => $input['observations'] ?? null,
            'extra_fields' => !empty($extraFields) ? json_encode($extraFields, JSON_UNESCAPED_UNICODE) : null
        ];

        try {
            $fileId = $this->employeeFileModel->create($data);
            $uploadErrors = $this->handleUploads($fileId, $employeeId, $type['name'] ?? '');

            if (!empty($uploadErrors)) {
                $_SESSION['warning'] = implode(' ', $uploadErrors);
            }

            $_SESSION['success'] = 'Expediente creado correctamente.';
            $this->redirect(\App\Core\UrlHelper::employee($employeeId . '/expedientes'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al crear expediente: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::route('panel/employee-files/create/' . $employeeId));
        }
    }

    public function edit($id)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'write');

        $id = (int)$id;
        $file = $this->employeeFileModel->getByIdWithRelations($id);
        if (!$file) {
            $_SESSION['error'] = 'Expediente no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $employee = $this->employeeModel->getEmployeeWithFullDetails($file['employee_id']);
        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $types = $this->getTypes();
        $subtypes = $this->getSubtypesByType($file['type_id']);
        $attachments = $this->attachmentModel->getByEmployeeFile($id);
        $extraFields = $this->decodeExtraFields($file['extra_fields'] ?? null);
        $dynamicFieldsHtml = $this->renderDynamicFields($file['type_name'], $extraFields, $file['subtype_name']);

        $data = [
            'title' => 'Editar Expediente',
            'page_title' => 'Editar Expediente',
            'employee' => $employee,
            'types' => $types,
            'subtypes' => $subtypes,
            'file' => $file,
            'attachments' => $attachments,
            'dynamic_fields_html' => $dynamicFieldsHtml,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employee_files/form', $data);
    }

    public function update($id)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'write');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        AuthMiddleware::validateCSRF();

        $id = (int)$id;
        $file = $this->employeeFileModel->getByIdWithRelations($id);
        if (!$file) {
            $_SESSION['error'] = 'Expediente no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $input = Security::sanitizeInput($_POST);
        $employeeId = (int)($input['employee_id'] ?? $file['employee_id']);
        $typeId = (int)($input['type_id'] ?? $file['type_id']);
        $subtypeId = (int)($input['subtype_id'] ?? $file['subtype_id']);

        $type = $this->getTypeById($typeId);
        $subtype = $this->getSubtypeById($subtypeId);

        $errors = $this->validateBaseFields($input, $type, $subtype);
        $extraFields = $this->filterExtraFields($type['name'] ?? '', $input['extra'] ?? []);

        if (!empty($errors)) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect(\App\Core\UrlHelper::route('panel/employee-files/edit/' . $id));
        }

        $updateData = [
            'type_id' => $typeId,
            'subtype_id' => $subtypeId,
            'document_date' => $input['document_date'] ?? $file['document_date'],
            'document_number' => $input['document_number'] ?? null,
            'observations' => $input['observations'] ?? null,
            'extra_fields' => !empty($extraFields) ? json_encode($extraFields, JSON_UNESCAPED_UNICODE) : null
        ];

        try {
            $this->employeeFileModel->update($id, $updateData);

            $removeIds = array_map('intval', $input['remove_attachments'] ?? []);
            $this->removeAttachments($removeIds, $id);

            $uploadErrors = $this->handleUploads($id, $employeeId, $type['name'] ?? '');
            if (!empty($uploadErrors)) {
                $_SESSION['warning'] = implode(' ', $uploadErrors);
            }

            $_SESSION['success'] = 'Expediente actualizado correctamente.';
            $this->redirect(\App\Core\UrlHelper::employee($employeeId . '/expedientes'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar expediente: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::route('panel/employee-files/edit/' . $id));
        }
    }

    public function show($id)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'read');

        $id = (int)$id;
        $file = $this->employeeFileModel->getByIdWithRelations($id);
        if (!$file) {
            $_SESSION['error'] = 'Expediente no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $employee = $this->employeeModel->getEmployeeWithFullDetails($file['employee_id']);
        if (!$employee) {
            $_SESSION['error'] = 'Empleado no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $attachments = $this->attachmentModel->getByEmployeeFile($id);
        $extraFields = $this->decodeExtraFields($file['extra_fields'] ?? null);

        $data = [
            'title' => 'Detalle de Expediente',
            'page_title' => 'Detalle de Expediente',
            'employee' => $employee,
            'file' => $file,
            'attachments' => $attachments,
            'extra_fields' => $extraFields
        ];

        $this->view('admin/employee_files/show', $data);
    }

    public function delete($id)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'delete');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        AuthMiddleware::validateCSRF();

        $id = (int)$id;
        $file = $this->employeeFileModel->getByIdWithRelations($id);
        if (!$file) {
            $_SESSION['error'] = 'Expediente no encontrado.';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $employeeId = (int)$file['employee_id'];

        try {
            $attachments = $this->attachmentModel->getByEmployeeFile($id);
            $this->deleteAttachmentFiles($attachments);
            $this->employeeFileModel->delete($id);

            $_SESSION['success'] = 'Expediente eliminado correctamente.';
            $this->redirect(\App\Core\UrlHelper::employee($employeeId . '/expedientes'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al eliminar expediente: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::employee($employeeId . '/expedientes'));
        }
    }

    public function subtypes()
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'read');

        $typeId = (int)($_GET['type_id'] ?? 0);
        if ($typeId <= 0) {
            $this->json(['success' => false, 'data' => []]);
        }

        $subtypes = $this->getSubtypesByType($typeId);
        $this->json(['success' => true, 'data' => $subtypes]);
    }

    public function form()
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'read');

        $typeId = (int)($_GET['type_id'] ?? 0);
        $subtypeId = (int)($_GET['subtype_id'] ?? 0);
        $fileId = (int)($_GET['employee_file_id'] ?? 0);

        $type = $this->getTypeById($typeId);
        $subtype = $this->getSubtypeById($subtypeId);

        if (!$type || !$subtype || (int)$subtype['type_id'] !== $typeId) {
            $this->json(['success' => false, 'html' => '']);
        }

        $extraFields = [];
        if ($fileId > 0) {
            $file = $this->employeeFileModel->find($fileId);
            if ($file && (int)$file['type_id'] === $typeId && (int)$file['subtype_id'] === $subtypeId) {
                $extraFields = $this->decodeExtraFields($file['extra_fields'] ?? null);
            }
        }

        $html = $this->renderDynamicFields($type['name'], $extraFields, $subtype['name']);
        $this->json(['success' => true, 'html' => $html]);
    }

    public function downloadAttachment($id)
    {
        PermissionMiddleware::requirePermission('panel/employee-files', 'read');

        $id = (int)$id;
        $attachment = $this->attachmentModel->find($id);
        if (!$attachment) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            exit();
        }

        $path = $this->getAttachmentFullPath($attachment['file_path']);
        if (!is_file($path)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            exit();
        }

        $safeName = $this->sanitizeFileName((string)($attachment['original_name'] ?? 'archivo'));
        header('Content-Type: ' . ($attachment['mime_type'] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit();
    }

    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }

    private function getTypes(): array
    {
        $sql = "SELECT id, name
                FROM employee_file_types
                WHERE status = 1
                ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getTypeById(int $typeId): ?array
    {
        $sql = "SELECT id, name
                FROM employee_file_types
                WHERE id = ? AND status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$typeId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function getSubtypeById(int $subtypeId): ?array
    {
        $sql = "SELECT id, type_id, name
                FROM employee_file_subtypes
                WHERE id = ? AND status = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subtypeId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function getSubtypesByType(int $typeId): array
    {
        $sql = "SELECT id, name
                FROM employee_file_subtypes
                WHERE type_id = ? AND status = 1
                ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$typeId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function validateBaseFields(array $input, ?array $type, ?array $subtype): array
    {
        $errors = [];

        if (!$type) {
            $errors[] = 'Tipo de expediente inválido.';
        }

        if (!$subtype || ($type && (int)$subtype['type_id'] !== (int)$type['id'])) {
            $errors[] = 'Subtipo de expediente inválido.';
        }

        $documentDate = $input['document_date'] ?? '';
        if (!$this->isValidDate($documentDate)) {
            $errors[] = 'Fecha del documento inválida.';
        }

        return $errors;
    }

    private function buildStats(array $files): array
    {
        $types = [];
        $attachments = 0;

        foreach ($files as $file) {
            $types[$file['type_id']] = true;
            $attachments += (int)($file['attachments_count'] ?? 0);
        }

        return [
            'total_files' => count($files),
            'total_types' => count($types),
            'total_attachments' => $attachments
        ];
    }

    private function getDynamicFieldsConfig(): array
    {
        $paidLicenseCommon = [
            ['name' => 'start_date', 'label' => 'Fecha inicio', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Fecha fin', 'type' => 'date', 'required' => true],
            ['name' => 'total_days', 'label' => 'Total de dias concedidos', 'type' => 'number', 'step' => '1', 'required' => true],
            [
                'name' => 'paid_license',
                'label' => 'Goza de remuneracion',
                'type' => 'select',
                'options' => ['Si' => 'Si', 'No' => 'No'],
                'default' => 'Si',
                'required' => true
            ],
            ['name' => 'license_reason_detail', 'label' => 'Motivo', 'type' => 'textarea'],
            ['name' => 'authorized_by', 'label' => 'Autorizado por', 'type' => 'text'],
            ['name' => 'resolution_number', 'label' => 'Resolucion', 'type' => 'text'],
            ['name' => 'resolution_file', 'label' => 'Documento de respaldo', 'type' => 'file', 'accept' => '.pdf,image/*']
        ];

        $unpaidLicenseCommon = [
            ['name' => 'start_date', 'label' => 'Fecha inicio', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Fecha fin', 'type' => 'date', 'required' => true],
            ['name' => 'total_days', 'label' => 'Total de dias', 'type' => 'number', 'step' => '1', 'required' => true],
            [
                'name' => 'paid_license',
                'label' => 'Goza de remuneracion',
                'type' => 'text',
                'default' => 'No',
                'readonly' => true
            ],
            ['name' => 'license_reason_detail', 'label' => 'Motivo', 'type' => 'textarea'],
            ['name' => 'approved_by', 'label' => 'Aprobado por', 'type' => 'text'],
            ['name' => 'approval_document', 'label' => 'Documento de aprobacion', 'type' => 'file', 'accept' => '.pdf,image/*']
        ];

        $specialLicenseCommon = [
            ['name' => 'start_date', 'label' => 'Fecha inicio', 'type' => 'date', 'required' => true],
            ['name' => 'end_date', 'label' => 'Fecha fin (si aplica)', 'type' => 'date'],
            [
                'name' => 'total_days',
                'label' => 'Total de dias (o hasta recuperacion)',
                'type' => 'text',
                'placeholder' => 'Hasta recuperacion',
                'required' => true
            ],
            ['name' => 'diagnosis', 'label' => 'Diagnostico o justificacion medica', 'type' => 'textarea', 'required' => true],
            ['name' => 'medical_center', 'label' => 'Centro medico', 'type' => 'text'],
            ['name' => 'doctor_name', 'label' => 'Medico tratante', 'type' => 'text'],
            [
                'name' => 'medical_file',
                'label' => 'Dictamen medico o certificado',
                'type' => 'file',
                'accept' => '.pdf,image/*',
                'required' => true
            ]
        ];

        $specialProfessionalFields = [
            ['name' => 'professional_report', 'label' => 'Informe de accidente o enfermedad profesional', 'type' => 'textarea'],
            ['name' => 'issuing_entity', 'label' => 'Entidad que emite', 'type' => 'text']
        ];

        return [
            'Estudios Academicos' => [
                ['name' => 'institution', 'label' => 'Institución', 'type' => 'text', 'required' => true],
                ['name' => 'title_obtained', 'label' => 'Título obtenido', 'type' => 'text', 'required' => true],
                ['name' => 'start_date', 'label' => 'Fecha inicio', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'Fecha fin', 'type' => 'date'],
                ['name' => 'registration_number', 'label' => 'Número de registro', 'type' => 'text'],
                ['name' => 'resolution_number', 'label' => 'Número de resolución', 'type' => 'text'],
                ['name' => 'title_file', 'label' => 'Archivo del título', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Capacitación' => [
                ['name' => 'event_name', 'label' => 'Nombre del evento', 'type' => 'text', 'required' => true],
                ['name' => 'organizer', 'label' => 'Institución organizadora', 'type' => 'text'],
                ['name' => 'hours', 'label' => 'Horas de duración', 'type' => 'number', 'step' => '0.5'],
                ['name' => 'event_date', 'label' => 'Fecha del evento', 'type' => 'date'],
                ['name' => 'certificate_file', 'label' => 'Certificado', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Permisos' => [
                ['name' => 'start_datetime', 'label' => 'Fecha y hora inicio', 'type' => 'datetime'],
                ['name' => 'end_datetime', 'label' => 'Fecha y hora fin', 'type' => 'datetime'],
                ['name' => 'reason_detail', 'label' => 'Motivo detallado', 'type' => 'textarea'],
                ['name' => 'immediate_boss', 'label' => 'Jefe inmediato', 'type' => 'text'],
                ['name' => 'justification_file', 'label' => 'Archivo justificativo', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Amonestaciones' => [
                ['name' => 'incident_date', 'label' => 'Fecha del incidente', 'type' => 'date'],
                ['name' => 'incident_description', 'label' => 'Descripción del hecho', 'type' => 'textarea'],
                ['name' => 'resolution_number', 'label' => 'Resolución', 'type' => 'text'],
                ['name' => 'resolution_file', 'label' => 'Archivo de resolución', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Movimiento de Personal' => [
                ['name' => 'movement_date', 'label' => 'Fecha del movimiento', 'type' => 'date'],
                ['name' => 'previous_position', 'label' => 'Cargo/posición anterior', 'type' => 'text'],
                ['name' => 'new_position', 'label' => 'Nuevo cargo/posición', 'type' => 'text'],
                ['name' => 'resolution_number', 'label' => 'Resolución', 'type' => 'text'],
                ['name' => 'resolution_file', 'label' => 'Documento del movimiento', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Evaluación de Desempeño' => [
                ['name' => 'evaluation_period', 'label' => 'Período evaluado', 'type' => 'text'],
                ['name' => 'evaluation_date', 'label' => 'Fecha de evaluación', 'type' => 'date'],
                ['name' => 'evaluator', 'label' => 'Evaluador', 'type' => 'text'],
                ['name' => 'score', 'label' => 'Puntaje', 'type' => 'number', 'step' => '0.01']
            ],
            'Vacaciones' => [
                ['name' => 'period_start', 'label' => 'Período inicio', 'type' => 'date'],
                ['name' => 'period_end', 'label' => 'Período fin', 'type' => 'date'],
                ['name' => 'days_requested', 'label' => 'Días solicitados', 'type' => 'number', 'step' => '1'],
                ['name' => 'balance_before', 'label' => 'Saldo anterior', 'type' => 'number', 'step' => '0.01'],
                ['name' => 'balance_after', 'label' => 'Saldo posterior', 'type' => 'number', 'step' => '0.01']
            ],
            'Tiempo Compensatorio' => [
                ['name' => 'movement_date', 'label' => 'Fecha del movimiento', 'type' => 'date'],
                ['name' => 'hours_adjusted', 'label' => 'Horas ajustadas', 'type' => 'number', 'step' => '0.25'],
                ['name' => 'balance_before', 'label' => 'Saldo anterior', 'type' => 'number', 'step' => '0.25'],
                ['name' => 'balance_after', 'label' => 'Saldo posterior', 'type' => 'number', 'step' => '0.25']
            ],
            'Documento' => [
                ['name' => 'document_type', 'label' => 'Tipo de documento', 'type' => 'text'],
                ['name' => 'issue_date', 'label' => 'Fecha de emisión', 'type' => 'date'],
                ['name' => 'expiration_date', 'label' => 'Fecha de vencimiento', 'type' => 'date'],
                ['name' => 'issuing_authority', 'label' => 'Entidad emisora', 'type' => 'text'],
                ['name' => 'document_file', 'label' => 'Documento principal', 'type' => 'file', 'accept' => '.pdf,image/*']
            ],
            'Experiencia' => [
                ['name' => 'company_name', 'label' => 'Institución/empresa', 'type' => 'text'],
                ['name' => 'role', 'label' => 'Cargo desempeñado', 'type' => 'text'],
                ['name' => 'start_date', 'label' => 'Fecha inicio', 'type' => 'date'],
                ['name' => 'end_date', 'label' => 'Fecha fin', 'type' => 'date'],
                ['name' => 'reference_contact', 'label' => 'Referencia/Contacto', 'type' => 'text']
            ],
            'Licencias con Sueldo' => [
                'common' => $paidLicenseCommon,
                'subtypes' => [
                    'Representaci?n de la Instituci?n, Estado o Pa?s' => [
                        ['name' => 'representation_entity', 'label' => 'Institucion o evento representado', 'type' => 'text'],
                        ['name' => 'representation_place', 'label' => 'Lugar', 'type' => 'text'],
                        ['name' => 'representation_activity', 'label' => 'Descripcion de la actividad', 'type' => 'textarea']
                    ],
                    'Estudios' => [
                        ['name' => 'study_program', 'label' => 'Programa o cursos', 'type' => 'text'],
                        ['name' => 'study_institution', 'label' => 'Institucion educativa', 'type' => 'text'],
                        [
                            'name' => 'study_schedule',
                            'label' => 'Horario',
                            'type' => 'select',
                            'options' => ['Manana' => 'Manana', 'Tarde' => 'Tarde', 'Noche' => 'Noche'],
                            'placeholder' => 'Seleccione...'
                        ]
                    ],
                    'Representaci?n de la asociaci?n de servidor' => [
                        ['name' => 'association_name', 'label' => 'Nombre de la asociacion', 'type' => 'text'],
                        ['name' => 'association_role', 'label' => 'Cargo que representa', 'type' => 'text'],
                        ['name' => 'association_activity', 'label' => 'Actividad gremial', 'type' => 'textarea']
                    ],
                    'Capacitaci?n' => [
                        ['name' => 'training_name', 'label' => 'Nombre del curso o evento', 'type' => 'text'],
                        ['name' => 'training_organizer', 'label' => 'Entidad organizadora', 'type' => 'text'],
                        ['name' => 'training_hours', 'label' => 'Duracion en horas', 'type' => 'number', 'step' => '0.5']
                    ],
                    'RAZONES EXTRAORDINARIAS' => [
                        ['name' => 'extraordinary_reason', 'label' => 'Descripcion de la razon extraordinaria', 'type' => 'textarea']
                    ]
                ]
            ],
            'Licencias sin Sueldo' => [
                'common' => $unpaidLicenseCommon,
                'subtypes' => [
                    'Asumir cargo de elecci?n popular' => [
                        ['name' => 'public_position', 'label' => 'Cargo publico', 'type' => 'text'],
                        ['name' => 'public_entity', 'label' => 'Entidad', 'type' => 'text'],
                        ['name' => 'public_term', 'label' => 'Periodo del cargo', 'type' => 'text']
                    ],
                    'Asuntos Personales' => [
                        ['name' => 'personal_reason', 'label' => 'Motivo detallado', 'type' => 'textarea']
                    ],
                    'Asumir cargo de libre nobramiento y remoci?n' => [
                        ['name' => 'appointment_position', 'label' => 'Cargo', 'type' => 'text'],
                        ['name' => 'appointment_institution', 'label' => 'Institucion', 'type' => 'text'],
                        [
                            'name' => 'appointment_file',
                            'label' => 'Copia del nombramiento',
                            'type' => 'file',
                            'accept' => '.pdf,image/*',
                            'required' => true
                        ]
                    ],
                    'Estudiar' => [
                        ['name' => 'study_program', 'label' => 'Programa de estudios', 'type' => 'text'],
                        ['name' => 'study_institution', 'label' => 'Institucion', 'type' => 'text'],
                        ['name' => 'study_duration', 'label' => 'Duracion del programa', 'type' => 'text']
                    ]
                ]
            ],
            'Licencias Especiales' => [
                'common' => $specialLicenseCommon,
                'subtypes' => [
                    'Enfermedad Profesional' => $specialProfessionalFields,
                    'Riesgos Profesionales' => $specialProfessionalFields,
                    'Enfermedad/Incapacidad superior quince d?as' => [
                        ['name' => 'rest_days', 'label' => 'Dias de reposo prescritos', 'type' => 'number', 'step' => '1']
                    ],
                    'Gravidez' => [
                        ['name' => 'due_date', 'label' => 'Fecha probable de parto', 'type' => 'date'],
                        ['name' => 'gestation_weeks', 'label' => 'Semanas de gestacion', 'type' => 'number', 'step' => '1'],
                        [
                            'name' => 'maternity_stage',
                            'label' => 'Prenatal/Postnatal',
                            'type' => 'select',
                            'options' => ['Prenatal' => 'Prenatal', 'Postnatal' => 'Postnatal'],
                            'placeholder' => 'Seleccione...'
                        ]
                    ]
                ]
            ],
        ];
    }

    private function getDynamicFieldsForRender(string $typeName, string $subtypeName = ''): array
    {
        $config = $this->getDynamicFieldsConfig();
        if (empty($config[$typeName])) {
            return [];
        }

        $typeConfig = $config[$typeName];
        if (array_key_exists(0, $typeConfig)) {
            return $typeConfig;
        }

        $fields = $typeConfig['common'] ?? [];
        $subtypes = $typeConfig['subtypes'] ?? [];
        if ($subtypeName && isset($subtypes[$subtypeName])) {
            $fields = array_merge($fields, $subtypes[$subtypeName]);
        }

        return $fields;
    }

    private function getDynamicFieldsForType(string $typeName): array
    {
        $config = $this->getDynamicFieldsConfig();
        if (empty($config[$typeName])) {
            return [];
        }

        $typeConfig = $config[$typeName];
        if (array_key_exists(0, $typeConfig)) {
            return $typeConfig;
        }

        $fields = $typeConfig['common'] ?? [];
        foreach (($typeConfig['subtypes'] ?? []) as $subtypeFields) {
            $fields = array_merge($fields, $subtypeFields);
        }

        return $fields;
    }

    private function renderDynamicFields(string $typeName, array $values = [], string $subtypeName = ''): string
    {
        $fields = $this->getDynamicFieldsForRender($typeName, $subtypeName);

        if (empty($fields)) {
            return '<div class="callout callout-info">No hay campos adicionales para este tipo de expediente.</div>';
        }

        ob_start();
        ?>
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Formulario específico: <?= htmlspecialchars($typeName) ?> / <?= htmlspecialchars($subtypeName) ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($fields as $field): ?>
                        <?php
                        $fieldName = $field['name'];
                        $fieldLabel = $field['label'] ?? $fieldName;
                        $fieldType = $field['type'] ?? 'text';
                        $required = !empty($field['required']) ? 'required' : '';
                        $readonly = !empty($field['readonly']) ? 'readonly' : '';
                        $placeholder = $field['placeholder'] ?? '';
                        $placeholderAttr = $placeholder !== '' ? 'placeholder="' . htmlspecialchars($placeholder) . '"' : '';
                        $value = $values[$fieldName] ?? ($field['default'] ?? '');
                        $columnClass = $fieldType === 'textarea' ? 'col-md-12' : 'col-md-6';
                        ?>
                        <div class="<?= $columnClass ?>">
                            <div class="form-group">
                                <label><?= htmlspecialchars($fieldLabel) ?></label>
                                <?php if ($fieldType === 'textarea'): ?>
                                    <textarea name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control" rows="3" <?= $required ?> <?= $readonly ?> <?= $placeholderAttr ?>><?= htmlspecialchars($value) ?></textarea>
                                <?php elseif ($fieldType === 'date'): ?>
                                    <input type="text" name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control date-picker" value="<?= htmlspecialchars($value) ?>" <?= $required ?> <?= $readonly ?> <?= $placeholderAttr ?>>
                                <?php elseif ($fieldType === 'datetime'): ?>
                                    <input type="text" name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control datetime-picker" value="<?= htmlspecialchars($value) ?>" <?= $required ?> <?= $readonly ?> <?= $placeholderAttr ?>>
                                <?php elseif ($fieldType === 'number'): ?>
                                    <input type="number" step="<?= htmlspecialchars($field['step'] ?? '1') ?>" name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control" value="<?= htmlspecialchars($value) ?>" <?= $required ?> <?= $readonly ?> <?= $placeholderAttr ?>>
                                <?php elseif ($fieldType === 'select'): ?>
                                    <?php $options = $field['options'] ?? []; ?>
                                    <select name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control" <?= $required ?>>
                                        <?php if ($placeholder !== ''): ?>
                                            <option value="" <?= $value === '' ? 'selected' : '' ?>><?= htmlspecialchars($placeholder) ?></option>
                                        <?php endif; ?>
                                        <?php foreach ($options as $optionValue => $optionLabel): ?>
                                            <?php
                                            if (is_int($optionValue)) {
                                                $optionValue = $optionLabel;
                                            }
                                            $selected = ((string)$value !== '' && (string)$value === (string)$optionValue) ? 'selected' : '';
                                            ?>
                                            <option value="<?= htmlspecialchars((string)$optionValue) ?>" <?= $selected ?>><?= htmlspecialchars((string)$optionLabel) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($fieldType === 'file'): ?>
                                    <div class="custom-file">
                                        <input type="file" name="<?= htmlspecialchars($fieldName) ?>" class="custom-file-input" accept="<?= htmlspecialchars($field['accept'] ?? '.pdf,image/*') ?>" <?= $required ?>>
                                        <label class="custom-file-label">Seleccionar archivo</label>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="extra[<?= htmlspecialchars($fieldName) ?>]" class="form-control" value="<?= htmlspecialchars($value) ?>" <?= $required ?> <?= $readonly ?> <?= $placeholderAttr ?>>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function filterExtraFields(string $typeName, array $extraFields): array
    {
        $fields = $this->getDynamicFieldsForType($typeName);
        $allowed = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'file') {
                continue;
            }
            $name = $field['name'];
            if (array_key_exists($name, $extraFields)) {
                $allowed[$name] = $extraFields[$name];
            }
        }

        return $allowed;
    }

    private function decodeExtraFields(?string $extraFields): array
    {
        if (!$extraFields) {
            return [];
        }

        $decoded = json_decode($extraFields, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function handleUploads(int $fileId, int $employeeId, string $typeName): array
    {
        $errors = [];

        if (isset($_FILES['attachments'])) {
            $files = $this->normalizeFilesArray($_FILES['attachments']);
            foreach ($files as $file) {
                $result = $this->storeUploadedFile($file, $employeeId, 'adjunto');
                if (!empty($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                    continue;
                }

                $this->attachmentModel->create(array_merge($result['data'], [
                    'employee_file_id' => $fileId
                ]));
            }
        }

        $fields = $this->getDynamicFieldsForType($typeName);
        foreach ($fields as $field) {
            if (($field['type'] ?? '') !== 'file') {
                continue;
            }
            $inputName = $field['name'];
            if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = $this->storeUploadedFile($_FILES[$inputName], $employeeId, $inputName);
            if (!empty($result['errors'])) {
                $errors = array_merge($errors, $result['errors']);
                continue;
            }

            $this->attachmentModel->create(array_merge($result['data'], [
                'employee_file_id' => $fileId
            ]));
        }

        return $errors;
    }

    private function storeUploadedFile(array $file, int $employeeId, string $label): array
    {
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;
        $uploadErrors = Security::checkFileUpload($file, $allowedTypes, $maxSize);

        if (!empty($uploadErrors)) {
            return ['errors' => $uploadErrors];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $relativeDir = 'employee_files/employee_' . $employeeId . '/';
        $baseDir = $this->getAttachmentBasePath();
        $fullDir = $baseDir . $relativeDir;
        TenantStorage::ensureDirectory($fullDir);

        $safeName = $this->sanitizeFileName($file['name']);
        $fileName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
        $targetPath = $fullDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['errors' => ['No se pudo guardar el archivo adjunto.']];
        }

        return [
            'data' => [
                'label' => $label,
                'file_path' => $relativeDir . $fileName,
                'original_name' => $file['name'],
                'mime_type' => $mimeType,
                'file_size' => (int)$file['size']
            ]
        ];
    }

    private function removeAttachments(array $ids, int $fileId): void
    {
        if (empty($ids)) {
            return;
        }

        $attachments = $this->attachmentModel->getByIds($ids);
        $attachments = array_filter($attachments, static function ($attachment) use ($fileId) {
            return (int)$attachment['employee_file_id'] === $fileId;
        });

        $this->deleteAttachmentFiles($attachments);
        $attachmentIds = array_column($attachments, 'id');
        $this->attachmentModel->deleteByIds($attachmentIds);
    }

    private function deleteAttachmentFiles(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $this->getAttachmentFullPath($attachment['file_path']);
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function getAttachmentBasePath(): string
    {
        $tenantKey = TenantStorage::getTenantKey();
        return __DIR__ . '/../../storage/tenants/' . $tenantKey . '/files/';
    }

    private function getAttachmentFullPath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        return $this->getAttachmentBasePath() . ltrim($relativePath, '/');
    }

    private function sanitizeFileName(string $fileName): string
    {
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        if (strlen($fileName) > 100) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $baseName = substr($fileName, 0, 90);
            $fileName = $baseName . '.' . $extension;
        }
        return $fileName;
    }

    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];
        if (!isset($files['name']) || !is_array($files['name'])) {
            return $normalized;
        }

        foreach ($files['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0
            ];
        }

        return $normalized;
    }

    private function isValidDate($date): bool
    {
        if (!$date) {
            return false;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
