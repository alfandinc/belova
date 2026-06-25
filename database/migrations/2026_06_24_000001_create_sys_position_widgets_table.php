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
        Schema::create('sys_position_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('hrd_position')->onDelete('cascade');
            $table->foreignId('widget_id')->constrained('sys_dashboard_widgets')->onDelete('cascade');
            $table->integer('order_index')->default(0);
            $table->integer('column_span')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sys_position_widgets');
    }
};
