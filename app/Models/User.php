<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use PDO;
use Exception;

/**
 * Modelo User - Gestión de usuarios del sistema
 * Integra con tabla legacy 'admin' pero con arquitectura moderna
 */
class User extends Model
{
    public $table = 'admin';
    protected $fillable = [
        'username', 'password', 'firstname', 'lastname', 
        'photo', 'created_on', 'role_id', 'status'
    ];

    /**
     * Obtener todos los usuarios con información de rol
     */
    public function getAllWithRoles()
    {
        try {
            $sql = "SELECT u.*, r.name as role_name, r.description as role_description
                    FROM {$this->table} u
                    LEFT JOIN roles r ON u.role_id = r.id
                    ORDER BY u.id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting users with roles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Autenticar usuario
     */
    public function authenticate($username, $password)
    {
        try {
            $sql = "SELECT u.*, r.name as role_name, r.status as role_status
                    FROM {$this->table} u
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE u.username = ? AND u.status = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Verificar que el rol esté activo
                if ($user['role_status'] != 1) {
                    return ['success' => false, 'message' => 'El rol del usuario no está activo'];
                }
                
                // Registrar último acceso
                $this->updateLastLogin($user['id']);
                
                // Remover password del array de retorno
                unset($user['password']);
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        } catch (Exception $e) {
            error_log("Error authenticating user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en autenticación'];
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function create($data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateUserData($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar que el username no exista
            if ($this->usernameExists($data['username'])) {
                return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
            }

            // Hash de la contraseña
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['created_on'] = date('Y-m-d');

            // Manejo de foto
            $data['photo'] = $this->handlePhotoUpload($data);

            $sql = "INSERT INTO {$this->table} (username, password, firstname, lastname, photo, created_on, role_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['username'],
                $data['password'],
                $data['firstname'],
                $data['lastname'],
                $data['photo'],
                $data['created_on'],
                $data['role_id'],
                $data['status'] ?? 1
            ]);

            if ($result) {
                $userId = $this->db->lastInsertId();
                // Usuario creado exitosamente
                return ['success' => true, 'id' => $userId];
            }

            return ['success' => false, 'message' => 'Error al crear usuario'];
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en creación de usuario'];
        }
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $data)
    {
        try {
            // Validaciones específicas
            $validation = $this->validateUserData($data, $id);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar que el username no exista (excluyendo el usuario actual)
            if ($this->usernameExists($data['username'], $id)) {
                return ['success' => false, 'message' => 'El nombre de usuario ya existe'];
            }

            // Construir query dinámicamente
            $fields = [];
            $values = [];

            foreach (['username', 'firstname', 'lastname', 'role_id', 'status'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            // Manejo de contraseña (solo si se proporciona)
            if (!empty($data['password'])) {
                $fields[] = "password = ?";
                $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            // Manejo de foto
            $newPhoto = $this->handlePhotoUpload($data);
            if ($newPhoto !== null) {
                $fields[] = "photo = ?";
                $values[] = $newPhoto;
            }

            $values[] = $id;

            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);

            if ($result) {
                // Usuario actualizado exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al actualizar usuario'];
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en actualización de usuario'];
        }
    }

    /**
     * Cambiar estado del usuario
     */
    public function changeStatus($id, $status)
    {
        try {
            $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $id]);

            if ($result) {
                // Estado de usuario cambiado exitosamente
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Error al cambiar estado'];
        } catch (Exception $e) {
            error_log("Error changing user status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en cambio de estado'];
        }
    }

    /**
     * Eliminar usuario (soft delete - cambiar a inactivo)
     */
    public function delete($id)
    {
        try {
            // Verificar que no sea el último admin activo
            if ($this->isLastActiveAdmin($id)) {
                return ['success' => false, 'message' => 'No se puede eliminar el último administrador activo'];
            }

            return $this->changeStatus($id, 0);
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en eliminación de usuario'];
        }
    }

    /**
     * Validar datos del usuario
     */
    private function validateUserData($data, $excludeId = null)
    {
        $errors = [];

        // Username requerido y formato
        if (empty($data['username'])) {
            $errors[] = 'El nombre de usuario es requerido';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'El nombre de usuario debe tener al menos 3 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'El nombre de usuario solo puede contener letras, números y guión bajo';
        }

        // Contraseña requerida solo en creación
        if (empty($excludeId) && empty($data['password'])) {
            $errors[] = 'La contraseña es requerida';
        } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        }

        // Nombres requeridos
        if (empty($data['firstname'])) {
            $errors[] = 'El nombre es requerido';
        }
        if (empty($data['lastname'])) {
            $errors[] = 'El apellido es requerido';
        }

        // Role ID requerido y existente
        if (empty($data['role_id'])) {
            $errors[] = 'El rol es requerido';
        } elseif (!$this->roleExists($data['role_id'])) {
            $errors[] = 'El rol seleccionado no existe';
        }

        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors)
        ];
    }

    /**
     * Verificar si username existe
     */
    private function usernameExists($username, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE username = ?";
            $params = [$username];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking username existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si rol existe
     */
    private function roleExists($roleId)
    {
        try {
            $sql = "SELECT id FROM roles WHERE id = ? AND status = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking role existence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si es el último admin activo
     */
    private function isLastActiveAdmin($userId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} u
                    JOIN roles r ON u.role_id = r.id
                    WHERE u.status = 1 AND r.name LIKE '%admin%' AND u.id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] <= 0;
        } catch (Exception $e) {
            error_log("Error checking last admin: " . $e->getMessage());
            return true; // Por seguridad, asumir que sí es el último
        }
    }

    /**
     * Manejo de subida de foto
     */
    private function handlePhotoUpload($data)
    {
        // Por ahora retornar la foto existente o vacía
        // En una implementación completa se manejarían los uploads
        return $data['photo'] ?? '';
    }

    /**
     * Actualizar último acceso
     */
    private function updateLastLogin($userId)
    {
        try {
            // Agregar campo last_login si no existe
            $sql = "UPDATE {$this->table} SET created_on = created_on WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }

    /**
     * Buscar usuario por ID
     */
    public function findById($id)
    {
        return $this->find($id);
    }

    /**
     * Obtener permisos del usuario
     */
    public function getUserPermissions($userId)
    {
        try {
            $sql = "SELECT rp.menu_id, rp.read_perm, rp.write_perm, rp.delete_perm
                    FROM {$this->table} u
                    JOIN role_permissions rp ON u.role_id = rp.role_id
                    WHERE u.id = ? AND u.status = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            $permissions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[$row['menu_id']] = [
                    'read' => (bool)$row['read_perm'],
                    'write' => (bool)$row['write_perm'],
                    'delete' => (bool)$row['delete_perm']
                ];
            }
            
            return $permissions;
        } catch (Exception $e) {
            error_log("Error getting user permissions: " . $e->getMessage());
            return [];
        }
    }
}