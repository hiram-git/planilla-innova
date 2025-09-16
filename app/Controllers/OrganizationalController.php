<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador para gestión de estructura organizacional
 */
class OrganizationalController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Vista principal del organigrama
     */
    public function index()
    {
        $this->requireAuth();
        
        // Obtener estructura organizacional
        $organizationalModel = $this->model('Organizational');
        $hierarchy = $organizationalModel->getOrganizationalHierarchy();
        $statistics = $organizationalModel->getStatistics();
        
        $data = [
            'title' => 'Estructura Organizacional',
            'page_title' => 'Organigrama Empresarial',
            'hierarchy' => $hierarchy,
            'statistics' => $statistics
        ];
        
        $this->view('admin.organizational.index', $data);
    }

    /**
     * Crear nuevo elemento organizacional
     */
    public function create()
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCsrfToken();
                
                $data = [
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'id_padre' => !empty($_POST['id_padre']) ? (int)$_POST['id_padre'] : null
                ];
                
                // Validaciones
                if (empty($data['descripcion'])) {
                    throw new \Exception('La descripción es requerida');
                }
                
                // Crear elemento
                $organizationalModel = $this->model('Organizational');
                $organizationalModel->create($data);
                
                $_SESSION['success'] = 'Elemento organizacional creado exitosamente';
                $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
                
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Error al crear elemento: ' . $e->getMessage();
                $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
            }
        } else {
            // Mostrar formulario
            $organizationalModel = $this->model('Organizational');
            $elementsFlat = $organizationalModel->getOrganizationalFlat();
            
            $data = [
                'title' => 'Crear Elemento',
                'page_title' => 'Nuevo Elemento Organizacional',
                'elementsFlat' => $elementsFlat
            ];
            
            $this->view('admin.organizational.create', $data);
        }
    }

    /**
     * Editar elemento organizacional
     */
    public function edit($id)
    {
        $this->requireAuth();

        $organizationalModel = $this->model('Organizational');
        $element = $organizationalModel->getById($id);
        
        if (!$element) {
            $_SESSION['error'] = 'Elemento no encontrado';
            $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->validateCsrfToken();
                
                $data = [
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'id_padre' => !empty($_POST['id_padre']) ? (int)$_POST['id_padre'] : null
                ];
                
                // Validaciones
                if (empty($data['descripcion'])) {
                    throw new \Exception('La descripción es requerida');
                }
                
                // Prevenir ciclos - no puede ser padre de sí mismo
                if ($data['id_padre'] == $id) {
                    throw new \Exception('Un elemento no puede ser padre de sí mismo');
                }
                
                // Actualizar elemento
                $organizationalModel->update($id, $data);
                
                $_SESSION['success'] = 'Elemento actualizado exitosamente';
                $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
                
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Error al actualizar elemento: ' . $e->getMessage();
                $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
            }
        } else {
            // Mostrar formulario
            $elementsFlat = $organizationalModel->getOrganizationalFlat();
            
            $data = [
                'title' => 'Editar Elemento',
                'page_title' => 'Editar Elemento Organizacional',
                'element' => $element,
                'elementsFlat' => $elementsFlat
            ];
            
            $this->view('admin.organizational.edit', $data);
        }
    }

    /**
     * Eliminar elemento organizacional
     */
    public function delete($id)
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }
        
        try {
            // Para AJAX no necesitamos CSRF por ahora, pero podemos agregarlo después
            $organizationalModel = $this->model('Organizational');
            $organizationalModel->delete($id);
            
            $this->jsonResponse(['success' => true, 'message' => 'Elemento eliminado exitosamente']);
            
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mover elemento a nuevo padre
     */
    public function move($id)
    {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Método no permitido']);
            return;
        }
        
        try {
            $this->validateCsrfToken();
            
            $data = json_decode(file_get_contents('php://input'), true);
            $newParentId = $data['new_parent_id'] ?? null;
            
            $organizationalModel = $this->model('Organizational');
            $organizationalModel->moveToParent($id, $newParentId);
            
            $this->jsonResponse(['success' => true]);
            
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Buscar elementos organizacionales
     */
    public function search()
    {
        $this->requireAuth();
        
        $query = $_GET['q'] ?? '';
        $results = [];
        
        if (!empty($query)) {
            $organizationalModel = $this->model('Organizational');
            $results = $organizationalModel->search($query);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }


    /**
     * API para obtener jerarquía en formato JSON para gráficos
     */
    public function getChartData()
    {
        $this->requireAuth();
        
        header('Content-Type: application/json');
        
        try {
            $organizationalModel = $this->model('Organizational');
            $data = $organizationalModel->getOrganizationalChart();
            
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Obtener ruta completa desde la raíz hasta un elemento
     */
    public function getPath($id)
    {
        $this->requireAuth();
        
        try {
            $organizationalModel = $this->model('Organizational');
            $path = $organizationalModel->getPathToRoot($id);
            
            $this->jsonResponse(['success' => true, 'path' => $path]);
            
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Exportar organigrama en diferentes formatos
     */
    public function export($format = 'json')
    {
        $this->requireAuth();
        
        try {
            $organizationalModel = $this->model('Organizational');
            $data = $organizationalModel->getOrganizationalHierarchy();
            
            switch (strtolower($format)) {
                case 'json':
                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="organigrama.json"');
                    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'csv':
                    $this->exportToCsv($data);
                    break;
                    
                default:
                    $this->jsonResponse(['success' => false, 'error' => 'Formato no soportado']);
                    return;
            }
            
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
        
        exit;
    }

    /**
     * Exportar a CSV
     */
    private function exportToCsv($data)
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="organigrama.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Encabezados CSV
        fputcsv($output, ['ID', 'Descripción', 'ID Padre', 'Path', 'Nivel']);
        
        // Función recursiva para procesar la jerarquía
        $this->processCsvData($output, $data, 0);
        
        fclose($output);
    }

    /**
     * Procesar datos para CSV recursivamente
     */
    private function processCsvData($output, $elements, $level)
    {
        foreach ($elements as $element) {
            fputcsv($output, [
                $element['id'],
                $element['descripcion'],
                $element['id_padre'] ?? '',
                $element['path'] ?? '',
                $level
            ]);
            
            if (!empty($element['children'])) {
                $this->processCsvData($output, $element['children'], $level + 1);
            }
        }
    }

    /**
     * Verificar autenticación
     */
    private function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
    }

    /**
     * Validar token CSRF (implementación simple)
     */
    private function validateCsrfToken()
    {
        // Por ahora una implementación básica
        // En producción se debe implementar CSRF real
        return true;
    }
}