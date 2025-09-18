<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Helpers\PermissionHelper;

class Employee extends Controller
{
    public function __construct()
    {
        parent::__construct(); // ✅ Inicializar $this->db desde Controller base
        $this->requireAuth();
    }

    public function index()
    {
        $data = [
            'title' => 'Gestión de Empleados',
            'page_title' => 'Colaboradores',
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/index', $data);
    }

    public function create()
    {
        $position = $this->model('Posicion');
        $schedule = $this->model('Schedule');
        $situacion = $this->model('Situacion');
        $tipoPlanilla = $this->model('TipoPlanilla');
        $company = $this->model('Company');
        $cargo = $this->model('Cargo');
        $funcion = $this->model('Funcion');
        $partida = $this->model('Partida');
        $organigrama = $this->model('Organizational');

        $data = [
            'title' => 'Agregar Empleado',
            'page_title' => 'Agregar Colaborador',
            'positions' => $position->all(),
            'schedules' => $schedule->all(),
            'situaciones' => $situacion->all(),
            'tipos_planilla' => $tipoPlanilla->all(),
            'cargos' => $cargo->all(),
            'funciones' => $funcion->all(),
            'partidas' => $partida->all(),
            'organigrama_elementos' => $organigrama->getOrganizationalFlat(),
            'company_config' => $company->getCompanyConfig(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/create', $data);
    }

    public function show($id)
    {
        $employee = $this->model('Employee');
        
        $employeeData = $employee->getEmployeeWithFullDetails($id);
        
        if (!$employeeData) {
            $_SESSION['error_message'] = 'Empleado no encontrado';
            $this->redirect(\App\Core\UrlHelper::employee());
            return;
        }

        $data = [
            'title' => 'Detalles del Empleado',
            'page_title' => 'Ver Colaborador',
            'employee' => $employeeData,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/show', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $employee = $this->model('Employee');

        // Validación básica
        $errors = $employee->validateEmployeeData($data);

        // Validar unicidad de documento
        if (isset($data['document_id']) && !$employee->isDocumentIdUnique($data['document_id'])) {
            $errors['document_id'] = 'El número de cédula ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::employee('create'));
        }

        try {
            // Manejar subida de foto
            $photoFilename = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoErrors = Security::checkFileUpload($_FILES['photo'], ['image/jpeg', 'image/png', 'image/gif'], 2097152);
                if (empty($photoErrors)) {
                    $photoFilename = $this->uploadPhoto($_FILES['photo']);
                } else {
                    $_SESSION['error'] = 'Error en la foto: ' . implode(', ', $photoErrors);
                    $this->redirect(\App\Core\UrlHelper::employee('create'));
                }
            }

            // Generar ID de empleado automáticamente
            $employeeId = $employee->generateEmployeeId();

            // Preparar datos para inserción
            $employeeData = [
                'employee_id' => $employeeId,
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'document_id' => $data['document_id'],
                'clave_seguro_social' => $data['clave_seguro_social'] ?? null,
                'address' => $data['address'] ?? '',
                'birthdate' => $data['birthdate'],
                'fecha_ingreso' => $data['fecha_ingreso'] ?? date('Y-m-d'),
                'contact_info' => $data['contact'] ?? '',
                'gender' => $data['gender'],
                'position_id' => !empty($data['position']) ? $data['position'] : null,
                'schedule_id' => $data['schedule'],
                'situacion_id' => $data['situacion'] ?? null,
                'tipo_planilla_id' => $data['tipo_planilla'] ?? null,
                'sueldo_individual' => !empty($data['sueldo_individual']) ? (float)$data['sueldo_individual'] : null,
                'cargo_id' => !empty($data['cargo_id']) ? $data['cargo_id'] : null,
                'funcion_id' => !empty($data['funcion_id']) ? $data['funcion_id'] : null,
                'partida_id' => !empty($data['partida_id']) ? $data['partida_id'] : null,
                'photo' => $photoFilename,
                'organigrama_id' => !empty($data['organigrama_id']) ? $data['organigrama_id'] : null,
                'created_on' => date('Y-m-d')
            ];

            $employee->create($employeeData);
            
            $_SESSION['success'] = 'Colaborador agregado exitosamente con ID: ' . $employeeId;
            $this->redirect(\App\Core\UrlHelper::employee());
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al agregar colaborador: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::employee('create'));
        }
    }

    public function edit($id)
    {
        $employee = $this->model('Employee');
        $position = $this->model('Posicion');
        $schedule = $this->model('Schedule');
        $situacion = $this->model('Situacion');
        $tipoPlanilla = $this->model('TipoPlanilla');
        $company = $this->model('Company');
        $cargo = $this->model('Cargo');
        $funcion = $this->model('Funcion');
        $partida = $this->model('Partida');
        $organigrama = $this->model('Organizational');

        $employeeData = $employee->getEmployeeWithFullDetails($id);
        if (!$employeeData) {
            $_SESSION['error'] = 'Empleado no encontrado';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        $data = [
            'title' => 'Editar Empleado',
            'page_title' => 'Editar Colaborador',
            'employee' => $employeeData,
            'positions' => $position->all(),
            'schedules' => $schedule->all(),
            'situaciones' => $situacion->all(),
            'tipos_planilla' => $tipoPlanilla->all(),
            'cargos' => $cargo->all(),
            'funciones' => $funcion->all(),
            'partidas' => $partida->all(),
            'organigrama_elementos' => $organigrama->getOrganizationalFlat(),
            'company_config' => $company->getCompanyConfig(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/employees/edit', $data);
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $employee = $this->model('Employee');

        $employeeData = $employee->find($id);
        if (!$employeeData) {
            $_SESSION['error'] = 'Empleado no encontrado';
            $this->redirect(\App\Core\UrlHelper::employee());
        }

        // Validación con campos de edición  
        $errors = $employee->validateEmployeeUpdateData($data);

        // Validar unicidad de documento (excluyendo el actual)
        if (isset($data['edit_document_id']) && !$employee->isDocumentIdUnique($data['edit_document_id'], $id)) {
            $errors['edit_document_id'] = 'El número de cédula ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect(\App\Core\UrlHelper::employee("edit/$id"));
        }

        try {
            // Manejar actualización de foto
            $photoFilename = $employeeData['photo'];
            if (isset($_FILES['edit_photo']) && $_FILES['edit_photo']['error'] === UPLOAD_ERR_OK) {
                $photoErrors = Security::checkFileUpload($_FILES['edit_photo'], ['image/jpeg', 'image/png', 'image/gif'], 2097152);
                if (empty($photoErrors)) {
                    $newPhoto = $this->uploadPhoto($_FILES['edit_photo']);
                    if ($newPhoto) {
                        // Eliminar foto anterior si existe
                        if ($photoFilename && file_exists("./images/$photoFilename")) {
                            unlink("./images/$photoFilename");
                        }
                        $photoFilename = $newPhoto;
                    }
                }
            }

            // Preparar datos para actualización
            $updateData = [
                'firstname' => $data['edit_firstname'],
                'lastname' => $data['edit_lastname'],
                'document_id' => $data['edit_document_id'],
                'clave_seguro_social' => $data['edit_clave_seguro_social'] ?? null,
                'address' => $data['edit_address'] ?? '',
                'birthdate' => $data['edit_birthdate'],
                'fecha_ingreso' => $data['edit_fecha_ingreso'] ?? $employeeData['fecha_ingreso'],
                'contact_info' => $data['edit_contact'] ?? '',
                'gender' => $data['edit_gender'],
                'position_id' => !empty($data['edit_position']) ? $data['edit_position'] : null,
                'schedule_id' => $data['edit_schedule'],
                'situacion_id' => $data['edit_situacion'] ?? null,
                'tipo_planilla_id' => $data['edit_tipo_planilla'] ?? null,
                'sueldo_individual' => !empty($data['edit_sueldo_individual']) ? (float)$data['edit_sueldo_individual'] : null,
                'cargo_id' => !empty($data['edit_cargo_id']) ? $data['edit_cargo_id'] : null,
                'funcion_id' => !empty($data['edit_funcion_id']) ? $data['edit_funcion_id'] : null,
                'partida_id' => !empty($data['edit_partida_id']) ? $data['edit_partida_id'] : null,
                'photo' => $photoFilename,
                'organigrama_id' => !empty($data['edit_organigrama_id']) ? $data['edit_organigrama_id'] : null
            ];

            $employee->update($id, $updateData);
            
            $_SESSION['success'] = 'Colaborador actualizado exitosamente';
            $this->redirect(\App\Core\UrlHelper::employee());
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar colaborador: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::employee("edit/$id"));
        }
    }

    public function delete($id)
    {
        AuthMiddleware::requireAuth();
        
        $employee = $this->model('Employee');
        
        try {
            $employeeData = $employee->find($id);
            if (!$employeeData) {
                $_SESSION['error'] = 'Empleado no encontrado';
                $this->redirect(\App\Core\UrlHelper::employee());
            }

            // Verificar si tiene asistencias registradas
            $attendance = $this->model('Attendance');
            $attendanceRecords = $attendance->where('employee_id', $id);
            
            if (!empty($attendanceRecords)) {
                $_SESSION['error'] = 'No se puede eliminar el empleado porque tiene registros de asistencia';
                $this->redirect(\App\Core\UrlHelper::employee());
                return;
            }

            // Verificar si está en planillas generadas
            $payrollDetail = $this->model('PayrollDetail');
            $payrollRecords = $payrollDetail->where('employee_id', $id);
            
            if (!empty($payrollRecords)) {
                $_SESSION['error'] = 'No se puede eliminar el empleado porque está incluido en planillas generadas';
                $this->redirect(\App\Core\UrlHelper::employee());
                return;
            } else {
                // Eliminar foto si existe
                if ($employeeData['photo'] && file_exists("./images/{$employeeData['photo']}")) {
                    unlink("./images/{$employeeData['photo']}");
                }
                
                $employee->delete($id);
                $_SESSION['success'] = 'Colaborador eliminado exitosamente';
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al eliminar colaborador';
        }

        $this->redirect(\App\Core\UrlHelper::employee());
    }

    // API endpoints para AJAX
    public function getRow()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $id = $_POST['id'] ?? '';
        if (empty($id)) {
            $this->json(['error' => 'ID requerido'], 400);
        }

        $employee = $this->model('Employee');
        $employeeData = $employee->getEmployeeWithFullDetails($id);

        if ($employeeData) {
            $this->json($employeeData);
        } else {
            $this->json(['error' => 'Empleado no encontrado'], 404);
        }
    }

    public function getPositionDetails()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        $positionId = $_POST['position_id'] ?? '';
        if (empty($positionId)) {
            $this->json(['error' => 'ID de posición requerido'], 400);
        }

        $position = $this->model('Posicion');
        $positionData = $position->getPositionWithRelations($positionId);

        if ($positionData) {
            $this->json([
                'partida' => $positionData['descripcion_partida'] ?? '',
                'cargo' => $positionData['descripcion_cargo'] ?? '',
                'funcion' => $positionData['descripcion_funcion'] ?? '',
                'sueldo' => $positionData['sueldo'] ?? 0
            ]);
        } else {
            $this->json(['error' => 'Posición no encontrada'], 404);
        }
    }

    private function uploadPhoto($file)
    {
        $uploadDir = './images/';
        $fileName = time() . '_' . $this->sanitizeFileName($file['name']);
        $uploadPath = $uploadDir . $fileName;

        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $fileName;
        }
        
        return false;
    }
    
    /**
     * Sanitizar nombre de archivo
     */
    private function sanitizeFileName($fileName) 
    {
        // Remover caracteres especiales y espacios
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        // Evitar nombres muy largos
        if (strlen($fileName) > 100) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $baseName = substr($fileName, 0, 90);
            $fileName = $baseName . '.' . $extension;
        }
        return $fileName;
    }

    private function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }

