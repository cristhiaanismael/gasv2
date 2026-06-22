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
     * NOTA: Si no hay ningún periodo con status=1, retorna el último periodo
     * registrado como fallback para evitar romper el sistema. Si deseas
     * comportamiento estricto (null cuando no hay activo), usa getActivoEstricto().
     */
    public function getActiveFullRow()
    {
        $row = $this->where('status', 1)->orderBy('id_corte', 'DESC')->first();
        return $row ?: $this->where('fecha_inicio >=', '2026-01-01 00:00:00')->orderBy('id_corte', 'DESC')->first();
    }

    /**
     * Igual que getActiveFullRow() pero retorna null si no hay periodo activo.
     * Usar donde un periodo inactivo podría causar cálculos incorrectos.
     */
    public function getActivoEstricto()
    {
        return $this->where('status', 1)->orderBy('id_corte', 'DESC')->first();
    }

    /**
     * Registra un nuevo periodo de corte de forma segura, garantizando que sea el único activo.
     */
    public function registrarNuevoPeriodo($data)
    {
        $this->db->transStart();
        
        // Desactivar todos los demás periodos
        $this->where('status', 1)->set(['status' => 0])->update();
        
        // Insertar el nuevo periodo activo
        $this->insert($data);
        
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    /**
     * Verifica si existe un periodo que se traslape con las fechas dadas.
     */
    public function existsOverlap($inicio, $fin)
    {
        return $this->where('status', 1)
                    ->groupStart()
                        ->where('fecha_inicio <=', $fin)
                        ->where('fecha_fin >=', $inicio)
                    ->groupEnd()
                    ->first();
    }

    /**
     * Obtiene todos los periodos ordenados cronológicamente.
     */
    public function getAllOrdered()
    {
        return $this->where('fecha_inicio >=', '2026-01-01 00:00:00')
                    ->orderBy('fecha_inicio', 'DESC')
                    ->orderBy('id_corte', 'DESC')
                    ->findAll();
    }

    /**
     * Obtiene el periodo inmediatamente anterior a la fecha dada.
     * Encapsula la query del controlador Historial::getPreviousList (BUG6).
     */
    public function getPeriodoAnterior($fechaInicio)
    {
        if (!$fechaInicio) return null;

        return $this->where('fecha_inicio <', $fechaInicio)
                    ->where('fecha_inicio >=', '2026-01-01 00:00:00')
                    ->orderBy('fecha_inicio', 'DESC')
                    ->first();
    }
}

