<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('hrd_employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('shift_id');
            $table->date('date');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('hrd_employee')->onDelete('cascade');
            $table->foreign('shift_id')->references('id')->on('hrd_shifts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('hrd_employee_schedules');
    }
};
