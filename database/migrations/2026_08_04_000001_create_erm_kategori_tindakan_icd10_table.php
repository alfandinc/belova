<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_kategori_tindakan_icd10', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_tindakan_id')->constrained('erm_kategori_tindakan')->onDelete('cascade');
            $table->foreignId('icd10_id')->constrained('erm_icd10')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['kategori_tindakan_id', 'icd10_id'], 'erm_kategori_icd10_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_kategori_tindakan_icd10');
    }
};
