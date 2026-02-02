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
            if (!Schema::hasColumn('running_pesertas', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('status');
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
            if (Schema::hasColumn('running_pesertas', 'verified_at')) {
                $table->dropColumn('verified_at');
            }
        });
    }
};
