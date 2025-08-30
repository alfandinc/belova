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
        Schema::create('attendance_lateness_recap', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('month', 7); // Format: YYYY-MM
            $table->integer('total_late_days')->default(0);
            $table->integer('total_late_minutes')->default(0);
            $table->integer('total_overtime_minutes')->default(0);
            $table->integer('total_late_minus_overtime')->default(0);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_lateness_recap');
    }
};
