<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Use raw statements to avoid needing doctrine/dbal dependency
        DB::statement('ALTER TABLE satusehat_locations MODIFY COLUMN latitude DOUBLE NULL');
        DB::statement('ALTER TABLE satusehat_locations MODIFY COLUMN longitude DOUBLE NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE satusehat_locations MODIFY COLUMN latitude DECIMAL(10,7) NULL');
        DB::statement('ALTER TABLE satusehat_locations MODIFY COLUMN longitude DECIMAL(10,7) NULL');
    }
};
