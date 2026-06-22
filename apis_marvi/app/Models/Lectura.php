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
     * Búsqueda de departamentos por Lectura Anterior (OmniSearch)
     */
    public function searchByLecturaIni($cadena, $periodo)
    {
        return $this->select('id_departamento')
                    ->where('periodo', $periodo)
                    ->like('lectura_ini', $cadena)
                    ->findAll();
    }

    /**
     * Búsqueda de departamentos por Lectura Actual (OmniSearch)
     */
    public function searchByLecturaFin($cadena, $periodo)
    {
        return $this->select('id_departamento')
                    ->where('periodo', $periodo)
                    ->like('lectura_fin', $cadena)
                    ->findAll();
    }

    /**
     * Búsqueda de departamentos por Total del Periodo (OmniSearch)
     */
    public function searchByTotalPeriodo($cadena, $periodo)
    {
        return $this->select('id_departamento')
                    ->where('periodo', $periodo)
                    ->like('total_a_pagar', $cadena)
                    ->findAll();
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

        $forzar_calculo = isset($data['forzar_calculo']) ? (int)$data['forzar_calculo'] : 0;

        // 2. Cálculos
        $consumo_m3     = $lectura_fin - $lectura_ini;
        $consumo_litros = round($consumo_m3 * $config['factor'], 3);
        $monto          = round($consumo_litros * $config['precioLitro'], 3);
        
        // Si el consumo es cero y no se forzó el cálculo, no se cobra la cuota de administración
        $active_cuota_admin = ($consumo_m3 == 0 && !$forzar_calculo) ? 0.00 : round((float)$config['cuotaAdmin'], 3);
        $total_cargo    = round($monto + $active_cuota_admin, 2);

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
                'monto'           => $monto,
                'cuota_admin'     => $active_cuota_admin,
                'total_a_pagar'   => $total_cargo,
                'foto'            => $foto ?: $existing['foto'] // Mantener anterior si no hay nueva
            ]);

            // Re-sincronizar movimientos completos (borra anteriores y reinserta con valores actualizados)
            $movModel->syncReadingMovements(
                $id_lectura,
                $id_depto,
                $monto,
                $active_cuota_admin,
                (float)($existing['cargos_add'] ?? 0), // Preservar cargos adicionales existentes
                0,
                $periodo
            );
        } else {
            $id_lectura = $this->insert([
                'id_departamento' => $id_depto,
                'periodo'         => $periodo,
                'lectura_ini'     => $lectura_ini,
                'lectura_fin'     => $lectura_fin,
                'consumo_m3'      => $consumo_m3,
                'consumos_litros' => $consumo_litros,
                'monto'           => $monto,
                'cuota_admin'     => $active_cuota_admin,
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
                $active_cuota_admin, 
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
     * Actualiza una lectura existente desde el Historial con recálculo completo.
     * Encapsula la transacción y la lógica de cuota cero (BUG1 + BUG5).
     * El controlador NO gestiona la transacción ni el cálculo: solo pasa datos.
     *
     * @param int         $id_lectura  PK de la lectura a actualizar
     * @param array       $lectura     Fila actual de la lectura (obtenida por el controlador)
     * @param array       $payload     Nuevos valores: lectura_fin, cargos_add, ajuste, nota, foto
     * @param array       $config      Configuración del edificio: precioLitro, factor, cuotaAdmin
     * @param Movimientos $movModel    Modelo de movimientos (inyectado desde el controlador)
     * @return array ['total_a_pagar', 'lectura']
     */
    public function actualizarLectura($id_lectura, $lectura, $payload, $config, $movModel)
    {
        $lec_fin    = ($payload['lectura_fin'] !== null) ? (float)$payload['lectura_fin'] : (float)$lectura['lectura_fin'];
        $cargos_add = (float)($payload['cargos_add'] ?? 0);
        $ajuste     = (float)($payload['ajuste'] ?? 0);
        $foto       = $payload['foto'] ?? $lectura['foto'];
        $nota       = $payload['nota'] ?? null;

        // Recálculo completo (lógica idéntica a registrarLectura para garantizar consistencia)
        $lec_ini  = (float)$lectura['lectura_ini'];
        $m3       = max(0, $lec_fin - $lec_ini);
        $lt       = round($m3 * (float)$config['factor'], 3);
        $montoGas = round($lt * (float)$config['precioLitro'], 3);

        // BUG1 FIX: Aplicar la misma regla que registrarLectura — si consumo=0, cuota=0
        $active_cuota_admin = ($m3 == 0) ? 0.00 : round((float)$config['cuotaAdmin'], 3);

        $total_calculado = round($montoGas + $active_cuota_admin + $cargos_add + $ajuste, 2);

        if (array_key_exists('total_a_pagar', $payload) && $payload['total_a_pagar'] !== null) {
            $total_a_pagar = (float)$payload['total_a_pagar'];
            
            // Si el frontend forzó a 0 (por regla de lecturas iguales) pero el cálculo era mayor
            if ($total_a_pagar == 0 && $total_calculado > 0 && $m3 == 0) {
                $active_cuota_admin = 0;
                $cargos_add = 0;
                $ajuste = 0;
            }
        } else {
            // Recálculo natural forzado (ej. botón recalcular)
            $total_a_pagar = $total_calculado;
        }

        $data = [
            'lectura_fin'     => $lec_fin,
            'consumo_m3'      => $m3,
            'consumos_litros' => $lt,
            'monto'           => $montoGas,
            'cuota_admin'     => $active_cuota_admin,
            'cargos_add'      => $cargos_add,
            'total_a_pagar'   => $total_a_pagar,
            'foto'            => $foto
        ];

        // Notas siempre en JSON para ser consistente con addNota()
        if ($nota !== null && trim($nota) !== '') {
            $notasExistentes = json_decode($lectura['nota'] ?? '[]', true);
            if (!is_array($notasExistentes)) $notasExistentes = [];
            $notasExistentes[] = [
                'text' => trim($nota),
                'date' => date('Y-m-d H:i:s'),
                'user' => 'Admin'
            ];
            $data['nota'] = json_encode($notasExistentes);
        }

        // BUG5 FIX: La transacción se gestiona aquí en el Modelo, no en el Controlador
        $this->db->transStart();

        $this->update($id_lectura, $data);

        // El ajuste va a movimientos (NO existe columna ajuste en lectura)
        $movModel->syncReadingMovements(
            $id_lectura,
            $lectura['id_departamento'],
            $montoGas,
            $active_cuota_admin,
            $cargos_add,
            $ajuste,
            $lectura['periodo'] ?? 'Actual'
        );

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \Exception('Error al actualizar la lectura en base de datos');
        }

        return [
            'total_a_pagar' => $total_a_pagar,
            'lectura'       => $this->find($id_lectura)
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
