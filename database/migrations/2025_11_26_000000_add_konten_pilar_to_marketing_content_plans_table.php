<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKontenPilarToMarketingContentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->string('konten_pilar')->nullable()->after('jenis_konten');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->dropColumn('konten_pilar');
        });
    }
}
