<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * Modelo para valores de campos adicionales por empleado
 *
 * Gestiona la relación entre empleados y sus valores personalizados
 * en campos adicionales definidos en el sistema.
 */
class EmployeeAdditionalFieldValue extends Model
{
    public $table = 'employee_additional_field_values';

    protected $fillable = [
        'employee_id',
        'field_id',
        'valor'
    ];

    /**
     * Obtener todos los valores de campos adicionales de un empleado
     *
     * @param int $employeeId
     * @return array
     */
    public function getByEmployee(int $employeeId): array
    {
        try {
            $sql = "SELECT
                        eafv.*,
                        eaf.codigo,
                        eaf.etiqueta,
                        eaf.descripcion,
                        eaf.tipo_dato,
                        eaf.orden
                    FROM {$this->table} eafv
                    INNER JOIN employee_additional_fields eaf ON eafv.field_id = eaf.id
                    WHERE eafv.employee_id = ?
                    ORDER BY eaf.orden ASC, eaf.etiqueta ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo valores de campos adicionales del empleado $employeeId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener valor de un campo específico por código o etiqueta
     *
     * @param int $employeeId
     * @param string $codigoOEtiqueta
     * @return mixed|null
     */
    public function getValueByCode(int $employeeId, string $codigoOEtiqueta)
    {
        try {
            $sql = "SELECT eafv.valor, eaf.tipo_dato
                    FROM {$this->table} eafv
                    INNER JOIN employee_additional_fields eaf ON eafv.field_id = eaf.id
                    WHERE eafv.employee_id = ?
                    AND (eaf.codigo = ? OR eaf.etiqueta = ?)
                    AND eaf.activo = 1
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $codigoOEtiqueta, $codigoOEtiqueta]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return null;
            }

            // Convertir según tipo de dato
            return $this->castValue($result['valor'], $result['tipo_dato']);

        } catch (PDOException $e) {
            error_log("Error obteniendo valor de campo '$codigoOEtiqueta' para empleado $employeeId: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Guardar o actualizar valor de un campo para un empleado
     *
     * @param int $employeeId
     * @param int $fieldId
     * @param mixed $valor
     * @return bool
     */
    public function saveFieldValue(int $employeeId, int $fieldId, $valor): bool
    {
        try {
            // Validar que el campo exista
            $fieldModel = new EmployeeAdditionalField();
            $field = $fieldModel->find($fieldId);

            if (!$field) {
                error_log("Campo adicional con ID $fieldId no encontrado");
                return false;
            }

            // Validar el valor según el tipo de dato
            $validationResult = $fieldModel->validateValueForType($valor, $field['tipo_dato']);
            if (!$validationResult['valid']) {
                error_log("Valor inválido para campo $fieldId: {$validationResult['message']}");
                return false;
            }

            // Verificar si ya existe un valor
            $sql = "SELECT id FROM {$this->table}
                    WHERE employee_id = ? AND field_id = ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$employeeId, $fieldId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Actualizar valor existente
                return $this->update($existing['id'], ['valor' => $valor]);
            } else {
                // Crear nuevo valor
                $newId = $this->create([
                    'employee_id' => $employeeId,
                    'field_id' => $fieldId,
                    'valor' => $valor
                ]);

                return $newId !== false;
            }

        } catch (PDOException $e) {
            error_log("Error guardando valor de campo adicional: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Guardar múltiples valores para un empleado
     *
     * @param int $employeeId
     * @param array $values Array asociativo [field_id => valor]
     * @return array ['success' => bool, 'saved' => int, 'errors' => int]
     */
    public function saveBulkValues(int $employeeId, array $values): array
    {
        $saved = 0;
        $errors = 0;

        foreach ($values as $fieldId => $valor) {
            // Saltar campos vacíos (excepto para BOOLEAN que puede ser 0)
            if ($valor === '' || $valor === null) {
                continue;
            }

            if ($this->saveFieldValue($employeeId, $fieldId, $valor)) {
                $saved++;
            } else {
                $errors++;
            }
        }

        return [
            'success' => $errors === 0,
            'saved' => $saved,
            'errors' => $errors
        ];
    }

    /**
     * Validar valor según tipo de dato del campo
     *
     * @param int $fieldId
     * @param mixed $valor
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateValue(int $fieldId, $valor): array
    {
        try {
            $fieldModel = new EmployeeAdditionalField();
            $field = $fieldModel->find($fieldId);

            if (!$field) {
                return ['valid' => false, 'message' => 'Campo no encontrado'];
            }

            return $fieldModel->validateValueForType($valor, $field['tipo_dato']);

        } catch (\Exception $e) {
            return ['valid' => false, 'message' => 'Error en validación: ' . $e->getMessage()];
        }
    }

    /**
     * Convertir valor según tipo de dato
     *
     * @param mixed $value
     * @param string $tipoDato
     * @return mixed
     */
    protected function castValue($value, string $tipoDato)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($tipoDato) {
            case 'NUMERO':
                return (float)$value;

            case 'BOOLEAN':
                return (bool)$value;

            case 'FECHA':
                return $value; // Mantener como string en formato Y-m-d

            case 'TEXTO':
            default:
                return (string)$value;
        }
    }

    /**
     * Eliminar todos los valores de un empleado
     *
     * @param int $employeeId
     * @return bool
     */
    public function deleteByEmployee(int $employeeId): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE employee_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$employeeId]);
        } catch (PDOException $e) {
            error_log("Error eliminando valores de empleado $employeeId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar valor específico de un campo para un empleado
     *
     * @param int $employeeId
     * @param int $fieldId
     * @return bool
     */
    public function deleteFieldValue(int $employeeId, int $fieldId): bool
    {
        try {
            $sql = "DELETE FROM {$this->table}
                    WHERE employee_id = ? AND field_id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$employeeId, $fieldId]);
        } catch (PDOException $e) {
            error_log("Error eliminando valor de campo $fieldId para empleado $employeeId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener empleados que tienen un valor específico en un campo
     *
     * @param int $fieldId
     * @param mixed $valor
     * @return array
     */
    public function getEmployeesByFieldValue(int $fieldId, $valor): array
    {
        try {
            $sql = "SELECT
                        e.id,
                        e.employee_id,
                        CONCAT(e.firstname, ' ', e.lastname) as nombre_completo,
                        eafv.valor
                    FROM {$this->table} eafv
                    INNER JOIN employees e ON eafv.employee_id = e.id
                    WHERE eafv.field_id = ?
                    AND eafv.valor = ?
                    ORDER BY e.lastname, e.firstname";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fieldId, $valor]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error buscando empleados por valor de campo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de un campo adicional
     *
     * @param int $fieldId
     * @return array
     */
    public function getFieldStats(int $fieldId): array
    {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT employee_id) as total_empleados,
                        COUNT(*) as total_valores,
                        MIN(updated_at) as primera_asignacion,
                        MAX(updated_at) as ultima_actualizacion
                    FROM {$this->table}
                    WHERE field_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fieldId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total_empleados' => 0,
                'total_valores' => 0,
                'primera_asignacion' => null,
                'ultima_actualizacion' => null
            ];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas del campo $fieldId: " . $e->getMessage());
            return [
                'total_empleados' => 0,
                'total_valores' => 0,
                'primera_asignacion' => null,
                'ultima_actualizacion' => null
            ];
        }
    }
}
