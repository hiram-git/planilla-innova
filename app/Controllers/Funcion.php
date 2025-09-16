<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de funciones
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class Funcion extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Funcion';
        $this->viewPath = 'funciones';
        $this->routeName = 'funciones';
        $this->singularName = 'Función';
        $this->pluralName = 'Funciones';
    }
}