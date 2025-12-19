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
        if (Schema::hasTable('satusehat_encounters')) {
            Schema::table('satusehat_encounters', function (Blueprint $table) {
                if (!Schema::hasColumn('satusehat_encounters', 'status')) {
                    $table->string('status', 50)->nullable()->after('raw_response')->comment('Encounter status: arrived|in-progress|finished');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('satusehat_encounters')) {
            Schema::table('satusehat_encounters', function (Blueprint $table) {
                if (Schema::hasColumn('satusehat_encounters', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
