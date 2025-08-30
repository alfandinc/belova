<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('pr_omset_bulanan', function (Blueprint $table) {
            $table->id();
            $table->string('bulan'); // format: MM-YYYY or YYYY-MM
            $table->unsignedBigInteger('insentif_omset_id');
            $table->decimal('nominal', 15, 2);
            $table->timestamps();

            $table->foreign('insentif_omset_id')->references('id')->on('pr_insentif_omset')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('pr_omset_bulanan');
    }
};
