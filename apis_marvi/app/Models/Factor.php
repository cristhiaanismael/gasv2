<?php

namespace App\Models;

use CodeIgniter\Model;

class Factor extends Model
{
    protected $table            = 'factor';
    protected $primaryKey       = 'id_factor';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['factor', 'fecha_register', 'id_edificio'];

    protected $useTimestamps = false;

    /**
     * Obtiene el factor de conversión vigente para un edificio.
     */
    public function getVigente($id_edificio)
    {
        $row = $this->where('id_edificio', $id_edificio)
                    ->orderBy('id_factor', 'DESC')
                    ->first();

        return floatval($row['factor'] ?? 1);
    }

    /**
     * Registra un nuevo factor de conversión para un edificio.
     */
    public function registrar($id_edificio, $valor)
    {
        return $this->insert([
            'factor'         => strval($valor),
            'fecha_register' => date('Y-m-d H:i:s'),
            'id_edificio'    => $id_edificio
        ]);
    }
}
