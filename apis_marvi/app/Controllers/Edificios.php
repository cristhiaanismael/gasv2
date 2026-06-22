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
        
        // Obtenemos los edificios, ordenados por orden y número
        $data = $model->getAllOrdered();

        return $this->respond($data);
    }

    public function save()
    {
        $model = new EdificioModel();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $id = $input['id_edificio'] ?? null;
        
        $data = [
            'num_edificio' => $input['num_edificio'] ?? '',
            'calle'        => $input['calle'] ?? '',
            'num_ext'      => $input['num_ext'] ?? '',
            'municipio'    => $input['municipio'] ?? '',
            'colonia'      => $input['colonia'] ?? '',
            'codigo_p'     => $input['codigo_p'] ?? '',
            'id_cuenta'    => $input['id_cuenta'] ?? 1,
            'orden'        => isset($input['orden']) ? intval($input['orden']) : 0
        ];

        if ($id) {
            if ($model->update($id, $data)) {
                return $this->respond(['success' => true, 'message' => 'Edificio actualizado correctamente']);
            }
            return $this->fail('No se pudo actualizar el edificio');
        } else {
            if ($model->insert($data)) {
                return $this->respond(['success' => true, 'message' => 'Edificio guardado correctamente']);
            }
            return $this->fail('No se pudo guardar el edificio');
        }
    }

    public function reorder()
    {
        $model = new EdificioModel();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        if (!empty($input['ids']) && is_array($input['ids'])) {
            foreach ($input['ids'] as $index => $id) {
                $model->update($id, ['orden' => ($index + 1) * 10]);
            }
            return $this->respond(['success' => true, 'message' => 'Orden de edificios actualizado']);
        }
        return $this->fail('Datos de ordenamiento no válidos');
    }

    public function delete($id = null)
    {
        $model = new EdificioModel();
        if ($model->find($id)) {
            if ($model->delete($id)) {
                return $this->respond(['success' => true, 'message' => 'Edificio eliminado correctamente']);
            }
            return $this->fail('No se pudo eliminar el edificio');
        }
        return $this->failNotFound('Edificio no encontrado');
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

    public function saveConfig($id)
    {
        $model = new EdificioModel();
        $json  = $this->request->getJSON(true);

        if (!$json) {
            return $this->fail('No se recibieron datos.');
        }

        $precioLitro = isset($json['precioLitro']) ? floatval($json['precioLitro']) : null;
        $factor      = isset($json['factor'])      ? floatval($json['factor'])      : null;
        $cuotaAdmin  = isset($json['cuotaAdmin'])  ? floatval($json['cuotaAdmin'])  : null;

        if ($precioLitro === null || $factor === null || $cuotaAdmin === null) {
            return $this->fail('Todos los parámetros (precioLitro, factor, cuotaAdmin) son obligatorios.');
        }

        if ($precioLitro < 0 || $factor <= 0 || $cuotaAdmin < 0) {
            return $this->fail('Los parámetros deben ser valores positivos.');
        }

        try {
            $current  = $model->getConfiguracion($id);
            $inserted = $model->saveConfiguracion($id, $precioLitro, $factor, $cuotaAdmin, $current);

            $message = empty($inserted)
                ? 'Los valores no han cambiado.'
                : 'Se actualizaron: ' . implode(', ', $inserted) . '.';

            return $this->respond([
                'success' => true,
                'message' => $message,
                'config'  => $model->getConfiguracion($id)
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function getConfigHistory($id)
    {
        $model = new EdificioModel();
        return $this->respond($model->getHistorialConfiguracion($id));
    }
}
