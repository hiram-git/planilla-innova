<?php

namespace App\Controllers;

use App\Core\ReferenceController;

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
}