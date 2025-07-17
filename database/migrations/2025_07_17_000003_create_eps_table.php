<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('akreditasi_eps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('standar_id')->constrained('akreditasi_standars')->onDelete('cascade');
            $table->string('name');
            $table->string('kelengkapan_bukti');
            $table->integer('skor_maksimal');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('eps');
    }
};
