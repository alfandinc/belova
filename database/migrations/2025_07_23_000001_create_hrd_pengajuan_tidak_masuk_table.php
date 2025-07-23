<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hrd_pengajuan_tidak_masuk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('jenis', ['sakit', 'izin']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('total_hari');
            $table->string('alasan');
            $table->enum('status_manager', ['menunggu', 'disetujui', 'ditolak'])->nullable();
            $table->text('notes_manager')->nullable();
            $table->timestamp('tanggal_persetujuan_manager')->nullable();
            $table->enum('status_hrd', ['menunggu', 'disetujui', 'ditolak'])->nullable();
            $table->text('notes_hrd')->nullable();
            $table->timestamp('tanggal_persetujuan_hrd')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hrd_pengajuan_tidak_masuk');
    }
};
