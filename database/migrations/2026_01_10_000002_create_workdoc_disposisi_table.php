<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workdoc_disposisi', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_terima')->nullable();
            $table->json('disposisi_pimpinan')->nullable();
            $table->json('tujuan_disposisi')->nullable();
            $table->text('catatan')->nullable();
            $table->date('tanggal_dibaca')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workdoc_disposisi');
    }
};
