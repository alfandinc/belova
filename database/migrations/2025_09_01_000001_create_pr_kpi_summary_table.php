<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pr_kpi_summary', function (Blueprint $table) {
            $table->id();
            $table->string('bulan'); // YYYY-MM
            $table->double('total_kpi_poin')->default(0);
            $table->double('average_kpi_poin')->default(0);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('pr_kpi_summary');
    }
};
