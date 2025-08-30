<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pr_kpi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_poin');
            $table->decimal('initial_poin', 8, 2);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('pr_kpi');
    }
};
