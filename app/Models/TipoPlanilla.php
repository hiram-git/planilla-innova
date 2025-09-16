<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de tipos de planilla
 * Hereda funcionalidad CRUD básica de ReferenceModel
 */
class TipoPlanilla extends ReferenceModel
{
    public $table = 'tipos_planilla';

    /**
     * Buscar por código (método específico adicional)
     */
    public function findByCodigo($codigo)
    {
        return $this->first('codigo', $codigo);
    }
}