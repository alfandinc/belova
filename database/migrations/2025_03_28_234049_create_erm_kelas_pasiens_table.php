<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erm_kelas_pasiens', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas'); // Example: VIP, Kelas 1, Kelas 2, etc.
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('erm_kelas_pasiens');
    }
};
