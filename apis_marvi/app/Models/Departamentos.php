<?php
 
namespace App\Models;
 
use CodeIgniter\Model;
 
class Departamentos extends Model
{
    protected $table            = 'departamentos';
    protected $primaryKey       = 'id_departamento';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['num_departamento', 'id_edificio', 'id_cliente', 'deleted_at'];
    protected $deletedField     = 'deleted_at';
 
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
                        c.nombre, c.ape_pat, c.ape_mat, c.correo, c.correo_2,
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
                              AND m.fecha >= \'2026-01-01 00:00:00\'
                        ) as saldo_total,
                        (
                            SELECT m.monto
                            FROM movimientos m
                            WHERE m.id_departamento = departamentos.id_departamento
                              AND m.tipo = \'pago\'
                            ORDER BY m.fecha DESC
                            LIMIT 1
                        ) as ultimo_abono_monto,
                        (
                            SELECT m.fecha
                            FROM movimientos m
                            WHERE m.id_departamento = departamentos.id_departamento
                              AND m.tipo = \'pago\'
                            ORDER BY m.fecha DESC
                            LIMIT 1
                        ) as ultimo_abono_fecha
                    ')
                    ->join('clientes c', 'departamentos.id_cliente = c.id_cliente', 'left')
                    ->join('edificios e', 'departamentos.id_edificio = e.id_edificio', 'left')
                    ->join('lectura l', 'departamentos.id_departamento = l.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left')
                    ->where('departamentos.id_edificio', $id_edificio)
                    ->orderBy('departamentos.id_departamento', 'ASC')
                    ->findAll();
    }

    /**
     * Obtiene solo los IDs de departamento filtrando por un arreglo de edificios.
     */
    public function getIdsByEdificios(array $ids_edificios)
    {
        if (empty($ids_edificios)) return [];
        $rows = $this->select('id_departamento')->whereIn('id_edificio', $ids_edificios)->findAll();
        return array_column($rows, 'id_departamento');
    }

    /**
     * Obtiene solo los IDs de departamento filtrando por un arreglo de clientes.
     */
    public function getIdsByClientes(array $ids_clientes)
    {
        if (empty($ids_clientes)) return [];
        $rows = $this->select('id_departamento')->whereIn('id_cliente', $ids_clientes)->findAll();
        return array_column($rows, 'id_departamento');
    }

    /**
     * Obtiene solo los IDs de departamento filtrando por coincidencia en su número (OmniSearch).
     */
    public function searchIdsByNumDepartamento($cadena)
    {
        $rows = $this->select('id_departamento')
                     ->like('num_departamento', $cadena)
                     ->findAll();
        return array_column($rows, 'id_departamento');
    }

    /**
     * Obtiene el listado maestro unificado para OmniSearch usando un arreglo de IDs de departamento.
     */
    public function getDeptosConLecturaByIds(array $ids_departamentos, $periodo)
    {
        if (empty($ids_departamentos)) return [];

        return $this->select('
                        departamentos.id_departamento,
                        departamentos.num_departamento,
                        departamentos.id_edificio,
                        c.nombre, c.ape_pat, c.ape_mat, c.correo, c.correo_2,
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
                              AND m.fecha >= \'2026-01-01 00:00:00\'
                        ) as saldo_total,
                        (
                            SELECT m.monto
                            FROM movimientos m
                            WHERE m.id_departamento = departamentos.id_departamento
                              AND m.tipo = \'pago\'
                            ORDER BY m.fecha DESC
                            LIMIT 1
                        ) as ultimo_abono_monto,
                        (
                            SELECT m.fecha
                            FROM movimientos m
                            WHERE m.id_departamento = departamentos.id_departamento
                              AND m.tipo = \'pago\'
                            ORDER BY m.fecha DESC
                            LIMIT 1
                        ) as ultimo_abono_fecha
                    ')
                    ->join('clientes c', 'departamentos.id_cliente = c.id_cliente', 'left')
                    ->join('edificios e', 'departamentos.id_edificio = e.id_edificio', 'left')
                    ->join('lectura l', 'departamentos.id_departamento = l.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left')
                    ->whereIn('departamentos.id_departamento', $ids_departamentos)
                    ->orderBy('e.orden', 'ASC')
                    ->orderBy('e.num_edificio', 'ASC')
                    ->orderBy('departamentos.num_departamento', 'ASC')
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
        return $this->select('departamentos.*, 
                              c.nombre as cliente_nombre, c.ape_pat as cliente_ape_pat, c.ape_mat as cliente_ape_mat, 
                              c.correo as cliente_correo, c.correo_2 as cliente_correo_2, c.correo_admin as cliente_correo_admin,
                              c.telefono as cliente_telefono, c.telefono_2 as cliente_telefono_2,
                              c.convenio as cliente_convenio, c.referencia as cliente_referencia,
                              e.num_edificio, e.calle, e.num_ext, e.colonia, e.municipio, e.codigo_p')
                    ->join('clientes c', 'departamentos.id_cliente = c.id_cliente', 'left')
                    ->join('edificios e', 'departamentos.id_edificio = e.id_edificio', 'left')
                    ->where('departamentos.id_edificio', $id_edificio)
                    ->orderBy('departamentos.num_departamento', 'ASC')
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

    /**
     * Obtiene el consumo M3 anterior, saldo anterior de recibo y saldo inicial para todos los departamentos de un edificio.
     */
    public function getPreviousDataByBuilding($id_edificio, $prevPeriodo, $fechaInicio)
    {
        $escapedPrevPeriodo = $this->db->escape($prevPeriodo ?: '');
        $escapedFechaInicio = $this->db->escape($fechaInicio);

        return $this->db->table($this->table . ' d')
                     ->select("
                         d.id_departamento, 
                         COALESCE((
                             SELECT l.consumos_litros
                             FROM lectura l
                             WHERE l.id_departamento = d.id_departamento 
                               AND l.periodo = {$escapedPrevPeriodo}
                               AND l.fecha_register >= '2026-01-01 00:00:00'
                             LIMIT 1
                         ), 0) as consumos_litros_ant,
                         COALESCE((
                             SELECT l.total_a_pagar
                             FROM lectura l
                             WHERE l.id_departamento = d.id_departamento 
                               AND l.periodo = {$escapedPrevPeriodo}
                               AND l.fecha_register >= '2026-01-01 00:00:00'
                             LIMIT 1
                         ), 0) as saldo_anterior_recibo,
                         COALESCE((
                             SELECT SUM(CASE WHEN m.tipo = 'cargo' THEN m.monto ELSE -m.monto END)
                             FROM movimientos m
                             WHERE m.id_departamento = d.id_departamento 
                               AND m.fecha < {$escapedFechaInicio}
                               AND m.fecha >= '2026-01-01 00:00:00'
                         ), 0) as saldo_inicial
                     ")
                     ->where('d.id_edificio', $id_edificio)
                     ->where('d.deleted_at IS NULL', null, false) // Respetar soft deletes
                     ->get()
                     ->getResultArray();
    }

    /**
     * Obtiene el listado de departamentos de un edificio indicando si tienen o no lectura en un periodo.
     */
    public function getLecturaStatusByBuilding($id_edificio, $periodo)
    {
        return $this->select('departamentos.id_departamento, departamentos.num_departamento, IF(l.id_lectura IS NOT NULL, 1, 0) as tiene_lectura')
                    ->join('lectura l', 'departamentos.id_departamento = l.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left')
                    ->where('departamentos.id_edificio', $id_edificio)
                    ->where('departamentos.deleted_at IS NULL')
                    ->orderBy('departamentos.num_departamento', 'ASC')
                    ->findAll();
    }
}
