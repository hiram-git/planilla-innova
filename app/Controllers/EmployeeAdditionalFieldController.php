<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Helpers\PermissionHelper;
use App\Models\EmployeeAdditionalField;
use App\Models\EmployeeAdditionalFieldValue;

/**
 * Controlador para gestión de campos adicionales personalizados de empleados
 */
class EmployeeAdditionalFieldController extends Controller
{
    protected $fieldModel;
    protected $valueModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->fieldModel = new EmployeeAdditionalField();
        $this->valueModel = new EmployeeAdditionalFieldValue();
    }

    /**
     * Vista principal - Listado de campos adicionales
     */
    public function index()
    {
        PermissionMiddleware::requirePermission('panel/additional-fields', 'read');

        $data = [
            'title' => 'Campos Adicionales de Empleados',
            'page_title' => 'Campos Adicionales',
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/additional_fields/index', $data);
    }

    /**
     * Vista de creación
     */
    public function create()
    {
        PermissionMiddleware::requirePermission('panel/additional-fields', 'create');

        $data = [
            'title' => 'Agregar Campo Adicional',
            'page_title' => 'Nuevo Campo Adicional',
            'tipos_dato' => EmployeeAdditionalField::TIPOS_DATO,
            'siguiente_orden' => $this->fieldModel->getNextOrder(),
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/additional_fields/create', $data);
    }

    /**
     * Procesar creación
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/additional-fields');
            return;
        }

        PermissionMiddleware::requirePermission('panel/additional-fields', 'create');
        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);

        // Validar datos
        $errors = $this->fieldModel->validateFieldData($data);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect('/panel/additional-fields/create');
            return;
        }

        try {
            // Preparar datos
            $fieldData = [
                'codigo' => strtoupper($data['codigo']),
                'etiqueta' => $data['etiqueta'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo_dato' => $data['tipo_dato'],
                'valor_defecto' => $data['valor_defecto'] ?? null,
                'orden' => !empty($data['orden']) ? (int)$data['orden'] : $this->fieldModel->getNextOrder(),
                'activo' => isset($data['activo']) ? 1 : 0
            ];

            $newId = $this->fieldModel->create($fieldData);

            if ($newId) {
                $_SESSION['success'] = "Campo adicional '{$data['etiqueta']}' creado exitosamente";
                $this->redirect('/panel/additional-fields');
            } else {
                $_SESSION['error'] = 'Error al crear el campo adicional';
                $_SESSION['old_data'] = $data;
                $this->redirect('/panel/additional-fields/create');
            }

        } catch (\Exception $e) {
            error_log("Error creando campo adicional: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear el campo adicional: ' . $e->getMessage();
            $_SESSION['old_data'] = $data;
            $this->redirect('/panel/additional-fields/create');
        }
    }

    /**
     * Vista de edición
     */
    public function edit($id)
    {
        PermissionMiddleware::requirePermission('panel/additional-fields', 'update');

        $field = $this->fieldModel->find($id);

        if (!$field) {
            $_SESSION['error'] = 'Campo adicional no encontrado';
            $this->redirect('/panel/additional-fields');
            return;
        }

        // Obtener estadísticas de uso
        $stats = $this->fieldModel->getUsageStats($id);

        $data = [
            'title' => 'Editar Campo Adicional',
            'page_title' => 'Editar Campo Adicional',
            'field' => $field,
            'stats' => $stats,
            'tipos_dato' => EmployeeAdditionalField::TIPOS_DATO,
            'csrf_token' => AuthMiddleware::generateCSRF()
        ];

        $this->view('admin/additional_fields/edit', $data);
    }

    /**
     * Procesar actualización
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/panel/additional-fields');
            return;
        }

        PermissionMiddleware::requirePermission('panel/additional-fields', 'update');
        AuthMiddleware::validateCSRF();

        $field = $this->fieldModel->find($id);

        if (!$field) {
            $_SESSION['error'] = 'Campo adicional no encontrado';
            $this->redirect('/panel/additional-fields');
            return;
        }

        $data = Security::sanitizeInput($_POST);

        // Validar datos (excluyendo el ID actual para validación de unicidad)
        $errors = $this->fieldModel->validateFieldData($data, $id);

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/additional-fields/edit/$id");
            return;
        }

        try {
            // Preparar datos de actualización
            $updateData = [
                'codigo' => strtoupper($data['codigo']),
                'etiqueta' => $data['etiqueta'],
                'descripcion' => $data['descripcion'] ?? null,
                'tipo_dato' => $data['tipo_dato'],
                'valor_defecto' => $data['valor_defecto'] ?? null,
                'orden' => !empty($data['orden']) ? (int)$data['orden'] : $field['orden'],
                'activo' => isset($data['activo']) ? 1 : 0
            ];

            $updated = $this->fieldModel->update($id, $updateData);

            if ($updated) {
                $_SESSION['success'] = "Campo adicional '{$data['etiqueta']}' actualizado exitosamente";
                $this->redirect("/panel/additional-fields/edit/$id");
            } else {
                $_SESSION['error'] = 'Error al actualizar el campo adicional';
                $_SESSION['old_data'] = $data;
                $this->redirect("/panel/additional-fields/edit/$id");
            }

        } catch (\Exception $e) {
            error_log("Error actualizando campo adicional: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el campo adicional: ' . $e->getMessage();
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/additional-fields/edit/$id");
        }
    }

    /**
     * Eliminar campo adicional
     */
    public function delete($id)
    {
        PermissionMiddleware::requirePermission('panel/additional-fields', 'delete');
        AuthMiddleware::requireAuth();

        try {
            $field = $this->fieldModel->find($id);

            if (!$field) {
                $_SESSION['error'] = 'Campo adicional no encontrado';
                $this->redirect('/panel/additional-fields');
                return;
            }

            // Verificar cuántos empleados usan este campo
            $stats = $this->fieldModel->getUsageStats($id);

            if ($this->fieldModel->delete($id)) {
                if ($stats['empleados_usando'] > 0) {
                    $_SESSION['success'] = "Campo '{$field['etiqueta']}' eliminado exitosamente " .
                                          "(se eliminaron {$stats['empleados_usando']} valores asociados)";
                } else {
                    $_SESSION['success'] = "Campo '{$field['etiqueta']}' eliminado exitosamente";
                }
            } else {
                $_SESSION['error'] = 'Error al eliminar el campo adicional';
            }

        } catch (\Exception $e) {
            error_log("Error eliminando campo adicional: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar el campo adicional';
        }

        $this->redirect('/panel/additional-fields');
    }

    /**
     * API AJAX para DataTables
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

            // Mapeo de columnas
            $columns = [
                0 => null, // Checkbox - no ordenable
                1 => 'codigo',
                2 => 'etiqueta',
                3 => 'tipo_dato',
                4 => 'valor_defecto',
                5 => 'orden',
                6 => 'activo',
                7 => null  // Acciones - no ordenable
            ];

            // Obtener total de registros
            $totalRecords = $this->fieldModel->count();

            // Construir consulta con filtros
            $fields = $this->getFieldsWithPagination($start, $length, $searchValue, $columns[$orderColumn], $orderDir);

            // Contar registros filtrados
            $filteredRecords = $this->getFilteredCount($searchValue);

            // Formatear datos para DataTables
            $data = [];
            foreach ($fields as $field) {
                $tipoDatoLabel = $this->getTipoDatoLabel($field['tipo_dato']);
                $estadoBadge = $field['activo']
                    ? '<span class="badge badge-success">Activo</span>'
                    : '<span class="badge badge-secondary">Inactivo</span>';

                $valorDefecto = !empty($field['valor_defecto'])
                    ? htmlspecialchars($field['valor_defecto'])
                    : '<em class="text-muted">Sin valor</em>';

                // Botones de acciones con permisos
                $actions = '';

                if (PermissionHelper::canWrite('additional-fields')) {
                    $editUrl = url('/panel/additional-fields/edit/' . $field['id']);
                    $actions .= '<a href="' . $editUrl . '"
                                    class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                 </a> ';
                }

                if (PermissionHelper::canDelete('additional-fields')) {
                    $actions .= '<button type="button"
                                    class="btn btn-danger btn-sm delete-btn"
                                    data-id="' . $field['id'] . '"
                                    data-name="' . htmlspecialchars($field['etiqueta']) . '"
                                    title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                 </button>';
                }

                $data[] = [
                    '<input type="checkbox" class="field-checkbox" value="' . $field['id'] . '">',
                    '<code>' . htmlspecialchars($field['codigo']) . '</code>',
                    htmlspecialchars($field['etiqueta']),
                    $tipoDatoLabel,
                    $valorDefecto,
                    '<span class="badge badge-info">' . $field['orden'] . '</span>',
                    $estadoBadge,
                    $actions
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
            error_log("Error en datatablesAjax: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => 'Error al cargar campos adicionales: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }

    /**
     * Obtener campos con paginación y filtros
     */
    protected function getFieldsWithPagination($start, $length, $search, $orderColumn, $orderDir)
    {
        $sql = "SELECT * FROM employee_additional_fields";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE codigo LIKE :search1 OR etiqueta LIKE :search2 OR descripcion LIKE :search3";
            $searchParam = "%{$search}%";
            $params = [
                'search1' => $searchParam,
                'search2' => $searchParam,
                'search3' => $searchParam
            ];
        }

        if ($orderColumn && in_array($orderDir, ['asc', 'desc'])) {
            $sql .= " ORDER BY {$orderColumn} {$orderDir}";
        } else {
            $sql .= " ORDER BY orden ASC, etiqueta ASC";
        }

        $sql .= " LIMIT :limit OFFSET :offset";

        $db = $this->fieldModel->getDatabase();
        $connection = $db->getConnection();
        $stmt = $connection->prepare($sql);

        // Vincular parámetros de búsqueda
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
        }

        // Vincular parámetros LIMIT y OFFSET como enteros
        $stmt->bindValue(':limit', (int)$length, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$start, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Contar registros filtrados
     */
    protected function getFilteredCount($search)
    {
        $sql = "SELECT COUNT(*) as total FROM employee_additional_fields";
        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE codigo LIKE :search1 OR etiqueta LIKE :search2 OR descripcion LIKE :search3";
            $searchParam = "%{$search}%";
            $params = [
                'search1' => $searchParam,
                'search2' => $searchParam,
                'search3' => $searchParam
            ];
        }

        $db = $this->fieldModel->getDatabase();
        $connection = $db->getConnection();
        $stmt = $connection->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value, \PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return intval($result['total'] ?? 0);
    }

    /**
     * Obtener etiqueta visual para tipo de dato
     */
    protected function getTipoDatoLabel($tipoDato)
    {
        $labels = [
            'TEXTO' => '<span class="badge badge-primary"><i class="fas fa-font"></i> Texto</span>',
            'NUMERO' => '<span class="badge badge-success"><i class="fas fa-hashtag"></i> Numérico</span>',
            'FECHA' => '<span class="badge badge-info"><i class="fas fa-calendar"></i> Fecha</span>',
            'BOOLEAN' => '<span class="badge badge-warning"><i class="fas fa-toggle-on"></i> Sí/No</span>'
        ];

        return $labels[$tipoDato] ?? $tipoDato;
    }

    /**
     * Requerir autenticación
     */
    protected function requireAuth()
    {
        AuthMiddleware::requireAuth();
    }
}
