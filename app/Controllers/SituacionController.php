<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de situaciones de empleados
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class SituacionController extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Situacion';
        $this->viewPath = 'situaciones';
        $this->routeName = 'situaciones';
        $this->singularName = 'Situación';
        $this->pluralName = 'Situaciones';
    }
}