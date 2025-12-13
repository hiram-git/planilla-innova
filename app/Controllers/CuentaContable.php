<?php

namespace App\Controllers;

use App\Core\ReferenceController;

/**
 * Controlador para gestión de cuentas contables (antes: partidas)
 * Hereda funcionalidad CRUD básica de ReferenceController
 *
 * Las cuentas contables se asignan a:
 * - Conceptos de planilla (para clasificación contable)
 */
class CuentaContable extends ReferenceController
{
    protected function initializeNames()
    {
        $this->modelName = 'CuentaContable';
        $this->viewPath = 'cuentas_contables';
        $this->routeName = 'cuentas-contables';
        $this->singularName = 'Cuenta Contable';
        $this->pluralName = 'Cuentas Contables';
    }
}
