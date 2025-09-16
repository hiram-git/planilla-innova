<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de partidas presupuestarias
 * Hereda funcionalidad CRUD básica de ReferenceController
 */
class Partida extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'Partida';
        $this->viewPath = 'partidas';
        $this->routeName = 'partidas';
        $this->singularName = 'Partida';
        $this->pluralName = 'Partidas';
    }
}