<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('erm_pasien_merchandises', function (Blueprint $table) {
            $table->id();
            $table->string('pasien_id');
            $table->foreignId('merchandise_id')->constrained('erm_merchandises')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('given_by_user_id')->nullable();
            $table->timestamp('given_at')->nullable();
            $table->timestamps();

            $table->index(['pasien_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('erm_pasien_merchandises');
    }
};
