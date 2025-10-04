<?php

namespace App\Controllers;

use App\Core\ReferenceController;
use App\Core\AuthMiddleware;
use App\Core\Security;

/**
 * Controlador para gestión de tipos de planilla
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class TipoPlanillaController extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'TipoPlanilla';
        $this->viewPath = 'tipos-planilla';
        $this->routeName = 'tipos-planilla';
        $this->singularName = 'Tipo de Planilla';
        $this->pluralName = 'Tipos de Planilla';
    }

    /**
     * Override update para actualizar sessionStorage cuando se edita
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/panel/{$this->routeName}");
        }

        AuthMiddleware::validateCSRF();

        $data = Security::sanitizeInput($_POST);
        $model = $this->model($this->modelName);

        $item = $model->find($id);
        if (!$item) {
            $_SESSION['error'] = $this->singularName . ' no encontrado';
            $this->redirect("/panel/{$this->routeName}");
        }

        // Validación
        $errors = $model->validateReferenceUpdateData($data);

        // Validar unicidad de código (excluyendo el actual)
        if (isset($data['edit_codigo']) && !$model->isCodigoUnique($data['edit_codigo'], $id)) {
            $errors['edit_codigo'] = 'El código ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/{$this->routeName}/{$id}/edit");
        }

        try {
            $updateData = [
                'codigo' => $data['edit_codigo'],
                'nombre' => $data['edit_nombre'],
                'descripcion' => $data['edit_descripcion'] ?? '',
                'activo' => isset($data['edit_activo']) ? 1 : 0
            ];

            $model->update($id, $updateData);

            $_SESSION['success'] = $this->singularName . ' actualizado exitosamente';

            // Guardar flag para actualizar sessionStorage en el cliente
            $_SESSION['update_session_storage'] = [
                'tipo_planilla_id' => $id,
                'tipo_planilla_nombre' => $updateData['nombre']
            ];

            $this->redirect("/panel/{$this->routeName}");
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar ' . strtolower($this->singularName) . ': ' . $e->getMessage();
            $this->redirect("/panel/{$this->routeName}/{$id}/edit");
        }
    }
}