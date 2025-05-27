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
        Schema::create('erm_visitations', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('pasien_id', 6)->nullable();
            $table->foreignId('metode_bayar_id')->nullable()->constrained('erm_metode_bayar')->nullOnDelete();
            $table->foreignId('dokter_id')->nullable()->constrained('erm_dokters')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->tinyInteger('status_kunjungan')->default(0);
            $table->enum('status_dokumen', ['asesmen', 'cppt'])->nullable();;
            $table->date('tanggal_visitation')->nullable();
            $table->integer('no_antrian')->nullable();

            $table->timestamps();


            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_visitations');
    }
};
