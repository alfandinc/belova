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
        Schema::create('finance_pengajuan_dana', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode_pengajuan')->unique();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('division_id')->nullable();
            $table->date('tanggal_pengajuan')->nullable();
            $table->string('jenis_pengajuan')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('draft');
            $table->string('nama_bank')->nullable();
            $table->string('no_rekening')->nullable();
            $table->string('atas_nama')->nullable();
            $table->string('bukti_transaksi')->nullable();
            $table->timestamps();

            // indexes
            $table->index('employee_id');
            $table->index('division_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('finance_pengajuan_dana');
    }
};
