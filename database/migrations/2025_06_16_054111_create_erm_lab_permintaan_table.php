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
        Schema::create('erm_lab_permintaan', function (Blueprint $table) {
            $table->id();
            $table->string('visitation_id');
            $table->unsignedBigInteger('lab_test_id');
            $table->enum('status', ['requested', 'processing', 'completed'])->default('requested');
            $table->text('hasil')->nullable();
            $table->unsignedBigInteger('dokter_id')->nullable();
            $table->timestamps();
            $table->foreign('visitation_id')->references('id')->on('erm_visitations');
            $table->foreign('lab_test_id')->references('id')->on('erm_lab_test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_lab_permintaan');
    }
};
