<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de cargos
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class Cargo extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Cargo';
        $this->viewPath = 'cargos';
        $this->routeName = 'cargos';
        $this->singularName = 'Cargo';
        $this->pluralName = 'Cargos';
    }
}