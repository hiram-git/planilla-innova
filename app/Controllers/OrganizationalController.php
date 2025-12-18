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
     * AJAX: Obtener cargos filtrados por departamento
     * GET /panel/organizational/cargos-by-departamento/{departamentoId}
     */
    public function getCargosByDepartamento($departamentoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            if ($departamentoId === null) {
                echo json_encode(['success' => false, 'error' => 'ID de departamento requerido']);
                exit;
            }

            $cargoModel = $this->model('Cargo');
            $cargos = $cargoModel->getCargosByDepartamento($departamentoId);

            echo json_encode([
                'success' => true,
                'cargos' => $cargos
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener todos los cargos activos
     * GET /panel/organizational/all-cargos
     */
    public function getAllCargos()
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $cargoModel = $this->model('Cargo');
            $cargos = $cargoModel->getAllActive();

            echo json_encode([
                'success' => true,
                'cargos' => $cargos
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Actualizar asociaciones de cargos al departamento
     * POST /panel/organizational/update-cargos/{departamentoId}
     */
    public function updateCargosDepartamento($departamentoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            if ($departamentoId === null) {
                echo json_encode(['success' => false, 'error' => 'ID de departamento requerido']);
                exit;
            }

            // Obtener IDs de cargos desde el body JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $cargoIds = $input['cargo_ids'] ?? [];

            // Validar que sea un array
            if (!is_array($cargoIds)) {
                echo json_encode(['success' => false, 'error' => 'Los IDs de cargos deben ser un array']);
                exit;
            }

            $cargoModel = $this->model('Cargo');
            $result = $cargoModel->updateDepartamentoAsociaciones($departamentoId, $cargoIds);

            echo json_encode([
                'success' => true,
                'message' => 'Asociaciones actualizadas exitosamente',
                'updated' => $result
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener funciones filtradas por cargo (incluye genéricas)
     * GET /panel/organizational/funciones-by-cargo/{cargoId}
     */
    public function getFuncionesByCargo($cargoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $funcionModel = $this->model('Funcion');

            // Si cargoId es null o 'all', retornar solo genéricas
            if ($cargoId === null || $cargoId === 'all') {
                $funciones = $funcionModel->getFuncionesGenericas();
            } else {
                // Retornar funciones específicas del cargo + genéricas
                $funciones = $funcionModel->getFuncionesByCargo($cargoId);
            }

            echo json_encode([
                'success' => true,
                'funciones' => $funciones
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener árbol de departamentos para selects jerárquicos
     * GET /panel/organizational/departamentos-tree
     */
    public function getDepartamentosTree()
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $excludeId = $_GET['exclude_id'] ?? null;

            $organigramaModel = $this->model('Organigrama');
            $tree = $organigramaModel->getTreeOptions($excludeId);

            echo json_encode([
                'success' => true,
                'departamentos' => $tree
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener todos los departamentos (para select simple)
     * GET /panel/organizational/departamentos
     */
    public function getDepartamentos()
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $organigramaModel = $this->model('Organigrama');
            $departamentos = $organigramaModel->all();

            echo json_encode([
                'success' => true,
                'departamentos' => $departamentos
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener información completa de un departamento con sus cargos
     * GET /panel/organizational/departamento-info/{id}
     */
    public function getDepartamentoInfo($departamentoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            if ($departamentoId === null) {
                echo json_encode(['success' => false, 'error' => 'ID de departamento requerido']);
                exit;
            }

            $organigramaModel = $this->model('Organigrama');
            $departamento = $organigramaModel->getDepartamentoWithCargos($departamentoId);

            if (!$departamento) {
                echo json_encode(['success' => false, 'error' => 'Departamento no encontrado']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'departamento' => $departamento
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener todas las funciones activas
     * GET /panel/organizational/all-funciones
     */
    public function getAllFunciones()
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            $funcionModel = $this->model('Funcion');
            $funciones = $funcionModel->getAllActive();

            echo json_encode([
                'success' => true,
                'funciones' => $funciones
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Obtener funciones por departamento
     * GET /panel/organizational/getFuncionesByDepartamento/{departamentoId}
     */
    public function getFuncionesByDepartamento($departamentoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        try {
            if ($departamentoId === null) {
                echo json_encode(['success' => false, 'error' => 'ID de departamento requerido']);
                exit;
            }

            $funcionModel = $this->model('Funcion');
            $funciones = $funcionModel->getFuncionesByDepartamento($departamentoId);

            echo json_encode([
                'success' => true,
                'funciones' => $funciones
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * AJAX: Actualizar asociaciones de funciones a un cargo
     * POST /panel/organizational/update-funciones/{cargoId}
     */
    public function updateFuncionesCargo($cargoId = null)
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            if ($cargoId === null) {
                echo json_encode(['success' => false, 'error' => 'ID de cargo requerido']);
                exit;
            }

            // Obtener IDs de funciones desde el body JSON
            $input = json_decode(file_get_contents('php://input'), true);
            $funcionIds = $input['funcion_ids'] ?? [];

            // Validar que sea un array
            if (!is_array($funcionIds)) {
                echo json_encode(['success' => false, 'error' => 'Los IDs de funciones deben ser un array']);
                exit;
            }

            $funcionModel = $this->model('Funcion');
            $result = $funcionModel->updateCargoAsociaciones($cargoId, $funcionIds);

            echo json_encode([
                'success' => true,
                'message' => 'Asociaciones actualizadas exitosamente',
                'updated' => $result
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Reporte de estructura organizacional en formato de árbol PDF
     * GET /panel/organizational/report-tree
     */
    public function reportTree()
    {
        $this->requireAuth();

        try {
            $organigramaModel = $this->model('Organigrama');
            $tree = $organigramaModel->getOrganizationalTree();

            // Calcular estadísticas generales
            $statistics = $this->calculateTreeStatistics($tree);

            // Obtener información de la empresa (mismo método que reportes de planilla)
            $company = $this->getCompanyInfo();

            // Generar PDF
            $this->generateTreePDF($tree, $statistics, $company);

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al generar reporte: ' . $e->getMessage();
            $this->redirect(\App\Core\UrlHelper::url('panel/organizational'));
        }
    }

    /**
     * Generar PDF del árbol organizacional
     */
    private function generateTreePDF($tree, $statistics, $company)
    {
        // Crear instancia de PDF personalizada en orientación horizontal
        $pdf = new OrganizationalTreePDF($company, 'L', 'mm', 'LETTER', true, 'UTF-8', false);

        // Configurar información del documento
        $pdf->SetCreator('Innova Planilla');
        $pdf->SetAuthor($company['nombre'] ?? 'Sistema de Planillas');
        $pdf->SetTitle('Reporte Árbol Organizacional');
        $pdf->SetSubject('Estructura Organizacional');

        // Configurar márgenes
        $pdf->SetMargins(10, 35, 10);
        $pdf->SetHeaderMargin(10);
        $pdf->SetFooterMargin(15);
        $pdf->SetAutoPageBreak(false);

        // Agregar primera página para estadísticas
        $pdf->AddPage();

        // Título del reporte
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->Cell(0, 10, 'ESTRUCTURA ORGANIZACIONAL', 0, 1, 'C');
        $pdf->Ln(5);

        // Sección de estadísticas
        $this->renderStatistics($pdf, $statistics);

        // Agregar nueva página para el organigrama
        $pdf->AddPage();

        // Renderizar árbol organizacional estilo diagrama
        $this->renderOrganizationalChart($pdf, $tree);

        // Salida del PDF
        $filename = 'Organigrama_' . date('Y-m-d_His') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }

    /**
     * Renderizar estadísticas en el PDF
     */
    private function renderStatistics($pdf, $statistics)
    {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetFillColor(52, 152, 219);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 8, 'RESUMEN EJECUTIVO', 0, 1, 'L', true);
        $pdf->Ln(2);

        // Crear tabla de estadísticas en 2 columnas
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $colWidth = 90;
        $cellHeight = 7;

        // Fila 1
        $pdf->SetFillColor(236, 240, 241);
        $pdf->Cell($colWidth, $cellHeight, 'Total Departamentos:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidth, $cellHeight, $statistics['total_departamentos'], 1, 1, 'C');

        // Fila 2
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(236, 240, 241);
        $pdf->Cell($colWidth, $cellHeight, 'Total Empleados:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidth, $cellHeight, $statistics['total_empleados'], 1, 1, 'C');

        // Fila 3
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(236, 240, 241);
        $pdf->Cell($colWidth, $cellHeight, 'Total Cargos/Posiciones:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidth, $cellHeight, $statistics['total_cargos'], 1, 1, 'C');

        // Fila 4
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetFillColor(236, 240, 241);
        $pdf->Cell($colWidth, $cellHeight, 'Niveles Jerárquicos:', 1, 0, 'L', true);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($colWidth, $cellHeight, ($statistics['niveles_jerarquicos'] + 1), 1, 1, 'C');
    }

    /**
     * Renderizar organigrama estilo diagrama con cajas y líneas
     */
    private function renderOrganizationalChart($pdf, $tree)
    {
        // Calcular posiciones de cada nodo
        $positions = $this->calculateNodePositions($tree);

        // Dibujar líneas de conexión primero
        $this->drawConnections($pdf, $positions);

        // Dibujar cajas de departamentos
        $this->drawDepartmentBoxes($pdf, $positions);
    }

    /**
     * Calcular posiciones de cada nodo en el organigrama
     */
    private function calculateNodePositions($tree, $level = 0, $parentId = null, &$positions = [], &$currentX = 10)
    {
        $boxWidth = 50;
        $boxHeight = 22; // Aumentado para acomodar empleado
        $horizontalGap = 5;
        $verticalGap = 40; // Aumentado para mejor espaciado
        $startY = 40; // Posición Y inicial para evitar superposición con header

        foreach ($tree as $node) {
            $childrenCount = count($node['children'] ?? []);
            $childStartX = $currentX;

            if ($childrenCount > 0) {
                // Calcular posición de los hijos primero (recursivamente)
                $this->calculateNodePositions($node['children'], $level + 1, $node['id'], $positions, $currentX);

                // Encontrar posiciones de los hijos de este nodo
                $myChildren = [];
                foreach ($positions as $pos) {
                    if (isset($pos['parent_id']) && $pos['parent_id'] == $node['id']) {
                        $myChildren[] = $pos;
                    }
                }

                // Centrar el nodo padre sobre sus hijos
                if (count($myChildren) > 0) {
                    $firstChildX = $myChildren[0]['x'];
                    $lastChildX = $myChildren[count($myChildren) - 1]['x'];
                    $nodeX = $firstChildX + (($lastChildX - $firstChildX) / 2);
                } else {
                    $nodeX = $currentX;
                    $currentX += $boxWidth + $horizontalGap;
                }
            } else {
                // Nodo hoja - posición secuencial
                $nodeX = $currentX;
                $currentX += $boxWidth + $horizontalGap;
            }

            $y = $startY + ($level * $verticalGap);

            // Guardar posición del nodo
            $positions[$node['id']] = [
                'id' => $node['id'],
                'x' => $nodeX,
                'y' => $y,
                'width' => $boxWidth,
                'height' => $boxHeight,
                'level' => $level,
                'descripcion' => $node['descripcion'],
                'empleados' => $node['empleados'] ?? [],
                'parent_id' => $parentId,
                'has_children' => $childrenCount > 0
            ];
        }

        return $positions;
    }

    /**
     * Dibujar líneas de conexión entre nodos
     */
    private function drawConnections($pdf, $positions)
    {
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(100, 100, 100);

        foreach ($positions as $node) {
            if ($node['parent_id'] !== null && isset($positions[$node['parent_id']])) {
                $parent = $positions[$node['parent_id']];

                // Punto de inicio (abajo del padre)
                $x1 = $parent['x'] + ($parent['width'] / 2);
                $y1 = $parent['y'] + $parent['height'];

                // Punto final (arriba del hijo)
                $x2 = $node['x'] + ($node['width'] / 2);
                $y2 = $node['y'];

                // Dibujar línea vertical desde el padre
                $midY = $y1 + (($y2 - $y1) / 2);

                // Línea vertical del padre
                $pdf->Line($x1, $y1, $x1, $midY);

                // Línea horizontal
                $pdf->Line($x1, $midY, $x2, $midY);

                // Línea vertical al hijo
                $pdf->Line($x2, $midY, $x2, $y2);
            }
        }
    }

    /**
     * Dibujar cajas de departamentos
     */
    private function drawDepartmentBoxes($pdf, $positions)
    {
        // Colores por nivel
        $colors = [
            0 => [41, 128, 185],   // Azul oscuro
            1 => [52, 152, 219],   // Azul
            2 => [46, 204, 113],   // Verde
            3 => [241, 196, 15],   // Amarillo
            4 => [231, 76, 60],    // Rojo
        ];

        foreach ($positions as $node) {
            $x = $node['x'];
            $y = $node['y'];
            $w = $node['width'];
            $h = $node['height'];

            // Color según nivel
            $level = min($node['level'], 4);
            $color = $colors[$level];

            // Dibujar caja con relleno
            $pdf->SetFillColor($color[0], $color[1], $color[2]);
            $pdf->SetDrawColor(50, 50, 50);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect($x, $y, $w, $h, 'DF');

            // Texto del departamento
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 7);
            $pdf->SetXY($x, $y + 2);

            // Truncar texto si es muy largo
            $descripcion = $node['descripcion'];
            if (strlen($descripcion) > 25) {
                $descripcion = substr($descripcion, 0, 22) . '...';
            }

            $pdf->Cell($w, 4, $descripcion, 0, 1, 'C');

            // Mostrar nombre del empleado asignado debajo del departamento
            if (!empty($node['empleados'])) {
                $empleado = $node['empleados'][0];
                $nombre = $empleado['full_name'] ?? '';

                if (!empty($nombre)) {
                    $pdf->SetFont('helvetica', '', 6);
                    $pdf->SetX($x);

                    // Truncar nombre si es muy largo
                    if (strlen($nombre) > 28) {
                        $nombre = substr($nombre, 0, 25) . '...';
                    }

                    $pdf->Cell($w, 3, $nombre, 0, 1, 'C');

                    // Mostrar cargo debajo del nombre
                    if (!empty($empleado['cargo_nombre'])) {
                        $pdf->SetFont('helvetica', 'I', 5);
                        $pdf->SetX($x);
                        $cargo = $empleado['cargo_nombre'];

                        // Truncar cargo si es muy largo
                        if (strlen($cargo) > 28) {
                            $cargo = substr($cargo, 0, 25) . '...';
                        }

                        $pdf->Cell($w, 3, $cargo, 0, 1, 'C');
                    }
                }
            } else {
                // Si no hay empleados, mostrar texto indicativo
                $pdf->SetFont('helvetica', 'I', 5);
                $pdf->SetX($x);
                $pdf->Cell($w, 3, 'Sin asignar', 0, 1, 'C');
            }
        }
    }

    /**
     * Calcular estadísticas del árbol organizacional
     */
    private function calculateTreeStatistics($tree, &$stats = null)
    {
        if ($stats === null) {
            $stats = [
                'total_departamentos' => 0,
                'total_empleados' => 0,
                'total_cargos' => 0,
                'niveles_jerarquicos' => 0
            ];
        }

        foreach ($tree as $node) {
            $stats['total_departamentos']++;
            $stats['total_empleados'] += count($node['empleados'] ?? []);
            $stats['total_cargos'] += count($node['cargos'] ?? []);

            if ($node['nivel_jerarquico'] > $stats['niveles_jerarquicos']) {
                $stats['niveles_jerarquicos'] = $node['nivel_jerarquico'];
            }

            if (!empty($node['children'])) {
                $this->calculateTreeStatistics($node['children'], $stats);
            }
        }

        return $stats;
    }

    /**
     * Obtener información de la empresa desde la BD
     * Mismo método que ReportController para consistencia
     */
    private function getCompanyInfo()
    {
        try {
            $organigramaModel = $this->model('Organigrama');
            $db = $organigramaModel->getDatabase();
            $connection = $db->getConnection();

            $sql = "SELECT * FROM companies WHERE id = 1";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $company = $stmt->fetch();

            return [
                'company_name' => $company['company_name'] ?? 'EMPRESA EJEMPLO S.A.',
                'ruc' => $company['ruc'] ?? '1234567890-1-DV',
                'address' => $company['address'] ?? 'Dirección Empresa',
                'legal_representative' => $company['legal_representative'] ?? 'Representante Legal',
                'jefe_recursos_humanos' => $company['jefe_recursos_humanos'] ?? 'Jefe de RRHH',
                'cargo_jefe_rrhh' => $company['cargo_jefe_rrhh'] ?? 'Jefe de Recursos Humanos',
                'elaborado_por' => $company['elaborado_por'] ?? 'Especialista en Nóminas',
                'cargo_elaborador' => $company['cargo_elaborador'] ?? 'Especialista en Nóminas',
                'firma_director_planilla' => (!empty($company['firma_director_planilla']) && trim($company['firma_director_planilla']) !== '')
                    ? $company['firma_director_planilla']
                    : ($company['legal_representative'] ?? ''),
                'cargo_director_planilla' => (!empty($company['cargo_director_planilla']) && trim($company['cargo_director_planilla']) !== '')
                    ? $company['cargo_director_planilla']
                    : '',
                'firma_contador_planilla' => (!empty($company['firma_contador_planilla']) && trim($company['firma_contador_planilla']) !== '')
                    ? $company['firma_contador_planilla']
                    : '',
                'cargo_contador_planilla' => (!empty($company['cargo_contador_planilla']) && trim($company['cargo_contador_planilla']) !== '')
                    ? $company['cargo_contador_planilla']
                    : '',
                'logo_empresa' => $company['logo_empresa'] ?? '',
                'logo_izquierdo_reportes' => $company['logo_izquierdo_reportes'] ?? '',
                'logo_derecho_reportes' => $company['logo_derecho_reportes'] ?? ''
            ];
        } catch (\Exception $e) {
            return [
                'company_name' => 'EMPRESA EJEMPLO S.A.',
                'ruc' => '1234567890-1-DV',
                'address' => 'Dirección Empresa',
                'legal_representative' => 'Representante Legal',
                'jefe_recursos_humanos' => 'Jefe de RRHH',
                'cargo_jefe_rrhh' => 'Jefe de Recursos Humanos',
                'elaborado_por' => 'Especialista en Nóminas',
                'cargo_elaborador' => 'Especialista en Nóminas',
                'firma_director_planilla' => '',
                'cargo_director_planilla' => '',
                'firma_contador_planilla' => '',
                'cargo_contador_planilla' => '',
                'logo_empresa' => '',
                'logo_izquierdo_reportes' => '',
                'logo_derecho_reportes' => ''
            ];
        }
    }

    /**
     * Verificar autenticación
     */
    protected function requireAuth()
    {
        if (!isset($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
    }

    /**
     * Validar token CSRF (implementación simple)
     */
    protected function validateCsrfToken()
    {
        // Por ahora una implementación básica
        // En producción se debe implementar CSRF real
        return true;
    }
}

/**
 * Clase personalizada de TCPDF para el reporte de árbol organizacional
 */
class OrganizationalTreePDF extends \TCPDF
{
    private $companyData;

    public function __construct($companyData, $orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
        $this->companyData = $companyData;
    }

    // Header personalizado
    public function Header()
    {
        $logoPath = __DIR__ . '/../../images/logos/';
        $logoHeight = 12; // Reducido de 15 a 12
        $pageWidth = $this->getPageWidth();
        $margin = 10;
        $currentY = 8;

        // Logo izquierdo
        if (!empty($this->companyData['logo_izquierdo_reportes'])) {
            $leftLogoPath = $logoPath . $this->companyData['logo_izquierdo_reportes'];
            if (file_exists($leftLogoPath)) {
                $leftLogoWidth = 25; // Reducido de 35 a 25
                try {
                    $this->Image($leftLogoPath, $margin, $currentY, $leftLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo izquierdo: " . $e->getMessage());
                }
            }
        }

        // Logo derecho
        if (!empty($this->companyData['logo_derecho_reportes'])) {
            $rightLogoPath = $logoPath . $this->companyData['logo_derecho_reportes'];
            if (file_exists($rightLogoPath)) {
                $rightLogoWidth = 25; // Reducido de 35 a 25
                $rightX = $pageWidth - $margin - $rightLogoWidth;
                try {
                    $this->Image($rightLogoPath, $rightX, $currentY, $rightLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo derecho: " . $e->getMessage());
                }
            }
        }

        // Logo principal (centro) - solo si no hay logos laterales
        if (empty($this->companyData['logo_izquierdo_reportes']) &&
            empty($this->companyData['logo_derecho_reportes']) &&
            !empty($this->companyData['logo_empresa'])) {
            $mainLogoPath = $logoPath . $this->companyData['logo_empresa'];
            if (file_exists($mainLogoPath)) {
                $mainLogoWidth = 35; // Reducido de 45 a 35
                $centerX = ($pageWidth - $mainLogoWidth) / 2;
                try {
                    $this->Image($mainLogoPath, $centerX, $currentY, $mainLogoWidth, 0, '', '', '', false, 300, '', false, false, 0);
                } catch (\Exception $e) {
                    error_log("Error cargando logo principal: " . $e->getMessage());
                }
            }
        }

        // Nombre de la empresa centrado
        $this->SetFont('helvetica', 'B', 14); // Reducido de 16 a 14
        $companyName = strtoupper($this->companyData['company_name'] ?? 'EMPRESA');
        $companyNameWidth = $this->GetStringWidth($companyName);
        $centerX = ($pageWidth - $companyNameWidth) / 2;
        $textY = $currentY + ($logoHeight / 2) - 2; // Ajustado de -3 a -2

        $this->SetXY($centerX, $textY);
        $this->Cell($companyNameWidth, 0, $companyName, 0, 0, 'C');

        // RUC centrado debajo del nombre
        if (!empty($this->companyData['ruc'])) {
            $this->SetFont('helvetica', '', 9); // Reducido de 10 a 9
            $rucText = 'RUC: ' . $this->companyData['ruc'];
            $rucWidth = $this->GetStringWidth($rucText);
            $rucX = ($pageWidth - $rucWidth) / 2;
            $this->SetXY($rucX, $textY + 5); // Reducido de 6 a 5
            $this->Cell($rucWidth, 0, $rucText, 0, 0, 'C');
        }
    }

    // Footer personalizado
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);

        // Línea separadora (ajustada para landscape)
        $this->SetLineStyle(['width' => 0.3, 'color' => [200, 200, 200]]);
        $this->Line(10, $this->GetY() - 2, 269, $this->GetY() - 2);

        // Texto del footer
        $this->Cell(0, 10, 'Generado el ' . date('d/m/Y H:i:s') . ' | Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}