<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_kode_tindakan_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kode_tindakan_id')->constrained('erm_kode_tindakan')->onDelete('cascade');
            $table->foreignId('kategori_tindakan_id')->constrained('erm_kategori_tindakan')->onDelete('cascade');
            $table->timestamps();
            // Use a shorter index name to avoid MySQL identifier length limits
            $table->unique(['kode_tindakan_id', 'kategori_tindakan_id'], 'erm_kode_tind_kat_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_kode_tindakan_kategori');
    }
};
