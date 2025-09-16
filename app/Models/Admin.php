<?php

namespace App\Models;

use App\Core\Model;

class Admin extends Model
{
    public $table = 'admin';
    public $fillable = ['username', 'password', 'firstname', 'lastname', 'photo', 'created_on'];

    public function authenticate($username, $password)
    {
        // ✅ REFACTORIZADO: Obtener usuario con información de rol
        $sql = "SELECT a.*, r.name as role_name, r.description as role_description 
                FROM admin a 
                LEFT JOIN roles r ON a.role_id = r.id 
                WHERE a.username = ? AND a.status = 1";
                
        $admin = $this->db->find($sql, [$username]);
        
        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        
        return false;
    }

    public function createAdmin($data)
    {
        $errors = $this->validateAdminData($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if (!$this->isUsernameUnique($data['username'])) {
            return ['success' => false, 'errors' => ['username' => 'El nombre de usuario ya existe']];
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['created_on'] = date('Y-m-d');

        try {
            $adminId = $this->create($data);
            return ['success' => true, 'id' => $adminId];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear administrador']];
        }
    }

    public function updateAdmin($id, $data)
    {
        $errors = $this->validateAdminData($data, true);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        if (!$this->isUsernameUnique($data['username'], $id)) {
            return ['success' => false, 'errors' => ['username' => 'El nombre de usuario ya existe']];
        }

        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }

        try {
            $this->update($id, $data);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al actualizar administrador']];
        }
    }

    public function validateAdminData($data, $isUpdate = false)
    {
        $rules = [
            'username' => 'required|min:3|max:30',
            'firstname' => 'required|min:2|max:50',
            'lastname' => 'required|min:2|max:50'
        ];

        if (!$isUpdate || !empty($data['password'])) {
            $rules['password'] = 'required|min:6';
        }

        return $this->validate($data, $rules);
    }

    public function isUsernameUnique($username, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM admin WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->find($sql, $params);
        return $result['count'] == 0;
    }

    public function getFullName($admin)
    {
        return trim($admin['firstname'] . ' ' . $admin['lastname']);
    }

    /**
     * ✅ NUEVO: Verificar si un usuario es super admin
     */
    public function isSuperAdmin($admin)
    {
        return isset($admin['role_id']) && $admin['role_id'] == 1;
    }

    /**
     * ✅ NUEVO: Obtener todos los roles activos
     */
    public function getAllRoles()
    {
        $sql = "SELECT * FROM roles WHERE status = 1 ORDER BY name";
        return $this->db->query($sql);
    }

    /**
     * ✅ NUEVO: Obtener información de rol por ID
     */
    public function getRoleById($roleId)
    {
        $sql = "SELECT * FROM roles WHERE id = ? AND status = 1";
        return $this->db->find($sql, [$roleId]);
    }

    /**
     * ✅ NUEVO: Obtener todos los usuarios con información de roles
     */
    public function getAllUsersWithRoles()
    {
        $sql = "SELECT a.*, r.name as role_name, r.description as role_description 
                FROM admin a 
                LEFT JOIN roles r ON a.role_id = r.id 
                WHERE a.status = 1 
                ORDER BY a.username";
        return $this->db->query($sql);
    }
}