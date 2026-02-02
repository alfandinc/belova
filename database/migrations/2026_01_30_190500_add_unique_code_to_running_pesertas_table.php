<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (!Schema::hasColumn('running_pesertas', 'unique_code')) {
                $table->string('unique_code')->nullable()->unique()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('running_pesertas', function (Blueprint $table) {
            if (Schema::hasColumn('running_pesertas', 'unique_code')) {
                $table->dropUnique(['unique_code']);
                $table->dropColumn('unique_code');
            }
        });
    }
};
