<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Lecturas extends BaseController
{
    use ResponseTrait;

    /**
     * POST api/lectura
     * 
     * Registra una lectura de consumo y genera automáticamente
     * el movimiento financiero (cargo) correspondiente.
     * 
     * Flujo:
     *   1. Validar datos de entrada
     *   2. Obtener tarifa (precio_litros, factor, cuota_admin) del edificio
     *   3. Calcular consumo y monto
     *   4. INSERT lectura
     *   5. INSERT movimiento tipo 'cargo'
     *   6. Responder con resumen
     */    public function registrar()
    {
        $lecturaModel = new \App\Models\Lectura();

        // Soporta tanto JSON como Form-Data
        $payload = [
            'id_departamento' => $this->request->getPost('id_departamento'),
            'lectura_ini'     => $this->request->getPost('lectura_ini'),
            'lectura_fin'     => $this->request->getPost('lectura_fin'),
            'fecha_registro'  => $this->request->getPost('fecha_registro') ?? date('Y-m-d'),
            'foto'            => ''
        ];

        if (!$payload['id_departamento']) {
            $json = $this->request->getJSON();
            if ($json) {
                $payload['id_departamento'] = $json->id_departamento ?? null;
                $payload['lectura_ini']     = $json->lectura_ini ?? 0;
                $payload['lectura_fin']     = $json->lectura_fin ?? 0;
                $payload['fecha_registro']  = $json->fecha_registro ?? date('Y-m-d');
            }
        }

        if (!$payload['id_departamento']) return $this->fail('id_departamento es requerido.');

        // Procesar Archivo
        $file = $this->request->getFile('foto');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $uploadPath = FCPATH . 'uploads/lecturas';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            $file->move($uploadPath, $newName);
            $payload['foto'] = $newName;
        }

        try {
            // Delegar toda la lógica al Modelo (MVC Estricto)
            $result = $lecturaModel->registrarLectura($payload);

            return $this->respondCreated([
                'status'  => 'success',
                'message' => 'Lectura registrada correctamente',
                ...$result
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function ultima($id_departamento)
    {
        $model = new \App\Models\Lectura();
        $ultima = $model->getUltimaByDepto($id_departamento);

        if (!$ultima) return $this->respond(['lectura_fin' => 0, 'periodo' => null]);

        return $this->respond($ultima);
    }
}
