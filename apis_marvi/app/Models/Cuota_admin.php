<?php

namespace App\Models;

use CodeIgniter\Model;

class Cuota_admin extends Model
{
    protected $table            = 'cuota_admin';
    protected $primaryKey       = 'id_cuota';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = ['cuota', 'fecha_register', 'id_edificio'];

    protected $useTimestamps = false;

    /**
     * Obtiene la cuota de administración vigente para un edificio.
     */
    public function getVigente($id_edificio)
    {
        $row = $this->where('id_edificio', $id_edificio)
                    ->orderBy('id_cuota', 'DESC')
                    ->first();

        return floatval($row['cuota'] ?? 0);
    }

    /**
     * Registra una nueva cuota de administración para un edificio.
     */
    public function registrar($id_edificio, $valor)
    {
        return $this->insert([
            'cuota'          => strval($valor),
            'fecha_register' => date('Y-m-d H:i:s'),
            'id_edificio'    => $id_edificio
        ]);
    }
}
