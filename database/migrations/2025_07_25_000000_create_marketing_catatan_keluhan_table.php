<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketing_catatan_keluhan', function (Blueprint $table) {
            $table->id();
            $table->string('perusahaan');
            $table->string('pasien_id'); // foreign key to erm_pasiens
            $table->date('visit_date');
            $table->string('unit');
            $table->string('kategori');
            $table->text('keluhan');
            $table->text('penyelesaian')->nullable();
            $table->text('rencana_perbaikan')->nullable();
            $table->date('deadline_perbaikan')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->foreign('pasien_id')->references('id')->on('erm_pasiens')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_catatan_keluhan');
    }
};
