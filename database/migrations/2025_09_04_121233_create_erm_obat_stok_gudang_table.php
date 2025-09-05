<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('erm_obat_stok_gudang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('erm_obat')->onDelete('cascade');
            $table->foreignId('gudang_id')->constrained('erm_gudang')->onDelete('cascade');
            $table->decimal('stok', 10, 2)->default(0);
            $table->decimal('min_stok', 10, 2)->default(0);
            $table->decimal('max_stok', 10, 2)->default(0);
            $table->string('batch')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('rak')->nullable();
            $table->string('lokasi')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint untuk memastikan tidak ada duplikasi obat di gudang yang sama dengan batch yang sama
            $table->unique(['obat_id', 'gudang_id', 'batch'], 'unique_obat_gudang_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('erm_obat_stok_gudang');
    }
};
