<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de frecuencias
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class FrecuenciaController extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Frecuencia';
        $this->viewPath = 'frecuencias';
        $this->routeName = 'frecuencias';
        $this->singularName = 'Frecuencia';
        $this->pluralName = 'Frecuencias';
    }
}