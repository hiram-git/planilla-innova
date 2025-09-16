<?php

namespace App\Models;

use App\Core\ReferenceModel;

/**
 * Modelo para gestión de frecuencias
 * Hereda funcionalidad CRUD básica de ReferenceModel
 */
class Frecuencia extends ReferenceModel
{
    public $table = 'frecuencias';

    /**
     * Buscar por código (método específico adicional)
     */
    public function findByCodigo($codigo)
    {
        return $this->first('codigo', $codigo);
    }
}