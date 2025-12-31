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
        Schema::create('erm_lab_test_obat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('lab_test_id');
            $table->unsignedBigInteger('obat_id');
            $table->decimal('dosis', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('lab_test_id')->references('id')->on('erm_lab_test')->onDelete('cascade');
            $table->foreign('obat_id')->references('id')->on('erm_obat')->onDelete('cascade');

            $table->index(['lab_test_id'], 'erm_lab_test_obat_lab_test_idx');
            $table->index(['obat_id'], 'erm_lab_test_obat_obat_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erm_lab_test_obat');
    }
};
