<?php
 
namespace App\Models;
 
use CodeIgniter\Model;
 
class Departamentos extends Model
{
    protected $table            = 'departamentos';
    protected $primaryKey       = 'id_departamento';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['num_departamento', 'id_edificio', 'id_cliente'];
 
    /**
     * Obtiene la información total del departamento, incluyendo cliente y edificio.
     */
    public function getInfoCompleta($id)
    {
        return $this->select('departamentos.*, c.nombre, c.ape_pat, c.ape_mat, c.correo, c.convenio, c.referencia,
                              e.num_edificio, e.calle, e.colonia, e.num_ext, e.municipio, e.codigo_p, e.id_cuenta')
                    ->join('clientes c', 'departamentos.id_cliente = c.id_cliente', 'left')
                    ->join('edificios e', 'departamentos.id_edificio = e.id_edificio', 'left')
                    ->where('departamentos.id_departamento', $id)
                    ->first();
    }
 
    /**
     * Obtiene el listado maestro de departamentos para el historial por edificio.
     */
    public function getDeptosConLectura($id_edificio, $periodo)
    {
        return $this->select('
                        departamentos.id_departamento,
                        departamentos.num_departamento,
                        departamentos.id_edificio,
                        c.nombre, c.ape_pat, c.ape_mat, c.correo,
                        l.id_lectura,
                        l.foto,
                        l.nota,
                        COALESCE(l.lectura_ini, 0) as lectura_ini,
                        COALESCE(l.lectura_fin, 0) as lectura_fin,
                        COALESCE(l.consumo_m3, 0) as consumo_m3,
                        COALESCE(l.consumos_litros, 0) as consumos_litros,
                        COALESCE(l.total_a_pagar, 0) as total_a_pagar,
                        l.periodo,
                        e.num_edificio as nombre_edificio,
                        (
                            SELECT SUM(CASE WHEN m.tipo = \'cargo\' THEN m.monto ELSE -m.monto END)
                            FROM movimientos m
                            WHERE m.id_departamento = departamentos.id_departamento
                        ) as saldo_total
                    ')
                    ->join('clientes c', 'departamentos.id_cliente = c.id_cliente', 'left')
                    ->join('edificios e', 'departamentos.id_edificio = e.id_edificio', 'left')
                    ->join('lectura l', 'departamentos.id_departamento = l.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left')
                    ->where('departamentos.id_edificio', $id_edificio)
                    ->orderBy('departamentos.id_departamento', 'ASC')
                    ->findAll();
    }

    /**
     * Registro masivo de departamentos para un edificio.
     */
    public function registerBatch($id_edificio, $departamentos)
    {
        $inserted = 0;
        foreach ($departamentos as $num_depto) {
            $data = [
                'id_edificio' => $id_edificio,
                'num_departamento' => trim($num_depto)
            ];
            if ($this->insert($data)) {
                $inserted++;
            }
        }
        return $inserted;
    }

    /**
     * Obtiene la lista simple de departamentos de un edificio.
     */
    public function listByBuilding($id_edificio)
    {
        return $this->where('id_edificio', $id_edificio)
                    ->orderBy('num_departamento', 'ASC')
                    ->findAll();
    }

    /**
     * Obtiene depto con su edificio asociado.
     */
    public function getWithBuilding($id)
    {
        return $this->select('departamentos.*, edificios.id_edificio, edificios.num_edificio')
                    ->join('edificios', 'edificios.id_edificio = departamentos.id_edificio')
                    ->where('id_departamento', $id)
                    ->first();
    }
}
