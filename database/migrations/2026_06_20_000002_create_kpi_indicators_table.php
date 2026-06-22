<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiIndicatorsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('indicator_name');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('kpi_indicator_categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_indicators');
    }
}
