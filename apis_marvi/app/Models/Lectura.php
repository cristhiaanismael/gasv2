<?php

namespace App\Models;

use CodeIgniter\Model;

class Lectura extends Model
{
    protected $table            = 'lectura';
    protected $primaryKey       = 'id_lectura';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'foto',
        'periodo',
        'id_departamento',
        'fecha_register',
        'lectura_ini',
        'consumos_litros',
        'consumo_m3',
        'fecha_limite',
        'ticket_pago',
        'monto',
        'cuota_admin',
        'cargos_add',
        'lectura_fin',
        'ruta_pdf',
        'fecha_pago',
        'total_a_pagar',
        'nota'
    ];

    protected $useTimestamps = false;

    /**
     * Obtiene la lectura de un periodo específico para un departamento.
     */
    public function getLecturaByPeriodo($id_departamento, $periodo)
    {
        return $this->where([
            'id_departamento' => $id_departamento,
            'periodo' => $periodo
        ])->first();
    }

    /**
     * Obtiene las últimas N lecturas para el historial del recibo.
     */
    /**
     * Obtiene el listado maestro de lecturas recientes con su saldo calculado.
     */
    public function getHistorialReciente($id_departamento, $limit = 12)
    {
        return $this->select('
                        lectura.*,
                        (
                            lectura.total_a_pagar - 
                            COALESCE((
                                SELECT SUM(monto) 
                                FROM movimientos 
                                WHERE referencia_id = lectura.id_lectura 
                                  AND referencia_tipo = \'lectura\' 
                                  AND tipo = \'pago\'
                            ), 0)
                        ) as saldo_pendiente
                    ')
                    ->where('id_departamento', $id_departamento)
                    ->orderBy('id_lectura', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Obtiene la última lectura final física del departamento.
     */
    public function getUltimaFin($id_departamento)
    {
        $row = $this->where('id_departamento', $id_departamento)
                    ->orderBy('id_lectura', 'DESC')
                    ->first();
        return $row['lectura_fin'] ?? '0.00';
    }

    /**
     * Añade un nuevo comentario al hilo de notas (JSON).
     */
    public function addNota($id_lectura, $texto)
    {
        $lectura = $this->find($id_lectura);
        if (!$lectura) return false;

        $notas = json_decode($lectura['nota'] ?? '[]', true);
        if (!is_array($notas)) $notas = [];

        $notas[] = [
            'text' => $texto,
            'date' => date('Y-m-d H:i:s'),
            'user' => 'Admin' // Valor por defecto solicitado
        ];

        return $this->update($id_lectura, ['nota' => json_encode($notas)]);
    }

    /**
     * Obtiene TODOS los comentarios de los últimos 12 meses de un departamento
     * consolidándolos en una línea de tiempo única e inyectando el periodo.
     */
    public function getAllNotas($id_departamento, $limit = 12)
    {
        $readings = $this->where('id_departamento', $id_departamento)
                         ->orderBy('id_lectura', 'DESC')
                         ->limit($limit)
                         ->findAll();

        $allNotas = [];
        foreach ($readings as $r) {
            $periodo = $r['periodo'] ?? 'Desconocido';
            $id_lec  = $r['id_lectura'];
            $notas   = json_decode($r['nota'] ?? '[]', true);
            
            if (!is_array($notas)) {
                // Manejo de notas legacy (string plano)
                if (!empty($r['nota'])) {
                    $notas = [['text' => $r['nota'], 'date' => $r['fecha'] ?? '', 'user' => 'Admin']];
                } else {
                    continue;
                }
            }

            foreach ($notas as $idx => $n) {
                $n['id_lectura'] = $id_lec; // Referencia para borrado
                $n['index']      = $idx;    // Posición para borrado
                $n['periodo']    = $periodo; // Contexto solicitado
                $allNotas[] = $n;
            }
        }

        // Ordenar por fecha (más reciente al final para estilo chat)
        usort($allNotas, function($a, $b) {
            return strtotime($a['date'] ?? 0) - strtotime($b['date'] ?? 0);
        });

        return $allNotas;
    }

    /**
     * Elimina un comentario específico del hilo por su índice.
     */
    public function deleteNota($id_lectura, $index)
    {
        $lectura = $this->find($id_lectura);
        if (!$lectura) return false;

        $notas = json_decode($lectura['nota'] ?? '[]', true);
        if (!is_array($notas) || !is_numeric($index) || !isset($notas[$index])) return false;

        array_splice($notas, $index, 1);

        return $this->update($id_lectura, ['nota' => json_encode($notas)]);
    }

    /**
     * Obtiene el listado de PDFs de un edificio para un periodo.
     */
    public function getPdfsByEdificio($id_edificio, $periodo)
    {
        return $this->db->table('departamentos d')
            ->select('d.id_departamento, d.num_departamento, l.ruta_pdf, l.id_lectura')
            ->join('lectura l', 'l.id_departamento = d.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left')
            ->where('d.id_edificio', $id_edificio)
            ->get()
            ->getResultArray();
    }

    /**
     * Búsqueda Omnidireccional (Global)
     * Busca por nombre, correo, depto, total o lectura en todo el universo de datos.
     */
    public function searchOmni($query, $periodo, $fechas = [])
    {
        // 1. Obtener los IDs de departamentos que coinciden en el periodo actual
        $builder = $this->db->table('departamentos d');
        $builder->select('d.id_departamento');
        $builder->join('clientes c', 'd.id_cliente = c.id_cliente', 'left');
        
        // Join con lectura solo de este periodo
        $builder->join('lectura l', 'd.id_departamento = l.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left');
        
        // Join con movimientos filtrados por fecha del periodo
        $builder->join('movimientos m', 'd.id_departamento = m.id_departamento AND m.fecha >= "' . $fechas['inicio'] . '" AND m.fecha <= "' . $fechas['fin'] . '"', 'left');
        
        $builder->groupStart()
                ->like('c.nombre', $query)
                ->orLike('c.ape_pat', $query)
                ->orLike('c.ape_mat', $query)
                ->orLike('c.correo', $query)
                ->orLike('d.num_departamento', $query)
                ->orLike('l.total_a_pagar', $query)
                ->orLike('l.lectura_fin', $query)
                ->orLike('l.nota', $query) 
                ->orLike('m.monto', $query)
                ->orLike('m.descripcion', $query)
                ->groupEnd();
        
        $deptoIdsRes = $builder->distinct()->select('d.id_departamento')->limit(15)->get()->getResultArray();
        if (empty($deptoIdsRes)) return [];
        
        $deptoIds = array_column($deptoIdsRes, 'id_departamento');

        // 2. Para esos departamentos, traer la información del periodo actual
        $finalBuilder = $this->db->table('departamentos d');
        $finalBuilder->select('
            d.id_departamento, d.num_departamento, d.id_edificio,
            c.nombre, c.ape_pat, c.ape_mat, c.correo,
            e.num_edificio as nombre_edificio,
            l.id_lectura, l.lectura_fin, l.total_a_pagar, l.periodo, l.nota, l.foto,
            (SELECT SUM(monto) FROM movimientos WHERE id_departamento = d.id_departamento AND tipo = "abono" AND fecha >= "' . $fechas['inicio'] . '" AND fecha <= "' . $fechas['fin'] . '") as total_abonos
        ');
        $finalBuilder->join('clientes c', 'd.id_cliente = c.id_cliente', 'left');
        $finalBuilder->join('edificios e', 'd.id_edificio = e.id_edificio', 'left');
        
        // Join con lectura ESTRICTAMENTE de este periodo
        $finalBuilder->join('lectura l', 'l.id_departamento = d.id_departamento AND l.periodo = ' . $this->db->escape($periodo), 'left');
        
        $finalBuilder->whereIn('d.id_departamento', $deptoIds);
        
        return $finalBuilder->get()->getResultArray();
    }
    /**
     * Registra una lectura y su movimiento financiero asociado (Cargo).
     * Sigue estrictamente el patrón MVC moviendo la lógica del controlador al modelo.
     */
    public function registrarLectura($data)
    {
        $id_depto       = $data['id_departamento'];
        $lectura_ini    = floatval($data['lectura_ini']);
        $lectura_fin    = floatval($data['lectura_fin']);
        $fecha_registro = $data['fecha_registro'];
        $foto           = $data['foto'] ?? '';

        $deptoModel    = new Departamentos();
        $edificioModel = new Edificios();
        $corteModel    = new Cortes();
        $movModel      = new Movimientos();

        // 1. Obtener contexto
        $depto = $deptoModel->getWithBuilding($id_depto);
        if (!$depto) throw new \Exception('Departamento no encontrado');

        $config = $edificioModel->getConfiguracion($depto['id_edificio']);
        $periodo = $corteModel->getActivePeriod();

        if ($config['precioLitro'] <= 0) {
            throw new \Exception("No hay precio de gas registrado para el edificio {$depto['num_edificio']}.");
        }

        // 2. Cálculos
        $consumo_m3     = round($lectura_fin - $lectura_ini, 2);
        $consumo_litros = round($consumo_m3 * $config['factor'], 2);
        $monto          = round($consumo_litros * $config['precioLitro'], 2);
        $total_cargo    = round($monto + $config['cuotaAdmin'], 2);

        // 3. Persistencia (Transacción)
        $this->db->transStart();

        // Buscar si ya existe lectura para este departamento en este periodo
        $existing = $this->where('id_departamento', $id_depto)
                         ->where('periodo', $periodo)
                         ->first();

        if ($existing) {
            $id_lectura = $existing['id_lectura'];
            $this->update($id_lectura, [
                'lectura_fin'     => $lectura_fin,
                'consumo_m3'      => $consumo_m3,
                'consumos_litros' => $consumo_litros,
                'consumos_mes'    => $consumo_m3,
                'monto'           => $monto,
                'cuota_admin'     => $config['cuotaAdmin'],
                'total_a_pagar'   => $total_cargo,
                'foto'            => $foto ?: $existing['foto'] // Mantener anterior si no hay nueva
            ]);

            // Sincronizar el cargo en movimientos
            $movModel->syncReadingCharge($id_lectura, $total_cargo);
        } else {
            $id_lectura = $this->insert([
                'id_departamento' => $id_depto,
                'periodo'         => $periodo,
                'lectura_ini'     => $lectura_ini,
                'lectura_fin'     => $lectura_fin,
                'consumo_m3'      => $consumo_m3,
                'consumos_litros' => $consumo_litros,
                'consumos_mes'    => $consumo_m3,
                'monto'           => $monto,
                'cuota_admin'     => $config['cuotaAdmin'],
                'cargos_add'      => 0,
                'total_a_pagar'   => $total_cargo,
                'fecha_register'  => $fecha_registro,
                'foto'            => $foto
            ]);

            // Sincronizar movimientos desglosados (Consumo, Cuota, etc.)
            $movModel->syncReadingMovements(
                $id_lectura, 
                $id_depto, 
                $monto, 
                $config['cuotaAdmin'], 
                0, // cargos_add inicial es 0
                0, // ajuste inicial es 0
                $periodo
            );
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \Exception('Error al guardar en base de datos');
        }

        return [
            'id_lectura'      => $id_lectura,
            'consumo_m3'      => $consumo_m3,
            'consumo_litros'  => $consumo_litros,
            'total_cargo'     => $total_cargo,
            'periodo'         => $periodo,
            'num_edificio'    => $depto['num_edificio'],
            'num_departamento' => $depto['num_departamento']
        ];
    }
    /**
     * Obtiene la última lectura registrada para un departamento.
     */
    public function getUltimaByDepto($id_departamento)
    {
        return $this->where('id_departamento', $id_departamento)
                    ->orderBy('id_lectura', 'DESC')
                    ->first();
    }
}
