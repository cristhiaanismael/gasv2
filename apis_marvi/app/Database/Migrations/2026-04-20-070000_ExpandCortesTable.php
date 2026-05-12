<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandCortesTable extends Migration
{
    public function up()
    {
        $fields = [
            'fecha_inicio' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'periodo'
            ],
            'fecha_fin' => [
                'type' => 'DATE',
                'null' => true,
                'after' => 'fecha_inicio'
            ],
            'status' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'fecha_fin'
            ],
        ];
        $this->forge->addColumn('cortes', $fields);
        
        // También eliminamos la tabla periodos si existe
        if ($this->db->tableExists('periodos')) {
            $this->forge->dropTable('periodos');
        }
    }

    public function down()
    {
        $this->forge->dropColumn('cortes', ['fecha_inicio', 'fecha_fin', 'status']);
    }
}
