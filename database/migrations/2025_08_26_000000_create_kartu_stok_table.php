<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('erm_kartu_stok', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obat_id');
            $table->dateTime('tanggal');
            $table->enum('tipe', ['masuk', 'keluar']);
            $table->integer('qty');
            $table->integer('stok_setelah');
            $table->string('ref_type')->nullable(); // faktur/resep
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();

            $table->foreign('obat_id')->references('id')->on('erm_obat');
        });
    }

    public function down()
    {
    Schema::dropIfExists('erm_kartu_stok');
    }
};
