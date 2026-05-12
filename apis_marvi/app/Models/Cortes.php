<?php

namespace App\Models;

use CodeIgniter\Model;

class Cortes extends Model
{
    protected $table            = 'cortes';
    protected $primaryKey       = 'id_corte';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['periodo', 'status', 'fecha_inicio', 'fecha_fin'];

    /**
     * Obtiene el nombre del periodo actualmente activo.
     */
    public function getActivePeriod()
    {
        $row = $this->getActiveFullRow();
        return $row['periodo'] ?? '---';
    }

    /**
     * Obtiene la fila completa del periodo activo.
     */
    public function getActiveFullRow()
    {
        $row = $this->where('status', 1)->orderBy('id_corte', 'DESC')->first();
        return $row ?: $this->orderBy('id_corte', 'DESC')->first();
    }

    /**
     * Verifica si existe un periodo que se traslape con las fechas dadas.
     */
    public function existsOverlap($inicio, $fin)
    {
        return $this->where('status', 1)
                    ->groupStart()
                        ->where("fecha_inicio <= '$fin'")
                        ->where("fecha_fin >= '$inicio'")
                    ->groupEnd()
                    ->first();
    }
    /**
     * Obtiene todos los periodos ordenados cronológicamente.
     */
    public function getAllOrdered()
    {
        return $this->orderBy('fecha_inicio', 'DESC')
                    ->orderBy('id_corte', 'DESC')
                    ->findAll();
    }
}
