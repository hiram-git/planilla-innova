<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de situaciones de empleado
 * Hereda funcionalidad CRUD básica de ReferenceModel
 */
class Situacion extends ReferenceModel
{
    public $table = 'situaciones';

    /**
     * Buscar por código (método específico adicional)
     */
    public function findByCodigo($codigo)
    {
        return $this->first('codigo', $codigo);
    }
}