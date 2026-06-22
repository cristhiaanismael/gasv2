<?php

namespace App\Models;

use CodeIgniter\Model;

class Precio_litros extends Model
{
    protected $table            = 'precio_litros';
    protected $primaryKey       = 'id_precio';
    protected $returnType       = 'array';
    protected $allowedFields    = ['costo', 'id_edificio', 'fecha_vencimiento'];

    /**
     * Obtiene el precio vigente de gas para un edificio específico.
     */
    public function getPrecioVigente($id_edificio)
    {
        $precio = $this->where('id_edificio', $id_edificio)
                       ->orderBy('id_precio', 'DESC')
                       ->first();

        return floatval($precio['costo'] ?? 0);
    }

    /**
     * Registra un nuevo precio de gas para un edificio.
     */
    public function registrar($id_edificio, $costo)
    {
        return $this->insert([
            'producto'    => 'gas',
            'costo'       => $costo,
            'id_edificio' => $id_edificio
        ]);
    }
}
