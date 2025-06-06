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
        Schema::create('finance_billing', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->string('billable_id', 50); // Adjust size as needed
            $table->string('billable_type'); // Default size is fine for type
            $table->decimal('jumlah', 15, 2); // harga satuan atau total item
            $table->text('keterangan')->nullable();
            $table->decimal('diskon', 12, 2)->default(0);
            $table->enum('diskon_type', ['%', 'nominal'])->nullable();
            $table->integer('qty')->default(1);
            $table->timestamps();

            $table->foreign('visitation_id')->references('id')->on('erm_visitations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_billing');
    }
};
