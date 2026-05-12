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
                       ->get()
                       ->getRowArray();

        return floatval($result['saldo'] ?? 0);
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
        return $this->select('movimientos.*, COALESCE(lectura.periodo, "Ajuste Manual") as periodo_nombre')
                    ->join('lectura', 'movimientos.referencia_id = lectura.id_lectura AND movimientos.referencia_tipo = \'lectura\'', 'left')
                    ->where('movimientos.id_departamento', $id_departamento)
                    ->orderBy('movimientos.fecha', 'DESC')
                    ->orderBy('movimientos.id_movimiento', 'DESC')
                    ->findAll();
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
}
