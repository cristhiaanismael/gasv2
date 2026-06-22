<?php

namespace App\Models;

use CodeIgniter\Model;

class ConfiguracionCobranzaModel extends Model
{
    protected $table = 'configuracion_cobranza';
    protected $primaryKey = 'id';
    protected $allowedFields = ['tipo_entidad', 'entidad_id', 'asunto', 'mensaje'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function __construct()
    {
        parent::__construct();
        $this->checkAndInitializeTable();
    }

    private function checkAndInitializeTable()
    {
        $db = \Config\Database::connect();
        
        // Crear tabla si no existe
        $query = "CREATE TABLE IF NOT EXISTS configuracion_cobranza (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_entidad ENUM('global', 'edificio', 'departamento') NOT NULL DEFAULT 'global',
            entidad_id INT NULL,
            asunto VARCHAR(255) NOT NULL,
            mensaje TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_tipo_entidad (tipo_entidad, entidad_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $db->query($query);

        // Verificar si existe la global
        $globalExists = $this->where('tipo_entidad', 'global')->first();
        if (!$globalExists) {
            $defaultSubject = "Recibo de Gas - Depto {{numero_departamento}} - {{mes_curso}}";
            $defaultMessage = "Hola {{nombre_titular}},\n\nAdjuntamos tu recibo de gas correspondiente al periodo {{mes_curso}}.\n\nTe recordamos que tienes un saldo pendiente de \${{saldo_actual}}. Por favor, realiza tu pago a la brevedad.\n\nSaludos,\nEquipo Marvifet";
            
            $this->insert([
                'tipo_entidad' => 'global',
                'entidad_id'   => null,
                'asunto'       => $defaultSubject,
                'mensaje'      => $defaultMessage
            ]);
        }
    }

    /**
     * Obtiene la plantilla más específica para un departamento.
     * Prioridad: 1. Departamento, 2. Edificio, 3. Global
     */
    public function getTemplateResolved($id_departamento, $id_edificio)
    {
        // Buscar departamento
        if ($id_departamento) {
            $tpl = $this->where('tipo_entidad', 'departamento')->where('entidad_id', $id_departamento)->first();
            if ($tpl) return $tpl;
        }

        // Buscar edificio
        if ($id_edificio) {
            $tpl = $this->where('tipo_entidad', 'edificio')->where('entidad_id', $id_edificio)->first();
            if ($tpl) return $tpl;
        }

        // Buscar global
        return $this->where('tipo_entidad', 'global')->first();
    }

    /**
     * Obtiene una plantilla para el administrador
     */
    public function getTemplateConfig($tipo, $entidad_id)
    {
        $builder = $this->where('tipo_entidad', $tipo);
        if ($tipo === 'global') {
            $builder->where('entidad_id IS NULL');
        } else {
            $builder->where('entidad_id', $entidad_id);
        }
        return $builder->first();
    }

    /**
     * Guarda o actualiza una plantilla
     */
    public function saveTemplate($tipo, $entidad_id, $asunto, $mensaje)
    {
        $existing = $this->getTemplateConfig($tipo, $entidad_id);
        
        $data = [
            'tipo_entidad' => $tipo,
            'entidad_id'   => $tipo === 'global' ? null : $entidad_id,
            'asunto'       => $asunto,
            'mensaje'      => $mensaje
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
            return $existing['id'];
        } else {
            return $this->insert($data);
        }
    }
}
