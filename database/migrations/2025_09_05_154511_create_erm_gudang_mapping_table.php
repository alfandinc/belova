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
        Schema::create('erm_gudang_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type'); // 'resep', 'tindakan'
            $table->unsignedBigInteger('gudang_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('gudang_id')->references('id')->on('erm_gudang')->onDelete('cascade');
            
            // Unique constraint: only one active mapping per transaction type
            $table->unique(['transaction_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_gudang_mapping');
    }
};
