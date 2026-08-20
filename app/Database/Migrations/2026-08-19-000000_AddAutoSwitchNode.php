<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAutoSwitchNode extends Migration
{
    public function up()
    {
        // Per-user "switch me to the fastest node right after login"
        // preference - see AuthController::loginAction() and the taskbar's
        // "Switch server" dropdown. A new column, not folded into
        // AddUserProfiles: that migration already ran on every live node.
        $this->forge->addColumn('user_profiles', [
            'auto_switch_node' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => true, 'default' => 0, 'after' => 'taskbar_width'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_profiles', 'auto_switch_node');
    }
}
