<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de partidas presupuestarias
 * Hereda funcionalidad CRUD básica de ReferenceController
 *
 * Las partidas presupuestarias se asignan a:
 * - Empleados (para clasificación presupuestaria)
 * - Posiciones (estructura organizacional)
 */
class PartidaPresupuestaria extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'PartidaPresupuestaria';
        $this->viewPath = 'partidas_presupuestarias';
        $this->routeName = 'partidas-presupuestarias';
        $this->singularName = 'Partida Presupuestaria';
        $this->pluralName = 'Partidas Presupuestarias';
    }
}
