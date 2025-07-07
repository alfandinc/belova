<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erm_hasil_lis', function (Blueprint $table) {
            $table->string('kode')->primary();
            $table->string('visitation_id');
            $table->string('kode_lis')->nullable();          
            $table->string('header')->nullable();
            $table->string('sub_header')->nullable();
            $table->string('nama_test')->nullable();
            $table->string('hasil')->nullable();
            $table->string('flag')->nullable();
            $table->string('metode')->nullable();
            $table->string('nilai_rujukan')->nullable();
            $table->string('satuan')->nullable();
            

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_hasil_lis');
    }
};
