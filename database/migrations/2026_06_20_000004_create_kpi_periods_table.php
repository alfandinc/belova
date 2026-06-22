<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKpiPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('kpi_periods', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->enum('status', ['started', 'open', 'closed'])->default('started');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kpi_periods');
    }
}
