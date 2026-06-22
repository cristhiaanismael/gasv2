<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\Cortes;
use App\Models\Edificios;
use App\Models\Departamentos;

class Configuracion extends BaseController
{
    use ResponseTrait;

    public function getPeriodos()
    {
        $model = new Cortes();
        // Cambiamos a orden cronológico (por fecha de inicio)
        // para que la lista sea intuitiva independientemente del orden de registro
        $data = $model->getAllOrdered();
        return $this->respond($data);
    }

    public function getPeriodoActivo()
    {
        $model = new Cortes();
        $periodo = $model->getActivePeriod();
        return $this->respond(['periodo' => $periodo]);
    }

    public function addPeriodo()
    {
        $model = new Cortes();
        $json  = $this->request->getJSON();
        
        $nombre = trim($json->nombre_periodo);
        $inicio = $json->fecha_inicio;
        $fin    = $json->fecha_fin;

        // Delegar validación de traslape al Modelo
        $overlap = $model->existsOverlap($inicio, $fin);

        if ($overlap) {
            return $this->fail('Este periodo se traslapa con uno existente: ' . $overlap['periodo']);
        }

        $data = [
            'periodo'      => $nombre,
            'fecha_inicio' => $inicio,
            'fecha_fin'    => $fin,
            'status'       => 1
        ];

        if ($model->registrarNuevoPeriodo($data)) {
            return $this->respondCreated(['status' => 'success', 'message' => 'Periodo creado correctamente']);
        }

        return $this->fail('Error al crear el periodo');
    }

    public function addEdificio()
    {
        $model = new Edificios();
        $data  = $this->request->getJSON(true);
        
        if ($model->insert($data)) {
            return $this->respondCreated(['status' => 'success', 'message' => 'Edificio creado correctamente']);
        }

        return $this->fail('Error al crear el edificio');
    }

    public function massAddDepartamentos()
    {
        $model = new Departamentos();
        $json  = $this->request->getJSON();
        
        $id_edificio   = $json->id_edificio;
        $departamentos = $json->departamentos;

        // Delegar inserción masiva al Modelo
        $inserted = $model->registerBatch($id_edificio, $departamentos);

        return $this->respond([
            'status'  => 'success', 
            'message' => "Se registraron $inserted departamentos"
        ]);
    }
}
