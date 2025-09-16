<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use Exception;

/**
 * Modelo Role - Gestión de roles y permisos del sistema
 * Integra con tablas legacy 'roles' y 'role_permissions'
 */
class Role extends Model
{
    public $table = 'roles';
    protected $fillable = ['name', 'description', 'status'];

    /**
     * Módulos del sistema con sus IDs de menú
     */
    private $systemModules = [
        1 => ['name' => 'Dashboard', 'url' => 'home.php', 'icon' => 'fas fa-tachometer-alt'],
        2 => ['name' => 'Datos de empresa', 'url' => 'datos_empresa.php', 'icon' => 'fas fa-building'],
        3 => ['name' => 'Posiciones', 'url' => 'positions', 'icon' => 'fas fa-sitemap'],
        4 => ['name' => 'Partidas', 'url' => 'partidas', 'icon' => 'fas fa-list-alt'],
        5 => ['name' => 'Organigrama', 'url' => 'organigrama.php', 'icon' => 'fas fa-project-diagram'],
        6 => ['name' => 'Cargos', 'url' => 'cargos', 'icon' => 'fas fa-user-tie'],
        7 => ['name' => 'Funciones', 'url' => 'funciones', 'icon' => 'fas fa-tasks'],
        8 => ['name' => 'Colaboradores', 'url' => 'employees', 'icon' => 'fas fa-users'],
        9 => ['name' => 'Horas Extras', 'url' => 'overtime.php', 'icon' => 'fas fa-clock'],
        10 => ['name' => 'Horarios', 'url' => 'schedules', 'icon' => 'fas fa-calendar-alt'],
        11 => ['name' => 'Asistencia', 'url' => 'attendance.php', 'icon' => 'fas fa-calendar-check'],
        12 => ['name' => 'Acreedores', 'url' => 'deduction.php', 'icon' => 'fas fa-hand-holding-usd'],
        13 => ['name' => 'Planillas', 'url' => 'payrolls', 'icon' => 'fas fa-file-invoice-dollar'],
        14 => ['name' => 'Conceptos', 'url' => 'concepts', 'icon' => 'fas fa-calculator'],
        15 => ['name' => 'Tipos de Planilla', 'url' => 'tipos-planilla', 'icon' => 'fas fa-tags'],
        16 => ['name' => 'Usuarios', 'url' => 'users', 'icon' => 'fas fa-user-cog'],
        17 => ['name' => 'Roles', 'url' => 'roles', 'icon' => 'fas fa-user-shield']
    ];