    /**
     * API: Endpoint para DataTables - Carga AJAX con paginación, búsqueda y ordenamiento server-side
     */
    public function datatablesAjax()
    {
        header('Content-Type: application/json');
        
        try {
            // Parámetros de DataTables
            $draw = intval($_GET['draw'] ?? 1);
            $start = intval($_GET['start'] ?? 0);
            $length = intval($_GET['length'] ?? 10);
            $searchValue = $_GET['search']['value'] ?? '';
            
            // Parámetros de ordenamiento
            $orderColumn = intval($_GET['order'][0]['column'] ?? 1);
            $orderDir = $_GET['order'][0]['dir'] ?? 'asc';
            
            // Mapeo de columnas para ordenamiento
            $columns = [
                0 => null, // Foto - no ordenable
                1 => 'employee_id',
                2 => 'firstname', // Para nombre completo usaremos firstname
                3 => 'document_id',
                4 => 'position_name',
                5 => null, // Horario - no ordenable
                6 => 'created_on',
                7 => null  // Acciones - no ordenable
            ];
            
            $employee = $this->model('Employee');
            
            // Obtener total de registros sin filtro
            $totalRecords = $employee->count();
            
            // Construir consulta con filtros
            $whereConditions = [];
            $params = [];
            
            // Filtrar solo empleados con situación válida (no nulos)
            $whereConditions[] = "employees.situacion_id IS NOT NULL";
            
            // Filtrar por tipo de planilla si se proporciona desde el navbar
            $tipoPlanillaId = intval($_GET['tipo_planilla_id'] ?? 0);
            if ($tipoPlanillaId > 0) {
                $whereConditions[] = "employees.tipo_planilla_id = ?";
                $params[] = $tipoPlanillaId;
            }
            
            if (!empty($searchValue)) {
                $whereConditions[] = "(employees.firstname LIKE ? OR employees.lastname LIKE ? OR employees.document_id LIKE ? OR employees.employee_id LIKE ? OR posiciones.codigo LIKE ? OR cargos.descripcion LIKE ?)";
                $searchParam = "%{$searchValue}%";
                $params = array_merge($params, array_fill(0, 6, $searchParam));
            }
            
            // Obtener datos paginados
            $employees = $this->getEmployeesWithPagination($start, $length, $whereConditions, $params, $columns[$orderColumn], $orderDir);
            
            // Contar registros filtrados
            $filteredRecords = $this->getFilteredEmployeesCount($whereConditions, $params);
            
            // Obtener tipo de empresa para mostrar columna condicional
            $companyModel = $this->model('Company');
            $companyConfig = $companyModel->getCompanyConfig();
            $isPublicInstitution = ($companyConfig['tipo_institucion'] ?? 'privada') === 'publica';
            
            $data = [];
            foreach ($employees as $emp) {
                $schedule = $emp['time_in'] && $emp['time_out'] 
                    ? date('h:i A', strtotime($emp['time_in'])) . ' - ' . date('h:i A', strtotime($emp['time_out']))
                    : 'Sin horario';
                
                $photo = $emp['photo'] ? \App\Core\UrlHelper::url('images/' . $emp['photo']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiNFOUVDRUYiLz4KPGNpcmNsZSBjeD0iMjAiIGN5PSIxNiIgcj0iNiIgZmlsbD0iIzZCN0I4NCIvPgo8cGF0aCBkPSJNMzAgMzJDMzAgMjYuNDc3MSAyNS41MjI5IDIyIDIwIDIyUzEwIDI2LjQ3NzEgMTAgMzJIMzBaIiBmaWxsPSIjNkI3Qjg0Ii8+Cjwvc3ZnPgo=';
                
                $photoHtml = '<img src="' . $photo . '" alt="Foto" class="img-circle" style="width: 40px; height: 40px; object-fit: cover;">';
                
                // ✅ NUEVO: Botones CRUD con permisos granulares
                $employeeName = htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']);
                $actionsHtml = '';
                
                // Botón Ver
                if (PermissionHelper::canRead('employees')) {
                    $actionsHtml .= '<a href="' . \App\Core\UrlHelper::employee($emp['id']) . '" class="btn btn-info btn-sm" title="Ver">
                                        <i class="fas fa-eye"></i>
                                     </a> ';
                }
                
                // Botón Editar
                if (PermissionHelper::canWrite('employees')) {
                    $actionsHtml .= '<a href="' . \App\Core\UrlHelper::employee('edit/' . $emp['id']) . '" class="btn btn-warning btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                     </a> ';
                }
                
                // Botón Eliminar con nombre del empleado
                if (PermissionHelper::canDelete('employees')) {
                    $actionsHtml .= '<button type="button" class="btn btn-danger btn-sm delete-btn" 
                                            data-id="' . $emp['id'] . '" 
                                            data-name="' . $employeeName . '" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                     </button> ';
                }
                
                // Si no tiene permisos, mostrar solo ver (si lo tiene)
                if (empty(trim($actionsHtml)) && PermissionHelper::canRead('employees')) {
                    $actionsHtml = '<button class="btn btn-sm btn-info view-btn" data-id="' . $emp['id'] . '" title="Ver">
                        <i class="fas fa-eye"></i>
                    </button>';
                }
                
                // Determinar qué mostrar en la columna condicional según tipo de empresa
                $conditionalColumn = '';
                if ($isPublicInstitution) {
                    // Empresa pública: mostrar posición
                    $conditionalColumn = htmlspecialchars($emp['position_name'] ?? 'Sin posición');
                } else {
                    // Empresa privada: mostrar cargo
                    $conditionalColumn = htmlspecialchars($emp['cargo_name'] ?? 'Sin cargo');
                }
                
                $data[] = [
                    $photoHtml,
                    htmlspecialchars($emp['employee_id']),
                    htmlspecialchars($emp['firstname'] . ' ' . $emp['lastname']),
                    htmlspecialchars($emp['document_id'] ?? ''),
                    $conditionalColumn,
                    $schedule,
                    date('d/m/Y', strtotime($emp['created_on'])),
                    $actionsHtml
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
            error_log("Error en Employee@datatablesAjax: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error al cargar empleados: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Método auxiliar para obtener empleados con paginación y filtros
     */
    private function getEmployeesWithPagination($start, $length, $whereConditions, $params, $orderColumn, $orderDir)
    {
        $employee = $this->model('Employee');
        $db = $employee->getDatabase();
        $connection = $db->getConnection();
        
        $sql = "SELECT employees.*, 
                       employees.id AS id,
                       posiciones.codigo AS position_name,
                       cargos.descripcion AS cargo_name,
                       schedules.time_in, 
                       schedules.time_out
                FROM employees 
                LEFT JOIN posiciones ON posiciones.id = employees.position_id 
                LEFT JOIN cargos ON cargos.id = employees.cargo_id
                LEFT JOIN schedules ON schedules.id = employees.schedule_id";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        if ($orderColumn && in_array($orderDir, ['asc', 'desc'])) {
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
    
    /**
     * Método auxiliar para contar empleados filtrados
     */
    private function getFilteredEmployeesCount($whereConditions, $params)
    {
        $employee = $this->model('Employee');
        $db = $employee->getDatabase();
        $connection = $db->getConnection();
        
        $sql = "SELECT COUNT(*) as total
                FROM employees 
                LEFT JOIN posiciones ON posiciones.id = employees.position_id 
                LEFT JOIN cargos ON cargos.id = employees.cargo_id
                LEFT JOIN schedules ON schedules.id = employees.schedule_id";
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return intval($result['total'] ?? 0);
    }

    /**
     * API: Obtener opciones de empleados para select
     */
    public function getOptions()
    {
        try {
            // Parámetros para paginación y búsqueda de Select2
            $search = $_GET['q'] ?? '';
            $page = intval($_GET['page'] ?? 1);
            $limit = 30; // Límite de resultados por página
            $offset = ($page - 1) * $limit;
            
            $employeeModel = $this->model('Employee');
            
            // Construir condiciones WHERE
            $whereConditions = []; // Solo empleados activos
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(firstname LIKE ? OR lastname LIKE ? OR employee_id LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Si no hay condiciones adicionales, usar WHERE simple
            if (empty($whereConditions)) {
                $whereClause = "1=1";
            }
            
            // Obtener total de registros (para paginación)
            $totalQuery = "SELECT COUNT(*) as total FROM employees WHERE {$whereClause}";
            $stmt = $this->db->prepare($totalQuery);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Obtener empleados con paginación
            $query = "SELECT id, firstname, lastname, employee_id 
                     FROM employees 
                     WHERE {$whereClause} 
                     ORDER BY firstname, lastname 
                     LIMIT {$limit} OFFSET {$offset}";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $employees = $stmt->fetchAll();
            
            $results = [];
            foreach ($employees as $emp) {
                $results[] = [
                    'id' => $emp['id'],
                    'text' => $emp['firstname'] . ' ' . $emp['lastname'] . ' (' . $emp['employee_id'] . ')',
                    'firstname' => $emp['firstname'],
                    'lastname' => $emp['lastname'],
                    'employee_id' => $emp['employee_id']
                ];
            }
            
            // Formato esperado por Select2
            $response = [
                'results' => $results,
                'pagination' => [
                    'more' => ($offset + $limit) < $total
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (\Exception $e) {
            error_log("Error en Employee@getOptions: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage(), 'results' => [], 'pagination' => ['more' => false]]);
        }
        exit;
    }

    /**
     * Contar empleados activos por tipo de planilla
     */
    public function countByType()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            // Validar token CSRF
            if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
                throw new \Exception('Token de seguridad inválido');
            }

            $tipoPlanillaId = $_POST['tipo_planilla_id'] ?? null;
            
            if (!$tipoPlanillaId) {
                throw new \Exception('ID de tipo de planilla requerido');
            }

            // Contar empleados procesables con position_id asignada para el tipo de planilla especificado
            // Solo incluir empleados cuya situación esté definida en concepto_situaciones
            $sql = "SELECT COUNT(*) as count 
                    FROM employees e 
                    WHERE e.tipo_planilla_id = ?
                      AND e.situacion_id IN (
                          SELECT DISTINCT cs.situacion_id 
                          FROM concepto_situaciones cs
                      )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tipoPlanillaId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'count' => (int)$result['count']
            ]);

        } catch (\Exception $e) {
            error_log("Error en Employee@countByType: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'count' => 0
            ]);
        }
        exit;
    }
}