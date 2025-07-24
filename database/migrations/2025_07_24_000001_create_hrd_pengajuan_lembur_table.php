<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hrd_pengajuan_lembur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->decimal('total_jam', 5, 2)->nullable();
            $table->text('alasan');
            $table->string('status_manager')->nullable();
            $table->text('notes_manager')->nullable();
            $table->timestamp('tanggal_persetujuan_manager')->nullable();
            $table->string('status_hrd')->nullable();
            $table->text('notes_hrd')->nullable();
            $table->timestamp('tanggal_persetujuan_hrd')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrd_pengajuan_lembur');
    }
};
