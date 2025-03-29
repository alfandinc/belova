<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_pasiens', function (Blueprint $table) {
            $table->id();
            $table->string('nik')->unique();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->enum('gender', ['L', 'P']);
            $table->string('marital_status');
            $table->string('pendidikan');
            $table->string('agama');
            $table->string('pekerjaan');
            $table->text('alamat');
            $table->foreignId('village_id')->constrained('area_villages');
            $table->foreignId('kelas_pasien_id')->constrained('erm_kelas_pasiens');

            $table->string('penanggung_jawab');
            $table->string('no_hp_penanggung_jawab');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('erm_pasiens');
    }
};
