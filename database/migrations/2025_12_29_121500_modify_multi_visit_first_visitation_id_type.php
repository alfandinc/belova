<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change first_visitation_id to varchar to support string visitation IDs
        DB::statement("ALTER TABLE `erm_multi_visit_usages` MODIFY `first_visitation_id` VARCHAR(64) NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE `erm_multi_visit_usages` MODIFY `first_visitation_id` BIGINT UNSIGNED NULL;");
    }
};
