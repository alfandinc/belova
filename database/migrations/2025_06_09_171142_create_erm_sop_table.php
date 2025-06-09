<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateErmSopTable extends Migration
{
    public function up()
    {
        Schema::create('erm_sop', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tindakan_id');
            $table->string('nama_sop');
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(1);
            $table->timestamps();

            $table->foreign('tindakan_id')
                ->references('id')
                ->on('erm_tindakan')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_sop');
    }
}
