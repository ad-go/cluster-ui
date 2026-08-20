<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserProfiles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'phone'          => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'avatar'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'language'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'theme'          => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'default' => 'light'],
            'navbar_height'  => ['type' => 'INT', 'constraint' => 5, 'null' => true],
            'taskbar_width'  => ['type' => 'INT', 'constraint' => 5, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('user_profiles', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_profiles', true);
    }
}
