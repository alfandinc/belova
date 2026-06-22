<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiPositionIndicatorsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_position_indicators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('indicator_id');
            $table->decimal('weight_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('hrd_position')->onDelete('cascade');
            $table->foreign('indicator_id')->references('id')->on('kpi_indicators')->onDelete('cascade');
            $table->unique(['position_id', 'indicator_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_position_indicators');
    }
}
