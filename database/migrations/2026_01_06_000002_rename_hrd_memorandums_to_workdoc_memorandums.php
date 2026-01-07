<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hrd_memorandums')) {
            Schema::rename('hrd_memorandums', 'workdoc_memorandums');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workdoc_memorandums')) {
            Schema::rename('workdoc_memorandums', 'hrd_memorandums');
        }
    }
};
