<?php

namespace App\Models;

use CodeIgniter\Model;

class Clientes extends Model
{
    protected $table            = 'clientes';
    protected $primaryKey       = 'id_cliente';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nombre',
        'ape_pat',
        'ape_mat',
        'telefono',
        'telefono_2',
        'convenio',
        'referencia',
        'correo',
        'correo_2',
        'correo_admin',
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

    /**
     * Búsqueda de Clientes (OmniSearch)
     * Recupera clientes que coincidan con la cadena enviada (nombre o apellidos)
     */
    public function searchClientes($cadena)
    {
        return $this->select('id_cliente, nombre, ape_pat, ape_mat')
                    ->groupStart()
                        ->like('nombre', $cadena)
                        ->orLike('ape_pat', $cadena)
                        ->orLike('ape_mat', $cadena)
                    ->groupEnd()
                    ->findAll();
    }

    /**
     * Búsqueda de Clientes por Correo (OmniSearch)
     */
    public function searchCorreos($cadena)
    {
        return $this->select('id_cliente')
                    ->groupStart()
                        ->like('correo', $cadena)
                        ->orLike('correo_2', $cadena)
                        ->orLike('correo_admin', $cadena)
                    ->groupEnd()
                    ->findAll();
    }
}
