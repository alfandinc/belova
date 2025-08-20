<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeInstagramToJsonInEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->text('instagram')->nullable()->change(); // Change to TEXT for JSON array
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hrd_employee', function (Blueprint $table) {
            $table->string('instagram', 100)->nullable()->change(); // Revert to string
        });
    }
}
