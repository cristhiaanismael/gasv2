<?php

namespace App\Controllers;

use App\Models\Departamentos as DepartamentoModel;
use App\Models\Lectura;
use App\Models\Movimientos;
use App\Models\Cortes;
use CodeIgniter\API\ResponseTrait;

class Departamentos extends BaseController
{
    use ResponseTrait;

    public function info($id)
    {
        $deptoModel = new DepartamentoModel();
        $lecturaModel = new Lectura();
        $movModel = new Movimientos();
        
        $data = $deptoModel->getInfoCompleta($id);

        if (empty($data)) {
            return $this->failNotFound('Departamento no encontrado: ' . $id);
        }

        // Obtener datos delegando a los Modelos (MVC Estricto)
        $data['ultima_lectura'] = floatval($lecturaModel->getUltimaFin($id));

        // Nuevo: Detectar si ya tiene lectura en el periodo actual
        $corteModel = new Cortes();
        $periodoActual = $corteModel->getActivePeriod();
        $data['lectura_actual'] = $lecturaModel->getLecturaByPeriodo($id, $periodoActual);

        $data['saldo_actual']   = $movModel->getSaldoTotal($id);

        return $this->respond($data);
    }

    public function listByBuilding($id_edificio)
    {
        $model = new DepartamentoModel();
        $data = $model->listByBuilding($id_edificio);

        return $this->respond($data);
    }
}
