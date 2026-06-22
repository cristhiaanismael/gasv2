<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ConfiguracionCobranzaModel;
use App\Models\Edificios;
use App\Models\Departamentos;

class ConfiguracionCobranza extends BaseController
{
    protected $configModel;

    public function __construct()
    {
        $this->configModel = new ConfiguracionCobranzaModel();
    }

    /**
     * POST /configuracion-cobranza/save-template
     */
    public function saveTemplate()
    {
        $tipo = $this->request->getPost('tipo_entidad'); // 'global', 'edificio', 'departamento'
        $entidad_id = $this->request->getPost('entidad_id');
        $asunto = $this->request->getPost('asunto');
        $mensaje = $this->request->getPost('mensaje');

        if (!$tipo || !$asunto || !$mensaje) {
            return $this->response->setJSON(['status' => false, 'message' => 'Faltan datos requeridos.']);
        }

        if ($tipo !== 'global' && empty($entidad_id)) {
            return $this->response->setJSON(['status' => false, 'message' => 'Debe seleccionar un edificio o departamento.']);
        }

        try {
            $this->configModel->saveTemplate($tipo, $entidad_id, $asunto, $mensaje);
            return $this->response->setJSON(['status' => true, 'message' => 'Plantilla guardada correctamente.']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => false, 'message' => 'Error al guardar plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /configuracion-cobranza/get-template
     */
    public function getTemplate()
    {
        $tipo = $this->request->getGet('tipo_entidad');
        $entidad_id = $this->request->getGet('entidad_id');

        if (!$tipo) {
            return $this->response->setJSON(['status' => false, 'message' => 'Tipo no especificado.']);
        }

        try {
            $template = $this->configModel->getTemplateConfig($tipo, $entidad_id);
            if ($template) {
                return $this->response->setJSON(['status' => true, 'data' => $template]);
            } else {
                return $this->response->setJSON(['status' => false, 'message' => 'No hay plantilla configurada para esta entidad.']);
            }
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => false, 'message' => 'Error al obtener plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * GET /configuracion-cobranza/get-options
     * Devuelve la lista de edificios o departamentos para los selects
     */
    public function getOptions()
    {
        $tipo = $this->request->getGet('tipo');
        $parent_id = $this->request->getGet('parent_id'); // if getting deptos for an edificio

        if ($tipo === 'edificios') {
            $model = new Edificios();
            $data = $model->findAll();
            return $this->response->setJSON(['status' => true, 'data' => $data]);
        } elseif ($tipo === 'departamentos') {
            if (!$parent_id) return $this->response->setJSON(['status' => false, 'message' => 'Falta parent_id']);
            $model = new Departamentos();
            $data = $model->where('id_edificio', $parent_id)->findAll();
            return $this->response->setJSON(['status' => true, 'data' => $data]);
        }

        return $this->response->setJSON(['status' => false, 'message' => 'Tipo inválido']);
    }
}
