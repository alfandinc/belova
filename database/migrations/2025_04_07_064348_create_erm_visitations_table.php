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
            $table->tinyInteger('progress')->default(1); // 1 = perawat, 2 = dokter, dll
            $table->enum('status', ['asesmen', 'cppt'])->default('asesmen');
            $table->date('tanggal_visitation');

            $table->string('no_antrian');


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
