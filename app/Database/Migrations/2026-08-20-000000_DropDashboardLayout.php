<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropDashboardLayout extends Migration
{
    public function up()
    {
        // dashboard_layout (added by AddDashboardLayout) was GridStack's
        // own per-card x/y/w/h/id JSON blob, for a six-card draggable
        // dashboard layout that was already replaced by a single-purpose
        // network view before this column's own migration ever shipped
        // (see Dashboard.php's own header comment) - public/assets/
        // dashboard-grid.js, the file this column's data was for, never
        // existed in this repo at all. Nothing has read or written this
        // column since; found live 2026-08-20 auditing for exactly this.
        //
        // Wrapped in try/catch, not a bare call - found live 2026-08-20 on
        // beta: SQLite's own ALTER TABLE ... DROP COLUMN support only
        // arrived in 3.35.0 (2021), and beta's bundled sqlite3 extension is
        // 3.7.17 (2013) - a hard "syntax error" there, not a graceful
        // no-op. The column is a harmless unused leftover either way (never
        // read from or written to), so a node too old to drop it should
        // still finish this migration successfully and move on, rather
        // than block every later `spark migrate` run on this one column
        // forever.
        try {
            $this->forge->dropColumn('user_profiles', 'dashboard_layout');
        } catch (\Throwable $e) {
            log_message('warning', 'DropDashboardLayout: could not drop user_profiles.dashboard_layout (likely SQLite < 3.35, no DROP COLUMN support) - left in place, harmless: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $this->forge->addColumn('user_profiles', [
            'dashboard_layout' => ['type' => 'TEXT', 'null' => true, 'after' => 'taskbar_width'],
        ]);
    }
}
