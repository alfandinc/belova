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
        Schema::table('erm_hasil_skinchecks', function (Blueprint $table) {
            $table->text('decoded_text')->nullable()->after('qr_image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('erm_hasil_skinchecks', function (Blueprint $table) {
            $table->dropColumn('decoded_text');
        });
    }
};
