<?php

namespace App\Models;

use CodeIgniter\Model;

class Edificios extends Model
{
    protected $table            = 'edificios';
    protected $primaryKey       = 'id_edificio';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'num_edificio',
        'calle',
        'num_ext',
        'municipio',
        'colonia',
        'codigo_p',
        'id_cuenta',
        'orden'
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];
    /**
     * Obtiene la configuración técnica del edificio (Precio, Factor, Cuota)
     */
    public function getConfiguracion($id)
    {
        $rowPrecio = $this->db->table('precio_litros')->where('id_edificio', $id)->orderBy('id_precio', 'DESC')->limit(1)->get()->getRowArray();
        $rowFactor = $this->db->table('factor')->where('id_edificio', $id)->orderBy('id_factor', 'DESC')->limit(1)->get()->getRowArray();
        $rowCuota  = $this->db->table('cuota_admin')->where('id_edificio', $id)->orderBy('id_cuota', 'DESC')->limit(1)->get()->getRowArray();

        return [
            'id_edificio' => $id,
            'precioLitro' => floatval($rowPrecio['costo'] ?? 0),
            'factor'      => floatval($rowFactor['factor'] ?? 1),
            'cuotaAdmin'  => floatval($rowCuota['cuota'] ?? 0)
        ];
    }
    /**
     * Obtiene todos los edificios ordenados por orden y número.
     */
    public function getAllOrdered()
    {
        return $this->orderBy('orden', 'ASC')->orderBy('num_edificio', 'ASC')->findAll();
    }
}
