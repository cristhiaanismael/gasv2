<?php

namespace App\Models;

use CodeIgniter\Model;

class Edificios extends Model
{
    protected $table            = 'edificios';
    protected $primaryKey       = 'id_edificio';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'num_edificio',
        'calle',
        'num_ext',
        'municipio',
        'colonia',
        'codigo_p',
        'id_cuenta',
        'orden',
        'deleted_at'
    ];

    // Dates
    protected $useTimestamps = false;
    protected $deletedField  = 'deleted_at';

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
     * Guarda la configuración del edificio (precio, factor, cuota).
     * Solo inserta nuevos registros históricos si el valor cambió.
     * Gestiona su propia transacción (BUG3: lógica movida desde el controlador).
     *
     * @return array Lista de campos que fueron actualizados
     */
    public function saveConfiguracion($id, $precioLitro, $factor, $cuotaAdmin, $current)
    {
        $inserted = [];

        $this->db->transStart();

        if ($precioLitro !== $current['precioLitro'] || !$current['precioLitro']) {
            $this->db->table('precio_litros')->insert([
                'producto'    => 'gas',
                'costo'       => $precioLitro,
                'id_edificio' => $id
            ]);
            $inserted[] = 'Precio de gas';
        }

        if ($factor !== $current['factor'] || !$current['factor']) {
            $this->db->table('factor')->insert([
                'factor'         => strval($factor),
                'fecha_register' => date('Y-m-d H:i:s'),
                'id_edificio'    => $id
            ]);
            $inserted[] = 'Factor de conversión';
        }

        if ($cuotaAdmin !== $current['cuotaAdmin'] || !$current['cuotaAdmin']) {
            $this->db->table('cuota_admin')->insert([
                'cuota'          => strval($cuotaAdmin),
                'fecha_register' => date('Y-m-d H:i:s'),
                'id_edificio'    => $id
            ]);
            $inserted[] = 'Cuota de administración';
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \Exception('Error al guardar la configuración del edificio.');
        }

        return $inserted;
    }

    /**
     * Obtiene el historial completo de precio, factor y cuota de un edificio.
     * (BUG3: lógica movida desde el controlador Edificios::getConfigHistory)
     */
    public function getHistorialConfiguracion($id)
    {
        $precios = $this->db->table('precio_litros')
            ->where('id_edificio', $id)
            ->orderBy('id_precio', 'DESC')
            ->get()->getResultArray();

        $factores = $this->db->table('factor')
            ->where('id_edificio', $id)
            ->orderBy('id_factor', 'DESC')
            ->get()->getResultArray();

        $cuotas = $this->db->table('cuota_admin')
            ->where('id_edificio', $id)
            ->orderBy('id_cuota', 'DESC')
            ->get()->getResultArray();

        return compact('precios', 'factores', 'cuotas');
    }

    /**
     * Obtiene todos los edificios ordenados por orden y número.
     */
    public function getAllOrdered()
    {
        return $this->orderBy('orden', 'ASC')->orderBy('num_edificio', 'ASC')->findAll();
    }

    /**
     * Obtiene el progreso de lecturas por edificio para un periodo dado.
     * Retorna el número de departamentos totales y cuántos ya tienen lectura.
     */
    public function getProgresoLecturas($periodo)
    {
        $escapedPeriodo = $this->db->escape($periodo);
        $sql = "
            SELECT 
                e.id_edificio,
                e.num_edificio as nombre_edificio,
                COUNT(d.id_departamento) as total_deptos,
                SUM(CASE WHEN l.id_lectura IS NOT NULL THEN 1 ELSE 0 END) as deptos_con_lectura
            FROM edificios e
            LEFT JOIN departamentos d ON e.id_edificio = d.id_edificio AND d.deleted_at IS NULL
            LEFT JOIN lectura l ON d.id_departamento = l.id_departamento AND l.periodo = {$escapedPeriodo}
            WHERE e.deleted_at IS NULL
            GROUP BY e.id_edificio
            ORDER BY e.orden ASC, e.num_edificio ASC
        ";
        return $this->db->query($sql)->getResultArray();
    }
    /**
     * Búsqueda de Edificios (OmniSearch)
     * Recupera edificios que coincidan con la cadena enviada (num_edificio o calle)
     */
    public function searchEdificios($cadena)
    {
        return $this->select('id_edificio, num_edificio as nombre_edificio, calle')
                    ->groupStart()
                        ->like('num_edificio', $cadena)
                        ->orLike('calle', $cadena)
                    ->groupEnd()
                    ->findAll();
    }
}

