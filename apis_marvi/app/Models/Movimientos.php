<?php

namespace App\Models;

use CodeIgniter\Model;

class Movimientos extends Model
{
    protected $table            = 'movimientos';
    protected $primaryKey       = 'id_movimiento';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id_departamento',
        'tipo',
        'monto',
        'referencia_id',
        'referencia_tipo',
        'descripcion',
        'fecha'
    ];

    protected $useTimestamps = false;

    const TIPOS_VALIDOS = ['cargo', 'pago', 'ajuste'];

    protected $beforeInsert = ['validarTipo'];
    protected $beforeUpdate = ['validarTipo'];

    /**
     * Valida que el tipo de movimiento sea uno de los permitidos por el ENUM de la base de datos.
     * Si no es válido, lanza una excepción fatal para alertar al desarrollador.
     */
    protected function validarTipo(array $data)
    {
        if (isset($data['data']['tipo'])) {
            $tipo = $data['data']['tipo'];
            if (!in_array($tipo, self::TIPOS_VALIDOS)) {
                $errorMsg = "ERROR FATAL: El tipo de movimiento '{$tipo}' no está definido. " . 
                           "Valores permitidos: " . implode(', ', self::TIPOS_VALIDOS);
                throw new \Exception($errorMsg);
            }
        }
        return $data;
    }

    /**
     * Obtiene el último abono (pago) de un departamento.
     */
    public function getUltimoAbono($id_departamento)
    {
        return $this->where('id_departamento', $id_departamento)
                    ->where('tipo', 'pago')
                    ->where('fecha >=', '2026-01-01 00:00:00')
                    ->orderBy('fecha', 'DESC')
                    ->orderBy('id_movimiento', 'DESC')
                    ->first();
    }

    /**
     * Calcula el saldo total de un departamento (Cargos - Abonos)
     */
    public function getSaldoTotal($id_departamento)
    {
        // cargo (+) | pago (-) | ajuste (-)
        $result = $this->select('SUM(CASE 
                        WHEN tipo = \'cargo\' THEN monto 
                        WHEN tipo = \'pago\' THEN -monto 
                        WHEN tipo = \'ajuste\' THEN -monto 
                        ELSE 0 END) as saldo', false)
                       ->where('id_departamento', $id_departamento)
                       ->where('fecha >=', '2026-01-01 00:00:00')
                       ->get()
                       ->getRowArray();

        return floatval($result['saldo'] ?? 0);
    }

    /**
     * Búsqueda de departamentos por Saldo Actual (OmniSearch)
     */
    public function searchBySaldoActual($cadena)
    {
        $sql = "SELECT id_departamento, 
                SUM(CASE 
                    WHEN tipo = 'cargo' THEN monto 
                    WHEN tipo = 'pago' THEN -monto 
                    WHEN tipo = 'ajuste' THEN -monto 
                    ELSE 0 
                END) as saldo
                FROM movimientos
                WHERE fecha >= '2026-01-01 00:00:00'
                GROUP BY id_departamento
                HAVING CAST(CAST(saldo AS DECIMAL(12,2)) AS CHAR) LIKE ? OR CAST(ROUND(saldo, 0) AS CHAR) LIKE ?";
        return $this->db->query($sql, ["%$cadena%", "%$cadena%"])->getResultArray();
    }

    /**
     * Búsqueda de departamentos por Saldo Anterior (OmniSearch)
     */
    public function searchBySaldoAnt($cadena, $fecha)
    {
        if (!$fecha) return [];
        $sql = "SELECT id_departamento, 
                SUM(CASE 
                    WHEN tipo = 'cargo' THEN monto 
                    WHEN tipo = 'pago' THEN -monto 
                    WHEN tipo = 'ajuste' THEN -monto 
                    ELSE 0 
                END) as saldo
                FROM movimientos
                WHERE fecha >= '2026-01-01 00:00:00' AND fecha < ?
                GROUP BY id_departamento
                HAVING CAST(saldo AS CHAR) LIKE ?";
        return $this->db->query($sql, [$fecha, "%$cadena%"])->getResultArray();
    }

    /**
     * Búsqueda de departamentos por Total de Abonos (OmniSearch)
     */
    public function searchByAbonos($cadena)
    {
        $sql = "SELECT id_departamento, 
                SUM(CASE 
                    WHEN tipo IN ('pago', 'ajuste') THEN monto 
                    ELSE 0 
                END) as total_abonos
                FROM movimientos
                WHERE fecha >= '2026-01-01 00:00:00'
                GROUP BY id_departamento
                HAVING CAST(total_abonos AS CHAR) LIKE ?";
        return $this->db->query($sql, ["%$cadena%"])->getResultArray();
    }

    /**
     * Búsqueda de departamentos por Total de Adeudos/Cargos (OmniSearch)
     */
    public function searchByAdeudos($cadena)
    {
        $sql = "SELECT id_departamento, 
                SUM(CASE 
                    WHEN tipo = 'cargo' THEN monto 
                    ELSE 0 
                END) as total_adeudos
                FROM movimientos
                WHERE fecha >= '2026-01-01 00:00:00'
                GROUP BY id_departamento
                HAVING CAST(total_adeudos AS CHAR) LIKE ?";
        return $this->db->query($sql, ["%$cadena%"])->getResultArray();
    }

    /**
     * Búsqueda de departamentos por Saldo a Favor (OmniSearch)
     */
    public function searchBySaldoFavor($cadena)
    {
        $sql = "SELECT id_departamento, 
                SUM(CASE 
                    WHEN tipo = 'cargo' THEN monto 
                    WHEN tipo = 'pago' THEN -monto 
                    WHEN tipo = 'ajuste' THEN -monto 
                    ELSE 0 
                END) as saldo
                FROM movimientos
                WHERE fecha >= '2026-01-01 00:00:00'
                GROUP BY id_departamento
                HAVING saldo < 0 AND CAST(ABS(saldo) AS CHAR) LIKE ?";
        return $this->db->query($sql, ["%$cadena%"])->getResultArray();
    }

    /**
     * Obtiene la suma de abonos en un rango de fechas.
     */
    public function getSumAbonosByRange($id_departamento, $fecha_ini, $fecha_fin)
    {
        if (!$fecha_ini || !$fecha_fin) return 0;
        
        $res = $this->selectSum('monto')
                    ->where('id_departamento', $id_departamento)
                    ->where('tipo', 'pago') // 'abono' no existe en el enum, es 'pago'
                    ->where('fecha >=', $fecha_ini)
                    ->where('fecha <=', $fecha_fin)
                    ->first();
                    
        return floatval($res['monto'] ?? 0);
    }

    /**
     * Obtiene los movimientos de un departamento con el nombre del periodo asociado (si existe).
     */
    public function getMovimientosConPeriodo($id_departamento)
    {
        return $this->select('movimientos.*, 
            CASE 
                WHEN lectura.periodo IS NOT NULL THEN lectura.periodo
                WHEN movimientos.tipo = \'pago\' THEN \'Pago Directo / Manual\'
                ELSE \'Ajuste Manual\'
            END as periodo_nombre', false)
                    ->join('lectura', 'movimientos.referencia_id = lectura.id_lectura AND movimientos.referencia_tipo = \'lectura\'', 'left')
                    ->where('movimientos.id_departamento', $id_departamento)
                    ->where('movimientos.fecha >=', '2026-01-01 00:00:00')
                    ->orderBy('movimientos.fecha', 'DESC')
                    ->orderBy('movimientos.id_movimiento', 'DESC')
                    ->findAll();
    }

    /**
     * Calcula el saldo inicial de un departamento antes de una fecha dada.
     */
    public function getSaldoInicialAntesDeFecha($id_departamento, $fecha)
    {
        if (!$fecha) return 0.0;
        
        $res = $this->select('SUM(CASE 
                        WHEN tipo = \'cargo\' THEN monto 
                        WHEN tipo = \'pago\' THEN -monto 
                        WHEN tipo = \'ajuste\' THEN -monto 
                        ELSE 0 END) as saldo', false)
                    ->where('id_departamento', $id_departamento)
                    ->where('fecha <', $fecha)
                    ->where('fecha >=', '2026-01-01 00:00:00')
                    ->get()
                    ->getRowArray();

        return floatval($res['saldo'] ?? 0);
    }
    /**
     * Sincroniza los movimientos financieros asociados a una lectura.
     * Crea registros separados para Consumo, Cargos Adicionales y Ajustes para máxima claridad en el historial.
     */
    public function syncReadingMovements($id_lectura, $id_depto, $montoGas, $cuotaAdmin, $add, $ajuste, $periodo)
    {
        // 1. Limpiar movimientos previos vinculados a esta lectura para evitar duplicidad
        $this->where([
            'referencia_id'   => $id_lectura,
            'referencia_tipo' => 'lectura'
        ])->delete();

        $movements = [];
        $fecha = date('Y-m-d H:i:s');

        // A. Movimiento de Consumo (Gas + Cuota)
        $montoConsumo = (float)$montoGas + (float)$cuotaAdmin;
        if ($montoConsumo != 0) {
            $movements[] = [
                'id_departamento' => $id_depto,
                'tipo'            => 'cargo',
                'monto'           => $montoConsumo,
                'referencia_id'   => $id_lectura,
                'referencia_tipo' => 'lectura',
                'descripcion'     => "Consumo de Gas + Cuota Admin ({$periodo})",
                'fecha'           => $fecha
            ];
        }

        // B. Movimiento de Cargos Adicionales (Si existen)
        if ($add != 0) {
            $movements[] = [
                'id_departamento' => $id_depto,
                'tipo'            => 'cargo',
                'monto'           => abs($add),
                'referencia_id'   => $id_lectura,
                'referencia_tipo' => 'lectura',
                'descripcion'     => "Cargo Adicional ({$periodo})",
                'fecha'           => $fecha
            ];
        }

        // C. Movimiento de Ajuste (Si existe)
        if ($ajuste != 0) {
            $esRecargo = ($ajuste > 0);
            $movements[] = [
                'id_departamento' => $id_depto,
                'tipo'            => $esRecargo ? 'cargo' : 'ajuste',
                'monto'           => abs($ajuste),
                'referencia_id'   => $id_lectura,
                'referencia_tipo' => 'lectura',
                'descripcion'     => $esRecargo ? "Ajuste del Periodo (Recargo - {$periodo})" : "Ajuste del Periodo (Rebaja - {$periodo})",
                'fecha'           => $fecha
            ];
        }

        if (!empty($movements)) {
            return $this->insertBatch($movements);
        }

        return true;
    }

    /**
     * Registra un pago directo/manual para un departamento.
     * Encapsula la lógica de negocio que estaba en Historial::registerPayment.
     */
    public function registrarPago($id_departamento, $monto, $descripcion = 'Pago registrado', $id_lectura = null)
    {
        return $this->insert([
            'id_departamento' => $id_departamento,
            'tipo'            => 'pago',
            'monto'           => $monto,
            'descripcion'     => $descripcion,
            'referencia_id'   => $id_lectura,
            'referencia_tipo' => $id_lectura ? 'lectura' : null,
            'fecha'           => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Registra un ajuste (recargo o rebaja) para un departamento.
     * Si monto > 0 → 'cargo' (aumenta deuda). Si monto < 0 → 'ajuste' (disminuye deuda).
     * Encapsula la lógica de negocio que estaba en Historial::registerAdjustment.
     */
    public function registrarAjuste($id_departamento, $monto, $descripcion = null)
    {
        $esRecargo = ($monto > 0);

        return $this->insert([
            'id_departamento' => $id_departamento,
            'tipo'            => $esRecargo ? 'cargo' : 'ajuste',
            'monto'           => abs($monto),
            'descripcion'     => $descripcion ?? ($esRecargo ? 'Ajuste manual (Recargo)' : 'Ajuste manual (Rebaja)'),
            'fecha'           => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Calcula los totales de movimientos de un departamento.
     * Encapsula el foreach de cálculo que estaba en Historial::getMovimientos.
     */
    public function getTotalesMovimientos($id_departamento)
    {
        $movs        = $this->getMovimientosConPeriodo($id_departamento);
        $totalCargos = 0;
        $totalAbonos = 0;

        foreach ($movs as $m) {
            if ($m['tipo'] === 'cargo') {
                $totalCargos += (float)$m['monto'];
            } elseif ($m['tipo'] === 'pago' || $m['tipo'] === 'ajuste') {
                $totalAbonos += (float)$m['monto'];
            }
        }

        return [
            'totalCargos' => $totalCargos,
            'totalAbonos' => $totalAbonos,
            'saldoNeto'   => $totalCargos - $totalAbonos,
            'movimientos' => $movs
        ];
    }

    /**
     * Obtiene el desglose del Saldo Cierre(ant):
     * - El cargo del recibo del periodo anterior (de lectura.total_a_pagar)
     * - Abonos y ajustes manuales registrados antes del inicio del periodo actual
     */
    public function getBreakdownSaldoMovs($id_departamento, $fechaInicio)
    {
        $db = \Config\Database::connect();

        $result = [];

        // 1. Obtener el periodo anterior
        $prevRow = $db->query("
            SELECT periodo, fecha_inicio, fecha_fin FROM cortes
            WHERE fecha_inicio < ? AND fecha_inicio > '0000-01-01'
            ORDER BY fecha_inicio DESC
            LIMIT 1
        ", [$fechaInicio])->getRow();

        if ($prevRow) {
            // 2. Buscar la lectura del periodo anterior para este departamento
            $lec = $db->query("
                SELECT id_lectura, lectura_ini, lectura_fin, monto, cuota_admin,
                       cargos_add, total_a_pagar, consumo_m3, consumos_litros,
                       fecha_register
                FROM lectura
                WHERE id_departamento = ? AND periodo = ?
                LIMIT 1
            ", [$id_departamento, $prevRow->periodo])->getRow();

            if ($lec) {
                // Cargo: consumo de gas
                if ((float)$lec->monto > 0) {
                    $result[] = [
                        'tipo'        => 'cargo',
                        'descripcion' => 'Gas (' . round($lec->consumo_m3, 2) . ' m³ / ' . round($lec->consumos_litros, 0) . ' lt)',
                        'monto'       => (float)$lec->monto,
                        'fecha'       => $lec->fecha_register,
                    ];
                }
                // Cargo: cuota admin
                if ((float)$lec->cuota_admin > 0) {
                    $result[] = [
                        'tipo'        => 'cargo',
                        'descripcion' => 'Cuota Administración',
                        'monto'       => (float)$lec->cuota_admin,
                        'fecha'       => $lec->fecha_register,
                    ];
                }
                // Cargo: cargos adicionales
                if (abs((float)$lec->cargos_add) > 0.01) {
                    $result[] = [
                        'tipo'        => ((float)$lec->cargos_add > 0) ? 'cargo' : 'abono',
                        'descripcion' => 'Cargos Adicionales',
                        'monto'       => abs((float)$lec->cargos_add),
                        'fecha'       => $lec->fecha_register,
                    ];
                }

                // 3. Abonos/pagos vinculados a esa lectura o registrados antes del periodo actual
                $movs = $db->query("
                    SELECT tipo, monto, fecha, descripcion
                    FROM movimientos
                    WHERE id_departamento = ?
                      AND tipo IN ('pago', 'abono')
                      AND (
                          (referencia_tipo = 'lectura' AND referencia_id = ?)
                          OR (fecha BETWEEN ? AND ?)
                          OR (fecha >= ? AND fecha < ?)
                      )
                    ORDER BY fecha ASC
                ", [
                    $id_departamento,
                    $lec->id_lectura,
                    $prevRow->fecha_inicio, $prevRow->fecha_fin,
                    $prevRow->fecha_fin, $fechaInicio
                ])->getResultArray();

                foreach ($movs as $m) {
                    $result[] = [
                        'tipo'        => 'abono',
                        'descripcion' => $m['descripcion'] ?: 'Abono',
                        'monto'       => (float)$m['monto'],
                        'fecha'       => $m['fecha'],
                    ];
                }
            }
        }

        // Fallback: si no encontramos nada por periodo, buscar movimientos previos a la fecha
        if (empty($result)) {
            $fallback = $db->query("
                SELECT tipo, monto, fecha, descripcion
                FROM movimientos
                WHERE id_departamento = ?
                  AND fecha < ?
                  AND fecha >= '2026-01-01 00:00:00'
                ORDER BY fecha DESC
                LIMIT 15
            ", [$id_departamento, $fechaInicio])->getResultArray();

            foreach ($fallback as $m) {
                $result[] = [
                    'tipo'        => $m['tipo'],
                    'descripcion' => $m['descripcion'] ?: ($m['tipo'] === 'cargo' ? 'Cargo' : 'Abono'),
                    'monto'       => (float)$m['monto'],
                    'fecha'       => $m['fecha'],
                ];
            }
        }

        return $result;
    }

}
