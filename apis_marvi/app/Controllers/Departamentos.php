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
        $raw = $model->listByBuilding($id_edificio);
        
        $data = [];
        foreach ($raw as $row) {
            $clienteFull = null;
            if ($row['id_cliente']) {
                $clienteFull = trim(($row['cliente_nombre'] ?? '') . ' ' . ($row['cliente_ape_pat'] ?? '') . ' ' . ($row['cliente_ape_mat'] ?? ''));
            }
            $data[] = [
                'id'          => intval($row['id_departamento']),
                'numero'      => $row['num_departamento'],
                'id_edificio' => intval($row['id_edificio']),
                'id_cliente'  => $row['id_cliente'] ? intval($row['id_cliente']) : null,
                'cliente'     => $clienteFull,
                'ref'         => $row['cliente_referencia'],
                'contacto'    => $row['cliente_correo'] ?: $row['cliente_telefono'],
                'convenio'    => $row['cliente_convenio'],
                'num_edi'     => $row['num_edificio'],
                'calle'       => $row['calle'],
                'num_ext'     => $row['num_ext'],
                'municipio'   => $row['municipio'],
                'colonia'     => $row['colonia'],
                'codigo_p'    => $row['codigo_p'],
                
                // Detailed client fields
                'cliente_nombre'       => $row['cliente_nombre'],
                'cliente_ape_pat'      => $row['cliente_ape_pat'],
                'cliente_ape_mat'      => $row['cliente_ape_mat'],
                'cliente_correo'       => $row['cliente_correo'],
                'cliente_correo_2'     => $row['cliente_correo_2'],
                'cliente_correo_admin' => $row['cliente_correo_admin'],
                'cliente_telefono'     => $row['cliente_telefono'],
                'cliente_telefono_2'   => $row['cliente_telefono_2']
            ];
        }

        return $this->respond($data);
    }

    public function save()
    {
        $model = new DepartamentoModel();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $id = $input['id_departamento'] ?? null;
        
        $data = [
            'num_departamento' => $input['num_departamento'] ?? '',
            'id_edificio'      => $input['id_edificio'] ?? 0
        ];

        if (isset($input['id_cliente'])) {
            $data['id_cliente'] = $input['id_cliente'] ?: null;
        }

        if ($id) {
            if ($model->update($id, $data)) {
                return $this->respond(['success' => true, 'message' => 'Departamento actualizado correctamente']);
            }
            return $this->fail('No se pudo actualizar el departamento');
        } else {
            if ($model->insert($data)) {
                return $this->respond(['success' => true, 'message' => 'Departamento guardado correctamente']);
            }
            return $this->fail('No se pudo guardar el departamento');
        }
    }

    public function migrate()
    {
        $model = new DepartamentoModel();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $edificioDestino = $input['edificioDestino'] ?? null;
        $deptos = $input['deptos'] ?? null;

        if (!$edificioDestino || empty($deptos) || !is_array($deptos)) {
            return $this->fail('Datos de migración incompletos o inválidos');
        }

        foreach ($deptos as $id) {
            $model->update($id, ['id_edificio' => $edificioDestino]);
        }

        return $this->respond(['success' => true, 'message' => 'Departamentos migrados correctamente']);
    }

    public function delete($id = null)
    {
        $model = new DepartamentoModel();
        if ($model->find($id)) {
            if ($model->delete($id)) {
                return $this->respond(['success' => true, 'message' => 'Departamento eliminado correctamente']);
            }
            return $this->fail('No se pudo eliminar el departamento');
        }
        return $this->failNotFound('Departamento no encontrado');
    }
}
