<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('erm_icd10', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->text('description');
            $table->string('category')->nullable(); // corresponds to ICD10_2010
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erm_icd10');
    }
};
