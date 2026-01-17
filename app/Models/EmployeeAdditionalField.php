<?php

namespace App\Models;

use App\Core\Model;
use PDO;
use PDOException;

/**
 * Modelo para gestión de campos adicionales personalizados de empleados
 *
 * Permite definir campos personalizados con código único, tipo de dato,
 * valor por defecto y orden de visualización.
 */
class EmployeeAdditionalField extends Model
{
    public $table = 'employee_additional_fields';

    protected $fillable = [
        'codigo',
        'etiqueta',
        'descripcion',
        'tipo_dato',
        'valor_defecto',
        'orden',
        'activo'
    ];

    /**
     * Tipos de datos permitidos para campos adicionales
     */
    const TIPOS_DATO = ['TEXTO', 'NUMERO', 'FECHA', 'BOOLEAN'];

    /**
     * Obtener todos los campos adicionales activos ordenados
     *
     * @return array
     */
    public function getAllActive(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE activo = 1
                    ORDER BY orden ASC, etiqueta ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo campos adicionales activos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener todos los campos (activos e inactivos) para gestión
     *
     * @return array
     */
    public function getAllForManagement(): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}
                    ORDER BY orden ASC, etiqueta ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo todos los campos adicionales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar campo por código único
     *
     * @param string $codigo
     * @return array|null
     */
    public function getByCode(string $codigo): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE codigo = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$codigo]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error buscando campo por código '$codigo': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar que el código sea único (excluyendo un ID opcional)
     *
     * @param string $codigo
     * @param int|null $excludeId
     * @return bool
     */
    public function isCodeUnique(string $codigo, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE codigo = ?";
            $params = [$codigo];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] == 0;
        } catch (PDOException $e) {
            error_log("Error validando unicidad de código: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar datos de campo adicional
     *
     * @param array $data
     * @param int|null $excludeId
     * @return array Errores de validación
     */
    public function validateFieldData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Validar código
        if (empty($data['codigo'])) {
            $errors['codigo'] = 'El código es obligatorio';
        } elseif (!preg_match('/^[A-Z0-9_]+$/', $data['codigo'])) {
            $errors['codigo'] = 'El código solo puede contener letras mayúsculas, números y guiones bajos';
        } elseif (strlen($data['codigo']) > 50) {
            $errors['codigo'] = 'El código no puede exceder 50 caracteres';
        } elseif (!$this->isCodeUnique($data['codigo'], $excludeId)) {
            $errors['codigo'] = 'Este código ya está en uso';
        }

        // Validar etiqueta
        if (empty($data['etiqueta'])) {
            $errors['etiqueta'] = 'La etiqueta es obligatoria';
        } elseif (strlen($data['etiqueta']) > 100) {
            $errors['etiqueta'] = 'La etiqueta no puede exceder 100 caracteres';
        }

        // Validar descripción (opcional)
        if (!empty($data['descripcion']) && strlen($data['descripcion']) > 255) {
            $errors['descripcion'] = 'La descripción no puede exceder 255 caracteres';
        }

        // Validar tipo de dato
        if (empty($data['tipo_dato'])) {
            $errors['tipo_dato'] = 'El tipo de dato es obligatorio';
        } elseif (!in_array($data['tipo_dato'], self::TIPOS_DATO)) {
            $errors['tipo_dato'] = 'Tipo de dato inválido';
        }

        // Validar valor por defecto según tipo de dato
        if (!empty($data['valor_defecto']) && !empty($data['tipo_dato'])) {
            $validationResult = $this->validateValueForType($data['valor_defecto'], $data['tipo_dato']);
            if (!$validationResult['valid']) {
                $errors['valor_defecto'] = $validationResult['message'];
            }
        }

        // Validar orden
        if (isset($data['orden']) && !is_numeric($data['orden'])) {
            $errors['orden'] = 'El orden debe ser un número';
        }

        return $errors;
    }

    /**
     * Validar que un valor sea compatible con el tipo de dato
     *
     * @param mixed $value
     * @param string $tipoDato
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateValueForType($value, string $tipoDato): array
    {
        switch ($tipoDato) {
            case 'NUMERO':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => 'El valor debe ser numérico'];
                }
                break;

            case 'FECHA':
                if (!\DateTime::createFromFormat('Y-m-d', $value)) {
                    return ['valid' => false, 'message' => 'El valor debe ser una fecha válida (YYYY-MM-DD)'];
                }
                break;

            case 'BOOLEAN':
                if (!in_array($value, ['0', '1', 0, 1, true, false], true)) {
                    return ['valid' => false, 'message' => 'El valor debe ser 0 o 1'];
                }
                break;

            case 'TEXTO':
                // El texto acepta cualquier valor
                if (strlen($value) > 255) {
                    return ['valid' => false, 'message' => 'El texto no puede exceder 255 caracteres'];
                }
                break;

            default:
                return ['valid' => false, 'message' => 'Tipo de dato desconocido'];
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Activar o desactivar un campo
     *
     * @param int $id
     * @param bool $activo
     * @return bool
     */
    public function toggleActive(int $id, bool $activo): bool
    {
        try {
            return $this->update($id, ['activo' => $activo ? 1 : 0]);
        } catch (\Exception $e) {
            error_log("Error cambiando estado de campo adicional: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar orden de un campo
     *
     * @param int $id
     * @param int $nuevoOrden
     * @return bool
     */
    public function updateOrder(int $id, int $nuevoOrden): bool
    {
        try {
            return $this->update($id, ['orden' => $nuevoOrden]);
        } catch (\Exception $e) {
            error_log("Error actualizando orden de campo adicional: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener siguiente número de orden disponible
     *
     * @return int
     */
    public function getNextOrder(): int
    {
        try {
            $sql = "SELECT MAX(orden) as max_orden FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ($result['max_orden'] ?? 0) + 1;
        } catch (PDOException $e) {
            error_log("Error obteniendo siguiente orden: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Eliminar campo adicional
     * (Los valores asociados se eliminan automáticamente por CASCADE)
     *
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
    {
        try {
            // Verificar si hay valores asociados
            $sql = "SELECT COUNT(*) as count FROM employee_additional_field_values WHERE field_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $hasValues = $result['count'] > 0;

            // Eliminar (CASCADE se encargará de los valores)
            $deleted = parent::delete($id);

            if ($deleted && $hasValues) {
                error_log("Campo adicional ID $id eliminado junto con {$result['count']} valores asociados");
            }

            return $deleted;
        } catch (\Exception $e) {
            error_log("Error eliminando campo adicional: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de uso de un campo
     *
     * @param int $id
     * @return array
     */
    public function getUsageStats(int $id): array
    {
        try {
            $sql = "SELECT
                        COUNT(DISTINCT eafv.employee_id) as empleados_usando,
                        COUNT(*) as total_valores
                    FROM employee_additional_field_values eafv
                    WHERE eafv.field_id = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'empleados_usando' => 0,
                'total_valores' => 0
            ];
        } catch (PDOException $e) {
            error_log("Error obteniendo estadísticas de uso: " . $e->getMessage());
            return [
                'empleados_usando' => 0,
                'total_valores' => 0
            ];
        }
    }
}
