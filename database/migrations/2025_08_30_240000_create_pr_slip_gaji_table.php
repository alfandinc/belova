<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pr_slip_gaji', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('bulan'); // format: MM-YYYY or YYYY-MM
            $table->decimal('gaji_perhari', 15, 2)->nullable();
            $table->decimal('gaji_perjam', 15, 2)->nullable();
            $table->decimal('gaji_pokok', 15, 2)->nullable();
            $table->decimal('tunjangan_jabatan', 15, 2)->nullable();
            $table->decimal('uang_makan', 15, 2)->nullable();
            $table->decimal('kpi_poin', 8, 2)->nullable();
            $table->decimal('uang_kpi', 15, 2)->nullable();
            $table->decimal('jasa_medis', 15, 2)->nullable();
            $table->decimal('total_jam_lembur', 8, 2)->nullable();
            $table->decimal('uang_lembur', 15, 2)->nullable();
            $table->decimal('potongan_pinjaman', 15, 2)->nullable();
            $table->decimal('potongan_bpjs_kesehatan', 15, 2)->nullable();
            $table->decimal('potongan_jamsostek', 15, 2)->nullable();
            $table->decimal('potongan_penalty', 15, 2)->nullable();
            $table->decimal('potongan_lain', 15, 2)->nullable();
            $table->decimal('benefit_bpjs_kesehatan', 15, 2)->nullable();
            $table->decimal('benefit_jht', 15, 2)->nullable();
            $table->decimal('benefit_jkk', 15, 2)->nullable();
            $table->decimal('benefit_jkm', 15, 2)->nullable();
            $table->decimal('total_pendapatan', 15, 2)->nullable();
            $table->decimal('total_potongan', 15, 2)->nullable();
            $table->decimal('total_gaji', 15, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('pr_slip_gaji');
    }
};
