<?php

namespace App\Controllers;

use App\Models\Edificios as EdificioModel;
use CodeIgniter\API\ResponseTrait;

class Edificios extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        $model = new EdificioModel();
        
        // Obtenemos los edificios, ordenados por por número
        $data = $model->getAllOrdered();

        if (empty($data)) {
            return $this->failNotFound('No se encontraron edificios');
        }

        return $this->respond($data);
    }

    public function getConfig($id)
    {
        $model = new EdificioModel();
        try {
            $config = $model->getConfiguracion($id);
            return $this->respond($config);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
