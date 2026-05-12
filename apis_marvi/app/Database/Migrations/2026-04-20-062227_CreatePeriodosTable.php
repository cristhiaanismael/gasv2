<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePeriodosTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_periodo' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'nombre_periodo' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'fecha_inicio' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'fecha_fin' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id_periodo', true);
        $this->forge->createTable('periodos');
    }

    public function down()
    {
        $this->forge->dropTable('periodos');
    }
}
