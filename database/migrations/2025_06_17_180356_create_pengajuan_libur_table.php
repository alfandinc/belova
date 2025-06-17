<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrd_pengajuan_libur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrd_employee')->onDelete('cascade');
            $table->enum('jenis_libur', ['cuti_tahunan', 'ganti_libur']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('total_hari');
            $table->text('alasan');
            $table->enum('status_manager', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('notes_manager')->nullable();
            $table->timestamp('tanggal_persetujuan_manager')->nullable();
            $table->enum('status_hrd', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('notes_hrd')->nullable();
            $table->timestamp('tanggal_persetujuan_hrd')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hrd_pengajuan_libur');
    }
};