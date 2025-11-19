<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusReviewToErmPasiensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            $table->string('status_review')->nullable()->after('status_akses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_pasiens', function (Blueprint $table) {
            if (Schema::hasColumn('erm_pasiens', 'status_review')) {
                $table->dropColumn('status_review');
            }
        });
    }
}
