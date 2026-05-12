<?php

namespace App\Models;

use CodeIgniter\Model;

class Datos_empresa extends Model
{
    protected $table            = 'datos_empresa';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'nombre', 'giro', 'calle', 'colonia', 'codigo_postal', 'delegacion',
        'rfc', 'telefono', 'email', 'web', 'clabe', 'banco'
    ];

    /**
     * Obtiene los datos de la empresa (asumiendo que solo hay una fila).
     */
    public function getInfo()
    {
        return $this->first();
    }
}
