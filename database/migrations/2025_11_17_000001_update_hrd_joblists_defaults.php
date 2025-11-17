<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        // Update existing values: 'open' -> 'progress', 'normal' -> 'low'
        DB::table('hrd_joblists')->where('status', 'open')->update(['status' => 'progress']);
        DB::table('hrd_joblists')->where('priority', 'normal')->update(['priority' => 'low']);

        // Modify column defaults (MySQL syntax)
        // Ensure the columns remain varchar(255)
        DB::statement("ALTER TABLE `hrd_joblists` MODIFY `status` varchar(255) NOT NULL DEFAULT 'progress'");
        DB::statement("ALTER TABLE `hrd_joblists` MODIFY `priority` varchar(255) NOT NULL DEFAULT 'low'");
    }

    public function down()
    {
        // Revert defaults back to previous assumptions
        DB::statement("ALTER TABLE `hrd_joblists` MODIFY `status` varchar(255) NOT NULL DEFAULT 'open'");
        DB::statement("ALTER TABLE `hrd_joblists` MODIFY `priority` varchar(255) NOT NULL DEFAULT 'normal'");

        // Optionally revert values (best effort)
        DB::table('hrd_joblists')->where('status', 'progress')->update(['status' => 'open']);
        DB::table('hrd_joblists')->where('priority', 'low')->update(['priority' => 'normal']);
    }
};
