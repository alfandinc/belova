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
            if (!Schema::hasColumn('running_pesertas', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('id');
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
            if (Schema::hasColumn('running_pesertas', 'registered_at')) {
                $table->dropColumn('registered_at');
            }
        });
    }
};
