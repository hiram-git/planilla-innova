<?php

namespace App\Models;

use App\Core\Model;
use App\Services\PlanillaConceptCalculator;
use PDO;
use PDOException;

/**
 * Modelo para gestión de conceptos de nómina
 */
class Concept extends Model
{
    public $table = 'concepto';
    protected $fillable = [
        'concepto',
        'descripcion',
        'cuenta_contable',
        'tipo_concepto',
        'unidad',
        'tipos_planilla',
        'frecuencias',
        'situaciones',
        'formula',
        'valor_fijo',
        'imprime_detalles',
        'prorratea',
        'modifica_valor',
        'valor_referencia',
        'monto_calculo',
        'monto_cero',
        // Campos de parametrización para reportes
        'incluir_reporte',
        'categoria_reporte',
        'orden_reporte'
    ];

    /**
     * Crear concepto con relaciones
     */
    public function createWithRelations($data)
    {
        try {
            $this->db->beginTransaction();

            // Separar relaciones del data principal
            $tiposPlanilla = $data['tipos_planilla_ids'] ?? [];
            $frecuencias = $data['frecuencias_ids'] ?? [];
            $situaciones = $data['situaciones_ids'] ?? [];

            // Remover arrays de relación del data principal
            unset($data['tipos_planilla_ids'], $data['frecuencias_ids'], $data['situaciones_ids']);

            // Crear el concepto
            $conceptId = $this->create($data);

            if (!$conceptId) {
                throw new \Exception('Error creando el concepto');
            }

            // Crear relaciones
            $this->syncTiposPlanilla($conceptId, $tiposPlanilla);
            $this->syncFrecuencias($conceptId, $frecuencias);
            $this->syncSituaciones($conceptId, $situaciones);

            $this->db->commit();
            return $conceptId;

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error creando concepto con relaciones: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar concepto con relaciones
     */
    public function updateWithRelations($id, $data)
    {
        try {
            $this->db->beginTransaction();

            // Separar relaciones del data principal
            $tiposPlanilla = $data['tipos_planilla_ids'] ?? [];
            $frecuencias = $data['frecuencias_ids'] ?? [];
            $situaciones = $data['situaciones_ids'] ?? [];

            // Actualizando concepto con relaciones

            // Remover arrays de relación del data principal
            unset($data['tipos_planilla_ids'], $data['frecuencias_ids'], $data['situaciones_ids']);

            // Actualizar el concepto
            $result = $this->update($id, $data);

            if (!$result) {
                throw new \Exception('Error actualizando el concepto');
            }

            // Actualizar relaciones
            $this->syncTiposPlanilla($id, $tiposPlanilla);
            $this->syncFrecuencias($id, $frecuencias);
            $this->syncSituaciones($id, $situaciones);

            $this->db->commit();
            // Concepto actualizado exitosamente con relaciones
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            error_log("Error actualizando concepto con relaciones: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sincronizar tipos de planilla
     */
    public function syncTiposPlanilla($conceptId, $tiposPlanillaIds)
    {
        // Eliminar relaciones existentes
        $this->db->query("DELETE FROM concepto_tipos_planilla WHERE concepto_id = ?", [$conceptId]);

        // Insertar nuevas relaciones
        foreach ($tiposPlanillaIds as $tipoId) {
            $this->db->query(
                "INSERT INTO concepto_tipos_planilla (concepto_id, tipo_planilla_id) VALUES (?, ?)",
                [$conceptId, $tipoId]
            );
        }
    }

    /**
     * Sincronizar frecuencias
     */
    public function syncFrecuencias($conceptId, $frecuenciasIds)
    {
        // Eliminar relaciones existentes
        $this->db->query("DELETE FROM concepto_frecuencias WHERE concepto_id = ?", [$conceptId]);

        // Insertar nuevas relaciones
        foreach ($frecuenciasIds as $frecuenciaId) {
            $this->db->query(
                "INSERT INTO concepto_frecuencias (concepto_id, frecuencia_id) VALUES (?, ?)",
                [$conceptId, $frecuenciaId]
            );
        }
    }

    /**
     * Sincronizar situaciones
     */
    public function syncSituaciones($conceptId, $situacionesIds)
    {
        // Eliminar relaciones existentes
        $this->db->query("DELETE FROM concepto_situaciones WHERE concepto_id = ?", [$conceptId]);

        // Insertar nuevas relaciones
        foreach ($situacionesIds as $situacionId) {
            $this->db->query(
                "INSERT INTO concepto_situaciones (concepto_id, situacion_id) VALUES (?, ?)",
                [$conceptId, $situacionId]
            );
        }
    }

    /**
     * Obtener concepto con sus relaciones
     */
    public function findWithRelations($id)
    {
        try {
            $concept = $this->find($id);
            if (!$concept) {
                return null;
            }

            // Obtener tipos de planilla relacionados
            $sql = "SELECT tp.* FROM tipos_planilla tp 
                    INNER JOIN concepto_tipos_planilla ctp ON tp.id = ctp.tipo_planilla_id 
                    WHERE ctp.concepto_id = ?";
            $concept['tipos_planilla_rel'] = $this->db->query($sql, [$id])->fetchAll();

            // Obtener frecuencias relacionadas
            $sql = "SELECT f.* FROM frecuencias f 
                    INNER JOIN concepto_frecuencias cf ON f.id = cf.frecuencia_id 
                    WHERE cf.concepto_id = ?";
            $concept['frecuencias_rel'] = $this->db->query($sql, [$id])->fetchAll();

            // Obtener situaciones relacionadas
            $sql = "SELECT s.* FROM situaciones s 
                    INNER JOIN concepto_situaciones cs ON s.id = cs.situacion_id 
                    WHERE cs.concepto_id = ?";
            $concept['situaciones_rel'] = $this->db->query($sql, [$id])->fetchAll();

            return $concept;

        } catch (\Exception $e) {
            error_log("Error obteniendo concepto con relaciones: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener todos los conceptos activos
     */
    public function getAllActive()
    {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY tipo_concepto DESC, descripcion ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos activos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos por tipo
     */
    public function getByType($type)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE tipo = ? AND activo = 1 ORDER BY descripcion";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos por tipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos de ingresos
     */
    public function getIncomesConcepts()
    {
        return $this->getByType('INGRESO');
    }

    /**
     * Obtener conceptos de deducciones
     */
    public function getDeductionsConcepts()
    {
        return $this->getByType('DEDUCCION');
    }

    /**
     * Validar fórmula de concepto con validaciones avanzadas
     */
    public function validateFormula($formula, $employeeId = null)
    {
        if (empty($formula)) {
            return ['valid' => true, 'message' => 'Fórmula vacía - se usará como valor fijo'];
        }

        try {
            // Validaciones básicas de sintaxis
            $basicValidation = $this->validateFormulaSyntax($formula);
            if (!$basicValidation['valid']) {
                return $basicValidation;
            }

            // Cargar calculadora para validación
            if (!class_exists(PlanillaConceptCalculator::class)) {
                require_once __DIR__ . '/../Services/PlanillaConceptCalculator.php';
            }

            $calculator = new PlanillaConceptCalculator();
            
            // Si se proporciona un empleado, usar sus datos para validar
            if ($employeeId) {
                $calculator->setVariablesColaborador($employeeId);
            } else {
                // Usar variables por defecto para validación
                $calculator->setVariablesColaborador(1); // ID del primer empleado
            }

            // Intentar evaluar la fórmula directamente
            $result = $calculator->evaluarFormula($formula);
            
            // Validar el resultado
            if (!is_numeric($result)) {
                return [
                    'valid' => false,
                    'message' => 'La fórmula no produce un resultado numérico válido'
                ];
            }

            // Verificar rangos razonables
            if ($result < -999999 || $result > 999999) {
                return [
                    'valid' => false,
                    'message' => 'El resultado está fuera del rango permitido (-999,999 a 999,999)'
                ];
            }
            
            return [
                'valid' => true, 
                'message' => 'Fórmula válida',
                'test_result' => $result,
                'variables_used' => $this->extractVariables($formula)
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false, 
                'message' => 'Error en fórmula: ' . $e->getMessage()
            ];
        }
    }



    /**
     * Activar/desactivar concepto
     */
    public function toggleActive($id)
    {
        try {
            $concept = $this->find($id);
            if (!$concept) {
                return false;
            }

            // Usar el campo 'situaciones' para manejar activo/inactivo
            $currentStatus = $concept['situaciones'] ?? '';
            $newStatus = $currentStatus === 'activo' ? 'inactivo' : 'activo';
            
            return $this->update($id, ['situaciones' => $newStatus]);
        } catch (\Exception $e) {
            error_log("Error cambiando estado del concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener conceptos con estadísticas de uso
     */
    public function getWithUsageStats()
    {
        try {
            $sql = "SELECT 
                        c.*,
                        COUNT(pd.id) as veces_usado,
                        COALESCE(AVG(pd.monto), 0) as promedio_monto,
                        COALESCE(SUM(pd.monto), 0) as total_monto
                    FROM {$this->table} c
                    LEFT JOIN planilla_detalle pd ON c.id = pd.concepto_id
                    GROUP BY c.id
                    ORDER BY c.tipo_concepto DESC, c.descripcion";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos con estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar conceptos por descripción
     */
    public function search($term)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE descripcion LIKE ? 
                    ORDER BY activo DESC, tipo DESC, descripcion";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['%' . $term . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando conceptos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos que dependen de otros (fórmulas complejas)
     */
    public function getDependentConcepts()
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE formula IS NOT NULL 
                    AND formula != '' 
                    AND formula REGEXP '[A-Z_]+' 
                    ORDER BY descripcion";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos dependientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos básicos (valores fijos o fórmulas simples)
     */
    public function getBasicConcepts()
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE formula IS NULL 
                    OR formula = '' 
                    OR formula REGEXP '^[0-9.]+$'
                    ORDER BY tipo DESC, descripcion";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos básicos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un concepto puede ser eliminado
     */
    public function canDelete($id)
    {
        try {
            // Verificar si el concepto ha sido usado en planillas
            $sql = "SELECT COUNT(*) as count FROM planilla_detalle WHERE concepto_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] == 0;
        } catch (PDOException $e) {
            error_log("Error verificando si concepto puede eliminarse: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar concepto solo si no ha sido usado
     */
    public function delete($id)
    {
        try {
            if (!$this->canDelete($id)) {
                throw new \Exception('No se puede eliminar el concepto porque ya ha sido usado en planillas');
            }

            return parent::delete($id);
        } catch (\Exception $e) {
            error_log("Error eliminando concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas generales de conceptos
     */
    public function getGeneralStats()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_conceptos,
                        SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as conceptos_activos,
                        SUM(CASE WHEN tipo = 'INGRESO' THEN 1 ELSE 0 END) as conceptos_ingreso,
                        SUM(CASE WHEN tipo = 'DEDUCCION' THEN 1 ELSE 0 END) as conceptos_deduccion,
                        SUM(CASE WHEN formula IS NOT NULL AND formula != '' THEN 1 ELSE 0 END) as con_formula,
                        SUM(CASE WHEN formula IS NULL OR formula = '' THEN 1 ELSE 0 END) as sin_formula
                    FROM {$this->table}";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas generales: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validaciones básicas de sintaxis de fórmula
     */
    private function validateFormulaSyntax($formula)
    {
        // Verificar caracteres permitidos - agregadas comillas simples y dobles para las nuevas funciones
        if (!preg_match('/^[A-Z0-9_.(),\s\+\-\*\/\>\<\=\!\'\"]+$/i', $formula)) {
            return [
                'valid' => false,
                'message' => 'La fórmula contiene caracteres no permitidos'
            ];
        }

        // Verificar paréntesis balanceados
        $openParens = substr_count($formula, '(');
        $closeParens = substr_count($formula, ')');
        if ($openParens !== $closeParens) {
            return [
                'valid' => false,
                'message' => 'Paréntesis no balanceados en la fórmula'
            ];
        }

        // Verificar que no empiece o termine con operadores
        if (preg_match('/^[\+\-\*\/]|[\+\-\*\/]$/', trim($formula))) {
            return [
                'valid' => false,
                'message' => 'La fórmula no puede empezar o terminar con operadores'
            ];
        }

        // Verificar operadores consecutivos
        if (preg_match('/[\+\-\*\/]{2,}/', $formula)) {
            return [
                'valid' => false,
                'message' => 'No se permiten operadores consecutivos'
            ];
        }

        return ['valid' => true, 'message' => 'Sintaxis válida'];
    }

    /**
     * Extraer variables utilizadas en la fórmula
     */
    private function extractVariables($formula)
    {
        $variables = [];
        $knownVars = ['SALARIO', 'HORAS', 'ANTIGUEDAD', 'FICHA', 'INIPERIODO', 'FINPERIODO'];

        foreach ($knownVars as $var) {
            if (strpos($formula, $var) !== false) {
                $variables[] = $var;
            }
        }

        // Buscar funciones especiales
        if (preg_match('/ACREEDOR\s*\([^)]+\)/', $formula)) {
            $variables[] = 'ACREEDOR()';
        }
        if (preg_match('/SI\s*\([^)]+\)/', $formula)) {
            $variables[] = 'SI()';
        }
        if (preg_match('/ANTIGUEDAD\s*\([^)]+\)/', $formula)) {
            $variables[] = 'ANTIGUEDAD()';
        }
        if (preg_match('/ACUMULADOS\s*\([^)]+\)/', $formula)) {
            $variables[] = 'ACUMULADOS()';
        }

        return array_unique($variables);
    }

    /**
     * Verificar dependencias circulares en fórmulas
     */
    public function checkCircularDependency($conceptId, $formula)
    {
        try {
            // Obtener conceptos que esta fórmula podría referenciar
            $referencedConcepts = $this->getReferencedConcepts($formula);
            
            if (empty($referencedConcepts)) {
                return ['valid' => true, 'message' => 'Sin dependencias'];
            }

            // Verificar si alguno de los conceptos referenciados depende de este concepto
            foreach ($referencedConcepts as $refId) {
                if ($this->hasCircularReference($conceptId, $refId, [])) {
                    return [
                        'valid' => false,
                        'message' => 'Dependencia circular detectada con concepto ID: ' . $refId
                    ];
                }
            }

            return ['valid' => true, 'message' => 'Sin dependencias circulares'];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error verificando dependencias: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener conceptos referenciados en una fórmula
     */
    private function getReferencedConcepts($formula)
    {
        $referenced = [];
        
        // Buscar referencias a otros conceptos (formato CONCEPTO_ID)
        if (preg_match_all('/CONCEPTO_(\d+)/', $formula, $matches)) {
            $referenced = array_map('intval', $matches[1]);
        }

        return array_unique($referenced);
    }

    /**
     * Verificar referencia circular recursivamente
     */
    private function hasCircularReference($originalId, $currentId, $visited)
    {
        if (in_array($currentId, $visited)) {
            return true; // Ciclo detectado
        }

        if ($currentId == $originalId) {
            return true; // Referencia a sí mismo
        }

        $visited[] = $currentId;
        $concept = $this->find($currentId);
        
        if (!$concept || empty($concept['formula'])) {
            return false;
        }

        $referenced = $this->getReferencedConcepts($concept['formula']);
        
        foreach ($referenced as $refId) {
            if ($this->hasCircularReference($originalId, $refId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener historial de cambios de un concepto
     */
    public function getChangeHistory($conceptId)
    {
        try {
            $sql = "SELECT 
                        ch.*,
                        u.firstname,
                        u.lastname
                    FROM concept_changes ch
                    LEFT JOIN users u ON ch.user_id = u.id
                    WHERE ch.concept_id = ?
                    ORDER BY ch.changed_at DESC
                    LIMIT 50";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$conceptId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo historial de cambios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registrar cambio en el historial
     */
    private function logChange($conceptId, $changeType, $oldValue, $newValue, $field)
    {
        try {
            // Solo registrar si existe la tabla de historial
            $sql = "SELECT COUNT(*) FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'concept_changes'";
            $stmt = $this->db->query($sql);
            if ($stmt->fetchColumn() == 0) {
                return; // Tabla no existe, skip logging
            }

            $sql = "INSERT INTO concept_changes 
                    (concept_id, change_type, field_name, old_value, new_value, user_id, changed_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $conceptId,
                $changeType,
                $field,
                $oldValue,
                $newValue,
                $_SESSION['admin_id'] ?? null
            ]);
        } catch (\Exception $e) {
            // Error en logging no debe interrumpir la operación principal
            error_log("Error registrando cambio: " . $e->getMessage());
        }
    }

    /**
     * Actualizar concepto con logging de cambios
     */
    public function update($id, $data)
    {
        try {
            // Obtener valores anteriores para el historial
            $oldConcept = $this->find($id);
            
            // Validar fórmula si se está actualizando
            if (isset($data['formula']) && !empty($data['formula'])) {
                $validation = $this->validateFormula($data['formula']);
                if (!$validation['valid']) {
                    throw new \Exception('Fórmula inválida: ' . $validation['message']);
                }

                // Verificar dependencias circulares
                $depValidation = $this->checkCircularDependency($id, $data['formula']);
                if (!$depValidation['valid']) {
                    throw new \Exception('Dependencia circular: ' . $depValidation['message']);
                }
            }

            $result = parent::update($id, $data);

            // Registrar cambios en el historial
            if ($result && $oldConcept) {
                foreach ($data as $field => $newValue) {
                    $oldValue = $oldConcept[$field] ?? null;
                    if ($oldValue != $newValue) {
                        $this->logChange($id, 'UPDATE', $oldValue, $newValue, $field);
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            error_log("Error actualizando concepto ID $id: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Crear concepto con validaciones avanzadas
     */
    public function create($data)
    {
        try {
            // Validar fórmula si se proporciona
            if (!empty($data['formula'])) {
                $validation = $this->validateFormula($data['formula']);
                if (!$validation['valid']) {
                    throw new \Exception('Fórmula inválida: ' . $validation['message']);
                }
            }

            // Verificar descripción única
            if ($this->isDescriptionDuplicate($data['descripcion'])) {
                throw new \Exception('Ya existe un concepto con esta descripción');
            }

            $conceptId = parent::create($data);

            // Registrar creación en el historial
            if ($conceptId) {
                $this->logChange($conceptId, 'CREATE', null, json_encode($data), 'all');
            }

            return $conceptId;
        } catch (\Exception $e) {
            error_log("Error creando concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si la descripción ya existe
     */
    private function isDescriptionDuplicate($description, $excludeId = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE descripcion = ?";
            $params = [$description];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error verificando descripción duplicada: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el código ya existe
     */
    public function isCodeDuplicate($code, $excludeId = null)
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE concepto = ?";
            $params = [$code];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (\Exception $e) {
            error_log("Error checking code duplicate: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Duplicar concepto (crear copia)
     */
    public function duplicate($id, $newDescription)
    {
        try {
            $original = $this->find($id);
            if (!$original) {
                return false;
            }

            // Verificar que la nueva descripción no exista
            if ($this->isDescriptionDuplicate($newDescription)) {
                throw new \Exception('Ya existe un concepto con la descripción: ' . $newDescription);
            }

            $newConcept = $original;
            unset($newConcept['id']);
            unset($newConcept['created_on']);
            unset($newConcept['updated_on']);
            $newConcept['descripcion'] = $newDescription;
            $newConcept['activo'] = 0; // Crear inactivo por seguridad

            $newId = $this->create($newConcept);
            
            if ($newId) {
                $this->logChange($newId, 'DUPLICATE', null, 'Duplicado desde concepto ID: ' . $id, 'origin');
            }

            return $newId;
        } catch (\Exception $e) {
            error_log("Error duplicando concepto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener conceptos similares por descripción
     */
    public function findSimilar($description, $limit = 5)
    {
        try {
            $sql = "SELECT *, 
                        CASE 
                            WHEN LOWER(descripcion) = LOWER(?) THEN 100
                            WHEN descripcion LIKE ? THEN 80
                            WHEN descripcion LIKE ? THEN 60
                            ELSE 40
                        END as similarity_score
                    FROM {$this->table} 
                    WHERE descripcion LIKE ?
                    ORDER BY similarity_score DESC, descripcion
                    LIMIT ?";
            
            $searchTerm = '%' . $description . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $description,
                $description . '%',
                '%' . $description,
                $searchTerm,
                $limit
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando conceptos similares: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos activos para usar en planillas
     */
    public function getActiveConceptsForPayroll()
    {
        try {
            $sql = "SELECT 
                        id,
                        concepto,
                        descripcion,
                        tipo_concepto as tipo,
                        formula,
                        valor_fijo,
                        modifica_valor
                    FROM {$this->table} 
                    WHERE activo = 1 
                    ORDER BY tipo_concepto DESC, descripcion ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos activos para planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener conceptos aplicables a un tipo específico de planilla
     */
    public function getConceptsForPayrollType($payrollTypeId)
    {
        try {
            $sql = "SELECT DISTINCT c.* 
                    FROM {$this->table} c
                    INNER JOIN concepto_tipos_planilla ctp ON c.id = ctp.concepto_id
                    WHERE c.activo = 1 
                    AND ctp.tipo_planilla_id = ?
                    ORDER BY c.tipo_concepto DESC, c.descripcion ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$payrollTypeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo conceptos por tipo de planilla: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un concepto es aplicable a un empleado específico
     */
    public function isApplicableToEmployee($conceptId, $employeeId)
    {
        try {
            // Obtener información del concepto y empleado
            $concept = $this->find($conceptId);
            if (!$concept || !$concept['activo']) {
                return false;
            }

            // Por ahora retornamos true, pero aquí se pueden agregar 
            // validaciones específicas según situación del empleado,
            // tipo de planilla, etc.
            return true;
        } catch (\Exception $e) {
            error_log("Error verificando aplicabilidad del concepto: " . $e->getMessage());
            return false;
        }
    }
}