<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de horarios
 * Hereda funcionalidad CRUD básica de ReferenceController + métodos específicos para horarios
 */
class Schedule extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Schedule';
        $this->viewPath = 'schedules';
        $this->routeName = 'schedules';
        $this->singularName = 'Horario';
        $this->pluralName = 'Horarios';
    }

    /**
     * Override del método store para manejar campos específicos de horarios
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/panel/{$this->routeName}");
        }

        \App\Middleware\AuthMiddleware::validateCSRF();

        $data = \App\Core\Security::sanitizeInput($_POST);
        $model = $this->model($this->modelName);

        // Validación específica para horarios
        $errors = $model->validateReferenceData($data);

        // Validar unicidad de código
        if (isset($data['codigo']) && !$model->isCodigoUnique($data['codigo'])) {
            $errors['codigo'] = 'El código ya está registrado';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_data'] = $data;
            $this->redirect("/panel/{$this->routeName}/create");
        }

        try {
            $createData = [
                'codigo' => $data['codigo'],
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'time_in' => $data['time_in'],
                'time_out' => $data['time_out'],
                'activo' => 1
            ];

            $result = $model->create($createData);
            $_SESSION['success'] = $this->singularName . ' creado exitosamente';
            $this->redirect("/panel/{$this->routeName}");
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al crear ' . strtolower($this->singularName) . ': ' . $e->getMessage();
            $this->redirect("/panel/{$this->routeName}/create");
        }
    }

    /**
     * Override del método update para manejar campos específicos de horarios
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("/panel/{$this->routeName}");
        }

        \App\Middleware\AuthMiddleware::validateCSRF();

        $data = \App\Core\Security::sanitizeInput($_POST);
        $model = $this->model($this->modelName);

        $item = $model->find($id);
        if (!$item) {
            $_SESSION['error'] = $this->singularName . ' no encontrado';
            $this->redirect("/panel/{$this->routeName}");
        }

        // Validación específica para horarios
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
                'time_in' => $data['edit_time_in'],
                'time_out' => $data['edit_time_out'],
                'activo' => isset($data['edit_activo']) ? 1 : 0
            ];
            
            $model->update($id, $updateData);
            $_SESSION['success'] = $this->singularName . ' actualizado exitosamente';
            $this->redirect("/panel/{$this->routeName}");
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error al actualizar ' . strtolower($this->singularName) . ': ' . $e->getMessage();
            $this->redirect("/panel/{$this->routeName}/{$id}/edit");
        }
    }
}