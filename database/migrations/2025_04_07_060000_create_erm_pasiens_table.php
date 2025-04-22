<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_pasiens', function (Blueprint $table) {
            $table->string('id', 6)->primary();
            $table->string('nik')->unique();
            $table->string('nama');
            $table->date('tanggal_lahir');
            $table->string('gender');
            $table->string('agama');
            $table->string('marital_status');
            $table->string('pendidikan');
            $table->string('pekerjaan');
            $table->string('gol_darah');
            $table->text('notes')->nullable();
            $table->string('alamat');
            $table->foreignId('village_id')->nullable()->constrained('area_villages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('no_hp');
            $table->string('no_hp2');
            $table->string('email');
            $table->string('instagram');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('erm_pasiens');
    }
};