    /**
     * Obtener todos los roles activos
     */
    public function getAllActive()
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE status = 1 ORDER BY name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting active roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener rol con permisos
     */
    public function getRoleWithPermissions($id)
    {
        try {
            // Obtener rol
            $role = $this->findById($id);
            if (!$role) {
                return null;
            }

            // Obtener permisos
            $sql = "SELECT menu_id, read_perm, write_perm, delete_perm 
                    FROM role_permissions 
                    WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $permissions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[$row['menu_id']] = [
                    'read' => (bool)$row['read_perm'],
                    'write' => (bool)$row['write_perm'],
                    'delete' => (bool)$row['delete_perm']
                ];
            }

            $role['permissions'] = $permissions;
            return $role;
        } catch (Exception $e) {
            error_log("Error getting role with permissions: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crear nuevo rol
     */
    public function create($data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateRoleData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar que el nombre no exista
            if ($this->nameExists($data['name'])) {
                return ['success' => false, 'message' => 'Ya existe un rol con ese nombre'];
            }

            $this->db->beginTransaction();

            try {
                // Crear rol
                $sql = "INSERT INTO {$this->table} (name, description, status, created_at)
                        VALUES (?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    $data['name'],
                    $data['description'] ?? '',
                    $data['status'] ?? 1
                ]);

                if (!$result) {
                    throw new Exception("Error al crear el rol");
                }

                $roleId = $this->db->lastInsertId();

                // Crear permisos
                $this->saveRolePermissions($roleId, $data['permissions'] ?? []);

                $this->db->commit();
                // Rol creado exitosamente
                return ['success' => true, 'id' => $roleId];

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error creating role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en creación de rol'];
        }
    }

    /**
     * Actualizar rol
     */
    public function update($id, $data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateRoleData($data, $id);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar que el nombre no exista (excluyendo el rol actual)
            if ($this->nameExists($data['name'], $id)) {
                return ['success' => false, 'message' => 'Ya existe un rol con ese nombre'];
            }

            $this->db->beginTransaction();

            try {
                // Actualizar rol
                $sql = "UPDATE {$this->table} 
                        SET name = ?, description = ?, status = ? 
                        WHERE id = ?";
                
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute([
                    $data['name'],
                    $data['description'] ?? '',
                    $data['status'] ?? 1,
                    $id
                ]);

                if (!$result) {
                    throw new Exception("Error al actualizar el rol");
                }

                // Actualizar permisos
                $this->saveRolePermissions($id, $data['permissions'] ?? []);

                $this->db->commit();
                // Rol actualizado exitosamente
                return ['success' => true];

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error updating role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en actualización de rol'];
        }
    }

    /**
     * Cambiar estado del rol
     */
    public function changeStatus($id, $status)
    {
        try {
            // Verificar que no sea el último rol admin activo
            if ($status == 0 && $this->isLastActiveAdminRole($id)) {
                return ['success' => false, 'message' => 'No se puede desactivar el último rol de administrador'];
            }

            $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $id]);

            if ($result) {
                // Estado de rol cambiado exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al cambiar estado'];
        } catch (Exception $e) {
            error_log("Error changing role status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en cambio de estado'];
        }
    }

    /**
     * Eliminar rol (soft delete)
     */
    public function delete($id)
    {
        try {
            // Verificar que no tenga usuarios asignados
            if ($this->hasAssignedUsers($id)) {
                return ['success' => false, 'message' => 'No se puede eliminar un rol con usuarios asignados'];
            }

            return $this->changeStatus($id, 0);
        } catch (Exception $e) {
            error_log("Error deleting role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en eliminación de rol'];
        }
    }

    /**
     * Guardar permisos del rol
     */
    private function saveRolePermissions($roleId, $permissions)
    {
        try {
            // Eliminar permisos existentes
            $sql = "DELETE FROM role_permissions WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);

            // Insertar nuevos permisos
            if (!empty($permissions)) {
                $sql = "INSERT INTO role_permissions (role_id, menu_id, read_perm, write_perm, delete_perm)
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);

                foreach ($permissions as $menuId => $perms) {
                    $stmt->execute([
                        $roleId,
                        $menuId,
                        isset($perms['read']) ? 1 : 0,
                        isset($perms['write']) ? 1 : 0,
                        isset($perms['delete']) ? 1 : 0
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log("Error saving role permissions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validar datos del rol
     */
    private function validateRoleData($data, $excludeId = null)
    {
        $errors = [];

        // Nombre requerido
        if (empty($data['name'])) {
            $errors[] = 'El nombre del rol es requerido';
        } elseif (strlen($data['name']) < 2) {
            $errors[] = 'El nombre del rol debe tener al menos 2 caracteres';
        }

        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }

    /**
     * Verificar si nombre existe
     */
    private function nameExists($name, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE name = ?";
            $params = [$name];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking role name existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si es el último rol admin activo
     */
    private function isLastActiveAdminRole($roleId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}
                    WHERE status = 1 AND (name LIKE '%admin%' OR name LIKE '%administrador%') 
                    AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] <= 0;
        } catch (Exception $e) {
            error_log("Error checking last admin role: " . $e->getMessage());
            return true; // Por seguridad
        }
    }

    /**
     * Verificar si el rol tiene usuarios asignados
     */
    private function hasAssignedUsers($roleId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM admin WHERE role_id = ? AND status = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking assigned users: " . $e->getMessage());
            return true; // Por seguridad
        }
    }

    /**
     * Obtener módulos del sistema
     */
    public function getSystemModules()
    {
        return $this->systemModules;
    }

    /**
     * Verificar permiso específico
     */
    public function hasPermission($roleId, $menuId, $permissionType = 'read')
    {
        try {
            $column = $permissionType . '_perm';
            $sql = "SELECT {$column} FROM role_permissions WHERE role_id = ? AND menu_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId, $menuId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result[$column] : false;
        } catch (Exception $e) {
            error_log("Error checking permission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener roles con conteo de usuarios
     */
    public function getRolesWithUserCount()
    {
        try {
            $sql = "SELECT r.*, 
                           COUNT(u.id) as user_count,
                           COUNT(CASE WHEN u.status = 1 THEN 1 END) as active_user_count
                    FROM {$this->table} r
                    LEFT JOIN admin u ON r.id = u.role_id
                    GROUP BY r.id
                    ORDER BY r.name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting roles with user count: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar rol por ID
     */
    public function findById($id)
    {
        return $this->find($id);
    }

    /**
     * Clonar rol con sus permisos
     */
    public function cloneRole($sourceId, $newName, $newDescription = '')
    {
        try {
            // Obtener rol fuente con permisos
            $sourceRole = $this->getRoleWithPermissions($sourceId);
            if (!$sourceRole) {
                return ['success' => false, 'message' => 'Rol fuente no encontrado'];
            }

            // Crear nuevo rol
            $newRoleData = [
                'name' => $newName,
                'description' => $newDescription,
                'permissions' => $sourceRole['permissions']
            ];

            return $this->create($newRoleData);
        } catch (Exception $e) {
            error_log("Error cloning role: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al clonar rol'];
        }
    }
}