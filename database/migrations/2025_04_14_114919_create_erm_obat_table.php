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
        Schema::create('erm_obat', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('nama')->nullable();
            $table->string('satuan')->nullable();
            $table->string('dosis')->nullable();
            $table->string('harga_umum')->nullable();
            $table->string('harga_inhealth')->nullable();
            $table->string('stok')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('erm_supplier')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_obat');
    }
};
