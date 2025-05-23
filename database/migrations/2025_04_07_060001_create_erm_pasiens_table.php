<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_pasiens', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('nik')->nullable();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->string('gender');
            $table->string('agama')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('gol_darah')->nullable();
            $table->text('notes')->nullable();
            $table->string('alamat');
            $table->foreignId('village_id')->nullable()->constrained('area_villages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('no_hp');
            $table->string('no_hp2')->nullable();
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('erm_pasiens');
    }
};
