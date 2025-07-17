<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('akreditasi_standars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bab_id')->constrained('akreditasi_babs')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('standars');
    }
};
