<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sys_position_widgets', function (Blueprint $table) {
            $table->integer('row_index')->default(1)->after('position_id');
        });
    }

    public function down(): void
    {
        Schema::table('sys_position_widgets', function (Blueprint $table) {
            $table->dropColumn('row_index');
        });
    }
};