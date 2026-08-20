<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDashboardLayout extends Migration
{
    public function up()
    {
        // JSON blob of GridStack's own layout (per-card x/y/w/h/id) - see
        // public/assets/dashboard-grid.js. A new column, not folded into
        // AddUserProfiles: that migration already ran on every live node,
        // and CI4's migration runner has no "alter an already-applied
        // migration" story - a new one is the correct way to add a column
        // after the fact.
        $this->forge->addColumn('user_profiles', [
            'dashboard_layout' => ['type' => 'TEXT', 'null' => true, 'after' => 'taskbar_width'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('user_profiles', 'dashboard_layout');
    }
}
