<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiIndicatorCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_indicator_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->decimal('weight_percentage', 5, 2)->default(0);
            $table->enum('evaluator_type', ['direct_parent', 'specific_position', 'bottom_up'])->default('direct_parent');
            $table->unsignedBigInteger('evaluator_position_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('evaluator_position_id')->references('id')->on('hrd_position')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_indicator_categories');
    }
}
