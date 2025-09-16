<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security;
use App\Models\TipoAcumulado;

/**
 * TipoAcumuladoController
 * Controlador para gestión de tipos de acumulados
 */
class TipoAcumuladoController extends Controller
{
    private $tipoAcumuladoModel;

    public function __construct()
    {
        parent::__construct();
        $this->tipoAcumuladoModel = new TipoAcumulado();
    }

    /**
     * Mostrar lista de tipos de acumulados
     */
    public function index()
    {
        $tiposAcumulados = $this->tipoAcumuladoModel->getAll();
        
        $data = [
            'title' => 'Tipos de Acumulados',
            'tiposAcumulados' => $tiposAcumulados
        ];
        
        $this->render('admin/tipos-acumulados/index', $data);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $data = [
            'title' => 'Crear Tipo de Acumulado'
        ];
        
        $this->render('admin/tipos-acumulados/create', $data);
    }

    /**
     * Procesar creación de tipo de acumulado
     */
    public function store()
    {
        if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $data = [
            'codigo' => trim($_POST['codigo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'periodicidad' => $_POST['periodicidad'] ?? 'ANUAL',
            'fecha_inicio_periodo' => $_POST['fecha_inicio_periodo'] ?: null,
            'fecha_fin_periodo' => $_POST['fecha_fin_periodo'] ?: null,
            'reinicia_automaticamente' => isset($_POST['reinicia_automaticamente']) ? 1 : 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        // Validaciones
        $errors = $this->validateTipoAcumulado($data);
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ]);
            return;
        }

        try {
            $id = $this->tipoAcumuladoModel->create($data);
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de acumulado creado exitosamente',
                'id' => $id
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear tipo de acumulado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mostrar tipo de acumulado específico
     */
    public function show($id)
    {
        $tipoAcumulado = $this->tipoAcumuladoModel->getById($id);
        
        if (!$tipoAcumulado) {
            header('HTTP/1.1 404 Not Found');
            $data = ['title' => 'Tipo de Acumulado no encontrado'];
            $this->render('errors/404', $data);
            return;
        }

        $data = [
            'title' => 'Detalle: ' . $tipoAcumulado['descripcion'],
            'tipoAcumulado' => $tipoAcumulado
        ];
        
        $this->render('admin/tipos-acumulados/show', $data);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        $tipoAcumulado = $this->tipoAcumuladoModel->getById($id);
        
        if (!$tipoAcumulado) {
            header('HTTP/1.1 404 Not Found');
            $data = ['title' => 'Tipo de Acumulado no encontrado'];
            $this->render('errors/404', $data);
            return;
        }

        $data = [
            'title' => 'Editar: ' . $tipoAcumulado['descripcion'],
            'tipoAcumulado' => $tipoAcumulado
        ];
        
        $this->render('admin/tipos-acumulados/edit', $data);
    }

    /**
     * Procesar actualización de tipo de acumulado
     */
    public function update($id)
    {
        if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $data = [
            'codigo' => trim($_POST['codigo'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'periodicidad' => $_POST['periodicidad'] ?? 'ANUAL',
            'fecha_inicio_periodo' => $_POST['fecha_inicio_periodo'] ?: null,
            'fecha_fin_periodo' => $_POST['fecha_fin_periodo'] ?: null,
            'reinicia_automaticamente' => isset($_POST['reinicia_automaticamente']) ? 1 : 0,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        // Validaciones
        $errors = $this->validateTipoAcumulado($data, $id);
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $errors
            ]);
            return;
        }

        try {
            $result = $this->tipoAcumuladoModel->update($id, $data);
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de acumulado actualizado exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar tipo de acumulado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar tipo de acumulado
     */
    public function delete($id)
    {
        if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            // Verificar si tiene conceptos asociados
            $conceptosAsociados = $this->tipoAcumuladoModel->getConceptosAsociados($id);
            
            if ($conceptosAsociados > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "No se puede eliminar: tiene {$conceptosAsociados} concepto(s) asociado(s)"
                ]);
                return;
            }

            $result = $this->tipoAcumuladoModel->delete($id);
            echo json_encode([
                'success' => true,
                'message' => 'Tipo de acumulado eliminado exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar tipo de acumulado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleStatus($id)
    {
        if (!Security::validateToken($_POST['csrf_token'] ?? '')) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $result = $this->tipoAcumuladoModel->toggleStatus($id);
            echo json_encode([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'new_status' => $result['new_status']
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al cambiar estado: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * DataTables AJAX endpoint
     */
    public function datatablesAjax()
    {
        $draw = intval($_GET['draw'] ?? 1);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $searchValue = $_GET['search']['value'] ?? '';

        $result = $this->tipoAcumuladoModel->getDatatables($start, $length, $searchValue);

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['data']
        ]);
    }

    /**
     * Obtener opciones para select (AJAX)
     */
    public function options()
    {
        $search = $_GET['q'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        
        $result = $this->tipoAcumuladoModel->getOptions($search, $page);
        
        echo json_encode($result);
    }

    /**
     * Verificar duplicados de código
     */
    public function checkDuplicate()
    {
        $codigo = trim($_GET['codigo'] ?? '');
        $excludeId = intval($_GET['exclude_id'] ?? 0);
        
        if (empty($codigo)) {
            echo json_encode(['exists' => false]);
            return;
        }

        $exists = $this->tipoAcumuladoModel->existsCodigo($codigo, $excludeId);
        echo json_encode(['exists' => $exists]);
    }

    /**
     * Validar datos del tipo de acumulado
     */
    private function validateTipoAcumulado($data, $excludeId = null)
    {
        $errors = [];

        // Validar código
        if (empty($data['codigo'])) {
            $errors['codigo'] = 'El código es obligatorio';
        } elseif (strlen($data['codigo']) > 20) {
            $errors['codigo'] = 'El código no puede tener más de 20 caracteres';
        } elseif (!preg_match('/^[A-Z0-9_]+$/', $data['codigo'])) {
            $errors['codigo'] = 'El código solo puede contener letras mayúsculas, números y guiones bajos';
        } elseif ($this->tipoAcumuladoModel->existsCodigo($data['codigo'], $excludeId)) {
            $errors['codigo'] = 'Ya existe un tipo de acumulado con este código';
        }

        // Validar descripción
        if (empty($data['descripcion'])) {
            $errors['descripcion'] = 'La descripción es obligatoria';
        } elseif (strlen($data['descripcion']) > 100) {
            $errors['descripcion'] = 'La descripción no puede tener más de 100 caracteres';
        }

        // Validar periodicidad
        $periodicidadesValidas = ['MENSUAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL', 'ESPECIAL'];
        if (!in_array($data['periodicidad'], $periodicidadesValidas)) {
            $errors['periodicidad'] = 'La periodicidad no es válida';
        }

        // Validar fechas
        if (!empty($data['fecha_inicio_periodo']) && !empty($data['fecha_fin_periodo'])) {
            $fechaInicio = strtotime($data['fecha_inicio_periodo']);
            $fechaFin = strtotime($data['fecha_fin_periodo']);
            
            if ($fechaInicio >= $fechaFin) {
                $errors['fecha_fin_periodo'] = 'La fecha de fin debe ser posterior a la fecha de inicio';
            }
        }

        return $errors;
    }
}