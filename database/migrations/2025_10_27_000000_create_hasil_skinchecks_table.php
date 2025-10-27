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
    Schema::create('erm_hasil_skinchecks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('visitation_id');
            $table->string('qr_image');
            $table->string('url')->nullable();
            $table->timestamps();

            // add index and foreign key to visitation table (erm_visitations)
            $table->index('visitation_id');
            $table->foreign('visitation_id')
                ->references('id')
                ->on('erm_visitations')
                ->onDelete('cascade');
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
            $table->dropForeign(['visitation_id']);
        });
        Schema::dropIfExists('erm_hasil_skinchecks');
    }
};
