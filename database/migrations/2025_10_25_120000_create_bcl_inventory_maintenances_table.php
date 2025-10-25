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
        Schema::create('bcl_inventory_maintenances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_id')->nullable()->index();
            $table->string('inv_number')->nullable()->index();
            $table->date('tanggal');
            $table->text('description');
            $table->decimal('cost', 12, 2)->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('doc_id')->nullable()->index();
            $table->unsignedBigInteger('journal_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bcl_inventory_maintenances');
    }
};
