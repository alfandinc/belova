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
        Schema::create('erm_billing', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            // Explicitly define the size of transaksible_id
            $table->string('billable_id', 50); // Adjust size as needed
            $table->string('billable_type'); // Default size is fine for type
            $table->decimal('jumlah', 15, 2); // harga satuan atau total item
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_billing');
    }
};
