<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hrd_pengajuan_ganti_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hrd_employee')->onDelete('cascade');
            $table->date('tanggal_shift'); // Date of the shift to be changed
            $table->foreignId('shift_lama_id')->nullable()->constrained('hrd_shifts')->onDelete('set null'); // Current shift
            $table->foreignId('shift_baru_id')->constrained('hrd_shifts')->onDelete('cascade'); // Requested new shift
            $table->text('alasan'); // Reason for shift change
            $table->enum('status_manager', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('notes_manager')->nullable();
            $table->timestamp('tanggal_persetujuan_manager')->nullable();
            $table->enum('status_hrd', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('notes_hrd')->nullable();
            $table->timestamp('tanggal_persetujuan_hrd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hrd_pengajuan_ganti_shift');
    }
};
