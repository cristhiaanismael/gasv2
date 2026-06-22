<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;

class Clientes extends BaseController
{
    use ResponseTrait;

    public function save()
    {
        $clienteModel = new \App\Models\Clientes();
        $deptoModel = new \App\Models\Departamentos();
        
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $idCliente = $input['id_cliente'] ?? null;
        $idDepto = $input['id_departamento'] ?? null;
        
        $data = [
            'nombre'       => $input['nombre'] ?? '',
            'ape_pat'      => $input['ape_pat'] ?? '',
            'ape_mat'      => $input['ape_mat'] ?? '',
            'correo'       => $input['correo'] ?? '',
            'correo_2'     => $input['correo_2'] ?? '',
            'correo_admin' => $input['correo_admin'] ?? '',
            'telefono'     => $input['telefono'] ?? '',
            'telefono_2'   => $input['telefono_2'] ?? '',
            'convenio'     => $input['convenio'] ?? '',
            'referencia'   => $input['referencia'] ?? ''
        ];

        if ($idCliente) {
            // Update existing client
            if (!$clienteModel->update($idCliente, $data)) {
                return $this->fail('No se pudo actualizar el cliente');
            }
        } else {
            // Insert new client
            $idCliente = $clienteModel->insert($data);
            if (!$idCliente) {
                return $this->fail('No se pudo crear el cliente');
            }
        }

        // Now, assign this client to the department if idDepto is provided
        if ($idDepto) {
            if (!$deptoModel->update($idDepto, ['id_cliente' => $idCliente])) {
                return $this->fail('Se guardó el cliente pero no se pudo asociar al departamento');
            }
        }

        return $this->respond([
            'success' => true, 
            'message' => 'Cliente guardado y asignado correctamente',
            'id_cliente' => intval($idCliente)
        ]);
    }
}
